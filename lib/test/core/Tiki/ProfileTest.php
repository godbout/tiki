<?php

class ProfileTest extends TikiTestCase
{
	/**
	 * @dataProvider versionProfiles
	 *
	 * @param $profileData
	 * @param $expected
	 */
	public function testGetTikiSupportedVersions($profileData, $expected)
	{
		$profile = Tiki_Profile::fromString($profileData);

		$this->assertEquals($expected, $profile->getTikiSupportedVersions());
	}

	public function versionProfiles()
	{
		return [
			[
				'{CODE(caption=>YAML,wrap=1)}' .
				\Symfony\Component\Yaml\Yaml::dump(
					[
						'profile' => [
							'tiki' => '^19.0'
						],
						'preferences' => [
							'feature_trackers' => 'y'
						],
					]
				) . '{CODE}',
				'^19.0'
			],
		];
	}

	/**
	 * @dataProvider tikiVersionCompatibility
	 * @param $profileData
	 * @param $tikiVersion
	 * @param $expected
	 */
	public function testTikiVersionCompatibility($profileData, $tikiVersion, $expected)
	{

		$profile = Tiki_Profile::fromString($profileData);

		$this->assertEquals($expected, $profile->isCompatible($tikiVersion));
	}

	public function tikiVersionCompatibility()
	{
		$code = "{CODE(caption=>YAML,wrap=1)}\n%s\n{CODE}";

		$base = [
			'profile' => [
				'tiki' => '^19.0',
			],
			'preferences' => [
				'feature_trackers' => 'y'
			]
		];

		$versionsLoop = [
			[
				'constraint' => '>18.3',
				'tiki' => [
					'19.0alpha',
					'19.0beta1',
					'19.0RC1',
					'19.0',
					'19.1',
				],
				'expected' => true
			],
			[
				'constraint' => '^19.0',
				'tiki' => [
					'19.0',
					'19.1',
				],
				'expected' => true
			],
			[
				'constraint' => '^19.0',
				'tiki' => [
					'15.0',
					'18.3',
					'20.0alpha',
					'20.0',
				],
				'expected' => false
			],
			[
				'constraint' => '^19.0-stable',
				'tiki' => [
					'19.0alpha',
					'19.0beta1',
					'19.0RC1',
				],
				'expected' => false
			],
		];

		$data = [];
		foreach ($versionsLoop as $item) {
			$base['profile']['tiki'] = $item['constraint'];
			$expected = $item['expected'];
			foreach ($item['tiki'] as $version) {
				$data[] = [
					sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
					$version,
					$expected
				];
			}
		}

		$base['profile']['tiki'] = '';
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			'19.x',
			true
		];

		unset($base['profile']['tiki']);
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			'19.x',
			true
		];

		unset($base['profile']);
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			'19.x',
			true
		];

		return $data;
	}

	/**
	 * @dataProvider profileDependencies
	 */
	public function testGetReferences($profileData, $expected)
	{
		$profile = Tiki_Profile::fromString($profileData);

		$this->assertEquals($expected, $profile->getReferences());
	}

	/**
	 * @dataProvider profileReferences
	 * @param $reference
	 * @param $expected
	 */
	public function testIsValidReference($reference, $expected)
	{
		$this->assertEquals($expected, Tiki_Profile::isValidReference($reference));
	}

	public function profileReferences()
	{
		return [
			['$profiles.tiki.org:Test_All_Themes:Test_All_Themes', true],
			['$profiles.tiki.org:Test_All_Themes', true],
			['$profileobject:user_tracker_acceptedTerms$', true],
			['$profilestikiorg:BugTrackerProfile:bug_tracker', true],
			['$profiles.tiki.org:Client.Management.Profile:client_tracker', false],
			['$profiles.tiki.org:Sample.Data.Profile', false]
		];
	}

	public function profileDependencies()
	{
		$code = "{CODE(caption=>YAML,wrap=1)}\n%s\n{CODE}";

		$base = [
			'dependencies' => [
				'$profiles.tiki.org:Test_All_Themes:Test_All_Themes',
			]
		];

		$data = [];
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			[['domain' => 'profiles.tiki.org', 'profile' => 'Test_All_Themes', 'object' => 'Test_All_Themes']],
		];

		$base['dependencies'] = ['$profiles.tiki.org:Test_All_Themes'];
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			[['domain' => 'profiles.tiki.org', 'profile' => 'Test_All_Themes', 'object' => null]]
		];

		$base['dependencies'] = ['$profiles.tiki.org:Test_All_Themes', '$profiles.tiki.org:Test_All_Modules:Test_All_Modules'];
		$data[] = [
			sprintf($code, \Symfony\Component\Yaml\Yaml::dump($base)),
			[
				['domain' => 'profiles.tiki.org', 'profile' => 'Test_All_Themes', 'object' => null],
				['domain' => 'profiles.tiki.org', 'profile' => 'Test_All_Modules', 'object' => 'Test_All_Modules']
			]
		];

		return $data;
	}
}
