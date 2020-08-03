<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Search_Expr_And as AndX;
use Search_Expr_Distance as Distance;
use Search_Expr_ImplicitPhrase as ImplicitPhrase;
use Search_Expr_Initial as Initial;
use Search_Expr_MoreLikeThis as MoreLikeThis;
use Search_Expr_Not as NotX;
use Search_Expr_Or as OrX;
use Search_Expr_Range as Range;
use Search_Expr_Token as Token;

class Search_Elastic_QueryBuilder
{
    private $factory;
    private $documentReader;
    private $index;

    public function __construct(Search_Elastic_Index $index = null)
    {
        $this->factory = new Search_Elastic_TypeFactory;
        $this->documentReader = function ($type, $object) {
            return null;
        };
        $this->index = $index;
    }

    public function build(Search_Expr_Interface $expr)
    {
        $query = $expr->traverse($this);

        if (count($query) && isset($query['bool']) && empty($query['bool'])) {
            return [];
        }

        $query = ["query" => $query];

        return $query;
    }

    public function setDocumentReader($callback)
    {
        $this->documentReader = $callback;
    }

    public function __invoke($callback, $node, $childNodes)
    {
        if ($node instanceof ImplicitPhrase) {
            $node = $node->getBasicOperator();
        }

        if ($node instanceof Token) {
            return $this->handleToken($node);
        } elseif (count($childNodes) === 1 && ($node instanceof AndX || $node instanceof OrX)) {
            return reset($childNodes)->traverse($callback);
        } elseif ($node instanceof OrX) {
            $inner = array_map(
                function ($expr) use ($callback) {
                    return $expr->traverse($callback);
                },
                $childNodes
            );

            return [
                'bool' => [
                    'should' => $this->flatten($inner, 'should'),
                    "minimum_should_match" => 1,
                ],
            ];
        } elseif ($node instanceof AndX) {
            $not = [];
            $inner = array_map(
                function ($expr) use ($callback) {
                    return $expr->traverse($callback);
                },
                $childNodes
            );

            $inner = array_filter(
                $inner,
                function ($part) use (& $not) {
                    // Only merge in the single-part NOT
                    if (isset($part['bool']['must_not']) && count($part['bool']) == 1) {
                        $not = array_merge($not, $part['bool']['must_not']);

                        return false;
                    }

                    return true;
                }
            );
            $inner = $this->flatten($inner, 'must');
            if (count($inner) == 1 && isset($inner[0]['bool'])) {
                $base = $inner[0]['bool'];
                if (! isset($base['must_not'])) {
                    $base['must_not'] = [];
                }

                $base['must_not'] = array_merge($base['must_not'], $not);

                return [
                    'bool' => array_filter($base),
                ];
            }

            return [
                    'bool' => array_filter(
                        [
                            'must' => $inner,
                            'must_not' => $not,
                        ]
                    ),
                ];
        } elseif ($node instanceof NotX) {
            $inner = array_map(
                function ($expr) use ($callback) {
                    return $expr->traverse($callback);
                },
                $childNodes
            );
            if (count($inner) == 1 && isset($inner[0]['bool']) && isset($inner[0]['bool']['must_not'])) {
                return [
                    'bool' => [
                        'must' => $inner[0]['bool']['must_not'],
                    ],
                ];
            }

            return [
                    'bool' => [
                        'must_not' => $inner,
                    ],
                ];
        } elseif ($node instanceof Initial) {
            return [
                'match_phrase_prefix' => [
                    $this->getNodeField($node) => [
                        "query" => $this->getTerm($node),
                        "boost" => $node->getWeight(),
                    ],
                ],
            ];
        } elseif ($node instanceof Range) {
            return [
                'range' => [
                    $this->getNodeField($node) => [
                        "gte" => $this->getTerm($node->getToken('from')),
                        "lte" => $this->getTerm($node->getToken('to')),
                        "boost" => $node->getWeight(),
                    ],
                ],
            ];
        } elseif ($node instanceof MoreLikeThis) {
            $type = $node->getObjectType();
            $object = $node->getObjectId();

            $content = $node->getContent() ?: $this->getDocumentContent($type, $object);

            return [
                'more_like_this' => [
                    'fields' => [$this->getNodeField($node) ?: 'contents'],
                    'like' => $content,
                    'boost' => $node->getWeight(),
                ],
            ];
        } elseif ($node instanceof Distance) {
            return [
                'geo_distance' => [
                    'distance' => $node->getDistance(),
                    $this->getNodeField($node) => [
                        'lat' => $node->getLat(),
                        'lon' => $node->getLon(),
                    ]
                ]
            ];
        }

        throw new Exception(tr('Feature not supported.'));
    }

