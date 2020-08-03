<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_sql_info()
{
    return [
        'name' => tra('SQL'),
        'documentation' => 'PluginSQL',
        'description' => tra('Query a MySQL database and display the results'),
        'prefs' => [ 'wikiplugin_sql' ],
        'body' => tr('The SQL query goes in the body. Example: ') . '<code>SELECT column1, column2 FROM table</code>',
        'validate' => 'all',
        'iconname' => 'database',
        'introduced' => 1,
        'extraparams' => true,
        'params' => [
            'db' => [
                'required' => true,
                'name' => tra('DSN Name'),
                'description' => tr('DSN name of the database being queried. The DSN name needs to first be defined at
					%0', '<code>tiki-admin_dsn.php</code>'),
                'since' => '1',
                'default' => ''
            ],
            'raw' => [
                'required' => false,
                'name' => tra('Raw return'),
                'description' => tra('Return with table formatting (default) or raw data with no table formatting'),
                'since' => '11.0',
                'default' => '0',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Normal'), 'value' => '0'],
                    ['text' => tra('Raw'), 'value' => '1']
                ]
            ],
            'delim' => [
                'required' => false,
                'name' => tra('Delim'),
                'description' => tr('The delimiter to be used between data elements (sets %0)', '<code>raw=1</code>'),
                'since' => '11.0',
            ],
            'wikiparse' => [
                'required' => false,
                'name' => tra('Wiki Parse'),
                'description' => tr('Turn wiki parsing of select results on and off (default is on)'),
                'since' => '11.0',
                'default' => '1',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Off'), 'value' => '0'],
                    ['text' => tra('On'), 'value' => '1']
                ]
            ]
        ]
    ];
}

function wikiplugin_sql($data, $params)
{
    global $tikilib;
    extract($params, EXTR_SKIP);

    if (! isset($db)) {
        return tra('Missing db param');
    }

    $perms = Perms::get([ 'type' => 'dsn', 'object' => $db ]);
    if (! $perms->dsn_query) {
        return tra('You do not have the permission that is needed to use this feature');
    }

    $bindvars = [];
    $data = html_entity_decode($data);
    if ($nb = preg_match_all("/\?/", $data, $out)) {
        foreach ($params as $key => $value) {
            if (preg_match('/^[0-9]*$/', $key)) {
                if (preg_match('/(.*)\[\$([^\]]*)\](.*)/', $value, $variable)) {
                    $originalVarValue = $varValue = '{{' . $variable[2] . '}}';
                    $varName = $variable[2];
                    TikiLib::lib('parser')->parse_wiki_argvariable($varValue);
                    if ($originalVarValue !== $varValue) {
                        $bindvars[$key] = $variable[1] . $varValue . $variable[3];
                    } else {
                        global $$varName;
                        $bindvars[$key] = $variable[1] . $$varName . $variable[3];
                    }
                } elseif (strpos($value, "$") === 0) {
                    $varName = substr($value, 1);
                    $originalVarValue = $varValue = '{{' . $varName . '}}';
                    TikiLib::lib('parser')->parse_wiki_argvariable($varName);
                    if ($originalVarValue !== $varValue) {
                        $bindvars[$key] = $varValue;
                    } else {
                        global $$varName;
                        $bindvars[$key] = $$varName;
                    }
                } else {
                    $bindvars[$key] = $value;
                }
            }
        }
        if (count($bindvars) != $nb) {
            return tra('Missing db param');
        }
    }

    $ret = '';
    $sql_oke = true;
    $dbmsg = '';

    if ($db = $tikilib->get_db_by_name($db)) {
        $result = $db->query($data, $bindvars);
    } else {
        return '~np~' . tra('Could not obtain valid DSN connection.') . '~/np~';
    }

    $setup_table = (isset($raw) or isset($delim)) ? false : true;
    $class = 'even';
    while ($result && $res = $result->fetchRow()) {
        if ($setup_table) {
            $ret .= "<table class='normal'><thead><tr>";

            $setup_table = false;

            foreach (array_keys($res) as $col) {
                $ret .= "<th>$col</th>";
            }

            $ret .= "</tr></thead>";
        }

        if (! isset($raw) && ! isset($delim)) {
            $ret .= "<tr>";
        }

        if ($class == 'even') {
            $class = 'odd';
        } else {
            $class = 'even';
        }

        $first_field = true;
        foreach ($res as $name => $val) {
            if (isset($delim) && ! $first_field) {
                $ret .= $delim;
            }

            if (isset($raw) || isset($delim)) {
                $ret .= "$val";
            } else {
                $ret .= "<td class=\"$class\">$val</td>";
            }

            $first_field = false;
        }

        if (! isset($raw) && ! isset($delim)) {
            $ret .= "<tr>";
        } elseif (isset($delim)) {
            $ret .= "<br>";
        }
    }

    if ($ret && ! isset($raw)) {
        $ret .= "</table>";
    }
    if ($dbmsg) {
        $ret .= $dbmsg;
    }

    if ($wikiparse) {
        return $ret;
    }

    return '~np~' . $ret . '~/np~';
}
