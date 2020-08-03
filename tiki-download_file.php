<?php
/**
 * @package tikiwiki
 */
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use \Tiki\File\PDFHelper;
use Tiki\Lib\Image\Image;

$force_no_compression = true;
$skip = false;
$thumbnail_format = 'jpeg';

require_once('tiki-setup.php');

global $user;

if (isset($_GET['fileId']) && isset($_GET['thumbnail']) && isset($_COOKIE[ session_name() ]) && count($_GET) == 2 && isset($_SESSION['allowed'][$_GET['fileId']])) {
    $query = "select * from `tiki_files` where `fileId`=?";
    $result = $tikilib->query($query, [(int)$_GET['fileId']]);
    if ($result) {
        $info = $result->fetchRow();
        $skip = true;
    } else {
        $info = [];
    }
}

if (! $skip) {
    $filegallib = TikiLib::lib('filegal');
    $access->check_feature('feature_file_galleries');
}

if ($prefs["user_store_file_gallery_picture"] == 'y' && isset($_REQUEST["avatar"])) {
    $userprefslib = TikiLib::lib('userprefs');
    if ($user_picture_id = $userprefslib->get_user_picture_id($_REQUEST["avatar"])) {
        $_REQUEST['fileId'] = $user_picture_id;
    } elseif (! empty($prefs['user_default_picture_id'])) {
        $_REQUEST['fileId'] = $prefs['user_default_picture_id'];
    }
}

    @set_time_limit(0);


$zip = false;
$error = '';

if (! $skip) {
    if (isset($_REQUEST['fileId']) && ! is_array($_REQUEST['fileId'])) {
        if (isset($_GET['draft'])) {
            $info = \Tiki\FileGallery\FileDraft::id($_REQUEST['fileId'])->getParams();
        } else {
            $info = $filegallib->get_file($_REQUEST['fileId']);
        }
    } elseif (isset($_REQUEST['galleryId']) && isset($_REQUEST['name'])) {
        $info = $filegallib->get_file_by_name($_REQUEST['galleryId'], $_REQUEST['name']);
    } elseif (isset($_REQUEST['fileId']) && is_array($_REQUEST['fileId'])) {
        $info = $filegallib->zip($_REQUEST['fileId'], $error);
        $zip = true;
    } elseif (! empty($_REQUEST['randomGalleryId'])) {
        $info = $filegallib->get_file(0, $_REQUEST['randomGalleryId']);
    } else {
        $access->display_error('', tra('Incorrect param'), 400);
    }
    if (! is_array($info)) {
        $access->display_error(null, tra('File has been deleted'), 404);
    }
    if ($prefs['auth_token_access'] != 'y' || ! $is_token_access) {
        // Check permissions except if the user comes with a valid Token

        if ($tiki_p_admin_file_galleries != 'y' && $info['backlinkPerms'] == 'y' && $filegallib->hasOnlyPrivateBacklinks($info['fileId'])) {
            if (! $user && $prefs['permission_denied_login_box'] === 'y' && empty($_SESSION['loginfrom'])) {
                $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
            }
            $access->display_error('', tra('Permission denied'), 401);
        }

        if (! $zip && $tiki_p_admin_file_galleries != 'y' && ! $userlib->user_has_perm_on_object($user, $info['fileId'], 'file', 'tiki_p_download_files')) {
            if (! $user && $prefs['permission_denied_login_box'] === 'y' && empty($_SESSION['loginfrom'])) {
                $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
            }
            $access->display_error('', tra('Permission denied'), 401);
        }
        if (isset($_GET['thumbnail']) && is_numeric($_GET['thumbnail'])) { //check also perms on thumb
            $info_thumb = $filegallib->get_file($_GET['thumbnail']);
            if (! $zip && $tiki_p_admin_file_galleries != 'y' && ! $userlib->user_has_perm_on_object($user, $info_thumb['fileId'], 'file', 'tiki_p_download_files')) {
                if (! $user && $prefs['permission_denied_login_box'] === 'y' && empty($_SESSION['loginfrom'])) {
                    $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
                }
                $access->display_error('', tra('Permission denied'), 401);
            }
        }
        if ($prefs['feature_use_fgal_for_user_files'] === 'y' && $tiki_p_admin_file_galleries !== 'y' && $prefs['userfiles_private'] === 'y') {
            $gal_info = $filegallib->get_file_gallery_info($info['galleryId']);
            if ($gal_info['type'] === 'user' && $gal_info['visible'] !== 'y' && $gal_info['user'] !== $user) {
                $access->display_error('', tra('Permission denied'), 401);
            }
        }
    }
}

//if the file is remote, display, and don't cache
$attributelib = TikiLib::lib('attribute');
$attributes = $attributelib->get_attributes('file', $info['fileId']);

