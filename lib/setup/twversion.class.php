<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Should generally be instantiated from tiki-setup.php

class TWVersion
{
    public $branch;		// Development cycle
    public $version;		// This version
    private $latestMinorRelease;		// Latest release in the same major version release series
    public $latestRelease;		// Latest release
    private $isLatestMajorVersion; // Whether or not the current major version is the latest
    public $releases;		// Array of all releases from website
    public $star;			// Star being used for this version tree
    public $svn;			// Is this a Subversion version or a package?
    public $git;			// Is this a Git version or a package?

    public function __construct()
    {
        // Set the development branch.  Valid are:
        //   stable   : Represents stable releases.
        //   unstable : Represents candidate and test/development releases.
        //   trunk    : Represents next generation development version.
        $this->branch = 'trunk';

        // Set everything else, including defaults.
        $this->version = '22.0vcs';	// needs to have no spaces for releases
        $this->star = 'UY Scuti';
        $this->releases = [];

        // Check for Subversion or not
        $this->svn = is_dir('.svn') ? 'y' : 'n';

        // Check for Git or not
        $this->git = is_dir('.git') ? 'y' : 'n';
    }

    // Returns the latest minor release in the same major version release series.
    public function getLatestMinorRelease()
    {
        $this->pollVersion();

        return $this->latestMinorRelease;
    }

    /**
     * Provides the version and subversion of Tiki without any preceding alpha characters eg. 22.0
     *
     * @return string
     */
    public function getBaseVersion()
    {
        return preg_replace("/^(\d+\.\d+).*$/", '$1', $this->version);
    }

    // Returns an array of all used Tiki stars.
    public function tikiStars()
    {
        return [
                1 => 'Spica',			// 0.9
                2 => 'Shaula',		// 0.95
                3 => 'Ras Algheti',	// 1.0.x
                4 => 'Capella',		// 1.1.x
                5 => 'Antares',		// 1.2.x
                6 => 'Pollux',		// 1.3.x
                7 => 'Mira',			// 1.4.x
                8 => 'Regulus',		// 1.5.x
                9 => 'Tau Ceti',		// 1.6.x
                10 => 'Eta Carinae',	// 1.7.x
                11 => 'Polaris',		// 1.8.x
                12 => 'Sirius',		// 1.9.x
                13 => 'Arcturus',		// 2.x
                14 => 'Betelgeuse',	// 3.x
                15 => 'Aldebaran',	// 4.x
                16 => 'Vulpeculae',	// 5.x
                17 => 'Rigel',		// 6.x
                18 => 'Electra',		// 7.x
                19 => 'Acubens',		// 8.x
                20 => 'Herbig Haro',	// 9.x
                21 => 'Sun',			// 10.x
                22 => 'Vega',			// 11.x
                23 => 'Altair',		// 12.x
                24 => 'Fomalhaut',	// 13.x
                25 => 'Peony',		// 14.x
                26 => 'Situla',		// 15.x
                27 => 'Tabby\'s',		// 16.x
                28 => 'Zeta Boötis',	// 17.x
                29 => 'Alcyone',	// 18.x
                30 => 'Denebola',	// 19.x
                31 => 'Tarazed',	// 20.x
                32 => 'UY Scuti', // 21.x
        ];
    }

