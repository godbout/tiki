<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\VObject;

use TikiLib;
use Perms;

/**
 * Tiki database CardDAV backend.
 *
 * This backend is used to store address book data in Tiki MySQL database.
 * It allows provides support for 2 system-level address books:
 * 1. webmail contacts
 * 2. user system data
 */
class CardDAVBackend extends CardDAV\Backend\AbstractBackend
{
    /**
     * Returns the list of addressbooks for a specific user.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getAddressBooksForUser($principalUri)
    {
        global $prefs, $user;

        $principal = PrincipalBackend::mapUriToUser($principalUri);

        if ($principal != $user) {
            throw new DAV\Exception\Forbidden("You don't have permission to view this user's address books.");
        }

        $result = [];

        if ($prefs['feature_webmail'] === 'y') {
            $result[] = [
                'id' => "webmail.$user",
                'uri' => 'webmail',
                'principaluri' => PrincipalBackend::mapUserToUri($user),
                '{DAV:}displayname' => 'Webmail Contacts',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'Webmail contacts managed by Tiki.',
            ];
        }

        $users = TikiLib::lib('user')->list_all_users();
        if ($users) {
            $result[] = [
                'id' => "system.$user",
                'uri' => 'system',
                'principaluri' => PrincipalBackend::mapUserToUri($user),
                '{DAV:}displayname' => 'System Users',
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => 'Tiki system users.',
            ];
        }


        $address_books = TikiLib::lib('addressbook')->list_address_books($user);
        foreach ($address_books as $row) {
            $result[] = [
                'id' => $row['addressBookId'],
                'uri' => $row['uri'],
                'principaluri' => PrincipalBackend::mapUserToUri($user),
                '{DAV:}displayname' => $row['name'],
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => $row['description'],
            ];
        }

        return $result;
    }

    /**
     * Updates properties for an address book.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string               $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch)
    {
        if (intval($addressBookId) == 0) {
            throw new DAV\Exception\Forbidden("Address book is read-only.");
        }

        $this->enforceAddressBookPermisions($addressBookId);

        $supportedProperties = [
            '{DAV:}displayname',
            '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description',
        ];

        $propPatch->handle($supportedProperties, function ($mutations) use ($addressBookId) {
            $updates = [];
            foreach ($mutations as $property => $newValue) {
                switch ($property) {
                    case '{DAV:}displayname':
                        $updates['name'] = $newValue;
                        break;
                    case '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description':
                        $updates['description'] = $newValue;
                        break;
                }
            }
            TikiLib::lib('addressbook')->update_address_book($addressBookId, $updates);
            return true;
        });
    }

    /**
     * Creates a new address book.
     *
     * @param string $principalUri
     * @param string $url          just the 'basename' of the url
     * @param array  $properties
     *
     * @return int Last insert id
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
        global $user;

        $principal = PrincipalBackend::mapUriToUser($principalUri);

        if ($principal != $user) {
            throw new DAV\Exception\Forbidden("You don't have permission to create address books for this user.");
        }

        $values = [
            'name' => null,
            'description' => null,
            'user' => $user,
            'uri' => $url,
        ];

        foreach ($properties as $property => $newValue) {
            switch ($property) {
                case '{DAV:}displayname':
                    $values['name'] = $newValue;
                    break;
                case '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description':
                    $values['description'] = $newValue;
                    break;
                default:
                    throw new DAV\Exception\BadRequest('Unknown property: '.$property);
            }
        }

        return TikiLib::lib('addressbook')->update_address_book(0, $values);
    }

    /**
     * Deletes an entire addressbook and all its contents.
     *
     * @param int $addressBookId
     */
    public function deleteAddressBook($addressBookId)
    {
        if (intval($addressBookId) == 0) {
            throw new DAV\Exception\Forbidden("Address book is read-only.");
        }

        $this->enforceAddressBookPermisions($addressBookId);

        TikiLib::lib('addressbook')->delete_address_book($addressBookId);
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressBookId
     *
     * @return array
     */
    public function getCards($addressBookId)
    {
        global $user;
        if (preg_match('/^(webmail|system).(.*)$/', $addressBookId, $m)) {
            $type = $m[1];
            $principal = $m[2];
        } else {
            $type = 'custom';
            $principal = null;
        }
        if ($principal && $principal != $user) {
            throw new DAV\Exception\Forbidden("You don't have permission to access this address book.");
        }
        $result = [];
        switch($type) {
            case 'webmail':
                $contacts = TikiLib::lib('contact')->list_contacts($user);
                foreach ($contacts as $row) {
                    $data = $this->constructCardDataFromContact($row);
                    $result[] = [
                        'id' => $row['contactId'],
                        'uri' => $row['contactId'].'.vcf',
                        'lastmodified' => time(),
                        'etag' => '"'.md5($data).'"',
                        'size' => strlen($data),
                    ];
                }
                break;
            case 'system':
                $users = TikiLib::lib('user')->get_users();
                foreach ($users['data'] as $userInfo) {
                    $data = $this->constructCardDataFromUser($userInfo);
                    $result[] = [
                        'id' => $userInfo['login'],
                        'uri' => $userInfo['login'].'.vcf',
                        'lastmodified' => time(),
                        'etag' => '"'.md5($data).'"',
                        'size' => strlen($data),
                    ];
                }
                break;
            case 'custom':
                $this->enforceAddressBookPermisions($addressBookId);
                $cards = TikiLib::lib('addressbook')->list_cards($addressBookId);
                foreach ($cards as $card) {
                    $result[] = [
                        'id' => $card['addressCardId'],
                        'uri' => $card['uri'],
                        'lastmodified' => $card['lastmodified'],
                        'etag' => '"'.$card['etag'].'"',
                        'size' => $card['size'],
                    ];
                }
        }
        return $result;
    }

    /**
     * Returns a specific card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     *
     * @return array
     */
    public function getCard($addressBookId, $cardUri)
    {
        return array_shift($this->getMultipleCards($addressBookId, [$cardUri]));
    }

    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId
     * @param array $uris
     *
     * @return array
     */
    public function getMultipleCards($addressBookId, array $uris)
    {
        global $user;
        if (preg_match('/^(webmail|system).(.*)$/', $addressBookId, $m)) {
            $type = $m[1];
            $principal = $m[2];
            $uris = array_map(function($uri){
                return str_replace('.vcf', '', $uri);
            }, $uris);
        } else {
            $type = 'custom';
            $principal = null;
        }
        if ($principal && $principal != $user) {
            throw new DAV\Exception\Forbidden("You don't have permission to access this address book.");
        }
        $result = [];
        switch($type) {
            case 'webmail':
                $rows = TikiLib::lib('contact')->list_contacts($user, -1, -1, 'contactId_asc', null, false, '', 'email', $uris);
                foreach ($rows as $row) {
                    $data = $this->constructCardDataFromContact($row);
                    $result[] = [
                        'id' => $row['contactId'],
                        'uri' => $row['contactId'].'.vcf',
                        'carddata' => $data,
                        'lastmodified' => time(),
                        'etag' => '"'.md5($data).'"',
                        'size' => strlen($data),
                    ];
                }
                break;
            case 'system':
                $users = TikiLib::lib('user')->get_users();
                foreach ($users['data'] as $userInfo) {
                    if (! in_array($userInfo['login'], $uris)) {
                        continue;
                    }
                    $data = $this->constructCardDataFromUser($userInfo);
                    $result[] = [
                        'id' => $userInfo['login'],
                        'uri' => $userInfo['login'].'.vcf',
                        'carddata' => $data,
                        'lastmodified' => time(),
                        'etag' => '"'.md5($data).'"',
                        'size' => strlen($data),
                    ];
                }
                break;
            case 'custom':
                $this->enforceAddressBookPermisions($addressBookId);
                $cards = TikiLib::lib('addressbook')->list_cards($addressBookId, -1, -1, $uris);
                foreach ($cards as $card) {
                    $result[] = [
                        'id' => $card['addressCardId'],
                        'uri' => $card['uri'],
                        'carddata' => $card['carddata'],
                        'lastmodified' => $card['lastmodified'],
                        'etag' => '"'.$card['etag'].'"',
                        'size' => $card['size'],
                    ];
                }
        }
        return $result;
    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     * @param string $cardData
     *
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData)
    {
        $this->enforceAddressBookPermisions($addressBookId);
        $data = [
            'carddata' => $cardData,
            'uri' => $cardUri,
            'addressBookId' => $addressBookId,
            'lastmodified' => time(),
            'size' => strlen($cardData),
            'etag' => md5($cardData)
        ];
        TikiLib::lib('addressbook')->create_card($data);
        return '"'.$data['etag'].'"';
    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     * @param string $cardData
     *
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        $this->enforceAddressBookPermisions($addressBookId);
        $data = [
            'carddata' => $cardData,
            'lastmodified' => time(),
            'size' => strlen($cardData),
            'etag' => md5($cardData)
        ];
        TikiLib::lib('addressbook')->update_card($addressBookId, $cardUri, $data);
        return '"'.$data['etag'].'"';
    }

    /**
     * Deletes a card.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     *
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri)
    {
        $this->enforceAddressBookPermisions($addressBookId);
        return TikiLib::lib('addressbook')->delete_card($addressBookId, $cardUri);
    }

    private function enforceAddressBookPermisions($addressBookId) {
        global $user;
        if (intval($addressBookId) == 0) {
            throw new DAV\Exception\Forbidden("Address book is read-only.");
        }
        $addressbook = TikiLib::lib('addressbook')->get_address_book($addressBookId);
        if (! $addressbook || $addressbook['user'] != $user) {
            throw new DAV\Exception\Forbidden("You don't have permission to modify this address book.");
        }
    }

    private function constructCardDataFromContact($contact) {
        global $url_host;
        $vcard = new VObject\Component\VCard([
            'UID'      => "tiki-$url_host-webmail-".$contact['contactId'],
            'FN'       => $contact['firstName'].' '.$contact['lastName'],
            'EMAIL'    => $contact['email'],
            'N'        => [$contact['lastName'], $contact['firstName'], '', '', ''],
            'NICKNAME' => $contact['nickname'],
        ]);
        return $vcard->serialize();
    }

    private function constructCardDataFromUser($userInfo) {
        global $url_host;
        $tikilib = TikiLib::lib('tiki');
        $user = $userInfo['login'];
        $realName = $tikilib->get_user_preference($user, 'realName', '');
        $nameParts = explode(' ', $realName, 2);
        $email = TikiLib::lib('user')->get_user_email($user);
        $vcard = new VObject\Component\VCard([
            'UID'   => "tiki-$url_host-user-$user",
            'FN'    => $realName,
            'EMAIL' => $email,
            'N'     => [$nameParts[1], $nameParts[0], '', '', ''],
        ]);
        $gender = $tikilib->get_user_preference($user, 'gender', '');
        if ($gender) {
            $vcard->GENDER = $gender;
        }
        $lang = $tikilib->get_language($user);
        if ($lang) {
            $vcard->LANG = $lang;
        }
        $country = $tikilib->get_user_preference($user, 'country', 'Other');
        if ($country && $country != 'Other') {
            $vcard->ADR = ['', '', '', '', '', '', $country];
            $lat = $tikilib->get_user_preference($user, 'lat', '');
            $lon = $tikilib->get_user_preference($user, 'lon', '');
            if ($lat && $lon) {
                $vcard->ADR['GEO'] = "$lat,$lon";
            }
        }
        $homePage = $tikilib->get_user_preference($user, 'homePage', '');
        if ($homePage) {
            $vcard->URL = $homePage;
        }
        $avatar = $tikilib->get_user_avatar_inline($user);
        if ($avatar) {
            $vcard->PHOTO = $avatar;
        }
        return $vcard->serialize();
    }
}
