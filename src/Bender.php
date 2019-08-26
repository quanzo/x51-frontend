<?php
namespace x51\classes\frontend;

use \JShrink\Minifier;
use \ScssPhp\ScssPhp\Compiler;
use \Tholu\Packer\Packer;
use \x51\functions\funcFileSystem;

/**
Список CSS складываем в глобальный массив $_stylesheets
Список JS складываем в глоб массив $_javascripts
 */

class Bender
{
    // JS minifier, can be "packer" or "jshrink"
    public $jsmin = "jshrink";

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
        foreach ($arFiles as $idx => $fn) {
            if (!file_exists($fn)) {
                $fn = $this->docRootDir() . $fn;
            }
            $fext = $this->get_ext($fn);
            $content = file_get_contents($fn);
            if ($fext == 'scss') {
                // подмена url картинок
                //$this->cssUrlCorrection
                $packed = $this->getMinifySCSS($this->cssUrlCorrection($content, $fn));
            }
            if ($fext == 'css') {
                $packed = $this->getMinifyCSS($this->cssUrlCorrection($content, $fn));
            }
            if ($fext == 'js') {
                $packed = $this->getMinifyJS($content) . ";\n";
            }
            fwrite($handler, '/*#' . $arFiles[$idx] . '#*/' . $packed);
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

    /**
     * Составляет и возвращает статистику по использованию файлов в сжатых версиях
     * Просматривает файлы в текущем каталоге и подкаталогах. Ищет метки файлов.
     * Результат в виде массива. Ключ - имя файла. Значение - целое число до 100 (прецентов)
     * На входе - папка с сжатыми файлами.
     *
     * @param string $dirFullPath
     * @return void
     */
    public function getCacheStat($dirFullPath, array &$stat)
    {
        $arDirList = funcFileSystem::scandir($dirFullPath, [
            'return_fullPath' => true,
            'show' => [
                'dir' => true,
                'file' => true,
            ],
            'return_full' => true,
            'func_check' => function ($arLine) {
                if ($arLine['dir']) {
                    return true;
                } else {
                    $ext = funcFileSystem::getFileExt($arLine['name']);
                    return $ext == 'css' || $ext == 'js';
                }
            },
        ]);
        foreach ($arDirList as $arFile) {
            if ($arFile['dir']) { // директория
                $this->getCacheStat($arFile['name'], $stat);
            } else { // ищем метки в файле
                foreach ($this->getFilenameFromPacked($arFile['name']) as $fn) {
                    if (!isset($stat[$fn])) {
                        $stat[$fn] = 0;
                    }
                    $stat[$fn]++;
                    if (!isset($stat['total'])) {
                        $stat['total'] = 0;
                    }
                    $stat['total']++;
                }
            }
        }
    } // end getCacheStat

    public function getFilenameFromPacked($packedFileName)
    {
        $i = 0;
        $f = fopen($packedFileName, 'r');
        try {
            $buff = '';
            
            while ($line = fread($f, 256)) { // читаем файл по 256 символов
                $buff .= $line; // заносим новые данные в буфер к остаткам предыдущих данных
                
                $len = strlen($buff);
                $pos = 0;
                do {
                    $posBegin = strpos($buff, '/*#', $pos); // определяем начало и конец блока с именем файла
                    $posEnd = strpos($buff, '#*/', $pos);
                    if ($posBegin !== false && $posEnd === false) { // начало, конца нет - в буфер данные для следующей итерации
                        $buff = substr($buff, $posBegin);
                        $theend = true;
                    } elseif ($posBegin === false && $posEnd === false) { // нет строки с именем файла
                        $buff = substr($buff, $len - 3);
                        $theend = true;
                    } else { // есть имя
                        $pos = $posEnd + 3;
                        yield trim(substr($buff, $posBegin + 3, $posEnd - $posBegin - 3));
                        $theend = false; // проверяем текст далее
                    }
                } while (!$theend);
            }
        } finally {
            fclose($f);            
        }
    }

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

    /** возвращает сжатое содержимое $str как SCSS
     *
     * @param string $str
     * @return string
     */
    protected function getMinifySCSS($str)
    {
        if ($this->getSCSS()) {
            $packed = $this->getSCSS()->compile($str);
        } else {
            $packed = $str;
        }
        return $packed;
    } // end getMinifySCSS

    /** возвращает сжатое содержимое $str как CSS
     *
     * @param string $str
     * @return string
     */
    protected function getMinifyCSS($str)
    {
        $packed = str_replace(["\n}", ";\n", "{\n", ",\n"], ['}', ';', '{', ','], preg_replace(
            [
                '/\/\*.*?\*\//si',
                '/\s+\n/s',
                '/\n\s+/s',
            ],
            [
                '',
                "\n",
                "\n",
            ],
            $str
        ));
        return $packed;
    } // end getMinifySCSS

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
            if ($this->ttl == -1) { // never recompile
                return false;
            }
            if ($this->ttl == 0) { // always recompile
                return true;
            }
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
