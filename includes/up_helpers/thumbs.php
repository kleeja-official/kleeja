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
 * Creates a a thumbnail of an image
 * @example helper_thumb('pics/apple.jpg','thumbs/tn_apple.jpg',100,100);
 * @param  string    $source_path
 * @param  string    $ext
 * @param  string    $dest_image
 * @param  int       $dw
 * @param  int       $dh
 * @return bool|null
 */
function helper_thumb($source_path, $ext, $dest_image, $dw, $dh)
{
    //no file, quit it
    if (! file_exists($source_path))
    {
        return null;
    }

    //check width, height
    if (intval($dw) == 0 || intval($dw) < 10)
    {
        $dw = 100;
    }

    if (intval($dh) == 0 || intval($dh) < 10)
    {
        $dh = $dw;
    }

    //if there is imagick lib, then we should use it
    if (function_exists('phpversion') && phpversion('imagick'))
    {
        $ext = strtolower(trim($ext));

        if (empty($ext))
        {
            $ext = strtolower(preg_replace('/^.*\./', '', $source_path));
        }

        helper_thumb_imagick($source_path, $ext, $dest_image, $dw, $dh);
        return null;
    }

    if (! function_exists('imagecreatefromjpeg') || ! function_exists('getimagesize'))
    {
        return null;
    }

    //get file info
    list($source_width, $source_height, $source_type) = getimagesize($source_path);
    
    $source_gdim = false;
    
    switch ($source_type)
    {
        case IMAGETYPE_GIF:
            $source_gdim = imagecreatefromgif($source_path);

            break;

        case IMAGETYPE_JPEG:
            $source_gdim = imagecreatefromjpeg($source_path);

            break;

        case IMAGETYPE_PNG:
            $source_gdim = imagecreatefrompng($source_path);

            break;

        case IMAGETYPE_BMP:
            if (! function_exists('imagecreatefrombmp'))
            {
                include dirname(__file__) . '/BMP.php';
            }

            $source_gdim = imagecreatefrombmp($source_path);

            break;
    }

    if (! $source_gdim)
    {
        return null;
    }

    $source_aspect_ratio  = $source_width / $source_height;
    $desired_aspect_ratio = $dw           / $dh;

    if ($source_aspect_ratio > $desired_aspect_ratio)
    {
        // Triggered when source image is wider
        $temp_height = $dh;
        $temp_width  = (int) ($dh * $source_aspect_ratio);
    }
    else
    {
        // Triggered otherwise (i.e. source image is similar or taller)
        $temp_width  = $dw;
        $temp_height = (int) ($dw / $source_aspect_ratio);
    }

    // Resize the image into a temporary GD image
    $temp_gdim = imagecreatetruecolor($temp_width, $temp_height);

    imagecopyresampled(
        $temp_gdim,
        $source_gdim,
        0, 0,
        0, 0,
        $temp_width, $temp_height,
        $source_width, $source_height
    );

    // Copy cropped region from temporary image into the desired GD image
    $x0 = (int) (($temp_width - $dw)  / 2);
    $y0 = (int) (($temp_height - $dh) / 2);

    $desired_gdim = imagecreatetruecolor($dw, $dh);
    imagecopy(
        $desired_gdim,
        $temp_gdim,
        0, 0,
        $x0, $y0,
        $dw, $dh
    );

    // Create thumbnail
    switch (strtolower(preg_replace('/^.*\./', '', $dest_image)))
    {
        case 'jpg':
        case 'jpeg':
            $return = @imagejpeg($desired_gdim, $dest_image, 90);

            break;

        case 'png':
            $return = @imagepng($desired_gdim, $dest_image);

            break;

        case 'gif':
            $return = @imagegif($desired_gdim, $dest_image);

        break;

        case 'bmp':
            $return = @imagebmp($desired_gdim, $dest_image);

            break;

        default:
            // Unsupported format
            $return = false;
    }

    @imagedestroy($desired_gdim);
    @imagedestroy($source_gdim);

    return $return;
}



/**
 * generating thumb from image using Imagick
 * 
 * @param mixed $x
 * @param mixed $y
 * @param mixed $cx
 * @param mixed $cy
 */
function scale_image_imagick($x, $y, $cx, $cy)
{
    //Set the default NEW values to be the old, in case it doesn't even need scaling
    list($nx, $ny) = [$x, $y];

    //If image is generally smaller, don't even bother
    if ($x >= $cx || $y >= $cx)
    {
        $rx = $ry = 0;

        //Work out ratios
        if ($x > 0)
        {
            $rx = $cx / $x;
        }

        if ($y > 0)
        {
            $ry = $cy / $y;
        }

        //Use the lowest ratio, to ensure we don't go over the wanted image size
        if ($rx > $ry)
        {
            $r = $ry;
        }
        else
        {
            $r = $rx;
        }

        //Calculate the new size based on the chosen ratio
        $nx = intval($x * $r);
        $ny = intval($y * $r);
    }

    //Return the results
    return [$nx, $ny];
}

function helper_thumb_imagick($name, $ext, $filename, $new_w, $new_h)
{
    //intiating the Imagick lib    
    $im = new Imagick($name);

    //guess the right thumb height, weights
    list($thumb_w, $thumb_h) = scale_image_imagick(
                    $im->getImageWidth(),
                    $im->getImageHeight(),
                    $new_w,
                    $new_h);

    //an exception for gif image
    //generating thumb with 10 frames only, big gif is a devil
    if ($ext == 'gif')
    {
        $i = 0;
        //$gif_new = new Imagick(); 
        foreach ($im as $frame)
        {
            $frame->thumbnailImage($thumb_w, $thumb_h);
            $frame->setImagePage($thumb_w, $thumb_h, 0, 0);
            //    $gif_new->addImage($frame->getImage()); 
            if ($i >= 10)
            {
                // more than 10 frames, quit it
                break;
            }
            $i++;
        }
        $im->writeImages($filename, true);
        return;
    }

    //and other image extension use one way
    $im->thumbnailImage($thumb_w, $thumb_h);

    //right it
    $im->writeImages($filename, false);
}
