<?php

namespace Tiki\Package;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;

class PackageCommandHelper
{
	/**
	 * Get information from available packages
	 *
	 * @param $availableComposerPackages
	 * @return array|null
	 */
	public static function getAvailablePackagesInfo($availableComposerPackages)
	{
		$packagesInfo = [];

		if (! empty($availableComposerPackages)) {
			foreach ($availableComposerPackages as $availableComposerPackage) {
				$packagesInfo[] = [
					$availableComposerPackage['key'],
					$availableComposerPackage['name'],
					$availableComposerPackage['requiredVersion'],
					$availableComposerPackage['licence'],
					implode(',', $availableComposerPackage['requiredBy'])
				];
			}
		}

		return $packagesInfo;
	}

	/**
	 * Render a table with available packages
	 *
	 * @param $output
	 * @param $packagesInfo
	 * @return bool
	 */
	public static function renderAvailablePackagesTable($output, $packagesInfo)
	{
		if (empty($packagesInfo)) {
			return false;
		}

		$availablePackagesHeaders = [
			'ID',
			'Package Name',
			'Required Version',
			'Licence',
			'Required By'
		];

		$table = new Table($output);
		$table
			->setHeaders($availablePackagesHeaders)
			->setRows($packagesInfo);
		$table->render();
	}

	/**
	 * Get information from installed packages
	 *
	 * @param $installedComposerPackages
	 * @return array|null
	 */
	public static function getInstalledPackagesInfo($installedComposerPackages)
	{
		$packagesInfo = [];

		if (! empty($installedComposerPackages)) {
			foreach ($installedComposerPackages as $installedComposerPackage) {
				$packagesInfo[] = [
					$installedComposerPackage['key'],
					$installedComposerPackage['name'],
					$installedComposerPackage['required'],
					$installedComposerPackage['status'],
					$installedComposerPackage['installed']
				];
			}
		}

		return $packagesInfo;
	}

	/**
	 * Render a table with installed packages
	 *
	 * @param $output
	 * @param $packagesInfo
	 * @return bool
	 */
	public static function renderInstalledPackagesTable($output, $packagesInfo)
	{
		if (empty($packagesInfo)) {
			return false;
		}

		$installedPackagesHeaders = [
			'ID',
			'Package Name',
			'Required Version',
			'Status',
			'Installed Version'
		];

		$table = new Table($output);
		$table
			->setHeaders($installedPackagesHeaders)
			->setRows($packagesInfo);
		$table->render();
	}

	/**
	 * Wrapper for standard console question
	 *
	 * @param $question
	 * @param null $default
	 * @param string $character
	 * @return Question
	 */
	public static function getQuestion($question, $default = null, $character = ':')
	{

		if ($default !== null) {
			$question = sprintf($question . ' [%s]' . $character . '  ', $default);
		} else {
			$question = $question . $character . ' ';
		}

		return new Question($question, $default);
	}

	/**
	 * Validate Package Selection
	 *
	 * @param $answer
	 * @param $packages
	 * @return array
	 */
	public static function validatePackageSelection($answer, $packages)
	{
		$selectedPackage = '';

		if (empty($answer)) {
			throw new \RuntimeException(
				'You must select an #ID'
			);
		} else {
			$packagesKeys = array_filter(array_map('trim', explode(' ', $answer)));
			$invalidPackagesKeys = array_diff($packagesKeys, array_column($packages, 'key'));

			if ($invalidPackagesKeys) {
				throw new \RuntimeException(
					'Invalid package(s) ID(s) #' . implode(',', $invalidPackagesKeys)
				);
			}

			foreach ($packagesKeys as $packagesKey) {
				if (in_array($packagesKey, array_column($packages, 'key'))) {
					$selectedPackage = $packagesKey;
					break;
				}
			}

			return $selectedPackage;
		}
	}

	/**
	 * Get removable packages
	 *
	 * @param $installedComposerPackages
	 * @return array
	 */
	public static function getRemovablePackages($installedComposerPackages)
	{
		$removablePackages = [];

		foreach ($installedComposerPackages as $installedComposerPackage) {
			if (! empty($installedComposerPackage['key'])) {
				$removablePackages[] = $installedComposerPackage;
			}
		}

		return $removablePackages;
	}

	/**
	 * Get removable packages
	 *
	 * @param $installedComposerPackages
	 * @return array
	 */
	public static function getUpdatablePackages($installedComposerPackages)
	{
		$updatablePackages = [];

		foreach ($installedComposerPackages as $installedComposerPackage) {
			if (! empty($installedComposerPackage['key'])
				&& ! empty($installedComposerPackage['installed'])
				&& ! empty($installedComposerPackage['upgradeVersion'])
			) {
				$updatablePackages[] = $installedComposerPackage;
			}
		}

		return $updatablePackages;
	}
}
