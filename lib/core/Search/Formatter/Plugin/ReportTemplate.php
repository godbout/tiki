<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_Plugin_ReportTemplate implements Search_Formatter_Plugin_Interface
{
	private $template;
	private $format;

	function __construct($template)
	{
		$this->template = WikiParser_PluginMatcher::match($template);
		$this->format = self::FORMAT_WIKI;
	}

	function setRaw($isRaw)
	{
		$this->format = $isRaw ? self::FORMAT_HTML : self::FORMAT_WIKI;
	}

	function getFormat()
	{
		return $this->format;
	}

	function getFields()
	{
		$parser = new WikiParser_PluginArgumentParser;

		$fields = [];
		foreach ($this->template as $match) {
			$name = $match->getName();

			if ($name === 'display') {
				$arguments = $parser->parse($match->getArguments());

				if (isset($arguments['name']) && ! isset($fields[$arguments['name']])) {
					$fields[$arguments['name']] = isset($arguments['default']) ? $arguments['default'] : null;
				}
			}
		}

		return $fields;
	}

	function prepareEntry($valueFormatter)
	{
		// TODO: handle both plain values from the search index for the report and display formatted values in the output
		return $valueFormatter->getPlainValues();
	}

	function renderEntries(Search_ResultSet $entries)
	{
		$matches = clone $this->template;
		foreach ($matches as $match) {
			$name = $match->getName();

			if ($name === 'calc') {
				$runner = new Math_Formula_Runner(
					[
						'Math_Formula_Function_' => '',
						'Tiki_Formula_Function_' => '',
					]
				);
				$value = '';
				try {
					$runner->setFormula($match->getBody());
					$runner->setVariables(['results' => (array)$entries]);
					$value = $runner->evaluate();
				} catch (Math_Formula_Exception $e) {
					$value = tr('Error evaluating formula %0: %1', $match->getBody(), $e->getMessage());
				}
				$match->replaceWith((string)$value);
			}
		}
		return $matches->getText();
	}
}
