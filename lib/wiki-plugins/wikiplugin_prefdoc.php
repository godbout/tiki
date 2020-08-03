<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id:

function wikiplugin_prefdoc_info()
{
    return [
        'name' => tra('Document Preferences'),
        'documentation' => 'PluginPrefDoc',
        'description' => tra('Generate documentation for Tiki preference tabs, mostly for use on the Tiki documentation website (doc.tiki.org).'),
        'prefs' => [ 'wikiplugin_prefdoc' ],
        'validate' => 'all',
        'introduced' => 17,
        'iconname' => 'th-list',
        'tags' => [ 'advanced' ],
        'params' => [
            'tab' => [
                'required' => false,
                'name' => tra('Preference-Tab'),
                'description' => tra('The name of the preference tab to display, or a list of available tabs upon invalid.'),
                'filter' => 'text',
                'since' => '17',
            ],
            'img' => [
                'required' => false,
                'name' => tra('Images'),
                'description' => tra('Show images at the top of each version of Tiki. Format: TikiVersion:FileGalleryID. Multiple images my be separated like so: TikiVersion1:FileGalleryID1|TikiVersion2:FileGalleryID2.'),
                'since' => '17',
                'filter' => 'text',
            ],
        ],
    ];
}

/**
 *
 * Generate, Display & Archive Tiki Pref Documentation
 *
 * @param $data string    this is ignored
 * @param $params array   parameters to process
 *
 * @return string         formatted pref table (wiki format) or list of preference tabs (with errors)
 */
function wikiplugin_prefdoc($data, $params)
{
    $Doc = new PrefsDoc();

    if (is_readable('storage/prefsdoc/state.json')) {
        $Doc->state = json_decode(file_get_contents("storage/prefsdoc/state.json"));
    }
    if (! $Doc->genPrefsState()) {
        return $Doc->error;
    }

    if (! isset($params['tab'])) {         // if no tab specified, return a list of available tabs
        return $Doc->genPrefCodes();
    }

    if (! $Doc->genPrefHistory($params['tab'], @$params['img'])) {
        return $Doc->error;
    }

    return $Doc->error . $Doc->docTable;
}


/**
 *
 *
 * Used for automatically generating preference documentation
 * for use on doc.tiki.org. It takes the currently installed preferences
 * and generates files in wiki-syntax
 *
 * Running this will overwrite any previously generated pref doc files
 *
 *
 * Class PrefsDoc
 *
 * @var $docTable string The output to be sent to displayed, in wiki format
 *
 */
class PrefsDoc extends TWVersion
{
    public $docTable;
    public $error;
    public $state;
    private $PrefVars;
    private $prevFilePrefs;
    private $fileCount;
    private $prefCount;
    private $prefDefault;
    private $prefDefaultFull;
    private $prefDescription;
    private $prefName;

    /**
     *
     * Populates $docTable with a string to display.
     * The main function to generate version tabs etc.
     *
     * @param $tabName string   The pref-tab name to create an output for
     * @param $images string    Images & Versions to display with a Version tab.
     *
     * @return bool             true on success false on failure
     */
    public function genPrefHistory($tabName, $images)
    {
        if (! isset($this->state->files->{$tabName})) {
            $this->error .= "Error: <strong>Cant find $tabName</strong> you may choose from one of the following: \n" . $this->genPrefCodes();

            return false;
        }

        $this->docTable = '{TABS(name="' . $tabName . '" tabs="Tiki Version ' . implode('|', $this->state->files->{$tabName}) . "\" toggle=\"n\")}";
        $imageArray = [];
        $images = explode('|', $images);
        foreach ($images as $key => $image) {
            $image = explode(':', $image);
            $imageArray[$image[0]][0] = @$image[1];
            $imageArray[$image[0]][1] = $image[0];
        }
        unset($images);

        // carry over images from past versions of Tiki, if available.
        foreach ($this->state->files->{$tabName} as $key => $version) {
            $count = $version;
            while ($version - $count < 5) {         // If the image is older than 5 tiki versions, dont display it.
                if (@$imageArray[$count][0]) {
                    $this->docTable .= '{img fileId="' . $imageArray[$count][0] . '" thumb="y" rel="box[g]" width="300" desc="Tiki ' . $imageArray[$count][1] . ' Preferences Image" alt="' . $tabName . '" align="center"}';

                    break;
                }
                $count--;
            }
            $this->genPrefVersion("storage/prefsdoc/$version-$tabName.json");
            $key ++;
            if (isset($this->state->files->{$tabName}[$key])) {
                $this->docTable .= '/////';
            }
        }


        $this->docTable .= '{TABS}';

        return true;
    }

