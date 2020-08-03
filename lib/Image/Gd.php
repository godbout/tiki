<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Lib\Image;

/**
 *
 */
class Gd extends ImageAbstract
{
    protected $gdinfo;
    protected $gdversion;
    protected $havegd = false;

    /**
     * @param $image
     * @param bool $isfile
     * @param string $format
     */
    public function __construct($image, $isfile = false, $format = 'jpeg')
    {

        // Which GD Version do we have?
        $exts = get_loaded_extensions();
        if (in_array('gd', $exts) && ! empty($image)) {
            $this->havegd = true;
            $this->getGdInfo();
            if ($isfile) {
                $this->filename = $image;
                parent::__construct(null, false);
                $this->loaded = false;
            } else {
                parent::__construct($image, false);
                $this->format = $format;
                $this->loaded = false;
            }
        } else {
            $this->havegd = false;
            $this->gdinfo = [];
        }
    }

    protected function loadData()
    {
        if (! $this->loaded && $this->havegd) {
            if (! empty($this->filename) && is_file($this->filename)) {
                $this->format = strtolower(substr($this->filename, strrpos($this->filename, '.') + 1));
                list($this->width, $this->height, $type) = getimagesize($this->filename);
                if (function_exists("image_type_to_extension")) {
                    $this->format = image_type_to_extension($type, false);
                } else {
                    $tmp = image_type_to_mime_type($type);
                    $this->format = strtolower(substr($tmp, strrpos($tmp, "/") + 1));
                }
                if ($this->isSupported($this->format)) {
                    if ($this->format == 'jpg') {
                        $this->format = 'jpeg';
                    }
                    $this->data = call_user_func('imagecreatefrom' . $this->format, $this->filename);
                    $this->loaded = true;
                }
            } elseif (! empty($this->data) &&
                $this->data != 'REFERENCE' &&
                preg_match('/^[<]svg/', $this->data) == false //In some cases, an svg will be recognized as an alternate picture type, here we simply check the beginning for "<svg" and if it is found, it is an svg
            ) {
                $this->data = imagecreatefromstring($this->data);
                $this->loaded = true;
            } else {
                parent::loadData();
            }
        }
    }

    /**
     * @param $x
     * @param $y
     */
    protected function resizeImage($x, $y)
    {
        if ($this->data) {
            if ($this->format == 'svg') {
                $svgAttributes = ' width="' . $x . '" height="' . $y . '" viewBox="0 0 ' . $this->width . ' ' . $this->height . '" preserveAspectRatio="xMinYMin meet"';
                $this->data = preg_replace('/width="' . $this->width . '" height="' . $this->height . '"/', $svgAttributes, $this->data);
            } else {
                $t = imagecreatetruecolor($x, $y);
                // trick #2 to have a transparent background for png instead of black
                imagesavealpha($t, true);
                imagealphablending($t, false);
                imagecolortransparent($t, imagecolorallocatealpha($t, 0, 0, 0, 127));

                imagecopyresampled($t, $this->data, 0, 0, 0, 0, $x, $y, $this->getWidth(), $this->getHeight());
                $this->data = $t;
                unset($t);
            }
        }
    }

    public function resizeThumb()
    {
        if ($this->thumb !== null) {
            $this->data = imagecreatefromstring($this->thumb);
            $this->loaded = true;
        } else {
            $this->loadData();
        }

        return parent::resizeThumb();
    }

    /**
     * @return null|string
     */
    public function display()
    {
        $this->loadData();
        if ($this->data) {
            //@ob_end_flush();	// ignore E_NOTICE if no buffer
            ob_start();
            switch (strtolower($this->format)) {
                case 'jpeg':
                case 'jpg':
                    if ($this->quality !== 75) {
                        imagejpeg($this->data, null, $this->quality);
                    } else {
                        imagejpeg($this->data);
                    }

                    break;
                case 'gif':
                    imagegif($this->data);

                    break;
                case 'png':
                    if ($this->quality !== 75) {
                        imagepng($this->data, null, (int)$this->quality / 10);
                    } else {
                        imagepng($this->data);
                    }

                    break;
                case 'wbmp':
                    imagewbmp($this->data);

                    break;
                case 'svg':
                    echo $this->data;

                    break;
                default:
                    ob_end_clean();

                    return null;
            }
            $image = ob_get_contents();
            ob_end_clean();

            return $image;
        }

        return null;
    }

