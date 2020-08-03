<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

class ContactLib extends TikiLib
{

    // Contacts
    public function list_contacts(
        $user,
        $offset = -1,
        $maxRecords = -1,
        $sort_mode = 'firstName_asc,lastName_asc,email_asc',
        $find = null,
        $include_group_contacts = false,
        $letter = '',
        $letter_field = 'email',
        $contactIds = []
    ) {
        if ($include_group_contacts) {
            $user_groups = "'" . join("','", $this->get_user_groups($user)) . "'";
            $mid = "where (`user`=? or `groupName` IN ($user_groups)) and `$letter_field` like ?";
        } else {
            $mid = "where `user`=? and `$letter_field` like ?";
        }

        $bindvars = [$user, $letter . '%'];

        if ($find !== null) {
            $findesc = '%' . $find . '%';
            $mid .= " and (`nickname` like ? or `firstName` like ? or `lastName` like ? or `email` like ?)";
            array_push($bindvars, $findesc, $findesc, $findesc, $findesc);
        }

        if ($contactIds) {
            $mid .= " and c.`contactId` in (" . implode(',', array_fill(0, count($contactIds), '?')) . ")";
            $bindvars = array_merge($bindvars, $contactIds);
        }

        $query = "select distinct c.* from `tiki_webmail_contacts` as c" .
                        " left join `tiki_webmail_contacts_groups` as a on a.`contactId`=c.`contactId`" .
                        " $mid order by c." . $this->convertSortMode($sort_mode);

        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $query = "select `groupName` from `tiki_webmail_contacts_groups` where `contactId`=?";
            $res2 = $this->query($query, [(int)$res['contactId']]);
            if ($res2) {
                while ($r2 = $res2->fetchRow()) {
                    $res['groups'][] = $r2['groupName'];
                }
            } else {
                $res['groups'] = [];
            }

            $res2 = $this->query(
                "select `fieldId`,`value` from `tiki_webmail_contacts_ext` where `contactId`=?",
                [(int)$res['contactId']]
            );

            if ($res2) {
                while ($r2 = $res2->fetchRow()) {
                    $res['ext'][$r2['fieldId']] = $r2['value'];
                }
            }
            $ret[] = $res;
        }

