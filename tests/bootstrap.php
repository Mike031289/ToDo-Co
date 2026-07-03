<?php

// 1. Load the official Composer autoloader
$loader = require dirname(__DIR__) . '/vendor/autoload.php';

// 2. Intercept and silence all legacy warnings/deprecations from vendor folder under PHP 7.4
set_error_handler(function ($severity, $message, $file, $line) {
    // If the warning/notice comes from the vendor directory, bypass it
    if (strpos($file, 'vendor') !== false) {
        return true;
    }
    return false;
});

// 3. Bridge the gap between Symfony 3 and PHPUnit 7
if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    class_alias('PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase');
}

// 4. Register Doctrine annotations loader
if (class_exists('Doctrine\Common\Annotations\AnnotationRegistry')) {
    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);
}
