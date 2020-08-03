<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @file
 * Customised version of HTMLPurifier.func.php for easy use in Tiki
 * This overrides the HTMLPurifier() function in HTMLPurifier.func.php
 *
 * Defines a function wrapper for HTML Purifier for quick use.
 * @note ''HTMLPurifier()'' is NOT the same as ''new HTMLPurifier()''
 * @param mixed $html
 * @param null|mixed $config
 */

/**
 * Purify HTML.
 * @param $html String HTML to purify
 * @param $config Configuration to use, can be any value accepted by
 *       HTMLPurifier_Config::create()
 */

/**
 * @param $html
 * @param null $config
 * @return mixed
 */
function HTMLPurifier($html, $config = null)
{
    static $purifier = false;
    if (! $purifier || ! $config) {
        if (! $config) {	// mod for tiki temp files location
            $config = getHTMLPurifierTikiConfig();
        }
        $purifier = new HTMLPurifier();
    }

    return $purifier->purify($html, $config);
}

/**
 * @return mixed
 */
function getHTMLPurifierTikiConfig()
{
    global $tikipath, $prefs;

    $directory = $tikipath . 'temp/cache/HTMLPurifierCache';
    if (! is_dir($directory)) {
        if (! mkdir($directory)) {
            $directory = $tikipath . 'temp/cache';
        } else {
            chmod(
                $directory,
                (int)$prefs['smarty_cache_perms'] | 0111 // Add search/execute permission for all ("chmod a+x"). "--x--x--x" is 0111 (octal).
            );
        }
    }
    $conf = HTMLPurifier_Config::createDefault();
    $conf->set('Cache.SerializerPath', $directory);
    if ($prefs['feature_wysiwyg'] == 'y' || $prefs['popupLinks'] == 'y') {
        $conf->set('HTML.DefinitionID', 'allow target');
        $conf->set('HTML.DefinitionRev', 1);
        $conf->set('Attr.EnableID', 1);
        $conf->set('HTML.Doctype', 'XHTML 1.0 Transitional');
        $conf->set('HTML.TidyLevel', 'light');
        if ($def = $conf->maybeGetRawHTMLDefinition()) {
            $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
            $def->addAttribute('a', 'name', 'CDATA');
            // Add usemap attribute to img tag
            $def->addAttribute('img', 'usemap', 'CDATA');
            // rel attribute for anchors
            $def->addAttribute('a', 'rel', 'CDATA');

            // Add map tag
            $map = $def->addElement(
                'map', // name
                'Block', // content set
                'Flow', // allowed children
                'Common', // attribute collection
                [ // attributes
                    'name' => 'CDATA',
                    'id' => 'ID',
                    'title' => 'CDATA',
                ]
            );
            $map->excludes = ['map' => true];

            // Add area tag
            $area = $def->addElement(
                'area', // name
                'Block', // content set
                'Empty', // don't allow children
                'Common', // attribute collection
                [ // attributes
                    'name' => 'CDATA',
                    'id' => 'ID',
                    'alt' => 'Text',
                    'coords' => 'CDATA',
                    'accesskey' => 'Character',
                    'nohref' => new HTMLPurifier_AttrDef_Enum(['nohref']),
                    'href' => 'URI',
                    'shape' => new HTMLPurifier_AttrDef_Enum(['rect', 'circle', 'poly', 'default']),
                    'tabindex' => 'Number',
                    'target' => new HTMLPurifier_AttrDef_Enum(['_blank', '_self', '_target', '_top'])
                ]
            );
            $area->excludes = ['area' => true];
        }
    }

    return $conf;
}
