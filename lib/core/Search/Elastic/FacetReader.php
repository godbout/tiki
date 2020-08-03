<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_FacetReader
{
    private $data;

    public function __construct(stdClass $data)
    {
        $this->data = $data;
    }

    public function getFacetFilter(Search_Query_Facet_Interface $facet)
    {
        $facetName = $facet->getName();
        $entry = null;

        if (empty($this->data->facets->$facetName->total) && empty($this->data->aggregations->$facetName->buckets)) {
            return null;
        }

        if (isset($this->data->facets->$facetName)) {
            $entry = $this->data->facets->$facetName;
        } elseif (isset($this->data->aggregations->$facetName)) {
            $entry = $this->data->aggregations->$facetName;
        }

        return new Search_ResultSet_FacetFilter($facet, $this->getFromTerms($entry));
    }

    private function getFromTerms($entry)
    {
        $out = [];

        if (! empty($entry->terms)) {
            foreach ($entry->terms as $term) {
                if ('' !== $term->term) {
                    $out[] = ['value' => $term->term, 'count' => $term->count];
                }
            }
        } elseif (! empty($entry->buckets)) {
            foreach ($entry->buckets as $bucket) {
                if ('' !== $bucket->key) {
                    $out[] = ['value' => $bucket->key, 'count' => $bucket->doc_count];
                }
            }
        }

        return $out;
    }
}
