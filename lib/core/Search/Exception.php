<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Exception extends Exception
{
  // add ability to ignore sending the feedback to the user on certain query exceptions when fields are not in the index
  public $suppress_feedback = false;
}
