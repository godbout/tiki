<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class FilegalsTest extends TikiTestCase
{
	function testPNGIsNotSVG()
	{
		$fgallib = TikiLib::lib('filegal');
        $data = file_get_contents('lib/test/filegals/testdata.png');
        $this->assertFalse($fgallib->fileContentIsSVG($data));
	}

	function testSVGDetect()
	{
		$fgallib = TikiLib::lib('filegal');
        $data = file_get_contents('lib/test/filegals/testdata.svg');
        $this->assertTrue($fgallib->fileContentIsSVG($data));
	}

	function testCompressedPNGIsNotSVG()
	{
		$fgallib = TikiLib::lib('filegal');
        $data = file_get_contents('lib/test/filegals/testdata.png.gz');
        $this->assertFalse($fgallib->fileContentIsSVG($data));
	}

	function testSVGDetectGzipped()
	{
		$fgallib = TikiLib::lib('filegal');
        $data = file_get_contents('lib/test/filegals/testdata.svgz');
        $this->assertTrue($fgallib->fileContentIsSVG($data));
	}

}
