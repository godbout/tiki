<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once 'lib/wiki/pluginslib.php';

function wikiplugin_listexecute_info()
{
    return [
        'name' => tra('List Execute'),
        'documentation' => 'PluginListExecute',
        'description' => tra('Set custom actions that can be executed on a filtered list of objects'),
        'prefs' => ['wikiplugin_listexecute', 'feature_search'],
        'body' => tra('List configuration information'),
        'validate' => 'all',
        'filter' => 'wikicontent',
        'profile_reference' => 'search_plugin_content',
        'iconname' => 'list',
        'introduced' => 11,
        'tags' => [ 'advanced' ],
        'params' => [
        ],
    ];
}

function wikiplugin_listexecute($data, $params)
{
    static $iListExecute = 0;
    $iListExecute++;

    $unifiedsearchlib = TikiLib::lib('unifiedsearch');

    $actions = [];

    $factory = new Search_Action_Factory;
    $factory->register(
        [
            'change_status' => 'Search_Action_ChangeStatusAction',
            'delete' => 'Search_Action_Delete',
            'email' => 'Search_Action_EmailAction',
            'wiki_approval' => 'Search_Action_WikiApprovalAction',
            'tracker_item_modify' => 'Search_Action_TrackerItemModify',
            'tracker_item_clone' => 'Search_Action_TrackerItemClone',
            'filegal_change_filename' => 'Search_Action_FileGalleryChangeFilename',
            'filegal_image_overlay' => 'Search_Action_FileGalleryImageOverlay',
        ]
    );

    $query = new Search_Query;
    $unifiedsearchlib->initQuery($query);

    $matches = WikiParser_PluginMatcher::match($data);

    $builder = new Search_Query_WikiBuilder($query);
    $builder->apply($matches, true);
    $tsret = $builder->applyTablesorter($matches, true);
    if (! empty($tsret['max']) || ! empty($_GET['numrows'])) {
        $max = ! empty($_GET['numrows']) ? $_GET['numrows'] : $tsret['max'];
        $builder->wpquery_pagination_max($query, $max);
        $builder->applyPagination();
    }
    $paginationArguments = $builder->getPaginationArguments();

    if (! empty($_REQUEST[$paginationArguments['sort_arg']])) {
        $query->setOrder($_REQUEST[$paginationArguments['sort_arg']]);
    }

    $selectedObjects = $_POST["objects$iListExecute"] ?? [];

    if (! empty($selectedObjects)) {
        $searchQuery = clone $query;
        if (in_array('ALL', $selectedObjects)) {
            // unified search needs a hard limit and we want to apply the action to as many items as possible
            $query->setRange(0, 9999);
        } else {
            // select only the items to apply the action to
            foreach ($selectedObjects as $identifier) {
                list($type, $id) = explode(':', $identifier);
                if ($type && $type != 'aggregate' && $id) {
                    $query->addObject($type, $id);
                }
            }
        }
    }

    $customOutput = false;

    foreach ($matches as $match) {
        $name = $match->getName();

        if ($name == 'action') {
            $action = $factory->fromMatch($match);

            if ($action && $action->isAllowed(Perms::get()->getGroups())) {
                $actions[$action->getName()] = $action;
            } else {
                foreach ($action->getSteps() as $step) {
                    if ($step instanceof Search_Action_UnknownStep) {
                        Feedback::error(tr("Invalid action: %0.", $step->getName()));
                    }
                }
            }
        }

        if ($name == 'output') {
            $customOutput = true;
        }
    }

    $index = $unifiedsearchlib->getIndex();

    PluginsLibUtil::handleDownload($query, $index, $matches);

    $result = $query->search($index);
    $result->setId('wplistexecute-' . $iListExecute);

    $resultBuilder = new Search_ResultSet_WikiBuilder($result);
    $resultBuilder->apply($matches);

    $dataSource = $unifiedsearchlib->getDataSource();
    $builder = new Search_Formatter_Builder;
    $builder->setPaginationArguments($paginationArguments);
    $builder->setActions($actions);
    $builder->setId('wplistexecute-' . $iListExecute);
    $builder->setCount($result->count());
    $builder->setTsOn($tsret['tsOn']);
    $builder->apply($matches);

    $result->setTsSettings($builder->getTsSettings());
    $result->setTsOn($tsret['tsOn']);

    $formatter = $builder->getFormatter();
    $formatter->setCounter($iListExecute);

    if (! $customOutput) {
        $plugin = new Search_Formatter_Plugin_SmartyTemplate('templates/wiki-plugins/wikiplugin_listexecute.tpl');
        $plugin->setFields(['report_status' => null]);
        $plugin->setData(
            [
                'actions' => $actions,
                'iListExecute' => $iListExecute
            ]
        );
        $builder->setFormatterPlugin($plugin);
        $formatter = $builder->getFormatter();
        $formatter->setCounter($iListExecute);
    }

    if (isset($_POST['list_action']) && ! empty($selectedObjects)) {
        $action = $_POST['list_action'];

        if ($result->count() > 9999) {
            Feedback::error(tr("There are too many search result items to apply %0 action to.", $_POST['list_action']));
        } elseif (isset($actions[$action])) {
            TikiLib::setExternalContext(true);

            $reportSource = new Search_Action_ReportingTransform;

            $tx = TikiDb::get()->begin();

            $action = $actions[$action];
            $numGood = 0;
            $list = $formatter->getPopulatedList($result);

            foreach ($list as $entry) {
                $identifier = "{$entry['object_type']}:{$entry['object_id']}";
                if (in_array($identifier, $selectedObjects) || in_array('ALL', $selectedObjects)) {
                    if (isset($_POST['list_input'])) {
                        $entry['value'] = $_POST['list_input'];
                    }

                    try {
                        $success = $action->execute($entry);
                        if (! $success) {
                            Feedback::error(tr("Unknown error executing action %0 on item %1.", $_POST['list_action'], $entry['title']));
                        }
                    } catch (Search_Action_Exception $e) {
                        Feedback::error(
                            tr("Error executing action %0 on item %1:", $_POST['list_action'], $entry['title'])
                            . ' ' . $e->getMessage()
                        );
                        $success = false;
                    }

                    if ($success) {
                        $numGood++;
                    }

                    $reportSource->setStatus($entry['object_type'], $entry['object_id'], $success);
                }
            }

            $tx->commit();

            TikiLib::setExternalContext(false);

            if ($numGood) {
                Feedback::success(tr("Action %0 executed successfully on %1 item(s).", $_POST['list_action'], $numGood));
            }

            // need to reload search results in case action has modified the original contents
            // or queried only specific objects
            $result = $searchQuery->search($index);
            $result->setId('wplistexecute-' . $iListExecute);
            $resultBuilder = new Search_ResultSet_WikiBuilder($result);
            $resultBuilder->apply($matches);
            $builder->setCount($result->count());
            // remove any tablesorter header js that will be added twice otherwise
            foreach (TikiLib::lib('header')->jq_onready as &$scripts) {
                foreach ($scripts as $key => $js) {
                    if (strstr($js, '$(\'table#wplistexecute-' . $iListExecute . '\').tablesorter(')) {
                        unset($scripts[$key]);
                    }
                }
            }
            $builder->apply($matches);
            $result->setTsSettings($builder->getTsSettings());
            $result->setTsOn($tsret['tsOn']);
            $formatter = $builder->getFormatter();
            $formatter->setCounter($iListExecute);

            $result->applyTransform($reportSource);
        }
    }

    return $formatter->format($result);
}
