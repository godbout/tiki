<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Elastic_Index implements Search_Index_Interface, Search_Index_QueryRepository
{
    private $connection;
    private $index;
    private $facetCount = 10;
    private $invalidateList = [];

    private $providedMappings = [];

    private $camelCase = false;
    private $possessiveStemmer = true;

    private $fieldMappings = null;

    private $multisearchIndices;
    private $multisearchStack;

    public function __construct(Search_Elastic_Connection $connection, $index)
    {
        $this->connection = $connection;
        $this->index = $index;
    }

    public function setCamelCaseEnabled($enabled)
    {
        $this->camelCase = (bool) $enabled;
    }

    public function setPossessiveStemmerEnabled($enabled)
    {
        $this->possessiveStemmer = (bool) $enabled;
    }

    public function destroy()
    {
        $this->connection->deleteIndex($this->index);

        return true;
    }

    /**
     * Get field mappings of Elastic
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->providedMappings;
    }

    public function exists()
    {
        $indexStatus = $this->connection->getIndexStatus($this->index);

        if (is_object($indexStatus)) {
            return ! empty($indexStatus->indices->{$this->index});
        }

        return (bool) $indexStatus;
    }

    public function addDocument(array $data)
    {
        list($objectType, $objectId, $data) = $this->generateDocument($data);
        unset($this->invalidateList[$objectType . ':' . $objectId]);

        if (! empty($data['hash'])) {
            $objectId .= "~~{$data['hash']}";
        }

        $this->connection->index($this->index, $objectType, $objectId, $data);
    }

    private function generateDocument(array $data)
    {
        $objectType = $data['object_type']->getValue();
        $objectId = $data['object_id']->getValue();

        $this->generateMapping($objectType, $data);

        $data = array_map(
            function ($entry) {
                return $entry->getValue();
            },
            $data
        );

        return [ $objectType, $objectId, $data ];
    }

    private function generateMapping($type, $data)
    {
        if (! isset($this->providedMappings[$type])) {
            $this->providedMappings[$type] = [];
        }

        $mapping = array_map(
            function ($entry) {
                if ($entry instanceof Search_Type_Numeric) {
                    return [
                        "type" => "float",
                        "fields" => [
                            "sort" => [
                                "type" => "float",
                                "null_value" => 0.0,
                                "ignore_malformed" => true,
                            ],
                            "nsort" => [
                                "type" => "float",
                                "null_value" => 0.0,
                                "ignore_malformed" => true,
                            ],
                        ],
                    ];
                } elseif ($entry instanceof Search_Type_Whole || $entry instanceof Search_Type_MultivaluePlain) {
                    return [
                        "type" => $this->connection->getVersion() >= 5 ? "keyword" : "string",
                        "index" => $this->connection->getVersion() >= 5 ? true : "not_analyzed",
                        "ignore_above" => 32765,
                        "fields" => [
                            "sort" => $this->connection->getVersion() >= 5 ?
                            [
                                "type" => "keyword",
                                "ignore_above" => 1000,
                            ] : [
                                "type" => "string",
                                "analyzer" => "sortable",
                            ],
                            "nsort" => [
                                "type" => "float",
                                "null_value" => 0.0,
                                "ignore_malformed" => true,
                            ],
                        ],
                    ];
                } elseif ($entry instanceof Search_Type_Object) {
                    return [
                        "type" => "object",
                    ];
                } elseif ($entry instanceof Search_Type_Json) {
                    return [
                        "type" => "object",
                        "enabled" => false,
                    ];
                } elseif ($entry instanceof Search_Type_Nested) {
                    return [
                        "type" => "nested",
                        "dynamic" => true,
                    ];
                } elseif ($entry instanceof Search_Type_GeoPoint) {
                    return [
                        "type" => "geo_point",
                        "index" => $this->connection->getVersion() >= 5 ? true : "not_analyzed",
                    ];
                } elseif ($entry instanceof Search_Type_DateTime) {
                    return [
                        "type" => "date",
                        "fields" => [
                            "sort" => [
                                "type" => "date",
                            ],
                            "nsort" => [
                                "type" => "date",
                            ],
                        ],
                    ];
                }
                $ret = [
                        "type" => $this->connection->getVersion() >= 5 ? "text" : "string",
                        "term_vector" => "with_positions_offsets",
                        "fields" => [
                            "sort" => $this->connection->getVersion() >= 5 ?
                            [
                                "type" => "keyword",
                                "ignore_above" => 1000,
                                "normalizer" => "sortable",
                            ] : [
                                "type" => "string",
                                "analyzer" => "sortable",
                            ],
                            "nsort" => [
                                "type" => "float",
                                "null_value" => 0.0,
                                "ignore_malformed" => true,
                            ],
                        ],
                    ];
                if ($entry instanceof Search_Type_SimpleText) {
                    $ret["analyzer"] = "sortable"; // sortable without any extras, best results for wildcard
                }

                return $ret;
            },
            array_diff_key($data, $this->providedMappings[$type])
        );
        $this->providedMappings[$type] = array_merge($this->providedMappings[$type], $mapping);
        $mapping = array_filter($mapping);

        if (! empty($mapping)) {
            $this->connection->mapping($this->index, $mapping, function () {
                return $this->getIndexDefinition();
            });
        }
    }

    private function getIndexDefinition()
    {
        global $prefs;
        $definition = [
            'analysis' => [
                'tokenizer' => [
                    'camel' => [
                        "type" => "pattern",
                        "pattern" => "([^\\p{L}\\d]+)|(?<=\\D)(?=\\d)|(?<=\\d)(?=\\D)|(?<=[\\p{L}&&[^\\p{Lu}]])(?=\\p{Lu})|(?<=\\p{Lu})(?=\\p{Lu}[\\p{L}&&[^\\p{Lu}]])"
                    ],
                ],
                'analyzer' => [
                    'default' => [
                        'tokenizer' => $this->camelCase ? 'camel' : 'standard',
                        'char_filter' => [
                            'versions_filter'
                        ],
                        'filter' => $this->connection->getVersion() >= 7.0 ? ['lowercase', 'asciifolding', 'word_delimiter'] : ['standard', 'lowercase', 'asciifolding', 'word_delimiter'],
                    ],
                    'sortable' => [
                        'tokenizer' => 'keyword',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
                'normalizer' => [
                    'sortable' => [
                        'type' => 'custom',
                        'char_filter' => [],
                        'filter' => ['lowercase', 'asciifolding']
                    ],
                ],
                'filter' => [
                    'tiki_stop' => [
                        'type' => 'stop',
                        'stopwords' => $prefs['unified_stopwords'],
                    ],
                ],
                'char_filter' => [
                    'versions_filter' => [
                        'type' => 'pattern_replace',
                        'pattern' => '(\\d+)\.(?=\\d)',
                        'replacement' => '$1',
                    ],
                ],
            ],
        ];

        // optionally removes 's from the end of words
        if ($this->possessiveStemmer) {
            $definition['analysis']['analyzer']['default']['filter'][] = 'english_possessive_stemmer';
            $definition['analysis']['analyzer']['sortable']['filter'][] = 'english_possessive_stemmer';
            $definition['analysis']['filter']['english_possessive_stemmer'] = ['type' => 'stemmer', 'name' => 'possessive_english'];
        }

        // see https://en.wikipedia.org/wiki/Stemming - e.g.  "stem" will find "stems", "stemmer", "stemming" and "stemmed"
        if ($this->connection->getVersion() >= 5) {
            $definition['analysis']['analyzer']['default']['filter'][] = 'porter_stem';
        } else {
            $definition['analysis']['analyzer']['default']['filter'][] = 'porterStem';
        }
        // stop words need to be last
        $definition['analysis']['analyzer']['default']['filter'][] = 'tiki_stop';
        $definition['analysis']['analyzer']['sortable']['filter'][] = 'tiki_stop';


        if ($this->connection->getVersion() > 5.0) {
            return ['settings' => $definition];
        }

        return $definition;		// ES 1 and 2 want analysis as the top level for index settings
    }

    public function endUpdate()
    {
        foreach ($this->invalidateList as $object) {
            $this->connection->unindex($this->index, $object['object_type'], $object['object_id']);
        }

        $this->connection->flush();

        $this->invalidateList = [];
    }

    public function optimize()
    {
        Feedback::warning(tr('Elasticsearch does not support optimize.'));
    }

    public function invalidateMultiple(array $objectList)
    {
        foreach ($objectList as $object) {
            $key = $object['object_type'] . ':' . $object['object_id'];
            $this->invalidateList[$key] = $object;
        }
    }

    /**
     * @param string $id The ID of each individual query in a multisearch
     * @param array Indices to search
     * @param Search_Query $fullQuery The fully formatted query that is to be passed to Elasticsearch
     * @param Search_Query $originalQuery The original query which would be used later to retrieve final results
     * @param mixed $indices
     */
    public function addToMultisearchStack($id, $indices, $fullQuery, $originalQuery)
    {
        $this->multisearchStack[] = ['id' => $id, 'fullQuery' => $fullQuery, 'originalQuery' => $originalQuery];
        $this->multisearchIndices = $indices;
    }

    /**
     * @return array Raw results retrieved from Elasticsearch. Not to be confused with final transformed results that
     * contains perms and are not cacheable because has source info as PDO objects etc.
     */
    public function triggerMultisearch()
    {
        $results = $this->connection->search($this->multisearchIndices, array_column($this->multisearchStack, 'fullQuery'), [], true);
        $ret = [];
        foreach ($results as $k => $result) {
            $ret[$this->multisearchStack[$k]['id']] = $result;
        }

        return $ret;
    }

    /**
     * @param Search_Query $query
     * @param int $resultStart
     * @param int $resultCount
     * @param string $multisearchId : When provided, it means that the provided query is to be added to an
     * Elasticsearch Multisearch query, stored in a stack in this object, rather than executed as a single query search.
     * @param Search_Elastic_ResultSet $resultFromMultisearch : When provided, it means that the results of
     * a Multisearch has come back and this is a single result just being processed as if a single result had come back.
     * Called from the Search_Query->search().
     * @return Search_Elastic_ResultSet
     */
    public function find(Search_Query_Interface $query, $resultStart, $resultCount, $multisearchId = '', $resultFromMultisearch = '')
    {
        global $prefs;
        /**
         * Sorted search size adjustment (part 1) - This is used to trim large data sets that need
         * to be sorted by date. Sorting can take a very long time on these data sets and the data
         * past a certain number of results are not generally useful.
         *
         * This checks to see if a particular query is cached if we are trying to sort by modification date.
         * If cached, set a time range filter to minimize results.
         */
        $soField = $query->getSortOrder()->getField();
        if ($prefs['unified_trim_sorted_search'] == 'y') {
            $cacheLib = TikiLib::lib("cache");
            $cacheKey = ($query->getExpr()->getSerializedParts());
            // if the sort order is modified or creation date, and there is a trim query cache item,
            // fetch the period filter that it should be filtering by (set in part 2, below) and add it to the search
            if (($soField == "modification_date" || $soField = "creation_date") && $periodFilter = $cacheLib->getCached($cacheKey, "esquery")) {
                $query->filterRange(strtotime("-" . $periodFilter . " days"), time(), $soField);
            }
        }
        /**End of Sorted Search size adjustment (part 1)*/

        if (!empty($resultFromMultisearch)) {
            // Results already gotten from Multisearch so no need to rebuild query
            $result = $resultFromMultisearch;
        } else {
            // Prepare query for search and actually perform search to get results back
            $builder = new Search_Elastic_OrderBuilder($this);
            $orderPart = $builder->build($query->getSortOrder());

            $builder = new Search_Elastic_FacetBuilder($this->facetCount, $this->connection->getVersion() >= 2.0);
            $facetPart = $builder->build($query->getFacets());

            if ($this->connection->getVersion() >= 6.0 && $query->getSortOrder()->getField() === Search_Query_Order::FIELD_SCORE) {
                $builder = new Search_Elastic_RescoreQueryBuilder;
                $rescorePart = $builder->build($query->getExpr());
            } else {
                $rescorePart = [];
            }

            $builder = new Search_Elastic_QueryBuilder($this);
            $builder->setDocumentReader($this->createDocumentReader());
            $queryPart = $builder->build($query->getExpr());

            $postFilterPart = $builder->build($query->getPostFilter()->getExpr());
            if (empty($postFilterPart)) {
                $postFilterPart = [];
            } else {
                $postFilterPart = ["post_filter" => [
                    'fquery' => $postFilterPart,
                ]];
            }

            $indices = [$this->index];

            $foreign = array_map(function ($query) use ($builder) {
                return $builder->build($query->getExpr());
            }, $query->getForeignQueries());

            foreach ($foreign as $indexName => $foreignQuery) {
                if ($this->connection->getIndexStatus($indexName)) {
                    $indices[] = $indexName;
                    if ($this->connection->getVersion() >= 6) {
                        if (!isset($queryPart['query']['dis_max'])) {
                            $queryPart['query']['dis_max']['queries'] = [
                                $queryPart['query']
                            ];
                            foreach ($queryPart['query'] as $key => $_) {
                                if ($key != 'dis_max') {
                                    unset($queryPart['query'][$key]);
                                }
                            }
                        }
                        $queryPart['query']['dis_max']['queries'][] = [
                            'bool' => [
                                'must' => [
                                    $foreignQuery['query'],
                                    [
                                        'match' => ['_index' => $this->connection->resolveAlias($indexName)]
                                    ]
                                ]
                            ]
                        ];
                    } else {
                        $queryPart = ['query' => [
                            'indices' => [
                                'index' => $indexName,
                                'query' => $foreignQuery['query'],
                                'no_match_query' => $queryPart['query'],
                            ],
                        ]];
                    }
                } else {
                    Feedback::error(tr('Federated index %0 not found', $indexName));
                }
            }

            $fullQuery = array_merge(
                $queryPart,
                $orderPart,
                $facetPart,
                $rescorePart,
                $postFilterPart,
                [
                    "from" => $resultStart,
                    "size" => $resultCount,
                    "highlight" => [
                        "tags_schema" => "styled",
                        "fields" => [
                            'contents' => [
                                "number_of_fragments" => 5,
                            ],
                            'file' => [
                                "number_of_fragments" => 5,
                            ],
                        ],
                    ],
                ]
            );

            if ($multisearchId > '') {
                // This is a request to add a query to the Multisearch stack.
                // Results are irrelevant for now as Multisearch hasn't happened yet.
                $this->addToMultisearchStack($multisearchId, $indices, $fullQuery, $query);

                return Search_ResultSet::create([]);
            }
            $result = $this->connection->search($indices, $fullQuery);
        } // END: Prepare query for search and actually perform search to get results back

        $hits = $result->hits;

        if (isset($hits->total->value)) {
            // TODO: new in 7.0 total might be approximate, so consider using track_total_hits:true
            $hits->total = $hits->total->value;
        }

        /**
         * Sorted Search size adjustment (part 2) - Checks to see if the number of results returned
         * are more than 500. If they are, set an approximate period filter to get to 500 results next
         * time that query is run.
         */
        if ($prefs['unified_trim_sorted_search'] == 'y') {
            if ($hits->total >= 500) {
                if (empty($periodFilter)) {
                    //set the default filter to ~6 months if no filter was previosuly set
                    $periodFilter = 180;
                } else {
                    // estimate the filter required to get 500 results
                    $periodFilter = round(500 / $hits->total * $periodFilter);
                }
                $cacheLib->cacheItem($cacheKey, (string) $periodFilter, "esquery");
            }
            // if total hits was less than 300 and a period filter had been set, increase the period filter
            // for the next search
            if ($hits->total < 300 && ! empty($periodFilter)) {
                $periodFilter = round(300 / $hits->total * $periodFilter);
                $cacheLib->cacheItem($cacheKey, (string) $periodFilter, "esquery");
            }
        }
        /** End Sorted Search size adjustment (part 2) */
        $indicesMap = array_combine($indices, $indices);

        $entries = array_map(
            function ($entry) use (& $indicesMap) {
                $data = (array) $entry->_source;

                if (isset($entry->highlight->contents)) {
                    $data['_highlight'] = implode('...', $entry->highlight->contents);
                } elseif (isset($entry->highlight->file)) {
                    $data['_highlight'] = implode('...', $entry->highlight->file);
                } else {
                    $data['_highlight'] = '';
                }
                $data['score'] = round($entry->_score, 2);

                $index = $entry->_index;

                // Make sure we reduce the returned index to something matching what we requested
                // if what was requested is an alias.
                // Note: This only supports aliases where the name is a prefix.
                if (isset($indicesMap[$index])) {
                    $index = $indicesMap[$index];
                } else {
                    foreach ($indicesMap as $candidate) {
                        if (0 === strpos($index, $candidate . '_')) {
                            $indicesMap[$index] = $candidate;
                            $index = $candidate;

                            break;
                        }
                    }
                }

                $data['_index'] = $index;

                return $data;
            },
            $hits->hits
        );

        $resultSet = new Search_Elastic_ResultSet($entries, $hits->total, $resultStart, $resultCount);

        $reader = new Search_Elastic_FacetReader($result);
        foreach ($query->getFacets() as $facet) {
            if ($filter = $reader->getFacetFilter($facet)) {
                $resultSet->addFacetFilter($filter);
            }
        }

        return $resultSet;
    }

    public function scroll(Search_Query_Interface $query)
    {
        $builder = new Search_Elastic_OrderBuilder($this);
        $orderPart = $builder->build($query->getSortOrder());

        $builder = new Search_Elastic_QueryBuilder($this);
        $builder->setDocumentReader($this->createDocumentReader());
        $queryPart = $builder->build($query->getExpr());

        $indices = [$this->index];

        $fullQuery = array_merge(
            $queryPart,
            $orderPart,
            [
                "size" => 100,
                "highlight" => [
                    "fields" => [
                        'contents' => [
                            "number_of_fragments" => 5,
                        ],
                        'file' => [
                            "number_of_fragments" => 5,
                        ],
                    ],
                ],
            ]
        );

        $args = ['scroll' => '5m'];
        $result = $this->connection->search($indices, $fullQuery, $args);
        $scrollId = $result->_scroll_id;

        do {
            foreach ($result->hits->hits as $entry) {
                yield (array) $entry->_source;
            }

            $result = $this->connection->scroll($scrollId, $args);
        } while (count($result->hits->hits) > 0);
    }

    public function getTypeFactory()
    {
        return new Search_Elastic_TypeFactory;
    }

    private function createDocumentReader()
    {
        $connection = $this->connection;
        $index = $this->index;

        return function ($type, $object) use ($connection, $index) {
            static $previous, $content;

            $now = "$index~$type~$object";
            if ($previous === $now) {
                return $content;
            }

            $previous = $now;
            $content = (array) $connection->document($index, $type, $object);

            return $content;
        };
    }

    public function getMatchingQueries(array $document)
    {
        list($type, $object, $document) = $this->generateDocument($document);

        return $this->connection->percolate($this->index, $type, $document);
    }

    public function store($name, Search_Expr_Interface $expr)
    {
        $builder = new Search_Elastic_QueryBuilder($this);
        $builder->setDocumentReader($this->createDocumentReader());
        $doc = $builder->build($expr);

        $this->connection->storeQuery($this->index, $name, $doc);
    }

    public function unstore($name)
    {
        $this->connection->unstoreQuery($this->index, $name);
    }

    public function setFacetCount($count)
    {
        $this->facetCount = (int) $count;
    }

    public function getFieldMapping($field)
    {
        if (empty($this->fieldMappings)) {
            $index = $this->index;

            try {
                $mappings = $this->connection->rawApi("/$index/_mapping/");
            } catch (Search_Elastic_Exception $e) {
                $mappings = false;
            }
            if (is_object($mappings)) {
                $mappings = reset($mappings);
                $mappings = isset($mappings->mappings) ? $mappings->mappings : $mappings; // v2 vs v5
                $mappings = reset($mappings);
                $mappings = isset($mappings->properties) ? $mappings->properties : $mappings; // v2 vs v5
                $this->fieldMappings = $mappings;
            }
        }
        if (isset($this->fieldMappings->$field)) {
            return $this->fieldMappings->$field;
        }

        return new stdClass;
    }

    public function resolveAlias($indexName)
    {
        return $this->connection->resolveAlias($indexName);
    }
}
