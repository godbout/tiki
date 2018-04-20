<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki;

use Faker\Provider\Base as FakerProviderBase;
use TikiLib;
use TikiDb;
use Tracker_Definition;
use Services_Tracker_Utilities;
use Services_Tracker_Controller;
use JitFilter;

/**
 * Class that handles all tiki faker data operations
 *
 * @uses Faker\Provider\Base
 * @access public
 */
class Faker extends FakerProviderBase
{
	/**
	 * @var bool if Faker should reuse files in the file gallery or create new files
	 */
	protected $tikiFilesReuseFiles = true;

	/**
	 * Random categories from tiki
	 *
	 * @return int
	 */
	public function tikiCategories()
	{
		$categoriesLib = TikiLib::lib('categ');
		$categories = $categoriesLib->getCategories();
		$category = $categories[array_rand($categories)];
		return isset($category['categId']) ? $category['categId'] : false;
	}

	/**
	 * Random checkbox fields values
	 *
	 * @return string
	 */
	public function tikiCheckbox()
	{
		$checkboxValues = ['y', 'n'];
		return $checkboxValues[array_rand($checkboxValues)];
	}

	/**
	 * Random dropdown options
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiDropdown($field)
	{
		if (! empty($field) && ! empty($field['options_array'])) {
			$options = $field['options_array'];
			$optionRand = $options[array_rand($options)];
			if (strpos($optionRand, '=') !== false) {
				$optionRand = explode('=', $optionRand);
				$optionRand = $optionRand[1];
			}
			return $optionRand;
		}
		return '';
	}

	/**
	 * Random radio options
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiRadio($field)
	{
		if (! empty($field) && ! empty($field['options_array'])) {
			$options = $field['options_array'];
			$optionRand = $options[array_rand($options)];
			if (strpos($optionRand, '=') !== false) {
				$optionRand = explode('=', $optionRand);
				$optionRand = $optionRand[0];
			}
			return $optionRand;
		}
		return '';
	}

	/**
	 * Random multiselect options
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiMultiselect($field)
	{
		if (! empty($field) && ! empty($field['options_array'])) {
			$options = $field['options_array'];
			$optionRand = $options[array_rand($options)];
			if (strpos($optionRand, '=') !== false) {
				$optionRand = explode('=', $optionRand);
				$optionRand = $optionRand[0];
			}
			return $optionRand;
		}
		return '';
	}

	/**
	 * Random tiki files
	 *
	 * @param $field
	 * @param $icon
	 * @return string
	 */
	public function tikiFiles($field, $icon = false)
	{
		$filegallib = TikiLib::lib('filegal');
		$galleryId = '';
		$files = [];
		$iconFile = ! empty($icon) ? 'tiki-download_file.php?fileId=' : '';

		if (! empty($field['options_map']['galleryId'])) {
			$galleryId = $field['options_map']['galleryId'];
			if ($this->tikiFilesReuseFiles) {
				$files = $filegallib->get_files_info($galleryId);
			} else {
				$files = [];
			}
		}
		if (! empty($files)) {
			$filesRand = $files[array_rand($files)];
			return $iconFile . $filesRand['fileId'];
		}
		if (empty($galleryId)) {
			global $user;
			$galInfo = [
				'galleryId' => '',
				'parentId' => 1,
				'name' => 'FakerGal' . time(),
				'description' => '',
				'user' => $user,
				'public' => 'y',
				'visible' => 'y',
			];
			$galleryId = $filegallib->replace_file_gallery($galInfo);
		}

		$path = 'img/profiles/';
		$files = array_diff(scandir($path), ['.', '..', 'index.php']);
		$imagePath = $path . $files[array_rand($files)];
		$imageInfo = getimagesize($imagePath);
		$imageSize = filesize($imagePath);
		$gal_info = $filegallib->get_file_gallery($galleryId);
		$uploadedFile = $filegallib->upload_single_file(
			$gal_info,
			time(),
			$imageSize,
			$imageInfo['mime'],
			file_get_contents($imagePath),
			null,
			600,
			400
		);

		return $iconFile . $uploadedFile;
	}

	/**
	 * Random itemsList options
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiItemLink($field)
	{
		if (! empty($field) && ! empty($field['options_map'])) {
			$map = $field['options_map'];
			if (! empty($map)) {
				$tikilib = TikiLib::lib('tiki');
				$table = $tikilib->table('tiki_tracker_item_fields');
				$itemList = $table->fetchAll(['itemId'], ['fieldId' => $map['fieldId']]);
				if (! empty($itemList)) {
					$item = $itemList[array_rand($itemList)];
					return $item['itemId'];
				}
			}
		}
		return '';
	}

	/**
	 * Random coordinates
	 *
	 * @return string
	 */
	public function tikiLocation()
	{
		$radius = 100;
		$angle = deg2rad(mt_rand(0, 359));
		$pointRadius = sqrt(mt_rand(0, $radius * $radius));
		$point = (sin($angle) * $pointRadius) . ',' . (cos($angle) * $pointRadius) . ',' . rand(0, 19);
		return $point;
	}