if (isset($attributes['tiki.content.url'])) {
    $smarty->loadPlugin('smarty_modifier_sefurl');
    $src = smarty_modifier_sefurl($info['fileId'], 'file');
    session_write_close();

    $client = $tikilib->get_http_client($src);
    $response = $client->send();
    header('Content-Type: ' . $response->getHeaders()->get('Content-Type')->getFieldValue());
    echo $response->getBody();
    exit();
}

// Add hits ( if download or display only ) + lock if set
if (! isset($_GET['thumbnail']) && ! isset($_GET['icon'])) {
    $statslib = TikiLib::lib('stats');
    $filegallib = TikiLib::lib('filegal');
    if (! $filegallib->add_file_hit($info['fileId'])) {
        $access->display_error('', tra('You cannot download this file right now. Your score is low or file limit was reached.'), 401);
    }
    $statslib->stats_hit($info['filename'], 'file', $info['fileId']);

    if ($prefs['feature_actionlog'] == 'y') {
        $logslib = TikiLib::lib('logs');
        $logslib->add_action('Downloaded', $info['galleryId'], 'file gallery', 'fileId=' . $info['fileId']);
    }

    if (! empty($_REQUEST['lock']) && $access->checkCsrf(true)) {
        if (! empty($info['lockedby']) && $info['lockedby'] != $user) {
            Feedback::error(tr(sprintf('The file has been locked by %s', $info['lockedby'])));
        }
        $result = $filegallib->lock_file($info['fileId'], $user);
        if ($result && $result->numRows()) {
            Feedback::success(tr('File locked'));
        } else {
            Feedback::error(tr('File not locked'));
        }
    }
}

session_write_close(); // close the session in case of large downloads to enable further browsing
error_reporting(E_ALL);
while (ob_get_level() > 1) {
    ob_end_clean();
}// Be sure output buffering is turned off

$file = new \Tiki\FileGallery\File($info);
$wrapper = $file->getWrapper();

$content_changed = false;
$md5 = $wrapper->getChecksum();
$last_modified = $file->lastModif;

// local files can be read and served in chunks
if ($wrapper->isFileLocal()) {
    $filepath = $wrapper->getReadableFile();
    $content = '';
} else {
    $filepath = '';
    $content = $wrapper->getContents();
}

$scale = 0;
if (isset($_GET['scale'])) {
    $scale = (float)$_GET['scale'];
}
if ($scale >= 1) {
    $scale = 0;
}

// ETag: Entity Tag used for strong cache validation.
if (! isset($_GET['display']) || isset($_GET['x']) || isset($_GET['y']) || $scale || isset($_GET['max']) || isset($_GET['format'])) {
    // if image will be modified, emit a different ETag for modifications.
    $str = isset($_GET['x']) ? $_GET['x'] . 'x' : '';
    $str .= isset($_GET['y']) ? $_GET['y'] . 'y' : '';
    $str .= $scale ? $scale . 's' : '';
    $str .= isset($_GET['max']) ? $_GET['max'] . 'm' : '';
    $str .= isset($_GET['format']) ? $_GET['format'] . 'f' : '';
    $etag = '"' . $md5 . '-' . crc32($md5) . '-' . crc32($str) . '"';
} else {
    $etag = '"' . $md5 . '-' . crc32($md5) . '"';
}
header('ETag: ' . $etag);

$use_client_cache = false;
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $last_modified == strtotime(current($a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'])))) {
    $use_client_cache = true;
} elseif (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
    $tmp = array_map('trim', explode(',', $_SERVER['HTTP_IF_NONE_MATCH']));
    foreach ($tmp as $v) {
        if ($v == '*' || $v == $etag) {
            $use_client_cache = true;

            break;
        }
    }
    unset($tmp);
}

header("Pragma: ");
header('Expires: ');
header('Cache-Control: ' . (! empty($user) ? 'private' : 'public') . ',must-revalidate,post-check=0,pre-check=0');

if ($use_client_cache) {
    header('Status: 304 Not Modified', true, 304);
    exit;
}
    if (! empty($last_modified)) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
    }


// Indicates if a 'office' document should be converted to pdf for download or display in browser.
$convertToPdf = (isset($_GET['display']) || isset($_GET['pdf'])) && PDFHelper::canConvertToPDF($info['filetype']) && $prefs['fgal_convert_documents_pdf'] == 'y';

