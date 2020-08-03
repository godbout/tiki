<?php

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Tiki\Package\ExtensionManager as PackageExtensionManager;

/**
 * Tiki initialization functions and classes
 *
 * @package TikiWiki
 * @subpackage lib\init
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

if (! file_exists(__DIR__ . '/../../vendor_bundled/vendor/autoload.php')) {
    $error = "Your Tiki is not completely installed because Composer has not been run to fetch package dependencies.\n" .
        "You need to run 'sh setup.sh' from the command line.\n" .
        "See https://doc.tiki.org/Composer for details.\n";

    if (http_response_code() === false) { // if running in cli
        $error = "\033[31m" . $error . "\e[0m\n";
    }
    echo $error;
    exit(1);
}

require_once __DIR__ . '/../../vendor_bundled/vendor/autoload.php'; // vendor libs bundled into tiki

// vendor libs managed by the user using composer (if any)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    // In some cases, the vendor folder may contain the files from the old vendor folder before migrating to
    // vendor_bundled. In these cases eg. when unzipping a Tiki => 17.x on top of an existing Tiki <= 16.x instance,
    // loading the autoload from the vendor folder will cause issues.
    // We check for some core libraries (ZendFramework, Smarty and Adodb), if they are all present in the
    // vendor folder we will consider that there is a old vendor folder, and skip loading the autoload.php unless
    // there is a file called do_not_clean.txt inside the vendor folder (we will only check the file exists)
    if (file_exists(__DIR__ . '/../../vendor/do_not_clean.txt')
        || ! ( // check the existence of critical files denoting a legacy vendor folder
            (file_exists(__DIR__ . '/../../vendor/zendframework/zend-config/src/Config.php') //ZF2
                || file_exists(__DIR__ . '/../../vendor/bombayworks/zendframework1/library/Zend/Config.php')) //ZF1
            && (file_exists(__DIR__ . '/../../vendor/smarty/smarty/libs/Smarty.class.php') //Smarty
                || file_exists(__DIR__ . '/../../vendor/smarty/smarty/distribution/libs/Smarty.class.php')) //Smarty
            && file_exists(__DIR__ . '/../../vendor/adodb/adodb/adodb.inc.php') //Adodb
        )) {
        $autoloader = require_once __DIR__ . '/../../vendor/autoload.php';
        // Autoload extension packages libs
        foreach (\Tiki\Package\ExtensionManager::getEnabledPackageExtensions(false) as $package) {
            if (is_dir($package['path'] . '/lib/') && strpos($package['path'], 'vendor_custom') === false) {
                $autoloader->addPsr4(str_replace('/', '\\', $package['name']) . '\\', $package['path'] . '/lib/');
            }
        }
    }
}

// vendor libraries managed by the user, packaged (if any)
if (is_dir(__DIR__ . '/../../vendor_custom')) {
    foreach (new DirectoryIterator(__DIR__ . '/../../vendor_custom') as $fileInfo) {
        if (! $fileInfo->isDir() || $fileInfo->isDot()) {
            continue;
        }
        if (file_exists($fileInfo->getPathname() . '/autoload.php')) {
            require_once $fileInfo->getPathname() . '/autoload.php';
            // Autoload extension packages libs
            $packagePath = $fileInfo->getPathname();
            if (is_dir($packagePath . '/lib/') && $composerJson = json_decode(file_get_contents($packagePath . '/composer.json'), true)) {
                $packageName = $composerJson['name'] ?? '';
                if ($packageName && \Tiki\Package\ExtensionManager::isExtension($packageName, $packagePath) && \Tiki\Package\ExtensionManager::isEnabled($packageName)) {
                    $autoloader->addPsr4(str_replace('/', '\\', $packageName) . '\\', $packagePath . '/lib/');
                }
            }
        }
    }
}

spl_autoload_register('Tiki_Autoload::autoload');

/**
 * performs some checks on the underlying system, before initializing Tiki.
 * @package TikiWiki\lib\init
 */
class TikiInit
{
    /**
     * dummy constructor
     */
    public function __construct()
    {
    }

