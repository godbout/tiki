<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for ItemLink
 *
 * Letter key: ~r~
 *
 */
class Tracker_Field_ItemLink extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Search_FacetProvider_Interface, Tracker_Field_Filterable
{
    const CASCADE_NONE = 0;
    const CASCADE_CATEG = 1;
    const CASCADE_STATUS = 2;
    const CASCADE_DELETE = 4;

    public static function getTypes()
    {
        return [
            'r' => [
                'name' => tr('Item Link'),
                'description' => tr('Link to another item, similar to a foreign key'),
                'help' => 'Items List and Item Link Tracker Fields',
                'prefs' => ['trackerfield_itemlink'],
                'tags' => ['advanced'],
                'default' => 'y',
                'supported_changes' => ['r', 'REL'],
                'params' => [
                    'trackerId' => [
                        'name' => tr('Tracker ID'),
                        'description' => tr('Tracker to link to'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'tracker',
                    ],
                    'fieldId' => [
                        'name' => tr('Field ID'),
                        'description' => tr('Default field to display'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'linkToItem' => [
                        'name' => tr('Display'),
                        'description' => tr('How the link to the item should be rendered'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Value'),
                            1 => tr('Link'),
                        ],
                        'legacy_index' => 2,
                    ],
                    'displayFieldsList' => [
                        'name' => tr('Multiple Fields'),
                        'description' => tr('Display the values from multiple fields instead of a single one.'),
                        'separator' => '|',
                        'filter' => 'int',
                        'legacy_index' => 3,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'displayFieldsListFormat' => [
                        'name' => tr('Format for Customising Multiple Fields'),
                        'description' => tr('Uses the translate function to replace %0 etc with the field values. E.g. "%0 any text %1"'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'displayFieldsList'
                        ],
                    ],
                    'displayFieldsListType' => [
                        'name' => tr('Multiple Fields display type'),
                        'description' => tr('Display multiple fields as concatenated list in a dropdown or as a table.'),
                        'filter' => 'alpha',
                        'options' => [
                            'dropdown' => tr('Dropdown'),
                            'table' => tr('Table'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsList'
                        ],
                        'legacy_index' => 14,
                    ],
                    'trackerListOptions' => [
                        'name' => tr('Plugin TrackerList options'),
                        'description' => tr('Override one or more options of Plugin TrackerList to customize displayed table at item edit time (e.g. editable, tsfilters, etc.)'),
                        'filter' => 'text',
                        'type' => 'textarea',
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'table'
                        ],
                        'legacy_index' => 15,
                        'profile_reference' => 'tracker_field_string',
                    ],
                    'status' => [
                        'name' => tr('Status Filter'),
                        'description' => tr('Limit the available items to a selected set'),
                        'filter' => 'alpha',
                        'options' => [
                            'opc' => tr('all'),
                            'o' => tr('open'),
                            'p' => tr('pending'),
                            'c' => tr('closed'),
                            'op' => tr('open, pending'),
                            'pc' => tr('pending, closed'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'linkPage' => [
                        'name' => tr('Link Page'),
                        'description' => tr('Link to a wiki page instead of directly to the item'),
                        'filter' => 'pagename',
                        'legacy_index' => 5,
                        'profile_reference' => 'wiki_page',
                    ],
                    'addItems' => [
                        'name' => tr('Add Items'),
                        'description' => tr('Display text to allow new items to be added - e.g. "Add item..." (requires jQuery-UI)'),
                        'filter' => 'text',
                        'legacy_index' => 6,
                    ],
                    'addItemsWikiTpl' => [
                        'name' => tr('Add Item Template Page'),
                        'description' => tr('Wiki page to use as a Pretty Tracker template'),
                        'filter' => 'pagename',
                        'legacy_index' => 7,
                        'profile_reference' => 'wiki_page',
                        'depends' => [
                            'field' => 'addItems'
                        ],
                    ],
                    'preSelectFieldHere' => [
                        'name' => tr('Preselect item based on value in this field'),
                        'description' => tr('Preselect item based on value in specified field ID of item being edited'),
                        'filter' => 'int',
                        'legacy_index' => 8,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'preSelectFieldThere' => [
                        'name' => tr('Preselect based on the value in this remote field'),
                        'description' => tr('Match preselect item to this field ID in the tracker that is being linked to'),
                        'filter' => 'int',
                        'legacy_index' => 9,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                        'depends' => [
                            'field' => 'preSelectFieldHere'
                        ],
                    ],
                    'preSelectFieldMethod' => [
                        'name' => tr('Preselection matching method'),
                        'description' => tr('Method to use to match fields for preselection purposes'),
                        'filter' => 'alpha',
                        'options' => [
                            'exact' => tr('Exact Match'),
                            'partial' => tr('Field here is part of field there'),
                            'domain' => tr('Match domain, used for URL fields'),
                            'crossSelect' => tr('Cross select. Load all matching items in the remote tracker'),
                            'crossSelectWildcards' => tr('Cross select. Load all matching items in the remote tracker plus wildcards'),
                        ],
                        'depends' => [
                            'field' => 'preSelectFieldHere'
                        ],
                        'legacy_index' => 10,
                    ],
                    'displayOneItem' => [
                        'name' => tr('One item per value'),
                        'description' => tr('Display only one item for each label (at random, needed for filtering records in a dynamic items list) or all items'),
                        'filter' => 'alpha',
                        'options' => [
                            'multi' => tr('Displays all the items for a same label with a notation value (itemId)'),
                            'one' => tr('Display only one item for each label'),
                        ],
                        'legacy_index' => 11,
                    ],
                    'selectMultipleValues' => [
                        'name' => tr('Select multiple values'),
                        'description' => tr('Allow the user to select multiple values'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'displayFieldsListType',
                            'value' => 'dropdown'
                        ],
                        'legacy_index' => 12,
                    ],
                    'indexRemote' => [
                        'name' => tr('Index remote fields'),
                        'description' => tr('Index one or multiple fields from the master tracker along with the child, separated by |'),
                        'separator' => '|',
                        'filter' => 'int',
                        'legacy_index' => 13,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                    ],
                    'cascade' => [
                        'name' => tr('Cascade actions'),
                        'description' => tr("Elements to cascade when the master is updated or deleted. Categories may conflict if multiple item links are used to different items attempting to manage the same categories. Same for status."),
                        'filter' => 'int',
                        'options' => [
                            self::CASCADE_NONE => tr('No'),
                            self::CASCADE_CATEG => tr('Categories'),
                            self::CASCADE_STATUS => tr('Status'),
                            self::CASCADE_DELETE => tr('Delete'),
                            (self::CASCADE_CATEG | self::CASCADE_STATUS) => tr('Categories and status'),
                            (self::CASCADE_CATEG | self::CASCADE_DELETE) => tr('Categories and delete'),
                            (self::CASCADE_DELETE | self::CASCADE_STATUS) => tr('Delete and status'),
                            (self::CASCADE_CATEG | self::CASCADE_STATUS | self::CASCADE_DELETE) => tr('All'),
                        ],
                        'legacy_index' => 14,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $string_id = $this->getInsertId();
        if (isset($requestData[$string_id])) {
            $value = $requestData[$string_id];
        } elseif (isset($requestData[$string_id . '_old'])) {
            $value = '';
        } else {
            $value = $this->getValue();
        }

        $data = [
            'value' => $value,
        ];

        if ($this->getOption('selectMultipleValues') && ! is_array($data['value'])) {
            $data['value'] = explode(',', $data['value']);
        }

        return $data;
    }

    public function addValue($value)
    {
        $existing = explode(',', $this->getValue());
        if (! in_array($value, $existing)) {
            $existing[] = $value;
        }

        return implode(',', $existing);
    }

    public function removeValue($value)
    {
        $existing = explode(',', $this->getValue());
        $existing = array_filter($existing, function ($v) use ($value) {
            return $v != $value;
        });

        return implode(',', $existing);
    }

    public function useSelector()
    {
        global $prefs;

        if ($prefs['feature_search'] != 'y') {
            return false;
        }

        if ($this->getOption('selectMultipleValues')) {
            return false;
        }

        if ($this->getOption('displayOneItem') === 'one') {
            return false;
        }

        if ($this->getOption('preSelectFieldMethod') === 'crossSelect' || $this->getOption('preSelectFieldMethod') === 'crossSelectWildcards') {
            return false;
        }

        if ($this->getOption('displayFieldsListType') === 'table') {
            return false;
        }

        return true;
    }

    public function renderInput($context = [])
    {
        $trackerPerms = Perms::get('tracker', $this->getOption('trackerId'));

        if ($this->useSelector()) {
            $value = $this->getValue();
            $placeholder = tr(TikiLib::lib('object')->get_title('tracker', $this->getOption('trackerId')));
            $status = implode(' OR ', str_split($this->getOption('status', 'opc'), 1));
            $value = $value ? "trackeritem:$value" : null;

            $format = $this->formatForObjectSelector();

            $template = $this->renderTemplate('trackerinput/itemlink_selector.tpl', $context, [
                'placeholder' => $placeholder,
                'status' => $status,
                'selector_value' => $value,
                'selector_id' => 'item' . $this->getItemId() . $this->getInsertId(),
                'format' => $format,
                'createTrackerItems' => $trackerPerms->create_tracker_items,
            ]);

            return $template;
        }

        $data = [
            'list' => $this->getItemList(),
            'displayFieldsListType' => $this->getOption('displayFieldsListType'),
            'createTrackerItems' => $trackerPerms->create_tracker_items,
        ];

        $servicelib = TikiLib::lib('service');
        if ($this->getItemId()) {
            $data['next'] = $servicelib->getUrl([
                'controller' => 'tracker',
                'action' => 'update_item',
                'trackerId' => $this->getConfiguration('trackerId'),
                'itemId' => $this->getItemId(),
            ]);
        } else {
            $data['next'] = $servicelib->getUrl([
                'controller' => 'tracker',
                'action' => 'insert_item',
                'trackerId' => $this->getConfiguration('trackerId'),
            ]);
        }

        $data['selectMultipleValues'] = (bool) $this->getOption('selectMultipleValues');

        // 'crossSelect' overrides the preselection reference, which is enabled, when a cross reference Item Link <-> Item Link
        //	When selecting a value another item link can provide the relation, then the cross link can point to several records having the same linked value.
        //	Example Contact and Report links to a Company. Report also links to Contact. When selecting Contact, Only Contacts in the same company as the Report is linked to, should be made visible.
        //	When 'crossSelect' is enabled
        //		1) The dropdown list is no longer disabled (else disabled)
        //		2) All rows in the remote tracker matching the criterea are displayed in the dropdown list (else only 1 row is displayed)
        $method = $this->getOption('preSelectFieldMethod');
        if ($method == 'crossSelect' || $method == 'crossSelectWildcards') {
            $data['crossSelect'] = 'y';
        } else {
            $data['crossSelect'] = 'n';
        }

        // Prepare for 'crossSelect'
        $linkValue = false;		// Value which links the tracker items
        if ($data['crossSelect'] === 'y') {
            // Check if itemId is set / used.
            // If not, it must be set here
            $itemData = $this->getItemData();
            if (empty($itemData['itemId'])) {
                if (! empty($_REQUEST['itemId'])) {
                    $linkValue = $_REQUEST['itemId'];
                }
            } else {
                $linkValue = $itemData['itemId'];
            }
        }

        if ($preselection = $this->getPreselection($linkValue)) {
            $data['preselection'] = $preselection;
            $data['preselection_value'] = TikiLib::lib('trk')->get_item_value($this->getConfiguration('trackerId'), $this->getItemId(), $this->getOption('preSelectFieldHere'));
        } else {
            $preselection = $data['preselection'] = [];
            $data['preselection_value'] = "";
        }

        $data['filter'] = $this->buildFilter();

        if ($data['crossSelect'] === 'y' && ! empty($preselection) && is_array($preselection)) {
            if ($this->getOption('displayFieldsListType') === 'table') {
                // nothing to do, list is loaded dynamically via plugin trackerlist
            } else {
                $data['list'] = array_intersect_key($data['list'], array_flip($preselection));
            }
        }

        if ($this->getOption('preSelectFieldThere') && $this->getOption('preSelectFieldMethod') != 'crossSelectWildcards') {
            $data['predefined'] = $this->getItemsToClone();
        } else {
            $data['predefined'] = [];
        }

        if ($data['displayFieldsListType'] === 'table') {
            $data['trackerListOptions'] = [
                'trackerId' => $this->getOption('trackerId'),
                'fields' => implode(':', $this->getOption('displayFieldsList')),
                'editableall' => 'y',
                'showlinks' => 'y',
                'sortable' => 'type:reset',
                'sortList' => '[1,0]',
                'tsfilters' => 'type:nofilter',
                'tsfilteroptions' => 'type:reset',
                'tspaginate' => 'max:5',
                'checkbox' => '/' . $this->getInsertId() . '//////y/' . implode(',', is_array($this->getValue()) ? $this->getValue() : [$this->getValue()]),
                'ignoreRequestItemId' => 'y',
                'url' => $servicelib->getUrl([
                    'controller' => 'tracker',
                    'action' => 'update_item',
                    'trackerId' => $this->getOption('trackerId'),
                    'itemId' => '#itemId',
                ])
            ];
            if ($this->getOption('preSelectFieldThere')) {
                $data['trackerListOptions']['filterfield'] = $this->getOption('preSelectFieldThere');
                $data['trackerListOptions']['exactvalue'] = $data['preselection_value'];
                if ($this->getOption('preSelectFieldMethod') == 'crossSelectWildcards') {
                    $data['trackerListOptions']['exactvalue'] = 'or(*,' . $data['trackerListOptions']['exactvalue'] . ')';
                }
            }
            if ($this->getOption('trackerListOptions')) {
                $parser = new WikiParser_PluginArgumentParser();
                $arguments = $parser->parse($this->getOption('trackerListOptions'));
                $data['trackerListOptions'] = array_merge($data['trackerListOptions'], $arguments);
                if (! empty($arguments['editable']) && empty($arguments['editableall'])) {
                    $data['trackerListOptions']['editableall'] = 'n';
                }
                if (isset($arguments['checkbox']) && (empty($arguments['checkbox']) || $arguments['checkbox'] == 'n')) {
                    $data['trackerListOptions']['checkbox'] = '/' . $this->getInsertId() . '//////y/' . implode(',', $data['preselection']);
                }
            }
        }

        if ($this->getOption('fieldId') && $this->getOption('addItems')) {
            $definition = Tracker_Definition::get($this->getOption('trackerId'));
            $fieldArray = $definition->getField($this->getOption('fieldId'));
            $data['otherFieldPermName'] = $this->getFieldReference($fieldArray);
        }

        return $this->renderTemplate('trackerinput/itemlink.tpl', $context, $data);
    }

    /**
     * the labels on the select will not necessarily be the title field, so offer the object_selector the correct format string
     * also used to format the proper string for Relations field conversion which again uses object_selector
     */
    private function formatForObjectSelector()
    {
        $displayFieldsListArray = $this->getDisplayFieldsListArray();
        $definition = Tracker_Definition::get($this->getOption('trackerId'));
        if (! $definition) {
            Feedback::error(tr('ItemLink: Tracker %0 not found for field "%1"', $this->getOption('trackerId'), $this->getConfiguration('permName')));

            return '';
        }
        if ($displayFieldsListArray) {
            array_walk($displayFieldsListArray, function (& $field) use ($definition) {
                $fieldArray = $definition->getField($field);
                if (! $fieldArray) {
                    $message = tr('ItemLink: Field %0 not found for field "%1"', $field, $this->getConfiguration('permName'));
                    $field = '<div class="alert alert-danger">' . $message . '</div>';
                } else {
                    $field = '{tracker_field_' . $this->getFieldReference($fieldArray) . '}';
                }
            });
            if ($format = $this->getOption('displayFieldsListFormat')) {
                $format = tra($format, '', false, $displayFieldsListArray);
            } else {
                $format = implode(' ', $displayFieldsListArray);
            }
        } else {
            $fieldArray = $definition->getField($this->getOption('fieldId'));
            if (! $fieldArray) {
                $message = tr('ItemLink: Field %0 not found for field "%1"', $this->getOption('fieldId'), $this->getConfiguration('permName'));
                $format = '<div class="alert alert-danger">' . $message . '</div>';
            } elseif (! $format = $this->getOption('displayFieldsListFormat')) {
                $format = '{tracker_field_' . $this->getFieldReference($fieldArray) . '} (itemId:{object_id})';
            }
        }

        return $format;
    }

    /**
     * gets the field permName or fieldId depending on unified_trackerfield_keys
     * DynamicList and ItemLink fields return the permName_text version to render the actual label
     *
     * @param $fieldArray
     * @return string
     */
    private function getFieldReference($fieldArray)
    {
        global $prefs;

        if ($prefs['unified_trackerfield_keys'] === 'fieldId') {
            return $fieldArray['fieldId'];
        } elseif ($fieldArray['type'] == 'r' || $fieldArray['type'] == 'w') {
            return $fieldArray['permName'] . '_text';
        }

        return $fieldArray['permName'];
    }

    private function buildFilter()
    {
        return [
            'tracker_id' => $this->getOption('trackerId'),
        ];
    }

    public function renderOutput($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        $item = $this->getValue();
        $label = $this->renderInnerOutput($context);

        if ($item && ! is_array($item) && $context['list_mode'] !== 'csv' && $this->getOption('fieldId')) {
            $smarty->loadPlugin('smarty_function_object_link');

            if ($this->getOption('linkPage')) {
                $link = smarty_function_object_link(
                    [
                        'type' => 'wiki page',
                        'id' => $this->getOption('linkPage') . '&itemId=' . $item,	// add itemId param TODO properly
                        'title' => $label,
                    ],
                    $smarty->getEmptyInternalTemplate()
                );
                // decode & and = chars
                return str_replace(['%26', '%3D'], ['&', '='], $link);
            }

            return parent::renderOutput($context);
        } elseif ($context['list_mode'] == 'csv' && $item) {
            if ($label) {
                return $label;
            }

            return $item;
        } elseif ($label) {
            return $label;
        }
    }

    protected function renderInnerOutput($context = [])
    {
        $item = $this->getValue();

        if (! is_array($item)) {
            // single value item field
            $items = [$item];
        } else {
            // item field has multiple values
            $items = $item;
        }

        $labels = [];
        foreach ($items as $i) {
            $labels[] = $this->getItemLabel($i, $context);
        }

        if ($this->getOption('displayFieldsListType') === 'table' && $context['list_mode'] !== 'csv') {
            $headers = [];
            $trackerId = (int) $this->getOption('trackerId');
            $definition = Tracker_Definition::get($trackerId);
            if ($fields = $this->getDisplayFieldsListArray()) {
                foreach ($fields as $fieldId) {
                    $field = $definition->getField($fieldId);
                    $headers[] = $field['name'];
                }
            }
            $label = '<table class="table table-condensed" style="background:none;"><thead><tr><td>' . tra($this->getTableDisplayFormat(), '', false, $headers) . '</td></tr></thead><tbody>' . implode('', $labels) . '</tbody></table>';
        } else {
            $label = implode(', ', $labels);
        }

        return $label;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $item = $this->getValue();
        $label = $this->getItemLabel($item);
        $baseKey = $this->getBaseKey();

        $out = [
            $baseKey => $typeFactory->identifier($item),
            "{$baseKey}_text" => $typeFactory->sortable($label),
        ];

        $indexRemote = array_filter($this->getOption('indexRemote', []));

        if (count($indexRemote) && is_numeric($item)) {
            $trklib = TikiLib::lib('trk');
            $trackerId = $this->getOption('trackerId');
            $item = $trklib->get_tracker_item($item);

            $definition = Tracker_Definition::get($trackerId);
            $factory = $definition->getFieldFactory();
            foreach ($indexRemote as $fieldId) {
                $field = $definition->getField($fieldId);
                $handler = $factory->getHandler($field, $item);

                foreach ($handler->getDocumentPart($typeFactory) as $key => $field) {
                    if (strpos($key, 'tracker_field') === 0) {
                        $key = $baseKey . substr($key, strlen('tracker_field'));
                        $out[$key] = $field;
                    }
                }
            }
        }

        return $out;
    }

    public function getProvidedFields()
    {
        $baseKey = $this->getBaseKey();
        $fields = [$baseKey, "{$baseKey}_text"];

        $trackerId = $this->getOption('trackerId');
        $indexRemote = array_filter((array) $this->getOption('indexRemote'));

        if (count($indexRemote)) {
            if ($definition = Tracker_Definition::get($trackerId)) {
                $factory = $definition->getFieldFactory();

                foreach ($indexRemote as $fieldId) {
                    $field = $definition->getField($fieldId);
                    $handler = $factory->getHandler($field);

                    foreach ($handler->getProvidedFields() as $key) {
                        $fields[] = $baseKey . substr($key, strlen('tracker_field'));
                    }
                }
            }
        }

        return $fields;
    }

    public function getGlobalFields()
    {
        $baseKey = $this->getBaseKey();
        $fields = ["{$baseKey}_text" => true];

        $trackerId = $this->getOption('trackerId');
        $indexRemote = array_filter($this->getOption('indexRemote') ?: []);

        if (count($indexRemote)) {
            if ($definition = Tracker_Definition::get($trackerId)) {
                $factory = $definition->getFieldFactory();

                foreach ($indexRemote as $fieldId) {
                    $field = $definition->getField($fieldId);
                    $handler = $factory->getHandler($field);

                    foreach ($handler->getGlobalFields() as $key => $flag) {
                        $fields[$baseKey . substr($key, strlen('tracker_field'))] = $flag;
                    }
                }
            }
        }

        return $fields;
    }

    public function getItemValue($itemId)
    {
        return $label = TikiLib::lib('object')->get_title('trackeritem', $itemId);
    }

    public function getItemLabel($itemIds, $context = ['list_mode' => ''])
    {
        $items = explode(',', $itemIds);

        $trklib = TikiLib::lib('trk');

        $fulllabel = '';

        foreach ($items as $itemId) {
            if (! empty($fulllabel)) {
                $fulllabel .= ', ';
            }

            $item = $trklib->get_tracker_item($itemId);

            if (! $item) {
                continue;
            }

            $trackerId = (int) $this->getOption('trackerId');
            $status = $this->getOption('status', 'opc');

            $parts = [];

            if ($fields = $this->getDisplayFieldsListArray()) {
                foreach ($fields as $fieldId) {
                    if (isset($item[$fieldId])) {
                        $parts[] = $fieldId;
                    }
                }
            } else {
                $fieldId = $this->getOption('fieldId');

                if (isset($item[$fieldId])) {
                    $parts[] = $fieldId;
                }
            }


            if (count($parts)) {
                if ($this->getOption('displayFieldsListType') === 'table' && $context['list_mode'] !== 'csv') {
                    $label = "<tr><td>" . $trklib->concat_item_from_fieldslist(
                        $trackerId,
                        $itemId,
                        $parts,
                        $status,
                        '</td><td>',
                        $context['list_mode'],
                        false,
                        $this->getTableDisplayFormat(),
                        $item
                    ) . "</td></tr>";
                } else {
                    $label = $trklib->concat_item_from_fieldslist(
                        $trackerId,
                        $itemId,
                        $parts,
                        $status,
                        ' ',
                        $context['list_mode'],
                        ! $this->getOption('linkToItem'),
                        $this->getOption('displayFieldsListFormat'),
                        $item
                    );
                }
            } else {
                $label = TikiLib::lib('object')->get_title('trackeritem', $itemId);
            }

            if ($label) {
                $fulllabel .= $label;
            }
        }

        return $fulllabel;
    }

    public function getItemList()
    {
        if ($displayFieldsList = $this->getDisplayFieldsListArray()) {
            if ($this->getOption('displayFieldsListType') === 'table') {
                $list = TikiLib::lib('trk')->get_fields_from_fieldslist(
                    $this->getOption('trackerId'),
                    $displayFieldsList
                );
            } else {
                $list = TikiLib::lib('trk')->concat_all_items_from_fieldslist(
                    $this->getOption('trackerId'),
                    $displayFieldsList,
                    $this->getOption('status', 'opc'),
                    ' ',
                    true
                );
                $list = $this->handleDuplicates($list);
            }
        } else {
            $list = TikiLib::lib('trk')->get_all_items(
                $this->getOption('trackerId'),
                $this->getOption('fieldId'),
                $this->getOption('status', 'opc')
            );
            $list = $this->handleDuplicates($list);
        }

        return $list;
    }

    private function handleDuplicates($list)
    {
        $uniqueList = array_unique($list);
        if ($this->getOption('displayOneItem') != 'multi') {
            $value = $this->getValue();
            if ($value && isset($list[$value]) && ! isset($uniqueList[$value])) {
                // if we already have a value set make sure we return the correct itemId one
                $uniqueList = [];
                foreach ($list as $itemId => $label) {
                    if (! in_array($label, $uniqueList)) {
                        if ($label === $list[$value]) {
                            $uniqueList[$value] = $label;
                        } else {
                            $uniqueList[$itemId] = $label;
                        }
                    }
                }
            }

            return $uniqueList;
        } elseif ($uniqueList != $list) {
            $newlist = [];
            foreach ($list as $itemId => $label) {
                if (in_array($label, $newlist)) {
                    $label = $label . " ($itemId)";
                }
                $newlist[$itemId] = $label;
            }

            return $newlist;
        }

        return $list;
    }

    private function getTableDisplayFormat()
    {
        $format = $this->getOption('displayFieldsListFormat');
        if (! $format) {
            $cnt = count($this->getDisplayFieldsListArray());
            $format = '%' . implode(',%', range(0, $cnt - 1));
        }

        return str_replace(',', '</td><td>', $format);
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
        $sourceOptions = explode(',', $info['options']);
        $trackerId = isset($sourceOptions[0]) ? (int) $sourceOptions[0] : 0;
        $fieldId = isset($sourceOptions[1]) ? (int) $sourceOptions[1] : 0;
        $status = isset($sourceOptions[4]) ? (int) $sourceOptions[4] : 'opc';

        $info['type'] = 'd';
        $info['options'] = $this->getRemoteItemLinks($syncInfo, $trackerId, $fieldId, $status);

        return $info;
    }

    private function getRemoteItemLinks($syncInfo, $trackerId, $fieldId, $status)
    {
        $controller = new Services_RemoteController($syncInfo['provider'], 'tracker');
        $items = $controller->getResultLoader('list_items', ['trackerId' => $trackerId, 'status' => $status]);
        $result = $controller->edit_field(['trackerId' => $trackerId, 'fieldId' => $fieldId]);

        $permName = $result['field']['permName'];
        if (empty($permName)) {
            return '';
        }

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item['itemId'] . '=' . $item['fields'][$permName];
        }

        return implode(',', $parts);
    }

    private function getPreselection($linkValue = false)
    {
        $trklib = TikiLib::lib('trk');

        $localField = $this->getOption('preSelectFieldHere');
        $remoteField = $this->getOption('preSelectFieldThere');
        $method = $this->getOption('preSelectFieldMethod');
        $localTrackerId = $this->getConfiguration('trackerId');
        $remoteTrackerId = $this->getOption('trackerId');

        $localValue = $trklib->get_item_value($localTrackerId, $this->getItemId(), $localField);

        if ($method == 'domain') {
            if (! preg_match('@^(?:http://)?([^/]+)@i', $localValue, $matches)) {
                return '';
            }
            $host = $matches[1];
            preg_match('/[^.]+\.[^.]+$/', $host, $matches);
            $domain = $matches[0];
            if (strlen($domain) > 6) {
                // avoid com.sg or similar country subdomains
                $localValue = $domain;
            } else {
                $localValue = $host;
            }
        }

        if ($method == 'domain' || $method == 'partial') {
            $partial = true;
        } else {
            $partial = false;
        }

        // If $linkValue is specified, it means get_all_item_id should be called,
        //	which can match a set of linked values. Not just 1
        if (! empty($linkValue)) {
            // get_all_item_id always collects all matching links. $partial is ignored
            //	Use the local value in the search, when it's available
            $value = empty($localValue) ? $linkValue : $localValue;
            $data = $trklib->get_all_item_id($remoteTrackerId, $remoteField, $value);
        } else {
            $data = $trklib->get_item_id($remoteTrackerId, $remoteField, $localValue, $partial);
        }

        return $data;
    }

    public function handleSave($value, $oldValue)
    {
        // if selectMultipleValues is enabled, convert the array
        // of options to string before saving the field value in the db
        if ($this->getOption('selectMultipleValues') || $this->getOption('displayFieldsListType') === 'table') {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
        } else {
            $value = (int) $value;
        }

        return [
            'value' => $value,
        ];
    }

    public function itemsRequireRefresh($trackerId, $modifiedFields)
    {
        if ($this->getOption('trackerId') != $trackerId) {
            return false;
        }

        $usedFields = array_merge(
            [$this->getOption('fieldId')],
            $this->getOption('indexRemote', []),
            $this->getDisplayFieldsListArray()
        );

        $intersect = array_intersect($usedFields, $modifiedFields);

        return count($intersect) > 0;
    }

    public function cascadeCategories($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_CATEG);
    }

    public function cascadeStatus($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_STATUS);
    }

    public function cascadeDelete($trackerId)
    {
        return $this->cascade($trackerId, self::CASCADE_DELETE);
    }

    private function cascade($trackerId, $flag)
    {
        if ($this->getOption('trackerId') != $trackerId) {
            return false;
        }

        return ($this->getOption('cascade') & $flag) > 0;
    }

    public function watchCompare($old, $new)
    {
        $o = $this->getItemLabel($old);
        $n = $this->getItemLabel($new);

        return parent::watchCompare($o, $n);	// then compare as text
    }

    /**
     * @return mixed
     */
    private function getDisplayFieldsListArray()
    {
        global $user, $tiki_p_admin_trackers;

        $fields = [];
        $option = $this->getOption('displayFieldsList');
        if (! is_array($option)) {
            $option = [$option];
        }
        // filter by user-visible fields
        $trackerId = (int) $this->getOption('trackerId');
        $definition = Tracker_Definition::get($trackerId);
        if ($definition) {
            foreach ($option as $fieldId) {
                $field = $definition->getField($fieldId);
                if ($field['isPublic'] == 'y' && ($field['isHidden'] == 'n' || $field['isHidden'] == 'c' || $field['isHidden'] == 'p' || $field['isHidden'] == 'a' || $tiki_p_admin_trackers == 'y')
                    && $field['type'] != 'x' && $field['type'] != 'h' && ($field['type'] != 'p' || $field['options_array'][0] != 'password')
                    && (empty($field['visibleBy']) or array_intersect(TikiLib::lib('tiki')->get_user_groups($user), $field['visibleBy']) || $tiki_p_admin_trackers == 'y')) {
                    $fields[] = $fieldId;
                }
            }
        } else {
            Feedback::error(tr('ItemLink field "%0": Tracker ID #%1 not found', $this->getConfiguration('permName'), $trackerId));
        }

        return $fields;
    }

    /***
     * Generate facets for search results
     *
     * @return array
     */
    public function getFacets()
    {
        $baseKey = $this->getBaseKey();

        return [
            Search_Query_Facet_Term::fromField($baseKey)
                ->setLabel($this->getConfiguration('name'))
                ->setRenderCallback([$this, 'getItemValue']),
        ];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        if (! $this->getOption('selectMultipleValues')) {
            // Cannot handle multiple values when exporting

            $schema->addNew($permName, 'id')
                ->setLabel($name)
                ->setRenderTransform(function ($value) {
                    return $value;
                })
                ->setParseIntoTransform(function (& $info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                })
                ;

            $fullLookup = new Tracker\Tabular\Schema\CachedLookupHelper;
            $fullLookup->setLookup(function ($value) {
                return $this->getItemLabel($value);
            });
            $schema->addNew($permName, 'lookup')
                ->setLabel($name)
                ->setReadOnly(true)
                ->addQuerySource('text', "tracker_field_{$permName}_text")
                ->setRenderTransform(function ($value, $extra) use ($fullLookup) {
                    if (isset($extra['text'])) {
                        return $extra['text'];
                    }

                    return $fullLookup->get($value);
                })
                ;

            if ($fieldId = $this->getOption('fieldId')) {
                $simpleField = Tracker\Tabular\Schema\CachedLookupHelper::fieldLookup($fieldId);
                $invertField = Tracker\Tabular\Schema\CachedLookupHelper::fieldInvert($fieldId);

                // if using displayFieldsList then only export the 'value' of the field, i.e. the title of the linked item
                $useTextLabel = empty(array_filter($this->getOption('displayFieldsList')));

                $schema->addNew($permName, 'lookup-simple')
                    ->setLabel($name)
                    ->addIncompatibility($permName, 'id')
                    ->addQuerySource('text', "tracker_field_{$permName}_text")
                    ->setRenderTransform(function ($value, $extra) use ($simpleField, $useTextLabel) {
                        if (isset($extra['text']) && $useTextLabel) {
                            return $extra['text'];
                        }

                        return $simpleField->get($value);
                    })
                    ->setParseIntoTransform(function (& $info, $value) use ($permName, $invertField) {
                        if ($id = $invertField->get($value)) {
                            $info['fields'][$permName] = $id;
                        }
                    })
                    ;
            }
            $schema->addNew($permName, 'name')
                ->setLabel($name)
                ->setReadOnly(true)
                ->setRenderTransform(function ($value) {
                    return $this->getItemLabel($value, ['list_mode' => 'csv']);
                });
        }

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $collection->addNew($permName, 'selector')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_os", [
                'type' => 'trackeritem',
                'tracker_status' => implode(' OR ', str_split($this->getOption('status', 'opc'), 1)),
                'tracker_id' => $this->getOption('trackerId'),
                '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->getOption('trackerId'))),
            ]))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterIdentifier((string) $value, $baseKey);
                }
            })
            ;

        $collection->addNew($permName, 'multiselect')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector(
                "tf_{$permName}_ms",
                [
                'type' => 'trackeritem',
                'tracker_status' => implode(' OR ', str_split($this->getOption('status', 'opc'), 1)),
                'tracker_id' => $this->getOption('trackerId'),
                '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->getOption('trackerId'))),
                ],
                true
            ))	// for multi
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $value = array_map(function ($v) {
                        return str_replace('trackeritem:', '', $v);
                    }, $value);
                    $query->filterMultivalue(implode(' OR ', $value), $baseKey);
                }
            })
        ;

