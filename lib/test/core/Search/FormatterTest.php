<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_FormatterTest extends PHPUnit\Framework\TestCase
{
    public function testBasicFormatter()
    {
        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=object_type})\n");

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format(
            [
                ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                ['object_type' => 'wiki page', 'object_id' => 'SomePage'],
            ]
        );

        $expect = <<<OUT
* HomePage (wiki page)
* SomePage (wiki page)

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testSpecifyFormatter()
    {
        global $prefs;
        $prefs['short_date_format'] = '%b %e, %Y';

        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=modification_date format=date})\n");

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format(
            [
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'HomePage',
                    'modification_date' => strtotime('2010-10-10 10:10:10')
                ],
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'SomePage',
                    'modification_date' => strtotime('2011-11-11 11:11:11')
                ],
            ]
        );

        $expect = <<<OUT
* HomePage (Oct 10, 2010)
* SomePage (Nov 11, 2011)

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testUnknownFormattingRule()
    {
        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=object_type format=doesnotexist})\n");

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format(
            [
                ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                ['object_type' => 'wiki page', 'object_id' => 'SomePage'],
            ]
        );

        $expect = <<<OUT
* HomePage (Unknown formatting rule 'doesnotexist' for 'object_type')
* SomePage (Unknown formatting rule 'doesnotexist' for 'object_type')

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testValueNotFound()
    {
        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=doesnotexist} ({display name=doesnotexisteither default=Test})\n");

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format([['object_type' => 'wiki page', 'object_id' => 'HomePage'], ]);

        $expect = <<<OUT
* No value for 'doesnotexist' (Test)

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testBasicSmartyFormatter()
    {
        $plugin = new Search_Formatter_Plugin_SmartyTemplate(__DIR__ . '/basic.tpl');
        $plugin->setData(['foo' => ['bar' => 'baz'], ]);

        $formatter = new Search_Formatter($plugin);

        // required for the SmartyFormatter since r59367
        $GLOBALS['base_url'] = '';

        $output = $formatter->format(
            [
                ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                ['object_type' => 'wiki page', 'object_id' => 'SomePage'],
            ]
        );

        $expect = <<<OUT
<div>~np~<table>
	<caption>baz: 2</caption>
	<tr><th>Object</th><th>Type</th></tr>
	<tr><td>HomePage</td><td>wiki page</td></tr>
	<tr><td>SomePage</td><td>wiki page</td></tr>
</table>
~/np~</div>
OUT;
        $this->assertXmlStringEqualsXmlString($expect, "<div>$output</div>");
    }

    public function testForEmbeddedMode()
    {
        $plugin = new Search_Formatter_Plugin_SmartyTemplate(__DIR__ . '/embedded.tpl', true);

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format(
            [
                ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                ['object_type' => 'wiki page', 'object_id' => 'SomePage'],
            ]
        );

        $expect = <<<OUT
<div>~np~<table>
	<caption>Count: 2</caption>
	<tr><th>Object</th><th>Type</th></tr>
	<tr><td>HomePage</td><td>wiki page</td></tr>
	<tr><td>SomePage</td><td>wiki page</td></tr>
</table>
~/np~</div>
OUT;
        $this->assertXmlStringEqualsXmlString($expect, "<div>$output</div>");
    }

    public function testAdditionalFieldDefinition()
    {
        $plugin = new Search_Formatter_Plugin_SmartyTemplate(__DIR__ . '/basic.tpl');

        $formatter = new Search_Formatter($plugin);
        $formatter->addSubFormatter('object_id', new Search_Formatter_Plugin_WikiTemplate("{display name=object_id}\n{display name=description default=None}"));

        $output = $formatter->format(
            [
                ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                ['object_type' => 'wiki page', 'object_id' => 'SomePage', 'description' => 'About'],
            ]
        );

        $expect = <<<OUT
<div>~np~<table>
	<caption>Count: 2</caption>
	<tr><th>Object</th><th>Type</th></tr>
	<tr><td>~/np~HomePage
None~np~</td><td>wiki page</td></tr>
	<tr><td>~/np~SomePage
About~np~</td><td>wiki page</td></tr>
</table>
~/np~</div>
OUT;
        $this->assertXmlStringEqualsXmlString($expect, "<div>$output</div>");
    }

    public function testPaginationInformationProvided()
    {
        $plugin = new Search_Formatter_Plugin_SmartyTemplate(__DIR__ . '/paginate.tpl');

        $formatter = new Search_Formatter($plugin);
        $output = $formatter->format(
            new Search_ResultSet(
                [
                    ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
                    ['object_type' => 'wiki page', 'object_id' => 'SomePage', 'description' => 'About'],
                ],
                22,
                20,
                10
            )
        );

        $this->assertRegExp('/<li[^>]*><a[^>]*>1<\/a><\/li>/', $output);
        $this->assertRegExp('/<li[^>]*><a[^>]*>2<\/a><\/li>/', $output);
        $this->assertRegExp('/<li[^>]*><a[^>]*>2<\/a><\/li>/', $output);
        $this->assertRegExp('/<li[^>]*><span[^>]*>3 <span[^>]*>/', $output);
        $this->assertStringNotContainsString('>4<', $output);
    }

    public function testSpecifyDataSource()
    {
        $searchResult = Search_ResultSet::create([
            ['object_type' => 'wiki page', 'object_id' => 'HomePage'],
            ['object_type' => 'wiki page', 'object_id' => 'SomePage'],
        ]);
        $withData = [
            ['object_type' => 'wiki page', 'object_id' => 'HomePage', 'description' => 'ABC'],
            ['object_type' => 'wiki page', 'object_id' => 'SomePage', 'description' => 'DEF'],
        ];

        $source = $this->createMock('Search_Formatter_DataSource_Interface');
        for ($i = 0; $i < 4; $i++) {
            $source->expects($this->at($i))
                ->method('getData')
                ->willReturnCallback(function ($entry, $field) use (&$withData, $i) {
                    $this->assertContains($field, ['object_id', 'description']);

                    return $withData[(int)($i / 2)];
                });
        }

        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=object_id} ({display name=description})\n");

        $formatter = new Search_Formatter($plugin);
        $searchResult->applyTransform(new Search_Formatter_Transform_DynamicLoader($source));

        $output = $formatter->format($searchResult);

        $expect = <<<OUT
