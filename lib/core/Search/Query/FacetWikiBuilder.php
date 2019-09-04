<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Query_FacetWikiBuilder
{
	private $facets = [];

	function apply(WikiParser_PluginMatcher $matches)
	{
		$argumentParser = new WikiParser_PluginArgumentParser;

		foreach ($matches as $match) {
			if ($match->getName() === 'facet') {
				$arguments = $argumentParser->parse($match->getArguments());
				if (isset($arguments['name'])) {
					$facet = [
						'name'     => $arguments['name'],
						'type'     => isset($arguments['type']) ? $arguments['type'] : 'terms',
					];

					if ($facet['type'] === 'terms') {
						$facet['operator'] = isset($arguments['operator']) ? $arguments['operator'] : 'or';
						$facet['count'] = isset($arguments['count']) ? $arguments['count'] : null;
					}

					if (isset($arguments['id'])) {
						$facet['id'] = $arguments['id'];
					} else {
						$facet['id'] = $arguments['name'];
					}

					$this->facets[] = $facet;
				}
			}
		}
	}

	function build(Search_Query $query, Search_FacetProvider $provider)
	{
		foreach ($this->facets as $facet) {
			if (isset($facet['id'])) {
				$real = $provider->getFacet($facet['id']);
			} else {
				$real = null;
			}
			if (! $real) {
				$real = $provider->getFacet($facet['name']);	// name is actually field, id allows multiple aggs per field
			}
			if ($real) {
				if ($facet['operator']) {
					$real->setOperator($facet['operator']);
				}

				if ($facet['count']) {
					$real->setCount($facet['count']);
				}

				$query->requestFacet($real);
			}
		}
	}
}