    private function flatten($list, $type)
    {
        // Only merge when alone, should queries contain the 'minimum_should_match' attribute
        $limit = ($type == 'should') ? 2 : 1;

        $out = [];
        foreach ($list as $entry) {
            if (isset($entry['bool'][$type]) && count($entry['bool']) === $limit) {
                $out = array_merge($out, $entry['bool'][$type]);
            } else {
                $out[] = $entry;
            }
        }

        return $out;
    }

    private function getTerm($node)
    {
        $value = $node->getValue($this->factory);
        $value = $value->getValue();
        if ($node->getType() === 'timestamp') {
            return $value;
        }

        return mb_strtolower($value);
    }

    private function handleToken($node)
    {
        $value = $node->getValue($this->factory)->getValue();
        $mapping = $this->index ? $this->index->getFieldMapping($node->getField()) : new stdClass;
        if ($value === '') {
            if (isset($mapping->type) && $mapping->type === 'date') {
                return [
                    "bool" => [
                        "must_not" => [
                            [
                                "exists" => ["field" => $this->getNodeField($node)]
                            ]
                        ]
                    ]
                ];
            }

            return [
                    "bool" => [
                        "must_not" => [
                            [
                                "wildcard" => [$this->getNodeField($node) => "*"]
                            ]
                        ]
                    ]
                ];
        }
        if (isset($mapping->type) && $mapping->type === 'float') {
            $value = (float)$value;
        }
        if ($node->getType() == 'identifier') {
            return ["match" => [
                $this->getNodeField($node) => [
                    "query" => $value,
                    "operator" => "and",
                ],
            ]];
        } elseif ($node->getType() == 'multivalue') {
            return ["match" => [
                $this->getNodeField($node) => [
                    "query" => reset($value),
                    "operator" => "and",
                ],
            ]];
        } elseif ($node->getType() == 'plaintext' && strstr($value, '*')) {
            return ["wildcard" => [
                $this->getNodeField($node) => strtolower($value),
            ]];
        } elseif ($node->getField() == '_index' && $this->index && $resolvedIndex = $this->index->resolveAlias($value)) {
            return [ "match" => [
                "_index" => [
                    "query" => $resolvedIndex,
                    "operator" => "and",
                ],
            ]];
        }

        return ["match" => [
                $this->getNodeField($node) => [
                    "query" => mb_strtolower($value),
                    "boost" => $node->getWeight(),
                    "operator" => "and",
                ],
            ]];
    }

    private function getDocumentContent($type, $object)
    {
        $cb = $this->documentReader;
        $document = $cb($type, $object);

        if (isset($document['contents'])) {
            return $document['contents'];
        }

        return '';
    }

    private function getNodeField($node)
    {
        global $prefs;
        $field = $node->getField();
        $mapping = $this->index ? $this->index->getFieldMapping($field) : new stdClass;
        if ((empty($mapping) || empty((array)$mapping)) && $prefs['search_error_missing_field'] === 'y') {
            if (preg_match('/^tracker_field_/', $field)) {
                $msg = tr('Field %0 does not exist in the current index. Please check field permanent name and if you have any items in that tracker.', $field);
                if ($prefs['unified_exclude_nonsearchable_fields'] === 'y') {
                    $msg .= ' ' . tr('You have disabled indexing non-searchable tracker fields. Check if this field is marked as searchable.');
                }
            } else {
                $msg = tr('Field %0 does not exist in the current index. If this is a tracker field, the proper syntax is tracker_field_%0.', $field, $field);
            }
            $e = new Search_Elastic_QueryParsingException($msg);
            if ($field == 'tracker_id') {
                $e->suppress_feedback = true;
            }

            throw $e;
        }

        return $field;
    }
}
