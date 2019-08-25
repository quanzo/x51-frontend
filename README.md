Compress css and js
========================

## How to use

```php
    $bender = new \x51\classes\frontend\Bender(
        [
            'path/to/scss/lib.scss'
        ],
        -1 // never update (only if the file is missing)
    );
    $bender->functionsConfig = 'path/to/scss-functions.php';
    $bender->variablesConfig = 'path/to/scss-vars.php';

    $arCssFiles = [
        'path/to/file.css',
        'path/to/file.scss',
    ];
    $arJsFiles = [
        'path/to/js.js'
    ];

    echo $bender->output('document-root-file.css', $arCssFiles); // <link href="/document-root-file.css" rel="stylesheet" type="text/css"/>
    echo $bender->output('document-root-file.js', $arJsFiles); // <script type="text/javascript" src="/document-root-file.js"></script>
```