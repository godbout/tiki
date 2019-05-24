<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * A text of markup, usually using Tiki's syntax ("wiki syntax"), which can be parsed
 *
 * This class is a contextual version of ParserLib. ParserLib is not contextual.
 * This class can be used to analyze 2 different pages in a single request and recognize those as different contexts. 2 fragments of the same wiki page can also be different contexts.
 * The extension of ParserLib is hopefully temporary. Ideally ParserLib would be replaced by a more complete version of this class.
 * TODO: Move remaining ParserLib methods and option property here
*/
class WikiParser_Parsable extends ParserLib
{
	/** @var string Code usually containing text and markup */
	private $markup;

	// Properties used by parallel parsing functions to share data

	/** @var array Footnotes added via the FOOTNOTE plugin. These are read by wikiplugin_footnotearea(). */
	public $footnotes;

	function __construct($markup)
	{
		$this->markup = $markup;
	}

	// This recursive function handles pre- and no-parse sections and plugins
	function parse_first(&$data, &$preparsed, &$noparsed, $real_start_diff = '0')
	{
		global $tikilib, $tiki_p_edit, $prefs, $pluginskiplist;
		$smarty = TikiLib::lib('smarty');
		$smarty->loadPlugin('smarty_function_icon');

		if (! is_array($pluginskiplist)) {
			$pluginskiplist = [];
		}

		$is_html = (isset($this->option['is_html']) ? $this->option['is_html'] : false);
		$data = $this->protectSpecialChars($data, $is_html);

		$matches = WikiParser_PluginMatcher::match($data);
		$argumentParser = new WikiParser_PluginArgumentParser;

		foreach ($matches as $match) {
			if ($this->option['parseimgonly'] && $this->getName() != 'img') {
				continue;
			}

			//note parent plugin in case of plugins nested in an include - to suppress plugin edit icons below
			$plugin_parent = isset($plugin_name) ? $plugin_name : false;
			$plugin_name = $match->getName();

			if (! $this->option['exclude_all_plugins'] && ! empty($this->option['exclude_plugins']) && in_array($plugin_name, $this->option['exclude_plugins'])) {
				$match->replaceWith('');
				continue;
			}

			if ($this->option['exclude_all_plugins'] && (empty($this->option['include_plugins']) || ! in_array($plugin_name, $this->option['include_plugins']))) {
				$match->replaceWith('');
				continue;
			}

			$plugin_data = $match->getBody();
			$arguments = $argumentParser->parse($match->getArguments());
			$start = $match->getStart();

			$pluginOutput = null;
			if ($this->plugin_enabled($plugin_name, $pluginOutput) || $this->option['ck_editor']) {
				static $plugin_indexes = [];

				if (! array_key_exists($plugin_name, $plugin_indexes)) {
					$plugin_indexes[$plugin_name] = 0;
				}

				$current_index = ++$plugin_indexes[$plugin_name];

				// get info to test for preview with auto_save
				if (! $this->option['skipvalidation']) {
					$status = $this->plugin_can_execute($plugin_name, $plugin_data, $arguments, $this->option['preview_mode'] || $this->option['ck_editor']);
				} else {
					$status = true;
				}
				global $tiki_p_plugin_viewdetail, $tiki_p_plugin_preview, $tiki_p_plugin_approve;
				$details = $tiki_p_plugin_viewdetail == 'y' && $status != 'rejected';
				$preview = $tiki_p_plugin_preview == 'y' && $details && ! $this->option['preview_mode'];
				$approve = $tiki_p_plugin_approve == 'y' && $details && ! $this->option['preview_mode'];

				if ($status === true || ($tiki_p_plugin_preview == 'y' && $details && $this->option['preview_mode'] && $prefs['ajax_autosave'] === 'y') || (isset($this->option['noparseplugins']) && $this->option['noparseplugins'])) {
					if (isset($this->option['stripplugins']) && $this->option['stripplugins']) {
						$ret = $plugin_data;
					} elseif (isset($this->option['noparseplugins']) && $this->option['noparseplugins']) {
						$ret = '~np~' . (string) $match . '~/np~';
					} else {
						//suppress plugin edit icons for plugins within includes since edit doesn't work for these yet
						$suppress_icons = $this->option['suppress_icons'];
						$this->option['suppress_icons'] = $plugin_name != 'include' && $plugin_parent && $plugin_parent == 'include' ?
							true : $this->option['suppress_icons'];

						$ret = $this->plugin_execute($plugin_name, $plugin_data, $arguments, $start, false);

						// restore previous suppress_icons state
						$this->option['suppress_icons'] = $suppress_icons;
					}
				} else {
					if ($status != 'rejected') {
						$smarty->assign('plugin_fingerprint', $status);
						$status = 'pending';
					}

					if ($this->option['ck_editor']) {
						$ret = $this->convert_plugin_for_ckeditor($plugin_name, $arguments, tra('Plugin execution pending approval'), $plugin_data, ['icon' => 'img/icons/error.png']);
					} else {
						$smarty->assign('plugin_name', $plugin_name);
						$smarty->assign('plugin_index', $current_index);

						$smarty->assign('plugin_status', $status);

						if (! $this->option['inside_pretty']) {
							$smarty->assign('plugin_details', $details);
						} else {
							$smarty->assign('plugin_details', '');
						}
						$smarty->assign('plugin_preview', $preview);
						$smarty->assign('plugin_approve', $approve);

						$smarty->assign('plugin_body', $plugin_data);
						$smarty->assign('plugin_args', $arguments);

						$ret = '~np~' . $smarty->fetch('tiki-plugin_blocked.tpl') . '~/np~';
					}
				}
			} else {
				$ret = $pluginOutput->toWiki();
			}

			if ($ret === false) {
				continue;
			}

			if ($this->plugin_is_editable($plugin_name) && (empty($this->option['preview_mode']) || ! $this->option['preview_mode']) && empty($this->option['indexing']) && (empty($this->option['print']) || ! $this->option['print']) && ! $this->option['suppress_icons']) {
				$headerlib = TikiLib::lib('header');
				$smarty->loadPlugin('smarty_function_icon');

				$id = 'plugin-edit-' . $plugin_name . $current_index;

				$headerlib->add_js(
					"\$(document).ready( function() {
if ( \$('#$id') ) {
\$('#$id').click( function(event) {
	popupPluginForm("
					. json_encode('editwiki')
					. ', '
					. json_encode($plugin_name)
					. ', '
					. json_encode($current_index)
					. ', '
					. json_encode($this->option['page'])
					. ', '
					. json_encode($arguments)
					. ', '
					. json_encode($this->unprotectSpecialChars($plugin_data, true)) //we restore it back to html here so that it can be edited, we want no modification, ie, it is brought back to html
					. ", event.target);
} );
}
} );
"
				);

				$displayIcon = $prefs['wiki_edit_icons_toggle'] != 'y' || isset($_COOKIE['wiki_plugin_edit_view']);

				$ret .= '~np~' .
						'<a id="' . $id . '" href="javascript:void(1)" class="editplugin"' . ($displayIcon ? '' : ' style="display:none;"') . '>' .
						smarty_function_icon(['name' => 'plugin', 'iclass' => 'tips', 'ititle' => tra('Edit plugin') . ':' . ucfirst($plugin_name)], $smarty->getEmptyInternalTemplate()) .
						'</a>' .
						'~/np~';
			}

			// End plugin handling

			$ret = str_replace('~/np~~np~', '', $ret);
			$match->replaceWith($ret);
		}