    /**
     *
     * Generates a list of valid pref codes when no preference page was specified
     *
     * @return string file-tab codes available for use.
     */
    public function genPrefCodes()
    {
        $codes = '';
        foreach ($this->state->files as $key => $value) {
            $codes .= $key . '<br>';
        }

        return $codes;
    }

    /**
     *
     * Generates a wiki syntax table for a specific version of tiki
     *
     * @param string $fileName The file name to write to disk.
     *
     * @return bool false on error, true otherwise
     */
    private function genPrefVersion($fileName)
    {
        if (! is_file($fileName)) {
            $this->error .= "Cant read $fileName. ";

            return false;
        }
        $this->docTable .= '{FANCYTABLE(head="Option | Description | Default" sortable="n")}';
        $FilePrefs = json_decode(file_get_contents($fileName));
        foreach ($FilePrefs->prefs as $prefName => &$pref) {
            $this->prevFilePrefs;

            // carry over missing information filled out in a newer version
            if (empty($pref->description)) {
                $pref->description = @$this->prevFilePrefs->$prefName->description;
            }
            if (empty($pref->detail)) {
                $pref->detail = @$this->prevFilePrefs->$prefName->detail;
            }
            if (empty($pref->help)) {
                $pref->help = @$this->prevFilePrefs->$prefName->help;
            }
            if (empty($pref->hint)) {
                $pref->hint = @$this->prevFilePrefs->$prefName->hint;
            }
            if (empty($pref->shorthint)) {
                $pref->shorthint = @$this->prevFilePrefs->$prefName->shorthint;
            }
            if (empty($pref->warning)) {
                $pref->warning = @$this->prevFilePrefs->$prefName->warning;
            }
            $this->setParams($pref);
            $this->docTable .= $this->prefName . '~|~' . $this->prefDescription . '~|~<span title="' . $this->prefDefaultFull . '">' . $this->prefDefault . "</span>\n";
        }
        $this->prevFilePrefs = $FilePrefs->prefs;
        $this->docTable .= "{FANCYTABLE}";

        return true;
    }