    /**
     * @param $angle
     * @return bool
     */
    public function rotate($angle)
    {
        $this->loadData();
        if ($this->data) {
            $this->data = imagerotate($this->data, $angle, 0);

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getGdInfo()
    {
        $gdinfo = [];
        $gdversion = '';

        if (function_exists("gd_info")) {
            $gdinfo = gd_info();
            preg_match("/[0-9]+\.[0-9]+/", $gdinfo["GD Version"], $gdversiontmp);
            $gdversion = $gdversiontmp[0];
        } else {
            //next try
            ob_start();
            phpinfo(INFO_MODULES);
            $gdversion = preg_match('/GD Version.*2.0/', ob_get_contents()) ? '2.0' : '1.0';
            $gdinfo["JPG Support"] = preg_match('/JPG Support.*enabled/', ob_get_contents());
            $gdinfo["PNG Support"] = preg_match('/PNG Support.*enabled/', ob_get_contents());
            $gdinfo["GIF Create Support"] = preg_match('/GIF Create Support.*enabled/', ob_get_contents());
            $gdinfo["WBMP Support"] = preg_match('/WBMP Support.*enabled/', ob_get_contents());
            $gdinfo["XBM Support"] = preg_match('/XBM Support.*enabled/', ob_get_contents());
            ob_end_clean();
        }

        if (isset($this)) {
            $this->gdinfo = $gdinfo;
            $this->gdversion = $gdversion;
        }

        return $gdinfo;
    }

    // This method do not need to be called on an instance
    /**
     * @param $format
     * @return bool|int
     */
    public function isSupported($format)
    {
        if (! function_exists('imagetypes')) {
            $gdinfo = isset($this) ? $this->gdinfo : $this->getGdInfo();
        }

        switch (strtolower($format)) {
            case 'jpeg':
            case 'jpg':
                if (isset($gdinfo) && $gdinfo['JPG Support']) {
                    return true;
                }

                    return (imagetypes() & IMG_JPG);
                
            case 'png':
                if (isset($gdinfo) && $gdinfo['PNG Support']) {
                    return true;
                }

                    return (imagetypes() & IMG_PNG);
                
            case 'gif':
                if (isset($gdinfo) && $gdinfo['GIF Create Support']) {
                    return true;
                }

                    return (imagetypes() & IMG_GIF);
                
            case 'wbmp':
                if (isset($gdinfo) && $gdinfo['WBMP Support']) {
                    return true;
                }

                    return (imagetypes() & IMG_WBMP);
                
            case 'xpm':
                if (isset($gdinfo) && $gdinfo['XPM Support']) {
                    return true;
                }

                    return (imagetypes() & IMG_XPM);
                
            case 'svg':
                return true;
        }

        return false;
    }

    /**
     * @return int|null
     */
    protected function getHeightImpl()
    {
        if ($this->loaded && $this->data) {
            if ($this->format == 'svg') {
                if (preg_match('/height="(\d+)"/', $this->data, $match)) {
                    return $match[1];
                }
            } else {
                return @imagesy($this->data);
            }
        } elseif ($this->height) {
            return $this->height;
        } elseif ($this->filename && is_readable($this->filename)) {
            list($this->width, $this->height, $type) = getimagesize($this->filename);
            if ($this->height) {
                return $this->height;
            }
        }
        if (! $this->loaded || ! $this->data) {
            $this->loadData();
        }
        if ($this->data) {
            return @imagesy($this->data);
        }
    }

    /**
     * @return int|null
     */
    protected function getWidthImpl()
    {
        if ($this->loaded && $this->data) {
            if ($this->format == 'svg') {
                if (preg_match('/width="(\d+)"/', $this->data, $match)) {
                    return $match[1];
                }
            } else {
                return @imagesx($this->data);
            }
        } elseif ($this->width) {
            return $this->width;
        } elseif ($this->filename && is_readable($this->filename)) {
            list($this->width, $this->height, $type) = getimagesize($this->filename);
            if ($this->width) {
                return $this->width;
            }
        }
        if (! $this->loaded || ! $this->data) {
            $this->loadData();
        }
        if ($this->data) {
            return @imagesx($this->data);
        }
    }


    /**
     * Allow adding text as overlay to a image
     * @param $text
     * @return string || boolean
     */
    public function addTextToImage($text)
    {
        if (! $this->loaded) {
            $this->loadData();
        }

        if (! $this->data) {
            return false;
        }

        $fontFile = '/lib/captcha/DejaVuSansMono.ttf';
        $padLeft = 20;
        $padBottom = $this->getHeight() - 20;
        $fontSize = 12;
        $textAngle = 0;
        $fontColor = imagecolorallocate($this->data, 255, 255, 0);
        $fontStroke = imagecolorallocate($this->data, 0, 0, 0);
        $fontStrokeWidth = 2;

        putenv('GDFONTPATH=' . realpath('.'));
        $result = $this->imageTtfStrokeText($this->data, $fontSize, $textAngle, $padLeft, $padBottom, $fontColor, $fontStroke, $fontFile, $text, $fontStrokeWidth);
        if (! $result) {
            return false;
        }

        return true;
    }

    /**
     * Adds text to the image, with a "shadow", to improve readability
     *
     * @param $image The current image (will be changed)
     * @param $size
     * @param $angle
     * @param $x
     * @param $y
     * @param $textcolor
     * @param $strokecolor
     * @param $fontfile
     * @param $text
     * @param $px
     * @return array
     */
    protected function imageTtfStrokeText(&$image, $size, $angle, $x, $y, $textcolor, $strokecolor, $fontfile, $text, $px)
    {
        for ($c1 = ($x - abs($px)); $c1 <= ($x + abs($px)); $c1++) {
            for ($c2 = ($y - abs($px)); $c2 <= ($y + abs($px)); $c2++) {
                imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
            }
        }

        return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
    }
}
