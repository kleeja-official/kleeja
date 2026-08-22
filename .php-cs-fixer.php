<?php

$finder = (new PhpCsFixer\Finder())
    ->exclude('cache')
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        'binary_operator_spaces' => ['default' => 'align'],
        'phpdoc_align' => true,
        'array_indentation' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'for',
                'foreach',
                'if',
                'return',
                'switch',
                'try'
            ],
        ],
        'single_space_around_construct' => true,
        'control_structure_braces' => true,
        'control_structure_continuation_position' => ['position' => 'next_line'],
        'cast_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        'elseif' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'include' => true,
        'indentation_type' => true,
        'array_syntax' => ['syntax' => 'short'],
        'constant_case' => ['case' => 'lower'],
        'method_chaining_indentation' => true,
        'method_argument_space' => true,
        'no_closing_tag' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_useless_return' => true,
        'no_whitespace_in_blank_line' => true,
        'not_operator_with_successor_space' => true,
        'single_line_empty_body' => true,
        'class_definition' => ['single_line' => true],
        'single_quote' => true,
        'trim_array_spaces' => true,
        'visibility_required' => true,
        'native_function_casing' => true,
        'no_empty_comment' => true,
        'single_line_comment_style' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'function_declaration' => true,
        'no_spaces_around_offset' => true,
        'spaces_inside_parentheses' => false,
    ])
    ->setFinder($finder)
    ->setIndent("    ");
