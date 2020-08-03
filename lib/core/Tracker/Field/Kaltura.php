<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for kaltura video integration
 *
 * Letter key: ~kaltura~
 *
 */
class Tracker_Field_Kaltura extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable
{
    public static function getTypes()
    {
        return [
            'kaltura' => [
                'name' => tr('Kaltura Video'),
                'description' => tr('Display a series of attached Kaltura videos.'),
                'help' => 'Kaltura',
                'prefs' => ['trackerfield_kaltura', 'feature_kaltura', 'wikiplugin_kaltura'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters used in the {kaltura} plugin, for example,.') . ' "width=800&height=600"',
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters used in the {kaltura} plugin, for example,.') . ' "width=240&height=80"',
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $insertId = $this->getInsertId();

        if (isset($requestData[$insertId])) {
            $value = implode(',', $requestData[$insertId]);
        } elseif (! empty($requestData['old_' . $insertId])) {    // all entries removed
            $value = '';
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        $kalturalib = TikiLib::lib('kalturauser');
        $movies = array_filter(explode(',', $this->getValue()));

        $movieList = $kalturalib->getMovieList($movies);
        $extra = array_diff(
            $movies,
            array_map(
                function ($movie) {
                    return $movie['id'];
                },
                $movieList
            )
        );

        return $this->renderTemplate(
            'trackerinput/kaltura.tpl',
            $context,
            [
                'movies' => $movieList,
                'extras' => $extra,
            ]
        );
    }

    public function renderOutput($context = [])
    {
        if ($context['list_mode'] === 'y') {
            $otherParams = $this->getOption('displayParamsForLists', []);
        } else {
            $otherParams = $this->getOption('displayParams', []);
        }

        if ($otherParams) {
            parse_str($otherParams, $otherParams);
        }

        include_once 'lib/wiki-plugins/wikiplugin_kaltura.php';

        $movieIds = array_filter(explode(',', $this->getValue()));
        $output = '';

        foreach ($movieIds as $id) {
            $params = array_merge($otherParams, ['id' => $id]);
            $output .= wikiplugin_kaltura('', $params);
        }

        return $output;
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }
}
