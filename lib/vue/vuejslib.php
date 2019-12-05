<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


class VueJsLib
{
	/**
	 * @param string $str    body of the vue document
	 * @param string $name   name of the component
	 * @param bool   $app    whether to create the App
	 * @param bool   $minify or not
	 *
	 * @return string
	 */

	public function processVue($str, $name = '', $app = false, $minify = false)
	{
		$headerlib = TikiLib::lib('header');

		if (is_readable($str)) {
			$str = file_get_contents($str);
		}

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML("<html lang=\"en\"><body>$str</body></html>");

		$script = $dom->getElementsByTagName('script');
		$template = $dom->getElementsByTagName('template');
		$style = $dom->getElementsByTagName('style');

		if (! $name && $app) {
			$name = 'App';
		}

		$nameLowerCase = strtolower($name);

		if ($script->length) {    // required
			$javascript = $script[0]->nodeValue;
			preg_match('/export default {(.*)}/ms', $javascript, $match);

			if ($match) {
				$originalExport = $export = $match[1];
				if ($template->length) {
					$templateNode = $template[0];
					$export .= ', template: `' . $this->getInnerHtml($templateNode) . '`';
					$javascript = str_replace($originalExport, $export, $javascript);
				}
				//$headerlib->add_js_module($javascript);
				// embedded modules cannot export apparently, also can't be found by import fns

			}
			global $tikidomainslash;
			$tempDir = './temp/public/' . $tikidomainslash;
			$hash = $nameLowerCase ? $nameLowerCase : md5(serialize($javascript));

			$file = $tempDir . "vue_" . $hash . ".js";
			if ($minify) {
				$minifier = new MatthiasMullie\Minify\JS($javascript);
				$minifier->minify($file);
			} else {
				file_put_contents($file, $javascript);
			}
			chmod($file, 0644);

			if ($app) {
				$headerlib->add_js_module(
					"
import $name from \"$file\";

new Vue({
	  render: h => h($name),
	}).\$mount(`#$nameLowerCase`);
"
				);
				return "<div id=\"$nameLowerCase\"></div>";
			}
		}

		return '';
	}

	// thanks dpetroff https://www.php.net/manual/en/class.domelement.php#101243
	function getInnerHtml($node)
	{
		$innerHTML = '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML($child);
		}

		return str_replace('&#13;', "\r", $innerHTML);
	}

	/**
	 *
	 */
	public function getPredicateUI()
	{

		$appHtml = $this->processVue('lib/vue/rules/TrackerRulesApp.vue', 'TrackerRulesApp', true);

		$appHtml .= $this->processVue('lib/vue/rules/TextArgument.vue', 'TextArgument');
		$appHtml .= $this->processVue('lib/vue/rules/DateArgument.vue', 'DateArgument');
		$appHtml .= $this->processVue('lib/vue/rules/NullArgument.vue', 'NullArgument');
		$appHtml .= $this->processVue('lib/vue/rules/TrackerRules.vue', 'TrackerRules');

		return $appHtml;
	}

}