<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package;

abstract class ComposerPackage implements PackageInterface
{
	protected $packageType;

	protected $name;
	protected $requiredVersion;
	protected $licence;
	protected $licenceUrl;
	protected $requiredBy;
	protected $scripts;

	/**
	 * @param $name
	 * @param $requiredVersion
	 * @param $licence
	 * @param $licenceUrl
	 * @param $requiredBy
	 * @param $scripts
	 */
	protected function setPackageInfo($name, $requiredVersion, $licence, $licenceUrl, $requiredBy, $scripts = [])
	{

		$this->packageType = Type::COMPOSER;

		$this->name = $name;
		$this->requiredVersion = $requiredVersion;
		$this->licence = $licence;
		$this->licenceUrl = $licenceUrl;
		$this->requiredBy = $requiredBy;
		$this->scripts = $scripts;
	}

	public function getType()
	{
		return $this->packageType;
	}

	public function getAsArray()
	{
		return [
			'key' => $this->getKey(),
			'name' => $this->name,
			'requiredVersion' => $this->requiredVersion,
			'licence' => $this->licence,
			'licenceUrl' => $this->licenceUrl,
			'requiredBy' => $this->requiredBy,
		];
	}

	public function getKey()
	{
		$className = static::class;
		$pos = strrpos($className, '\\');
		if ($pos === false) {
			return $className;
		}

		return substr($className, $pos + 1);
	}

	public function getScripts()
	{
		return $this->scripts;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getRequiredVersion()
	{
		return $this->requiredVersion;
	}

	/**
	 * @return mixed
	 */
	public function getLicence()
	{
		return $this->licence;
	}

	/**
	 * @return mixed
	 */
	public function getLicenceUrl()
	{
		return $this->licenceUrl;
	}

	/**
	 * @return mixed
	 */
	public function getRequiredBy()
	{
		return $this->requiredBy;
	}

}