    /**
     *
     * This sets an vars of an individual preference
     *
     * @param $param object the prefs to be processed into pretty & standardized output
     */
    private function setParams($param)
    {
        $this->prefDefaultFull = '';

        // set default
        if (! empty($param->options) && isset($param->default) && $param->default !== '') {
            $this->prefDefault = $param->options->{$param->default};
        } elseif ($param->default === 'n') {
            $this->prefDefault = 'Disabled';
        } elseif ($param->default === 'y') {
            $this->prefDefault = 'Enabled';                        // Change default codes to human readable format
        } elseif (is_array($param->default)) {
            $this->prefDefault = implode(', ', $param->default);
        } else {
            $this->prefDefault = $param->default;
        }
        // end first processing the below should be applied to the above.... not a continuation (eg. empty array)
        $this->prefDefault = trim($this->prefDefault);
        if ($this->prefDefault == '') {
            $this->prefDefault = '~~gray:None~~';
        } elseif (! empty($param->units)) {
            $this->prefDefault .= ' ' . $param->units;
        } elseif (! preg_match('/\W/', $this->prefDefault)) {                // if Pref is a singe word
            $this->prefDefault = ucfirst($this->prefDefault);                    // then caps the first letter.
        } else {
            if (strlen($this->prefDefault) > 30) {
                $this->prefDefaultFull = $this->wikiConvert($this->prefDefault, true);
                $this->prefDefault = substr($this->prefDefault, 0, 27) . '...';
            }
            $this->prefDefault = $this->wikiConvert($this->prefDefault, true);
        }

        // set name
        if ($param->help) {
            $this->prefName = '<a href="' . $param->help . '">~np~' . $param->name . '~/np~</a>';
        } else {
            $this->prefName = '~np~' . $param->name . '~/np~';
        }
        $this->prefName = $this->wikiConvert($this->prefName);

        // set description
        $this->prefDescription = $param->description;
        if ($param->detail) {
            if ($this->prefDescription) {					// new line if existing content
                $this->prefDescription .= '<br>';
            }
            $this->prefDescription .= '<span class="fas fa-asterisk text-info" title="Detail"></span><i> ' . $param->detail . '</i>';
        }
        if ($param->hint) {
            if ($this->prefDescription) {					// new line if existing content
                $this->prefDescription .= '<br>';
            }
            $this->prefDescription .= '<span class="far fa-hand-pointer text-info" title="Hint"></span><i> ' . $param->hint . '</i>';
        }
        if ($param->shorthint) {
            if ($this->prefDescription) {					// new line if existing content
                $this->prefDescription .= '<br>';
            }
            $this->prefDescription .= '<span class="far fa-hand-pointer text-info" title="Short Hint"></span><i> ' . $param->shorthint . '</i>';
        }
        // if the pref is marked as deprecated, show as such and use the warning as the deprecated notice.
        if (! empty($param->tags)) {
            foreach ($param->tags as $tag) {
                if ($tag === 'deprecated') {
                    if (!$param->warning) {
                        $param->warning = 'Will be removed in an upcoming version of Tiki.';
                    }
                    $this->prefDescription .= ' <span class="fas fa-broom text-warning" title="Deprecated: ' . $param->warning . '"></span>';
                    unset($param->warning);
                }
            }
        }

        if ($param->warning) {
            if ($this->prefDescription) {					// new line if existing content
                $this->prefDescription .= '<br>';
            }
            $this->prefDescription .= '<span class="fas fa-exclamation-triangle text-warning" title="Warning"></span><i> ' . $param->warning . '</i>';
        }
        // display list of options
        if (! empty($param->options)) {
            $count = 0;
            $options = '';
            foreach ($param->options as $option) {
                if ($count) {
                    $options .= ' | ';
                }
                $options .= $option;
                $count++;
            }
            if ($count) {											// If options exist, then add them
                if ($this->prefDescription) {						// new line if existing content
                    $this->prefDescription .= '<br>';
                    if (strlen($options) > 400) {					// truncate options if they get too long
                        $options = substr($options, 0, 397) . '...';
                    }
                }
                $options = $this->wikiConvert($options, true);											// sanitize special characters
                $options = preg_replace('/\s+/', ' ', $options);			// replace all excess whitespace characters with a single space.
                $this->prefDescription .= '<span class="small text-muted"><span class="fas fa-list-ul" title="Options"></span> ' . $options . '</span>';
            }
        }
        if (! empty($param->tags)) {
            foreach ($param->tags as $tag) {
                if ($tag === 'experimental') {
                    $this->prefDescription .= ' <span class="fas fa-flask text-danger" title="Experimental: may not work as intended"></span>';
                }
            }
        }
        $this->prefDescription = $this->wikiConvert($this->prefDescription);
    }

    /**
     * Preps a string from tiki prefs for insertion into tiki-syntax land
     *
     * @param $string string to be parsed
     *
     * @param $escape bool if $string should be enclosed in tiki no-parse tags
     *
     *
     * @return string parsed string sutable for wiki insertion
     *
     */
    private function wikiConvert($string, $escape = false)
    {
        $escapedString = '';
        if ($string) {
            if ($escape) {
                $escapedString = ' ~np~';
            }
            $escapedString .= str_replace("\n", ' ', $string);
            $escapedString = trim($escapedString);
            if ($escape) {
                $escapedString .= '~/np~';
            }
        }

        return $escapedString;
    }

    /**
     *
     * Check if the prefs documentation is up to date.
     * If its not up to date, then update it.
     *
     * Documentation is updated for every minor version change.
     * A separate set of documentation is created for every major version.
     * Only consecutive versions are supported (you cant skip a version)
     *
     * @return bool false on error, true on success.
     */
    public function genPrefsState()
    {
        if ($this->state->version === $this->getBaseVersion()) {
            return true;
        }

        if (! is_dir('storage/prefsdoc')) {
            if (! mkdir('storage/prefsdoc')) {            // create subdir for housing generated files, if it does not exist
                $this->error .= "Cant create storage/prefsdoc directory.";

                return false;
            }
        }

        // prepare to generate prefs doc
        $this->fileCount = 0;
        $this->prefCount = 0;
        $this->getPrefs();

        $docFiles = scandir('templates/admin'); // grab all the files that house prefs
        foreach ($docFiles as $fileName) {
            if (substr($fileName, 0, 8) === 'include_') {  // filter out any file thats not a pref file
                $FilePrefs = $this->getAdminUIPrefs($fileName);
                foreach ($FilePrefs as $tabName => $tab) {
                    if (! $this->writeFile($tabName, $tab)) {
                        return false;
                    }
                    $this->prefCount++;
                }
            }
        }
        // record the state so its easy to figure out whats what next time.
        $preState = (object)[];
        $files = scandir('storage/prefsdoc/');
        foreach ($files as $file) {
            if (preg_match('/^([\d]+)-([a-z0-9-]+).json$/', $file, $matches)) {    // return version number and tab name, filtering out non-matching files
                $preState->{$matches[2]}[] = (int)$matches[1];
            }
        }
        foreach ($preState as $key => $tabState) {                                        // sort the versions from high to low
            rsort($preState->$key);
        }

        $this->state = json_encode([
            'version' => $this->getBaseVersion(),
            'created' => time(),
            'files' => $preState]);
        file_put_contents('storage/prefsdoc/state.json', $this->state);
        $this->state = json_decode($this->state);

        $logslib = TikiLib::lib('logs');
        $logslib->add_log('prefDoc', $this->fileCount . ' pref doc files generated/updated covering ' . $this->prefCount . ' prefs');

        return true;
    }

