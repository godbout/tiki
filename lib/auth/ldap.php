<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/*
 *  Class that adds LDAP Authentication to Tiki and aids Tiki to get User/Group Information
 *  from a LDAP directory
 */

use Zend\Ldap\Filter;
use Zend\Ldap\Ldap;
use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Collection\DefaultIterator as LdapCollectionIterator;

class TikiLdapLib
{

	// var to hold a established connection
	/** @var Ldap */
	protected $ldaplink = null;

	// var for ldap configuration parameters
	protected $options = [
		'host' => 'localhost',
		'port' => null,
		'version' => 3,
		'starttls' => false,
		'useSsl' => false,
		'baseDn' => '',
		'filter' => '(objectClass=*)',
		'scope' => 'sub',
		'bind_type' => 'default',
		'username' => '',
		'password' => '',
		'userdn' => '',
		'useroc' => 'inetOrgPerson',
		'userattr' => 'cn',
		'fullnameattr' => '',
		'emailattr' => 'mail',
		'countryattr' => '',
		'groupdn' => '',
		'groupattr' => 'gid',
		'groupoc' => 'groupOfNames',
		'groupnameattr' => '',
		'groupdescattr' => '',
		'groupmemberattr' => '',
		'groupmemberisdn' => true,
		'usergroupattr' => '',
		'groupgroupattr' => '',
		'debug' => false
	];

	protected $logslib = null;

	/**
	 * @var array The user attributes
	 */
	protected $user_attributes = null;

	// Constructor
	public function __construct($options)
	{
		// debug setting
		$logslib = TikiLib::lib('logs');
		if (isset($options['debug']) && ($options['debug'] === true || $options['debug'] == 'y' )&& ($logslib instanceof LogsLib)) {
			$this->options['debug'] = true;
			$this->logslib = $logslib;
		}
		// Configure the connection

		// host can be a list of hostnames.
		// It is easier to create URIs because if we use ssl, we have to create a URI
		if (isset($options['host']) && ! empty($options['host'])) {
			$h = $options['host'];
		} else { // use default
			$h = $this->options['host'];
		}

		$t = preg_split('#[\s,]#', $h);
		if (isset($options['useSsl']) && ($options['useSsl'] == 'y' || $options['useSsl'] === true)) {
			$prefix = 'ldaps://';
			$port = 636;
		} else {
			$prefix = 'ldap://';
			$port = 389;
		}
		if (isset($options['port']) && ! empty($options['port'])) {
			$port = (int)$options['port'];
		}

		$this->options['port'] = $port; // its save to set port in URI

		$this->options['host'] = [];
		foreach ($t as $h) {
			if (preg_match('#^ldaps?://#', $h)) { // entry is already URI
				$this->options['host'] = $h;
			} else {
				$this->options['host'] = $h;
			}
		}

		if (isset($options['useStartTls']) && ! empty($options['useStartTls'])) {
			$this->options['useStartTls'] = ($options['useStartTls'] === true || $options['useStartTls'] == 'y');
		}

		if (isset($options['groupmemberisdn']) && ! empty($options['groupmemberisdn'])) {
			$this->options['groupmemberisdn'] = ($options['groupmemberisdn'] === true || $options['groupmemberisdn'] == 'y');
		}

		// only string checking fo these ones
		foreach (['baseDn', 'username', 'password', 'userdn', 'useroc', 'userattr',
				'fullnameattr', 'emailattr', 'groupdn', 'groupattr', 'groupoc', 'groupnameattr',
				'groupdescattr', 'groupmemberattr', 'usergroupattr', 'groupgroupattr', 'binddn', 'bindpw'] as $n) {
			if (isset($options[$n]) && ! empty($options[$n])) {
				$this->options[$n] = $options[$n];
			}
		}

		if (empty($this->options['groupgroupattr'])) {
			$this->options['groupgroupattr'] = $this->options['usergroupattr'];
		}

		if (isset($options['password'])) {
			$this->options['bindpw'] = $options['password'];
		}

		if (isset($options['scope']) && ! empty($options['scope'])) {
			switch ($options['scope']) {
				case 'sub':
				case 'one':
				case 'base':
					$this->options['scope'] = $options['scope'];
					break;

				default:
					break;
			}
		}

		if (isset($options['bind_type']) && ! empty($options['bind_type'])) {
			switch ($options['bind_type']) {
				case 'ad':
				case 'ol':
				case 'full':
				case 'plain':
				case 'explicit':
					$this->options['bind_type'] = $options['bind_type'];
					break;

				default:
					break;
			}
		}
	}
	// End public function TikiLdapLib($options)