// Handle images display, files thumbnails and icons
if (isset($_GET['preview']) || isset($_GET['thumbnail']) || isset($_GET['display']) || isset($_GET['icon']) || $convertToPdf) {
    $use_cache = false;

    // Cache only thumbnails to avoid DOS attacks
    $cacheName = '';
    $cacheType = '';
    $cachelib = TikiLib::lib('cache');

    if ((isset($_GET['thumbnail']) || isset($_GET['preview']) || $convertToPdf) && ! isset($_GET['display']) && ! isset($_GET['icon']) && ! $scale && ! isset($_GET['x']) && ! isset($_GET['y']) && ! isset($_GET['format']) && ! isset($_GET['max'])) {
        $use_cache = true;
        $cacheName = $md5;

        if ($convertToPdf) {
            $cacheTypePrefix = 'pdf_';
        } elseif (isset($_GET['thumbnail'])) {
            $cacheTypePrefix = 'thumbnail_';
        } else {
            $cacheTypePrefix = 'preview_';
        }
        $cacheType = $cacheTypePrefix . ((int)$_REQUEST['fileId']) . '_';
    }

    $build_content = true;
    $content_temp = $cachelib->getCached($cacheName, $cacheType);
    if ($use_cache && $content_temp) {
        if ($content_temp !== serialize(false) and $content_temp != "") {
            $build_content = false;
            $content = $content_temp;
        }
        $content_changed = true;
    }
    unset($content_temp);

    if ($build_content) {
        if ($convertToPdf) {
            $pdfFile = PDFHelper::convertToPDF($_REQUEST['fileId']);
            $content = file_get_contents($pdfFile);
            unlink($pdfFile);
            $content_changed = true;
        } elseif (! isset($_GET['display']) || isset($_GET['x']) || isset($_GET['y']) || $scale || isset($_GET['max']) || isset($_GET['format']) || isset($_GET['thumbnail'])) {
            // Modify the original image if needed
            if (! Image::isAvailable()) {
                die();
            }

            $content_changed = true;
            $format = substr($info['filename'], strrpos($info['filename'], '.') + 1);

            // Fallback to an icon if the format is not supported
            $tmp = Image::create('img/trans.png', true, 'png');	// needed to call non-static Image functions non-statically
            if (! $tmp->isSupported($format)) {
                // Is the filename correct? Maybe it doesn't have an extenstion?
                // Try to determine the format from the filetype too
                $format = substr($info['filetype'], strrpos($info['filetype'], '/') + 1);
                if (! $tmp->isSupported($format)) {
                    $_GET['icon'] = 'y';
                    $_GET['max'] = 32;
                }
            }

            do {
                $tryIconFallback = false;

                if (isset($_GET['icon'])) {
                    unset($filepath);
                    $content = null; // Explicitely free memory before generating icon

                    if (isset($_GET['max'])) {
                        $icon_x = $_GET['max'];
                        $icon_y = $_GET['max'];
                    } else {
                        $icon_x = isset($_GET['x']) ? $_GET['x'] : 0;
                        $icon_y = isset($_GET['y']) ? $_GET['y'] : 0;
                    }

                    $ext = pathinfo($info['filename']);	// TODO replace with mimelib functions
                    $format = isset($ext['extension']) ? $ext['extension'] : $format;
                    $content = $tmp->icon($format, $icon_x, $icon_y);
                    $format = $tmp->getIconDefaultFormat();
                    $info['filetype'] = 'image/' . $format;
                    $info['lastModif'] = 0;
                }

                if (! isset($_GET['icon']) || (isset($_GET['format']) && $_GET['format'] != $format)) {
                    if (! empty($filepath)) {
                        $image = Image::create($filepath, true, $format);
                    } else {
                        $image = Image::create($content, false, $format);
                        $content = null; // Explicitely free memory before getting cache
                    }
                    if ($image->isEmpty()) {
                        die;
                    }

                    $resize = false;
                    // We resize if needed
                    if (isset($_GET['x']) || isset($_GET['y'])) {
                        $image->resize(isset($_GET['x']) ? (int) $_GET['x'] : 0, isset($_GET['y']) ? (int) $_GET['y'] : 0);
                        $resize = true;
                    } elseif ($scale) {
                        // We scale if needed
                        $image->scale($scale);
                        $resize = true;
                    } elseif (isset($_GET['max'])) {
                        // We reduce size if length or width is greater that $_GET['max'] if needed
                        $image->resizeMax($_GET['max'] + 0);
                        $resize = true;
                    } elseif (isset($_GET['thumbnail'])) {
                        // We resize to a thumbnail size if needed
                        if (is_numeric($_GET['thumbnail'])) {
                            if (empty($info_thumb)) {
                                $info_thumb = $filegallib->get_file($_GET['thumbnail']);
                            }
                            $file_thumb = new \Tiki\TikiFile\File($info_thumb);
                            $image = Image::create($file_thumb->getContents());
                            $content = null; // Explicitely free memory before getting cache
                            if ($image->isEmpty()) {
                                die;
                            }
                        }
                        $image->resizeThumb();
                    } elseif (isset($_GET['preview'])) {
                        // We resize to a preview size if needed
                        $image->resizeMax('800');
                        $resize = true;
                    }

                    // We change the image format if needed
                    if (isset($_GET['format']) && $image->isSupported($_GET['format'])) {
                        if (isset($_GET['quality'])) {
                            $image->setQuality($_GET['quality']);
                        }
                        $image->convert($_GET['format']);
                    } elseif (isset($_GET['thumbnail']) && $image->getFormat() != 'svg') {
                        // Or, if no format is explicitely specified and a thumbnail has to be created, we convert the image to the $thumbnail_format
                        if ($image->getFormat()) {
                            $thumbnail_format = $image->getFormat();	// preserves transparency if png or gif
                        }
                        $image->convert($thumbnail_format);
                    }

                    $content = $image->display();

                    // If the new image creating has failed, fallback to an icon
                    if (! isset($_GET['icon']) && ($content === null || $content === false)) {
                        $tryIconFallback = true;
                        $_GET['icon'] = 'y';
                        $_GET['max'] = 32;
                    } else {
                        $info['filetype'] = $image->getMimeType();
                    }
                }
            } while ($tryIconFallback);
        }
        if (strpos($info['filetype'], 'image/svg') !== false) {
            $info['filetype'] = 'image/svg+xml';
            $content = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n" . $content;
        }

        if ($info['filetype'] === 'text/plain') {
            $content = nl2br($content);
        }

        if ($use_cache && ! empty($content)) {
            // Remove all existing thumbnails for this file, to avoid taking too much disk space
            // (only one thumbnail size is handled at the same time)
            $cachelib->empty_type_cache($cacheType);

            // Cache Thumbnail
            $cachelib->cacheItem($cacheName, $content, $cacheType);
        }
    }
}

