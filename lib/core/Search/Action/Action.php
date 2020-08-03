<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Search_Action_Action
{
    /**
     * Provides the list of values required by the actiion to execute.
     */
    public function getValues();

    public function validate(JitFilter $data);

    public function execute(JitFilter $data);

    public function requiresInput(JitFilter $data);
}
