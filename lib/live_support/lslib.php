<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Lslib extends TikiLib
{
    public function set_operator_id($reqId, $senderId)
    {
        $query = 'update `tiki_live_support_requests` set `operator_id` = ? where `reqId`=?';

        $this->query($query, [$senderId, $reqId]);
    }

    public function set_user_id($reqId, $senderId)
    {
        $query = 'update `tiki_live_support_requests` set `user_id` = ? where `reqId`=?';

        $this->query($query, [$senderId, $reqId]);
    }

    public function new_user_request($user, $tiki_user, $email, $reason)
    {
        $reqId = md5(uniqid('.'));
        $query = 'insert into `tiki_live_support_requests`' .
                        ' (`reqId`, `user`, `tiki_user`, `email`, `reason`, `req_timestamp`,' .
                        ' `status`, `timestamp`, `operator`, `chat_started`, `chat_ended`, `operator_id`, `user_id`)' .
                        ' values(?,?,?,?,?,?,?,?,?,?,?,?,?)';

        $this->query(
            $query,
            [$reqId, $user, $tiki_user, $email, $reason, $this->now, 'active', $this->now, '', 0, 0, '', '']
        );

        return $reqId;
    }

    public function get_last_request()
    {
        $x = $this->getOne('select max(`timestamp`) from `tiki_live_support_requests`', []);

        if ($x) {
            return $x;
        }

        return 0;
    }

    public function get_max_active_request()
    {
        return $this->getOne('select max(`reqId`) from `tiki_live_support_requests` where `status`=?', ['active']);
    }

    // Remove active requests
    public function purge_requests()
    {
        $min = $this->now - 60 * 2; // 1 minute = timeout.
        $query = 'update `tiki_live_support_requests` set `status`=? where `timestamp` < ?';
        $this->query($query, ['timeout', $min]);
    }

    // Get status for request
    public function get_request_status($reqId)
    {
        return $this->getOne('select `status` from `tiki_live_support_requests` where `reqId`=?', [$reqId]);
    }

    public function set_request_status($reqId, $status)
    {
        $query = 'update `tiki_live_support_requests` set `status`=? where `reqId`=?';

        $this->query($query, [$status, $reqId]);
    }

    // Get request information
    public function get_request($reqId)
    {
        $query = 'select * from `tiki_live_support_requests` where `reqId`=?';

        $result = $this->query($query, [$reqId]);
        $res = $result->fetchRow();

        return $res;
    }

    public function set_operator_status($user, $status)
    {
        // If switching to offline then sum online time for this operator
        if ($status == 'offline') {
            $query = 'update `tiki_live_support_operators` set `time_online` = ? - `status_since` where `user`=? and `status`=?';

            $this->query($query, [$this->now, $user, 'online']);
        }

        $query = 'update `tiki_live_support_operators` set `status`=?, `status_since`=? where `user`=?';
        $this->query($query, [$status, $this->now, $user]);
    }

    public function get_operator_status($user)
    {
        $status = $this->getOne('select `status` from `tiki_live_support_operators` where `user`=?', [$user]);

        if (! $status) {
            $status = 'offline';
        }

        return $status;
    }

    // Accepts a request, change status to op_accepted
    public function operator_accept($reqId, $user, $operator_id)
    {
        $query = 'update `tiki_live_support_requests` set `operator_id`=?,operator=?,status=?,timestamp=?,chat_started=? where `reqId`=?';
        $this->query($query, [$operator_id, $user, 'op_accepted', $this->now, $this->now, $reqId]);
        $query = 'update `tiki_live_support_operators` set `accepted_requests` = `accepted_requests` + 1 where `user`=?';
        $this->query($query, [$user]);
    }

    public function user_close_request($reqId)
    {
        if (! $reqId) {
            return;
        }

        $query = 'update `tiki_live_support_requests` set `status`=?,timestamp=?,chat_ended=? where `reqId`=?';
        $this->query($query, ['user closed', $this->now, $this->now, $reqId]);
    }

    public function operator_close_request($reqId)
    {
        if (! $reqId) {
            return;
        }

        $query = 'update `tiki_live_support_requests` set `status`=?,timestamp=?,chat_ended=? where `reqId`=?';
        $this->query($query, ['operator closed', $this->now, $this->now, $reqId]);
    }

    public function get_requests($status)
    {
        $this->purge_requests();

        $query = 'select * from `tiki_live_support_requests` where `status`=?';
        $result = $this->query($query, [$status]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        return $ret;
    }

    //EVENT HANDLING
    public function get_new_events($reqId, $senderId, $last)
    {
        $query = 'select * from `tiki_live_support_events` where `senderId`=? and reqId=? and `eventId`>?';

        $result = $this->query($query, [$senderId, $reqId, $last]);
        $ret = '';
        $ret = '<?xml version="1.0" ?>';
        $ret .= '<events>';

        while ($res = $result->fetchRow()) {
            $ret .= '<event>' . '<data>' . $res['data'] . '</data></event>';
        }

        $ret .= '</events>';

        return $ret;
    }

    public function get_last_event($reqId, $senderId)
    {
        return $this->getOne(
            'select max(`seqId`) from `tiki_live_support_events` where `senderId`<>? and reqId=?',
            [$senderId, $reqId]
        );
    }

    public function get_support_event($reqId, $event, $senderId)
    {
        return $this->getOne(
            'select `data` from `tiki_live_support_events` where `senderId`<>? and `reqId`=? and `seqId`=?',
            [$senderId, $reqId, $event]
        );
    }

    public function put_message($reqId, $msg, $senderId)
    {
        $seq = $this->getOne('select max(`seqId`) from `tiki_live_support_events` where `reqId`=?', [$reqId]);

        if (! $seq) {
            $seq = 0;
        }

        $seq++;
        $query = 'insert into `tiki_live_support_events`(`seqId`, `reqId`, `type`, `senderId`, `data`, `timestamp`)' .
                            ' values(?,?,?,?,?,?)';

        $this->query($query, [$seq, $reqId, 'msg', $senderId, $msg, $this->now]);
    }

    public function operators_online()
    {
        return $this->getOne('select count(*) from `tiki_live_support_operators` where `status`=?', ['online']);
    }
}
$lslib = new Lslib;
