<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Package;

class ComposerCli
{

	const COMPOSER_PHAR = 'temp/composer.phar';
	const COMPOSER_CONFIG = 'composer.json';
	const PHP_COMMAND_NAMES = [
		'php',
		'php56',
		'php5.6',
		'php5.6-cli',
	];
	//const PHP_MIN_VERSION = '5.6.0';
	const PHP_MIN_VERSION = '5.5.0';

	protected $basePath = '';
	protected $phpCli = null;

	public function __construct($basePath)
	{
		$basePath = realpath($basePath);
		if ($basePath) {
			$this->basePath = $basePath . '/';
		}
	}

	protected function getComposerConfigFilePath()
	{
		return $this->basePath . self::COMPOSER_CONFIG;
	}

	protected function getComposerConfig()
	{
		if (!$this->checkConfigExists()) {
			return false;
		}
		$content = json_decode(file_get_contents($this->getComposerConfigFilePath()), true);

		return $content;
	}

	protected function getComposerConfigOrDefault()
	{
		$content = $this->getComposerConfig();
		if ($content !== false) {
			return $content;
		}

		$distFile = $this->basePath . self::COMPOSER_CONFIG . '.dist';
		if (!file_exists($distFile)) {
			$content = json_decode(file_get_contents($distFile), true);
			if ($content !== false) {
				return $content;
			}
		}

		return json_decode('{"minimum-stability": "stable","config": {"process-timeout": 5000,"bin-dir": "bin"}}', true);
	}

	protected function getComposerPharPath()
	{
		return $this->basePath . self::COMPOSER_PHAR;
	}

	protected function getPhpVersion($php)
	{
		exec($php . ' --version', $output);
		foreach ($output as $line) {
			$parts = explode(' ', $line);
			if ($parts[0] === 'PHP') {
				return $parts[1];
			}
		}

		return '';
	}

	protected function getPhpPath()
	{
		if (!is_null($this->phpCli)) {
			return $this->phpCli;
		}

		$this->phpCli = false;
		foreach (explode(':', $_SERVER['PATH']) as $path) {
			foreach (self::PHP_COMMAND_NAMES as $cli) {
				$possibleCli = $path . DIRECTORY_SEPARATOR . $cli;
				if (file_exists($possibleCli) && is_executable($possibleCli)) {
					$version = $this->getPhpVersion($possibleCli);
					if (version_compare($version, self::PHP_MIN_VERSION, '<')) {
						continue;
					}
					$this->phpCli = $possibleCli;

					return $this->phpCli;
				}
			}
		}

		return $this->phpCli;
	}

	public function canExecuteComposer()
	{
		static $canExecute = null;
		if (!is_null($canExecute)) {
			return $canExecute;
		}

		$canExecute = false;

		if (file_exists($this->getComposerPharPath())) {
			exec($this->getPhpPath() . ' ' . $this->getComposerPharPath() . ' --no-ansi --version', $output);
			if (is_array($output) && strncmp($output[0], 'Composer', 8) == 0) {
				$canExecute = true;
			}
		}

		return $canExecute;
	}

	protected function execComposer($args, $asString = false)
	{
		$output = [];
		$code = 1;

		if (!is_array($args)) {
			$args = array($args);
		}

		$cmd = $this->getPhpPath();
		if ($cmd) {
			array_unshift($args, $this->getComposerPharPath());
		} else {
			$cmd = $this->getComposerPharPath();
		}

		$args = array_map(
			function ($item) {
				return escapeshellarg($item);
			},
			$args
		);

		//TODO: Move to Symfony\Process ?
		$cmdString = escapeshellcmd($cmd) . ' ' . implode(' ', $args) . ' 2>&1';
		exec($cmdString, $output, $code);

		if ($asString) {
			$output = implode("\n", $output);
		}

		return [$output, $code];
	}

	protected function execShow()
	{
		if (!$this->canExecuteComposer()) {
			return [];
		}
		list($result) = $this->execComposer(['--format=json', 'show'], true);
		$json = json_decode($result, true);

		return $json;
	}


	public function checkConfigExists()
	{
		return file_exists($this->getComposerConfigFilePath());
	}

	public function getListOfPackagesFromConfig()
	{
		if (!$this->checkConfigExists() || !$this->canExecuteComposer()) {
			return false;
		}

		$content = json_decode(file_get_contents($this->getComposerConfigFilePath()), true);
		$composerShow = $this->execShow();

		$installedPackages = [];
		foreach ($composerShow['installed'] as $package) {
			$installedPackages[$package['name']] = $package;
		}

		$result = [];

		foreach ($content['require'] as $name => $version) {
			if (isset($installedPackages[$name])) {
				$result[] = [
					'name' => $name,
					'status' => ComposerManager::STATUS_INSTALLED,
					'required' => $version,
					'installed' => $installedPackages[$name]['version'],
				];
			} else {
				$result[] = [
					'name' => $name,
					'status' => ComposerManager::STATUS_MISSING,
					'required' => $version,
					'installed' => '',
				];
			}
		}

		return $result;
	}

	public function installMissingPackages()
	{
		if (!$this->checkConfigExists() || !$this->canExecuteComposer()) {
			return false;
		}

		list($output) = $this->execComposer(['--no-ansi', '--no-dev', '--prefer-dist', 'update', 'nothing'], true);

		return $output;
	}

	public function installPackage(ComposerPackage $package)
	{
		if (!$this->canExecuteComposer()) {
			return false;
		}

		$composerJson = $this->getComposerConfigOrDefault();
		$composerJson = $this->addComposerPackageToJson(
			$composerJson,
			$package->getName(),
			$package->getRequiredVersion(),
			$package->getScripts()
		);
		$fileContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($this->getComposerConfigFilePath(), $fileContent);

		$commandOutput = $this->installMissingPackages();

		return tr('= New composer.json file content') . ":\n\n"
		. $fileContent . "\n\n"
		. tr('= Composer execution output') . ":\n\n"
		. $commandOutput;
	}

	public function addComposerPackageToJson($composerJson, $package, $version, $scripts = [])
	{

		$scriptsKeys = [
			'pre-install-cmd',
			'post-install-cmd',
			'pre-update-cmd',
			'post-update-cmd',
		];

		if (!is_array($composerJson)) {
			$composerJson = [];
		}
		// require
		if (!isset($composerJson['require'])) {
			$composerJson['require'] = [];
		}
		if (!isset($composerJson['require'][$package])) {
			$composerJson['require'][$package] = $version;
		}

		// scripts
		if (is_array($scripts) && count($scripts)) {
			if (!isset($composerJson['scripts'])) {
				$composerJson['scripts'] = [];
			}
			foreach ($scriptsKeys as $type) {
				if (!isset($scripts[$type])) {
					continue;
				}
				$scriptList = $scripts[$type];
				if (is_string($scriptList)) {
					$scriptList = [$scriptList];
				}
				if (!count($scriptList)) {
					continue;
				}
				if (!isset($composerJson['scripts'][$type])) {
					$composerJson['scripts'][$type] = [];
				}
				foreach ($scriptList as $scriptString) {
					$composerJson['scripts'][$type][] = $scriptString;
				}
				$composerJson['scripts'][$type] = array_unique($composerJson['scripts'][$type]);
			}
		}

		return $composerJson;
	}
}