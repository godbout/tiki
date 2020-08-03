<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ActionStepTest extends PHPUnit\Framework\TestCase
{
    public function testMissingField()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);

        $step = new Search_Action_ActionStep($action, []);
        $this->expectException(Search_Action_Exception::class);
        $this->expectExceptionMessage("Missing required action parameter or value: hello");
        $step->validate([]);
        $this->assertEquals(['hello'], $step->getFields());
    }

    public function testMissingValueButNotRequired()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => false]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => null])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, []);
        $this->assertTrue($step->validate([]));
        $this->assertEquals(['hello'], $step->getFields());
    }

    public function testValueProvidedStaticInDefinition()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => 'world'])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, ['hello' => 'world']);
        $this->assertTrue($step->validate([]));
        $this->assertEquals([], $step->getFields());
    }

    public function testValueProvidedInEntryDirectly()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => 'world'])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, []);
        $this->assertTrue($step->validate(['hello' => 'world']));
        $this->assertEquals(['hello'], $step->getFields());
    }

    public function testDefinitionDefersToSingleField()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => 'world'])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, ['hello_field' => 'test']);
        $this->assertTrue($step->validate(['test' => 'world']));
        $this->assertEquals(['test'], $step->getFields());
    }

    public function testDefinitionCoalesceField()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => 'right'])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, ['hello_field_coalesce' => 'foo,bar,test,baz,hello']);
        $this->assertTrue($step->validate(['test' => 'right', 'baz' => 'wrong']));
        $this->assertEquals(['foo', 'bar', 'test', 'baz', 'hello'], $step->getFields());
    }

    public function testDefinitionCoalesceFieldNoMatch()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello' => true]);

        $step = new Search_Action_ActionStep($action, ['hello_field_coalesce' => 'foo,bar,test,baz,hello']);
        $this->expectException(Search_Action_Exception::class);
        $step->validate([]);
        $this->assertEquals(['foo', 'bar', 'test', 'baz', 'hello'], $step->getFields());
    }

    public function testRequiresValueAsArrayButMissing()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello+' => false]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => []])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, []);
        $this->assertTrue($step->validate([]));
        $this->assertEquals(['hello'], $step->getFields());
    }

    public function testRequiresValueAsArrayAndSingleValue()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello+' => false]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => ['world']])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, ['hello' => 'world']);
        $this->assertTrue($step->validate([]));
        $this->assertEquals([], $step->getFields());
    }

    public function testRequiresValueAsArrayAndMultipleValues()
    {
        $action = $this->createMock('Search_Action_Action');
        $action
            ->method('getValues')
            ->willReturn(['hello+' => false]);
        $action->expects($this->once())
            ->method('validate')
            ->with($this->equalTo(new JitFilter(['hello' => ['a', 'b']])))
            ->willReturn(true);

        $step = new Search_Action_ActionStep($action, ['hello_field_multiple' => 'foo,bar,baz']);
        $this->assertTrue($step->validate(['foo' => 'a', 'baz' => 'b']));
        $this->assertEquals(['foo', 'bar', 'baz'], $step->getFields());
    }
}
