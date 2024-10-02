<?php

$rules = [
    '@Symfony' => true,
    'strict_param' => true,
    'php_unit_method_casing' => ['case' => 'snake_case'],
    'phpdoc_align' => [
        'align' => 'left'
    ],
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true
    ],
    'yoda_style' => [
        'equal' => false,
        'identical' => false,
        'less_and_greater' => false,
    ],
];

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
