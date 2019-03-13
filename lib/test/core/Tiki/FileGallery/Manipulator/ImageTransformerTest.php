<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @group unit
 *
 */

use Tiki\FileGallery\File;
use Tiki\FileGallery\Manipulator\ImageTransformer;

class Tiki_FileGallery_Manipulator_ImageTransformerTest extends TikiTestCase
{
  function testResize()
  {
    global $prefs;

    $old_pref = $prefs['fgal_use_db'];
    $prefs['fgal_use_db'] = 'y';

    $path = __DIR__ . '/../../../../filegals/testdata.png';
    $data = file_get_contents($path);
    $file = new File(['filename' => 'testdata.png', 'filetype' => 'image/png', 'data' => $data]);

    (new ImageTransformer($file))->run(['width' => 20, 'height' => 20]);

    $size = getimagesize($file->getWrapper()->getReadableFile());

    $this->assertEquals(20, max($size[0], $size[1]));

    $prefs['fgal_use_db'] = $old_pref;
  }
}
