<?php
/* Аккумуляция ресурсов для подключения в страницу сайта
Собираем имена файлов.
Добавляем:

$res->add('/css/base.css');
$res->add('/css/script.js');

echo $res->output();

*/
namespace x51\classes\frontend;
	use \x51\functions\funcFileSystem;
	
class Resources extends \x51\classes\Base implements \x51\interfaces\frontend\IResources{
	/* накопление css и js.
	вместе с шаблоном могут быть расположены 2 файла с именем шаблона и расширением css или js
	*/
	protected $arCssFiles=array();
	protected $arJsFiles=array();
	protected $dirCache;
	protected $shortCacheDir;
	
	public function __construct($dirShortName='/cache/') {
		$this->shortCacheDir = $dirShortName;
		$this->dirCache = funcFileSystem::normalizePath($_SERVER['DOCUMENT_ROOT'].'/'.$dirShortName);
		if (!is_dir($this->dirCache)) {
			@mkdir($this->dirCache, 0754, true);
		}
	} // end construct
	
	public function getCacheDir() {
		return $this->shortCacheDir;
	}
	
	// Накопление ресурсов JS и CSS. вместе с шаблоном могут быть расположены 2 файла с именем шаблона и расширением css или js
	public function add($f) {
		$r=is_readable($f) && file_exists($f);
		if (!$r) {
			$f=funcFileSystem::normalizePath($_SERVER['DOCUMENT_ROOT'].'/'.$f);
			$r=is_readable($f) && file_exists($f);
		}
		if ($r) {
			$ext=funcFileSystem::getFileExt($f);
			$a='';
			$type='';
			switch ($ext) {
				case 'js': {
					$a='arJsFiles';
					$type='js';
					break;
				}
				case 'css': {
					$a='arCssFiles';
					$type='css';
					break;
				}
			}
			if ($a) {
				if (!isset($this->$a[$f])) {
					$this->$a[$f]=$type;
				}
			}
		}
	} // end includeResources
	
	protected function resourcesHash($arRes) {
		$str='';
		foreach ($arRes as $f => $type) {
			$str.=$f.' ';
		}
		return md5($str);
	} // resourcesHash
	
	protected function outputResourcesToFile(& $arRes, $fn) {
		if (!file_exists($fn) && is_writable($this->dirCache)) {
			foreach ($arRes as $f => $type) {
				file_put_contents($fn, "/*** ".$f." ***/\n".file_get_contents($f), FILE_APPEND);
			}
		}
	} // end outputResourcesToFile
	
	public function getCssFiles() {
		return array_keys($this->arCssFiles);
	}
	
	public function getJsFiles() {
		return array_keys($this->arJsFiles);
	}
	
	public function outputFiles() {
		$out=[];
		if ($this->arCssFiles) {
			//ksort($this->arCssFiles);
			$fn=$this->dirCache.$this->resourcesHash($this->arCssFiles).'.css';
			$this->outputResourcesToFile($this->arCssFiles, $fn);
			if (file_exists($fn)) {
				$out['css'] = $fn;
			}
		}
		if ($this->arJsFiles) {
			//ksort($this->arJsFiles);
			$fn=$this->dirCache.$this->resourcesHash($this->arJsFiles).'.js';
			$this->outputResourcesToFile($this->arJsFiles, $fn);
			if (file_exists($fn)) {
				$out['js'] = $fn;
			}
		}
		return $out;
	}
	
	public function output() {
		$out='';
		$arFiles = $this->outputFiles();
		if (!empty($arFiles['css'])) {
			$out.='<link href="'.funcFileSystem::relativePath($arFiles['css'], $_SERVER['DOCUMENT_ROOT']).'?v='.filemtime($arFiles['css']).'" type="text/css"  rel="stylesheet" />';
		}
		if (!empty($arFiles['js'])) {
			$out.='<script type="text/javascript" src="'.funcFileSystem::relativePath($arFiles['js'], $_SERVER['DOCUMENT_ROOT']).'?v='.filemtime($arFiles['js']).'"></script>';
		}		
		return $out;
	} // end output
} // end class