<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_code.php');

class WikiPlugin_CodeTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider provider
	 * @param $data
	 * @param $expectedOutput
	 * @param array $params
	 */
	public function testWikiPluginCode($data, $expectedOutput, $params = []): void
	{
		$this->assertEquals($expectedOutput, wikiplugin_code($data, $params));
	}

	public function provider(): array
	{
		return [
			['', '<pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox1" ></pre>'],
			['<script>alert(document.cookie);</script>', '<pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox2" ><script>alert(document.cookie);</script></pre>', ['ishtml' => 1]],
			['~np~~tc~{img fileId="42"}~/tc~~/np~', '<pre class="codelisting"  data-theme="off"  data-wrap="1"  dir="ltr"  style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="codebox3" >~np~~tc~{img fileId=&quot;42&quot;}~/tc~~/np~</pre>']
		];
	}
}
