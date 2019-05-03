<?php
// Read & Save 24bit BMP files

// Author: de77
// Licence: MIT
// Webpage: de77.com
// Version: 07.02.2010

//no for directly open
if (! defined('IN_COMMON'))
{
    exit();
}

class BMP
{
    public static function imagebmp(&$img, $filename = false)
    {
        return imagebmp($img, $filename);
    }

    public static function imagecreatefrombmp($filename)
    {
        return imagecreatefrombmp($filename);
    }
}

function imagebmp(&$img, $filename = false)
{
    $wid     = imagesx($img);
    $hei     = imagesy($img);
    $wid_pad = str_pad('', $wid % 4, "\0");

    $size = 54 + ($wid + $wid_pad) * $hei;

    //prepare & save header
    $header['identifier']		     = 'BM';
    $header['file_size']		      = dword($size);
    $header['reserved']			      = dword(0);
    $header['bitmap_data']		    = dword(54);
    $header['header_size']		    = dword(40);
    $header['width']			         = dword($wid);
    $header['height']			        = dword($hei);
    $header['planes']			        = word(1);
    $header['bits_per_pixel']	  = word(24);
    $header['compression']		    = dword(0);
    $header['data_size']		      = dword(0);
    $header['h_resolution']		   = dword(0);
    $header['v_resolution']		   = dword(0);
    $header['colors']			        = dword(0);
    $header['important_colors']	= dword(0);

    if ($filename)
    {
        $f = fopen($filename, 'wb');

        foreach ($header AS $h)
        {
            fwrite($f, $h);
        }

        //save pixels
        for ($y=$hei-1; $y>=0; $y--)
        {
            for ($x=0; $x<$wid; $x++)
            {
                $rgb = imagecolorat($img, $x, $y);
                fwrite($f, byte3($rgb));
            }
            fwrite($f, $wid_pad);
        }
        fclose($f);

        return true;
    }
    else
    {
        foreach ($header AS $h)
        {
            echo $h;
        }

        //save pixels
        for ($y=$hei-1; $y>=0; $y--)
        {
            for ($x=0; $x<$wid; $x++)
            {
                $rgb = imagecolorat($img, $x, $y);
                echo byte3($rgb);
            }
            echo $wid_pad;
        }

        return false;
    }
}

function imagecreatefrombmp($filename)
{
    $f = fopen($filename, 'rb');

    //read header    
    $header = fread($f, 54);
    $header = unpack(	'c2identifier/Vfile_size/Vreserved/Vbitmap_data/Vheader_size/' .
                        'Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vdata_size/' .
                        'Vh_resolution/Vv_resolution/Vcolors/Vimportant_colors', $header);

    if ($header['identifier1'] != 66 or $header['identifier2'] != 77)
    {
        //die('Not a valid bmp file');
        return false;
    }

    if ($header['bits_per_pixel'] != 24)
    {
        //die('Only 24bit BMP images are supported');
        return false;
    }

    $wid2 = ceil((3*$header['width']) / 4) * 4;

    $wid = $header['width'];
    $hei = $header['height'];

    $img = imagecreatetruecolor($header['width'], $header['height']);

    //read pixels    
    for ($y=$hei-1; $y>=0; $y--)
    {
        $row    = fread($f, $wid2);
        $pixels = str_split($row, 3);

        for ($x=0; $x<$wid; $x++)
        {
            imagesetpixel($img, $x, $y, dwordize($pixels[$x]));
        }
    }
    fclose($f);    	    

    return $img;
}	

function dwordize($str)
{
    $a = ord($str[0]);
    $b = ord($str[1]);
    $c = ord($str[2]);
    return $c*256*256 + $b*256 + $a;
}

function byte3($n)
{
    return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255);
}
function dword($n)
{
    return pack('V', $n);
}
function word($n)
{
    return pack('v', $n);
}
