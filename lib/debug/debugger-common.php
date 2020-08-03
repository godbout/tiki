<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * \brief Commonly used stuff
 * \author zaufi <zaufi@sendmail.ru>
 */

/// Result not available
define('NO_RESULT', 0);

/// Command returns text/plain. execute() must return string type.
define('TEXT_RESULT', 1);
/// Command returns text/html. execute() must return string type.
define('HTML_RESULT', 2);
/// Command need tpl file to display result. execute() may return any type.
define('TPL_RESULT', 3);

/**
 * \brief Every command and debugger itself have a result type.
 */
class ResultType
{
    /// Type of result (see consts defined above)
    public $result_type;

    /// Template name if $result_type == TPL_RESULT
    public $result_tpl;

    /// Constructor init all
    public function __construct()
    {
        $this->reset();
    }

    /// Init all vars to default state
    public function reset()
    {
        $this->result_tpl = '';

        $this->result_type = NO_RESULT;
    }

    /// Accessor for result_type
    public function result_type()
    {
        return $this->result_type;
    }

    public function set_result_type($type)
    {
        $this->result_tpl = '';

        $this->result_type = ($type == TEXT_RESULT || $type == HTML_RESULT || $type == TPL_RESULT) ? $type : NO_RESULT;
    }

    /// Accessor for result_tpl
    public function result_tpl()
    {
        return $this->result_tpl;
    }

    public function set_result_tpl($tpl)
    {
        $this->result_tpl = $tpl;
    }
}