    public static function getContainer()
    {
        /** @var ContainerBuilder $container */
        static $container;

        if ($container) {
            return $container;
        }

        require_once 'lib/setup/twversion.class.php';
        $TWV = new TWVersion();
        $version = $TWV->getVersion();

        $cache = TIKI_PATH . '/temp/cache/container.php';
        if (is_readable($cache)) {
            require_once $cache;

            if (! class_exists('TikiCachedContainer')) {
                // mangled or otherwise invalid container
                unlink($cache);
            } else {
                $container = new TikiCachedContainer;

                /* If the server moved or was upgraded, the container must be recreated */
                if (TIKI_PATH == $container->getParameter('kernel.root_dir') &&
                        $container->hasParameter('tiki.version') &&					// no version before 15.0
                        $container->getParameter('tiki.version') === $version) {
                    if (TikiDb::get()) {
                        $container->set('tiki.lib.db', TikiDb::get());
                    }

                    return $container;
                }
                /* This server moved or was upgraded, container must be recreated */
                unlink($cache);
            }
        }

        $path = TIKI_PATH . '/db/config';
        $container = new ContainerBuilder;
        $container->addCompilerPass(new \Tiki\MailIn\Provider\CompilerPass);
        $container->addCompilerPass(new \Tiki\Recommendation\Engine\CompilerPass);
        $container->addCompilerPass(new \Tiki\Wiki\SlugManager\CompilerPass);
        $container->addCompilerPass(new \Search\Federated\CompilerPass);
        $container->addCompilerPass(new \Tracker\CompilerPass);

        $container->setParameter('kernel.root_dir', TIKI_PATH);
        $container->setParameter('tiki.version', $version);

        $loader = new XmlFileLoader($container, new FileLocator($path));

        $loader->load('tiki.xml');
        $loader->load('controllers.xml');
        $loader->load('mailin.xml');

        try {
            $loader->load('custom.xml');
        } catch (InvalidArgumentException $e) {
            // Do nothing, absence of custom.xml file is expected
        }

        $extensionPackagesDefinition = PackageExtensionManager::getEnabledPackageExtensions(false);
        $container->setParameter('tiki.packages.extensions', $extensionPackagesDefinition);
        foreach ($extensionPackagesDefinition as $packageDefinition) {
            try {
                $path = sprintf('%s/%s/config/services.xml', TIKI_PATH, $packageDefinition['path']);
                $loader->load($path);
            } catch (InvalidArgumentException $e) {
                // Do nothing, absence of services.xml file is expected
            }
        }

        if (TikiDb::get()) {
            $container->set('tiki.lib.db', TikiDb::get());
        }

        $container->compile();

        $dumper = new PhpDumper($container);
        file_put_contents($cache, $dumper->dump([
            'class' => 'TikiCachedContainer',
        ]));

        return $container;
    }

    /** Return 'windows' if windows, otherwise 'unix'
     * \static
     */
    public function os()
    {
        static $os;
        if (! isset($os)) {
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                $os = 'windows';
            } else {
                $os = 'unix';
            }
        }

