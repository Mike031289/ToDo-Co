<?php

// tests/bootstrap.php

/**
 * 1. Load the Composer Autoloader
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
}

/**
 * 2. Compatibility Polyfill (PHPUnit 7 / Symfony 3)
 * Maps the legacy global PHPUnit class name to the modern namespaced one.
 */
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

/**
 * 3. Global Error Handler Filter
 * Silences PHP 7.4 / Symfony 3.4 deprecations and runtime warnings during test execution.
 */
$previousHandler = set_error_handler(function ($severity, $message, $file, $line) use (&$previousHandler) {
    if ($severity === E_WARNING || $severity === E_USER_DEPRECATED || $severity === E_DEPRECATED) {
        return true;
    }
    if ($previousHandler) {
        return $previousHandler($severity, $message, $file, $line);
    }
    return false;
});