        return $ret;
    }

    public function are_contacts($contacts, $user = '')
    {
        $ret = [];

        foreach ($contacts as $con) {
            $con = trim($con);

            $query = "select count(*) from `tiki_webmail_contacts` where `email`=?";
            $cant = $this->getOne($query, [$con]);

            if (! $cant) {
                $ret[] = $con;
            }
        }

        return $ret;
    }

    public function exist_contact($contact, $user = '')
    {
        $contact = trim($contact);
        $query = "select count(*) from `tiki_webmail_contacts` where `email`=? " . (($user != '') ? (' and `user` =? ') : (''));
        $params[] = $contact;
        if ($user != '') {
            $params[] = $user;
        }

        if ($this->getOne($query, $params) == 0) {
            return false;
        }

        return true;
    }

    public function list_contacts_by_letter($user, $offset, $maxRecords, $sort_mode, $letter, $include_group_contacts = false)
    {
        return $this->list_contacts($user, $offset, $maxRecords, $sort_mode, '', $include_group_contacts, $letter);
    }

    public function parse_nicknames($dirs)
    {
        for ($i = 0, $icount_dirs = count($dirs); $i < $icount_dirs; $i++) {
            if (! strstr($dirs[$i], '@') && ! empty($dirs[$i])) {
                $query = "select `email` from `tiki_webmail_contacts` where `nickname`=?";
                $result = $this->query($query, [$dirs[$i]]);
                if ($result->numRows()) {
                    $res = $result->fetchRow();
                    $dirs[$i] = $res["email"];
                }
            }
        }

        return $dirs;
    }

    public function add_contacts($contacts, $user)
    {
        if (is_array($contacts)) {
            foreach ($contacts as $contact) {
                $query = 'insert into `tiki_webmail_contacts` set `firstName`=?, `lastName`=?, `email`=?, `nickname`=?, `user`=?';
                $res = $this->query(
                    $query,
                    [$contact['firstName'], $contact['lastName'], $contact['email'], $contact['nickname'], $user]
                );

                if ($res->numRows()) {
                    $result[] = $res->fetchRow();
                }
            }
        }

        return $result;
    }

    public function get_contact_by_uri($uri, $user)
    {
        $query = "select * from `tiki_webmail_contacts` where (`contactId` = ? OR `uri` = ?) AND `user` = ?";
        $result = $this->query($query, [(int)$uri, $uri, $user]);
        if (! $result->numRows()) {
            return false;
        }

        return $result->fetchRow();
    }

    public function replace_contact(
        $contactId,
        $firstName,
        $lastName,
        $email,
        $nickname,
        $user,
        $groups = [],
        $exts = [],
        $dontDeleteExts = false
    ) {
        global $tiki_p_admin, $tiki_p_admin_group_webmail;

        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $email = trim($email);
        $nickname = trim($nickname);
        if ($contactId) {
            if ($this->is_a_user_contact($contactId, $user, true)) {
                $query = "update `tiki_webmail_contacts` set `firstName`=?, `lastName`=?, `email`=?, `nickname`=? where `contactId`=?";
                $bindvars = [$firstName, $lastName, $email, $nickname, (int)$contactId];
                $result = $this->query($query, $bindvars);
            } else {
                return false;
            }
        } else {
            $contactId = $this->getOne('select max(`contactId`) from `tiki_webmail_contacts`') + 1;
            $query = "insert into `tiki_webmail_contacts`(`contactId`,`firstName`,`lastName`,`email`,`nickname`,`user`) values(?,?,?,?,?,?)";
            $result = $this->query($query, [(int)$contactId, $firstName, $lastName, $email, $nickname, $user]);
        }
        if (is_array($groups)) {
            $this->query('delete from `tiki_webmail_contacts_groups` where `contactId`=?', [(int)$contactId]);
            foreach ($groups as $group) {
                $this->query('insert into `tiki_webmail_contacts_groups` (`contactId`,`groupName`) values (?,?)', [(int)$contactId, $group]);
            }
        }
        if (! $dontDeleteExts) {
            if ($tiki_p_admin == 'y' || $tiki_p_admin_group_webmail == 'y') {	// only a quick fix for shared ext contact data - only admins can delete
                $query = 'delete from `tiki_webmail_contacts_ext` where `contactId`=?';
                $bindvars = [(int)$contactId];
            } else {
                $query = 'DELETE x.* FROM `tiki_webmail_contacts_ext` AS x ' .
                    'LEFT JOIN `tiki_webmail_contacts_fields` AS f ON x.`fieldId`=f.`fieldId`' .
                    'WHERE x.`contactId`=? AND f.`flagsPublic`=\'n\' AND f.`user`=?';
                $bindvars = [(int)$contactId, $user];
            }

            $this->query($query, $bindvars);
        }

        foreach ($exts as $fieldId => $ext) {
            if ($fieldId > 0 && $ext != '') {
                if ($dontDeleteExts && $this->getOne('select count(*) from `tiki_webmail_contacts_ext` where `contactId`=? and `fieldId`=?', [(int)$contactId, (int)$fieldId])) {
                    $this->query(
                        'update `tiki_webmail_contacts_ext` set `value`=? where `contactId`=? and `fieldId`=?',
                        [$ext, (int)$contactId, (int)$fieldId]
                    );
                } else {
                    $this->query(
                        'insert into `tiki_webmail_contacts_ext` (`contactId`,`fieldId`,`value`, `hidden`) values (?,?,?, 0)',
                        [(int)$contactId, (int)$fieldId, $ext]
                    );
                }
            }
        }

        return true;
    }

    public function is_a_user_contact($contactId, $user, $include_group_contacts = true)
    {
        if ($contactId > 0) {
            $user_groups = "'" . join("','", $this->get_user_groups($user)) . "'";
            $query = "select count(*) as res from `tiki_webmail_contacts` as c" .
                            " left join `tiki_webmail_contacts_groups` as a on a.`contactId`=c.`contactId`" .
                            " where c.`contactId`=? and (`user`=? or `groupName` IN ($user_groups))";

            $result = $this->query($query, [(int)$contactId, $user]);
            if ($result) {
                $r = $result->fetchRow();
            }

            return ($r['res'] > 0);
        }

        return false;
    }

    public function remove_contact($contactId, $user, $include_group_contacts = true)
    {
        if ($this->is_a_user_contact($contactId, $user, $include_group_contacts)) {
            $this->query('delete from `tiki_webmail_contacts` where `contactId`=?', [(int)$contactId]);
            $this->query('delete from `tiki_webmail_contacts_groups` where `contactId`=?', [(int)$contactId]);
            $this->query('delete from `tiki_webmail_contacts_ext` where `contactId`=?', [(int)$contactId]);

            return true;
        }

        return false;
    }

    public function get_contact_email($email, $user, $include_group_contacts = true)
    {
        $cid = $this->get_contactId_email($email, $user, $include_group_contacts);
        if (! $cid) {
            return false;
        }

        $info = $this->get_contact($cid, $user, $include_group_contacts);

        foreach ($info['ext'] as $k => $v) {
            if (! in_array($k, array_keys($exts))) {
                $exts[$k] = $v;
                $traducted_exts[$k]['tra'] = tra($info['fieldname']);
                $traducted_exts[$k]['art'] = $info['fieldname'];
                $traducted_exts[$k]['id'] = $k;
            }
        }

        return $info['ext'];
    }

    public function get_contactId_email($email, $user, $include_group_contacts = true)
    {
        $cid = $this->getOne("Select `contactId` from tiki_webmail_contacts where `email`=?", [$email]);
        if ($this->is_a_user_contact($cid, $user, $include_group_contacts)) {
            return $cid;
        }

        return 0;
    }

    public function get_contact($contactId, $user, $include_group_contacts = true)
    {
        if ($this->is_a_user_contact($contactId, $user, $include_group_contacts)) {
            $query = "select * from `tiki_webmail_contacts` where `contactId`=?";
            $result = $this->query($query, [(int)$contactId]);

            if (! $result->numRows()) {
                return false;
            }

            $res = $result->fetchRow();
            $query = "select `groupName` from `tiki_webmail_contacts_groups` where `contactId`=?";
            $res2 = $this->query($query, [(int)$res['contactId']]);
            $ret2 = [];

            if ($res2) {
                while ($r2 = $res2->fetchRow()) {
                    $res['groups'][] = $r2['groupName'];
                }
            }

            $res2 = $this->query("select `fieldId`,`value` from `tiki_webmail_contacts_ext` where `contactId`=?", [$contactId]);

            if ($res2) {
                while ($r2 = $res2->fetchRow()) {
                    $res['ext'][$r2['fieldId']] = $r2['value'];
                }
            }

            return $res;
        }

        return false;
    }

    public function get_contact_ext_val($user, $contactId, $fieldId)
    {
        $res = $this->getOne(
            'select `value` from `tiki_webmail_contacts_ext` where `contactId`=? and `fieldId`=?',
            [(int) $contactId, (int) $fieldId]
        );

        return $res;
    }

    // this function is never called, it is just for making get_strings.php happy, so that default fields in the next function will be in translation files
    public function make_get_strings_happy()
    {
        tra('Personal Phone');
        tra('Personal Mobile');
        tra('Personal Fax');
        tra('Work Phone');
        tra('Work Mobile');
        tra('Work Fax');
        tra('Company');
        tra('Organization');
        tra('Department');
        tra('Division');
        tra('Job Title');
        tra('Street Address');
        tra('City');
        tra('State');
        tra('Postal Code');
        tra('Country');
    }

    public function get_ext_list($user)
    {
        global $user;
        $query = 'select * from `tiki_webmail_contacts_fields` where `user`=? order by `order`, `fieldname`';
        $bindvars = [$user];

        $res = $this->query($query, $bindvars);
        // default values if no user is specified or if user has no ext list
        if (! $res->numRows()) {
            $exts = ['Personal Phone', 'Personal Mobile', 'Personal Fax', 'Work Phone', 'Work Mobile',
                    'Work Fax', 'Company', 'Organization', 'Department', 'Division', 'Job Title',
                    'Street Address', 'City', 'State', 'Postal Code', 'Country'];
            if (($user == null) || (empty($user))) {
                return $exts;
            }
            foreach ($exts as $ext) {
                $this->add_ext($user, $ext);
            }
            $res = $this->query($query, $bindvars);
        }
        while ($row = $res->fetchRow()) {
            $ret[] = $row;
        }

        return $ret;
    }

    public function get_ext($id)
    {
        $res = $this->query('SELECT * FROM `tiki_webmail_contacts_fields` WHERE `fieldId`=?', [(int)$id]);
        if (! $res->numRows()) {
            return null;
        }

        return $res->fetchRow();
    }

    public function get_ext_by_name($user, $name, $contactId = 0)
    {
        if ($contactId) {	// TODO more (some) security for group contacts - not  && $this->is_a_user_contact($contactId, $user)
            //$res = $this->query('select * from `tiki_webmail_contacts_fields` where `flagsPublic`=\'y\' and `fieldname`=?', array($name));
            $query = 'SELECT f.* FROM `tiki_webmail_contacts_fields` AS f LEFT JOIN `tiki_webmail_contacts_ext` AS x ON x.`fieldId` = f.`fieldId` ' .
                'WHERE f.`flagsPublic`=\'y\' AND f.`fieldname`=? AND x.`contactId`=?';
            $res = $this->query($query, [$name, (int) $contactId]);
        }
        if (empty($res) || ! $res->numRows()) {	// temporary global fields - need to add groupishness one day..?
            $res = $this->query('SELECT * FROM `tiki_webmail_contacts_fields` WHERE `user`=? AND `fieldname`=?', [$user, $name]);
        }
        if (empty($res) || ! $res->numRows()) {
            $res = $this->query('SELECT * FROM `tiki_webmail_contacts_fields` WHERE `fieldname`=? AND `flagsPublic`=\'y\'', [$name]);
        }
        if (! $res->numRows()) {
            return null;
        }

        return $res->fetchRow();
    }

    public function add_ext($user, $name, $public = false)
    {
        if ($public) {	// check for previous public one
            $c = $this->getOne('SELECT COUNT(*) FROM `tiki_webmail_contacts_fields` WHERE `fieldname`=? AND `flagsPublic`=\'y\'', [$name]);
        } else {
            $c = 0;
        }

        if (! $c) {
            $pubvar = $public ? 'y' : 'n';
            $this->query("INSERT INTO `tiki_webmail_contacts_fields` (`user`, `fieldname`, `flagsPublic`) VALUES (?,?,?)", [$user, $name, $pubvar]);
        }
    }

    public function remove_ext($user, $fieldId)
    {
        $this->query(
            'delete from `tiki_webmail_contacts_fields` where `user`=? and `fieldId`=?',
            [$user, $fieldId]
        );
    }

    public function rename_ext($user, $fieldId, $newname)
    {
        $this->query(
            'update `tiki_webmail_contacts_fields` set `fieldname`=? where `fieldId`=? and `user`=?',
            [$newname, $fieldId, $user]
        );
    }

    public function modify_ext($user, $fieldId, $new_values)
    {
        if (is_array($new_values)) {
            foreach ($new_values as $f => $v) {
                if ($query != '') {
                    $query .= ', ';
                }
                $query .= "`$f`=?";
                $bindvars[] = $v;
            }
            $query = "update `tiki_webmail_contacts_fields` set $query where `fieldId`=? and `user`=?";
            $bindvars[] = $fieldId;
            $bindvars[] = $user;
            $this->query($query, $bindvars);
        }
    }
}
