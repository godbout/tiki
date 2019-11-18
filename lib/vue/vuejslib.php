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
	 * @param array  $data   values to expose to the Vue App
	 * @param bool   $minify or not
	 *
	 * @return string
	 * @throws Exception
	 */

	public function processVue($str, $name = '', $app = false, $data = [], $minify = false)
	{
		$headerlib = TikiLib::lib('header');

		if (is_readable($str)) {
			$str = file_get_contents($str);
		}

		// process some shorthand syntax that doesn't work for us
		$str = preg_replace('/\s(@)(?=' . implode('|', $this->jsEvents()) . ')\b/', ' v-on:', $str);

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML("<html lang=\"en\"><body>$str</body></html>");

		$script = $dom->getElementsByTagName('script');
		$template = $dom->getElementsByTagName('template');
		$style = $dom->getElementsByTagName('style');

		if (! $name && $app) {
			$name = 'App';
		}

		$nameLowerCase = strtolower($name);

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
				$data = json_encode($data);

				$headerlib->add_js_module(
					"
import $name from \"$file\";

var vm = new Vue({
	  render: h => h($name),
	  data: function () { return $data; },
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
	 * generates a predicate ui vue.js based rules gui for a tracker field
	 *
	 * @param array $params
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getFieldRules($params)
	{
		if (empty($params['fieldId'])) {
			Feedback::error(tr('No fieldId for Field Rules'));
		}

		foreach ($params['targetFields'] as & $field) {
			switch ($field['type']) {
				case 'f':    // datetime
				case 'j':    // datepicker
				case 'CAL':  // calendar item
					$field['argumentType'] = 'DateTime';
					break;
				case 'n':    // number
				case 'b':    // currency
					$field['argumentType'] = 'Number';
					break;
				case 'c':    // checkbox
					$field['argumentType'] = 'Boolean';
					break;
				case 'e':    // Category
				case 'd':    // DropDown
				case 'D':    // DropDown with Other
				case 'M':    // Multiselect
				case 'w':    // DynamicList
					$field['argumentType'] = 'Array';
					break;
				default:
					$field['argumentType'] = 'Text';
					break;
			}
		}

		if (is_string($params['rules'])) {
			$params['rules'] = json_decode(html_entity_decode($params['rules']));
		}

		if (! is_object($params['rules']) || empty($params['rules'])) {
			$params['rules'] = [
				'conditions' => null,
				'actions' => null,
				'else' => null,
			];
		}

		$params['definitiion'] = \Tracker\Rule\Definition::get();

		$appHtml = $this->processVue('lib/vue/rules/TrackerRulesApp.vue', 'TrackerRulesApp', true, $params);

		$appHtml .= $this->processVue('lib/vue/rules/TextArgument.vue', 'TextArgument');
		$appHtml .= $this->processVue('lib/vue/rules/NumberArgument.vue', 'NumberArgument');
		$appHtml .= $this->processVue('lib/vue/rules/DateArgument.vue', 'DateArgument');
		$appHtml .= $this->processVue('lib/vue/rules/NoArgument.vue', 'NoArgument');
		$appHtml .= $this->processVue('lib/vue/rules/BoolArgument.vue', 'BoolArgument');

		$appHtml .= $this->processVue('lib/vue/rules/TrackerRules.vue', 'TrackerRules');

		return $appHtml;
	}

	public function generateTrackerRulesJS($fields, $insPrefix = 'ins_', $parentSelector = '.form-group:first') {

		$js = '';

		foreach ($fields as $field) {
			if (! empty( $field['rules'])) {
				$rules = Tiki\Lib\core\Tracker\Rule\Rules::fromData($field['fieldId'], $field['rules']);
				$js .= $rules->getJavaScript($field['fieldId'], $parentSelector);
			}
		}

		return $js;
	}

	/**
	 * @return array
	 */
	private function jsEvents(): array
	{
		return [
			'abort',
			'afterprint',
			'animationcancel',
			'animationend',
			'animationiteration',
			'animationstart',
			'audioprocess',
			'auxclick',
			'beforeprint',
			'beforeunload',
			'blur',
			'canplay',
			'canplaythrough',
			'change',
			'click',
			'close',
			'complete',
			'compositionend',
			'compositionstart',
			'compositionupdate',
			'contextmenu',
			'copy',
			'cut',
			'dblclick',
			'drag',
			'dragend',
			'dragenter',
			'dragleave',
			'dragover',
			'dragstart',
			'drop',
			'durationchange',
			'emptied',
			'ended',
			'error',
			'focus',
			'fullscreenchange',
			'fullscreenerror',
			'keydown',
			'keypress',
			'keyup',
			'load',
			'load',
			'loadeddata',
			'loadedmetadata',
			'loadend',
			'loadstart',
			'message',
			'mousedown',
			'mouseenter',
			'mouseleave',
			'mousemove',
			'mouseout',
			'mouseover',
			'mouseup',
			'offline',
			'online',
			'open',
			'pagehide',
			'pageshow',
			'paste',
			'pause',
			'play',
			'playing',
			'pointerlockchange',
			'pointerlockerror',
			'popstate',
			'progress',
			'ratechange',
			'reset',
			'resize',
			'scroll',
			'seeked',
			'seeking',
			'select',
			'stalled',
			'submit',
			'suspend',
			'timeout',
			'timeupdate',
			'transitioncancel',
			'transitionend',
			'transitionrun',
			'transitionstart',
			'unload',
			'volumechange',
			'waiting',
			'wheel',
			// seems to be a custom vue or ui-predicate event?
			'initialize',
		];
	}

}