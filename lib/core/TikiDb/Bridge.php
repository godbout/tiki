<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiDb_Bridge extends TikiDb
{
    public function startTimer() // {{{
    {
        self::get()->startTimer();
    } // }}}

    public function stopTimer($starttime) // {{{
    {
        self::get()->stopTimer($starttime);
    } // }}}

    public function qstr($str) // {{{
    {
        return self::get()->qstr($str);
    } // }}}

    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = true) // {{{
    {
        return self::get()->query($query, $values, $numrows, $offset, $reporterrors);
    } // }}}

    public function fetchAll($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = true) // {{{
    {
        return self::get()->fetchAll($query, $values, $numrows, $offset, $reporterrors);
    } // }}}

    public function queryError($query, &$error, $values = null, $numrows = -1, $offset = -1) // {{{
    {
        return self::get()->queryError($query, $error, $values, $numrows, $offset);
    } // }}}

    public function queryException($query, $values = null, $numrows = -1, $offset = -1) // {{{
    {
        return self::get()->queryException($query, $values, $numrows, $offset);
    } // }}}

    public function getOne($query, $values = null, $reporterrors = true, $offset = 0) // {{{
    {
        return self::get()->getOne($query, $values, $reporterrors, $offset);
    } // }}}

    public function setErrorHandler(TikiDb_ErrorHandler $handler) // {{{
    {
        self::get()->setErrorHandler($handler);
    } // }}}

    public function setTablePrefix($prefix) // {{{
    {
        self::get()->setTablePrefix($prefix);
    } // }}}

    public function setUsersTablePrefix($prefix) // {{{
    {
        self::get()->setUsersTablePrefix($prefix);
    } // }}}

    public function getServerType() // {{{
    {
        return self::get()->getServerType();
    } // }}}

    public function setServerType($type) // {{{
    {
        self::get()->setServerType($type);
    } // }}}

    public function getErrorMessage() // {{{
    {
        return self::get()->getErrorMessage();
    } // }}}

    protected function setErrorMessage($message) // {{{
    {
        self::get()->setErrorMessage($message);
    } // }}}

    protected function handleQueryError($query, $values, $result, $mode) // {{{
    {
        self::get()->handleQueryError($query, $values, $result, $mode);
    } // }}}

    protected function convertQueryTablePrefixes(&$query) // {{{
    {
        self::get()->convertQueryTablePrefixes($query);
    } // }}}

    public function convertSortMode($sort_mode, $fields = null) // {{{
    {
        return self::get()->convertSortMode($sort_mode, $fields);
    } // }}}

    public function getQuery() // {{{
    {
        return self::get()->getQuery();
    } // }}}

    public function setQuery($sql) // {{{
    {
        return self::get()->setQuery($sql);
    } // }}}

    public function ifNull($field, $ifNull) // {{{
    {
        return self::get()->ifNull($field, $ifNull);
    } // }}}

    public function in($field, $values, &$bindvars) // {{{
    {
        return self::get()->in($field, $values, $bindvars);
    } // }}}

    public function concat() // {{{
    {
        $arr = func_get_args();

        return call_user_func_array([ self::get(), 'concat' ], $arr);
    } // }}}

    public function table($tableName, $autoIncrement = true) // {{{
    {
        return self::get()->table($tableName, $autoIncrement);
    } // }}}
}
