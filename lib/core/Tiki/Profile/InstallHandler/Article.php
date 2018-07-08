<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Article extends Tiki_Profile_InstallHandler
{
	function getData()
	{
		if ($this->data) {
			return $this->data;
		}

		$data = $this->obj->getData();

		$defaults = [
			'author' => 'Anonymous',
			'heading' => '',
			'publication_date' => time(),
			'expiration_date' => time() + 3600 * 24 * 365,
			'type' => 'Article',
			'topic' => 0,
			'topline' => '',
			'subtitle' => '',
			'link_to' => '',
			'language' => 'en',
			'geolocation' => '',
		];

		$data = array_merge($defaults, $data);

		return $this->data = $data;
	}

	function canInstall()
	{
		$data = $this->getData();

		if (! isset($data['title'], $data['topic'], $data['body'])) {
			return false;
		}

		return true;
	}

	function _install()
	{
		global $prefs;
		$artlib = TikiLib::lib('art');
		$data = $this->getData();

		$this->replaceReferences($data);

		$dateConverter = new Tiki_Profile_DateConverter;

		$id = $artlib->replace_article(
			$data['title'],
			$data['author'],
			$data['topic'],
			'n',
			null,
			null,
			null,
			null,
			$data['heading'],
			$data['body'],
			$dateConverter->convert($data['publication_date']),
			$dateConverter->convert($data['expiration_date']),
			'admin',
			0,
			0,
			0,
			$data['type'],
			$data['topline'],
			$data['subtitle'],
			$data['link_to'],
			null,
			$data['language']
		);

		if ($prefs['geo_locate_article'] == 'y' && ! empty($data['geolocation'])) {
			TikiLib::lib('geo')->set_coordinates('article', $id, $data['geolocation']);
		}

		return $id;
	}

	/**
	 * Export articles
	 *
	 * @param Tiki_Profile_Writer $writer
	 * @param int $id
	 * @param bool $withTopic
	 * @param bool $withType
	 * @param bool $all
	 * @return bool
	 */
	public static function export(Tiki_Profile_Writer $writer, $id, $withTopic = false, $withType = false, $all = false)
	{
		$artlib = TikiLib::lib('art');

		if (isset($id) && ! $all) {
			$listArticles = [];
			$listArticles[] = $artlib->get_article($id, false);
		} else {
			$listArticles = $artlib->list_articles();
			$listArticles = $listArticles['data'];
		}

		if (empty($listArticles[0])) {
			return false;
		}

		foreach ($listArticles as $article) {
			$id = $article['articleId'];

			if (! $id) {
				return false;
			}

			$bodypage = "article_{$id}_body";
			$writer->writeExternal($bodypage, $writer->getReference('wiki_content', $article['body']));
			$out = [
				'title' => $article['title'],
				'author' => $article['authorName'],
				'body' => "wikicontent:$bodypage",
				'type' => $writer->getReference('article_type', $article['type']),
				'publication_date' => $article['publishDate'],
				'expiration_date' => $article['expireDate'],
				'topline' => $article['topline'],
				'subtitle' => $article['subtitle'],
				'link_to' => $article['linkto'],
				'language' => $article['lang'],
			];

			if ($article['topicId']) {
				if ($withTopic) {
					Tiki_Profile_InstallHandler_ArticleTopic::export($writer, $article['topicId']);
				}

				$out['topic'] = $writer->getReference('article_topic', $article['topicId']);
			}

			if ($article['heading']) {
				$headerpage = "article_{$id}_heading";
				$writer->writeExternal($headerpage, $writer->getReference('wiki_content', $article['heading']));
				$out['heading'] = "wikicontent:$headerpage";
			}

			$out = array_filter($out);
			$writer->addObject('article', $id, $out);

			if ($withType) {
				Tiki_Profile_InstallHandler_ArticleType::export($writer, $article['type']);
			}
		}

		return true;
	}

	/**
	 * Remove article
	 *
	 * @param string $article
	 * @return bool
	 */
	function remove($article)
	{
		if (! empty($article)) {
			$artlib = TikiLib::lib('art');
			$article = $artlib->list_articles(0, -1, 'articleId_desc', $article);
			$count = isset($article['cant']) ? $article['cant'] : 0;
			if ($count == 1
				&& ! empty($article['data'][0]['articleId'])
				&& $artlib->remove_article($article['data'][0]['articleId'])
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get current article data
	 *
	 * @param array $article
	 * @return mixed
	 */
	public function getCurrentData($article)
	{
		$articleName = ! empty($article['title']) ? $article['title'] : '';
		if (! empty($articleName)) {
			$artlib = TikiLib::lib('art');
			$article = $artlib->list_articles(0, 1, 'articleId_desc', $articleName);
			$articleId = ! empty($article['data'][0]['articleId']) ? $article['data'][0]['articleId'] : 0;
			if (! empty($articleId)) {
				$articleData = $article['data'][0];
				return $articleData;
			}
		}
		return false;
	}
}
