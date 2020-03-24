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
					} elseif ($facet['type'] === 'date_range') {
						$facet['ranges'] = isset($arguments['ranges']) ? $arguments['ranges'] : null;
					} elseif ($facet['type'] === 'date_histogram') {
						$facet['interval'] = isset($arguments['interval']) ? $arguments['interval'] : null;
						$facet['format'] = isset($arguments['format']) ? $arguments['format'] : null;
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

				if ($facet['type'] === 'date_histogram' && ! is_a($real, '\Search_Query_Facet_DateHistogram')) {
					// tracker date fields return a generic "Term" facet but the plugin should choose range of histogram
					$real = Search_Query_Facet_DateHistogram::fromField($real->getField())->setLabel($real->getLabel());

				} else if ($facet['type'] === 'date_range' && ! is_a($real, '\Search_Query_Facet_DateRange')) {
					// same for date range
					$real = Search_Query_Facet_DateRange::fromField($real->getField())->setLabel($real->getLabel());
				}

				if (isset($facet['operator']) && $facet['operator']) {
					$real->setOperator($facet['operator']);
				}

				if (isset($facet['count']) && $facet['count']) {
					$real->setCount($facet['count']);
				}

				if (isset($facet['order']) && $facet['order']) {
					$real->setOrder($facet['order']);
				}

				if (isset($facet['min']) && $facet['min'] !== null) {
					$real->setMinDocCount($facet['min']);
				}

				if (is_a($real, '\Search_Query_Facet_DateRange')) {
					if (! empty($facet['ranges'])) {
						$ranges = explode('|', $facet['ranges']);
						$real->clearRanges();
						foreach (array_filter($ranges) as & $range) {
							$range = explode(',', $range);
							if (count($range) > 2) {
								$real->addRange($range[1], $range[0], $range[2]);
							} else if (count($range) > 1) {
								$real->addRange($range[1], $range[0]);
							}
						}
					}
				} elseif (is_a($real, '\Search_Query_Facet_DateHistogram')) {

					if (! empty($facet['interval'])) {
						$real->setInterval($facet['interval']);
					}

					if (! empty($facet['format'])) {
						$format = $facet['format'];

						$real->setRenderCallback(
							function ($date) use ($format) {
								if ($date === 0) {
									$date = 1000;	// tikilib makes zero date now FIXME (it)
								}
								return TikiLib::lib('tiki')->date_format($format, $date / 1000);
							}
						);
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
