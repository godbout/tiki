<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Recommendation\Input;

class UserInput
{
    private $user;

    public function __construct($username)
    {
        $this->user = $username;
    }

    public function getUser()
    {
        return $this->user;
    }
}
