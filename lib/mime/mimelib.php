<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
 *
 */
class MimeLib
{
    /**
     * @param $filename
     * @param $path
     * @return string
     */
    public static function from_path($filename, $path)
    {
        if ($type = self::physical_check_from_path($path)) {
            return self::handle_physical_exceptions($type, $filename);
        }

        return self::from_file_extension($filename);
    }

    /**
     * @param $filename
     * @param $content
     * @return string
     */
    public static function from_content($filename, $content)
    {
        if ($type = self::physical_check_from_content($content)) {
            return self::handle_physical_exceptions($type, $filename, $content);
        }

        return self::from_file_extension($filename);
    }

    /**
     * @param $filename
     * @return string
     */
    public static function from_filename($filename)
    {
        return self::from_file_extension($filename);
    }

    /**
     * @param $type
     * @param $filename
     * @param $content
     * @return string
     */
    private static function handle_physical_exceptions($type, $filename, $content = '')
    {
        global $prefs;

        if ($type === 'application/zip' || $type === 'application/octet-stream' || $type === 'application/vnd.ms-office') {
            return self::from_file_extension($filename);
        } elseif ($type === 'text/html' && self::get_extension($filename) == 'svg') {
            $type = 'image/svg+xml';
        } elseif ($type === 'text/html' && $prefs['vimeo_upload'] === 'y' && is_numeric($filename)) {
            if (strpos($content, 'vimeo.com') !== false) {
                $type = 'video/vimeo';
            }
        } else {
            $extension = self::get_extension($filename);

            if (in_array($extension, ["xlsx", "xltx", "potx", "ppsx", "pptx", "sldx", "docx", "dotx", "xlam", "xlsb"])) {
                return self::from_file_extension($filename);
            }
        }

        return $type;
    }

    /**
     * @param $filename
     * @return string
     */
    private static function get_extension($filename)
    {
        $ext = pathinfo($filename);

        return isset($ext['extension']) ? $ext['extension'] : '';
    }

    /**
     * @param $filename
     * @return string
     */
    private static function from_file_extension($filename)
    {
        global $mimetypes;
        include_once(__DIR__ . '/../mime/mimetypes.php');

        if (isset($mimetypes)) {
            $ext = self::get_extension($filename);
            $mimetype = isset($mimetypes[$ext]) ? $mimetypes[$ext] : '';

            if (! empty($mimetype)) {
                return $mimetype;
            }
        }

        return "application/octet-stream";
    }

    /**
     * @param $path
     * @return string
     */
    private static function physical_check_from_path($path)
    {
        if ($finfo = self::get_finfo()) {
            if (file_exists($path)) {
                $type = $finfo->file($path); // Heuristically determine most likely MIME type based on the contents of the file at $path

                return $type;
            }
        }
    }

    /**
     * @param $content
     * @return string
     */
    private static function physical_check_from_content($content)
    {
        if ($finfo = self::get_finfo()) {
            $type = $finfo->buffer($content);

            return $type;
        }
    }

    /**
     * @return finfo
     */
    private static function get_finfo()
    {
        static $finfo = false;
        global $prefs;

        if ($finfo) {
            return $finfo;
        }

        if ($prefs['tiki_check_file_content'] == 'y' && class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo) {
                return $finfo;
            }
        }
    }
}
