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
 * This helper is used to make a watermark on a given image,
 * return nothing because if it work then ok , and if not then ok too :)
 * @todo text support
 *
 * @param $name
 * @param $ext
 * @return bool|void
 */
function helper_watermark($name, $ext)
{
    $return = false;

    is_array($plugin_run_result = Plugins::getInstance()->run('helper_watermark_func', get_defined_vars())) ? extract($plugin_run_result) : null; //run hook

    if ($return)
    {
        return;
    }

    //is this file really exsits ?
    if (! file_exists($name))
    {
        return;
    }

    $src_logo = $logo_path = false;

    if (file_exists(dirname(__FILE__) . '/../../images/watermark.png'))
    {
        $logo_path= dirname(__FILE__) . '/../../images/watermark.png';
        $src_logo = @imagecreatefrompng($logo_path);
    }
    elseif (file_exists(dirname(__FILE__) . '/../../images/watermark.gif'))
    {
        $logo_path= dirname(__FILE__) . '/../../images/watermark.gif';
        $src_logo = @imagecreatefromgif($logo_path);
    }

    //no watermark pic
    if (! $src_logo)
    {
        return;
    }

    //if there is imagick lib, then we should use it
    if (function_exists('phpversion') && phpversion('imagick'))
    {
        helper_watermark_imagick($name, $ext, $logo_path);
        return;
    }
	
	//now, lets work and detect our image extension
	list($bwidth, $bheight, $src_img_type) = getimagesize($name);
	
	$src_img = false;
    
    switch ($src_img_type)
    {
        case IMAGETYPE_GIF:
            //$src_img = imagecreatefromgif($name);
            return;

            break;

        case IMAGETYPE_JPEG:
            $src_img = imagecreatefromjpeg($name);

            break;

        case IMAGETYPE_PNG:
            $src_img = imagecreatefrompng($name);

            break;

        case IMAGETYPE_BMP:
            if (! function_exists('imagecreatefrombmp'))
            {
                include dirname(__file__) . '/BMP.php';
            }

            $src_img = imagecreatefrombmp($name);

            break;
    }

    if (! $src_img)
    {
        return;
    }

    //detect width, height for the watermark image
    $lwidth  = @imagesx($src_logo);
    $lheight = @imagesy($src_logo);


    if ($bwidth > $lwidth+5 &&  $bheight > $lheight+5)
    {
        //where exaxtly do we have to make the watermark ..
        $src_x = $bwidth  - ($lwidth + 5);
        $src_y = $bheight - ($lheight + 5);

        //make it now, watermark it
        @imagealphablending($src_img, true);
        @imagecopy($src_img, $src_logo, $src_x, $src_y, 0, 0, $lwidth, $lheight);

        if (strpos($ext, 'jp') !== false)
        {
            //no compression, same quality
            @imagejpeg($src_img, $name, 100);
        }
        elseif (strpos($ext, 'png') !== false)
        {
            //no compression, same quality
            @imagepng($src_img, $name, 0);
        }
        elseif (strpos($ext, 'gif') !== false)
        {
            @imagegif($src_img, $name);
        }
        elseif (strpos($ext, 'bmp') !== false)
        {
            @imagebmp($src_img, $name);
        }
    }
    else
    {
        //image is not big enough to watermark it
        return;
    }
}


//
// generate watermarked images by imagick
//
function helper_watermark_imagick($name, $ext, $logo)
{
    //Not just me babe, All the places misses you ..
    $im = new Imagick($name);

    $watermark = new Imagick($logo);
    //$watermark->readImage($);

    //how big are the images?
    $iWidth    = $im->getImageWidth();
    $iHeight   = $im->getImageHeight();
    $wWidth    = $watermark->getImageWidth();
    $wHeight   = $watermark->getImageHeight();

    if ($iHeight < $wHeight || $iWidth < $wWidth)
    {
        //resize the watermark
        $watermark->scaleImage($iWidth, $iHeight);

        //get new size
        $wWidth  = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();
    }

    //calculate the position
    $x = $iWidth  - ($wWidth - 5);
    $y = $iHeight - ($wHeight - 5);

    //an exception for gif image
    //generating thumb with 10 frames only, big gif is a devil
    if ($ext == 'gif')
    {
        $i = 0;
        //$gif_new = new Imagick();
        foreach ($im as $frame)
        {
            $frame->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);

            //    $gif_new->addImage($frame->getImage());
            if ($i >= 10)
            {
                // more than 10 frames, quit it
                break;
            }
            $i++;
        }
        $im->writeImages($name, true);
        return;
    }

    $im->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);

    $im->writeImages($name, false);
}
