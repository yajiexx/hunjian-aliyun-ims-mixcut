<?php

spl_autoload_register(function ($class) {
    $prefixes = array(
        'Hunjian\\AliyunImsMixcut\\' => __DIR__ . '/../src/',
        'Hunjian\\AliyunImsMixcut\\Tests\\' => __DIR__ . '/',
    );

    foreach ($prefixes as $prefix => $baseDir) {
        if (strpos($class, $prefix) !== 0) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});
