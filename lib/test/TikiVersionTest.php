<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$


class TikiVersionTest extends PHPUnit\Framework\TestCase
{
    public function versions()
    {
        return [
            ['9.0', new Tiki_Version_Version(9, 0)],
            ['9.1', new Tiki_Version_Version(9, 1)],
            ['9.1beta2', new Tiki_Version_Version(9, 1, null, 'beta', 2)],
            ['1.9.12.1beta2', new Tiki_Version_Version(1, 9, '12.1', 'beta', 2)],
            ['9.0pre', new Tiki_Version_Version(9, 0, null, 'pre')],
        ];
    }

    /**
     * @dataProvider versions
     * @param $string
     * @param $version
     */
    public function testParseVersions($string, $version)
    {
        $this->assertEquals($version, Tiki_Version_Version::get($string));
    }

    /**
     * @dataProvider versions
     * @param $string
     * @param $version
     */
    public function testWriteVersions($string, $version)
    {
        $this->assertEquals($string, (string) $version);
    }

    public function testVerifyLatestVersion()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('9.0');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
9.0
8.4
6.7
O;
            }
        );

        $this->assertEquals('http://tiki.org/regular.cycle', $out);
        $this->assertEquals([], $response);
    }

    public function testVerifyPastSupportedVersion()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('8.4');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
9.0
8.4
6.7
O;
            }
        );

        $this->assertEquals(
            [
                new Tiki_Version_Upgrade('8.4', '9.0', false),
            ],
            $response
        );
    }

    public function testVerifyMinorUpdate()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('8.2');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
9.0
8.4
6.7
O;
            }
        );

        $this->assertEquals(
            [
                new Tiki_Version_Upgrade('8.2', '8.4', true),
                new Tiki_Version_Upgrade('8.4', '9.0', false),
            ],
            $response
        );
    }

    public function testVerifyUpgradePrerelease()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('8.4beta3');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
9.0
8.4
6.7
O;
            }
        );

        $this->assertEquals(
            [
                new Tiki_Version_Upgrade('8.4beta3', '8.4', true),
                new Tiki_Version_Upgrade('8.4', '9.0', false),
            ],
            $response
        );
    }

    public function testUpgradeFromUnsupportedVersion()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('4.3');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
8.4
9.0
6.7
O;
            }
        );

        $this->assertEquals(
            [
                new Tiki_Version_Upgrade('4.3', '9.0', true),
            ],
            $response
        );
    }

    public function testCurrentVersionMoreRecent()
    {
        $checker = new Tiki_Version_Checker;
        $checker->setCycle('regular');
        $checker->setVersion('10.0');

        $response = $checker->check(
            function ($url) use (&$out) {
                $out = $url;

                return <<<O
8.4
9.0
6.7
O;
            }
        );

        $this->assertEquals([], $response);
    }

    /**
     * @dataProvider upgradeMessages
     * @param $string
     * @param $upgrade
     */
    public function testObtainMessages($string, $upgrade)
    {
        $this->assertEquals($string, $upgrade->getMessage());
    }

    public function upgradeMessages()
    {
        return [
            ['Version 8.2 is no longer supported. A minor upgrade to 8.4 is strongly recommended.', new Tiki_Version_Upgrade('8.2', '8.4', true)],
            ['Version 4.3 is no longer supported. A major upgrade to 9.0 is strongly recommended.', new Tiki_Version_Upgrade('4.3', '9.0', true)],
            ['Version 8.4 is still supported. However, a major upgrade to 9.0 is available.', new Tiki_Version_Upgrade('8.4', '9.0', false)],
        ];
    }
}
