<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use TikiLib;
use Perms;
use Perms_Context;

class BasicAuth extends AbstractBasic {
  protected function validateUserPass($username, $password) {
    global $user;

    if ($username == 'Anonymous') {
      $user = $username;
      $isvalid = true;
    } else {
      list($isvalid, $user) = TikiLib::lib('user')->validate_user($username, $password);
    }

    if ($isvalid) {
      // enforce permissions of the incoming user and their groups
      $permContext = new Perms_Context($user, false);
      $permContext->activatePermanently();
    }

    return $isvalid;
  }
}
