<?php
namespace x51\classes\frontend;

use \JShrink\Minifier;
use \ScssPhp\ScssPhp\Compiler;
use \Tholu\Packer\Packer;
use \x51\functions\funcArray;

/**
Список CSS складываем в глобальный массив $_stylesheets
Список JS складываем в глоб массив $_javascripts
 */

class Bender
{
    // JS minifier, can be "packer" or "jshrink"
    public $jsmin = "packer";

    // Packed file time to live in sec (-1 = never recompile, 0 = always recompile, default: 3600)
    public $ttl = 3600;
    // Project's root dir
    public $docRootDir;
    public $formatter = '\\ScssPhp\\ScssPhp\\Formatter\\Nested';
    /*Five formatters are included:
    ScssPhp\ScssPhp\Formatter\Expanded
    ScssPhp\ScssPhp\Formatter\Nested (default)
    ScssPhp\ScssPhp\Formatter\Compressed
    ScssPhp\ScssPhp\Formatter\Compact
    ScssPhp\ScssPhp\Formatter\Crunched
     */
    public $functionsConfig = '';
    public $variablesConfig = '';

    protected $scss = false;
    private $version_key = 'v';

    /**
     *
     * @param array $arImportPath - массив с файловыми путями
     * @param number $TTL - время актуальности сжатых файлов
     */
    public function __construct($arImportPath = array(), $TTL = 3600)
    {
        $this->ttl = $TTL;
        $this->arImportPath = $arImportPath;
        $this->docRootDir = defined('HOME_DIR') ? HOME_DIR : $_SERVER['DOCUMENT_ROOT'];
        $this->scss = false;
        $this->functionsConfig = __DIR__ . '/functions.php';
        $this->variablesConfig = __DIR__ . '/variables.php';
    } // end construct

    protected function getSCSS()
    { // возвращает препроцессор SCSS
        if (!$this->scss) {
            $this->scss = new Compiler();
            if ($this->arImportPath) {
                $this->scss->setImportPaths($this->arImportPath);
            }
            $this->scss->setFormatter($this->formatter);

            if ($this->functionsConfig && file_exists($this->functionsConfig)) {
                $arFunc = include $this->functionsConfig;
                if ($arFunc) {
                    foreach ($arFunc as $name => $func) {
                        $this->scss->registerFunction($name, $func);
                    }
                }
            }

            if ($this->variablesConfig && file_exists($this->variablesConfig)) {
                $arVar = include $this->variablesConfig;
                $this->scss->setVariables($arVar);
            }
        }
        return $this->scss;
    } // end getSCSS

    /** из относительного пути делает почти абсолютный
     * $url = 'http://www.example.com/something/../else';
     * echo canonicalize($url); http://www.example.com/else
     *
     * @param string $address
     * @return string
     */
    public function canonicalize($address)
    {
        $address = explode('/', str_replace('//', '/', $address));
        $keys = array_keys($address, '..');
        foreach ($keys as $keypos => $key) {
            array_splice($address, $key - ($keypos * 2 + 1), 2);
        }
        $address = implode('/', $address);
        $address = str_replace('./', '', $address);
        return $address;
    }

    /** имя файла относительно doc root
     *
     * @param string $str
     * @param string $filename
     * @return string
     */
    public function cssUrlCorrection($str, $filename)
    { // преобразует относительный url в абсолютный относительно $filename
        $dirFile = dirname($filename) . '/';
        $arMatches = array();
        if (preg_match_all('/url\s*\(\s*[\'"]*\s*([a-zA-z\.\/0-9\-?&=]+)\s*[\'"]*\s*\)/', $str, $arMatches, PREG_PATTERN_ORDER)) {
            $arFrom = array();
            $arTo = array();

            $arMatches[1] = array_unique($arMatches[1]); // необходимо убрать повторяющиеся элементы
            $docRoot = $this->docRootDir(); // папка публикации
            $lenDocRoot = strlen($docRoot);
            foreach ($arMatches[1] as $findUrl) {
                $arFrom[] = $findUrl;
                $pathAbs = $this->canonicalize($dirFile . $findUrl); // абсолютный путь к файлу
                $posRootDir = strpos($pathAbs, $docRoot); // проверим наличие папки публикации в пути к файлу
                if ($posRootDir !== false && $posRootDir == 0) { // файл находится в папке публикации - путь можно скорректировать относительно папки публикации
                    $arTo[] = '/' . substr($pathAbs, $lenDocRoot);
                } else { // файл вне папки публикации - оставим его
                    //$arTo[] = $pathAbs;
                    $arTo[] = $findUrl;
                }
            }
            return str_replace($arFrom, $arTo, $str);
        }
        return $str;
    } // end cssUrlCorrection

