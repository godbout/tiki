<?php
/**
 * This checks that composer was installed and otherwise displays a web-friendly error page
 *
 * @package Tiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// this script may only be included - so its better to die if called directly.
// Don't call tiki-setup.php because it does the same test on composer's
// installation and displays a web-ugly error message // which only looks nice in
// command line mode
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}


if (! file_exists('vendor_bundled/vendor/autoload.php')) {
	$title = "Tiki Installer missing third party software files";
	$content = "<p>Your Tiki is not completely installed because Composer has not been run to fetch package dependencies.</p>";
	$content .= "<p>You need to run <b>sh setup.sh</b> from the command line.</p>";
	$content .= "<p>See <a href='https://doc.tiki.org/Composer' target='_blank' class='text-yellow-inst'>https://doc.tiki.org/Composer</a> for details.</p>";
	createPage($title, $content);
	exit;
}

/**
 * creates the HTML page to be displayed.
 *
 * Tiki may not have been installed when we reach here, so we can't use our templating system yet.
 * This needs to be done before tiki-setup.php is called because tiki-setup.php produces a message formatted for command-line only
 *
 * @param string $title   page Title
 * @param mixed  $content page Content
 */
function createPage($title, $content)
{
	echo <<<END
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" rel="stylesheet" href="themes/default/css/default.css" />
		<link type="text/css" rel="stylesheet" href="themes/css/tiki-install.css" />
		<title>$title</title>
	</head>
	<body class="installer-body">
		<div id="fixedwidth" class="fixedwidth">

            <header class="header-main">
                <img alt="Site Logo" src="img/tiki/Tiki_WCG_light.png" class="logo-box" />
	                <div class="text-box">
                        <div class="heading-text">
                            <h3 class="main-text">$title</h3>
                        </div>
                        <div class="text-info">
                            $content
                        </div>
                    </div>
                     <a href="https://tiki.org" class="btn_powered" target="_blank" title="Powered by Tiki Wiki CMS Groupware">
                        <img src="img/tiki/tikibutton.png" alt="Powered by Tiki Wiki CMS Groupware">
                    </a>
            </header>
        </div>
	</body>
</html>
END;
	die;
}
