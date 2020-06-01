<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

interface AddressBookTypeInterface
{
  function isEnabled();
  function isReadOnly();
	function getAddressBooks();
  function getCards($uris);
  function createCard($cardUri, $cardData);
  function updateCard($cardUri, $cardData);
  function deleteCard($cardUri);
}
