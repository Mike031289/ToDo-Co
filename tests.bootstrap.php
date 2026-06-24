<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

// 1. Gestion du pont de compatibilité pour PHPUnit 7+ et Symfony 3.1
if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    class_alias('PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase');
}

// 2. Interception et masquage des alertes de dépréciation PHP 7.4 au runtime des tests
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// 3. Inclusion de l'autoloader officiel de l'application
$loader = require __DIR__ . '/app/autoload.php';

// Enregistrement des annotations Doctrine
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
