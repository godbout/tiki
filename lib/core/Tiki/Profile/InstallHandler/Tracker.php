<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Tracker extends Tiki_Profile_InstallHandler
{
    public function getData() // {{{
    {
        if ($this->data) {
            return $this->data;
        }

        $data = $this->obj->getData();

        $data = Tiki_Profile::convertLists($data, ['show' => 'y', 'allow' => 'y'], true);

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    } // }}}

    public static function getOptionMap() // {{{
    {
        // Also used by TrackerOption
        return [
            'name' => '',
            'description' => '',
            'fieldPrefix' => '',
            'show_status' => 'showStatus',
            'show_status_admin_only' => 'showStatusAdminOnly',
            'list_default_status' => 'defaultStatus',
            'email' => 'outboundEmail',
            'email_simplified' => 'simpleEmail',
            'default_status' => 'newItemStatus',
            'modification_status' => 'modItemStatus',
            'allow_user_see_own' => 'userCanSeeOwn',
            'allow_group_see_own' => 'groupCanSeeOwn',
            'allow_creator_modification' => 'writerCanModify',
            'allow_creator_deletion' => 'writerCanRemove',
            'allow_creator_group_modification' => 'writerGroupCanModify',
            'allow_creator_group_deletion' => 'writerGroupCanRemove',
            'show_creation_date' => 'showCreatedView',
            'show_list_creation_date' => 'showCreated',
            'show_modification_date' => 'showLastModifView',
            'show_list_modification_date' => 'showLastModif',
            'creation_date_format' => 'showCreatedFormat',
            'modification_date_format' => 'showLastModifFormat',
            'sort_default_field' => 'defaultOrderKey',
            'sort_default_order' => 'defaultOrderDir',
            'allow_rating' => 'useRatings',
            'allow_comments' => 'useComments',
            'show_comments' => 'showComments',
            'show_last_comment' => 'showLastComment',
            'save_and_comment' => 'saveAndComment',
            'allow_attachments' => 'useAttachments',
            'restrict_start' => 'start',
            'restrict_end' => 'end',
            'hide_list_empty_fields' => 'doNotShowEmptyField',
            'allow_one_item_per_user' => 'oneUserItem',
            'section_format' => 'sectionFormat',
            'popup_fields' => 'showPopup',
            'admin_only_view' => 'adminOnlyViewEditItem',
            'use_form_classes' => 'useFormClasses',
            'form_classes' => 'formClasses',
        ];
    } // }}}

    private static function getDefaults() // {{{
    {
        $defaults = array_fill_keys(array_keys(self::getOptionMap()), 'n');
        $defaults['name'] = '';
        $defaults['description'] = '';
        $defaults['creation_date_format'] = '';
        $defaults['modification_date_format'] = '';
        $defaults['email'] = '';
        $defaults['default_status'] = 'o';
        $defaults['modification_status'] = '';
        $defaults['list_default_status'] = 'o';
        $defaults['sort_default_order'] = 'asc';
        $defaults['sort_default_field'] = '';
        $defaults['restrict_start'] = '';
        $defaults['restrict_end'] = '';
        $defaults['popup_fields'] = '';
        $defaults['section_format'] = 'flat';

        return $defaults;
    } // }}}

    public static function getOptionConverters() // {{{
    {
        // Also used by TrackerOption
        return [
            'restrict_start' => new Tiki_Profile_DateConverter,
            'restrict_end' => new Tiki_Profile_DateConverter,
            'sort_default_field' => new Tiki_Profile_ValueMapConverter([ 'modification' => -1, 'creation' => -2, 'item' => -3 ]),
            'list_default_status' => new Tiki_Profile_ValueMapConverter([ 'open' => 'o', 'pending' => 'p', 'closed' => 'c' ]),
            'default_status' => new Tiki_Profile_ValueMapConverter([ 'open' => 'o', 'pending' => 'p', 'closed' => 'c' ]),
            'modification_status' => new Tiki_Profile_ValueMapConverter([ 'open' => 'o', 'pending' => 'p', 'closed' => 'c' ]),
        ];
    } // }}}

    public function canInstall() // {{{
    {
        $data = $this->getData();

        // Check for mandatory fields
        if (! isset($data['name'])) {
            $ref = $this->obj->getRef();

            throw (new Exception('No name for tracker:' . (empty($ref) ? '' : ' ref=' . $ref)));
        }

        // Check for unknown fields
        $optionMap = $this->getOptionMap();

        $remain = array_diff(array_keys($data), array_keys($optionMap));
        if (count($remain)) {
            throw (new Exception('Cannot map object options: "' . implode('","', $remain) . '" for tracker:' . $data['name']));
        }

        return true;
    } // }}}

    public function _install() // {{{
    {
        $values = self::getDefaults();

        $input = $this->getData();
        $this->replaceReferences($input);

        $conversions = self::getOptionConverters();
        foreach ($input as $key => $value) {
            if (array_key_exists($key, $conversions)) {
                $values[$key] = $conversions[$key]->convert($value);
            } else {
                $values[$key] = $value;
            }
        }

        $name = $values['name'];
        $description = $values['description'];

        unset($values['name']);
        unset($values['description']);

        $optionMap = $this->getOptionMap();

        $options = [];
        foreach ($values as $key => $value) {
            $key = $optionMap[$key];
            $options[$key] = $value;
        }

        $trklib = TikiLib::lib('trk');

        $trackerId = $trklib->get_tracker_by_name($name);

        return $trklib->replace_tracker($trackerId, $name, $description, $options, 'y');
    } // }}}

    /**
     * Export trackers
     *
     * @param Tiki_Profile_Writer $writer
     * @param int $trackerId
     * @param bool $all
     * @return bool
     */
    public static function export(Tiki_Profile_Writer $writer, $trackerId, $all = false) // {{{
    {
        $trklib = TikiLib::lib('trk');

        if (isset($trackerId) && ! $all) {
            $listTrackers = [];
            $listTrackers[] = ['trackerId' => $trackerId];
        } else {
            $listTrackers = $trklib->list_trackers();
            $listTrackers = $listTrackers['data'];
        }

        foreach ($listTrackers as $tracker) {
            $trackerId = $tracker['trackerId'];
            $info = $trklib->get_tracker($trackerId);

            if (empty($info)) {
                return false;
            }

            if ($options = $trklib->get_tracker_options($trackerId)) {
                $info = array_merge($info, $options);
            }

            $data = [
                'name' => $info['name'],
                'description' => $info['description'],
            ];

            $optionMap = array_flip(self::getOptionMap());
            $defaults = self::getDefaults();
            $conversions = self::getOptionConverters();

            $allow = [];
            $show = [];

            foreach ($info as $key => $value) {
                if (empty($optionMap[$key])) {
                    continue;
                }

                $optionKey = $optionMap[$key];
                $default = '';
                if (isset($defaults[$optionKey])) {
                    $default = $defaults[$optionKey];
                }

                if ($value != $default) {
                    if (strstr($optionKey, 'allow_')) {
                        $allow[] = str_replace('allow_', '', $optionKey);
                    } elseif (strstr($optionKey, 'show_')) {
                        $show[] = str_replace('show_', '', $optionKey);
                    } elseif (isset($conversions[$optionKey]) && method_exists($conversions[$optionKey], 'reverse')) {
                        $data[$optionKey] = $conversions[$optionKey]->reverse($value);
                    } else {
                        $data[$optionKey] = $value;
                    }
                }
            }

            if (! empty($allow)) {
                $data['allow'] = $allow;
            }
            if (! empty($show)) {
                $data['show'] = $show;
            }

            $fieldReferences = [];
            foreach (['sort_default_field', 'popup_fields'] as $key) {
                if (isset($data[$key])) {
                    $fieldReferences[$key] = $data[$key];
                    unset($data[$key]);
                }
            }

            $reference = $writer->addObject('tracker', $trackerId, $data);

            $fields = $trklib->list_tracker_fields($trackerId);
            foreach ($fields['data'] as $field) {
                $writer->pushReference("{$reference}_{$field['permName']}");
                Tiki_Profile_InstallHandler_TrackerField::export($writer, $field);
            }

            foreach (array_filter($fieldReferences) as $key => $value) {
                $value = preg_replace_callback(
                    '/(\d+)/',
                    function ($match) use ($writer) {
                        return $writer->getReference('tracker_field', $match[1]);
                    },
                    $value
                );
                $writer->pushReference("{$reference}_{$key}");
                $writer->addObject(
                    'tracker_option',
                    "$key-$trackerId",
                    [
                        'tracker' => $writer->getReference('tracker', $trackerId),
                        'name' => $key,
                        'value' => $value,
                    ]
                );
            }
        }

        return true;
    } // }}}

    public function _export($trackerId, $profileObject) // {{{
    {
        $writer = new Tiki_Profile_Writer('temp', 'none');
        self::export($writer, $trackerId);

        return $writer->dump();
    } // }}}

    /**
     * Remove tracker
     *
     * @param string $tracker
     * @return bool
     */
    public function remove($tracker)
    {
        if (! empty($tracker)) {
            $trklib = TikiLib::lib('trk');
            $trackerId = $trklib->get_tracker_by_name($tracker);
            if (! empty($trackerId) && $trklib->remove_tracker($trackerId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current tracker data
     *
     * @param array $tracker
     * @return mixed
     */
    public function getCurrentData($tracker)
    {
        $trackerName = ! empty($tracker['name']) ? $tracker['name'] : '';
        $trklib = TikiLib::lib('trk');
        $trackerId = $trklib->get_tracker_by_name($trackerName);
        if (! empty($trackerId)) {
            $trackerData = $trklib->get_tracker($trackerId);
            $trackerOptions = $trklib->get_trackers_options($trackerId);
            $currentOptions = [];
            if (! empty($trackerOptions)) {
                foreach ($trackerOptions as $key => $value) {
                    if (! empty($value['name']) && ! empty($value['value'])) {
                        $currentOptions[$value['name']] = $value['value'];
                    }
                }
            }
            $trackerFields = $trklib->list_tracker_fields($trackerId);
            if (! empty($trackerData)) {
                $trackerData['options'] = $currentOptions;
                $trackerData['fields'] = ! empty($trackerFields['data']) ? $trackerFields['data'] : [];

                return $trackerData;
            }
        }

        return false;
    }
}
