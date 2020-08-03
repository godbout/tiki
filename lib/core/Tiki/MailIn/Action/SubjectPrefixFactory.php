<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Action;

use Tiki\MailIn\Account;
use Tiki\MailIn\Exception\MailInException;
use Tiki\MailIn\Source\Message;

class SubjectPrefixFactory implements FactoryInterface
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function createAction(Account $account, Message $message)
    {
        $subject = $message->getSubject();

        foreach ($this->config as $prefix => $factory) {
            if (empty($prefix) || strpos($subject, $prefix) === 0) {
                $subject = trim(substr($subject, strlen($prefix)));
                $message->setSubject($subject);

                return $factory->createAction($account, $message);
            }
        }

        throw new MailInException(tr("Unable to find suitable action."));
    }
}
