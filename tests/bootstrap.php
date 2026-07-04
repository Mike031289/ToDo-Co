<?php

// tests/bootstrap.php

/**
 * 1. Load the Composer Autoloader
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
}

/**
 * 2. Compatibility Polyfills (PHPUnit 7 / Symfony 3)
 */
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

// Fournit la classe ET la méthode handleError attendue par le phpunit-bridge
if (!class_exists('PHPUnit_Util_ErrorHandler')) {
    class PHPUnit_Util_ErrorHandler {
        public static function handleError($severity, $message, $file, $line, $context = null) {
            return true;
        }
    }
}

/**
 * 3. Global Error Handler Filter
 * Silences PHP 7.4 / Symfony 3.4 deprecations and runtime warnings during test execution.
 */
$previousHandler = set_error_handler(function ($severity, $message, $file, $line, $context = null) use (&$previousHandler) {
    // On intercepte et on ignore silencieusement les dépréciations et les warnings
    if ($severity === E_WARNING || $severity === E_USER_DEPRECATED || $severity === E_DEPRECATED) {
        return true;
    }

    if ($previousHandler) {
        // Si le handler précédent est le bridge de Symfony brisé, on dévie vers notre helper
        if (is_array($previousHandler) && get_class($previousHandler[0]) === 'Symfony\Bridge\PhpUnit\DeprecationErrorHandler') {
            return PHPUnit_Util_ErrorHandler::handleError($severity, $message, $file, $line, $context);
        }

        try {
            return $previousHandler($severity, $message, $file, $line, $context);
        } catch (\TypeError $e) {
            return $previousHandler($severity, $message, $file, $line);
        }
    }
    return false;
});