		$data = $matches->getText();

		$this->strip_unparsed_block($data, $noparsed);

		// ~pp~
		$start = -1;
		while (false !== $start = strpos($data, '~pp~', $start + 1)) {
			if (false !== $end = strpos($data, '~/pp~', $start)) {
				$content = substr($data, $start + 4, $end - $start - 4);

				// ~pp~ type "plugins"
				$key = "§" . md5($tikilib->genPass()) . "§";
				$noparsed["key"][] = preg_quote($key);
				$noparsed["data"][] = '<pre>' . $content . '</pre>';
				$data = substr($data, 0, $start) . $key . substr($data, $end + 5);
			}
		}
	}

	/**
	 * Standard parsing
	 * options defaults : is_html => false, absolute_links => false, language => ''
	 * @return string
	 */
	function parse($options)
	{
		// Don't bother if there's nothing...
		if (gettype($this->markup) <> 'string' || mb_strlen($this->markup) < 1) {
			return '';
		}

		global $prefs;

		$this->setOptions(); //reset options;

		// Handle parsing options
		if (! empty($options)) {
			$this->setOptions($options);
		}

		if ($this->option['is_html'] && ! $this->option['parse_wiki']) {
			return $this->markup;
		}

		// remove tiki comments first
		if ($this->option['ck_editor']) {
			$data = preg_replace(';~tc~(.*?)~/tc~;s', '<tikicomment>$1</tikicomment>', $this->markup);
		} else {
			$data = preg_replace(';~tc~(.*?)~/tc~;s', '', $this->markup);
		}

		$this->parse_wiki_argvariable($data);

		/* <x> XSS Sanitization handling */

		// Fix false positive in wiki syntax
		//   It can't be done in the sanitizer, that can't know if the input will be wiki parsed or not
		$data = preg_replace('/(\{img [^\}]+li)<x>(nk[^\}]+\})/i', '\\1\\2', $data);

		// Handle pre- and no-parse sections and plugins
		$preparsed = ['data' => [],'key' => []];
		$noparsed = ['data' => [],'key' => []];
		$this->strip_unparsed_block($data, $noparsed, true);
		if (! $this->option['noparseplugins'] || $this->option['stripplugins']) {
			$this->parse_first($data, $preparsed, $noparsed);
			$this->parse_wiki_argvariable($data);
		}

		// Handle ~pre~...~/pre~ sections
		$data = preg_replace(';~pre~(.*?)~/pre~;s', '<pre>$1</pre>', $data);

		// Strike-deleted text --text-- (but not in the context <!--[if IE]><--!> or <!--//--<!CDATA[//><!--
		// FIXME produces false positive for strings containing html comments. e.g: --some text<!-- comment -->
		$data = preg_replace("#(?<!<!|//)--([^\s>].+?)--#", "<strike>$1</strike>", $data);

		// Handle comments again in case parse_first method above returned wikiplugins with comments (e.g. PluginInclude a wiki page with comments)
		$data = preg_replace(';~tc~(.*?)~/tc~;s', '', $data);

		// Handle html comment sections
		$data = preg_replace(';~hc~(.*?)~/hc~;s', '<!-- $1 -->', $data);

		// Replace special characters
		// done after url catching because otherwise urls of dyn. sites will be modified // What? Chealer
		// must be done before color as we can have "~hs~~hs" (2 consecutive non-breaking spaces. The color syntax uses "~~".)
		// jb 9.0 html entity fix - excluded not $this->option['is_html'] pages
		if (! $this->option['is_html']) {
			$this->parse_htmlchar($data);
		}

		//needs to be before text color syntax because of use of htmlentities in lib/core/WikiParser/OutputLink.php
		$data = $this->parse_data_wikilinks($data, false, $this->option['ck_editor']);

		// Replace colors ~~foreground[,background]:text~~
		// must be done before []as the description may contain color change
		$parse_color = 1;
		$temp = $data;
		while ($parse_color) { // handle nested colors, parse innermost first
			$temp = preg_replace_callback(
				"/~~([^~:,]+)(,([^~:]+))?:([^~]*)(?!~~[^~:,]+(?:,[^~:]+)?:[^~]*~~)~~/Ums",
				'ParserLib::colorAttrEscape',
				$temp,
				-1,
				$parse_color
			);

			if (! empty($temp)) {
				$data = $temp;
			}
		}

		// On large pages, the above preg rule can hit a BACKTRACE LIMIT
		// In case it does, use the simpler color replacement pattern.
		if (empty($temp)) {
			$data = preg_replace_callback(
				"/\~\~([^\:\,]+)(,([^\:]+))?:([^~]*)\~\~/Ums",
				'ParserLib::colorAttrEscape',
				$data
			);
		}

		// Extract [link] sections (to be re-inserted later)
		$noparsedlinks = [];

		// This section matches [...].
		// Added handling for [[foo] sections.  -rlpowell
		preg_match_all("/(?<!\[)(\[[^\[][^\]]+\])/", $data, $noparseurl);

		foreach (array_unique($noparseurl[1]) as $np) {
			$key = '§' . md5(TikiLib::genPass()) . '§';

			$aux["key"] = $key;
			$aux["data"] = $np;
			$noparsedlinks[] = $aux;
			$data = preg_replace('/(^|[^a-zA-Z0-9])' . preg_quote($np, '/') . '([^a-zA-Z0-9]|$)/', '\1' . $key . '\2', $data);
		}

		// BiDi markers
		$bidiCount = 0;
		$bidiCount = preg_match_all("/(\{l2r\})/", $data, $pages);
		$bidiCount += preg_match_all("/(\{r2l\})/", $data, $pages);

		$data = preg_replace("/\{l2r\}/", "<div dir='ltr'>", $data);
		$data = preg_replace("/\{r2l\}/", "<div dir='rtl'>", $data);
		$data = preg_replace("/\{lm\}/", "&lrm;", $data);
		$data = preg_replace("/\{rm\}/", "&rlm;", $data);
		// smileys
		$data = $this->parse_smileys($data);

		// parse_tagged_users
		if (isset($prefs['feature_tag_users']) && $prefs['feature_tag_users'] == 'y') {
			$data = $this->parse_tagged_users($data);
		}

		$data = $this->parse_data_dynamic_variables($data, $this->option['language']);

		// Replace boxes
		$delim = (isset($prefs['feature_simplebox_delim']) && $prefs['feature_simplebox_delim'] != "" ) ? preg_quote($prefs['feature_simplebox_delim']) : preg_quote("^");
		$data = preg_replace("/${delim}(.+?)${delim}/s", "<div class=\"card bg-light\"><div class=\"card-body\">$1</div></div>", $data);

		// Underlined text
		$data = preg_replace("/===(.+?)===/", "<u>$1</u>", $data);
		// Center text
		if ($prefs['feature_use_three_colon_centertag'] == 'y' || ($prefs['namespace_enabled'] == 'y' && $prefs['namespace_separator'] == '::')) {
			$data = preg_replace("/:::(.+?):::/", "<div style=\"text-align: center;\">$1</div>", $data);
		} else {
			$data = preg_replace("/::(.+?)::/", "<div style=\"text-align: center;\">$1</div>", $data);
		}

		// reinsert hash-replaced links into page
		foreach ($noparsedlinks as $np) {
			$data = str_replace($np["key"], $np["data"], $data);
		}

		if ($prefs['wiki_pagination'] != 'y') {
			$data = str_replace($prefs['wiki_page_separator'], $prefs['wiki_page_separator'] . ' <em>' . tr('Wiki page pagination has not been enabled.') . '</em>', $data);
		}

		$data = $this->parse_data_externallinks($data);

		$data = $this->parse_data_tables($data);

		/* parse_data_process_maketoc() calls parse_data_inline_syntax().

		It seems wrong to just call parse_data_inline_syntax() when the parsetoc option is disabled.
		Despite its name, parse_data_process_maketoc() does not just deal with TOC-s.

		I believe it would be better that parse_data_process_maketoc() check parsetoc, only to set $need_maketoc, so that the following calls parse_data_process_maketoc() unconditionally. Chealer 2018-01-02
		*/
		if ($this->option['parsetoc']) {
			$this->parse_data_process_maketoc($data, $noparsed);
		} else {
			$data = $this->parse_data_inline_syntax($data);
		}

		// linebreaks using %%%
		$data = preg_replace("/\n?(?<![^%]\d)%%%/", "<br />", $data);

		// Close BiDi DIVs if any
		for ($i = 0; $i < $bidiCount; $i++) {
			$data .= "</div>";
		}

		// Put removed strings back.
		$this->replace_preparse($data, $preparsed, $noparsed, $this->option['is_html']);

		// Converts &lt;x&gt; (<x> tag using HTML entities) into the tag <x>. This tag comes from the input sanitizer (XSS filter).
		// This is not HTML valid and avoids using <x> in a wiki text,
		//   but hide '<x>' text inside some words like 'style' that are considered as dangerous by the sanitizer.
		$data = str_replace([ '&lt;x&gt;', '~np~', '~/np~' ], [ '<x>', '~np~', '~/np~' ], $data);

		if ($this->option['typography'] && ! $this->option['ck_editor']) {
			$data = typography($data, $this->option['language']);
		}

		return $data;
	}

	function plugin_execute($name, $data = '', $args = [], $offset = 0, $validationPerformed = false, $option = [])
	{
		global $killtoc;

		if (! empty($option)) {
			$this->setOptions($option);
		}

		$data = $this->unprotectSpecialChars($data, true);					// We want to give plugins original
		$args = preg_replace(['/^&quot;/', '/&quot;$/'], '', $args);		// Similarly remove the encoded " chars from the args

		$outputFormat = 'wiki';
		if (isset($this->option['context_format'])) {
			$outputFormat = $this->option['context_format'];
		}

		if (! $this->plugin_exists($name, true)) {
			return false;
		}

		if (! $validationPerformed && ! $this->plugin_enabled($name, $output)) {
			return $this->convert_plugin_output($output, '', $outputFormat);
		}

		if ($this->option['inside_pretty'] === true) {
			$trklib = TikiLib::lib('trk');
			$trklib->replace_pretty_tracker_refs($args);

			// Reset the tr_offset1 value, which comes from a list selection and specifies the offset to use within the resultset.
			//  Pretty trackers can contain other tracker plugins. These plugins should get the results from index = 0, and not the index in the calling list
			if (isset($_REQUEST['tr_offset1'])) {
				$_REQUEST['list_tr_offset1'] = $_REQUEST['tr_offset1'];
				$_REQUEST['tr_offset1'] = 0;
			}
			foreach ($args as $arg) {
				if (substr($arg, 0, 4) == '{$f_') {
					return $name . ': ' . tr(
						'Pretty tracker reference "%0" could not be replaced in plugin "%1".',
						str_replace(['{','}'], '', $arg),
						$name
					);
				}
			}
		}

		$func_name = 'wikiplugin_' . $name;

		if (! $validationPerformed && ! $this->option['ck_editor']) {
			$this->plugin_apply_filters($name, $data, $args);
		}

		if (function_exists($func_name)) {
			$pluginFormat = 'wiki';

			$info = $this->plugin_info($name, $args);
			if (isset($info['format'])) {
				$pluginFormat = $info['format'];
			}

			$killtoc = false;

			if ($pluginFormat === 'wiki' && $this->option['preview_mode'] === true && $_SESSION['wysiwyg'] === 'y') {	// fix lost new lines in wysiwyg plugins data
				$data = nl2br($data);
			}

			$saved_options = $this->option;	// save current options (but do not reset)

			$output = $func_name($data, $args, $offset, $this);

			$this->option = $saved_options; // restore parsing options after plugin has executed

			//This was added to remove the table of contents sometimes returned by other plugins, to use, simply have global $killtoc, and $killtoc = true;
			if ($killtoc == true) {
				while (($maketoc_start = strpos($output, "{maketoc")) !== false) {
					$maketoc_end = strpos($output, "}");
					$output = substr_replace($output, "", $maketoc_start, $maketoc_end - $maketoc_start + 1);
				}
			}

			$killtoc = false;

			$plugin_result = $this->convert_plugin_output($output, $pluginFormat, $outputFormat);
			if ($this->option['ck_editor'] == true) {
				return $this->convert_plugin_for_ckeditor($name, $args, $plugin_result, $data, $info);
			} else {
				return $plugin_result;
			}
		} elseif (WikiPlugin_Negotiator_Wiki_Alias::findImplementation($name, $data, $args)) {
			return $this->plugin_execute($name, $data, $args, $offset, $validationPerformed);
		}
	}
}