    // Returns an array of all valid versions of Tiki.
    public function tikiVersions()
    {
        // These are all the valid release versions of Tiki.
        // Newest version goes at the end.
        // Release Managers should update this array before
        // release.
        return [
                1 => '1.9.1',
                '1.9.1.1',
                '1.9.2',
                '1.9.3.1',
                '1.9.3.2',
                '1.9.4',
                '1.9.5',
                '1.9.6',
                '1.9.7',
                '1.9.8',
                '1.9.8.1',
                '1.9.8.2',
                '1.9.8.3',
                '1.9.9',
                '1.9.10',
                '1.9.10.1',
                '1.9.11',
                '2.0',
                '2.1',
                '2.2',
                '2.3',
                '2.4',
                '3.0beta1',
                '3.0beta2',
                '3.0beta3',
                '3.0beta4',
                '3.0rc1',
                '3.0rc2',
                '3.0',
                '3.1',
                '3.2',
                '3.3',
                '3.4',
                '3.5',
                '3.6',
                '3.7',
                '3.8',
                '4.0beta1',
                '4.0RC1',
                '4.0',
                '4.1',
                '4.2',
                '4.3',
                '5.0alpha',
                '5.0beta1',
                '5.0beta2',
                '5.0RC1',
                '5.0RC2',
                '5.0',
                '5.1RC1',
                '5.1',
                '5.2',
                '5.3',
                '6.0beta1',
                '6.0beta2',
                '6.0beta3',
                '6.0RC1',
                '6.0',
                '6.1alpha1',
                '6.1beta1',
                '6.1beta2',
                '6.1RC1',
                '6.1',
                '6.2',
                '6.3',
                '6.4',
                '6.5',
                '6.6',
                '6.7',
                '6.8',
                '6.9',
                '6.10',
                '6.11',
                '6.12',
                '6.13',
                '6.14',
                '6.15',
                '7.0beta1',
                '7.0beta2',
                '7.0RC1',
                '7.0',
                '7.1RC1',
                '7.1RC2',
                '7.1',
                '7.2',
                '7.3',
                '8.0beta',
                '8.0RC1',
                '8.0',
                '8.1',
                '8.2',
                '8.3',
                '8.4',
                '8.5',
                '9.0alpha',
                '9.0beta',
                '9.0beta2',
                '9.0',
                '9.1',
                '9.2beta1',
                '9.2',
                '9.3',
                '9.4',
                '9.5',
                '9.6',
                '9.7',
                '9.8',
                '9.9',
                '9.10',
                '9.11',
                '10.0alpha',
                '10.0beta',
                '10.0',
                '10.1',
                '10.2',
                '10.3',
                '10.4',
                '10.5',
                '10.6',
                '11.0beta',
                '11.0',
                '11.1',
                '11.2',
                '12.0alpha',
                '12.0beta',
                '12.0',
                '12.1alpha',
                '12.1beta',
                '12.1',
                '12.2',
                '12.3',
                '12.4',
                '12.5',
                '12.6',
                '12.7',
                '12.8',
                '12.9',
                '12.10',
                '12.11',
                '12.12',
                '12.13',
                '12.14',
                '13.0beta',
                '13.0',
                '13.1',
                '13.2',
                '14.0beta',
                '14.0',
                '14.1',
                '14.2',
                '14.3',
                '14.4',
                '15.0alpha',
                '15.0beta',
                '15.0',
                '15.1',
                '15.2',
                '15.3',
                '15.4',
                '15.5',
                '15.6',
                '15.7',
                '15.8',
                '15.9',
                '16.0beta',
                '16.0',
                '16.1',
                '16.2',
                '16.3',
                '16.4',
                '17.0alpha',
                '17.0beta',
                '17.0',
                '17.1',
                '17.2',
                '17.3',
                '18.0alpha',
                '18.0beta',
                '18.0',
                '18.1',
                '18.2',
                '18.3',
                '18.4',
                '18.5',
                '18.6',
                '18.7',
                '19.0alpha',
                '19.0beta1',
                '19.0RC1',
                '19.0',
                '19.1',
                '19.2',
                '19.3',
                '20.0alpha',
                '20.0beta',
                '20.0RC1',
                '20.0',
                '20.1',
                '20.2',
                '20.3',
                '20.4',
                '21.0alpha',
                '21.0beta',
                '21.0RC1',
                '21.0',
                '21.1',
                '21.2',
            ];
    }

    // Gets the latest star used by Tiki.
    public function getStar()
    {
        $stars = $this->tikiStars();

        return $stars[count($stars)];
    }

    // Determines the currently-running version of Tiki. eg. 22.0vcs
    public function getVersion()
    {
        return $this->version;
    }

    // Pulls the list of releases in the current branch of Tiki from
    // a central site.
    private function pollVersion()
    {
        static $done = false;
        if ($done) {
            return;
        }
        global $tikilib;
        $upgrade = 0;
        $major = 0;
        $velements = explode('.', $this->getBaseVersion());
        // .version contains an ordered list of release numbers, one per line. All minor releases from a same major release are grouped.
        $body = $tikilib->httprequest('tiki.org/' . $this->branch . '.version');
        $lines = explode("\n", $body);
        $this->isLatestMajorVersion = true;

        foreach ($lines as $line) {
            $relements = explode('.', $line);
            if (isset($relements[0]) && is_numeric($relements[0])) { // Avoid issues with empty lines
                $line = rtrim($line);
                $count = array_push($this->releases, $line);
                if ($relements[0] == $velements[0]) {
                    $this->latestMinorRelease = $line;
                } elseif ($relements[0] > $velements[0]) {
                    $this->isLatestMajorVersion = false;
                }
                $this->latestRelease = $line;
            }
        }
        $done = true;
    }

    // Returns true if the current major version is the latest, false otherwise.
    public function isLatestMajorVersion()
    {
        $this->pollVersion();

        return $this->isLatestMajorVersion;
    }

    // Returns true if the current version is the latest in its major version release series, false otherwise.
    public function isLatestMinorRelease()
    {
        $this->pollVersion();

        return $this->latestMinorRelease == $this->version || version_compare($this->version, $this->latestRelease) == 1;
    }
}
