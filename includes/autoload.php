<?php
// includes/autoload.php
spl_autoload_register(function ($class) {
    $file = $_SERVER['DOCUMENT_ROOT'] . '/ticket/classes/' . $class . '.php';
//    trigger_error(print_r($file, true));
    if (file_exists($file)) {
        require $file;
    }
});

//require_once __DIR__ . '/config.php';
//include_once $_SERVER['DOCUMENT_ROOT'] . '/ticket/config/config.php';
