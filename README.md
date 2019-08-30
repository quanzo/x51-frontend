Compress css and js
===================

How to use
----------

Generating a CSS from SCS takes more time. Do not use without caching.

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
    $bender = new \x51\classes\frontend\Bender(
        [ // path to use in @import in scss files (if need)
            'path/to/scss/lib.scss'
        ],
        -1 // never update (only if the file is missing)
        // 3600 - set 1 hour time to live
    );
    // adv options
    $bender->functionsConfig = 'path/to/scss-functions.php';
    $bender->variablesConfig = 'path/to/scss-vars.php';
    // or set array
    $bender->functionsConfig = [];
    $bender->variablesConfig = [];

    // array of css files to packed
    $arCssFiles = [
        'path/to/file.css',
        'path/to/file.scss',
    ];
    // array of js files to packed
    $arJsFiles = [
        'path/to/js.js'
    ];

    echo $bender->output('document-root-file.css', $arCssFiles); // <link href="/document-root-file.css" rel="stylesheet" type="text/css"/>
    echo $bender->output('document-root-file.js', $arJsFiles); // <script type="text/javascript" src="/document-root-file.js"></script>
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

### Example *scss-vars.php*

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
<?php
return array(
	'black' => '#000',
	'white' => '#fff',
);
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

### Example *scss-functions.php*

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
<?php
return [
    // use in scss: add-two(10, 10)
    'add-two' => function($args) {
        list($a, $b) = $args;
        return $a[1] + $b[1];
    }
];
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Function args

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/*
	function ($arg) {
		$arg - array of argument
		$arg[0] - first
		$arg[1] - second and etc
		$arg[0][0] - type
		$arg[0][1] - value
	}

*/
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
