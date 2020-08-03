<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for LDAP. Was not extensively tested after migration.
 *
 * Letter key: ~P~
 *
 */
class Tracker_Field_Ldap extends Tracker_Field_Abstract
{
    public static function getTypes()
    {
        return [
            'P' => [
                'name' => tr('LDAP'),
                'description' => tr('Display a field value from a specific user in LDAP'),
                'readonly' => true,
                'help' => 'LDAP Tracker Field',
                'prefs' => ['trackerfield_ldap'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'filter' => [
                        'name' => tr('Filter'),
                        'description' => tr('LDAP filter, can contain the %field_name% placeholder to be replaced with the current field\'s name'),
                        'example' => '(&(mail=%field_name%)(objectclass=posixaccount))',
                        'filter' => 'none',
                        'legacy_index' => 0,
                    ],
                    'field' => [
                        'name' => tr('Field'),
                        'description' => tr('Field name returned by LDAP'),
                        'filter' => 'text',
                        'legacy_index' => 1,
                    ],
                    'dsn' => [
                        'name' => tr('DSN'),
                        'description' => tr('Data source name registered in Tiki'),
                        'filter' => 'text',
                        'legacy_index' => 2,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        if ($this->getOption('dsn')) {
            $ldaplib = TikiLib::lib('ldap');

            // Retrieve DSN
            $info_ldap = TikiLib::lib('admin')->get_dsn_from_name($this->getOption('dsn'));

            if ($info_ldap) {
                $ldap_filter = $this->getOption('filter');

                // Replace %field_name% by real value
                preg_match('/%([^%]+)%/', $ldap_filter, $ldap_filter_field_names);

                if (isset($ldap_filter_field_names[1])) {
                    $field = $this->getTrackerDefinition()->getFieldFromName($ldap_filter_field_names[1]);

                    if ($field) {
                        $value = TikiLib::lib('trk')->get_field_value($field, $this->getItemData());

                        $ldap_filter = preg_replace('/%' . $ldap_filter_field_names[1] . '%/', $value, $ldap_filter);

                        // Get LDAP field value
                        return [
                            'value' => $ldaplib->get_field($info_ldap['dsn'], $ldap_filter, $this->getOption('field')),
                        ];
                    }
                }
            }
        }
    }

    public function renderInput($context = [])
    {
        return $this->getConfiguration('value');
    }
}
