<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Composer;

use Composer\Script\Event;
use Composer\Util\FileSystem;

class CleanVendors
{
/** @var array Files or directories to remove anywhere in vendor files. Case-insensitive. Must specify as lower case.  */
	private static $standardFiles = [
		'development',
		'demo',
		'demo1',
		'demo2',
		'demos',
		'demo.html',
		'demos.html',
		'demo.js',
		'doc',
		'docs',
		'documentation',
		'sample',
		'samples',
		'example',
		'examples',
		'example.html',
		'example.md',
		'test',
		'testing',
		'tests',
		'test.html',
		'vendor',
		'www',
		'.gitattributes',
		'.gitignore',
		'.gitmodules',
		'.jshintrc',
		'bower.json',
		'changes.txt',
		'changelog.txt',
		'changelog',
		'changelog.md',
		'composer.json',
		'composer.lock',
		'gruntfile.js',
		'gruntfile.coffee',
		'package.json',
		'.npmignore',
		'.github',
		'.scrutinizer.yml',
		'.travis.yml',
		'.travis.install.sh',
		'.editorconfig',
		'.jscsrc',
		'.jshintignore',
		'.eslintignore',
		'.eslintrc',
		'.hound.yml',
		'.coveralls.yml',
		'.php_cs',
		'.php_cs.dist',
		'.empty',
		'.mailmap',
		'.styleci.yml',
		'.eslintrc.json',
		'.styleci.yml',
		'contributing.md',
		'changes.md',
		'changes.md~',
		'gemfile',
		'gemfile.lock',
		'readme.txt',
		'readme',
		'readme.php',
		'readme.rst',
		'readme.texttile',
		'readme.markdown',
		'readme.mdown',
		'readme.md',
		'history.md',
		'history.md',
		'todo',
		'todo.md',
		'news',
		'building.md',
		'code_of_conduct.md',
		'conduct.md',
		'security.md',
		'support.md',
		'upgrading.md',
		'_translationstatus.txt',
		'info.txt',
		'robots.txt',
		'install',
		'appveyor.yml',
		'phpunit.xml.dist',
		'makefile',
		'cname',
		'devtools',
		'psalm.xml',
		'authors.txt',
		'authors',
		'credits.md',
		'notice',
	];

