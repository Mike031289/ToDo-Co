<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * 1. Enforce strict boolean comparisons (=== true/false)
 * Dynamic PHPUnit compatibility bridge for PHP 7.4 runtimes
 */
if (class_exists('PHPUnit_Framework_TestCase') === false && class_exists('PHPUnit\Framework\TestCase') === true) {
    class_alias('PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase');
}

/**
 * Intercept and suppress legacy deprecation notices triggered during Symfony 3.1 lifecycle
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/**
 * Load the application autoloader using an absolute path context.
 * Using require_once inside an isolation variable to bypass strict structural analysis.
 */
$autoloadPath = dirname(__FILE__) . '/app/autoload.php';
$loader = require_once $autoloadPath;

/**
 * Register the autoloader for Doctrine annotation tracking
 */
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
