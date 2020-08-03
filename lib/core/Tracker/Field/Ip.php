<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for IP selector:
 *
 * Letter key ~I~
 */
class Tracker_Field_Ip extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable
{
    public static function getTypes()
    {
        return [
            'I' => [
                'name' => tr('IP Selector'),
                'description' => tr('IP address input field'),
                'help' => 'IP selector',
                'prefs' => ['trackerfield_ipaddress'],
                'tags' => ['basic'],
                'default' => 'n',
                'params' => [
                    'autoassign' => [
                        'name' => tr('Auto-assign'),
                        'description' => tr('Automatically assign the value on creation or edit.'),
                        'filter' => 'int',
                        'default' => 1,
                        'options' => [
                            0 => tr('None'),
                            1 => tr('Creator'),
                            2 => tr('Modifier'),
                        ],
                        'legacy_index' => 0,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        global $tiki_p_admin_trackers;

        $ins_id = $this->getInsertId();
        $data = $this->getItemData();
        $autoAssign = $this->getOption('autoassign');

        if (empty($data) && $tiki_p_admin_trackers == 'n' && $autoAssign == '1') {
            // if it is a new tracker item, ip auto assign is enabled and user doesn't
            // have $tiki_p_admin_trackers there is no information about the ip address
            // in the form so we have to get it from TikiLib::get_ip_address()
            $value = TikiLib::lib('tiki')->get_ip_address();
        } elseif (isset($requestData[$ins_id])) {
            $value = $requestData[$ins_id];
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate("trackerinput/ip.tpl", $context);
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
