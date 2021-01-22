<?php
$finder = \PhpCsFixer\Finder::create()
    ->exclude('.repositories')
    ->exclude('vendor')
    ->in(__DIR__);
return \PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'no_superfluous_phpdoc_tags' => false,
        'position_after_functions_and_oop_constructs' => 'next',
        'single_line_throw' => false,
    ])
    ->setCacheFile(__DIR__ . '/.php_cs.cache')
    ->setFinder($finder);