* HomePage (ABC)
* SomePage (DEF)

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testFormatValueAsLink()
    {
        global $prefs;
        $prefs['feature_sefurl'] = 'y';

        $plugin = new Search_Formatter_Plugin_WikiTemplate("* {display name=title format=objectlink}\n");

        $formatter = new Search_Formatter($plugin);

        $output = $formatter->format(
            [
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'HomePage',
                    'title' => 'Home'
                ],
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'Some Page',
                    'title' => 'Test'
                ],
            ]
        );

        $expect = <<<OUT
* ~np~<a href="HomePage" class="" title="Home" data-type="wiki page" data-object="HomePage">Home</a>~/np~
* ~np~<a href="Some Page" class="" title="Test" data-type="wiki page" data-object="Some Page">Test</a>~/np~

OUT;
        $this->assertEquals($expect, $output);
    }

    public function testLinkInsideSmartyTemplate()
    {
        global $prefs;
        $prefs['feature_sefurl'] = 'y';

        $plugin = new Search_Formatter_Plugin_SmartyTemplate(__DIR__ . '/basic.tpl');

        $formatter = new Search_Formatter($plugin);
        $formatter->addSubFormatter('object_id', new Search_Formatter_Plugin_WikiTemplate("{display name=object_id format=objectlink}"));

        $output = $formatter->format(
            [
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'HomePage'
                ],
            ]
        );

        $expect = <<<OUT
<div>~np~<table>
	<caption>Count: 1</caption>
	<tr><th>Object</th><th>Type</th></tr>
	<tr><td><a href="HomePage" class="" title="HomePage" data-type="wiki page" data-object="HomePage">HomePage</a></td><td>wiki page</td></tr>
</table>
~/np~</div>
OUT;
        $this->assertXmlStringEqualsXmlString($expect, "<div>$output</div>");
    }

    public function testHighlightRequested()
    {
        $plugin = new Search_Formatter_Plugin_WikiTemplate('{display name=highlight}');

        $resultSet = new Search_ResultSet(
            [
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'HomePage',
                    'content' => 'Hello World'
                ],
                [
                    'object_type' => 'wiki page',
                    'object_id' => 'SomePage',
                    'content' => 'Test'
                ],
            ],
            22,
            20,
            10
        );
        $resultSet->setHighlightHelper(new Search_FormatterTest_HighlightHelper);

        $formatter = new Search_Formatter($plugin);
        $output = $formatter->format($resultSet);

        $this->assertStringContainsString('<strong>Hello</strong>', $output);
    }
}

class Search_FormatterTest_HighlightHelper implements Laminas\Filter\FilterInterface
{
    public function filter($content)
    {
        return str_replace('Hello', '<strong>Hello</strong>', $content);
    }
}
