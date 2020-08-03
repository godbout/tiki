<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Class TikiDb_Adodb_Result
 *
 * Returns result set along with affected rows
 */
class TikiDb_Adodb_Result
{
    /** @var ADORecordSet */
    public $result;
    /** @var int */
    public $numrows;

    /**
     * TikiDb_Adodb_Result constructor.
     * @param $result
     * @param $rowCount
     */
    public function __construct($result, $rowCount)
    {
        $this->result = &$result;
        $this->numrows = is_numeric($rowCount) ? $rowCount : $this->result->RowCount();
    }

    /** @return array|int|false */
    public function fetchRow()
    {
        if (is_object($this->result)) {
            return $this->result->fetchRow();
        } elseif (is_array($this->result)) {
            return array_shift($this->result);
        }

        return 0;
    }

    /** @return int */
    public function numRows()
    {
        return (int) $this->numrows;
    }
}

/**
 * Class TikiDb_Adodb
 */
class TikiDb_Adodb extends TikiDb
{
    /** @var ADODB_mysqli */
    private $db;
    /** @var int */
    private $rowCount;

    public function __construct($db) // {{{
    {
        if (! $db) {
            die("Invalid db object passed to TikiDB constructor");
        }

        $this->db = $db;
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
    } // }}}

    public function __destruct() // {{{
    {
        if ($this->db) {
            $this->db->Close();
        }
    } // }}}

    public function qstr($str) // {{{
    {
        return $this->db->quote($str);
    } // }}}

    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT) // {{{
    {
        global $num_queries;
        $num_queries++;

        if ($values === null || is_array($values) && count($values) === 0) {
            $values = false;
        }

        $numrows = (int)$numrows;
        $offset = (int)$offset;
        if ($query == null) {
            $query = $this->getQuery();
        }
        $this->convertQueryTablePrefixes($query);

        $starttime = $this->startTimer();
        if ($numrows == -1 && $offset == -1) {
            $result = $this->db->Execute($query, $values);
        } else {
            $result = $this->db->SelectLimit($query, $numrows, $offset, $values);
        }

        $this->stopTimer($starttime);
        $this->rowCount = $this->db->affected_rows();
        if (! $result) {
            $this->rowCount = 0;
            $this->setErrorMessage($this->db->ErrorMsg());

            $this->handleQueryError($query, $values, $result, $reporterrors);
        }

        global $num_queries;
        $num_queries++;
        $this->setQuery(null);

        return new TikiDb_Adodb_Result($result, $this->rowCount);
    } // }}}
}
