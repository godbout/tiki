<?php

use Symfony\Component\Yaml\Yaml;

$tikiBase = realpath(__DIR__ . '/../..');
require_once $tikiBase . '/vendor_bundled/vendor/autoload.php';

const TIKI_GITLAB_ID = 6204173;
$tags = [];
$versions = [];

$tempDir = $tikiBase . '/temp/satis/';
if (!file_exists($tempDir)) {
	mkdir($tempDir);
}

function getFromApi($page, $url, &$output)
{
	$contents = json_decode(file_get_contents($url . "?per_page=100&page=" . $page));

	if (empty($contents)) {
		return;
	}

	foreach ($contents as $content) {
		$output[] = $content->name;
	}

	getFromApi(++$page, $url, $output);
}

function getPackages($path, &$payload)
{
	$repositoryUrl = "https://gitlab.com/tikiwiki/tiki/-/raw/{$path}/";

	$originalRepositoryUrl = $repositoryUrl;

	preg_match('/(\d*)\./', $path, $match);
	$majorVersion = intval($match[1] ?? false);

	if ($path == 'master' || $majorVersion >= 17) {
		$repositoryUrl .= 'vendor_bundled/';
	}

	$composerLockContents = json_decode(@file_get_contents("$repositoryUrl/composer.lock"));

	if (empty($composerLockContents)) {
		echo 'Unable to parse composer.lock - ' . $repositoryUrl . '/composer.lock' . PHP_EOL;
		return;
	}

	$types = ['packages', 'packages-dev'];

	foreach ($types as $type) {

		if (!isset($composerLockContents->$type)) {
			continue;
		}

		foreach ($composerLockContents->$type as $package) {

			if (!isset($payload['used_packages'][$package->name])) {
				$payload['used_packages'][$package->name] = [$package->version];
			} elseif (!in_array($package->version, $payload['used_packages'][$package->name])) {
				$payload['used_packages'][$package->name][] = $package->version;
			}

			if (!empty($package->dist->url)) {
				$payload['tiki_composer_site_used_zips'][] = str_replace('https://composer.tiki.org/', '', $package->dist->url);
			}
		}
	}

	if ($path != 'master' && $majorVersion < 18) {
		return;
	}

	$installableComposerPackages = @file_get_contents("$originalRepositoryUrl/lib/core/Tiki/Package/ComposerPackages.yml");

	if ($installableComposerPackages !== false) {
		$installablePackages = Yaml::parse($installableComposerPackages);

		foreach ($installablePackages as $installablePackage) {
			if (!isset($payload['used_packages'][$installablePackage['name']])) {
				$payload['used_packages'][$installablePackage['name']] = [
					$installablePackage['requiredVersion']
				];
			} elseif (!in_array($installablePackage['requiredVersion'], $payload['used_packages'][$installablePackage['name']])) {
				$payload['used_packages'][$installablePackage['name']][] = $installablePackage['requiredVersion'];
			}

			if (!in_array($installablePackage['name'], $payload['tiki_packages_versionless'])) {
				$payload['tiki_packages_versionless'][] = $installablePackage['name'];
			}
		}
	}
}

function isVersionless($versionless_package_list, $package_name)
{
	foreach ($versionless_package_list as $package) {
		if (strpos($package_name, $package) !== false) {
			return true;
		}
	}

	return false;
}

function getMasterSatis()
{
	$url = 'https://gitlab.com/tikiwiki/tiki/-/raw/master/doc/devtools/satis.json';
	$content = file_get_contents($url);

	return json_decode($content);
}

echo "Fetching gitlab tags...\n";
getFromApi(1, "https://gitlab.com/api/v4/projects/" . TIKI_GITLAB_ID . "/repository/tags", $tags);
echo "Fetching gitlab versions...\n";
getFromApi(1, "https://gitlab.com/api/v4/projects/" . TIKI_GITLAB_ID . "/repository/branches", $versions);

// Only get version/tags since Tiki 11.0
$versions = array_filter($versions, function ($version) {
	return preg_match('/^1[1-9].x$|^[2-9]\d\.x$|^master$/', $version);
});

$tags = array_filter($tags, function ($tag) {
	$tag = preg_replace('/^tags\//', '', $tag);
	return \Composer\Semver\Comparator::greaterThanOrEqualTo($tag, '11');
});

$payload = [
	'used_packages' => [],
	'tiki_composer_site_used_zips' => [],
	'tiki_packages_versionless' => []
];

foreach ($versions as $version) {
	echo "version - $version\n";
	getPackages($version, $payload);
}

foreach ($tags as $tag) {
	echo "tag - $tag\n";
	getPackages($tag, $payload);
}

$used_packages = array_keys($payload['used_packages']);

$satis = getMasterSatis();

foreach ($satis->require as $package_name => $package_info) {
	if (!in_array($package_name, $used_packages)) {
		unset($satis->require->{$package_name});
	}
}

foreach ($satis->repositories as $key => $repository) {
	if ($repository->type !== 'package') {
		continue;
	}

	if (!isset($payload['used_packages'][$repository->package->name]) || !in_array($repository->package->version, $payload['used_packages'][$repository->package->name])) {
		unset($satis->repositories[$key]);
	}
}

$satis->repositories = array_values($satis->repositories);

file_put_contents('satis.json', json_encode($satis, 192)); // JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT

/*
 *  AVAILABILITY AND REDUNDANCY OF https://composer.tiki.org/ PACKAGES
*/

$site_available_packages = explode("\n", file_get_contents('https://composer.tiki.org/dist-file-list.txt'));
$packages_to_remove_from_site = [];
$packages_not_in_site = [];

foreach ($site_available_packages as $package) {
	if (!in_array($package, $payload['tiki_composer_site_used_zips']) && !isVersionless($payload['tiki_packages_versionless'], $package)) {
		$packages_to_remove_from_site[] = $package;
	}
}
$file = $tempDir . 'unused_tiki_packages_in_tiki_composer_site.txt';
file_put_contents($file, implode("\n", $packages_to_remove_from_site));
echo 'File created: ' . $file . PHP_EOL;

foreach ($payload['tiki_composer_site_used_zips'] as $package) {
	if (!in_array($package, $site_available_packages)) {
		$packages_not_in_site[] = $package;
	}
}
$file = $tempDir . 'packages_in_tiki_but_not_in_site.txt';
file_put_contents($file, implode("\n", $packages_not_in_site));
echo 'File created: ' . $file . PHP_EOL;

// This is easy to identify the versions used by all tiki versions
$file = $tempDir . 'all_tiki_used_packages.json';
file_put_contents($file, json_encode($payload['used_packages'], 192));
echo 'File created: ' . $file . PHP_EOL;
