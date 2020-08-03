<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Federated;

class TikiIndex implements IndexInterface
{
    private $prefix;
    private $groups;

    public function __construct($urlPrefix, array $groups = [])
    {
        $this->prefix = $urlPrefix;
        $this->groups = $groups;
    }

    public function getTransformations()
    {
        return [
            new UrlPrefixTransform($this->prefix),
        ];
    }

    public function applyContentConditions(\Search_Query $query, $content)
    {
        $query->filterContent('y', 'searchable');
        $query->filterContent($content, ['title', 'contents']);

        $this->applyRaw($query);
    }

    public function applySimilarConditions(\Search_Query $query, $type, $object)
    {
        $query->filterSimilar($type, $object);

        $this->applyRaw($query);
    }

    private function applyRaw($query)
    {
        $unified = \TikiLib::lib('unifiedsearch');
        $unified->initQueryBase($query, false);

        $applyAs = $this->groups;
        if (empty($applyAs)) {
            $unified->initQueryPermissions($query);
        } else {
            $query->filterPermissions($applyAs);
        }
    }
}
