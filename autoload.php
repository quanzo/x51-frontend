<?php
spl_autoload_register(function ($className) {
    $className = ltrim($className, '\\');
    $startNamespace = 'x51\\classes\\frontend\\';
    if (strpos($className, $startNamespace) === 0) {
        $fileName = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, strlen($startNamespace))) . '.php';
        if (file_exists($fileName)) {
            require $fileName;
            return true;
        }
    }
    return false;    
});
