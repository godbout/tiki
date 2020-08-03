<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/** \file
 * \brief Browse a tree, for example a categories tree
 *
 * \author zaufi@sendmail.ru
 * \enhanced by luci@sh.ground.cz
 *
 */
require_once('lib/tree/tree.php');

/**
 * \brief Class to render categories browse tree
 */
class BrowseTreeMaker extends TreeMaker
{
    /// Generate HTML code for tree. Need to redefine to add javascript cookies block
    public function make_tree($rootid, $ar)
    {
        $headerlib = TikiLib::lib('header');

        $r = '<ul class="tree root">' . "\n";

        $r .= $this->make_tree_r($rootid, $ar) . "</ul>\n";

        // java script block that opens the nodes as remembered in cookies
        $headerlib->add_jq_onready('setTimeout(function () {$(".tree.root:not(.init)").browse_tree().addClass("init")}, 100);');

        // return tree
        return $r;
    }

    //
    // Change default (no code 'cept user data) generation behaviour
    //
    // Need to generate:
    //
    // [indent = <tabulator>]
    // [node start = <li class="treenode">]
    //  [node data start]
    //   [flipper] +/- link to flip
    //   [node child start = <ul class="tree">]
    //    [child's code]
    //   [node child end = </ul>]
    //  [node data end]
    // [node end = </li>]
    //
    //
    //
    public function indent($nodeinfo)
    {
        return "\t\t";
    }

    public function node_start_code_flip($nodeinfo, $count = 0)
    {
        return "\t" . '<li class="treenode withflip ' . (($count % 2) ? 'odd' : 'even') . '">';
    }

    public function node_start_code($nodeinfo, $count = 0)
    {
        return "\t" . '<li class="treenode ' . (($count % 2) ? 'odd' : 'even') . '">';
    }

    //
    public function node_flipper_code($nodeinfo)
    {
        return '';
    }

    //
    public function node_data_start_code($nodeinfo)
    {
        return '';
    }

    //
    public function node_data_end_code($nodeinfo)
    {
        return "\n";
    }

    //
    public function node_child_start_code($nodeinfo)
    {
        global $prefs;

        if ($this->node_cookie_state($nodeinfo['id']) != 'o' && $prefs['javascript_enabled'] === 'y') {
            $style = ' style="display:none;"';
        } else {
            $style = '';
        }

        return '<ul class="tree" data-id="' . $nodeinfo['id'] .
                   '" data-prefix="' . $this->prefix . '"' . $style . '>';
    }

    //
    public function node_child_end_code($nodeinfo)
    {
        return '</ul>';
    }

    //
    public function node_end_code($nodeinfo)
    {
        return "\t" . '</li>';
    }
}
