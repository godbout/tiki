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
function module_logo_info()
{
    return [
        'name' => tra('Logo'),
        'description' => tra('Site logo, title and subtitle.'),
        'prefs' => ['feature_sitelogo'],
        'params' => [
            'src' => [
                'name' => tra('Image URL'),
                'description' => tra('Image to use. Defaults to sitelogo_src preference.'),
                'filter' => 'url',
            ],
            'bgcolor' => [
                'name' => tra('Background Color'),
                'description' => tra('CSS colour to use as background. Defaults to sitelogo_bgcolor preference.'),
                'filter' => 'text',
            ],
            'title_attr' => [				// seems module params called title disappear?
                'name' => tra('Title'),
                'description' => tra('Image title attribute. Defaults to sitelogo_title preference.'),
                'filter' => 'text',
            ],
            'alt_attr' => [
                'name' => tra('Alt'),
                'description' => tra('Image alt attribute. Defaults to sitelogo_alt preference.'),
                'filter' => 'text',
            ],
            'link' => [
                'name' => tra('Link'),
                'description' => tra('URL for the image and titles link. Defaults to "./".'),
                'filter' => 'url',
            ],
            'sitetitle' => [
                'name' => tra('Logo Title'),
                'description' => tra('Large text to go next to image. Defaults to sitetitle preference.'),
                'filter' => 'text',
            ],
            'sitesubtitle' => [
                'name' => tra('Logo Subtitle'),
                'description' => tra('Smaller text to go under the Logo Title. Defaults to sitesubtitle preference.'),
                'filter' => 'text',
            ],
            'class_image' => [
                'name' => tra('Logo Class'),
                'description' => tra('CSS class for the image container div. Defaults to sitelogo.'),
                'filter' => 'text',
            ],
            'class_titles' => [
                'name' => tra('Title Class'),
                'description' => tra('CSS class title text container div. Defaults to sitetitles.'),
                'filter' => 'text',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_logo($mod_reference, &$module_params)
{
    global $prefs;

    $module_params = array_merge(
        [
            'src' => $prefs['sitelogo_src'],
            'bgcolor' => $prefs['sitelogo_bgcolor'],
            'title_attr' => $prefs['sitelogo_title'],
            'alt_attr' => $prefs['sitelogo_alt'],
            'link' => './',
            'sitetitle' => $prefs['sitetitle'],
            'sitesubtitle' => $prefs['sitesubtitle'],
            'class_image' => 'sitelogo',
            'class_titles' => 'sitetitles',
        ],
        $module_params
    );
}
