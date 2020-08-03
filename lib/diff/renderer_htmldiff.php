<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * HTML diff renderer.
 * This class renders the diff of an HTML page with best effort.
 */
include_once("Renderer.php");

class Text_Diff_Renderer_htmldiff extends Tiki_Text_Diff_Renderer
{
    public function __construct($context_lines = 0, $words = 0)
    {
        $this->_leading_context_lines = $context_lines;
        $this->_trailing_context_lines = $context_lines;
        $this->_words = $words;
    }

    protected function _startDiff()
    {
        ob_start();
        $this->original = [];
        $this->final = [];
        $this->n = 0;
        $this->rspan = false;
        $this->lspan = false;
        //$this->tracked_tags = array ("table","ul","div");
        $this->tracked_tags = ["table", "ul"];
    }

    protected function _endDiff()
    {
        for ($i = 0; $i <= $this->n; $i++) {
            if ($this->original[$i] != "" and $this->final[$i] != "") {
                echo "<tr><td width='50%' colspan='2' style='vertical-align:top'>" . $this->original[$i] . "</td><td width='50%' colspan='2' style='vertical-align:top'>" . $this->final[$i] . "</td></tr>\n";
            }
        }
        //echo '</table>';
        $val = ob_get_contents();
        ob_end_clean();

        return $val;
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        return "$xbeg,$xlen,$ybeg,$ylen";
    }

    protected function _startBlock($header)
    {
    }

    protected function _endBlock()
    {
    }

    protected function _insert_tag($line, $tag, &$span)
    {
        $string = "";
        if ($line != '') {
            if (strstr($line, "<") === false) {
                if ($span === false) {
                    $string .= "<span class='$tag'>";
                    $span = true;
                }
                $string .= $line;
            } else {
                if ($span === true) {
                    $string .= "</span class='fin'>";
                    $span = false;
                }
                if (strstr($line, "class=") === false) {
                    $string .= preg_replace("#<([^/> ]+)(.*[^/]?)?>#", "<$1 class='$tag' $2>", $line);
                    $string = preg_replace("#<br class='(.*)'\s*/>#", "<span class='$1'>&crarr;</span><br class='$1' />", $string);
                } else {
                    $string .= preg_replace("#<([^/> ]+)(.*)class=[\"']?([^\"']+)[\"']?(.*[^/]?)?>#", "<$1$2 class='$3 $tag' $4>", $line);
                }
            }
        }

        return $string;
    }

    protected function _count_tags($line, $version)
    {
        preg_match("#<(/?)([^ >]+)#", $line, $out);
        if (count($out) > 1 && in_array($out[2], $this->tracked_tags)) {
            if (isset($this->tags[$version][$out[2]])) {
                if ($out[1] == '/') {
                    $this->tags[$version][$out[2]]--;
                } else {
                    $this->tags[$version][$out[2]]++;
                }
            }
        }
    }

    protected function _can_break($line)
    {
        if (preg_match("#<(p|h\d|br)#", $line) == 0) {
            return false;
        }

        if (isset($this->tags)) {
            foreach ($this->tags as $v) {
                foreach ($v as $tag) {
                    if ($tag != 0) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function _lines($lines, $prefix = '', $suffix = '', $type = '')
    {
        static $context = 0;

        switch ($type) {
            case 'context':
                foreach ($lines as $line) {
                    if ($context == 0 and $this->_can_break($line)) {
                        $context = 1;
                        $this->n++;
                    }

                    $this->_count_tags($line, 'original');
                    $this->_count_tags($line, 'final');
                    if ($this->lspan === true) {
                        $this->original[$this->n] .= "</span>";
                        $this->lspan = false;
                    }
                    if ($this->rspan === true) {
                        $this->final[$this->n] .= "</span>";
                        $this->rspan = false;
                    }
                    if (! isset($this->original[$this->n])) {
                        $this->original[$this->n] = '';
                    }
                    $this->original[$this->n] .= "$line";
                    if (! isset($this->final[$this->n])) {
                        $this->final[$this->n] = '';
                    }
                    $this->final[$this->n] .= "$line";
                }

                break;
            case 'change-added':
            case 'added':
                foreach ($lines as $line) {
                    if ($line != '') {
                        $this->_count_tags($line, 'final');
                        $this->final[$this->n] .= $this->_insert_tag($line, 'diffadded', $this->rspan);
                        $context = 0;
                    }
                }

                break;
            case 'deleted':
            case 'change-deleted':
                foreach ($lines as $line) {
                    if ($line != '') {
                        $this->_count_tags($line, 'original');
                        $this->original[$this->n] .= $this->_insert_tag($line, 'diffdeleted', $this->lspan);
                        $context = 0;
                    }
                }

                break;
        }
    }

    protected function _context($lines)
    {
        $this->_lines($lines, '', '', 'context');
    }

    protected function _added($lines, $changemode = false)
    {
        if ($changemode) {
            $this->_lines($lines, '+', '', 'change-added');
        } else {
            $this->_lines($lines, '+', '', 'added');
        }
    }

    protected function _deleted($lines, $changemode = false)
    {
        if ($changemode) {
            $this->_lines($lines, '-', '', 'change-deleted');
        } else {
            $this->_lines($lines, '-', '', 'deleted');
        }
    }

    protected function _changed($orig, $final)
    {
        $this->_deleted($orig, true);
        $this->_added($final, true);
    }
}
