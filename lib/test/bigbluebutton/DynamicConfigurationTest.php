<?php
use Tiki\BigBlueButton\Configuration;

class BigBlueButton_DynamicConfigurationTest extends PHPUnit\Framework\TestCase
{
	private $xml;

	public function setUp() : void
	{
		$this->xml = file_get_contents(__DIR__ . '/config.xml');
	}

	public function testPassthrough()
	{
		$config = new Configuration($this->xml);

		$this->assertXmlStringEqualsXmlString($this->xml, $config->getXml());
	}

	public function testDisableModule()
	{
		$config = new Configuration($this->xml);
		$config->removeModule('PhoneModule');

		$xml = $config->getXml();
		$this->assertStringNotContainsString('<module name="PhoneModule"', $xml);
	}

	public function testDisableModuleWithDependencies()
	{
		$config = new Configuration($this->xml);
		$config->removeModule('PresentModule');

		$xml = $config->getXml();
		$this->assertStringNotContainsString('<module name="PresentModule"', $xml);
		$this->assertStringNotContainsString('<module name="WhiteboardModule"', $xml);
	}
}
