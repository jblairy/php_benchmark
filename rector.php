<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/assets',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php84: true)
    ->withAttributesSets(symfony: true, doctrine: true, gedmo: true, phpunit:  true, sensiolabs: true)
    ->withComposerBased(twig: true, phpunit: true, doctrine: true, symfony: true, netteUtils: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, doctrineCodeQuality: true, naming: true, instanceOf: true, earlyReturn: true, strictBooleans: true, phpunitCodeQuality: true, rectorPreset: true, symfonyCodeQuality: true, symfonyConfigs: true, privatization: true);