	/**
	 * Random tiki page name
	 *
	 * @return string
	 */
	public function tikiPageSelector()
	{
		$tikilib = TikiLib::lib('tiki');
		$listPages = $tikilib->list_pageNames();

		if (! empty($listPages['data'])) {
			$listPages = $listPages['data'];
			$page = $listPages[array_rand($listPages)];
			return $page['pageName'];
		}
		return '';
	}

	/**
	 * Random tiki user
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiUserSelector($field)
	{
		$userlib = TikiLib::lib('user');
		$groupFilter = '';
		if (! empty($field) && ! empty($field['options_map']['groupIds'])) {
			$groupIds = $field['options_map']['groupIds'];
			if (is_array($groupIds)) {
				$table = TikiDb::get()->table('users_groups');
				$groupFilter = $table->fetchColumn(
					'groupName',
					[
						'id' => $table->in($groupIds),
					]
				);
			}
		}

		$listUsers = $userlib->get_users_light(0, -1, 'login_asc', '', $groupFilter);
		if (! empty($listUsers)) {
			$user = $listUsers[array_rand($listUsers)];
			return $user;
		}
		return '';
	}

	/**
	 * Return static text
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiStaticText($field)
	{
		if (! empty($field) && ! empty($field['description'])) {
			return $field['description'];
		}
		return '';
	}

	/**
	 * Insert unique identifier number
	 *
	 * @param $field
	 * @return null
	 */
	public function tikiUniqueIdentifier($field)
	{
		$trackerId = ! empty($field['trackerId']) ? $field['trackerId'] : 0;
		$trackerName = ! empty($field['permName']) ? $field['permName'] : '';
		$definition = Tracker_Definition::get($trackerId);
		$trackerUtilities = new Services_Tracker_Utilities();
		$trackerUtilities->insertItem(
			$definition,
			[
				'status' => null,
				'fields' => [$trackerName => ''],
			]
		);

		return null;
	}

	/**
	 * Random tiki group
	 *
	 * @return string
	 */
	public function tikiGroupSelector()
	{
		$userlib = TikiLib::lib('user');
		$listGroup = $userlib->list_all_groups();
		$group = $listGroup[array_rand($listGroup)];
		return $group;
	}

	/**
	 * Random tiki articles
	 */
	public function tikiArticles()
	{
		$artlib = TikiLib::lib('art');
		$listArticles = $artlib->list_articles();
		if (! empty($listArticles['data'])) {
			$article = $listArticles['data'][array_rand($listArticles['data'])];
			return $article['articleId'];
		}
		return '';
	}

	/**
	 * Random tiki Ratings
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiRating($field)
	{
		if (! empty($field['fieldId']) && ! empty($field['options_map']['option']) && ! empty($field['trackerId'])) {
			$trackerId = $field['trackerId'];
			$trackerName = ! empty($field['permName']) ? $field['permName'] : '';
			$definition = Tracker_Definition::get($trackerId);
			$trackerUtilities = new Services_Tracker_Utilities();
			$itemId = $trackerUtilities->insertItem(
				$definition,
				[
					'status' => null,
					'fields' => [$trackerName => ''],
				]
			);

			if (! empty($itemId)) {
				$ratings = $field['options_map']['option'];
				$input = [
					'action' => 'vote',
					'controller' => 'tracker',
					'f' => $field['fieldId'],
					'i' => $itemId,
					'v' => $ratings[array_rand($ratings)],
				];
				$tracker = new Services_Tracker_Controller();
				$tracker->action_vote(new JitFilter($input));
				return null;
			}
		}
		return '';
	}

	/**
	 * Relate tracker items
	 *
	 * @param $field
	 * @return string
	 */
	public function tikiRelations($field)
	{
		if (! empty($field['fieldId'])) {
			$tikilib = TikiLib::lib('tiki');
			$table = $tikilib->table('tiki_tracker_item_fields');
			$itemList = $table->fetchAll(
				['itemId'],
				['value' => $table->not(''), 'fieldId' => $table->not($field['fieldId'])]
			);
			if (! empty($itemList)) {
				$item = $itemList[array_rand($itemList)];
				return 'trackeritem:' . $item['itemId'];
			}
		}
		return '';
	}

	/**
	 * Set if Faker should reuse files in the file gallery or create new files
	 *
	 * @param $reuse
	 */
	public function setTikiFilesReuseFiles($reuse)
	{
		$this->tikiFilesReuseFiles = (bool)$reuse;
	}
}