    /**
     * compiles a all prefs for the current tiki install and sets PrefVars with it
     */
    private function getPrefs()
    {
        $prefs = [];
        $docFiles = scandir('lib/prefs'); // grab all the files that house prefs
        foreach ($docFiles as $fileName) {
            if ($fileName !== 'index.php' && substr($fileName, -4) === '.php') {  // filter out any file thats not a pref file
                require_once('lib/prefs/' . $fileName);
                $callVar = 'prefs_' . substr($fileName, 0, -4) . '_list';
                $prefs = array_merge($prefs, $callVar());            // create one big var with all the pref info
            }
        }
        // Sanitise specific output
        $prefs['webcron_token']['default'] = 'Random Token';

        $this->PrefVars = $prefs;
    }

    /**
     *
     * This generates a list of prefs in the order that they appear on the admin panel.
     *
     * @param string $fileName Name of file to scan
     *
     * @return array  array of pref names, or false on failure
     *
     */
    private function getAdminUIPrefs($fileName)
    {
        $file = file_get_contents('templates/admin/' . $fileName);
        $fileName = substr(substr($fileName, 8), 0, -4);                        // prepare the file name for further use
        $count = preg_match_all('/{tab name="?\'?(?:{tr})?([\w\s]*)(?:{\/tr})?"?\'?.*?}([\w\W]*?){\/tab}/i', $file, $tabs);
        if ($count) {
            while ($count >= 1) {
                $count--;
                $prefs = [];
                preg_match_all('/{preference.*name="?\'?(\w*)"?\'?.*}/i', $tabs[2][$count], $prefs);                    // Generate array of all the prefs
                $tabs[1][$count] = mb_ereg_replace('\W', '', strtolower($tabs[1][$count]));                // sanitize the tab name for disk
                foreach ($prefs[1] as $pref) {
                    if ($this->PrefVars[$pref]['name']) {                                                                        // dont save prefs that have no name
                        $tabPrefs[$fileName . '-' . $tabs[1][$count]][$pref] = $this->PrefVars[$pref];                        // Add full pref info in right order
                    }
                }
            }
        } elseif (preg_match_all('/{preference.*name="?\'?(\w*)"?\'?.*}/i', $file, $prefs)) {
            foreach ($prefs[1] as $pref) {
                $tabPrefs[$fileName][$pref] = $this->PrefVars[$pref];            // Add full pref info in right order
            }
        }

        return $tabPrefs;
    }

    /**
     *
     * Writes a pref file to disk
     *
     * @param $tabName string name of tab or pref file to write
     * @param $prefs array the prefs to write to file
     *
     * @return bool|string returns error message on failure, or false on success
     */
    private function writeFile($tabName, $prefs)
    {
        $version = (int)$this->getBaseVersion();

        $tabName = '-' . $tabName . '.json';                // Name of file to be written, minus prefex

        $prefs = json_encode([
            'prefs' => $prefs,
        ]);

        if (is_file('storage/prefsdoc/' . $version . $tabName)) {
            if (! unlink('storage/prefsdoc/' . $version . $tabName)) {
                $this->error .= ("Cant overwrite existing $version-$tabName.json ");

                return false;
            }
        }

        if (! file_put_contents('storage/prefsdoc/' . $version . $tabName, $prefs)) {
            // write one file for each pref page on control panel
            $this->error .= ("Unable to write $version.$tabName");

            return false;
        }
        $this->fileCount++;

        return true;
    }
}
