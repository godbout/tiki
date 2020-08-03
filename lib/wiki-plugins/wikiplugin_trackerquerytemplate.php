<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_trackerquerytemplate_info()
{
    return [
        'name' => tra('Tracker Query Template'),
        'documentation' => 'PluginTrackerQueryTemplate',
        'description' => tra('Generate a form from tracker data'),
        'prefs' => ['feature_trackers', 'wikiplugin_trackerquerytemplate'],
        'body' => tra('Wiki Syntax, with variables from tracker query.'),
        'filter' => 'striptags',
        'iconname' => 'code',
        'introduced' => 10,
        'tags' => [ 'basic' ],
        'params' => [
            'tracker' => [
                'required' => true,
                'name' => tra('Tracker'),
                'description' => tr(
                    'The name of the tracker to be queried, or if %0, the tracker ID.',
                    '<code>byname="n"</code>'
                ),
                'since' => '10.0',
                'filter' => 'text',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'debug' => [
                'required' => false,
                'name' => tra('Debug'),
                'description' => tra('Turn tracker query debug on (off by default).'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ]
            ],
            'byname' => [
                'required' => false,
                'name' => tra('Tracker'),
                'description' => tr('Use the tracker name instead of tracker ID in the %0 parameter. Also use the field
					name instead of field ID in the filter parameters. Set to Yes (%1) to use names (default) or
					No (%2) to use IDs.', '<code>tracker</code>', '<code>y</code>', '<code>n</code>'),
                'since' => '10.0',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ]
            ],
            'render' => [
                'required' => false,
                'name' => tra('Render'),
                'description' => tra('Render as needed for trackers (default).'),
                'since' => '10.0',
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ]
            ],
            'itemid' => [
                'required' => false,
                'name' => tra('Tracker Item Id'),
                'description' => tra('Item id of tracker item'),
                'since' => '10.0',
                'default' => '',
                'filter' => 'digits',
                'profile_reference' => 'tracker_item',
            ],
            'itemids' => [
                'required' => false,
                'name' => tra('Tracker Item Ids'),
                'description' => tra('Item id of tracker items, separated with comma'),
                'since' => '11.0',
                'default' => '',
                'filter' => 'digits',
                'separator' => ',',
                'profile_reference' => 'tracker_item',
            ],
            'likefilters' => [
                'required' => false,
                'name' => tra('Like Filters'),
                'description' => tr(
                    'Apply "like" filters to fields. Format: %0field:value;field:value;field:value%1,
					where %0field%1 may be the field name or ID depending on the setting for the %0byname%1 parameter.',
                    '<code>',
                    '</code>'
                ),
                'since' => '10.0',
                'filter' => 'text',
                'default' => ''
            ],
            'andfilters' => [
                'required' => false,
                'name' => tra('And Filters'),
                'description' => tr(
                    'Apply "and" filters to fields. Format: %0field:value;field:value;field:value%1,
					where %0field%1 may be the field name or ID depending on the setting for the %0byname%1 parameter.',
                    '<code>',
                    '</code>'
                ),
                'since' => '10.0',
                'filter' => 'text',
                'default' => ''
            ],
            'orfilters' => [
                'required' => false,
                'name' => tra('Or Filters'),
                'description' => tr(
                    'Apply "or" filters to fields. Format: %0field:value;field:value;field:value%1,
					where %0field%1 may be the field name or ID depending on the setting for the %0byname%1 parameter.',
                    '<code>',
                    '</code>'
                ),
                'since' => '10.0',
                'filter' => 'text',
                'default' => ''
            ],
            'getlast' => [
                'required' => false,
                'name' => tra('Get Last'),
                'description' => tra('Retrieve only the last item from the tracker.'),
                'since' => '10.0',
                'filter' => 'alpha',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ]
            ],
        ]
    ];
}

