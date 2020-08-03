<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class WikiParser_OutputLinkTest extends TikiTestCase
{
    private $info;

    protected function setUp() : void
    {
        $this->info = [];
    }

    public function testCreateLink()
    {
        // ((Test)) on missing page
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=Test" title="Create page: Test" class="wiki wikinew text-danger tips">Test</a>', $link->getHtml());
    }

    public function testCreateLinkWithLanguage()
    {
        // ((Test)) on missing page, with multilingual specified
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setLanguage('fr');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=Test&lang=fr" title="Create page: Test" class="wiki wikinew text-danger tips">Test</a>', $link->getHtml());
    }

    public function testCreateLinkWithDescription()
    {
        // ((Test|Hello World))
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setDescription('Hello World');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=Test" title="Create page: Test" class="wiki wikinew text-danger tips">Hello World</a>', $link->getHtml());
    }

    public function testCreateLinkWithRelationType()
    {
        // (real(Test))
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setQualifier('real');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=Test" title="Create page: Test" class="wiki wikinew text-danger tips real">Test</a>', $link->getHtml());
    }

    public function testCreateLinkWithVeryBigName()
    {
        // If page name exceeds 158 characters, it must be trimmed.
        // Link will be to trimmed page while displayed text will be full name
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabase');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSize" title="Create page: TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSize" class="wiki wikinew text-danger tips">TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabase</a>', $link->getHtml());
    }

    public function testCreateExistingLinkWithVeryBigName()
    {
        // If page name exceeds 158 characters, it must be trimmed.
        // Link will be to trimmed page while displayed text will be full name
        $this->info['TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSize'] = [
            'pageName' => 'TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSize',
            'description' => 'Testing',
            'lastModif' => 1234567890,
        ];

        $link = new WikiParser_OutputLink;
        $link->setIdentifier('TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeTHISMUSTBETRIMMED');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);

        $this->assertLinkIs('<a href="TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSize" title="Testing" class="wiki wiki_page">TestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeOfTheDatabaseTestWithAVeryBigNameThatExceedsTheColumnSizeTHISMUSTBETRIMMED</a>', $link->getHtml());
    }

    public function testPageDoesExist()
    {
        $this->info['Test'] = [
            'pageName' => 'Test',
            'description' => 'Testing',
            'lastModif' => 1234567890,
        ];

        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);

        $this->assertLinkIs('<a href="Test" title="Testing" class="wiki wiki_page">Test</a>', $link->getHtml());
    }

    public function testInfoFunctionProvidesAlias()
    {
        $this->info['Test'] = [
            'pageName' => 'Test1.2',
            'description' => 'Testing',
            'lastModif' => 1234567890,
        ];

        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);

        $this->assertLinkIs('<a href="Test1.2" title="Testing" class="wiki wiki_page">Test</a>', $link->getHtml());
    }

    public function testExistsWithRelType()
    {
        $this->info['Test'] = [
            'pageName' => 'Test',
            'description' => 'Testing',
            'lastModif' => 1234567890,
        ];

        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Test');
        $link->setQualifier('abc');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);

        $this->assertLinkIs('<a href="Test" title="Testing" class="wiki wiki_page abc">Test</a>', $link->getHtml());
    }

    public function testUndefinedExternalLink()
    {
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('out:Test');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);

        $this->assertLinkIs('<a href="tiki-editpage.php?page=out%3ATest" title="Create page: out:Test" class="wiki wikinew text-danger tips">out:Test</a>', $link->getHtml());
    }

    public function testWithDefinedExternal()
    {
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('out:Test');
        $link->setExternals(
            [
                'out' => 'http://example.com/$page',
                'other' => 'http://www.example.com/$page',
            ]
        );

        $this->assertLinkIs('<a href="http://example.com/Test" class="wiki ext_page out">Test</a>', $link->getHtml());
    }

    public function testWithDefinedExternalAndDescription()
    {
        $link = new WikiParser_OutputLink;
        $link->setIdentifier('out:Test');
        $link->setDescription('ABC');
        $link->setExternals(
            [
                'out' => 'http://example.com/$page',
                'other' => 'http://www.example.com/$page',
            ]
        );

        $this->assertLinkIs('<a href="http://example.com/Test" class="wiki ext_page out">ABC</a>', $link->getHtml());
    }

    public function testHandlePlural()
    {
        $this->info['Policies'] = false;
        $this->info['Policy'] = [
            'pageName' => 'Policy',
            'description' => 'Some Page',
            'lastModif' => 1234567890,
        ];

        $link = new WikiParser_OutputLink;
        $link->setIdentifier('Policies');
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setWikiLinkBuilder([$this, 'getWikiLink']);
        $link->setHandlePlurals(true);

        $this->assertLinkIs('<a href="Policy" title="Some Page" class="wiki wiki_page">Policies</a>', $link->getHtml());
    }

    public function testRenderCreateLinkWithNamespace()
    {
        // ((Test)) within a page in HelloWorld namespace
        $link = new WikiParser_OutputLink;
        $link->setNamespace('HelloWorld', '_');
        $link->setIdentifier('Test');

        $this->assertLinkIs('<a href="tiki-editpage.php?page=HelloWorld_Test" title="Create page: HelloWorld_Test" class="wiki wikinew text-danger tips">Test</a>', $link->getHtml());
    }

    public function testRenderLinkWithinSameNamespace()
    {
        $this->info['HelloWorld_Test'] = [
            'pageName' => 'HelloWorld_Test',
            'prettyName' => 'HelloWorld / Test',
            'namespace' => 'HelloWorld',
            'namespace_parts' => ['HelloWorld'],
            'baseName' => 'Test',
            'description' => '',
            'lastModif' => 1234567890,
        ];

        // ((Test)) within a page in HelloWorld namespace
        $link = new WikiParser_OutputLink;
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setNamespace('HelloWorld', '_');
        $link->setIdentifier('Test');

        $this->assertLinkIs('<a href="HelloWorld_Test" title="HelloWorld / Test" class="wiki wiki_page">Test</a>', $link->getHtml());
    }

    public function testRenderFromDifferentNamespace()
    {
        $this->info['HelloWorld_Test'] = [
            'pageName' => 'HelloWorld_Test',
            'prettyName' => 'HelloWorld / Test',
            'namespace' => 'HelloWorld',
            'namespace_parts' => ['HelloWorld'],
            'baseName' => 'Test',
            'description' => '',
            'lastModif' => 1234567890,
        ];

        // ((Test)) within a page in HelloWorld namespace
        $link = new WikiParser_OutputLink;
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setNamespace('Foobar', '_');
        $link->setIdentifier('HelloWorld_Test');

        $this->assertLinkIs('<a href="HelloWorld_Test" title="HelloWorld / Test" class="wiki wiki_page"><span class="namespace first last">HelloWorld</span>Test</a>', $link->getHtml());
    }

    public function testRenderFromDifferentNamespaceWithMultipleParts()
    {
        $this->info['Abc_Def_HelloWorld_Test'] = [
            'pageName' => 'Abc_Def_HelloWorld_Test',
            'prettyName' => 'Abc / Def / HelloWorld / Test',
            'namespace' => 'Abc_Def_HelloWorld',
            'namespace_parts' => ['Abc', 'Def', 'HelloWorld'],
            'baseName' => 'Test',
            'description' => '',
            'lastModif' => 1234567890,
        ];

        // ((Test)) within a page in HelloWorld namespace
        $link = new WikiParser_OutputLink;
        $link->setWikiLookup([$this, 'getPageInfo']);
        $link->setNamespace('Foobar', '_');
        $link->setIdentifier('Abc_Def_HelloWorld_Test');

        $this->assertLinkIs('<a href="Abc_Def_HelloWorld_Test" title="Abc / Def / HelloWorld / Test" class="wiki wiki_page"><span class="namespace first">Abc</span><span class="namespace">Def</span><span class="namespace last">HelloWorld</span>Test</a>', $link->getHtml());
    }

    public function getPageInfo($page)
    {
        if (isset($this->info[$page])) {
            return $this->info[$page];
        }
    }

    public function getWikiLink($page)
    {
        return $page;
    }

    private function assertLinkIs($expect, $content)
    {
        $this->assertEquals($expect, $content);
    }
}
