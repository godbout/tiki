<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Provider;

use Tiki\MailIn\Action;

class ArticlePutProvider implements ProviderInterface
{
    public function isEnabled()
    {
        global $prefs;

        return $prefs['feature_submissions'] == 'y';
    }

    public function getType()
    {
        return 'article-put';
    }

    public function getLabel()
    {
        return tr('Create or update article');
    }

    public function getActionFactory(array $acc)
    {
        return new Action\DirectFactory('Tiki\MailIn\Action\ArticlePut', [
            'topic' => $acc['article_topicId'],
            'type' => $acc['article_type'],
        ]);
    }
}