	/**
	 * Performs post-composer cleanup routines on vendor files.
	 * @param Event $event
	 */
	public static function clean(Event $event): void
	{
		$vendors = $event->getComposer()->getConfig()->get('vendor-dir');

		if (substr($vendors, -1, 1) !== DIRECTORY_SEPARATOR) {
			$vendors .= DIRECTORY_SEPARATOR;
		}

		$fs = new FileSystem;

		self::removeMultiple(
			$vendors . 'afarkas/html5shiv',
			[
				'src',
				'dist/html5shiv-printshiv.js',
			]
		);
		$fs->remove($vendors . 'bombayworks/zendframework1/library/Zend/Service/WindowsAzure/CommandLine/Scaffolders');
		self::removeMultiple(
			$vendors . 'codemirror/codemirror',
			[
				'mode/tiki',
				'index.html',
				'bin'
			]
		);
		$fs->remove($vendors . 'cwspear/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js');

		self::removeMultiple(
			$vendors . 'jquery/jquery-sheet',
			[
				'jquery-1.10.2.min.js',
				'jquery-ui',
				'parser.php',
				'parser/formula/formula.php'
			]
		);

		self::removeMultiple(
			$vendors . 'jquery/jquery-timepicker-addon',
			[
				'lib',
				'src',
				'jquery-ui-timepicker-addon.json',
				'jquery-ui-timepicker-addon.min.css',
				'jquery-ui-timepicker-addon.min.js'
			]
		);
		self::removeMultiple($vendors . 'jquery/jtrack', ['js/jquery.json-2.2.min.js', 'js/jquery-1.4.2.min.js']);
		self::removeMultiple(
			$vendors . 'jquery/md5',
			['css',
			 'js/md5.min.js'
			]
		);
		$fs->remove($vendors . 'jquery/minicart/src');
		self::removeMultiple(
			$vendors . 'jquery-plugins/anythingslider',
			[
				'anythingslider.jquery.json',
				'expand.html',
				'simple.html',
				'video.html'
			]
		);

		// note, example files are required
		$fs->remove($vendors . 'jquery-plugins/colorbox/content');

		self::removeMultiple(
			$vendors . 'jquery-plugins/galleriffic',
			[
				'js/jquery-1.3.2.js',
				'js/jquery.history.js',
				'js/jush.js',
				'example-1.html',
				'example-2.html',
				'example-3.html',
				'example-4.html',
				'example-5.html',
			]
		);
		$fs->remove($vendors . 'jquery-plugins/infinitecarousel/jquery.infinitecarousel3.min.js');
		self::removeMultiple(
			$vendors . 'jquery-plugins/jquery-validation',
			[
				'lib',
				'src',
				'dist/additional-methods.js',
				'dist/additional-methods.min.js',
				'dist/jquery.validate.min.js'
			]
		);
		self::removeMultiple(
			$vendors . 'jquery-plugins/jquery-json',
			[
				'dist',
				'libs',
			]
		);
		$fs->remove($vendors . 'jquery-plugins/reflection-jquery/src');
		self::removeMultiple(
			$vendors . 'jquery-plugins/superfish',
			[
				'src',
				'superfish.jquery.json',
				'dist/js/jquery.js',
				'dist/js/superfish.min.js'
			]
		);
		self::removeMultiple(
			$vendors . 'mottie/tablesorter',
			[
				'addons',
				'beta-testing',
				'css',
				'dist',
				'bower.json',
				'example.json',
				'Gruntfile.js',
				'index.html',
				'package.json',
				'tablesorter.jquery.json',
				'js/extras',
				'js/jquery.tablesorter.js',
				'js/jquery.tablesorter.widgets.js',
				'js/parsers/parser-date.js',
				'js/parsers/parser-date-extract.js',
				'js/parsers/parser-date-iso8601.js',
				'js/parsers/parser-date-month.js',
				'js/parsers/parser-date-range.js',
				'js/parsers/parser-date-two-digit-year.js',
				'js/parsers/parser-date-weekday.js',
				'js/parsers/parser-duration.js',
				'js/parsers/parser-feet-inch-fraction.js',
				'js/parsers/parser-file-type.js',
				'js/parsers/parser-globalize.js',
				'js/parsers/parser-huge-numbers.js',
				'js/parsers/parser-ignore-articles.js',
				'js/parsers/parser-image.js',
				'js/parsers/parser-leading-zeros.js',
				'js/parsers/parser-metric.js',
				'js/parsers/parser-named-numbers.js',
				'js/parsers/parser-network.js',
				'js/parsers/parser-roman.js',
				'js/widgets/widget-alignChar.js',
				'js/widgets/widget-build-table.js',
				'js/widgets/widget-chart.js',
				'js/widgets/widget-cssStickyHeaders.js',
				'js/widgets/widget-columns.js',					//in jquery.tablesorter.combined.js
				'js/widgets/widget-editable.js',
				'js/widgets/widget-filter.js',					//in jquery.tablesorter.combined.js
				'js/widgets/widget-filter-formatter-html5.js',
				'js/widgets/widget-filter-formatter-select2.js',
				'js/widgets/widget-filter-type-insideRange.js',
				'js/widgets/widget-formatter.js',
				'js/widgets/widget-headerTitles.js',
				'js/widgets/widget-lazyload.js',
				'js/widgets/widget-mark.js',
				'js/widgets/widget-output.js',
				'js/widgets/widget-print.js',
				'js/widgets/widget-reflow.js',
				'js/widgets/widget-repeatheaders.js',
				'js/widgets/widget-resizable.js',				//in jquery.tablesorter.combined.js
				'js/widgets/widget-saveSort.js',				//in jquery.tablesorter.combined.js
				'js/widgets/widget-scroller.js',
				'js/widgets/widget-sortTbodies.js',
				'js/widgets/widget-staticRow.js',
				'js/widgets/widget-stickyHeaders.js',			//in jquery.tablesorter.combined.js
				'js/widgets/widget-storage.js',					//in jquery.tablesorter.combined.js
				'js/widgets/widget-toggle.js',
				'js/widgets/widget-uitheme.js',					//in jquery.tablesorter.combined.js
				'js/widgets/widget-vertical-group.js',
				'js/widgets/widget-view.js'
			]
		);
		self::removeMultiple(
			$vendors . 'jquery-plugins/treetable',
			[
				'stylesheets/jquery.treetable.theme.default.css',
				'stylesheets/screen.css',
				'treetable.jquery.json'
			]
		);
		self::removeMultiple(
			$vendors . 'jquery-plugins/zoom',
			[
				'jquery.zoom.min.js',
				'zoom.jquery.json',
				'daisy.jpg',
				'roxy.jpg'
			]
		);
		self::removeMultiple($vendors . 'mediumjs/mediumjs', ['src', 'medium.min.js']);
		$fs->remove($vendors . 'onelogin/php-saml/demo-old');
		self::removeMultiple(
			$vendors . 'player',
			[
				'flv/base',
				'flv/classes',
				'flv/html5',
				'flv/mtasc',
				'flv/template_js',
				'flv/template_maxi',
				'flv/template_mini',
				'flv/template_multi',
				'flv/build.xml',
				'flv/flv_stream.php',
				'flv/template_default/compileTemplateDefault.bat',
				'flv/template_default/compileTemplateDefault.sh',
				'flv/template_default/rorobong.jpg',
				'flv/template_default/TemplateDefault.as',
				'mp3/classes',
				'mp3/mtasc',
				'mp3/template_js',
				'mp3/template_maxi',
				'mp3/template_mini',
				'mp3/template_multi',
				'mp3/build.xml',
				'mp3/template_default/compileTemplateDefault.bat',
				'mp3/template_default/compileTemplateDefault.sh',
				'mp3/template_default/TemplateDefault.as',
				'mp3/template_default/test.mp3',
			]
		);
		self::removeMultiple(
			$vendors . 'rangy/rangy',
			[
				'uncompressed/rangy-highlighter.js',
				'uncompressed/rangy-serializer.js',
				'uncompressed/rangy-textrange.js',
				'rangy-core.js',
				'rangy-cssclassapplier.js',
				'rangy-highlighter.js',
				'rangy-selectionsaverestore.js',
				'rangy-serializer.js',
				'rangy-textrange.js',
			]
		);
		self::removeMultiple(
			$vendors . 'studio-42/elfinder',
			[
				'files',
				'elfinder.html',
			]
		);

		$fs->remove($vendors . 'nicolaskruchten/pivottable/images/animation.gif');

		self::removeMultiple(
			$vendors . 'adodb/adodb-php',
			[
				'cute_icons_for_site',
				'session/adodb-sess.txt',
				'scripts',
				'pear/readme.Auth.txt',
				// Below are removed to avoid composer warnings caused by classes declared in multiple locations
				'datadict/datadict',
				'session/session',
				'adodb/adodb/perf/perf',
				'adodb/adodb/drivers/drivers',
				'adodb-active-recordx.inc.php',
				'drivers/adodb-informix.inc.php',
				'perf/perf-informix.inc.php',
				'datadict/datadict-informix.inc.php',
			]
		);

		$fs->remove($vendors . 'adodb/adodb-php/session/adodb-sess.txt');

		// and cwspear/bootstrap-hover-dropdown includes bootstrap without asking
		$fs->remove($vendors . 'components/bootstrap');
		//duplicate with mottie/tablesorter
		$fs->remove($vendors . 'components/tablesorter');
		// duplicate with rmm5t/jquery-timeago
		$fs->remove($vendors . 'components/jquery-timeago');
		// duplicate with afarkas/html5shiv
		$fs->remove($vendors . 'components/html5shiv');
		// duplicate with fullcalendar/fullcalendar
		$fs->remove($vendors . 'components/fullcalendar');
		// duplicate with cwspear/bootstrap-hover-dropdown
		$fs->remove($vendors . 'components/bootstrap-hover-dropdown');
		// duplicate with moment/moment
		$fs->remove($vendors . 'components/moment');
		// duplicate with drmonty/smartmenus
		$fs->remove($vendors . 'components/smartmenus');
		$fs->remove($vendors . 'rmm5t/jquery-timeago/index.html');
		$fs->remove($vendors . 'jason-munro/cypht/hm3.sample.ini');
		$fs->remove($vendors . 'league/commonmark/CHANGELOG-0.x.md');
		$fs->remove($vendors . 'pear/pear/README.CONTRIBUTING');
		$fs->remove($vendors . 'twbs/bootstrap/site/_data/examples.yml');
		$fs->remove($vendors . 'Sam152/Javascript-Equal-Height-Responsive-Rows/grids.js');
		$fs->remove($vendors . 'npm-asset/prefixfree/index.html');

		self::removeMultiple(
			$vendors . 'ckeditor/ckeditor',
			[
				'.npm',
				'plugins/codesnippet/lib/highlight/README.ru.md ',
			]
		);

		self::removeMultiple(
			$vendors . 'smarty/smarty',
			[
				'change_log.txt',
				'INHERITANCE_RELEASE_NOTES.txt',
				'SMARTY_2_BC_NOTES.txt',
				'SMARTY_3.0_BC_NOTES.txt',
				'SMARTY_3.1_NOTES.txt',
			]
		);

		self::removeMultiple(
			$vendors . 'bower-asset/fontawesome',
			[
				'advanced-options',
				'svg-with-js',
				'use-on-desktop',
			]
		);

		self::removeMultiple(
			$vendors . 'svg-edit/svg-edit/',
			[
				'embedapi.html',
				'extensions/imagelib/index.html',
				'browser-not-supported.html',
				'config-sample.js'
			]
		);

		self::removeMultiple(
			$vendors . 'ahand/mobileesp',
			[
				'ASP_NET',
				'Cpp',
				'Java',
				'JavaScript',
				'MobileESP_UA-Test-Strings',
				'Python',
			]
		);

		self::removeMultiple(
			$vendors . 'plotly/plotly.js/',
			[
				'src',
				'dist/extras',
				'dist/topojson',
			]
		);

		$fs->remove($vendors . 'css-tricks/anythingslider-themes/index.html');

		self::removeMultiple(
			$vendors . 'vimeo/froogaloop',
			[
				'actionscript',
				'javascript/froogaloop.js',
				'javascript/playground.html',
			]
		);

		self::removeMultiple(
			$vendors . 'haubek/bootstrap4c-chosen',
			[
				'dist',
				'src/scss/build.scss',
				'gulpfile.js',
				'yarn.lock',
			]
		);

		self::removeMultiple(
			$vendors . 'ezyang/htmlpurifier',
			[
				'INSTALL.fr.utf8',
				'release1-update.php',
				'release2-tag.php',
				'test-settings.sample.php',
				'test-settings.travis.php',
				'VERSION',
				'WHATSNEW',
				'WYSIWYG'
			]
		);

		self::removeMultiple(
			$vendors . 'kriswallsmith/assetic',
			[
				'CHANGELOG-1.0.md',
				'CHANGELOG-1.1.md',
				'CHANGELOG-1.2.md',
			]
		);

		self::removeMultiple(
			$vendors . 'openid/php-openid',
			[
				'CHANGES-2.1.0',
				'READEME.Debian',
				'README.git',
			]
		);

		// we remove standard files afterward so we have less files to search through
		self::removeStandard($vendors);
		self::addIndexFile($vendors);

		// now we process theme files
		$themes = __DIR__ . '/../../../../themes/';
		$fs->ensureDirectoryExists($themes);
		self::addIndexFile($themes);
	}

