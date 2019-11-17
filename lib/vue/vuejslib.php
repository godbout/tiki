<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


class VueJsLib
{
	/**
	 * @param string  $str body of the vue document
	 * @param bool    $app whether to create the App
	 */

	public function processVue($str, $app = false)
	{
		$headerlib = TikiLib::lib('header');

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML($str);

		$script   = $dom->getElementsByTagName('script');
		$template = $dom->getElementsByTagName('template');
		$style    = $dom->getElementsByTagName('style');

		if ($script->length) {    // required
			$javascript = $script[0]->nodeValue;
			preg_match('/export default {(.*)}/ms', $javascript, $match);

			if ($match) {
				$originalExport = $export = $match[1];
				if ($template->length) {
					$templateNode = $template[0];
					$export .= ', template: `' . $this->get_inner_html($templateNode) . '`';
					$javascript = str_replace($originalExport, $export, $javascript);
				}
				//$headerlib->add_js_module($javascript);
				// embedded modules cannot export apparently, also can't be found by import fns

				$minifier = new MatthiasMullie\Minify\JS($javascript);
				global $tikidomainslash;
				$tempDir = './temp/public/' . $tikidomainslash;
				$hash =  md5(serialize($javascript));
				$file = $tempDir . "min_vue_" . $hash . ".js";
				$temp = $minifier->minify($file);
				chmod($file, 0644);

			}
		}
		if ($app) {
			$headerlib->add_js_module('
import App from "'. $file . '";

new Vue({
	  render: h => h(App),
	}).$mount(`#app`);
');
			return '<div id="app"></div>';
		}
	}

	function get_inner_html( $node ) {
	    $innerHTML= '';
	    $children = $node->childNodes;
	    foreach ($children as $child) {
	        $innerHTML .= $child->ownerDocument->saveXML( $child );
	    }

	    return str_replace('&#13;', "\r", $innerHTML);
	}

}