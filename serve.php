<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.net
* @license ./docs/license.txt
*
*/



/**
 * We are in serve.php file, useful for exceptions
 */
define('IN_SERVE', true);

/**
 * Defaults rewrite rules
 */
$rules = [
    '^index.html$'                                           => ['file' => 'index.php'],
    '^download([0-9]*).html$'                                => ['file' => 'do.php', 'args' => 'id=$1'],
    '^downloadf-(.*)-([a-zA-Z0-9_-]*).html$'                 => ['file' => 'do.php', 'args' =>'filename=$1&x=$2'],
    '^down-([0-9]*).html$'                                   => ['file' => 'do.php', 'args' => 'down=$1'],
    '^downf-(.*)-([a-zA-Z0-9_-]*).html$'                     => ['file' => 'do.php', 'args' => 'downf=$1&x=$2'],
    '^downex-([0-9]*).html$'                                 => ['file' => 'do.php', 'args' => 'down=$1'],
    '^downexf-(.*)-([a-zA-Z0-9_-]*).html$'                   => ['file' =>'do.php', 'args' => 'downexf=$1&x=$2'],
    '^thumb([0-9]*).html$'                                   => ['file' => 'do.php', 'args' => 'thmb=$1'],
    '^imagef-(.*)-([a-zA-Z0-9_-]*).html$'                    => ['file' =>'do.php', 'args' => 'imgf=$1&x=$2'],
    '^thumbf-(.*)-([a-zA-Z0-9_-]*).html$'                    => ['file' => 'do.php', 'args' => 'thmbf=$1&x=$2'],
    '^image([0-9]*).html$'                                   => ['file' => 'do.php', 'args' => 'img=$1'],
    '^del([a-zA-Z0-9_-]*).html$'                             => ['file' => 'go.php', 'args' => 'go=del&cd=$1'],
    '^(call|guide|rules|stats|report).html$'                 => ['file' =>'go.php', 'args' => 'go=$1'],
    '^report[_-]([0-9]*).html$'                              => ['file' => 'go.php', 'args' => 'go=report&id=$1'],
    '^(filecp|profile|fileuser|register|login|logout).html$' => ['file' => 'ucp.php', 'args' => 'go=$1'],
    '^fileuser[_-]([0-9]+).html$'                            => ['file' => 'ucp.php', 'args' => 'go=fileuser&id=$1'],
    '^fileuser[_-]([0-9]+)-([0-9]+).html$'                   => ['file' => 'ucp.php', 'args' => 'go=fileuser&id=$1&page=$2'],
    // #for future plugins
    '^go-(.*).html$' => ['file' => 'go.php', 'args' => 'go=$1'],
];


if (file_exists('plugins_rules.php'))
{
    $plugins_rules = include_once 'plugins_rules.php';
    $rules         = array_merge($rules, $plugins_rules);
}

$base_folder = str_replace('/serve.php', '', parse_url('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], PHP_URL_PATH));
$request_uri = preg_replace('/^'. preg_quote($base_folder, '/') . '\//', '', strtok($_SERVER['REQUEST_URI'], '?'));

foreach ($rules as $rule_regex => $rule_result)
{
    if (preg_match("/{$rule_regex}/", $request_uri, $matches))
    {
        if (! empty($rule_result['args']))
        {
            parse_str($rule_result['args'], $args);

            foreach ($args as $arg_key => $arg_value)
            {
                if (preg_match('/^\$/', $arg_value))
                {
                    $match_number = ltrim($arg_value, '$');

                    if (isset($matches[$match_number]))
                    {
                        $_GET[$arg_key] = $matches[$match_number];
                    }
                }
                else
                {
                    $_GET[$arg_key] = $arg_value;
                }
            }
        }

        include $rule_result['file'];

        exit;
    }
}

//fallback
define('SERVE_FALLBACK', true);
include 'go.php';
