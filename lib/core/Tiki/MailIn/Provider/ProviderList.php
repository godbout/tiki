<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Provider;

class ProviderList
{
    private $list = [];

    public function addProvider(ProviderInterface $provider)
    {
        $this->list[] = $provider;
    }

    public function getList()
    {
        usort($this->list, function ($a, $b) {
            return strcmp($a->getLabel(), $b->getLabel());
        });

        return $this->list;
    }
}