	public function __destruct()
	{
		unset($this->ldaplink);
	}

	// Do a ldap bind
	public function bind($reconnect = false)
	{
		global $prefs;

		// Force the reconnection
		if ($this->ldaplink instanceof Ldap && $this->ldaplink->getBoundUser() !== false) {
			if ($reconnect === true) {
					$this->ldaplink->disconnect();
			} else {
					return LdapException::LDAP_SUCCESS; // do not try to reconnect since this may lead to huge timeouts
			}
		}

		// Set the bindpw with the options['password']
		if ($this->options['bind_type'] != 'explicit') {
			$this->options['bindpw'] = $this->options['password'];
		}

		$user = $this->options['username'];
		switch ($this->options['bind_type']) {
			case 'ad': // active directory
				preg_match_all('/\s*,?dc=\s*([^,]+)/i', $this->options['baseDn'], $t);
				$this->options['binddn'] = $user . '@';

				if (isset($t[1]) && is_array($t[1])) {
					foreach ($t[1] as $domainpart) {
						$this->options['binddn'] .= $domainpart . '.';
					}
					// cut trailing dot
					$this->options['binddn'] = substr($this->options['binddn'], 0, -1);
				}
				// set referrals to 0 to avoid LDAP_OPERATIONS_ERROR
				$this->options['options']['LDAP_OPT_REFERRALS'] = 0;
				// use user@domain for binding
				$this->options['tryUsernameSplit'] = false;
				break;

			case 'plain': // plain username
				$this->options['binddn'] = $user;
				break;

			case 'full':
				$this->options['binddn'] = $this->user_dn($user);
				break;

			case 'ol': // openldap
				$this->options['binddn'] = 'cn=' . $user . ',' . $prefs['auth_ldap_basedn'];
				break;

			case 'default':
				// Anonymous binding
				$this->options['binddn'] = '';
				$this->options['bindpw'] = '';
				break;

			case 'explicit':
				break;

			default:
				$this->add_log('ldap', 'Error: Invalid "bind_type" value "' . $this->options['bind_type'] . '".');
				die;
		}

		$this->add_log(
			'ldap',
			'Connect Host: ' . implode($this->options['host']) . '. Binddn: ' . $this->options['binddn'] . ' at line ' . __LINE__ . ' in ' . __FILE__
		);

		$permittedOptions = [
			'host',
			'port',
			'useSsl',
			'username',
			'password',
			'bindRequiresDn',
			'baseDn',
			'accountCanonicalForm',
			'accountDomainName',
			'accountDomainNameShort',
			'accountFilterFormat',
			'allowEmptyPassword',
			'useStartTls',
			'optReferrals',
			'tryUsernameSplit',
			'networkTimeout',
		];

		$options = [];
		//create options array to handle it to \Zend\Ldap\Ldap
		foreach ($permittedOptions as $o) {
			if (isset($this->options[$o])) {
				$options[$o] = $this->options[$o];
			}
		}

		try {
			$this->ldaplink = new Ldap($options);
			$this->ldaplink->bind($this->options['binddn'], $this->options['bindpw']);
		} catch (LdapException $e) {
			if ($prefs['auth_ldap_debug'] == 'y') {
				$this->add_log('ldap', 'Error: ' . $e->getMessage() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			}
			return $e->getCode();
		}

		return LdapException::LDAP_SUCCESS;
	} // End bind()



	// return information about user attributes
	public function get_user_attributes($force_reload = false)
	{
		if ($force_reload) {
			unset($this->user_attributes);
		}

		if (! empty($this->user_attributes)) {
			return $this->user_attributes;
		}

		$userdn = $this->user_dn();

		// ensure we have a connection to the ldap server
		if ($this->bind() !== LdapException::LDAP_SUCCESS) {
			//@todo fix this error since getMessage no longer works
			$this->add_log('ldap', 'Reuse of ldap connection failed: ' . $this->ldaplink->getLastError() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		// todo: only fetch needed attributes

		//A non-existing user may not return ldaplink->getEntry (found bug on windows server), if not found, user input incorrect username/password
		try {
			$searchresult = $this->ldaplink->search("(objectClass=*)", $userdn, Ldap::SEARCH_SCOPE_BASE, [], null);
			$searchresult->getInnerIterator()->setAttributeNameTreatment(LdapCollectionIterator::ATTRIBUTE_NATIVE);
			$entry = $searchresult->getFirst();
		} catch (LdapException $e) {
			$entry = null;
		}

		if ($force_reload || is_null($entry)) { // wrong userdn. So we have to search
			// prepare Search Filter
			$filter = Filter::equals($this->options['userattr'], $this->options['username']);
			$this->add_log('ldap', 'Searching for user information with filter: ' . $filter->toString() . ' at line ' . __LINE__ . ' in ' . __FILE__);

			try {
				$searchresult = $this->ldaplink->search($filter, $this->userbase_dn(), $this->options['scope']);
				$searchresult->getInnerIterator()->setAttributeNameTreatment(LdapCollectionIterator::ATTRIBUTE_NATIVE);
			} catch (LdapException $e) {
				$this->add_log('ldap', 'Search failed: ' . $e->getMessage() . ' at line ' . __LINE__ . ' in ' . __FILE__);
				return false;
			}

			if ($searchresult->count() != 1) {
				$this->add_log('ldap', 'Error: Search returned ' . $searchresult->count() . ' entries' . ' at line ' . __LINE__ . ' in ' . __FILE__);
				return false;
			}
			// get first entry
			$entry = $searchresult->getFirst();
		}

		$this->user_attributes = $this->parseLdapAttributes($entry);
		if (empty($this->user_attributes)) {
			$this->add_log('ldap', 'Error fetching user attributes at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		return $this->user_attributes;
	} // End: public function get_user_attributes()

	// Request all users attributes
	public function get_all_users_attributes()
	{
		// ensure we have a connection to the ldap server
		if ($this->bind() !== LdapException::LDAP_SUCCESS) {
			$this->add_log('ldap', 'Reuse of ldap connection failed: ' . $this->ldaplink->getLastError() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		// Prepare Search Filter
		$filter = Filter::equals('objectclass', $this->options['useroc']);

		$this->add_log('ldap', 'Searching for user information with filter: ' . $filter->toString() . ' at line ' . __LINE__ . ' in ' . __FILE__);

		try {
			$searchresult = $this->ldaplink->search($filter, $this->userbase_dn(), $this->options['scope']);
			$searchresult->getInnerIterator()->setAttributeNameTreatment(LdapCollectionIterator::ATTRIBUTE_NATIVE);
		} catch (LdapException $e) {
			$this->add_log('ldap', 'Search failed: ' . $e->getMessage() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		if ($searchresult->count() < 1) {
			$this->add_log('ldap', 'Error: Search returned ' . $searchresult->count() . ' entries' . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		$entries = $searchresult->toArray();
		$users_attributes = [];

		foreach ($entries as $entry) {
			$users_attributes[] = $this->parseLdapAttributes($entry);
		}

		return ($users_attributes);
	} // End: public function get_user_attributes()

	// return dn of all groups a user belongs to
	public function get_groups($force_reload = false)
	{
		$this->get_user_attributes($force_reload);

		// ensure we have a connection to the ldap server
		if ($this->bind() !== LdapException::LDAP_SUCCESS) {
			$this->add_log('ldap', 'Reuse of ldap connection failed: ' . $this->ldaplink->getLastError() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		$filter1 = Filter::equals('objectClass', $this->options['groupoc']);

		if (! empty($this->options['groupmemberattr'])) {
			// get membership from group information
			if ($this->options['groupmemberisdn']) {
				if ($this->user_attributes['dn'] == null) {
					return false;
				}
				$filter2 = Filter::equals($this->options['groupmemberattr'], $this->user_dn()) ;
			} else {
				$filter2 = Filter::equals($this->options['groupmemberattr'], $this->options['username']);
			}
			$filter = Filter::andFilter($filter1, $filter2);
		} elseif (! empty($this->options['usergroupattr'])) {
			// get membership from user information

			if ($this->options['usergroupattr'] === 'distinguishedName') {
				// get membership from user DN

				// split DN into RDN strings
				$dn_string = $this->user_attributes[$this->options['usergroupattr']];
				$rdn_strings = explode(',', $dn_string);

				// add value of RDNs with OU type
				$ugi = [];
				foreach ($rdn_strings as $rdn_string) {
					// split RDN string in type and value
					$rdn_parts = explode('=', $rdn_string, 2);
					$rdn_type = $rdn_parts[0];
					$rdn_value = $rdn_parts[1];

					// add RDN value if type is OU
					if (strtoupper($rdn_type) === 'OU') {
						$ugi[] = $rdn_value;
					}
				}
			} else {
				$ugi = &$this->user_attributes[$this->options['usergroupattr']];
			}

			if (! empty($ugi)) {
				if (! is_array($ugi)) {
					$ugi = [$ugi];
				}

				if (count($ugi) == 1) { // one gid
					$filter3 = Filter::equals($this->options['groupgroupattr'], $ugi[0]);
				} else { // mor gids
					$filtertmp = [];
					foreach ($ugi as $g) {
						$filtertmp[] = Filter::equals($this->options['groupgroupattr'], $g);
					}

					$filter3 = new Filter\OrFilter($filtertmp);
				}

				$filter = Filter::andFilter($filter1, $filter3);
			} else { // User has no group
				return [];
			}
		} else {
			// not possible to get groups - return empty array
			return [];
		}

		$this->add_log(
			'ldap',
			'Searching for group entries with filter: ' . $filter->toString() . ' base ' .
			$this->groupbase_dn() . ' at line ' . __LINE__ . ' in ' . __FILE__
		);

		try {
			$searchresult = $this->ldaplink->search($filter, $this->groupbase_dn(), $this->options['scope']);
			$searchresult->getInnerIterator()->setAttributeNameTreatment(LdapCollectionIterator::ATTRIBUTE_NATIVE);
		} catch (LdapException $e) {
			$this->add_log('ldap', 'Search failed: ' . $e->getMessage() . ' at line ' . __LINE__ . ' in ' . __FILE__);
			return false;
		}

		$this->add_log('ldap', 'Found ' . $searchresult->count() . ' entries. Extracting entries now.');

		$groupEntries = $searchresult->toArray();
		$this->groups = [];

		foreach ($groupEntries as $entry) {
			if (empty($entry)) {
				continue;
			}

			$group = $this->parseLdapAttributes($entry);
			$this->groups[$group['dn']] = $group; // no error checking necessary here
		}
		$this->add_log('ldap', count($this->groups) . ' groups found at line ' . __LINE__ . ' in ' . __FILE__);

		return($this->groups);
	} // End: private function get_group_dns()




	// helper functions
	private function userbase_dn()
	{
		if (empty($this->options['userdn'])) {
			return($this->options['baseDn']);
		}
		return($this->options['userdn'] . ',' . $this->options['baseDn']);
	}

	private function user_dn()
	{
		if (isset($this->user_attributes['dn'])) {
			// we did already fetch user attributes and have the real dn now
			return($this->user_attributes['dn']);
		}
		if (empty($this->options['userattr'])) {
			$ua = 'cn=';
		} else {
			$ua = $this->options['userattr'] . '=';
		}
		return($ua . $this->options['username'] . ',' . $this->userbase_dn());
	}

	private function groupbase_dn()
	{
		if (empty($this->options['groupdn'])) {
			return($this->options['baseDn']);
		}
		return($this->options['groupdn'] . ',' . $this->options['baseDn']);
	}

	private function add_log($facility, $message)
	{
		if ($this->options['debug']) {
			$this->logslib->add_log($facility, $message);
		}
	}

	/**
	 * Setter to set an option value
	 * @param string $name The name of the option
	 * @param mixed $value The value
	 * @return void
	 * @throw Exception
	 */
	public function setOption($name, $value = null)
	{
		if (isset($this->options[$name])) {
			$this->options[$name] = $value;
		} else {
			throw new Exception(sprintf("Undefined option: %s \n", $name), E_USER_WARNING);
		}
	}

	/**
	 * Return the value of the attribute past in param
	 * @param string $name The name of the attribute
	 * @return mixed
	 * @throw Exception
	 */
	public function getUserAttribute($name)
	{
		$value = '';
		try {
			$values = self::get_user_attributes();
			if (isset($values[$name])) {
				$value = $values[$name];
			} else {
				throw new Exception(sprintf("Undefined attribute %s \n", $name), E_USER_WARNING);
			}
		} catch (Exception $e) {
		}
		return $value;
	}

	/**
	 * Parse the ldap retrieved attributes for a given entry
	 *
	 * @param $entry
	 * @return array
	 */
	private function parseLdapAttributes($entry)
	{
		$attributes = [];
		foreach ($entry as $key => $value) {
			if (is_array($value)) {
				$attributes[$key] = count($value) == 1 ? array_shift($value) : $value;
			} else {
				$attributes[$key] = $value;
			}
		}

		return $attributes;
	}
}
