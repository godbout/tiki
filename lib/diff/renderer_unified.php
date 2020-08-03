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

/**
 * "Unified" diff renderer.
 *
 * This class renders the diff in classic "unified diff" format.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer/unified.php,v 1.2 2004/01/09 21:46:30 chuck Exp $
 *
 * @package Text_Diff
 */
class Text_Diff_Renderer_unified extends Tiki_Text_Diff_Renderer
{
    public function __construct($context_lines = 4)
    {
        $this->_leading_context_lines = $context_lines;
        $this->_trailing_context_lines = $context_lines;
        $this->_table = [];
    }

    protected function _startDiff()
    {
    }

    protected function _endDiff()
    {
        return $this->_table;
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1) {
            $l = $xbeg + $xlen - 1;
            $xbeg .= '-' . $l;
        }
        if ($ylen != 1) {
            $l = $ybeg + $ylen - 1;
            $ybeg .= '-' . $l;
        }
        $this->_table[] = ['type' => "diffheader", 'old' => "$xbeg", 'new' => "$ybeg"];
    }

    protected function _context($lines)
    {
        $this->_table[] = ['type' => "diffbody", 'data' => $lines];
    }
    protected function _added($lines)
    {
        $this->_table[] = ['type' => "diffadded", 'data' => $lines];
    }

    protected function _deleted($lines)
    {
        $this->_table[] = ['type' => "diffdeleted", 'data' => $lines];
    }

    protected function _changed($orig, $final)
    {
        $lines = diffChar($orig, $final, 0);
        $this->_deleted([$lines[0]]);
        $this->_added([$lines[1]]);
    }
}
