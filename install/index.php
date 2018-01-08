<?php
/**
*
* @package install
* @copyright (c) 2007 Kleeja.com
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
$_path = '../';



//before anything check PHP version compatibility
if (! function_exists('version_compare')
    || version_compare(PHP_VERSION, 7.0, '<'))
{
    exit(
        '<h2>You are using an old PHP version (' . PHP_VERSION . '), to run Kleeja you should use PHP 7.0 or above.</h2>'
    );
}


// if mysqli is not installed
if (! function_exists('mysqli_connect'))
{
    exit(
        '<h2>In order to use Kleeja, "<b>php_mysqli</b>" extension has to be installed on your server.</h2>'
    );
}




if(file_exists($_path . 'config.php'))
{
	include_once ($_path . 'config.php');
}

include_once $_path . 'includes/functions.php';

include_once $_path . 'includes/mysqli.php';

include_once 'includes/functions_install.php';



/**
* print header
*/
if (!ip('lang'))
{
	echo gettpl('header.html');
}


/**
* Navigation ..
*/
switch (g('step', 'str'))
{
default:
case 'language':

	if(ig('ln') && g('ln', 'str', '') !== '')
	{
//	    header('Location: ./?step=official&lang=' . g('ln'));
		echo '<meta http-equiv="refresh" content="0;url=./?step=what_is_kleeja&lang=' . g('ln') . '">';
		exit;
	}

	echo gettpl('lang.html');

break;
case 'what_is_kleeja':

	echo gettpl('what_is_kleeja.html');

break;
case 'official':

	echo gettpl('official.html');

break;
case 'choose' :

	$install_or_no	= $php_ver = true;

	//check version of PHP 
	if (! function_exists('version_compare')
        || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	{
		$php_ver = false;
	}

	if(file_exists($_path . 'config.php'))
	{
		include_once $_path . 'config.php';
		if(!empty($dbuser) && !empty($dbname))
		{
			$d = inst_get_config('language');

			if(!empty($d))
			{
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