        return $os;
    }


    /** Return true if windows, otherwise false
      * @static
      */
    public static function isWindows()
    {
        static $windows;
        if (! isset($windows)) {
            $windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
        }

        return $windows;
    }

    /**
     * Copes with Windows permissions
     *
     * @param string $path directory to test
     *
     * @return bool
     */
    public static function is_writeable($path)
    {
        if (self::isWindows()) {
            return self::is__writable($path);
        }

        return is_writeable($path);
    }

    /**
     * From the php is_writable manual (thanks legolas558 d0t users dot sf dot net)
     * Note the two underscores and no "e".
     *
     * will work in despite of Windows ACLs bug
     * NOTE: use a trailing slash for folders!!!
     * {@see http://bugs.php.net/bug.php?id=27609}
     * {@see http://bugs.php.net/bug.php?id=30931}
     *
     * @param string $path	directory to test	NOTE: use a trailing slash for folders!!!
     * @return bool
     */
    public static function is__writable($path)
    {
        if ($path[strlen($path) - 1] == '/') { // recursively return a temporary file path
            return self::is__writable($path . uniqid(mt_rand()) . '.tmp');
        } elseif (is_dir($path)) {
            return self::is__writable($path . '/' . uniqid(mt_rand()) . '.tmp');
        }
        // check tmp file for read/write capabilities
        $rm = file_exists($path);
        $f = @fopen($path, 'a');
        if ($f === false) {
            return false;
        }
        fclose($f);
        if (! $rm) {
            unlink($path);
        }

        return true;
    }


    /** Prepend $path to the include path
     * @static
     * @param string $path the path to prepend
     * @return string
     */
    public static function prependIncludePath($path)
    {
        $include_path = ini_get('include_path');
        $paths = explode(PATH_SEPARATOR, $include_path);

        if ($include_path && ! in_array($path, $paths)) {
            $include_path = $path . PATH_SEPARATOR . $include_path;
        } elseif (! $include_path) {
            $include_path = $path;
        }

        return set_include_path($include_path);
    }


    /** Append $path to the include path
     * @static
     * @param mixed $path
     */
    public static function appendIncludePath($path)
    {
        $include_path = ini_get('include_path');
        $paths = explode(PATH_SEPARATOR, $include_path);

        if ($include_path && ! in_array($path, $paths)) {
            $include_path .= PATH_SEPARATOR . $path;
        } elseif (! $include_path) {
            $include_path = $path;
        }

        return set_include_path($include_path);
    }

    /**
     * Convert a string to UTF-8. Fixes a bug in PHP decode
     * From http://w3.org/International/questions/qa-forms-utf-8.html
     * @static
     * @param string String to be converted
     * @param mixed $string
     * @return UTF-8 representation of the string
     */
    public static function to_utf8($string)
    {
        if (preg_match(
            '%^(?:
	  		   [\x09\x0A\x0D\x20-\x7E]            # ASCII
   		 | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
   		 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
			 | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
			 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
			)*$%xs',
            $string
        )
        ) {
            return $string;
        }

        return iconv('CP1252', 'UTF-8', $string);
    }

    /**
     * Determine if the web server is an IIS server
     * @return true if IIS server, else false
       * @static
     */
    public static function isIIS()
    {
        static $IIS;

        // Sample value Microsoft-IIS/7.5
        if (! isset($IIS) && isset($_SERVER['SERVER_SOFTWARE'])) {
            $IIS = substr($_SERVER['SERVER_SOFTWARE'], 0, 13) == 'Microsoft-IIS';
        }

        return $IIS;
    }

    /**
     * Determine if the web server is an IIS server
     * @return true if IIS server, else false
       * \static
     */
    public static function hasIIS_UrlRewriteModule()
    {
        return isset($_SERVER['IIS_UrlRewriteModule']) == true;
    }

    public static function getCredentialsFile()
    {
        global $default_api_tiki, $api_tiki, $db_tiki, $dbversion_tiki, $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki, $tikidomain, $tikidomainslash, $dbfail_url;
        // Please use the local.php file instead containing these variables
        // If you set sessions to store in the database, you will need a local.php file
        // Otherwise you will be ok.
        //$api_tiki		= 'pear';
        //$api_tiki			= 'pdo';
        $api_tiki = 'pdo';
        $db_tiki = 'mysql';
        $dbversion_tiki = '2.0';
        $host_tiki = 'localhost';
        $user_tiki = 'root';
        $pass_tiki = '';
        $dbs_tiki = 'tiki';
        $tikidomain = '';
        $dbfail_url = '';

        /*
        SVN Developers: Do not change any of the above.
        Instead, create a file, called db/local.php, containing any of
        the variables listed above that are different for your
        development environment.  This will protect you from
        accidentally committing your username/password to SVN!

        example of db/local.php
        <?php
        $host_tiki   = 'myhost';
        $user_tiki   = 'myuser';
        $pass_tiki   = 'mypass';
        $dbs_tiki    = 'mytiki';
        $api_tiki    = 'adodb';

        ** Multi-tiki
        **************************************
        see http://tikiwiki.org/MultiTiki19

        Setup of virtual tikis is done using setup.sh script
        -----------------------------------------------------------
        -> Multi-tiki trick for virtualhosting

        $tikidomain variable is set to :
        or TIKI_VIRTUAL
            That is set in apache virtual conf : SetEnv TIKI_VIRTUAL myvirtual
        or SERVER_NAME
            From apache directive ServerName set for that virtualhost block
        or HTTP_HOST
            From the real domain name called in the browser
            (can be ServerAlias from apache conf)

        */

        if (! isset($local_php) or ! is_file($local_php)) {
            $local_php = 'db/local.php';
        } else {
            $local_php = preg_replace(['/\.\./', '/^db\//'], ['', ''], $local_php);
        }
        $tikidomain = '';
        if (is_file('db/virtuals.inc')) {
            if (isset($_SERVER['TIKI_VIRTUAL']) and is_file('db/' . $_SERVER['TIKI_VIRTUAL'] . '/local.php')) {
                $tikidomain = $_SERVER['TIKI_VIRTUAL'];
            } elseif (isset($_SERVER['SERVER_NAME']) and is_file('db/' . $_SERVER['SERVER_NAME'] . '/local.php')) {
                $tikidomain = $_SERVER['SERVER_NAME'];
            } elseif (isset($_REQUEST['multi']) && is_file('db/' . $_REQUEST['multi'] . '/local.php')) {
                $tikidomain = $_REQUEST['multi'];
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                if (is_file('db/' . $_SERVER['HTTP_HOST'] . '/local.php')) {
                    $tikidomain = $_SERVER['HTTP_HOST'];
                } elseif (is_file('db/' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']) . '/local.php')) {
                    $tikidomain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
                }
            }
            if (! empty($tikidomain)) {
                $local_php = "db/$tikidomain/local.php";
            }
        }
        $tikidomainslash = (! empty($tikidomain) ? $tikidomain . '/' : '');

        $default_api_tiki = $api_tiki;
        $api_tiki = '';

        return $local_php;
    }

    public static function getEnvironmentCredentials()
    {
        // Load connection strings from environment variables, as used by Azure and possibly other hosts
        $connectionString = null;
        foreach (['MYSQLCONNSTR_Tiki', 'MYSQLCONNSTR_DefaultConnection'] as $envVar) {
            if (isset($_SERVER[$envVar])) {
                $connectionString = $_SERVER[$envVar];

                continue;
            }
        }

        if ($connectionString && preg_match('/^Database=(?P<dbs>.+);Data Source=(?P<host>.+);User Id=(?P<user>.+);Password=(?P<pass>.+)$/', $connectionString, $parts)) {
            $parts['charset'] = 'utf8';
            $parts['socket'] = null;

            return $parts;
        }

        return null;
    }
}

