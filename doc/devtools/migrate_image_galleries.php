<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
//	header("location: index.php");
//	exit;
//}

/**
 * One day this will be an installer upgrade script
 *
 * @param $installer
 */
function upgrade_2017mmdd_migrate_image_galleries_tiki($installer)
{
    $filegallib = TikiLib::lib('filegal');
    $attributelib = TikiLib::lib('attribute');

    $tikiGalleries = TikiDb::get()->table('tiki_galleries');
    $tikiGalleriesScales = TikiDb::get()->table('tiki_galleries_scales');
    $tikiImages = TikiDb::get()->table('tiki_images');
    $tikiImagesData = TikiDb::get()->table('tiki_images_data');

    $galleryIdMap = [];

    if ($tikiImages->fetchCount([])) {
        $rootFileGalleryId = $filegallib->replace_file_gallery([
            'name' => tra('Migrated Image Galleries'),
            'description' => tra('Converted image galleries from version created by Tiki 17'),
        ]);

        foreach ($tikiGalleries->fetchAll() as $gallery) {
            $gallery['sort_mode'] = $gallery['sortorder'] . '_' . $gallery['sortdirection'];
            $oldGalleryId = $gallery['galleryId'];
            $gallery['galleryId'] = 0;		// we want a new one

            if ($gallery['parentgallery'] < 0 || empty($galleryIdMap[$gallery['parentgallery']])) {
                $gallery['parentId'] = $rootFileGalleryId;
            } else {
                $gallery['parentId'] = $galleryIdMap[$gallery['parentgallery']];
            }

            $gallery['show_name'] = $gallery['showname'];
            $gallery['show_id'] = $gallery['showimageid'];
            $gallery['show_description'] = $gallery['showdescription'];
            $gallery['show_author'] = $gallery['showuser'];    // TODO something about creator?
            $gallery['show_hits'] = $gallery['showhits'];

            if ($gallery['show_name'] === 'y' && $gallery['show_filename'] === 'y') {
                $gallery['show_name'] = 'a';
            } elseif ($gallery['show_filename'] === 'y') {
                $gallery['show_name'] = 'f';
            } else {
                $gallery['show_name'] = 'n';
            }

            unset(
                $gallery['geographic'],
                $gallery['theme'],
                $gallery['rowImages'],
                $gallery['thumbSizeX'],
                $gallery['thumbSizeY'],
                $gallery['sortorder'],
                $gallery['sortdirection'],
                $gallery['galleryimage'],    // TODO something?
                $gallery['parentgallery'],
                $gallery['showname'],
                $gallery['showimageid'],
                $gallery['showdescription'],
                $gallery['showcreated'],
                $gallery['showuser'],
                $gallery['showhits'],
                $gallery['showxysize'],
                $gallery['showfilesize'],
                $gallery['showname'],
                $gallery['showfilename'],
                $gallery['defaultscale'],    // TODO something?
                $gallery['showcategories']
            );

            $fileGalleryId = $filegallib->replace_file_gallery($gallery);
            $galleryIdMap[$oldGalleryId] = $fileGalleryId;

            $images = $tikiImages->fetchAll([], ['galleryId' => $oldGalleryId]);
            foreach ($images as $image) {
                $imageData = $tikiImagesData->fetchAll([], [
                    'type' => 'o',                            // not thumbnails
                    'imageId' => $image['imageId'],
                ]);
                $image = array_merge($imageData[0], $image);
                $image['galleryId'] = $fileGalleryId;

                $file = new Tiki\FileGallery\File([
                    'galleryId' => $image['galleryId'],
                    'description' => $image['description'],
                    'user' => $image['user'],
                    'author' => $image['user'],
                    'created' => $image['created'],
                ]);
                $fileId = $file->replace($image['data'], $image['filetype'], $image['name'], $image['filename'], $image['xsize'], $image['ysize']);

                TikiLib::lib('geo')->set_coordinates(
                    'file',
                    $fileId,
                    [
                        'lon' => $image['lon'],
                        'lat' => $image['lat'],
                    ]
                );

                // add the old imageId as an attribute for future use in the img plugin
                $attributelib->set_attribute('file', $fileId, 'tiki.file.imageid', $image['imageId']);
            }
        }
    }
}

include_once('tiki-setup.php');
$installer = Installer::getInstance();

upgrade_2017mmdd_migrate_image_galleries_tiki($installer);
