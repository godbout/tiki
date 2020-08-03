<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_PDF_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki_print');
    }

    //function added to hold current state of fancy table / sorted table for pdf and print version. So when user generates pdf he gets his sorted data not default data in table.
    public function action_storeTable($input)
    {
        //write content to file
        $tableName = $input->tableName->text();
        //$tableHTML=$input->tableHTML->text();
        $tableFile = fopen("temp/" . $tableName . '_' . session_id() . ".txt", "w");
        //fwrite($tableFile,$input->tableHTML->text());
        fwrite($tableFile, $input->tableHTML->html());
        //create session array to hold temp tables for printing, table original name and file name
        chmod($tableFile, 0755);
    }

    public function action_checkPDFFile()
    {
        if (file_exists('temp/public/pdffile_' . session_id() . '.txt')) {
            $pdfLenght = file_get_contents('temp/public/pdffile_' . session_id() . '.txt');
            unlink('temp/public/pdffile_' . session_id() . '.txt');

            return $pdfLenght;
        }

        return false;
    }
}
