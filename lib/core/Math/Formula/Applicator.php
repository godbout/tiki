<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Math_Formula_Applicator
{
	function add($another);
  function sub($another);
  function mul($another);
  function div($another);
  function floor();
  function ceil();
  function round($decimals);
  function lessThan($another);
  function moreThan($another);
  function clone($number);
}
