<?php

declare(strict_types=1);


$finder = PhpCsFixer\Finder::create()
    ->in([
        sprintf('%s/src', __DIR__),
    ]);

return new PhpCsFixer\Config()
    ->setRules([
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        'no_empty_phpdoc' => true,
        'mb_str_functions' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'length'],
        'no_spaces_after_function_name' => true,
        'no_whitespace_in_blank_line' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'no_unused_imports' => true,
        'standardize_not_equals' => true,
        'declare_strict_types' => true,
        'is_null' => true,
        'yoda_style' => false,
        'no_empty_statement' => true,
        'void_return' => true,
        'list_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'return',
            ],
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
