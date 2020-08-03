<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class FilegalsTest extends TikiTestCase
{
    public function testPNGIsNotSVG(): void
    {
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/testdata.png';
        $data = file_get_contents($path);
        $this->assertFalse($fgallib->fileContentIsSVG($data));
    }

    public function testSVGDetect(): void
    {
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/testdata.svg';
        $data = file_get_contents($path);
        $this->assertTrue($fgallib->fileContentIsSVG($data));
    }

    public function testCompressedPNGIsNotSVG(): void
    {
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/testdata.png.gz';
        $data = file_get_contents($path);
        $this->assertFalse($fgallib->fileContentIsSVG($data));
    }

    public function testSVGDetectGzipped(): void
    {
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/testdata.svgz';
        $data = file_get_contents($path);
        $this->assertTrue($fgallib->fileContentIsSVG($data));
    }

    public function testSVGWithPNGExtensionIsNotSafe(): void
    {
        global $prefs;
        $prefs['fgal_allow_svg'] = 'n';
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/svg_content.png';
        $data = file_get_contents($path);
        $filename = 'svg_content.png';
        $caught = false;

        try {
            $fgallib->assertUploadedContentIsSafe($data, $filename);
        } catch (FileIsNotSafeException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
        unset($caught);

        try {
            $fgallib->assertUploadedFileIsSafe($path);
        } catch (FileIsNotSafeException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testHTMLFileWithSVGExtensionIsNotSafe(): void
    {
        global $prefs;
        $prefs['fgal_allow_svg'] = 'n';
        $fgallib = TikiLib::lib('filegal');
        $path = __DIR__ . '/../filegals/4.svg';
        $data = file_get_contents($path);
        $filename = '4.svg';
        $caught = false;

        try {
            $fgallib->assertUploadedContentIsSafe($data, $filename);
        } catch (FileIsNotSafeException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
        unset($caught);

        try {
            $fgallib->assertUploadedFileIsSafe($path);
        } catch (FileIsNotSafeException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }
}
