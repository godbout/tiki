<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for numeric and currency field
 *
 * Letter key: ~n~
 *
 */
class Tracker_Field_Numeric extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
    public static function getTypes()
    {
        return [
            'n' => [
                'name' => tr('Numeric'),
                'description' => tr('Provide a one-line field for numeric input only. Prepended or appended values may be alphanumeric.'),
                'help' => 'Numeric Tracker Field',
                'prefs' => ['trackerfield_numeric'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['d', 'D', 'R', 'M', 't', 'a', 'n', 'q', 'b', 'DUR'],
                'params' => [
                    'samerow' => [
                        'name' => tr('Same Row'),
                        'description' => tr('Displays the next field on the same line.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 1,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'size' => [
                        'name' => tr('Display Size'),
                        'description' => tr('Visible size of the field, in characters. Does not affect the numeric length.'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                    ],
                    'prepend' => [
                        'name' => tr('Prepend'),
                        'description' => tr('Text to be displayed in front of the numeric value.'),
                        'filter' => 'text',
                        'legacy_index' => 2,
                    ],
                    'append' => [
                        'name' => tr('Append'),
                        'description' => tr('Text to be displayed after the numeric value.'),
                        'filter' => 'text',
                        'legacy_index' => 3,
                    ],
                    'decimals' => [
                        'name' => tr('Decimal Places'),
                        'description' => tr('Number of decimal places to round to.'),
                        'filter' => 'int',
                        'legacy_index' => 4,
                    ],
                    'dec_point' => [
                        'name' => tr('Decimal separator when displaying data'),
                        'description' => tr('Single character. Use "c" for comma, "d" for dot or "s" for space. The valid decimal separator when inserting numbers may depend on the site language and web browser. See the documentation for more details.'),
                        'filter' => 'text',
                        'default' => '.',
                        'legacy_index' => 5,
                    ],
                    'thousands' => [
                        'name' => tr('Thousands separator when displaying data'),
                        'description' => tr('Single character: use "c" for comma, "d" for dot or "s" for space.  When inserting data, no thousands separator is needed.'),
                        'filter' => 'text',
                        'default' => ',',
                        'legacy_index' => 6,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();

        return [
            'value' => (isset($requestData[$ins_id]))
                ? $requestData[$ins_id]
                : $this->getValue(),
        ];
    }

    public function renderInnerOutput($context = [])
    {
        $decimals = $this->getOption('decimals');
        $dec_point = $this->getOption('dec_point');
        $thousands = $this->getOption('thousands');
        if (empty($decimals) && empty($dec_point)) {
            if (empty($thousands)) {
                return $this->getValue();
            }

            return number_format($this->getValue());
        }

        return number_format($this->getValue(), $decimals, $dec_point, $thousands);
    }

    public function renderOutput($context = [])
    {
        return $this->renderTemplate('trackeroutput/numeric.tpl', $context);
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/numeric.tpl', $context);
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $item = $this->getValue();
        $baseKey = $this->getBaseKey();

        $out = [
            $baseKey => $typeFactory->numeric($item),
        ];

        return $out;
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

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (& $info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;

        $prepend = $this->getOption('prepend');
        $append = $this->getOption('append');
        $schema->addNew($permName, 'formatted')
            ->setLabel($this->getConfiguration('name'))
            ->addIncompatibility($permName, 'default')
            ->setRenderTransform(function ($value) use ($prepend, $append) {
                return $prepend . $value . $append;
            })
            ->setParseIntoTransform(function (& $info, $value) use ($permName, $prepend, $append) {
                $value = substr($value, strlen($prepend), -strlen($append));
                $info['fields'][$permName] = $value;
            })
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        $filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $filters->addNew($permName, 'exact')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_em"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();
                if ($value) {
                    $query->filterIdentifier($value, $baseKey);
                }
            });

        $filters->addNew($permName, 'range')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\NumericRange("tf_{$permName}_range", $this->getOption('decimals')))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                if ($control->hasValue()) {
                    $query->filterNumericRange($control->getFrom(), $control->getTo(), $baseKey);
                }
            });

        return $filters;
    }
}
