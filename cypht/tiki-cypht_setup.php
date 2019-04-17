<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Convert stock Cypht to one that is suitable for Tiki integration.
// Basically all changes here must be added as pull requests to Cypht code.
// These that do not seem sensible go here.

if (php_sapi_name() != 'cli') {
  exit;
}

if (! file_exists('cypht/hm3.ini')) {
  die("Run this file from the main Tiki directory.");
}

// setup stock version with missing files and symlinks
copy('cypht/hm3.ini', 'vendor_bundled/vendor/jason-munro/cypht/hm3.ini');
chdir('vendor_bundled/vendor/jason-munro/cypht/modules');
if (! file_exists('tiki')) {
  symlink('../../../../../cypht/modules/tiki', 'tiki');
}
chdir('../../../../../');

// generate storage dirs
if (! is_dir('temp/cypht')) {
  umask(0);
  mkdir('temp/cypht', 0777);
  mkdir('temp/cypht/app_data', 0777);
  mkdir('temp/cypht/attachments', 0777);
  mkdir('temp/cypht/users', 0777);
}

// generate Cypht config
`cd vendor_bundled/vendor/jason-munro/cypht; php scripts/config_gen.php`;

// copy site.js and site.css
copy('vendor_bundled/vendor/jason-munro/cypht/site/site.js', 'cypht/site.js');
copy('vendor_bundled/vendor/jason-munro/cypht/site/site.css', 'cypht/site.css');

// js custom pacthes
$js = file_get_contents('cypht/site.js');
$js = str_replace("url: ''", "url: 'cypht/ajax.php'", $js);
file_put_contents('cypht/site.js', $js);

// copy stock assets
`cp -rp vendor_bundled/vendor/jason-munro/cypht/modules/smtp/assets cypht/modules/smtp/`;
`cp -rp vendor_bundled/vendor/jason-munro/cypht/modules/themes/assets cypht/modules/themes/`;
