<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('cache')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
      'binary_operator_spaces' => ['default' => 'align'],
      'phpdoc_align' => true,
      'array_indentation' => true,
      'blank_line_before_statement' => ['statements' => [
        'break', 'case', 'continue', 'default', 'die', 'for', 'foreach', 'if']
        ],
      'braces' => ['position_after_control_structures' => 'next'],
      'cast_spaces' => true,
      'concat_space' => ['spacing' => 'one'],
      'elseif' => true,
      'encoding' => true,
      'full_opening_tag' => true,
      'include' => true,
      'indentation_type' => true,
      'array_syntax' => ['syntax' => 'short'],
      'lowercase_constants' => true,
      'method_chaining_indentation' => true,
      'method_argument_space' => true,
      'no_closing_tag' => true,
      'no_singleline_whitespace_before_semicolons' => true,
      'no_useless_return' => true,
      'no_whitespace_in_blank_line' => true,
      'not_operator_with_successor_space' => true,
      'single_blank_line_at_eof' => true,
      'class_definition' => ['single_line' => true],
      'single_quote' => true,
      'trim_array_spaces' => true,
      'visibility_required' => true,
      'native_function_casing' => true,
      'no_empty_comment' => true,
      'single_line_comment_style' => true
    ])
    ->setFinder($finder)
    ->setIndent("    ")
;