	private static function addIndexFile($path)
	{
		if (file_exists($path) || ! is_writable($path)) {
			return;
		}

		file_put_contents($path . 'index.php', '<?php header("location: ../index.php"); die;');
	}

	/**
	 * Removes all files exactly matching $standardFiles entries.
	 * Case-insensative evaluation. Will search subdirectories.
	 * @param string $base	The base directory to search from.
	 */
	private static function removeStandard(string $base): void
	{
		$fs = new FileSystem;
		$files = glob($base . '/{,.}*[!.]', GLOB_MARK | GLOB_BRACE);
		foreach ($files as $file) {
			if (in_array(strtolower(basename($file)), self::$standardFiles, true)){
				$fs->remove($file);
			} elseif (is_dir($file)){
				self::removeStandard($file);
			}
		}
	}

	/**
	 * Remove multiple files. Must provide case sensitive params
	 * @param string	$base	The base directory(path) to use.
	 * @param array		$files	File/Directory names (omitting the base directory)
	 */
	private static function removeMultiple(string $base, array $files) : void
	{
		$fs = new FileSystem;
		if (is_dir($base)) {
			foreach ($files as $file) {
				$path = $base . '/' . $file;
				if (file_exists($path) || is_dir($path)) {
					$fs->remove($path);
				}
			}
		} else {
			echo "\e[031mError \e[0m $base not found\n";
		}
	}
}
