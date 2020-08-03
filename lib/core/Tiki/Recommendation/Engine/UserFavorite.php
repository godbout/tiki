<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation\Engine;

use Tiki\Recommendation\Debug\SourceDocument;
use Tiki\Recommendation\Recommendation;

class UserFavorite implements EngineInterface
{
    private $lib;

    public function __construct($unifiedsearch)
    {
        $this->lib = $unifiedsearch;
    }

    public function generate($input)
    {
        assert($input instanceof \Tiki\Recommendation\Input\UserInput);

        $user = $input->getUser();
        $userfavorite = (string) new \Search_Query_Relation('tiki.user.favorite.invert', 'user', $user);
        $previously = (string) new \Search_Query_Relation('tiki.recommendation.obtained.invert', 'user', $user);

        $context = new \Perms_Context($user);

        $query = $this->lib->buildQuery(['searchable' => 'y']);
        $query->setOrder('modification_date_desc');

        $query->setRange(0, 10);

        $query->filterRelation("\"$userfavorite\"");

        $result = $query->search($this->lib->getIndex());
        $content = '';
        foreach ($result as $row) {
            yield new SourceDocument($row['object_type'], $row['object_id'], $row['title']);
            $content .= ' ' . substr($row['contents'], 0, 10000);
        }

        // No need to get more like these to exclude document as it can be done more efficiently using a relation
        // Also more complete as all favorites are excluded, not only those sampled
        $query = $this->lib->buildQuery([]);
        $query->filterRelation("NOT \"$userfavorite\"");
        $query->filterRelation("NOT \"$previously\"");
        $query->filterSimilarToThese([], $content);
        $query->filterIdentifier('y', 'searchable');
        $query->setRange(0, 5);
        $result = $query->search($this->lib->getIndex());

        foreach ($result as $row) {
            yield new Recommendation($row['object_type'], $row['object_id'], $row['title']);
        }
    }
}
