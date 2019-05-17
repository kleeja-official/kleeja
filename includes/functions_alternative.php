<?php
/**
*
* @package Kleeja
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


/**
* After a lot of work, we faced many hosts who use a old PHP version, or 
* they disabled many general functions ... 
* so, this file contains those type of functions.
*/


//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}


if (! function_exists('htmlspecialchars_decode'))
{
    function htmlspecialchars_decode($string, $style=ENT_COMPAT)
    {
        $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS, $style));

        if ($style === ENT_QUOTES)
        {
            $translation['&#039;'] = '\'';
        }
        return strtr($string, $translation);
    }
}

//
//http://us2.php.net/manual/en/function.str-split.php#84891
if (! function_exists('str_split'))
{
    function str_split($string, $string_length=1)
    {
        if (strlen($string) > $string_length || ! $string_length)
        {
            do
            {
                $c          = strlen($string);
                $parts[]    = substr($string, 0, $string_length);
                $string     = substr($string, $string_length);
            } while ($string !== false);
        }
        else
        {
            $parts = [$string];
        }
        return $parts;
    }
}

//Custom base64_* functions
function kleeja_base64_encode($str = '')
{
    return function_exists('base64_encode') ? base64_encode($str) : base64encode($str);
}
function kleeja_base64_decode($str = '')
{
    return function_exists('base64_decode') ? base64_decode($str) : base64decode($str);
}

//http://www.php.net/manual/en/function.base64-encode.php#63270
function base64encode($string = '')
{
    if (! function_exists('convert_binary_str'))
    {
        function convert_binary_str($string)
        {
            if (strlen($string) <= 0)
            {
                return;
            }

            $tmp = decbin(ord($string[0]));
            $tmp = str_repeat('0', 8-strlen($tmp)) . $tmp;
            return $tmp . convert_binary_str(substr($string, 1));
        }
    }

    $binval = convert_binary_str($string);
    $final  = '';
    $start  = 0;

    while ($start < strlen($binval))
    {
        if (strlen(substr($binval, $start)) < 6)
        {
            $binval .= str_repeat('0', 6-strlen(substr($binval, $start)));
        }
        $tmp = bindec(substr($binval, $start, 6));

        if ($tmp < 26)
        {
            $final .= chr($tmp+65);
        }
        elseif ($tmp > 25 && $tmp < 52)
        {
            $final .= chr($tmp+71);
        }
        elseif ($tmp == 62)
        {
            $final .= '+';
        }
        elseif ($tmp == 63)
        {
            $final .= '/';
        }
        elseif (! $tmp)
        {
            $final .= 'A';
        }
        else
        {
            $final .= chr($tmp-4);
        }
        $start += 6;
    }

    if (strlen($final)%4>0)
    {
        $final .= str_repeat('=', 4-strlen($final)%4);
    }
    return $final;
}



function base64decode($str)
{
    $len        = strlen($str);
    $ret        = '';
    $b64        = [];
    $base64     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $len_base64 = strlen($base64);

    for ($i = 0; $i < 256; $i++)
    {
        $b64[$i] = 0;
    }

    for ($i = 0; $i < $len_base64; $i++)
    {
        $b64[ord($base64[$i])] = $i;
    }

    for ($j=0;$j<$len;$j+=4)
    {
        for ($i = 0; $i < 4; $i++)
        {
            $c     = ord($str[$j+$i]);
            $a[$i] = $c;
            $b[$i] = $b64[$c];
        }

        $o[0] = ($b[0] << 2) | ($b[1] >> 4);
        $o[1] = ($b[1] << 4) | ($b[2] >> 2);
        $o[2] = ($b[2] << 6) | $b[3];

        if ($a[2] == ord('='))
        {
            $i = 1;
        }
        elseif ($a[3] == ord('='))
        {
            $i = 2;
        }
        else
        {
            $i = 3;
        }

        for ($k=0;$k<$i;$k++)
        {
            $ret .= chr((int) $o[$k] & 255);
        }

        if ($i < 3)
        {
            break;
        }
    }

    return $ret;
}

if (! function_exists('filesize'))
{
    function kleeja_filesize($filename)
    {
        $a = fopen($filename, 'r'); 
        fseek($a, 0, SEEK_END); 
        $filesize = ftell($a); 
        fclose($a);
        return $filesize;
    }
}
else
{
    function kleeja_filesize($filename)
    {
        return filesize($filename);
    }
}


if (! function_exists('array_column'))
{
    function array_column($array, $column_name)
    {
        return array_map(
                                function($element) use ($column_name) {
                                    return $element[$column_name];
                                },
                                array_values($array)
                    );
    }
}
