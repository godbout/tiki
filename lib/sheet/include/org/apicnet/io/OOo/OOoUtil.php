<?php

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}


/*This file is part of J4PHP - Ensembles de propriétés et méthodes permettant le developpment rapide d'application web modulaire
Copyright (c) 2002-2004 @PICNet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE
as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU LESSER GENERAL PUBLIC LICENSE for more details.

You should have received a copy of the GNU LESSER GENERAL PUBLIC LICENSE
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
APIC::import("org.apicnet.io.File");
APIC::import("org.apicnet.io.CDir");


/**
 * OOoUtil
 *
 * @package
 * @author apicnet
 * @copyright Copyright (c) 2004
 * @version $Id: OOoUtil.php,v 1.3 2005-05-18 11:01:39 mose Exp $
 * @access public
 **/
class OOoUtil extends absOOo
{
    public $directories = ["Images", "META-INF"];
    public $pictures = ["gif", "png"];
    public $tmpdir;
    public $docExist;

    public function __construct()
    {
    }

    public function isTextDocument()
    {
    }

    public function isCalcDocument()
    {
    }

    public function isContent()
    {
    }

    public function isMeta()
    {
    }

    public function isSettings()
    {
    }


    public function Ouput()
    {
    }

    public function Zip($name)
    {
        $file = new File($this->tmpdir . "/" . $name, false);
        if ($file->exists()) {
            $file->delFile();
            $file->createFile();
        }

        $zip = APIC::LoadClass("org.apicnet.io.archive.CZip");
        if ($this->docExist) {
            $cdir = new CDir();
            $cdir->Read($this->tmpdir . "/", "", true, 5, true, true);
            $allFiles = [];

            reset($cdir->aFiles);
            foreach ($cdir->aFiles as $aFile) {
                $sFileName = $cdir->FileName($aFile);
                $sFilePath = $cdir->GetPath($aFile);
                $allFiles[] = $this->tmpdir . "/" . $sFilePath . $sFileName;
            }

            $zip->zip($allFiles, $name);
        } else {
            $this -> ErrorTracker(4, "Vous devez d'abord créer un document OpenOffice", 'Zip', __FILE__, __LINE__);
        }
    }

    public function unZip($dir, $file)
    {
        $zip = APIC::LoadClass("org.apicnet.io.archive.CZip");
        $zip->extract($dir, $file);
    }

    public function createDirectories()
    {
        $this->tmpdir = CACHE_PATH . "/OOotmp" . rand();
        mkdir($this->tmpdir);

        for ($i = 0; $i < count($this->directories); $i++) {
            mkdir($this->tmpdir . "/" . $this->directories[$i]);
        }
    }

    public function delDir($dir)
    {
        $current_dir = opendir($dir);
        while ($entryname = readdir($current_dir)) {
            if (is_dir("$dir/$entryname") and ($entryname != "." and $entryname != "..")) {
                $this->delDir("${dir}/${entryname}");
            } elseif ($entryname != "." and $entryname != "..") {
                unlink("${dir}/${entryname}");
            }
        }
        closedir($current_dir);
        rmdir(${dir});
    }

    public function convert($img, $format)
    {
    }


    public function listFiles()
    {
        $cdir = new CDir();
        $cdir->Read($this->tmpdir . "/", "", true, 5, true, true);
        $allFiles = [];

        reset($cdir->aFiles);
        foreach ($cdir->aFiles as $aFile) {
            $sFileName = $cdir->FileName($aFile);
            $sFilePath = $cdir->GetPath($aFile);
            $allFiles[] = $this->tmpdir . "/" . $sFilePath . $sFileName;
        }

        return $allFiles;
    }



    public function isImpressDocument()
    {
    }
    public function isDrawDocument()
    {
    }


    public function main()
    {
        $this->Directories();
    }
}
