<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

class Messu extends TikiLib
{

    /**
     * Put sent message to 'sent' box
     * @param mixed $user
     * @param mixed $from
     * @param mixed $to
     * @param mixed $cc
     * @param mixed $subject
     * @param mixed $body
     * @param mixed $priority
     * @param mixed $replyto_hash
     */
    public function save_sent_message($user, $from, $to, $cc, $subject, $body, $priority, $replyto_hash = '')
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');

        $subject = strip_tags($subject);
        $body = strip_tags($body, '<a><b><img><i>');
        // Prevent duplicates
        $hash = md5($subject . $body);

        if ($this->getOne(
            'select count(*) from `messu_sent` where `user`=? and `user_from`=? and `hash`=?',
            [$user, $from, $hash]
        )
        ) {
            return false;
        }

        $query = 'insert into `messu_sent`' .
                        ' (`user`, `user_from`, `user_to`, `user_cc`, `subject`, `body`, `date`,' .
                        ' `isRead`, `isReplied`, `isFlagged`, `priority`, `hash`, `replyto_hash`)' .
                        ' values(?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $this->query(
            $query,
            [
                $user,
                $from,
                $to,
                $cc,
                $subject,
                $body,
                (int) $this->now,
                'n',
                'n',
                'n',
                (int) $priority,
                $hash,
                $replyto_hash
            ]
        );

        return true;
    }

    /**
     * Send a message to a user
     *
     * @param string $user		username
     * @param string $from		from username
     * @param string $to		to username (again?)
     * @param string $cc		cc username
     * @param string $subject
     * @param string $body
     * @param int    $priority
     * @param string $replyto_hash
     * @param string $replyto_email y/n
     * @param string $bcc_sender	y/n send blind copy email to from user's
     * @return bool				success
     */
    public function post_message($user, $from, $to, $cc, $subject, $body, $priority, $replyto_hash = '', $replyto_email = '', $bcc_sender = '')
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');

        $subject = strip_tags($subject);
        $body = strip_tags($body, '<a><b><img><i>');
        // Prevent duplicates
        $hash = md5($subject . $body);

        if ($this->getOne(
            'select count(*) from `messu_messages` where `user`=? and `user_from`=? and `hash`=?',
            [$user, $from, $hash]
        )
        ) {
            return false;
        }

        $query = 'insert into `messu_messages`' .
                    ' (`user`, `user_from`, `user_to`, `user_cc`, `subject`, `body`, `date`' .
                    ', `isRead`, `isReplied`, `isFlagged`, `priority`, `hash`, `replyto_hash`)' .
                    ' values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->query(
            $query,
            [
                $user,
                $from,
                $to,
                $cc,
                $subject,
                $body,
                (int) $this->now,
                'n',
                'n',
                'n',
                (int) $priority,
                $hash,
                $replyto_hash
            ]
        );

        // Now check if the user should be notified by email
        $magId = $this->getOne('select LAST_INSERT_ID() from `messu_messages`', []);
        $foo = parse_url($_SERVER['REQUEST_URI']);
        $machine = $this->httpPrefix(true) . $foo['path'];
        $machine = str_replace('messu-compose', 'messu-mailbox', $machine);
        $machine = str_replace('messu-broadcast', 'messu-mailbox', $machine);
        // For non-sefurl calls, replace tiki-ajax_services with messu-mailbox if
        // service called is user > send_message
        if ($foo['query'] == "controller=user&action=send_message") {
            $machine = str_replace('tiki-ajax_services', 'messu-mailbox', $machine);
        }
        //For sefurl service call user > send_message, redirect to messu-mailbox.php
        $machine = str_replace('tiki-user-send_message', 'messu-mailbox.php', $machine);

        if ($this->get_user_preference($user, 'minPrio', 6) <= $priority) {
            if (! isset($_SERVER['SERVER_NAME'])) {
                $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
            }
            $email = $userlib->get_user_email($user);
            if ($userlib->user_exists($from)) {
                $from_email = $userlib->get_user_email($from);		// $from_email required for TikiMail constructor
            } elseif ($from == 'tiki-contact.php' && ! empty($prefs['sender_email'])) {
                $from_email = $prefs['sender_email'];
            } else {
                return false;										// non-existent users can't send messages (etc)
            }
            if ($email) {
                include_once('lib/webmail/tikimaillib.php');
                $smarty->assign('mail_site', $_SERVER['SERVER_NAME']);
                $smarty->assign('mail_machine', $machine);
                $smarty->assign('mail_date', $this->now);
                $smarty->assign('mail_user', stripslashes($user));
                $smarty->assign('mail_from', stripslashes($from));
                $smarty->assign('mail_subject', stripslashes($subject));
                $smarty->assign('mail_body', stripslashes($body));
                $smarty->assign('mail_truncate', $prefs['messu_truncate_internal_message']);
                $smarty->assign('messageid', $magId);

                try {
                    $mail = new TikiMail($user, $from_email);
                    $lg = $this->get_user_preference($user, 'language', $prefs['site_language']);

                    if (empty($subject)) {
                        $s = $smarty->fetchLang($lg, 'mail/messu_message_notification_subject.tpl');
                        $mail->setSubject(sprintf($s, $_SERVER['SERVER_NAME']));
                    } else {
                        $mail->setSubject($subject);
                    }

                    $mail_data = $smarty->fetchLang($lg, 'mail/messu_message_notification.tpl');
                    $mail->setText($mail_data);

                    if ($from_email) {
                        if ($bcc_sender === 'y' && ! empty($from_email)) {
                            $mail->setBcc($from_email);
                        }

                        if ($replyto_email !== 'y' && $userlib->get_user_preference($from, 'email is public', 'n') == 'n') {
                            $from_email = '';	// empty $from_email if not to be used - saves getting it twice
                        }

                        if (! empty($from_email)) {
                            $mail->setReplyTo($from_email);
                        }
                    }

                    if (! $mail->send([$email], 'mail')) {
                        return false; //TODO echo $mail->errors;
                    }
                } catch (Laminas\Mail\Exception\ExceptionInterface $e) {
                    Feedback::error($e->getMessage());

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get a list of messages from users mailbox or users mail archive (from
     * which depends on $dbsource)
     * @param mixed $user
     * @param mixed $offset
     * @param mixed $maxRecords
     * @param mixed $sort_mode
     * @param mixed $find
     * @param mixed $flag
     * @param mixed $flagval
     * @param mixed $prio
     * @param mixed $dbsource
     * @param mixed $replyto_hash
     * @param mixed $orig_or_reply
     */
    public function list_user_messages(
        $user,
        $offset,
        $maxRecords,
        $sort_mode,
        $find,
        $flag = '',
        $flagval = '',
        $prio = '',
        $dbsource,
        $replyto_hash = '',
        $orig_or_reply = 'r'
    ) {
        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $bindvars = [$user];
        $mid = '';

        if ($prio) {
            $mid = ' and priority=? ';
            $bindvars[] = $prio;
        }
        if ($replyto_hash) {
            // find replies
            if ($orig_or_reply == 'r') {
                $mid .= ' and replyto_hash=? ';
            // find original for the reply
            } else {
                $mid .= ' and hash=? ';
            }
            $bindvars[] = $replyto_hash;
        }
        if ($flag) {
            // Process the flags
            $mid .= " and `$flag`=? ";
            $bindvars[] = $flagval;
        }
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid .= ' and (`subject` like ? or `body` like ?)';
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $query = 'select * from `messu_' . $dbsource . "` where `user`=? $mid order by " .
                        $this->convertSortMode($sort_mode) . ',' . $this->convertSortMode('msgId_desc');
        $query_cant = 'select count(*) from `messu_' . $dbsource . "` where `user`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res['len'] = strlen($res['body']);

            if (empty($res['subject'])) {
                $res['subject'] = tra('NONE');
            }

            $ret[] = $res;
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    /**
     * Get the number of messages in the users mailbox or mail archive (from
     * which depends on $dbsource)
     * @param mixed $user
     * @param mixed $dbsource
     * @param mixed $unreadOnly
     * @param mixed $newSince
     */
    public function count_messages($user, $dbsource = 'messages', $unreadOnly = false, $newSince = 0)
    {
        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $bindvars = [$user];
        $query_cant = 'select count(*) from `messu_' . $dbsource . '` where `user`=?';
        if ($unreadOnly == true) {
            $query_cant .= ' and `isRead`="n"';
        }
        if (! empty($newSince)) {
            $query_cant .= ' and `date` >= ?';
            $bindvars[] = $newSince;
        }
        $cant = $this->getOne($query_cant, $bindvars);

        return $cant;
    }

    /**
     * Update message flagging
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $flag
     * @param mixed $val
     * @param mixed $dbsource
     */
    public function flag_message($user, $msgId, $flag, $val, $dbsource = 'messages')
    {
        if (! $msgId || ! (in_array($flag, ['isRead', 'isFlagged']))) {
            return false;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $query = 'update `messu_' . $dbsource . "` set `$flag`=? where `user`=? and `msgId`=?";

        return $this->query($query, [$val, $user, (int)$msgId]);
    }

    /**
     * Mark a message as replied
     * @param mixed $user
     * @param mixed $replyto_hash
     * @param mixed $dbsource
     */
    public function mark_replied($user, $replyto_hash, $dbsource = 'sent')
    {
        if ((! $replyto_hash) || ($replyto_hash == '')) {
            return false;
        }

        if ($dbsource == '') {
            $dbsource = 'sent';
        }

        $query = 'update `messu_' . $dbsource . '` set `isReplied`=? where `user`=? and `hash`=?';
        $this->query($query, ['y', $user, $replyto_hash]);
    }

    /**
     * Delete message from mailbox or users mail archive (from which depends on
     * $dbsource)
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $dbsource
     */
    public function delete_message($user, $msgId, $dbsource = 'messages')
    {
        if (! $msgId) {
            return false;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $query = 'delete from `messu_' . $dbsource . '` where `user`=? and `msgId`=?';

        return $this->query($query, [$user, (int)$msgId]);
    }

    /**
     * Move message from mailbox to users mail archive
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $dbsource
     */
    public function archive_message($user, $msgId, $dbsource = 'messages')
    {
        if (! $msgId) {
            return false;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $columns = '`user`, `user_from`, `user_to`, `user_cc`, `subject`, `body`, `date`, `isRead`, `isReplied`, `isFlagged`, `priority`, `hash`, `replyto_hash`';
        $query = 'insert into `messu_archive` (' . $columns . ') select ' . $columns . ' from `messu_' . $dbsource . '` where `user`=? and `msgId`=?';
        $this->query($query, [$user, (int)$msgId]);

        $query = 'delete from `messu_' . $dbsource . '` where `user`=? and `msgId`=?';

        return $this->query($query, [$user, (int)$msgId]);
    }

    /**
     * Move message from archive to users mailbox
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $dbsource
     */
    public function unarchive_message($user, $msgId, $dbsource = 'messages')
    {
        if (! $msgId) {
            return false;
        }

        $dbsource = $this->get_archive_source($user, $msgId);

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $columns = '`user`, `user_from`, `user_to`, `user_cc`, `subject`, `body`, `date`, `isRead`, `isReplied`, `isFlagged`, `priority`, `hash`, `replyto_hash`';
        $query = 'insert into `messu_' . $dbsource . '` (' . $columns . ') select ' . $columns . ' from `messu_archive` where `user`=? and `msgId`=?';
        $this->query($query, [$user, (int)$msgId]);

        $query = 'delete from `messu_archive` where `user`=? and `msgId`=?';

        return $this->query($query, [$user, (int)$msgId]);
    }

    /**
     * Move read message older than x days from mailbox to users mail archive
     * @param mixed $user
     * @param mixed $days
     * @param mixed $dbsource
     */
    public function archive_messages($user, $days, $dbsource = 'messages')
    {
        if ($days < 1) {
            return false;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $age = $this->now - ($days * 3600 * 24);

        // TODO: only move as much msgs into archive as there is space left in there
        $query = 'insert into `messu_archive` select * from `messu_' . $dbsource . '` where `user`=? and `isRead`=? and `date`<=?';
        $this->query($query, [$user, 'y', (int)$age]);

        $query = 'delete from `messu_' . $dbsource . '` where `user`=? and `isRead`=? and `date`<=?';
        $this->query($query, [$user, 'y', (int)$age]);
    }

    /**
     * Move forward to the next message and get it from the database
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $sort_mode
     * @param mixed $find
     * @param mixed $flag
     * @param mixed $flagval
     * @param mixed $prio
     * @param mixed $dbsource
     */
    public function get_next_message($user, $msgId, $sort_mode, $find, $flag, $flagval, $prio, $dbsource = 'messages')
    {
        if (! $msgId) {
            return 0;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $mid = '';
        $bindvars = [$user, (int)$msgId];
        if ($prio) {
            $mid .= ' and priority=? ';
            $bindvars[] = $prio;
        }

        if ($flag) {
            // Process the flags
            $mid .= " and `$flag`=? ";
            $bindvars[] = $flagval;
        }

        if ($find) {
            $findesc = '%' . $find . '%';
            $mid .= ' and (`subject` like ? or `body` like ?)';
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $query = 'select min(`msgId`) as `nextmsg` from `messu_' . $dbsource . "` where `user`=? and `msgId` > ? $mid";
        $result = $this->query($query, $bindvars, 1, 0);
        $res = $result->fetchRow();

        if (! $res) {
            return false;
        }

        return $res['nextmsg'];
    }

    /**
     * Move backward to the next message and get it from the database
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $sort_mode
     * @param mixed $find
     * @param mixed $flag
     * @param mixed $flagval
     * @param mixed $prio
     * @param mixed $dbsource
     */
    public function get_prev_message($user, $msgId, $sort_mode, $find, $flag, $flagval, $prio, $dbsource = 'messages')
    {
        if (! $msgId) {
            return 0;
        }

        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $mid = '';
        $bindvars = [$user, (int)$msgId];
        if ($prio) {
            $mid .= ' and priority=? ';
            $bindvars[] = $prio;
        }

        if ($flag) {
            // Process the flags
            $mid .= " and `$flag`=? ";
            $bindvars[] = $flagval;
        }
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid .= ' and (`subject` like ? or `body` like ?)';
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $query = 'select max(`msgId`) as `prevmsg` from `messu_' . $dbsource . "` where `user`=? and `msgId` < ? $mid";
        $result = $this->query($query, $bindvars, 1, 0);
        $res = $result->fetchRow();

        if (! $res) {
            return false;
        }

        return $res['prevmsg'];
    }

    /**
     * Get a message from the users mailbox or his mail archive (from which
     * depends on $dbsource)
     * @param mixed $user
     * @param mixed $msgId
     * @param mixed $dbsource
     */
    public function get_message($user, $msgId, $dbsource = 'messages')
    {
        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $bindvars = [$user, (int)$msgId];
        $query = 'select * from `messu_' . $dbsource . '` where `user`=? and `msgId`=?';
        $result = $this->query($query, $bindvars);
        $res = $result->fetchRow();
        $res['parsed'] = TikiLib::lib('parser')->parse_data($res['body']);
        $res['len'] = strlen($res['parsed']);

        if (empty($res['subject'])) {
            $res['subject'] = tra('NONE');
        }

        return $res;
    }

    /**
     * Get message from the users mailbox or his mail archive (from which
     * depends on $dbsource)
     * @param mixed $user
     * @param mixed $dbsource
     * @param mixed $subject
     * @param mixed $to
     * @param mixed $from
     */
    public function get_messages($user, $dbsource = 'messages', $subject = '', $to = '', $from = '')
    {
        if ($dbsource == '') {
            $dbsource = 'messages';
        }

        $bindvars[] = $user;

        $mid = '';

        // find mails with a specific subject
        if ($subject <> '') {
            $findesc = '%' . $subject . '%';
            $bindvars[] = $findesc;
            $mid .= ' and `subject` like ?';
        }

        // find mails to a specific user (to, cc, bcc)
        if ($to <> '') {
            $findesc = '%' . $to . '%';
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
            $mid .= ' and (`user_to` like ? or `user_cc` like ? or `user_bcc` like ?)';
        }

        // find mails from a specific user
        if ($from <> '') {
            $findesc = '%' . $from . '%';
            $bindvars[] = $findesc;
            $mid .= ' and `user_from` like ?';
        }
        $query = 'select * from `messu_' . $dbsource . "` where `user`=? $mid";

        $result = $this->query($query, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res['parsed'] = TikiLib::lib('parser')->parse_data($res['body']);
            $res['len'] = strlen($res['parsed']);
            if (empty($res['subject'])) {
                $res['subject'] = tra('NONE');
            }
            $ret[] = $res;
        }

        return $ret;
    }

    /**
     * Get mail source info from the  mail archive
     * @param mixed $user
     * @param mixed $msgId
     */
    public function get_archive_source($user, $msgId)
    {
        $dbsource = '';

        $res = $this->get_message($user, $msgId, 'archive');

        if ($res['user_from'] == $user) {
            $dbsource = 'sent';
        }

        return $dbsource;
    }
}
