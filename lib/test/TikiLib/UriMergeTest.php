<?php

class TikiLib_UriMergeTest extends PHPUnit\Framework\TestCase
{
	public function testFullReplace(): void
	{
		$this->assertEquals('http://www.example.com/', $this->merge('http://example.com/foo/bar?x=y', 'http://www.example.com'));
	}

	public function testAbsolutePath(): void
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com/foo/bar?x=y', '/foo/baz'));
	}

	public function testRelativePath(): void
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com/foo/bar?x=y', 'baz'));
	}

	public function testShortRelativePath(): void
	{
		$this->assertEquals('http://example.com/baz', $this->merge('http://example.com/foo', 'baz'));
	}

	public function testNoCurrentPath(): void
	{
		$this->assertEquals('http://example.com/foo/baz', $this->merge('http://example.com', 'foo/baz'));
	}

	public function testWithQueryString(): void
	{
		$this->assertEquals('http://example.com/foo/baz?y=x&a=b', $this->merge('http://example.com/foo/bar?x=y', 'baz?y=x&a=b'));
	}

	private function merge($first, $last)
	{
		return TikiLib::lib('tiki')->http_get_uri(new Laminas\Uri\Http($first), $last)->toString();
	}
}
