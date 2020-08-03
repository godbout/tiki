<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Action;

class WikiPrepend extends WikiPut
{
    public function getName()
    {
        return tr('Wiki Prepend');
    }

    protected function handleContent($data, $info)
    {
        if ($info) {
            return $data['body'] . "\n" . $info['data'];
        }

        return $data['body'];
    }
}
