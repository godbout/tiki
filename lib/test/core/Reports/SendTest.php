<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../../webmail/tikimaillib.php');

class Reports_SendTest extends TikiTestCase
{
    protected $obj;

    protected $dt;

    protected function setUp() : void
    {
        $this->dt = new DateTime;
        $this->dt->setTimestamp(strtotime('2012-03-27 15:55:16'));

        $this->mail = $this->createMock('TikiMail');
        $this->builder = $this->getMockBuilder('Reports_Send_EmailBuilder')->disableOriginalConstructor()->getMock();

        $tikiPrefs = ['short_date_format' => '%Y-%m-%d', 'browsertitle' => 'test'];

        $this->obj = new Reports_Send($this->dt, $this->mail, $this->builder, $tikiPrefs);
    }

    public function testEmailSubject_noChanges()
    {
        $this->mail->expects($this->exactly(2))->method('setSubject')->with('Report on test from 2012-03-27 (no changes)');

        $userData = ['login' => 'test', 'email' => 'test@test.com'];
        $reportPreferences = ['type' => 'html'];

        $this->obj->sendEmail($userData, $reportPreferences, []);
        $this->obj->sendEmail($userData, $reportPreferences, '');
    }

    public function testEmailSubject_oneChange()
    {
        $this->mail->expects($this->once())->method('setSubject')->with('Report on test from 2012-03-27 (1 change)');

        $userData = ['login' => 'test', 'email' => 'test@test.com'];
        $reportPreferences = ['type' => 'html'];

        $this->obj->sendEmail($userData, $reportPreferences, [1]);
    }

    public function testEmailSubject_multipleChanges()
    {
        $this->mail->expects($this->once())->method('setSubject')->with('Report on test from 2012-03-27 (2 changes)');

        $userData = ['login' => 'test', 'email' => 'test@test.com'];
        $reportPreferences = ['type' => 'html'];

        $this->obj->sendEmail($userData, $reportPreferences, [1, 2]);
    }

    public function testSendEmail()
    {
        $userData = ['login' => 'test', 'email' => 'test@test.com'];
        $reportPreferences = ['type' => 'html'];
        $reportCache = [];
        $emailBody = 'body';

        $this->builder->expects($this->once())->method('emailBody')
            ->with($userData, $reportPreferences, $reportCache)->willReturn($emailBody);
        $this->mail->expects($this->once())->method('setUser')->with('test');
        $this->mail->expects($this->once())->method('setHtml')->with($emailBody);
        $this->mail->expects($this->once())->method('setSubject')->with('Report on test from 2012-03-27 (no changes)');
        $this->mail->expects($this->once())->method('send')->with(['test@test.com']);

        $this->obj->sendEmail($userData, $reportPreferences, $reportCache);
    }
}
