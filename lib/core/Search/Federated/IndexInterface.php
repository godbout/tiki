<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Federated;

interface IndexInterface
{
    public function getTransformations();

    public function applyContentConditions(\Search_Query $query, $content);

    public function applySimilarConditions(\Search_Query $query, $type, $object);
}
