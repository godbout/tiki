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
class Transition_AtMostTest extends PHPUnit\Framework\TestCase
{
    public function testOver()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atMost', 2, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals(
            [['class' => 'extra', 'count' => 1, 'set' => ['C', 'D', 'F']], ],
            $transition->explain()
        );
    }

    public function testRightOn()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atMost', 3, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals([], $transition->explain());
    }

    public function testUnder()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atMost', 4, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals([], $transition->explain());
    }
}
