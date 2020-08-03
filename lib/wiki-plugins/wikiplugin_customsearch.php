<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_customsearch_info()
{
    return [
        'name' => tra('Custom Search'),
        'documentation' => 'PluginCustomSearch',
        'description' => tra('Create a custom search form for searching or listing items on the site'),
        'prefs' => ['wikiplugin_customsearch', 'wikiplugin_list', 'feature_search'],
        'body' => tra('LIST plugin configuration information'),
        'filter' => 'wikicontent',
        'profile_reference' => 'search_plugin_content',
        'iconname' => 'search',
        'introduced' => 8,
        'tags' => ['advanced'],
        'params' => [
            'wiki' => [
                'required' => false,
                'name' => tra('Template wiki page'),
                'description' => tra('Wiki page where search user interface template is found'),
                'since' => '8.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'tpl' => [
                'required' => false,
                'name' => tra('Template file'),
                'description' => tra('Smarty template (.tpl) file where search user interface template is found'),
                'since' => '12.2',
                'default' => '',
            ],
            'id' => [
                'required' => false,
                'name' => tra('Search Id'),
                'description' => tra('A unique identifier to distinguish custom searches for storing of previous search
					criteria entered by users'),
                'since' => '8.0',
                'filter' => 'alnum',
                'default' => 0,
            ],
            'autosearchdelay' => [
                'required' => false,
                'name' => tra('Search Delay'),
                'description' => tr('Delay in milliseconds before automatically triggering search after change
					(%00%1 disables and is the default)', '<code>', '</code>'),
                'since' => '8.0',
                'filter' => 'digits',
                'default' => 0,
            ],
            'searchfadediv' => [
                'required' => false,
                'name' => tra('Fade DIV Id'),
                'description' => tra('The specific ID of the specific div to fade out when AJAX search is in progress,
					if not set will attempt to fade the whole area or if failing simply show the spinner'),
                'since' => '8.0',
                'filter' => 'text',
                'default' => '',
            ],
            'recalllastsearch' => [
                'required' => false,
                'name' => tra('Recall Last Search'),
                'description' => tra('In the same session, return users to same search parameters on coming back to the
					search page after leaving'),
                'since' => '8.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 0,
            ],
            'callbackscript' => [
                'required' => false,
                'name' => tra('Custom JavaScript Page'),
                'description' => tra('The wiki page on which custom JavaScript is to be executed on return of Ajax results'),
                'since' => '8.0',
                'filter' => 'pagename',
                'default' => '',
            ],
            'destdiv' => [
                'required' => false,
                'name' => tra('Destination Div'),
                'description' => tra('The id of an existing div to contain the search results'),
                'since' => '9.0',
                'filter' => 'text',
                'default' => '',
            ],
            'searchonload' => [
                'required' => false,
                'name' => tra('Search On Load'),
                'description' => tra('Execute the search when the page loads (default: Yes)'),
                'since' => '9.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 1,
            ],
            'requireinput' => [
                'required' => false,
                'name' => tra('Require Input'),
                'description' => tra('Require first input field to be filled for search to trigger'),
                'since' => '12.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 0,
            ],
            'forcesortmode' => [
                'required' => false,
                'name' => tra('Force Sort'),
                'description' => tra('Force the use of specified sort mode in place of search relevance even when there is a text search query'),
                'since' => '13.0',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 1,
            ],
            'trimlinefeeds' => [
                'required' => false,
                'name' => tra('Trim Linefeeds'),
                'description' => tra('Remove the linefeeds added after each input which casues the wiki parser to add extra paragraphs.'),
                'since' => '14.1',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 0,
            ],
            'searchable_only' => [
                'required' => false,
                'name' => tra('Searchable Only Results'),
                'description' => tra('Only include results marked as searchable in the index.'),
                'since' => '14.1',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 1,
            ],
            'customsearchjs' => [
                'required' => false,
                'name' => tra('Use custom search JavaScript file'),
                'description' => tra('Mainly keeps the search state on the URL hash, but also adds some helper functions like easier sorting and page size.'),
                'since' => '14.1',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'filter' => 'digits',
                'default' => 0,
            ],
        ],
    ];
}

function wikiplugin_customsearch($data, $params)
{
    global $prefs;

    if ($prefs['javascript_enabled'] !== 'y') {
        require_once('lib/wiki-plugins/wikiplugin_list.php');
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_block_remarksbox');
        $repeat = false;

        $out = smarty_block_remarksbox(
            [
                'type' => 'warning',
                'title' => tr('JavaScript disabled'),
            ],
            tr('JavaScript is required for this search feature'),
            $smarty,
            $repeat
        );

        return '~np~' . $out . '~/np~' . wikiplugin_list($data, []);
    }

    static $instance_id = null;

    if (empty($params['wiki']) && empty($params['tpl'])) {
        return tra('Template is not specified');
    } elseif (! empty($params['wiki']) && ! TikiLib::lib('tiki')->page_exists($params['wiki'])) {
        $link = new WikiParser_OutputLink;
        $link->setIdentifier($params['wiki']);

        return tra('Template page not found') . ' ' . $link->getHtml();
    }

    if (isset($params['id'])) {
        $id = TikiLib::remove_non_word_characters_and_accents($params['id']);
    } else {
        if ($instance_id === null) {
            $instance_id = 0;
        } else {
            $instance_id++;
        }
        $id = (string) $instance_id;
    }
    if (isset($params['recalllastsearch']) && $params['recalllastsearch'] == 1 && (! isset($_REQUEST['forgetlastsearch']) || $_REQUEST['forgetlastsearch'] != 'y')) {
        $recalllastsearch = 1;
    } else {
        $recalllastsearch = 0;
    }

    $defaults = [];
    $plugininfo = wikiplugin_customsearch_info();
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults["$key"] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    if (! isset($_REQUEST["offset"])) {
        $offset = 0;
    } else {
        $offset = (int) $_REQUEST["offset"];
    }
    if (isset($_REQUEST['maxRecords'])) {
        $maxRecords = (int) $_REQUEST['maxRecords'];
    } elseif ($recalllastsearch && ! empty($_SESSION["customsearch_$id"]['maxRecords'])) {
        $maxRecords = (int) $_SESSION["customsearch_$id"]['maxRecords'];
    } else {
        $maxRecords = (int) $prefs['maxRecords'];
        $maxDefault = true;
    }
    if (! empty($_REQUEST['sort_mode'])) {
        $sort_mode = $_REQUEST['sort_mode'];
    } elseif ($recalllastsearch && ! empty($_SESSION["customsearch_$id"]['sort_mode'])) {
        $sort_mode = $_SESSION["customsearch_$id"]['sort_mode'];
    } else {
        $sort_mode = '';
    }

    $definitionKey = md5($data);
    $matches = WikiParser_PluginMatcher::match($data);
    $query = new Search_Query;
    if (! isset($params['searchable_only']) || $params['searchable_only'] == 1) {
        $query->filterIdentifier('y', 'searchable');
    }
    $builder = new Search_Query_WikiBuilder($query);
    $builder->apply($matches);
    $tsret = $builder->applyTablesorter($matches);
    if (! empty($tsret['max']) || ! empty($_GET['numrows'])) {
        $max = ! empty($_GET['numrows']) ? $_GET['numrows'] : $tsret['max'];
        $builder->wpquery_pagination_max($query, $max);
    }
    $paginationArguments = $builder->getPaginationArguments();

    // Use maxRecords set in LIST parameters rather then global default if set.
    if (isset($maxDefault) && $maxDefault) {
        if (! empty($paginationArguments['max'])) {
            $maxRecords = $paginationArguments['max'];
        }
    }

    // setup AJAX pagination
    $paginationArguments['offset_jsvar'] = "customsearch_$id.offset";
    $paginationArguments['sort_jsvar'] = "customsearch_$id.sort_mode";
    $paginationArguments['_onclick'] = "$('#customsearch_$id').submit();return false;";

    $builder = new Search_Formatter_Builder;
    $builder->setId('wpcs-' . $id);
    $builder->setPaginationArguments($paginationArguments);
    $builder->setTsOn($tsret['tsOn']);

    $facets = new Search_Query_FacetWikiBuilder;
    $facets->apply($matches);

    $cachelib = TikiLib::lib('cache');
    $cachelib->cacheItem(
        $definitionKey,
        serialize(
            [
                'query' => $query,
                'data' => $data,
                'builder' => $builder,
                'facets' => $facets,
                'tsret' => $tsret,
            ]
        ),
        'customsearch'
    );

    if (! empty($params['wiki'])) {
        $wikitpl = "tplwiki:" . $params['wiki'];
    } else {
        $wikitpl = $params['tpl'];
    }
    $wikicontent = TikiLib::lib('smarty')->fetch($wikitpl);
    TikiLib::lib('parser')->parse_wiki_argvariable($wikicontent);

    $matches = WikiParser_PluginMatcher::match($wikicontent);

    $fingerprint = md5($wikicontent);

    $sessionprint = "customsearch_{$id}_$fingerprint";
    if (isset($_SESSION[$sessionprint]) && $_SESSION[$sessionprint] != $fingerprint) {
        unset($_SESSION["customsearch_$id"]);
    }
    $_SESSION[$sessionprint] = $fingerprint;

    // important that offset from session is set after fingerprint check otherwise blank page might show
    if ($recalllastsearch && ! isset($_REQUEST['offset']) && ! empty($_SESSION["customsearch_$id"]["offset"])) {
        $offset = (int) $_SESSION["customsearch_$id"]["offset"];
    }

    $options = [
        'searchfadetext' => tr('Loading...'),
        'searchfadediv' => $params['searchfadediv'],
        'results' => empty($params['destdiv']) ? "#customsearch_{$id}_results" : "#{$params['destdiv']}",
        'autosearchdelay' => ! empty($params['autosearchdelay']) ? max(1500, (int) $params['autosearchdelay']) : 0,
        'searchonload' => (int) $params['searchonload'],
        'requireinput' => (bool) $params['requireinput'],
        'origrequireinput' => (bool) $params['requireinput'],
        'forcesortmode' => (bool) $params['forcesortmode'],
    ];

    /**
     * NOTES: Search Execution
     *
     * There is a global delay on execution of 1 second. This makes sure
     * multiple submissions will never trigger multiple requests.
     *
     * There is an additional autosearchdelay configuration that can trigger the search
     * on field change rather than explicit request. Explicit requests will still work.
     */
    $script = "
var customsearch$id = {
	options: " . json_encode($options) . ",
	id: " . json_encode($id) . ",
	offset: 0,
	searchdata: {},
	definition: " . json_encode((string) $definitionKey) . ",
	autoTimeout: null,
	add: function (fieldId, filter) {
		this.searchdata[fieldId] = filter;
		this.auto();
	},
	remove: function (fieldId) {
		delete this.searchdata[fieldId];
		this.auto();
	},
	load: function () {
		this._executor(this);
	},
	auto: function () {
	},
	_executor: delayedExecutor(1000, function (cs) {
		var selector = '#' + cs.options.searchfadediv;
		if (cs.options.searchfadediv.length <= 1 && $(selector).length === 0) {
			selector = '#customsearch_' + cs.id;
		}

		$(selector).tikiModal(cs.options.searchfadetext);

		if ($(cs.options.results).length) {
			var resultsTop = $(cs.options.results).offset().top;
			if( resultsTop && $(window).scrollTop() > resultsTop ) {
				$('html, body').animate({scrollTop: resultsTop + 'px'}, 'fast');
			}
		}
		cs._load(function (data) {
			$(selector).tikiModal();
			$(cs.options.results).html(data);
			$(document).trigger('pageSearchReady');
		});
		cs.store_query = '';
	}),
	init: function () {
		var that = this;
		if (that.options.searchonload) {
			that.load();
		}

		if (that.options.autosearchdelay) {
			that.auto = delayedExecutor(that.options.autosearchdelay, function () {
				if (that.options.requireinput && (!$('#customsearch_$id').find(':text').val() || $('#customsearch_$id').find(':text').val().indexOf('...') > 0)) {
					return false;
				}
				that.load();
			});
		}
	}
};
$('#customsearch_$id').click(function() {
	customsearch$id.offset = 0;
});
$('#customsearch_$id').submit(function() {
	if (customsearch$id.options.requireinput && (!$(this).find(':text').val() || $(this).find(':text').val().indexOf('...') > 0)) {
		alert(tr('Please enter a search query'));
		return false;
	}
	if (customsearch$id.options.origrequireinput != customsearch$id.options.requireinput) {
		customsearch$id.options.requireinput = customsearch$id.options.origrequireinput;
	}
	customsearch$id.load();
	return false;
});

window.customsearch_$id = customsearch$id;
";

    $parser = new WikiParser_PluginArgumentParser;
    $dr = 0;
    foreach ($matches as $match) {
        $name = $match->getName();
        $arguments = $parser->parse($match->getArguments());
        $key = $match->getInitialStart();
        $fieldid = "customsearch_{$id}_$key";
        if (isset($arguments['id'])) {
            $fieldid = $arguments['id'];
        }
        if ($name == 'sort' && ! empty($arguments['mode']) && empty($sort_mode)) {
            $sort_mode = $arguments['mode'];
            $match->replaceWith('');

            continue;
        }
        if (! empty($arguments['_field']) && ! empty($arguments['_filter']) && $arguments['_filter'] == 'content') {
            $filter = $arguments['_field'];
        } elseif (empty($arguments['_field']) && ! empty($arguments['_filter']) && $arguments['_filter'] == 'content') {
            $filter = 'content';
        } else {
            $filter = '';
        }
        if ($filter && ! empty($_REQUEST['default'][$filter])) {
            $default = $_REQUEST['default'][$filter];
        } elseif ($recalllastsearch && isset($_SESSION["customsearch_$id"][$fieldid])) {
            $default = $_SESSION["customsearch_$id"][$fieldid];
        } elseif (! empty($arguments['_default'])) {
            if (strpos($arguments['_default'], ',') !== false) {
                $default = explode(',', $arguments['_default']);
            } else {
                $default = $arguments['_default'];
            }
        } else {
            $default = '';
        }
        if ($name == 'categories') {
            $parent = $arguments['_parent'];
            if (! empty($_REQUEST['defaultcat'][$parent])) {
                $default = $_REQUEST['defaultcat'][$parent];
            }
        }
        $function = "cs_design_{$name}";
        if (function_exists($function)) {
            if (isset($arguments['_group'])) {
                $fieldname = "customsearch_{$id}_gr" . $arguments['_group'];
            } elseif (isset($arguments['_textrange'])) {
                $fieldname = "customsearch_{$id}_textrange" . $arguments['_textrange'];
            } elseif (isset($arguments['_daterange'])) {
                $fieldname = "customsearch_{$id}_daterange" . $arguments['_daterange'];
            } else {
                $fieldname = $fieldid;
            }
            $html = $function($id, $fieldname, $fieldid, $arguments, $default, $script);
            if ($params['trimlinefeeds']) {
                $html = trim($html);
            }
            $match->replaceWith($html);
        }
        if ($name == 'daterange') {
            $dr++;
        }
    }

    $callbackScript = null;
    if (! empty($params['callbackscript']) && TikiLib::lib('tiki')->page_exists($params['callbackscript'])) {
        $callbackscript_tpl = "wiki:" . $params['callbackscript'];
        $callbackScript = TikiLib::lib('smarty')->fetch($callbackscript_tpl);
    }
    //get iconset icon if daterange is one of the fields
    if ($dr) {
        $smarty = TikiLib::lib('smarty');
        $smarty->loadPlugin('smarty_function_js_insert_icon');
        $iconinsert = smarty_function_js_insert_icon(['type' => 'jscalendar', 'return' => 'y'], $smarty->getEmptyInternalTemplate());
    } else {
        $iconinsert = '';
    }

    global $page;
    $script .= "$('.icon-pdf').parent().click(function(){storeSortTable('#customsearch_" . $id . "_results',$('#customsearch_" . $id . "_results').html())});
customsearch$id._load = function (receive) {
	var datamap = {
		definition: this.definition,
		adddata: $.toJSON(this.searchdata),
		searchid: this.id,
		offset: customsearch$id.offset,
		maxRecords: this.maxRecords,
		store_query: this.store_query,
		page: " . json_encode($page) . ",
		recalllastsearch: $recalllastsearch
	};
	if (!customsearch$id.options.forcesortmode && $('#customsearch_$id').find(':text').val() && $('#customsearch_$id').find(':text').val().indexOf('...') <= 0) {
		customsearch$id.sort_mode = 'score_desc';
	}
	if (customsearch$id.sort_mode) {
		// blank sort_mode is not allowed by Tiki input filter
		datamap.sort_mode = customsearch$id.sort_mode;
	}
	$.ajax({
		type: 'POST',
		url: $.service('search_customsearch', 'customsearch'),
		data: datamap,
		dataType: 'html',
		success: function(data) {
			receive(data);
			$('[data-toggle=\'popover\']').attr('data-html', true);
			$('[data-toggle=\'popover\']').popover();
			$callbackScript;
		},
		error: function ( jqXHR, textStatus, errorThrown ) {
			var selector = '#' + customsearch$id.options.searchfadediv;
			if (customsearch$id.options.searchfadediv.length <= 1 && $(selector).length === 0) {
				selector = '#customsearch_$id';
			}
			$(selector).tikiModal();

			$('#customsearch_$id').showError(jqXHR)
		}
	});
};
customsearch$id.sort_mode = " . json_encode($sort_mode) . ";
customsearch$id.offset = $offset;
customsearch$id.maxRecords = $maxRecords;
customsearch$id.store_query ='';
customsearch$id.init();
$iconinsert;
$(document).trigger('formSearchReady');
";

    TikiLib::lib('header')->add_jq_onready($script);

    if ($params['customsearchjs']) {
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/customsearch.js');
    }

    $out = '<div id="customsearch_' . $id . '_form"><form id="customsearch_' . $id . '">' . $matches->getText() . '</form></div>';

    if (empty($params['destdiv'])) {
        $out .= '<div id="customsearch_' . $id . '_results" class="customsearch_results"></div>';
    }

    if (! empty($params['wiki'])) {
        return $out;
    }
    // If using smarty tpl should assume it's all HTML
    $out = str_replace('~np~', '', $out);
    $out = str_replace('~/np~', '', $out);

    return '~np~' . $out . '~/np~';
}

function cs_design_setbasic($element, $fieldid, $fieldname, $arguments)
{
    $element->setAttribute('id', $fieldid);
    $element->setAttribute('name', $fieldname);
    foreach ($arguments as $k => $v) {
        if (substr($k, 0, 1) != '_') {
            $element->setAttribute($k, $v);
        }
    }
}

function cs_design_input($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    $document = new DOMDocument;
    $element = $document->createElement('input');
    cs_design_setbasic($element, $fieldid, $fieldname, $arguments);

    if (! empty($default)) {
        $arguments['default'] = $default;
    }
    $script .= "
(function (id, config, fieldname) {
	var field = $('#' + id);
	field.change(function() {
		var filter = {
			config: config,
			name: 'input',
			value: $(this).val()
		};

		if ($(this).is(':checkbox, :radio')) {
			filter.value = $(this).is(':checked');
		}

		if ($(this).is(':radio')) {
			$(this).closest('form').find(':radio')
				.filter(function () {
					return $(this).attr('name') == fieldname
				})
				.each(function() {
					customsearch$id.remove($(this).attr('id'));
				});
		}

		customsearch$id.add($(this).attr('id'), filter);
		customsearch$id.offset = 0;
	});

	if (config['default'] || $(field).attr('type') === 'hidden') {
		field.change();
	}
})('$fieldid', " . json_encode($arguments) . ", " . json_encode($fieldname) . ");
";

    $arguments = new JitFilter($arguments);
    $default = $arguments->default->text();
    $type = $arguments->type->word();

    if ($default && $type != "hidden") {
        if ((string) $default != 'n' && ($type == 'checkbox' || $type == 'radio')) {
            $element->setAttribute('checked', 'checked');
        } else {
            $element->setAttribute('value', $default);
        }
    }

    $document->appendChild($element);

    return $document->saveHTML();
}

function cs_design_categories($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    $document = new DOMDocument;
    extract($arguments, EXTR_SKIP);
    if (! isset($_style)) {
        $_style = 'select';
    }
    if (empty($_group) && ($_style == 'checkbox' || $_style == 'radio')) {
        return tr("_group is needed to be set if _style is checkbox or radio");
    }
    $showSubcategories = isset($_showdeep) && $_showdeep != 'n';
    if (isset($_parent) && ctype_digit($_parent) && $_parent > 0) {
        $filter = ['identifier' => $_parent, 'type' => $showSubcategories ? 'descendants' : 'children'];
    } else {
        $filter = ['type' => $showSubcategories ? 'all' : 'roots'];
    }
    if (! isset($_categpath)) {
        $_categpath = false;
    } else {
        $_categpath = ($_categpath === 'y');
    }

    $cats = TikiLib::lib('categ')->getCategories($filter);

    if ($_style == 'checkbox' || $_style == 'radio') {
        $currentlevel = 0;
        $orig_fieldid = $fieldid;
        foreach ($cats as $c) {
            $categId = $c['categId'];
            $fieldid = $orig_fieldid . "_cat$categId";
            $level = count($c['tepath']);
            if ($level > $currentlevel) {
                $ul[$level] = $document->createElement('ul');
                if ($currentlevel) {
                    $ul[$currentlevel]->appendChild($ul[$level]);
                } else {
                    $document->appendChild($ul[$level]);
                }
                $currentlevel = $level;
            } elseif ($level < $currentlevel) {
                $currentlevel = $level;
            }
            $li = $document->createElement('li');
            $li->setAttribute('class', $_style);
            $ul[$currentlevel]->appendChild($li);
            $input = $document->createElement('input');
            $input->setAttribute('type', $_style);
            cs_design_setbasic($input, $fieldid, $fieldname, $arguments);
            $input->setAttribute('value', $categId);

            $labelElement = $document->createElement('label');
            $label = $document->createTextNode($_categpath ? $c['relativePathString'] : $c['name']);

            $labelElement->appendChild($input);
            $labelElement->appendChild($label);

            $li->appendChild($labelElement);

            if ($_style == 'radio') {
                $radioreset = "$('input[type=radio][name=$fieldname]').each(function() {
	customsearch$id.remove($(this).attr('id'));
});"
                ;
            } else {
                $radioreset = '';
            }

            $script .= "
$('#$fieldid').change(function() {
	if ($(this).is(':checked')) {
		var filter = {
			config : " . json_encode($arguments) . ",
			name : 'categories',
			value : $(this).val()
		}
		$radioreset
		customsearch$id.add('$fieldid', filter);
	} else {
		customsearch$id.remove('$fieldid', filter);
	}
});
";

            if ($default && in_array($c['categId'], (array) $default)) {
                $input->setAttribute('checked', 'checked');
                $script .= "
$('#$fieldid').trigger('change');
";
            }
        }
    } elseif ($_style == 'select') {
        $element = $document->createElement('select');
        cs_design_setbasic($element, $fieldid, $fieldname, $arguments);
        $document->appendChild($element);
        // leave a blank one in the front
        if (! isset($arguments['multiple']) && ! isset($arguments['size']) || isset($arguments['_firstlabel'])) {
            if (! empty($arguments['_firstlabel'])) {
                $label = $arguments['_firstlabel'];
            } else {
                $label = '';
            }
            $option = $document->createElement('option', $label);
            $option->setAttribute('value', '');
            $element->appendChild($option);
        }
        $script .= "
$('#$fieldid').change(function() {
	customsearch$id.add('$fieldid', {
		config: " . json_encode($arguments) . ",
		name: 'categories',
		value: $(this).val()
	});
});
";

        foreach ($cats as $c) {
            $option = $document->createElement('option', $_categpath ? $c['relativePathString'] : str_replace("&", "&amp;", $c['name']));
            $option->setAttribute('value', $c['categId']);
            $element->appendChild($option);
            if ($default && in_array($c['categId'], (array) $default)) {
                $option->setAttribute('selected', 'selected');
                $script .= "
$('#$fieldid').trigger('change');
";
            }
        }
    }

    return '~np~' . $document->saveHTML() . '~/np~';
}

function cs_design_select($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    $document = new DOMDocument;
    $element = $document->createElement('select');
    cs_design_setbasic($element, $fieldid, $fieldname, $arguments);
    $document->appendChild($element);

    if (isset($arguments['_labels'])) {
        $labels = explode(',', $arguments['_labels']);
    } else {
        $labels = [];
    }
    if (isset($arguments['_options'])) {
        $options = explode(',', $arguments['_options']);
    } else {
        $options = [];
    }
    // get the options for an ItemLink field - needs _trackerId and _field set in the {select} plugin
    if (empty($options) && empty($labels) && isset($arguments['_field']) &&
            strpos($arguments['_field'], 'tracker_field_') === 0 && ! empty($arguments['_trackerId'])) {
        $definition = Tracker_Definition::get($arguments['_trackerId']);
        $field = $definition->getFieldFromPermName(str_replace('tracker_field_', '', $arguments['_field']));
        $handler = TikiLib::lib('trk')->get_field_handler($field);
        if ($field['type'] === 'r') {    // Item Link
            $labels = $handler->getItemList();
            $options = array_keys($labels);
            $labels = array_values($labels);
        } elseif ($field['type'] === 't') {	// Text field so get all values up to a sensible(?) amount
            global $prefs;

            // turns out using a straight "old fashioned" DISTINCT MySQL query here is the most efficient
            $result = TikiLib::lib('tiki')->query(
                'SELECT DISTINCT `value` FROM `tiki_tracker_item_fields` WHERE `fieldId` = ? ORDER BY `value` LIMIT 1000;',
                $field['fieldId']
            );

            while ($row = $result->fetchRow()) {
                $label = $row['value'];
                if (! in_array($label, $labels)) {
                    $labels[] = $label;

                    $label = explode(' ', $label);

                    foreach ($label as & $word) {
                        if ($prefs['unified_engine'] !== 'mysql') {
                            if (in_array($word, $prefs['unified_stopwords'])) {
                                $word = '';
                            }
                        } else {
                            if (strlen($word) < 4) {	// default mysql fulltext minimum word length TODO find current value for ft_min_word_len
                                $word = '';
                            }
                        }
                    }

                    $options[] = implode(' AND ', array_filter($label));
                }
            }
        } elseif ($field['type'] === 'u') {	// User Selector (only when in dropdown list mode)
            $html = $handler->renderInput();
            $fieldid = 'user_selector_' . $field['fieldId'];
            $script .= "
$('#$fieldid').change(function() {
	customsearch$id.add('$fieldid', {
		config: " . json_encode($arguments) . ",
		name: 'select',
		value: $(this).val()
	});
});
";

            return $html;
        } elseif ($field['type'] === 'd') {
            $data = $handler->getFieldData();

            $options = array_keys($data['possibilities']);
            $labels = array_values($data['possibilities']);
        }
    }
    if (isset($arguments['_mandatory']) && $arguments['_mandatory'] == 'y') {
        $mandatory = true;
    } else {
        $mandatory = false;
    }
    // leave a blank one in the front
    if (! $mandatory && ! isset($arguments['multiple']) && ! isset($arguments['size']) || isset($arguments['_firstlabel'])) {
        if (! empty($arguments['_firstlabel'])) {
            $label = $arguments['_firstlabel'];
        } else {
            $label = '';
        }
        $option = $document->createElement('option', $label);
        $option->setAttribute('value', '');
        $element->appendChild($option);
    }

    $script .= "
$('#$fieldid').change(function() {
	customsearch$id.add('$fieldid', {
		config: " . json_encode($arguments) . ",
		name: 'select',
		value: $(this).val()
	});
});
";

    foreach ($options as $k => $opt) {
        if (empty($labels[$k]) && is_numeric($labels[$k]) === false) {
            $body = $opt;
        } else {
            $body = $labels[$k];
        }

        $option = $document->createElement('option', $body);
        $option->setAttribute('value', $opt);
        if ($default && in_array($opt, (array) $default)) {
            $option->setAttribute('selected', 'selected');
            $script .= "
$('#$fieldid').trigger('change');
";
        }
        $element->appendChild($option);
    }

    return '~np~' . $document->saveHTML() . '~/np~';
}

function cs_design_daterange($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    extract($arguments, EXTR_SKIP);

    $smarty = TikiLib::lib('smarty');
    $smarty->loadPlugin('smarty_function_jscalendar');

    $params_from = [];
    $params_to = [];
    if (! empty($_showtime) && $_showtime == 'y') {
        $params_from['showtime'] = 'y';
        $params_to['showtime'] = 'y';
    } else {
        $params_from['showtime'] = 'n';
        $params_to['showtime'] = 'n';
    }
    $params_from['fieldname'] = $fieldname . '_from';
    $params_to['fieldname'] = $fieldname . '_to';
    $params_from['id'] = $fieldid_from = $fieldid . '_from';
    $params_to['id'] = $fieldid_to = $fieldid . '_to';

    if (isset($_from) && ! is_numeric($_from)) {
        $_from = strtotime($_from);
        if (! $_from) {
            Feedback::error(tr('_from parameter not valid: "%0"', $arguments['_from']));
        }
    }
    if (isset($_to) && ! is_numeric($_to)) {
        $_to = strtotime($_to);
        if (! $_to) {
            Feedback::error(tr('_to parameter not valid: "%0"', $arguments['_to']));
        }
    }
    if (isset($_gap) && ! is_numeric($_gap)) {
        $_gap = strtotime($_gap);
        if (! $_gap) {
            Feedback::error(tr('_gap parameter not valid: "%0"', $arguments['_gap']));
        } else {
            $_gap -= time();
        }
    }

    if (! empty($_from)) {
        if ($_from == 'now') {
            $params_from['date'] = TikiLib::lib('tiki')->now;
        } else {
            $params_from['date'] = $_from;
        }
        if (empty($_to)) {
            if (empty($_gap)) {
                $_gap = 365 * 24 * 3600;
            }
            $params_to['date'] = $params_from['date'] + $_gap;
        }
    } else {
        $params_from['date'] = TikiLib::lib('tiki')->now;
    }
    if (! empty($_to)) {
        if ($_to == 'now') {
            $params_to['date'] = TikiLib::lib('tiki')->now;
        } else {
            $params_to['date'] = $_to;
        }
        if (empty($_from)) {
            if (empty($_gap)) {
                $_gap = 365 * 24 * 3600;
            }
            $params_from['date'] = $params_to['date'] - $_gap;
        }
    } elseif (empty($params_to['date'])) {
        $params_to['date'] = TikiLib::lib('tiki')->now + 365 * 24 * 3600;
    }

    $picker = '';
    $picker .= smarty_function_jscalendar($params_from, $smarty->getEmptyInternalTemplate());
    $picker .= smarty_function_jscalendar($params_to, $smarty->getEmptyInternalTemplate());

    $script .= "
$('#{$fieldid_from}_dptxt,#{$fieldid_to}_dptxt').change(function() {
	updateDateRange_$fieldid();
});
function updateDateRange_$fieldid() {
	var from = $('#$fieldid_from').val();
	var to = $('#$fieldid_to').val();
	from = from.substr(0,10);to = to.substr(0,10); // prevent trailing 000 from date picker
	customsearch$id.add('$fieldid', {
		config: " . json_encode($arguments) . ",
		name: 'daterange',
		value: from + ',' + to
	});
}
updateDateRange_$fieldid();
";

    return $picker;
}

function cs_design_distance($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    $document = new DOMDocument;
    $distanceElement = $document->createElement('input');
    if (! empty($arguments['id'])) {
        $arguments['id'] = $fieldid . '_dist';
    }
    cs_design_setbasic($distanceElement, $fieldid . '_dist', $fieldname, $arguments);
    $latElement = $document->createElement('input');
    if (! empty($arguments['id'])) {
        $arguments['id'] = $fieldid . '_lat';
    }
    cs_design_setbasic($latElement, $fieldid . '_lat', $fieldname, $arguments);
    $lonElement = $document->createElement('input');
    if (! empty($arguments['id'])) {
        $arguments['id'] = $fieldid . '_lon';
    }
    cs_design_setbasic($lonElement, $fieldid . '_lon', $fieldname, $arguments);

    if (! empty($default)) {
        $arguments['default'] = $default;
    }
    $script .= "
(function (id, config, fieldname) {
	var fields = $('input[name=$fieldname]');
	fields.change(function() {
		var filter = {
			config: config,
			name: 'distance',
			value: fields.map(function () {return $(this).val();}).get().join()
		};
		customsearch$id.add($(this).attr('name'), filter);
	}).change();

})('$fieldid', " . json_encode($arguments) . ", " . json_encode($fieldname) . ");
";

    $arguments = new JitFilter($arguments);

    $distanceElement->setAttribute('value', $arguments->_distance->text());
    $latElement->setAttribute('value', $arguments->_lat->text());
    $lonElement->setAttribute('value', $arguments->_lon->text());

    $document->appendChild($distanceElement);
    $document->appendChild($latElement);
    $document->appendChild($lonElement);

    return $document->saveHTML();
}

function cs_design_store($id, $fieldname, $fieldid, $arguments, $default, &$script)
{
    global $prefs;
    if ($prefs['storedsearch_enabled'] != 'y') {
        return;
    }

    $document = new DOMDocument;
    $element = $document->createElement('input');
    $element->setAttribute('type', 'submit');
    cs_design_setbasic($element, $fieldid, $fieldname, $arguments);
    $document->appendChild($element);

    $script .= "

$('#$fieldid').click(function() {
	$(this).serviceDialog({
		title: $(this).val(),
		controller: 'search_stored',
		action: 'select',
		success: function (data) {
			customsearch$id.store_query = data.queryId;
			customsearch$id.load();
		}
	});
	return false;
});
";

    return $document->saveHTML();
}
