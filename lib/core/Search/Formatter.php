<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter
{
    private $plugin;
    private $counter;
    private $subFormatters = [];
    private $customFilters = [];
    private $alternateOutput;

    public function __construct(Search_Formatter_Plugin_Interface $plugin, $counter = 0)
    {
        $this->plugin = $plugin;
        $this->counter = $counter;
    }

    public function setAlternateOutput($output)
    {
        $this->alternateOutput = $output;
    }

    public function addSubFormatter($name, $formatter)
    {
        $this->subFormatters[$name] = $formatter;
    }

    public function addCustomFilter($filter)
    {
        $this->customFilters[] = $filter;
    }

    public function format($list)
    {
        if (0 == count($list) && $this->alternateOutput) {
            return $this->renderFilters() . $this->alternateOutput;
        }

        $list = $this->getPopulatedList($list);

        return $this->renderFilters()
            . $this->render($this->plugin, $list, Search_Formatter_Plugin_Interface::FORMAT_WIKI);
    }

    public function getPopulatedList($list, $preload = true)
    {
        global $prefs;

        if ($prefs['unified_cache_formatted_result'] === 'y') {
            $cachelib = TikiLib::lib('cache');
            $jsonList = $list->jsonSerialize();
            usort($jsonList['result'], function ($a, $b) {
                if ($a['object_id'] == $b['object_id']) {
                    return 0;
                }

                return ($a['object_id'] < $b['object_id']) ? -1 : 1;
            });
            $cacheKey = json_encode($jsonList) . serialize($this->plugin);
            if ($formattedList = $cachelib->getSerialized($cacheKey, 'searchformat')) {
                return $formattedList;
            }
        }

        $list = Search_ResultSet::create($list);
        $defaultValues = $this->plugin->getFields();

        $fields = array_keys($defaultValues);
        $subDefault = [];
        foreach ($this->subFormatters as $key => $plugin) {
            $subDefault[$key] = $plugin->getFields();
            $fields = array_merge($fields, array_keys($subDefault[$key]));
        }

        $data = [];

        $enableHighlight = in_array('highlight', $fields);
        foreach ($list as $pre) {
            if ($preload) {
                foreach ($fields as $f) {
                    if (isset($pre['ignored_fields']) && in_array($f, $pre['ignored_fields'])) {
                        continue;
                    }
                    if (isset($pre[$f])) {
                        $pre[$f]; // Dynamic loading if applicable
                    }
                }
            }

            $row = array_filter($defaultValues, function ($v) {
                return ($v !== null);	// allow empty default values like "" or 0 (or even false) but not null
            });
            // Clear blank values so the defaults prevail
            foreach ($pre as $k => $value) {
                if ($value !== '' && $value !== null) {
                    $row[$k] = $value;
                }
            }
            if ($enableHighlight) {
                $row['highlight'] = $list->highlight($row);
            }

            $subEntries = [];
            foreach ($this->subFormatters as $key => $plugin) {
                $subInput = new Search_Formatter_ValueFormatter(array_merge($subDefault[$key], $row));
                $subEntries[$key] = $this->render($plugin, Search_ResultSet::create([$plugin->prepareEntry($subInput)]), $this->plugin->getFormat(), $list);
            }

            $row = array_merge($row, $subEntries);

            $data[] = $this->plugin->prepareEntry(new Search_Formatter_ValueFormatter($row));
        }

        $formattedList = $list->replaceEntries($data);

        if ($prefs['unified_cache_formatted_result'] === 'y') {
            $cachelib->cacheItem($cacheKey, serialize($formattedList), 'searchformat');
        }

        return $formattedList;
    }

    public function renderFilters()
    {
        $filters = [];
        foreach ($this->customFilters as $filter) {
            $fieldName = str_replace('tracker_field_', '', $filter['field']);
            $mode = $filter['mode'];
            $filters[] = Tracker\Filter\Collection::getFilter($fieldName, $mode);
        }
        $input = new JitFilter(@$_REQUEST);
        $fields = [];
        foreach ($filters as $filter) {
            if (! $filter->getControl()->isUsable()) {
                continue;
            }
            $filter->applyInput($input);
            $field = [
                'id' => $filter->getControl()->getId(),
                'name' => $filter->getLabel(),
                'renderedInput' => $filter->getControl(),
            ];
            if (preg_match("/<input.*type=['\"](text|search)['\"]/", $field['renderedInput'])) {
                $field['textInput'] = true;
            }
            $fields[] = $field;
        }

        $url = parse_url(@$_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $filters = [];
        foreach ($_GET as $key => $val) {
            if (substr($key, 0, 3) != 'tf_') {
                $filters[$key] = $val;
            }
        }
        $url .= '?' . http_build_query($filters);

        if ($fields) {
            $smarty = TikiLib::lib('smarty');
            $smarty->assign('filterFields', $fields);
            $smarty->assign('filterCounter', $this->counter);
            $smarty->assign('filterUrl', $url);

            return '~np~' . $smarty->fetch('templates/search/list/filter.tpl') . '~/np~';
        }

        return '';
    }

    public function getCounter()
    {
        return $this->counter;
    }

    public function setCounter($cnt)
    {
        $this->counter = $cnt;
    }

    private function render($plugin, $resultSet, $target)
    {
        $pluginFormat = $plugin->getFormat();
        $rawOutput = $plugin->renderEntries($resultSet);

        if ($target == $pluginFormat || $pluginFormat == Search_Formatter_Plugin_Interface::FORMAT_CSV) {
            $out = $rawOutput;
        } elseif ($target == Search_Formatter_Plugin_Interface::FORMAT_WIKI && $pluginFormat == Search_Formatter_Plugin_Interface::FORMAT_HTML) {
            $out = "~np~$rawOutput~/np~";
        } elseif ($target == Search_Formatter_Plugin_Interface::FORMAT_HTML && $pluginFormat == Search_Formatter_Plugin_Interface::FORMAT_WIKI) {
            $out = "~/np~$rawOutput~np~";
        } elseif ($target == Search_Formatter_Plugin_Interface::FORMAT_CSV) {
            $out = strip_tags(Tikilib::lib('parser')->parse_data($rawOutput, ['is_html' => true]));
        }

        $out = str_replace(['~np~~/np~', '~/np~~np~'], '', $out);

        return $out;
    }
}