    /** создает файл со упакованными данными
     * Ресурсы будут помещены в $filename
     * Расширение без точки. css или js
     *
     * @param array $arFiles
     * @param string $ext
     * @param string $outputFilename
     */
    public function createPacked(array $arFiles, $ext, $outputFilename)
    {
        $pathOutputFilename = $this->performFilename($outputFilename);
        $handler = fopen($pathOutputFilename, 'w+');
        foreach ($arFiles as $fn) {
            if (!file_exists($fn)) {
                $fn = $this->docRootDir() . $fn;
            }

            $content = file_get_contents($fn);
            if ($ext == 'css' || $ext == 'scss') {
                // подмена url картинок
                //$this->cssUrlCorrection
                $packed = $this->getMinifyCSS($this->cssUrlCorrection($content, $fn));
            }
            if ($ext == 'js') {
                $packed = $this->getMinifyJS($content) . "\r\n;\r\n";

            }
            fwrite($handler, $packed);
        }
        fclose($handler);
    } // end createPacked

    // Print output for CSS or Javascript
    public function output($output, $arFileList = false)
    {
        $output = ltrim($output, './');
        $ext = $this->get_ext($output);
        if ($this->packedIsOld($output)) {
            $this->createPacked($this->getFiles($ext, $arFileList), $ext, $output);
        }

        if ($ext == 'css' || $ext == 'scss') {
            return '<link href="' . $this->get_src($output) . '" rel="stylesheet" type="text/css"/>';
        }
        if ($ext == 'js') {
            return '<script type="text/javascript" src="' . $this->get_src($output) . '"></script>';
        }
    } // end output

    /** отложенная загрузка js-файла - необходимо, чтобы был подключен файл func.js до вызова
     *
     * @param unknown $output
     * @param string $arFileList
     * @return string
     */
    public function outputLazzy($output, $arFileList = false)
    {
        $output = ltrim($output, './');
        $ext = $this->get_ext($output);
        // формирование файла
        if ($this->packedIsOld($output)) {
            $this->createPacked($this->getFiles($ext, $arFileList), $ext, $output);
        }

        if ($ext == 'js') {
            return '<script>$(document).ready(function () {
				jsLoad("' . $this->get_src($output) . '");
			});</script>';
        }
        if ($ext == 'css' || $ext == 'scss') {
            return '<link href="' . $this->get_src($output) . '" rel="stylesheet" type="text/css"/>';
        }
    } // end lazzy

    protected function docRootDir()
    {
        return $this->docRootDir;
    }

    // Get extension in lowercase
    protected function get_ext($src)
    {
        return strtolower(pathinfo($src, PATHINFO_EXTENSION));
    }

    /**
     * returns src for resource due to filemtime
     */
    protected function get_src($output)
    {
        $path = $this->docRootDir();
        return '/' . $output . '?' . $this->version_key . '=' . filemtime($path . "/" . $output);
    }

    protected function performFilename($filename)
    {
        if (substr($filename, 0, 1) == DIRECTORY_SEPARATOR) {
            return $filename;
        } else {
            return $this->docRootDir() . '/' . $filename;
        }
    } // end performFilename

    /** возвращает сжатое содержимое $str
     *
     * @param unknown $str
     * @return unknown
     */
    protected function getMinifyCSS($str)
    {
        if ($this->getSCSS()) {
            $packed = $this->getSCSS()->compile($str);
        } else {
            $packed = $str;
        }
        return $packed;
    } // end getMinifyCSS

    /** возвращает сжатое содержимое $str
     *
     * @param unknown $str
     * @return unknown
     */
    protected function getMinifyJS($str)
    {
        switch ($this->jsmin) {
            case "packer":
                $packer = new Packer($str, "Normal", true, false);
                $packed = $packer->pack();
                break;
            case "jshrink":
                $packed = Minifier::minify($str);
                break;
            default:
                $packed = $str;
        }
        return $packed;
    } // end getMinifyJS

    /** проверяет пакованный файл на актуальность
     *
     * @param unknown $outputFilename
     * @return boolean
     */
    protected function packedIsOld($outputFilename)
    {
        $pathOutputFilename = $this->performFilename($outputFilename);
        if (file_exists($pathOutputFilename)) {
            if ($this->ttl == -1) {
                return false;
            }
            // never recompile
            $fileage = time() - filemtime($pathOutputFilename);
            if ($fileage < $this->ttl) {
                return false;
            }
            // перекомпиляция не нужна
        }
        return true;
    } // end packedIsOld

    /** по расширению возвращает список файлов для обработки
     *
     * @param unknown $ext
     * @param string $arFileList
     * @return string[]|string
     */
    protected function getFiles($ext, $arFileList = false)
    {
        if (!$arFileList) {
            global $_javascripts, $_stylesheets;
            if ($ext == 'js') {
                $arFiles = &$_javascripts;
            }

            if ($ext == 'css' || $ext == 'scss') {
                $arFiles = &$_stylesheets;
            }

        } else {
            $arFiles = $arFileList;
        }
        if (!is_array($arFiles)) {
            return array($arFiles);
        }
        return $arFiles;
    } // getFiles

} // end class
