<?php
/**
 *
 * @package install
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// Report all errors, except notices
error_reporting(E_ALL ^ E_NOTICE);

/**
 * include important files
 */
define('IN_COMMON', true);

//path to this file from Kleeja root folder
define('PATH', '../');

//before anything check PHP version compatibility
if (version_compare(PHP_VERSION, 8.0, '<')) {
    exit(
        '<h2>You are using an old PHP version (' .
            PHP_VERSION .
            '), to run Kleeja you should use PHP 8.0 or above.</h2>'
    );
}

// if PDO or its drivers are not installed
if (!class_exists('PDO') || !array_intersect(['mysql', 'sqlite'], PDO::getAvailableDrivers())) {
    exit(
        '<h2>In order to use Kleeja, "<b>pdo_mysql</b>" or "<b>pdo_sqlite</b>" extension has to be installed on your server.</h2>'
    );
}

if (file_exists(PATH . 'config.php')) {
    include_once PATH . 'config.php';
}

include_once PATH . 'includes/functions.php';

include_once PATH . 'includes/pdo.php';

include_once 'includes/functions_install.php';

/**
 * print header
 */
if (!ip('lang')) {
    echo gettpl('header.html');
}

/**
 * Navigation ..
 */
switch (g('step', 'str')) {
    default:
    case 'language':
        if (ig('ln')) {
            echo '<meta http-equiv="refresh" content="0;url=./?step=what_is_kleeja&lang=' .
                g('ln', default: 'en') .
                '">';

            exit();
        }

        echo gettpl('lang.html');

        break;

    case 'what_is_kleeja':
        echo gettpl('what_is_kleeja.html');

        break;

    case 'official':
        echo gettpl('official.html');

        break;

    case 'choose':
        $install_or_no = $php_ver = true;

        //check version of PHP
        if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
            $php_ver = false;
        }

        if (file_exists(PATH . 'config.php')) {
            include_once PATH . 'config.php';

            if (!empty($dbuser) && !empty($dbname)) {
                $d = inst_get_config('language');

                if (!empty($d)) {
                    $install_or_no = false;
                }
            }
        }

        echo gettpl('choose.html');

        break;
}

/**
 * print footer
 */
echo gettpl('footer.html');
