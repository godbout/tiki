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
class Transition_AtLeastTest extends PHPUnit\Framework\TestCase
{
    public function testOver()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atLeast', 2, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals([], $transition->explain());
    }

    public function testRightOn()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atLeast', 3, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals([], $transition->explain());
    }

    public function testUnder()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atLeast', 4, ['C', 'D', 'E', 'F', 'G']);

        $this->assertEquals(
            [['class' => 'missing', 'count' => 1, 'set' => ['E', 'G']], ],
            $transition->explain()
        );
    }

    public function testImpossibleCondition()
    {
        $transition = new Tiki_Transition('A', 'B');
        $transition->setStates(['A', 'C', 'D', 'F']);
        $transition->addGuard('atLeast', 4, ['C', 'D', 'E']);

        $this->assertEquals(
            [['class' => 'invalid', 'count' => 4, 'set' => ['C', 'D', 'E']], ],
            $transition->explain()
        );
    }
}
