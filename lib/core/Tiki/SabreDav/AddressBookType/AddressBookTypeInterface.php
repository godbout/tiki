<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav\AddressBookType;

interface AddressBookTypeInterface
{
    public function isEnabled();
    public function isReadOnly();
    public function getAddressBooks();
    public function getCards($uris);
    public function createCard($cardUri, $cardData);
    public function updateCard($cardUri, $cardData);
    public function deleteCard($cardUri);
}
