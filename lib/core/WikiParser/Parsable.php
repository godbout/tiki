<?php
// (c) Copyright 2002-2018 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/* The extension of ParserLib is hopefully temporary. Ideally ParserLib would be replaced by a more complete version of this class. 
*/
class WikiParser_Parsable extends ParserLib
{
	/** @var string Code usually containing text and markup */
	private $markup;

	function __construct($markup)
	{
		$this->markup = $markup;
	}


	/**
	 * Standard parsing
	 * options defaults : is_html => false, absolute_links => false, language => ''
	 * @return string 
	 */
	function parse($options)
	{
		// Don't bother if there's nothing...
		if (mb_strlen($this->markup) < 1) {
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
		$data = preg_replace(';~tc~(.*?)~/tc~;s', '', $this->markup);

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
			
			/* While re-calling this is surely sub-optimal, it seems intentional (cf r23940).
			If I understand the commit message, re-called because plugins can alter $_GET. Perhaps the first call should be made only if this one won't be made. Chealer 2018-10-16 */
			$this->parse_wiki_argvariable($data);
		}

		// Handle ~pre~...~/pre~ sections
		$data = preg_replace(';~pre~(.*?)~/pre~;s', '<pre>$1</pre>', $data);

		// Strike-deleted text --text-- (but not in the context <!--[if IE]><--!> or <!--//--<!CDATA[//><!--
		// FIXME produces false positive for strings containing html comments. e.g: --some text<!-- comment -->
		$data = preg_replace("#(?<!<!|//)--([^\s>].+?)--#", "<strike>$1</strike>", $data);

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

		$data = $this->parse_data_dynamic_variables($data, $this->option['language']);

		// Replace boxes
		$delim = (isset($prefs['feature_simplebox_delim']) && $prefs['feature_simplebox_delim'] != "" ) ? preg_quote($prefs['feature_simplebox_delim']) : preg_quote("^");
		$data = preg_replace("/${delim}(.+?)${delim}/s", "<div class=\"well\">$1</div>", $data);

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
		$data = preg_replace("/\n?%%%/", "<br />", $data);

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
}
