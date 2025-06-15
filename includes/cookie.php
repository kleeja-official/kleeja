<?php

/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license http://www.kleeja.net/license
 *
 */

//no for directly open
if (! defined('IN_COMMON')) {
    exit;
}


class KleejaCookie {
    public function set(string $name, string $value, string $expire) {
        global $config;

        is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_set_cookie_func_usr_class', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        //
        //when user add cookie_* in config this will replace the current ones
        //
        global $config_cookie_name, $config_cookie_domain, $config_cookie_secure, $config_cookie_path;
        $config['cookie_name']         = isset($config_cookie_name) ? $config_cookie_name : $config['cookie_name'];
        $config['cookie_domain']       = isset($config_cookie_domain) ? $config_cookie_domain : $config['cookie_domain'];
        $config['cookie_secure']       = isset($config_cookie_secure) ? $config_cookie_secure : $config['cookie_secure'];
        $config['cookie_path']         = isset($config_cookie_path) ? $config_cookie_path : $config['cookie_path'];

        //
        //when user add define('FORCE_COOKIES', true) in config.php we will make our settings of cookies
        //
        if (defined('FORCE_COOKIES')) {
            $config['cookie_domain'] = ! empty($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : (! empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : @getenv('SERVER_NAME'));
            $config['cookie_domain'] = str_replace('www.', '.', substr($config['cookie_domain'], 0, strpos($config['cookie_domain'], ':')));
            $config['cookie_path']   = '/';
            $config['cookie_secure'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
        }

        // Enable sending of a P3P header
        header('P3P: CP="CUR ADM"');

        $name_data = rawurlencode($config['cookie_name'] . '_' . $name) . '=' . rawurlencode($value);
        $rexpire   = gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expire);
        $domain    = (! $config['cookie_domain'] || $config['cookie_domain'] == 'localhost' || $config['cookie_domain'] == '127.0.0.1') ? '' : '; domain=' . $config['cookie_domain'];

        header('Set-Cookie: ' . $name_data . ($expire ? '; expires=' . $rexpire : '') . '; path=' . $config['cookie_path'] . $domain . (! $config['cookie_secure'] ? '' : '; secure') . '; HttpOnly', false);
    }

    public function get(string $name): string | false {
        global $config;
        is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_get_cookie_func_usr_class', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

        return $_COOKIE[$config['cookie_name'] . '_' . $name] ?? false;
    }

    public function exists(string $name): bool {
        return $this->get($name) !== false;
    }
}


function cookie(): KleejaCookie {
    static $cookie = null;

    if (is_null($cookie)) {
        $cookie = new KleejaCookie;
    }
    return $cookie;
}
