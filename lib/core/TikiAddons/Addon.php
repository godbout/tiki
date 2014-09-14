<?php
// (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiAddons_Addon
{

	private $libraries = array();
	private $configuration = null;
	public $smarty = null;

	function __construct($folder)
	{
		if (strpos($folder, '/') !== false && strpos($folder, '_') === false) {
			$folder = str_replace('/', '_', $folder);
		}
		$prefname = 'ta_' . $folder . '_on';
			if (!isset($GLOBALS['prefs'][$prefname]) || $GLOBALS['prefs'][$prefname] != 'y') {
			throw new Exception(tra('Addon is not activated: ') . $folder);
		}
		$file = TIKI_PATH . "/addons/$folder/tikiaddon.json";
		$this->configuration = json_decode(file_get_contents($file));
		$this->checkDependencies();
		if ($this->configuration->smarty) {
			$this->smarty = new Smarty;
			$this->smarty->setCompileDir(TIKI_PATH . '/templates_c');
			$this->smarty->setTemplateDir(TIKI_PATH . "/addons/" . $this->getVendor() . "_" . $this->getShortName() . "/templates/");
			$this->smarty->setPluginsDir(
				array(
					TIKI_PATH . '/' . TIKI_SMARTY_DIR,    // the directory order must be like this to overload a plugin
					SMARTY_DIR . 'plugins',
				)
			);
			$secpol = new Tiki_Security_Policy($this->smarty);
			$secpol->secure_dir[] = dirname(TIKI_PATH . "/addons/" . $this->getVendor() . "_" . $this->getShortName() . "/templates/");
			$this->smarty->enableSecurity($secpol);
			$this->smarty->assign('prefs', $GLOBALS['prefs']);
		}
	}

	function getName()
	{
		return $this->configuration->name;
	}

	function getPackage()
	{
		return $this->configuration->package;
	}

	function getVersion()
	{
		return $this->configuration->version;
	}

	function getURL()
	{
		return $this->configuration->url;
	}

	function getVendor()
	{
		$parts = explode('/', $this->getPackage());
		return $parts[0];
	}

	function getShortName()
	{
		$parts = explode('/', $this->getPackage());
		return $parts[1];
	}

	function getDepends() {
		if (is_array($this->configuration->depends)) {
			return $this->configuration->depends;
		} else {
			return array();
		}
	}

	function getSemanticVersion($version)
	{
		return explode('.', $version);
	}

	function lib($name)
	{
		if (isset($this->libraries[$name])) {
			return $this->libraries[$name];
		}

		$container = TikiInit::getContainer();
		$service = 'tikiaddon.' . $this->getVendor() . '.' . $this->getShortName() . '.' . $name;

		if ($lib = $container->get($service, \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
			return $lib;
		}

		unlink(TIKI_PATH . '/temp/cache/container.php'); // Remove the container cache to help transition
		throw new Exception(tr("%0 library not found. It may be a typo or caused by a recent update.", $name));
	}

	private function checkDependencies() {
		$installed = array();
		$versions = array();
		foreach (Tikiaddons::getInstalled() as $conf) {
			$versions[$conf->package] = $conf->version;
			$installed[] = $conf->package;
		}
		foreach ($this->getDepends() as $depend) {
			if (!in_array($depend->package, $installed)) {
				throw new Exception($this->getPackage() . tra(' cannot load because it is missing the following dependency: ') . $depend->package);
			}
			if (!$this->checkVersionMatch($versions[$depend->package], $depend->version)) {
				throw new Exception($this->getPackage() . tra(' cannot load because it is missing a required version of a dependency: ') . $depend->package . ' versiom ' . $depend->version);
			}
		}
		return true;
	}

	function checkVersionMatch($version, $pattern) {
		$semanticVersion = $this->getSemanticVersion($version);
		$semanticPattern = $this->getSemanticVersion($pattern);
		foreach ($semanticPattern as $k => $v) {
			if (!isset($semanticVersion[$k])) {
				$semanticVersion[$k] = 0;
			}
			if (strpos($v, '-') !== false) {
				if ((int) $semanticVersion[$k] > (int) str_replace('-', '', $v)) {
					return false;
				}
			} elseif (strpos($v, '+') !== false) {
				if ((int) $semanticVersion[$k] < (int) str_replace('+', '', $v)) {
					return false;
				}
			} elseif ($v != '*') {
				if ((int) $semanticVersion[$k] !== (int) $v) {
					return false;
				}
			}
		}
		return true;
	}
}