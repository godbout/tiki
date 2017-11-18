<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_Articles extends Tracker_Field_Abstract
{
	private $articleSource;

	public static function getTypes()
	{
		$db = TikiDb::get();
		$topics = $db->table('tiki_topics')->fetchMap('topicId', 'name', [], -1, -1, 'name_asc');
		$types = $db->table('tiki_article_types')->fetchColumn('type', []);
		$types = array_combine($types, $types);

		$options = [
			'articles' => [
				'name' => tr('Articles'),
				'description' => tr('Attach articles to the tracker item.'),
				'prefs' => ['trackerfield_articles', 'feature_articles'],
				'tags' => ['advanced'],
				'help' => 'Articles Tracker Field',
				'default' => 'n',
				'params' => [
					'topicId' => [
						'name' => tr('Topic'),
						'description' => tr('Default article topic'),
						'filter' => 'int',
						'profile_reference' => 'article_topic',
						'options' => $topics,
					],
					'type' => [
						'name' => tr('Article Type'),
						'description' => tr('Default article type'),
						'filter' => 'text',
						'profile_reference' => 'article_type',
						'options' => $types,
					],
				],
			],
		];
		return $options;
	}

	function getFieldData(array $requestData = [])
	{
		global $prefs;
		$ins_id = $this->getInsertId();
		if (isset($requestData[$ins_id])) {
			if (is_string($requestData[$ins_id])) {
				$articleIds = explode(',', $requestData[$ins_id]);
			} else {
				$articleIds = $requestData[$ins_id];
			}

			$articleIds = array_filter(array_map('intval', $articleIds));
			$value = implode(',', $articleIds);
		} else {
			$value = $this->getValue();

			// Obtain the information from the database for display
			$articleIds = array_filter(explode(',', $value));
		}

		return [
			'value' => $value,
			'articleIds' => $articleIds,
		];
	}

	function renderInput($context = [])
	{
		global $prefs;
		// if the article is being indexed with the trackeritem as part of the autogenerated rss feature, the field should become read-only
		if ($prefs['tracker_article_indexing'] == 'y' && $prefs['tracker_article_trackerId'] == $this->getConfiguration('trackerId')) {
			$readonly = true;
		} else {
			$readonly = false;
		}
		$articleIds = $this->getConfiguration('articleIds');

		return $this->renderTemplate('trackerinput/articles.tpl', $context, [
			'filter' => ['type' => 'article'],
			'labels' => array_combine(
				$articleIds,
				array_map(function ($id) {
					return TikiLib::lib('object')->get_title('article', $id);
				}, $articleIds)
			),
			'readonly' => $readonly,
		]);
	}

	function renderOutput($context = [])
	{
		return $this->renderTemplate('trackeroutput/articles.tpl', $context, [
		]);
	}

	function handleSave($value, $oldValue)
	{
		$new = array_diff(explode(',', $value), explode(',', $oldValue));
		$remove = array_diff(explode(',', $oldValue), explode(',', $value));

		$itemId = $this->getItemId();

		$relationlib = TikiLib::lib('relation');
		$relations = $relationlib->get_relations_from('trackeritem', $itemId, 'tiki.article.attach');
		foreach ($relations as $existing) {
			if ($existing['type'] != 'article') {
				continue;
			}

			if (in_array($existing['itemId'], $remove)) {
				$relationlib->remove_relation($existing['relationId']);
			}
		}

		foreach ($new as $articleId) {
			$relationlib->add_relation('tiki.article.attach', 'trackeritem', $itemId, 'article', $articleId);
		}

		return [
			'value' => $value,
		];
	}

	/**
	 * This returns the document part for the article field in the trackeritem.
	 * Note that this indexing only works as part of the rss aggregator feature.
	 *
	 * @param Search_Type_Factory_Interface $typeFactory
	 * @return array
	 */
	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		global $prefs;
		//if article indexing is set to off for tracker, just return an empty array here.
		if ($prefs['tracker_article_indexing'] != 'y' || $prefs['tracker_article_trackerId'] != $this->getItemData()['trackerId']) {
			return [];
		}
		$value = $this->getValue();
		$baseKey = $this->getBaseKey();

		if (! $value) {
			return [];
		}

		if (empty($this->articleSource)) {
			$this->articleSource = new Search_ContentSource_ArticleSource();
		}

		$articleInfo = $this->articleSource->getDocument($value, $typeFactory);
		$data = [];
		//append the article field to the base key. ie. tracker_field_articleField_title, tracker_field_articleField_author, etc
		foreach ($articleInfo as $key => $v) {
			$data[$baseKey . "_" . $key] = $v;
		}
		$data[$baseKey] = $typeFactory->identifier($value);
		return $data;
	}

	function getProvidedFields()
	{
		$baseKey = $this->getBaseKey();

		if (empty($this->articleSource)) {
			$this->articleSource = new Search_ContentSource_ArticleSource();
		}

		$articleInfo = $this->articleSource->getProvidedFields();
		$data = [];
		foreach ($articleInfo as $k => $v) {
			$data[$k] = $baseKey . '_' . $v;
		}
		$data[] = $baseKey;

		return $data;
	}

	function getGlobalFields()
	{
		$baseKey = $this->getBaseKey();
		return [$baseKey => true];
	}
}
