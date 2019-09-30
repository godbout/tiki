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
						$facet['order'] = isset($arguments['order']) ? $arguments['order'] : null;
						$facet['min'] = isset($arguments['min']) ? $arguments['min'] : null;
					} else if ($facet['type'] === 'date_range') {
						$facet['ranges'] = isset($arguments['ranges']) ? $arguments['ranges'] : null;
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

				if ($facet['order']) {
					$real->setOrder($facet['order']);
				}

				if ($facet['min'] !== null) {
					$real->setMinDocCount($facet['min']);
				}

				if (is_a($real, '\Search_Query_Facet_DateRange') && ! empty($facet['ranges'])) {
					$ranges = explode('|', $facet['ranges']);
					$real->clearRanges();
					foreach (array_filter($ranges) as & $range) {
						$range = explode(',', $range);
						if (count($range) > 2) {
							$real->addRange($range[1], $range[0], $range[2]);
						} elseif (count($range) > 1) {
							$real->addRange($range[1], $range[0]);
						}
					}
				}

				$query->requestFacet($real);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getFacets(): array
	{
		return $this->facets;
	}

}
