<?php

class TikiLib_UriMergeTest extends PHPUnit\Framework\TestCase
{
	public function testFullReplace()
	{
		$this->assertEquals('http://www.example.com/', $this->merge('http://example.com/foo/bar?x=y', 'http://www.example.com'));
	}

	public function testAbsolutePath()
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com/foo/bar?x=y', '/foo/baz'));
	}

	public function testRelativePath()
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com/foo/bar?x=y', 'baz'));
	}

	public function testShortRelativePath()
	{
		$this->assertEquals('http://example.com/baz', $this->merge('http://example.com/foo', 'baz'));
	}

	public function testNoCurrentPath()
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com', 'foo/baz'));
	}

	public function testWithQueryString()
	{
		$this->assertEquals('http://example.com/foo/baz?y=x&a=b', $this->merge('http://example.com/foo/bar?x=y', 'baz?y=x&a=b'));
	}

	private function merge($first, $last)
	{
		return TikiLib::lib('tiki')->http_get_uri(new Zend\Uri\Http($first), $last)->toString();
	}
}