function wikiplugin_trackerquerytemplate($data, $params)
{
    $params = array_merge(
        [
            'tracker' => '',
            'debug' => '',
            'byname' => 'y',
            'render' => 'y',
            'likefilters' => '',
            'andfilters' => '',
            'getlast' => ''
        ],
        $params
    );

    if (! empty($params['itemids'])) {
        $itemIds = $params['itemids'];
        unset($params['itemids']);
        $newData = '';
        foreach ($itemIds as $itemId) {
            if (! empty($itemId)) {
                $params['itemid'] = $itemId;
                $newData .= wikiplugin_trackerquerytemplate($data, $params);
            }
        }

        return $newData;
    }

    $handler = new dataToFieldHandler();

    foreach ($params as &$param) {//We parse the variables
        $param = $handler->parse($param);
    }

    $query = Tracker_Query::tracker($params['tracker'])->excludeDetails();

    $pattern = 'id';

    if (! empty($params['byname']) && $params['byname'] == 'y') {
        $query->byName();
        $pattern = 'name';
    }

    if (! empty($params['render']) && $params['render'] == 'n') {
        $query->render(false);
    }

    if (! empty($params['itemid']) || isset($_REQUEST['itemId'])) {
        if (isset($_REQUEST['itemId'])) { //itemId overrides parameters
            $query->itemId($_REQUEST['itemId']);
            unset($_REQUEST['itemId']); //we unset because nested plugins may need to have itemId set
        } else {
            $query->itemId($params['itemid']);
        }
    }

    if (! empty($params['likefilters'])) {
        $likefilters = explode(';', $params['likefilters']);
        foreach ($likefilters as $likefilter) {
            $filter = explode(':', $likefilter);
            $query->filterFieldByValueLike($filter[0], $filter[1]);
        }
    }

    if (! empty($params['andfilters'])) {
        $andfilters = explode(';', $params['andfilters']);
        foreach ($andfilters as $andfilter) {
            $filter = explode(':', $andfilter);
            $query->filterFieldByValue($filter[0], $filter[1]);
        }
    }

    if (! empty($params['orfilters'])) {
        $orfilters = explode(';', $params['orfilters']);
        foreach ($orfilters as $orfilter) {
            $filter = explode(':', $orfilter);
            $query->filterFieldByValueOr($filter[0], $filter[1]);
        }
    }

    if (! empty($params['debug']) && $params['debug'] == 'y') {
        $query->debug();
    }

    if (! empty($params['getlast']) && $params['getlast'] == 'y') {
        $items = $query->getLast();
    } else {
        $items = $query->query();
    }

    $newData = '';

    foreach ($items as $itemId => $fields) {
        $trackerId = $query->trackerId();
        $handler->set($pattern, $fields, $query->itemsRaw[$itemId], $itemId, $trackerId);

        $newData .= $handler->parse($data);
        $newData = "~np~" . TikiLib::lib('parser')->parse_data($newData, ['is_html' => true]) . "~/np~";
        $handler->pop();
    }

    return $newData;
}

class dataToFieldHandler
{
    public static $itemStack = [];
    public $pattern;
    private $trackerId;
    private $itemId;
    private $fields;
    private $fieldsRaw;

    public function __construct() //initially set it to the last called item from trackers if it exists
    {
        $last = end(self::$itemStack);

        if (! empty($last)) {
            $this->set($last['pattern'], $last['fields'], $last['fieldsRaw'], $last['itemId'], $last['trackerId']);
        }
    }

    public function set($pattern, $fields, $fieldsRaw, $itemId, $trackerId)
    {
        $this->pattern = $pattern;
        $this->trackerId = $trackerId;
        $this->itemId = $itemId;
        $this->fields = $fields;
        $this->fieldsRaw = $fieldsRaw;

        self::$itemStack[] = [
            "pattern" => $this->pattern,
            "fields" => $this->fields,
            "fieldsRaw" => $this->fieldsRaw,
            "itemId" => $this->itemId,
            "trackerId" => $this->trackerId,
        ];
    }

    public function pop()
    {
        array_pop(self::$itemStack);
    }

    public function parse($data)
    {
        global $tikilib;

        $checkedData = trim($data);
        if (empty($checkedData) || empty(self::$itemStack)) {
            return $data;
        }

        $data = str_replace('$trackerId$', $this->trackerId, $data);
        $data = str_replace('$itemId$', $this->itemId, $data);
        $data = str_replace('$' . $this->trackerId . '_itemId$', $this->itemId, $data);

        if ($this->pattern == 'name') {
            foreach ($this->fields as $key => $field) {
                $data = str_replace('$' . $key . '$', $field, $data);
                $data = str_replace('$~' . $key . '$', $this->fieldsRaw[$key], $data);
            }
        } else {
            preg_match_all('/[\{][$](.)+?[\}]/', $data, $matches);
            if (! empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $data = str_replace($match, "(" . substr($match, 1, -1) . ")", $data);
                }
            }

            foreach ($this->fields as $key => $field) {
                $data = str_replace('($f_' . $key . ')', $field, $data);
                $data = str_replace('($~f_' . $key . ')', $this->fieldsRaw[$key], $data);
                $data = str_replace('{$f_' . $key . '}', $field, $data);
                $data = str_replace('{$~f_' . $key . '}', $this->fieldsRaw[$key], $data);
            }
        }

        if (strpos($data, '$created$')) {
            $data = str_replace('$created$', $tikilib->get_short_date($tikilib->getOne("SELECT created FROM tiki_tracker_items WHERE itemId = ?", [$this->itemId])), $data);
        }

        return $data;
    }
}