        $indexRemote = array_filter($this->getOption('indexRemote') ?: []);
        if (count($indexRemote)) {
            $trklib = TikiLib::lib('trk');
            $trackerId = $this->getOption('trackerId');
            $item = $trklib->get_tracker_item($this->getItemId());

            $definition = Tracker_Definition::get($trackerId);
            $factory = $definition->getFieldFactory();
            foreach ($indexRemote as $fieldId) {
                $field = $definition->getField($fieldId);
                $handler = $factory->getHandler($field, $item);

                if ($handler instanceof Tracker_Field_Filterable) {
                    $handler->setBaseKeyPrefix($permName . '_');
                    $sub = $handler->getFilterCollection();
                    $collection->addCloned($permName, $sub);
                }
            }
        }

        return $collection;
    }

    /**
     * Convert all items data to the only supported field type: Relations
     * This needs to be quick as it can run on potentially huge dataset, thus the raw db layer.
     * Also converts the relevant field options which are:
     *  - relation = trackername.fieldpermname.items
     *  - filter = ItemLink trackerId, trackeritem object type and status filter if different than all
     *  - format = converts multiple fields and format (if any) or uses the selected display field
     *
     * @param string $type - the field type to convert to
     * @throws Exception
     * @return array - converted field options
     */
    public function convertFieldTo($type)
    {
        if ($type !== 'REL') {
            throw new Exception(tr("Unsupported field conversion type: from %0 to %1", $this->getConfiguration('type'), $type));
        }

        $trklib = TikiLib::lib('trk');
        $relationlib = TikiLib::lib('relation');

        $trackerId = $this->getConfiguration('trackerId');
        $fieldId = $this->getConfiguration('fieldId');
        $remoteTrackerId = $this->getOption('trackerId');

        $tracker = $trklib->get_tracker($trackerId);

        $relation = preg_replace("/[^a-z0-9]/", "", strtolower($tracker['name']));
        $relation .= '.' . strtolower($this->getConfiguration('permName'));
        $relation .= '.' . 'items';

        $suffix = 1;
        while ($relationlib->relation_exists($relation, 'trackeritem')) {
            $relation = rtrim($relation, '0..9') . ($suffix++);
        }

        $format = $this->formatForObjectSelector();
        $filter = 'tracker_id=' . $remoteTrackerId . '&object_type=trackeritem';
        $status = $this->getOption('status');
        if ($status != 'opc') {
            $filter .= '&tracker_status=' . (implode(' OR ', str_split($status)));
        }

        $tx = $trklib->begin();
        $data = $trklib->fetchAll("SELECT tti.`itemId`, ttif.`value`
			FROM `tiki_tracker_items` tti, `tiki_tracker_item_fields` ttif
			WHERE tti.`trackerId` = ?
				AND tti.`itemId` = ttif.`itemId`
				AND ttif.`fieldId` = ?", [$trackerId, $fieldId]);
        foreach ($data as $row) {
            $itemId = $row['itemId'];
            $remoteIds = array_filter(explode(',', trim($row['value'])));
            $value = implode(
                "\n",
                array_map(function ($v) {
                    return "trackeritem:" . trim($v);
                }, $remoteIds)
            );
            $trklib->table('tiki_tracker_item_fields')->update(
                ['value' => $value],
                ['itemId' => $itemId, 'fieldId' => $fieldId]
            );
            foreach ($remoteIds as $id) {
                $relationlib->add_relation($relation, 'trackeritem', $itemId, 'trackeritem', $id);
            }
        }
        $tx->commit();

        return [
            'relation' => $relation,
            'filter' => $filter,
            'format' => $format
        ];
    }

    /**
     * Retrieve remote tracker items that should be available to be cloned instead of starting
     * with an empty item when user wants to add a new remote item.
     * @return array - formatter: itemId => item label
     */
    private function getItemsToClone()
    {
        $trackerId = $this->getOption('trackerId');
        $definition = Tracker_Definition::get($trackerId);
        $utilities = new Services_Tracker_Utilities;
        $result = [];

        $predefined = TikiLib::lib('trk')->get_all_item_id($trackerId, $this->getOption('preSelectFieldThere'), '*');
        foreach ($predefined as $itemId) {
            $item = $utilities->getItem($trackerId, $itemId);
            $result[$itemId] = $utilities->getTitle($definition, $item);
        }
        $result = $this->handleDuplicates($result);
        asort($result);

        return $result;
    }
}
