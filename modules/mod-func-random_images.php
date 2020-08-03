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
 * @return array
 */
function module_random_images_info()
{
    return [
        'name' => tra('Random Image'),
        'description' => tra('Displays a random image.'),
        'prefs' => ['feature_galleries'],
        'documentation' => 'Module random_images',
        'params' => [
            'galleryId' => [
                'name' => tra('Gallery identifier'),
                'description' => tra('If set to an image gallery identifier, restricts the chosen images to those in the identified gallery.') . " " . tra('Example value: 13.') . " " . tra('Not set by default.'),
                'filter' => 'int',
                'profile_reference' => 'image_gallery',
            ],
            'showlink' => [
                'name' => tra('Show link'),
                'description' => tra('If set to "n", the image thumbnail does not link to the image.') . " " . tra('Default: "y"'),
                'filter' => 'word'
            ],
            'showname' => [
                'name' => tra('Show name'),
                'description' => tra('If set to "y", the name of the image is displayed.') . " " . tra('Default: "n"'),
                'filter' => 'word'
            ],
            'showdescription' => [
                'name' => tra('Show description'),
                'description' => tra('If set to "y", the description of the image is displayed.') . " " . tra('Default: "n"'),
                'filter' => 'word'
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_random_images($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $imagegallib = TikiLib::lib('imagegal');

    if (isset($module_params["galleryId"])) {
        $galleryId = $module_params["galleryId"];
    } else {
        $galleryId = -1;
    }

    $ranking = $imagegallib->get_random_image($galleryId);
    $smarty->assign('img', $ranking);
}
