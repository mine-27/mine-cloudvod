<?php

spl_autoload_register(function ($class) {
    $prefix = 'MineCloudvod\\';

    $base_dir = MINECLOUDVOD_PATH . '/inc/';
    $len = strlen($prefix);
    if (0 !== strncmp($prefix, $class, $len)) {
        return;
    }
    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});