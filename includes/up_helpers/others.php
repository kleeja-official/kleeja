<?php
/**
*
* @package Kleeja_up_helpers
* @copyright (c) 2007-2012 Kleeja.net
* @license ./docs/license.txt
*
*/

//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}



/**
 * checking the safety and validity of sub-extension of given file
 *
 * @param mixed $filename
 */
function ext_check_safe($filename)
{
    //bad files extensions
    $not_allowed =    ['php', 'php3' ,'php5', 'php4', 'asp' ,'shtml' , 'html' ,'htm' ,'xhtml' ,'phtml', 'pl', 'cgi', 'htaccess', 'ini'];

    //let split the file name, suppose it filename.gif.php
    $tmp    = explode('.', $filename);

    //if it's less than 3, that its means normal
    if (sizeof($tmp) < 3)
    {
        return true;
    }

    $before_last_ext = $tmp[sizeof($tmp)-2];

    //in the bad extenion, return false to tell him
    if (in_array(strtolower($before_last_ext), $not_allowed))
    {
        return false;
    }
    else
    {
        return true;
    }
}


/**
 * create htaccess files for uploading folder
 * @param mixed $folder
 */
function generate_safety_htaccess($folder)
{
    $return = false;

    is_array($plugin_run_result = Plugins::getInstance()->run('generate_safety_htaccess_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    if ($return)
    {
        return true;
    }

    //data for the htaccess
    $htaccess_data = "<Files ~ \"^.*\.(php|php*|cgi|pl|phtml|shtml|sql|asp|aspx)\">\nOrder allow,deny\nDeny from all\n</Files>\n<IfModule mod_php4.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php5.c>\nphp_flag engine off\n</IfModule>\nRemoveType .php .php* .phtml .pl .cgi .asp .aspx .sql";

    //generate the htaccess
    $fi        = @fopen($folder . '/.htaccess', 'w');
    $fi2       = @fopen($folder . '/thumbs/.htaccess', 'w');
    @fwrite($fi, $htaccess_data);
    @fwrite($fi2, $htaccess_data);
}

/**
 * create an uploading folder
 * @param  string $folder
 * @return bool
 */
function make_folder($folder)
{
    $return = false;

    is_array($plugin_run_result = Plugins::getInstance()->run('make_folder_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook


    if ($return)
    {
        return true;
    }


    $folders = explode('/', $folder);


    $path = '';

    foreach ($folders as $sub_folder)
    {
        //try to make a new upload folder
        @mkdir($path . $sub_folder);
        @mkdir($path . $sub_folder . '/thumbs');


        //then try to chmod it to 0755
        @chmod($path . $sub_folder, 0755);
        @chmod($path . $sub_folder . '/thumbs/', 0755);

        //make it safe
        generate_safety_htaccess($path . $sub_folder);

        //create empty index so nobody can see the contents
        $fo  = @fopen($path . $sub_folder . '/index.html', 'w');
        $fo2 = @fopen($path . $sub_folder . '/thumbs/index.html', 'w');
        @fwrite($fo, '<a href="http://kleeja.com"><p>KLEEJA ..</p></a>');
        @fwrite($fo2, '<a href="http://kleeja.com"><p>KLEEJA ..</p></a>');

        $path .= $sub_folder . '/';
    }

    return file_exists($folder);
}

/**
 * Change the file name depend on given decoding type
 * @param mixed $filename
 * @param mixed $i_loop
 * @param mixed $ext
 * @param mixed $decoding_type
 */
function change_filename_decoding($filename, $i_loop, $ext, $decoding_type = '')
{
    global $config;

    $return = '';

    $decoding_type = empty($decoding_type) ? $config['decode'] : $decoding_type;


    //change it, time..
    if ($decoding_type == 'time' || $decoding_type == 1)
    {
        list($usec, $sec) = explode(' ', microtime());
        $extra            = str_replace('.', '', (float) $usec + (float) $sec);
        $return           = $extra . $i_loop . '.' . $ext;
    }
    // md5
    elseif ($decoding_type == 'md5' || $decoding_type == 2)
    {
        list($usec, $sec)    = explode(' ', microtime());
        $extra               = md5(((float) $usec + (float) $sec) . $filename);
        $extra               = substr($extra, 0, 12);
        $return              = $extra . $i_loop . '.' . $ext;
    }
    // exists before, change it a little
    elseif ($decoding_type == 'exists')
    {
        $return = substr($filename, 0, -(strlen($ext)+1)) . '_' . substr(md5(microtime(true) . $i_loop), rand(0, 20), 5) . '.' . $ext;
    }
    //nothing
    else
    {
        $filename = substr($filename, 0, -(strlen($ext)+1));
        $return   = preg_replace('/[,.?\/*&^\\\$%#@()_!|"\~\'><=+}{; ]/', '-', $filename) . '.' . $ext;
        $return   = preg_replace('/-+/', '-', $return);
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('change_filename_decoding_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $return;
}

/**
 * Change the file name depend on used templates {rand:..} {date:..}
 * @param mixed $filename
 */
function change_filename_templates($filename)
{
    //random number...
    if (preg_match('/{rand:([0-9]+)}/i', $filename, $m))
    {
        $filename = preg_replace('/{rand:([0-9]+)}/i', substr(md5(time()), 0, $m[1]), $filename);
    }

    //current date
    if (preg_match('/{date:([a-zA-Z-_]+)}/i', $filename, $m))
    {
        $filename = preg_replace('/{date:([a-zA-Z-_]+)}/i', date($m[1]), $filename);
    }

    is_array($plugin_run_result = Plugins::getInstance()->run('change_filename_templates_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    return $filename;
}

/**
 * check mime type of uploaded file
 * @return bool
 * @param  mixed $given_file_mime
 * @param  mixed $file_ext
 * @param  mixed $file_path
 */
function check_mime_type($given_file_mime, $file_ext, $file_path)
{
    $return = '';

    is_array($plugin_run_result = Plugins::getInstance()->run('kleeja_check_mime_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if ($return !== '')
    {
        return $return;
    }


    $mime = '';

    if (function_exists('finfo_open') || function_exists('mime_content_type'))
    {
        if (function_exists('mime_content_type'))
        {
            $mime = @mime_content_type($file_path);
        }
        else
        {
            $f_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime   = finfo_file($f_info, $file_path);
            finfo_close($f_info);
        }
    }
    elseif (! empty($given_file_mime))
    {
        $mime = $given_file_mime;
    }


    if (! empty($mime))
    {
        $supposed_mime = explode('/', get_mime_for_header($file_ext), 2);

        if (is_array($supposed_mime))
        {
            foreach ($supposed_mime as $s_mime)
            {
                if (strpos($mime, $s_mime) !== false)
                {
                    return true;
                }
            }

            return false;
        }
    }


    //if normal checks failed!

    if (@filesize($file_path) > 6*(1000*1024))
    {
        return true;
    }

    //check for bad things inside files ...
    //<.? i cant add it here cuz alot of files contain it
    $maybe_bad_codes_are = ['<' . 'script', 'zend', 'base64_decode', '<' . '?' . 'php', '<' . '?' . '='];

    if (! ($data = @file_get_contents($file_path)))
    {
        return true;
    }


    foreach ($maybe_bad_codes_are as $i)
    {
        if (strpos(strtolower($data), $i) !== false)
        {
            return false;
        }
    }


    return true;
}


/**
 * to prevent flooding at uploading
 * @param mixed $user_id
 */
function user_is_flooding($user_id = '-1')
{
    global $SQL, $dbprefix, $config;

    $return = 'empty';

    is_array($plugin_run_result = Plugins::getInstance()->run('user_is_flooding_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run

    if ($return != 'empty')
    {
        return $return;
    }

    //if the value is zero (means that the function is disabled) then return false immediately
    if (($user_id == '-1' && $config['guestsectoupload'] == 0) || $user_id != '-1' && $config['usersectoupload'] == 0)
    {
        return false;
    }

    //In my point of view I see 30 seconds is not bad rate to stop flooding ..
    //even though this minimum rate sometime isn't enough to protect Kleeja from flooding attacks
    $time = time() - ($user_id == '-1' ? $config['guestsectoupload'] : $config['usersectoupload']);

    $query = [
        'SELECT'          => 'f.time',
        'FROM'            => "{$dbprefix}files f",
        'WHERE'           => 'f.time >= ' . $time . ' AND f.user_ip = \'' . $SQL->escape(get_ip()) . '\'',
    ];

    if ($SQL->num_rows($SQL->build($query)))
    {
        return true;
    }

    return false;
}
