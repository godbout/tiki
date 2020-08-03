<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Formula_Function_RatingAverage extends Math_Formula_Function
{
    protected $mode = 'avg';

    public function evaluate($element)
    {
        $allowed = [ 'object', 'range', 'ignore', 'keep', 'revote' ];

        if ($extra = $element->getExtraValues($allowed)) {
            $this->error(tr('Unexpected values: %0', implode(', ', $extra)));
        }

        $object = $element->object;

        if (! $object || count($object) != 2) {
            $this->error(tra('Object must be provided and contain two arguments: type and object'));
        }

        $type = $this->evaluateChild($object[0]);
        $object = $this->evaluateChild($object[1]);

        $params = [];

        if ($range = $element->range) {
            if (count($range) == 1) {
                $params['range'] = $this->evaluateChild($range[0]);
            } else {
                $this->error(tra('Invalid range.'));
            }
        }

        if ($revote = $element->revote) {
            if (count($revote) == 1) {
                $params['revote'] = $this->evaluateChild($revote[0]);
            } else {
                $this->error(tra('Invalid revote period.'));
            }
        }

        if ($element->ignore) {
            $params['ignore'] = 'anonymous';
        }

        if ($keep = $element->keep) {
            if ($keep[0] == 'oldest' || $keep[0] == 'latest') {
                $params['keep'] = $keep[0];
            } else {
                $this->error(tra('Expecting "keep" to be "latest" or "oldest"'));
            }
        }

        $ratinglib = TikiLib::lib('rating');

        return $ratinglib->collect($type, $object, $this->mode, $params);
    }
}