if ($convertToPdf) {
    // Replace file metadata to output in response headers
    $info['filetype'] = 'application/pdf';
    $info['filename'] = pathinfo($info['filename'], PATHINFO_FILENAME) . '.pdf';
}

$mimelib = TikiLib::lib('mime');
if (empty($info['filetype']) || $info['filetype'] == 'application/x-octetstream'
            || $info['filetype'] == 'application/octet-stream' || $info['filetype'] == 'unknown') {
    $info['filetype'] = $mimelib->from_path($info['filename'], $filepath);
} elseif (isset($_GET['thumbnail']) && (strpos($info['filetype'], 'image') === false || ($content_changed && strpos($info['filetype'], 'image/svg') === false))) {	// use thumb format
    $info['filetype'] = $mimelib->from_content($info['filename'], $content);
}
header('Content-type: ' . $info['filetype']);

// IE6 can not download file with / in the name (the / can be there from a previous bug)
$file = basename($info['filename']);

// If the content has not changed, ask the browser to download it (instead of displaying it)
if ((! $content_changed and ! isset($_GET['display'])) || isset($_GET['pdf'])) {
    header("Content-Disposition: attachment; filename=\"$file\"");
} else {
    header("Content-Disposition: filename=\"$file\"");
}

if (! empty($filepath) and ! $content_changed) {
    $filesize = filesize($filepath);
    header("Accept-Ranges: bytes");
    if (empty($_SERVER['HTTP_RANGE'])) {
        header('Content-Length: ' . $filesize);
        readfile($filepath);
    } else {
        // support media range requests here, e.g. bytes=524288-524288 bytes=0- or bytes=0-1 etc
        $range = preg_split('/[=-]/', $_SERVER['HTTP_RANGE']);
        $start = strlen($range[1]) ? (int) $range[1] : 0;
        if ($start >= $filesize) {
            $start = $filesize - 1;
        }
        $end = strlen($range[2]) ? (int) $range[2] : $filesize - 1;
        if ($end >= $filesize) {
            $end = $filesize - 1;
        }
        header('HTTP/1.1 206 Partial Content');
        // FIXME Safari seems to fail when getting range 0-1 with Content-Length: 2 so leave it out for now
        //header('Content-Length: ' . ($end - $start + 1));
        header("Content-Range: bytes $start-$end/$filesize");
        header("Content-Disposition: inline; filename=\"$file\"");

        $fp = fopen($filepath, 'r');
        fseek($fp, $start);
        $chunkSize = 8192;	// should be a pref?

        while ($end) {
            $read = ($end > $chunkSize) ? $chunkSize : $end;
            $end -= $read;
            echo fread($fp, $read);
        }
    }
} else {
    if (function_exists('mb_strlen')) {
        header('Content-Length: ' . mb_strlen($content, '8bit'));
    } else {
        header('Content-Length: ' . strlen($content));
    }
    echo "$content";
}
