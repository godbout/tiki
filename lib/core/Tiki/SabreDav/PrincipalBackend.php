<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\DAVACL;
use Sabre\Uri;

use TikiLib;

/**
 * Tiki principal backend
 *
 *
 * This backend assumes all principals are in a single collection. The default collection
 * is 'principals/', but this can be overridden.
 *
 */
class PrincipalBackend extends DAVACL\PrincipalBackend\AbstractBackend implements DAVACL\PrincipalBackend\CreatePrincipalSupport
{

    /**
     * A list of additional fields to support
     *
     * @var array
     */
    protected $fieldMap = [

        /**
         * This property can be used to display the users' real name.
         */
        'name' => '{DAV:}displayname',

        /**
         * This is the users' primary email-address.
         */
        'email' => '{http://sabredav.org/ns}email-address',
    ];

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        global $prefs;

        $principals = [];

        $users = TikiLib::lib('tiki')->list_users(0, -1, 'login_asc');
        foreach ($users['data'] as $user) {
            $uri = self::mapUserToUri($user['login']);

            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($uri);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }

            $principals[] = [
                'uri' => $uri,
                $this->fieldMap['name'] => TikiLib::lib('tiki')->get_user_preference($user['login'], 'realName'),
                $this->fieldMap['email'] => $prefs['login_is_email'] == 'y' && $user['login'] != 'admin' ? $user['login'] : $user['email'],
            ];
        }

        return $principals;
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    public function getPrincipalByPath($path)
    {
        global $prefs;

        $user = null;
        if (preg_match('#principals/(.*)$#', $path, $m)) {
            if (TikiLib::lib('user')->user_exists($m[1])) {
                $user = $m[1];
            }
        }

        if (! $user) {
            return;
        }

        $principal = [
            'id' => $user['userId'],
            'uri' => self::mapUserToUri($user),
            $this->fieldMap['name'] => TikiLib::lib('tiki')->get_user_preference($user, 'realName'),
            $this->fieldMap['email'] => TikiLib::lib('user')->get_user_email($user),
        ];

        return $principal;
    }

    /**
     * Updates one ore more webdav properties on a principal.
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
     * @param string $path
     * @param DAV\PropPatch $propPatch
     */
    public function updatePrincipal($path, DAV\PropPatch $propPatch)
    {
        // noop - we don't allow Tiki user update for now
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @param string $test
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        if (count($searchProperties) == 0) {
            return [];    //No criteria
        }

        $results = [
            'by_name' => [],
            'by_email' => [],
        ];

        $query = 'SELECT uri FROM ' . $this->tableName . ' WHERE ';
        $values = [];
        foreach ($searchProperties as $property => $value) {
            switch ($property) {
                case '{DAV:}displayname':
                    $results['by_name'] = TikiLib::lib('user')->get_users(0, -1, 'login_asc', $value);
                    $results['by_name'] = array_column($results['by_name'], 'login');

                    break;
                case '{http://sabredav.org/ns}email-address':
                    $results['by_email'] = TikiLib::lib('user')->get_users(0, -1, 'login_asc', '', '', false, '', $value);
                    $results['by_email'] = array_column($results['by_email'], 'login');

                    break;
                default:
                    // Unsupported property
                    return [];
            }
        }

        if ($test == 'anyof') {
            $results = array_unique($results['by_name'] + $results['by_email']);
        } else {
            $results = array_intersect($results['by_name'], $results['by_email']);
        }

        $principals = [];
        foreach ($results as $user) {
            $uri = self::mapUserToUri($user);
            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($uri);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }
            $principals[] = $uri;
        }

        return $principals;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     * @return string
     */
    public function findByUri($uri, $principalPrefix)
    {
        $value = null;
        $scheme = null;
        list($scheme, $value) = explode(":", $uri, 2);
        if (empty($value)) {
            return null;
        }

        $uri = null;
        switch ($scheme) {
            case "mailto":
                $user = TikiLib::lib('user')->get_user_by_email($value);
                if ($user) {
                    $uri = self::mapUserToUri($user);
                    // Checking if the principal is in the prefix
                    list($rowPrefix) = Uri\split($uri);
                    if ($rowPrefix !== $principalPrefix) {
                        $uri = null;
                    }
                }

                break;
            default:
                //unsupported uri scheme
                return null;
        }

        return $uri;
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMemberSet($principal)
    {
        // noop - ignore groups for now
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMembership($principal)
    {
        // noop - ignore groups for now
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    public function setGroupMemberSet($principal, array $members)
    {
        // noop - ignore groups for now
    }

    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     * @param MkCol $mkCol
     * @return void
     */
    public function createPrincipal($path, MkCol $mkCol)
    {
        // noop - ignore user creation for now
    }


    public static function mapUriToUser($principalUri)
    {
        if (preg_match('#principals/(.*)$#', $principalUri, $m)) {
            $user = $m[1];
            if (TikiLib::lib('user')->user_exists($user)) {
                return $user;
            }

            throw new DAV\Exception('Principaluri does not exist in Tiki user database.');
        }

        throw new DAV\Exception('Principaluri is in invalid format.');
    }

    public static function mapUserToUri($user)
    {
        return 'principals/' . $user;
    }
}
