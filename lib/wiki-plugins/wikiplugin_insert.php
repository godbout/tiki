<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_insert_info()
{
    return [
        'name' => tr('Insert Tracker Item'),
        'description' => tr('Create a tracker item when the plugin is inserted in the page. The plugin code is removed and replaced by a link to the newly created item.'),
        'prefs' => ['wikiplugin_insert', 'feature_trackers', 'wikiplugin_objectlink'],
        'tags' => ['basic'],
        'iconname' => 'add',
        'introduced' => 10,
        'extraparams' => true,
        'defaultfilter' => 'text',
    ];
}

function wikiplugin_insert_rewrite($data, $params, $context)
{
    $tikilib = TikiLib::lib('tiki');

    $trackerIds = $tikilib->get_preference('tracker_insert_allowed', [], true);

    foreach ($trackerIds as $trackerId) {
        $utilities = new Services_Tracker_Utilities;
        $item = Tracker_Item::newItem($trackerId);

        if (! $item->canModify()) {
            continue;
        }

        $definition = $item->getDefinition();

        if (! $definition->canInsert(array_keys($params))) {
            continue;
        }

        $available = [];
        foreach ($params as $key => $value) {
            if ($item->canModifyField($key)) {
                $available[$key] = $value;
            }
        }

        $id = $utilities->insertItem(
            $definition,
            [
                'status' => 'o',
                'fields' => $available,
            ]
        );

        if (false !== $id) {
            $relationlib = TikiLib::lib('relation');
            $relationlib->add_relation('tiki.source.creator', 'trackeritem', $id, $context['type'], $context['itemId']);

            return "{objectlink type=trackeritem id=$id}";
        }
    }

    return false;
}

function wikiplugin_insert($data, $params)
{
    return '__' . tr('Item not inserted') . '__';
}
