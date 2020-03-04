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
		libxml_use_internal_errors(true);
		$dom->loadHTML("<html lang=\"en\"><body>$str</body></html>");
		$errors = libxml_get_errors();
		foreach ($errors as $error)
		{
			// template and ui-predicate tags are expected, so ignore them...
		    /* @var $error LibXMLError */
			if (! in_array($error->message, ["Tag template invalid\n", "Tag ui-predicate invalid\n"])) {
				trigger_error($error->message);
			}
		}
		libxml_clear_errors();

		$script = $dom->getElementsByTagName('script');
		$template = $dom->getElementsByTagName('template');
		$style = $dom->getElementsByTagName('style');

		if (! $name && $app) {
			$name = 'App';
		}

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
			$hash = $name ? $name : md5(serialize($javascript));

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
	}).\$mount(`#$name`);
"
				);
				return "<div id=\"$name\"></div>";
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

		// remove empties (?)
		$targetFields = array_values(array_filter($params['targetFields']));

		foreach ($targetFields as & $field) {
			$this->setFieldType($field);
		}

		// remove auto-inc and other non-compatible field types
		$params['targetFields'] = array_values(array_filter($targetFields));

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
		$appHtml .= $this->processVue('lib/vue/rules/NothingArgument.vue', 'NoArgument');
		$appHtml .= $this->processVue('lib/vue/rules/BoolArgument.vue', 'BoolArgument');
		$appHtml .= $this->processVue('lib/vue/rules/CollectionArgument.vue', 'CollectionArgument');

		$appHtml .= $this->processVue('lib/vue/rules/TrackerRules.vue', 'TrackerRules');

		return $appHtml;
	}

	public function generateTrackerRulesJS($fields, $parentSelector = '.form-group:first', $insPrefix = 'ins_') {

		global $prefs;

		$js = '';

		foreach ($fields as $field) {
			if (! empty( $field['rules']) && $field['rules'] !== '{"conditions":null,"actions":null,"else":null}') {

				$this->setFieldType($field);
				if ($field['argumentType'] === 'Collection') {
					$append = '[]';
				} else {
					$append = '';
				}

				$rules = Tiki\Lib\core\Tracker\Rule\Rules::fromData($field['fieldId'], $field['rules']);
				$js .= $rules->getJavaScript($field['fieldId'] . $append, $parentSelector);
			}
		}

		if ($prefs['jquery_ui_chosen'] === 'y') {
			$js .= "\$(document).trigger('chosen:update');\n";
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

	/**
	 * @param $field
	 */
	private function setFieldType(&$field): void
	{
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
			case 'M':    // Multiselect
				$field['argumentType'] = 'Collection';
				break;
			case 'q':    // auto increment (not used client-side)
				$field = [];
				return;
			default:
				$field['argumentType'] = 'Text';
				break;
		}
		if ($field['type'] === 'r' && $field['options_map']['selectMultipleValues'] ||		// ItemLink
			$field['type'] === 'w' && $field['options_map']['selectMultipleValues'] ||	// DynamicItemsList
			$field['type'] === 'u' && $field['options_map']['multiple']) {				// UserSelector

			$field['argumentType'] = 'Collection';
		}
	}

}