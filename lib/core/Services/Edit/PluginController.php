<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class Services_Edit_PluginController
 *
 * Controller for editing and listing wiki plugins
 *
 */
class Services_Edit_PluginController
{
    private $pluginList;

    public function __construct()
    {
        $this->pluginList = [];
    }

    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki');

        $this->pluginList = TikiLib::lib('wiki')->list_plugins(true, 'editwiki', false);
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'wiki page';
    }

    /**
     * List all or some of the plugins for the textarea control panel
     *
     * @param JitFilter $input
     * @return array
     */
    public function action_list($input)
    {
        $filter = $input->filter->text();
        $title = $input->title->text();
        $res = [];

        if ($filter) {
            $query = 'wikiplugin_* AND ' . $filter;
            $sort = '';
        } else {
            $query = 'wikiplugin_*';
            $sort = 'object_id_asc';
        }
        $filters = TikiLib::lib('prefs')->getEnabledFilters();
        $results = TikiLib::lib('prefs')->getMatchingPreferences($query, $filters, 500, $sort);

        foreach ($results as $result) {
            if (strpos($result, 'wikiplugin_') === 0) {
                $key = strtoupper(substr($result, 11));
                $arr = array_filter($this->pluginList, function ($plugin) use ($key) {
                    return $plugin['name'] === $key;
                });

                foreach ($this->pluginList as $plugin) {
                    if ($plugin['name'] === $key) {
                        $res[strtolower($key)] = array_shift($arr);

                        break;
                    }
                }
            }
        }

        if (! $title) {
            if ($res) {
                $title = tr('Plugins found containing: %0', $filter);
            } else {
                $title = tr('No plugins found containing: %0', $filter);
            }
        }

        return [
            'plugins' => $res,
            'title' => $title,
            'pref_filters' => $filters,
        ];
    }

    /**
     * Display plugin edit form or process saving changes
     *
     * @param JitFilter $input
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_EditConflict
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_edit($input)
    {
        global $prefs;

        $parserlib = TikiLib::lib('parser');

        $area_id = $input->area_id->alnumdash();
        $type = $input->type->word();
        $index = $input->index->int();
        $page = $input->page->pagename();
        $pluginArgs = $input->asArray('pluginArgs');
        $bodyContent = $input->bodyContent->wikicontent();
        $edit_icon = $input->edit_icon->int();
        $selectedMod = $input->selectedMod->text();

        $tikilib = TikiLib::lib('tiki');
        $pageInfo = $tikilib->get_page_info($page);
        if (! $pageInfo) {
            // in edit mode
        } else {
            $perms = $tikilib->get_perm_object($page, 'wiki page', $pageInfo, false);
            if ($perms['tiki_p_edit'] !== 'y') {
                throw new Services_Exception_Denied(tr('You do not have permission to edit "%0"', $page));
            }
        }

        Services_Exception_EditConflict::checkSemaphore($page);

        if ($edit_icon) {
            TikiLib::lib('service')->internal('semaphore', 'set', ['object_id' => $page]);
        }

        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $this->action_replace($input);

            TikiLib::lib('service')->internal('semaphore', 'unset', ['object_id' => $page]);

            return [
                'redirect' => TikiLib::lib('wiki')->sefurl($page),
            ];
        }          // render the form
        $info = $parserlib->plugin_info($type, $pluginArgs);
        $info['advancedParams'] = [];
        $validationRules = [];
        $objectlib = TikiLib::lib('object');

        foreach ($info['params'] as $key => & $param) {
            if ($prefs['feature_jquery_validation'] === 'y') {
                // $("#insertItemForm4").validate({rules: { ins_11: { required: true}, ins_13: { remote: { url: "validate-ajax.php", type: "post", data: { validator: "distinct", parameter: "trackerId=4&fieldId=13&itemId=0", message: "", input: function() { return $("#ins_13").val(); } } } }, ins_18: { required: true, remote: { url: "validate-ajax.php", type: "post", data: { validator: "distinct", parameter: "trackerId=4&fieldId=18&itemId=0", message: "this is not distinct!", input: function() { return $("#ins_18").val(); } } } }}, messages: { ins_11: { required: "This field is required" }, ins_18: { required: "this is not distinct!" }},
                if ($param['required']) {
                    if (empty($param['parentparam'])) {
                        $validationRules["params[$key]"] = ['required' => true];
                    } else {
                        $validationRules["params[$key]"] = ['required_in_group' => [
                                1,
                                '.group-' . $param['parentparam']['name'],
                                'other',
                            ]];
                    }
                }
            }
            if (! empty($param['advanced']) && ! isset($pluginArgs[$key]) && empty($param['parentparam'])) {
                $info['advancedParams'][$key] = $param;
                unset($info['params'][$key]);
            }
            // set up object selectors - TODO refactor code with \PreferencesLib::getPreference and \Services_Tracker_Controller::action_edit_field
            if (isset($param['profile_reference'])) {
                $param['selector_type'] = $objectlib->getSelectorType($param['profile_reference']);
                if (isset($param['parent'])) {
                    if (! preg_match('/[\[\]#\.]/', $param['parent'])) {
                        $param['parent'] = "#option-{$param['parent']}";
                    }
                } else {
                    $param['parent'] = null;
                }
                $param['parentkey'] = isset($param['parentkey']) ? $param['parentkey'] : null;
                $param['sort_order'] = isset($param['sort_order']) ? $param['sort_order'] : null;
            } else {
                $param['selector_type'] = null;
            }
            if (isset($param['profile_reference_extra_values'])) {
                $param['selector_type_reference'] = $param['selector_type'];
                $param['selector_type'] = 'extra';
            }
        }

        $extraParams = array_filter(array_diff_key($pluginArgs, $info['params']));

        foreach ($extraParams as $extraParam => $val) {
            $info['params'][$extraParam] = [
                    'required' => false,
                    'name' => $extraParam,
                    'description' => tr('Undefined parameter'),
                    'filter' => 'text',
                ];
        }

        if ($validationRules) {
            $rules = json_encode([
                    'rules' => $validationRules,
                    'errorClass' => 'invalid-feedback',
                    'highlight' => 'hiFunction',
                    'unhighlight' => 'unFunction',
                ]);

            // add the highlight and unhighlight functions back in, not as strings
            $rules = str_replace(
                ['"hiFunction"', '"unFunction"'],
                ['function(element) {$(element).addClass(\'is-invalid\');}', 'function(element) {$(element).removeClass(\'is-invalid\');}'],
                $rules
            );
            TikiLib::lib('header')->add_jq_onready('$("#plugin_params > form").validate(' . $rules . ');');
        }

        if ($type === 'module' && (isset($pluginArgs['module']) || $selectedMod)) {
            if ($selectedMod) {
                $pluginArgs['module'] = $selectedMod;
            }
            $file = 'modules/mod-func-' . $pluginArgs['module'] . '.php';
            if (file_exists($file)) {
                include_once($file);
                $info_func = "module_{$pluginArgs['module']}_info";
                if (function_exists($info_func)) {
                    $moduleInfo = $info_func();
                    if (isset($info['params']['max'])) {
                        $max = $info['params']['max'];
                        unset($info['params']['max']);    // move "max" to last
                    }
                    foreach ($moduleInfo['params'] as $key => $value) {
                        $info['params'][$key] = $value;
                    }
                    if (! empty($max)) {
                        $info['params']['max'] = $max;
                    }
                    // replace the module plugin description with the one from the select module
                    $info['params']['module']['description'] = $moduleInfo['description'];
                }
            }
        }

        return [
                // pass back the input parameters
                'area_id' => $area_id,
                'type' => $type,
                'index' => $index,
                'pageName' => $page,
                'pluginArgs' => $pluginArgs,
                'pluginArgsJSON' => json_encode($pluginArgs),
                'bodyContent' => $bodyContent,
                'edit_icon' => $edit_icon,
                'selectedMod' => $selectedMod,

                'info' => $info,
                'title' => $info['name'],
            ];
    }

    /**
     * Replace plugin in wiki content
     * Migrated from tiki-wikiplugin_edit.php
     *
     * FIXME: No verification that the replaced call was not changed during edition. Should probably check a fingerprint of the plugin call.
     *
     * @param JitFilter $input
     * @throws Exception
     * @throws Services_Exception
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_replace($input)
    {
        global $user;

        $tikilib = TikiLib::lib('tiki');

        $page = $input->page->pagename();
        $type = $input->type->word();
        $message = $input->message->text();
        $content = $input->content->wikicontent();
        $index = $input->index->int();
        $params = $input->asArray('params');

        $referer = $_SERVER['HTTP_REFERER'];

        if (! $page || ! $type || ! $referer) {
            throw new Services_Exception(tr('Missing parameters'));
        }

        $plugin = strtolower($type);

        if (! $message) {
            $message = tr('%0 Plugin modified by editor.', $plugin);
        }

        $info = $tikilib->get_page_info($page);
        if (! $info) {
            throw new Services_Exception_NotFound(tr('Page "%0" not found', $page));
        }

        $perms = $tikilib->get_perm_object($page, 'wiki page', $info, false);
        if ($perms['tiki_p_edit'] !== 'y') {
            throw new Services_Exception_Denied(tr('You do not have permission to edit "%0"', $page));
        }

        $current = $info['data'];

        $matches = WikiParser_PluginMatcher::match($current);
        $count = 0;
        $util = new Services_Utilities();
        foreach ($matches as $match) {
            if ($match->getName() !== $plugin) {
                continue;
            }

            ++$count;

            if ($index === $count && $util->checkCsrf()) {
                // by using content of "~same~", it will not replace the body that is there
                if ($content == "~same~") {
                    $content = $match->getBody();
                }

                if (! $params) {
                    $params = $match->getArguments();
                }

                $match->replaceWithPlugin($plugin, $params, $content);

                $tikilib->update_page(
                    $page,
                    $matches->getText(),
                    $message,
                    $user,
                    $tikilib->get_ip_address()
                );
                Feedback::success($message);

                return [];
            }
        }

        throw new Exception('Plugin edit failed');
    }

    /**
     * Convert a trackerlist plugin to list
     *
     * @param JitFilter $input
     * @throws Services_Exception
     * @throws Services_Exception_BadRequest
     * @throws Services_Exception_Denied
     * @return array
     */
    public function action_convert_trackerlist($input)
    {
        global $user;

        Services_Exception_Disabled::check('feature_trackers');
        Services_Exception_Disabled::check('wikiplugin_list');
        Services_Exception_Disabled::check('wikiplugin_trackerlist');
        Services_Exception_Disabled::check('wikiplugin_list_convert_trackerlist');

        $tikilib = TikiLib::lib('tiki');

        $page = $input->page->pagename();
        $type = $input->type->word();
        $message = $input->message->text();
        $content = $input->content->wikicontent();
        $index = $input->index->int();
        $params = $input->asArray('params');

        $referer = $_SERVER['HTTP_REFERER'];
        $util = new Services_Utilities();

        if (! $page || ! $type || ! $referer) {
            throw new Services_Exception(tr('Missing parameters'));
        }

        $plugin = strtolower($type);

        $info = $tikilib->get_page_info($page);
        if (! $info) {
            throw new Services_Exception_BadRequest(tr('Page "%0" not found', $page));
        }

        $perms = $tikilib->get_perm_object($page, 'wiki page', $info, false);
        if ($perms['tiki_p_edit'] !== 'y') {
            throw new Services_Exception_Denied(tr('You do not have permission to edit "%0"', $page));
        }

        if ($util->checkCsrf()) {
            $current = $info['data'];

            if (! $message) {
                $message = tr('%0 Plugin converted to list.', $plugin);
            }
            $matches = WikiParser_PluginMatcher::match($current);
            $count = 0;
            foreach ($matches as $match) {
                if ($match->getName() !== $plugin) {
                    continue;
                }

                ++$count;

                if ($index === $count) {
                    if (! $params) {
                        $params = $match->getArguments();
                    }

                    $converter = new Services_Edit_ListConverter('trackerlist');

                    $content = $converter->convert($params, $content);

                    $match->replaceWithPlugin('list', [], $content);

                    $text = $matches->getText();
                    $text .= $converter->getErrorsComment();
                    $text .= $converter->getAdditionalComments('wiki');

                    $tikilib->update_page(
                        $page,
                        $text,
                        $message,
                        $user,
                        $tikilib->get_ip_address()
                    );

                    Feedback::success(tr('Plugin %0 on page %1 converted.', $plugin, $page));

                    return [];
                }
            }

            throw new Exception('Plugin convert failed');
        }
    }

    /**
     * Create the data for the list plugin GUI
     *
     * @param JitFilter $input
     * @throws Exception
     * @return array
     */
    public function action_list_edit($input)
    {
        global $prefs;

        $body = $input->body->wikicontent();
        $current = [];
        $done = [];    // to keep a track on whcih plugins have already been included
        $plugins = Services_Edit_ListPluginHelper::getDefinition();

        $this->parsePlugins($body, $current, $done, null, $this->getAllowedPlugins($plugins));


        $fields = TikiLib::lib('unifiedsearch')->getAvailableFields();

        $trackers = [];
        if ($prefs['feature_trackers'] === 'y') {
            $trklib = TikiLib::lib('trk');

            $trackersData = $trklib->list_trackers();

            foreach ($trackersData['data'] as $trackerInfo) {
                $trackerId = $trackerInfo['trackerId'];
                $trackers[$trackerId] = [];
                $definition = Tracker_Definition::get($trackerId);

                foreach ($definition->getFields() as $fieldObject) {
                    $trackers[$trackerId][] = 'tracker_field_' . $fieldObject['permName'];
                }
            }
        }

        // generic fields missing from the content sources?
        $fields['global'] = array_merge([
            'object_id',
            'object_type',
            'title',
            'language',
            'creation_date',
            'modification_date',
            'contributors',
            'description',
            'contents',
        ], $fields['global']);

        sort($fields['global']);

        return [
            'plugins' => $plugins,
            'fields' => $fields,
            'current' => $current,
            'trackers' => $trackers,
        ];
    }

    /**
     * Recursively convert plugins to nested array
     *
     * @param string $body wiki content to "parse"
     * @param array $plugins resulting nested array of plugins
     * @param array $done flat array to track plugins already added to $plugins
     * @param array $parent
     * @param array $allowedPlugins names of plugins to include (sub plugins of {list}
     */
    private function parsePlugins($body, & $plugins, & $done, $parent = null, $allowedPlugins)
    {
        $matches = WikiParser_PluginMatcher::match($body);
        $argumentParser = new WikiParser_PluginArgumentParser;
        $lastMatchEnd = 0;
        $hasParent = $parent && in_array(strtolower($parent['name']), ['output', 'format']);

        /** @var WikiParser_PluginMatcher_Match $match */
        foreach ($matches as $match) {
            $name = $match->getName();

            $matchArray = [
                'name' => $match->getName(),
                'start' => $match->getStart(),
                'bodystart' => $match->getBodyStart(),
                'end' => $match->getEnd(),
                'args' => $match->getArguments(),
            ];

            if ($parent) {    // add in the parent body offset
                $matchArray['start'] += $parent['bodystart'];
                $matchArray['end'] += $parent['bodystart'];
                if ($matchArray['bodystart']) {
                    $matchArray['bodystart'] += $parent['bodystart'];
                }
            }

            if (in_array($name, $allowedPlugins) && ! in_array($matchArray, $done)) {
                $thisBody = $match->getBody();

                $thisPlugin = [
                    'name' => $name,
                    'params' => $argumentParser->parse($match->getArguments()),
                    'body' => $thisBody,
                    'plugins' => [],
                ];

                if ($thisBody && in_array(strtolower($name), ['output', 'format'])) {
                    $this->parsePlugins($thisBody, $thisPlugin['plugins'], $done, $matchArray, $allowedPlugins);
                }

                if (! in_array($matchArray, $done)) {
                    if ($hasParent) {
                        $plugins[] = substr($body, $lastMatchEnd, $match->getStart() - $lastMatchEnd);	// possibly wiki text
                        $lastMatchEnd = $match->getEnd();
                    }

                    $plugins[] = $thisPlugin;
                }
            } else {
                // other plugins in output or format body blocks, treat as wiki text
                if ($hasParent && ! in_array($matchArray, $done)) {
                    $wikiText = $match->getBody();

                    // find any nested plugins and mark them as done
                    if ($wikiText) {
                        $newPlugins = [];
                        $this->parsePlugins($wikiText, $newPlugins, $done, $matchArray, $allowedPlugins);

                        $plugins[] = substr($body, $lastMatchEnd, $match->getEnd() - $lastMatchEnd);
                        $lastMatchEnd = $match->getEnd();
                    }
                }
            }
            $done[] = $matchArray;
        }

        if ($hasParent) {
            $plugins[] = substr($body, $lastMatchEnd);
        }

        for ($i = 0; $i < count($plugins); $i++) {	// join wiki text parts together
            if ($i > 0 && is_string($plugins[$i]) && is_string($plugins[$i - 1])) {
                $plugins[$i - 1] .= $plugins[$i];
                $plugins[$i] = false;
            }
        }

        $plugins = array_values(array_filter($plugins));
    }

    /**
     * find all the plugins (commands) within plugin list
     *
     * @param $plugins array    from \Services_Edit_ListPluginHelper::getDefinition
     * @return array            flat array of names of plugins
     */
    private function getAllowedPlugins($plugins)
    {
        $pluginNames = array_keys($plugins);

        foreach ($plugins as $plugin) {
            if (is_array($plugin['params'])) {
                foreach ($plugin['params'] as $param) {
                    if (is_array($param['options'])) {
                        foreach ($param['options'] as $option) {
                            if (is_array($option['plugins'])) {
                                $pluginNames = array_merge($pluginNames, $this->getAllowedPlugins($option['plugins']));
                            }
                        }
                    }
                }
            }
        }

        return $pluginNames;
    }
}
