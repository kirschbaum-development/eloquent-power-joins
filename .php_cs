<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        'phpdoc_no_empty_return' => false,
        'phpdoc_var_annotation_correct_order' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'no_singleline_whitespace_before_semicolons' => true,
        'no_extra_blank_lines' => [
            'break', 'case', 'continue', 'curly_brace_block', 'default',
            'extra', 'parenthesis_brace_block', 'return',
            'square_brace_block', 'switch', 'throw', 'use', 'useTrait', 'use_trait',
        ],
        'cast_spaces' => [
            'space' => 'single',
        ],
        'single_quote' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'no_empty_phpdoc' => true,
        'no_empty_comment' => true,
        'array_indentation' => true,
        'short_scalar_cast' => true,
        'no_mixed_echo_print' => [
            'use' => 'echo',
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'no_empty_statement' => true,
        'unary_operator_spaces' => true, // $number ++ becomes $number++
        'hash_to_slash_comment' => true, // # becomes //
        'standardize_not_equals' => true, // <> becomes !=
        'native_function_casing' => true,
        'ternary_operator_spaces' => true,
        'ternary_to_null_coalescing' => true,
        'declare_equal_normalize' => [
            'space' => 'single',
        ],
        'function_typehint_space' => true,
        'no_leading_import_slash' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break', 'case', 'continue',
                'declare', 'default', 'die',
                'do', 'exit', 'for', 'foreach',
                'goto', 'if', 'include',
                'include_once', 'require', 'require_once',
                'return', 'switch', 'throw', 'try', 'while', 'yield',
            ],
        ],
        'combine_consecutive_unsets' => true,
        'method_chaining_indentation' => true,
        'no_whitespace_in_blank_line' => true,
        'blank_line_after_opening_tag' => true,
        'no_trailing_comma_in_list_call' => true,
        'list_syntax' => ['syntax' => 'short'],
        // public function getTimezoneAttribute( ? Banana $value) becomes public function getTimezoneAttribute(?Banana $value)
        'compact_nullable_typehint' => true,
        'explicit_string_variable' => true,
        'no_leading_namespace_whitespace' => true,
        'trailing_comma_in_multiline_array' => true,
        'not_operator_with_successor_space' => true,
        'object_operator_without_whitespace' => true,
        'single_blank_line_before_namespace' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_useless_return' => true,
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_order' => true,
        'phpdoc_scalar' => false,
        'phpdoc_separation' => false,
        'phpdoc_single_line_var_spacing' => false,
        'single_trait_insert_per_statement' => true,
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
    ])
    ->setLineEnding("\n");
