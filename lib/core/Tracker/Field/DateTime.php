<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for DateTime
 *
 * Letter key: ~f~
 *
 */
class Tracker_Field_DateTime extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable, Search_FacetProvider_Interface
{
    public static function getTypes()
    {
        return [
            'f' => [
                'name' => tr('Date and Time'),
                'description' => tr('Provide drop-down options to accurately select a date and/or time.'),
                'help' => 'Date Tracker Field',
                'prefs' => ['trackerfield_datetime'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['f', 'j'],
                'params' => [
                    'datetime' => [
                        'name' => tr('Type'),
                        'description' => tr('Components to be included'),
                        'filter' => 'text',
                        'options' => [
                            'dt' => tr('Date and Time'),
                            'd' => tr('Date only'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'startyear' => [
                        'name' => tr('Start year'),
                        'description' => tr('Year to allow selecting from'),
                        'example' => '1987',
                        'filter' => 'digits',
                        'legacy_index' => 1,
                    ],
                    'endyear' => [
                        'name' => tr('End year'),
                        'description' => tr('Year to allow selecting to'),
                        'example' => '2020',
                        'filter' => 'digits',
                        'legacy_index' => 2,
                    ],
                    'blankdate' => [
                        'name' => tr('Default selection'),
                        'description' => tr('Indicates if blank dates should be allowed.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('Current Date'),
                            'blank' => tr('Blank'),
                        ],
                        'legacy_index' => 3,
                    ],
                    'useTimeAgo' => [
                        'name' => tr('Time Ago'),
                        'description' => tr('Use timeago.js if the feature is enabled'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'isItemDateField' => [
                        'name' => tr('Item Date Field'),
                        'description' => tr("Use this date as the item's global date in the search index."),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();

        $value = $this->getValue();
        $data = [
            'value' => empty($value) ? ($this->getOption('blankdate') == 'blank' ? '' : TikiLib::lib('tiki')->now) : $value,
        ];

        if (isset($requestData[$ins_id . 'Month']) || isset($requestData[$ins_id . 'Day']) || isset($requestData[$ins_id . 'Year']) || isset($requestData[$ins_id . 'Hour']) || isset($requestData[$ins_id . 'Minute'])) {
            $data['value'] = TikiLib::lib('trk')->build_date($requestData, $this->getOption('datetime'), $ins_id);
            if (empty($data['value']) && (! empty($requestData[$ins_id . 'Month']) || ! empty($requestData[$ins_id . 'Day']) || ! empty($requestData[$ins_id . 'Year']) || ! empty($requestData[$ins_id . 'Hour']) || ! empty($requestData[$ins_id . 'Minute']))) {
                $data['error'] = 'y';
            }
        }

        return $data;
    }

    public function renderInput($context = [])
    {
        global $user;

        // datetime select fields now have the class "date" which triggers an automatic class rule in jquery.validation
        // but that expects a full valid date value, so always fails
        TikiLib::lib('header')->add_jq_onready('$.validator.classRuleSettings.date = false;
');

        TikiLib::lib('smarty')->assign('use_24hr_clock', TikiLib::lib('userprefs')->get_user_clock_pref($user));

        return $this->renderTemplate('trackerinput/datetime.tpl', $context);
    }

    public function renderInnerOutput($context = [])
    {
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $value = $this->getConfiguration('value');

        if ($value) {
            if (isset($context['list_mode']) && $context['list_mode'] == 'csv') {
                return $tikilib->get_short_datetime($value);
            }

            if ($prefs['jquery_timeago'] === 'y' && $this->getOption('useTimeAgo')) {
                TikiLib::lib('header')->add_jq_onready('$("time.timeago").timeago();');

                return '<time class="timeago" datetime="' . TikiLib::date_format('c', $value, false, 5, false) . '">' . $tikilib->get_short_datetime($value) . '</time>';
            }
            $date = $tikilib->get_short_date($value);
            if ($this->getOption('datetime') == 'd') {
                return $date;
            }

            if ($this->getOption('datetime') == 't') {
                return $tikilib->get_short_time($value);
            }

            $current = $tikilib->get_short_date($tikilib->now);

            if ($date == $current && $prefs['tiki_same_day_time_only'] == 'y') {
                return $tikilib->get_short_time($value);
            }

            return $tikilib->get_short_datetime($value);
        }
    }

    public function watchCompare($old, $new)
    {
        global $prefs;
        $dformat = $prefs['short_date_format'] . ' ' . $prefs['short_time_format'];
        if ($old) {
            $old = TikiLib::lib('tiki')->date_format($dformat, (int)$old);
        }
        $new = TikiLib::lib('tiki')->date_format($dformat, (int)$new);

        return parent::watchCompare($old, $new);
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

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $timestamp = $typeFactory->timestamp($value, $this->getOption('datetime') == 'd');

        if ($value && strlen($value) > 10) {
            trigger_error("Possibly incorrect timestamp value found when trying to send to search index. Tracker item " . $this->getItemId() . ", field " . $this->getConfiguration('permName') . ", value " . $value, E_USER_WARNING);
            if (substr($value, -3) === '000') {	// possibly milliseconds from js picker
                $timestamp = $typeFactory->timestamp($value / 1000, $this->getOption('datetime') == 'd');
            }
        }

        $data = [
            $this->getBaseKey() => $timestamp,
        ];

        if ($this->getOption('isItemDateField')) {
            $data['date'] = $timestamp;
        }

        return $data;
    }

    public function getProvidedFields()
    {
        $data = parent::getProvidedFields();

        if ($this->getOption('isItemDateField')) {
            $data[] = 'date';
        }

        return $data;
    }

    public function getGlobalFields()
    {
        $data = parent::getGlobalFields();

        if ($this->getOption('isItemDateField')) {
            $data['date'] = true;
        }

        return $data;
    }

    public function getTabularSchema()
    {
        global $prefs;

        $permName = $this->getConfiguration('permName');
        $type = $this->getOption('datetime');

        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $label = $this->getConfiguration('name');
        $helper = new Tracker\Tabular\Schema\DateHelper($label);
        $helper->setupUnix($schema->addNew($permName, 'unix'));

        $tikidate = TikiLib::lib('tikidate');
        if ($type == 'd') {
            $helper->setupFormat('Y-m-d', $schema->addNew($permName, 'yyyy-mm-dd'));
            $helper->setupFormat(str_replace($tikidate->search, $tikidate->replace, $prefs['short_date_format']), $schema->addNew($permName, 'short date format'));
            $helper->setupFormat(str_replace($tikidate->search, $tikidate->replace, $prefs['long_date_format']), $schema->addNew($permName, 'long date format'));
        } else {
            $helper->setupFormat('Y-m-d H:i:s', $schema->addNew($permName, 'yyyy-mm-dd hh:mm:ss'));
            $helper->setupFormat(str_replace($tikidate->search, $tikidate->replace, $prefs['short_date_format'] . ' ' . $prefs['short_time_format']), $schema->addNew($permName, 'short datetime format'));
            $helper->setupFormat(str_replace($tikidate->search, $tikidate->replace, $prefs['long_date_format'] . ' ' . $prefs['long_time_format']), $schema->addNew($permName, 'long datetime format'));
        }

        return $schema;
    }

    public function getFilterCollection()
    {
        $filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $filters->addNew($permName, 'range')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\DateRange("tf_{$permName}_range"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                if ($control->hasValue()) {
                    $query->filterRange($control->getFrom(), $control->getTo(), $baseKey);
                }
            });

        return $filters;
    }

    public function getFacets()
    {
        $baseKey = $this->getBaseKey();
        // add this a a generic Term facet/aggregation and the plugin will override it depending on type
        return [
            Search_Query_Facet_Term::fromField($baseKey)
                ->setLabel($this->getConfiguration('name'))
                ->setRenderCallback(function ($date) {
                    return TikiLib::lib('tiki')->get_short_date($date / 1000);
                })
        ];
    }
}