/**
 * set how Tiki will report Errors
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function tiki_error_handling($errno, $errstr, $errfile, $errline)
{
    global $prefs, $phpErrors;

    if (0 === error_reporting()) {
        // This error was triggered when evaluating an expression prepended by the at sign (@) error control operator, but since we are in a custom error handler, we have to ignore it manually.
        // See http://ca3.php.net/manual/en/language.operators.errorcontrol.php#98895 and http://php.net/set_error_handler
        return;
    }

    // FIXME: Optionally return false so errors are still logged
    $err[E_ERROR] = 'E_ERROR';
    $err[E_CORE_ERROR] = 'E_CORE_ERROR';
    $err[E_USER_ERROR] = 'E_USER_ERROR';
    $err[E_COMPILE_ERROR] = 'E_COMPILE_ERROR';
    $err[E_WARNING] = 'E_WARNING';
    $err[E_CORE_WARNING] = 'E_CORE_WARNING';
    $err[E_USER_WARNING] = 'E_USER_WARNING';
    $err[E_COMPILE_WARNING] = 'E_COMPILE_WARNING';
    $err[E_PARSE] = 'E_PARSE';
    $err[E_STRICT] = 'E_STRICT';
    $err[E_NOTICE] = 'E_NOTICE';
    $err[E_USER_NOTICE] = 'E_USER_NOTICE';
    $err[E_DEPRECATED] = 'E_DEPRECATED';
    $err[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';

    global $tikipath;
    $errfile = str_replace($tikipath, '', $errfile);
    switch ($errno) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_USER_ERROR:
        case E_COMPILE_ERROR:
        case E_WARNING:
        case E_CORE_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_PARSE:
        case E_RECOVERABLE_ERROR:
            $type = 'ERROR';

            break;
        case E_STRICT:
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            if (! defined('THIRD_PARTY_LIBS_PATTERN') || ! preg_match(THIRD_PARTY_LIBS_PATTERN, $errfile)) {
                if (! empty($prefs['smarty_notice_reporting']) && $prefs['smarty_notice_reporting'] != 'y' && strstr($errfile, '.tpl.php')) {
                    return;
                }
            }
            $type = 'NOTICE';

            break;
        default:
            return;
    }

    $back = "<div class='rbox-data p-3 mb-3' style='font-size: 12px; border: 1px solid'>";
    $back .= $type . " ($err[$errno]): <b>" . $errstr . "</b><br />";
    $back .= "At line $errline in $errfile"; // $errfile comes after $errline to ease selection for copy-pasting.
    $back .= "</div>";

    $phpErrors[] = $back;
}

// Patch missing $_SERVER['REQUEST_URI'] on IIS6
if (empty($_SERVER['REQUEST_URI'])) {
    if (TikiInit::isIIS()) {
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    }
}
