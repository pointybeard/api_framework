<?php

declare(strict_types=1);

$header = <<<EOF
This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.

Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>

For the full copyright and license information, please view the LICENCE
file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->files()
            ->name('*.php')
            ->in(__DIR__)
            ->in(__DIR__.'/src/Api_Framework')
            ->in(__DIR__.'/src/Includes')
            ->in(__DIR__.'/src/Install')
            ->exclude(__DIR__.'/vendor')
    )
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'is_null' => true,
        'blank_line_before_statement' => ['statements' => ['continue', 'declare', 'return', 'throw', 'try']],
        'cast_spaces' => ['space' => 'single'],
        'header_comment' => ['header' => $header],
        'include' => true,
        'class_attributes_separation' => ['elements' => ['const' => 'one', 'method' => 'one', 'property' => 'one']],
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'phpdoc_align' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'psr_autoloading' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'single_blank_line_before_namespace' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
    ])
;
