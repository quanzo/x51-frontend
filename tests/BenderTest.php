<?php
namespace x51\tests\functions;
	use x51\functions\funcArray;
	
class BenderTest extends \PHPUnit\Framework\TestCase{
	
	
	public function testBender(){
		$arCss = [
			__DIR__.'/input/1.css',
			__DIR__.'/input/2.scss',
		];
		$arJs = [
			__DIR__.'/input/1.js',
			__DIR__.'/input/2.js',
		];
		$bender = new \x51\classes\frontend\Bender([], 0);
		$bender->docRootDir = __DIR__.'/www';
		$bender->formatter = '\\ScssPhp\\ScssPhp\\Formatter\\Nested';
		$bender->jsmin = 'packer';
		
		$bender->createPacked($arCss, 'css', __DIR__.'/output/1.css');
		$bender->createPacked($arJs, 'js', __DIR__ . '/output/1.js');

		$this->assertTrue(file_exists(__DIR__.'/output/1.css') && md5_file(__DIR__.'/output/1.css') == md5_file(__DIR__.'/result/1.css'));
		$this->assertTrue(file_exists(__DIR__ . '/output/1.js') && md5_file(__DIR__ . '/output/1.js') == md5_file(__DIR__ . '/result/1.js'));

		
		$outfileCss = $bender->output('1.css', $arCss);
		$this->assertTrue(file_exists($bender->docRootDir . '/1.css') && md5_file($bender->docRootDir . '/1.css') == md5_file(__DIR__ . '/result/1.css'));
		
		$outfileJs = $bender->output('1.js', $arJs);
		$this->assertTrue(file_exists($bender->docRootDir . '/1.js') && md5_file($bender->docRootDir . '/1.js') == md5_file(__DIR__ . '/result/1.js'));


	}
	

	
} // end class