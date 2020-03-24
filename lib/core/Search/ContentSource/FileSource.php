<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_ContentSource_FileSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider
{
	private $db;

	function __construct()
	{
		$this->db = TikiDb::get();
	}

	function getReferenceMap()
	{
		return [
			'gallery_id' => 'file_gallery',
		];
	}

	function getDocuments()
	{
		$files = $this->db->table('tiki_files');
		return $files->fetchColumn(
			'fileId',
			[
				'archiveId' => 0,
			],
			-1,
			-1,
			'ASC'
		);
	}

	function getDocument($objectId, Search_Type_Factory_Interface $typeFactory)
	{
		global $prefs;

		$filegallib = Tikilib::lib('filegal');

		$file = $filegallib->get_file_info($objectId, true, false);

		if (! $file) {
			return false;
		}

		if (! empty($file['name'])) {
			// Many files when uploaded have underscore in the file name and makes search difficult
			$file['name'] = str_replace('_', ' ', $file['name']);
		}

		$data = [
			'title' => $typeFactory->sortable(empty($file['name']) ? $file['filename'] : $file['name']),
			'title_unstemmed' => $typeFactory->simpletext(empty($file['name']) ? $file['filename'] : $file['name']),
			'language' => $typeFactory->identifier('unknown'),
			'creation_date' => $typeFactory->timestamp($file['created']),
			'modification_date' => $typeFactory->timestamp($file['lastModif']),
			'date' => $typeFactory->timestamp($file['created']),
			'contributors' => $typeFactory->multivalue(array_unique([$file['author'], $file['user'], $file['lastModifUser']])),
			'description' => $typeFactory->plaintext($file['description']),
			'filename' => $typeFactory->identifier($file['filename']),
			'filetype' => $typeFactory->sortable(preg_replace('/^([\w-]+)\/([\w-]+).*$/', '$1/$2', $file['filetype'])),
			'filesize' => $typeFactory->plaintext($file['filesize']),
			'hits' => $typeFactory->numeric($file['hits']),

			'gallery_id' => $typeFactory->identifier($file['galleryId']),
			'file_comment' => $typeFactory->plaintext($file['comment']),
			'file_content' => $typeFactory->plaintext($file['search_data']),
			'ocr_content' => $typeFactory->plaintext($file['ocr_data']),

			'parent_object_type' => $typeFactory->identifier('file gallery'),
			'parent_object_id' => $typeFactory->identifier($file['galleryId']),
			'parent_view_permission' => $typeFactory->identifier('tiki_p_download_files'),
		];

		if ($prefs['fgal_enable_email_indexing'] === 'y' && $file['filetype'] == 'message/rfc822') {
			$file_object = Tiki\FileGallery\File::id($file['fileId']);
			$parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
			if ($parsed_fields) {
				$data += [
					'email_subject' => $typeFactory->plaintext($parsed_fields['subject']),
					'email_from' => $typeFactory->plaintext($parsed_fields['from']),
					'email_sender' => $typeFactory->plaintext($parsed_fields['sender']),
					'email_recipient' => $typeFactory->plaintext($parsed_fields['recipient']),
					'email_date' => $typeFactory->timestamp($parsed_fields['date']),
					'email_content_type' => $typeFactory->plaintext($parsed_fields['content_type']),
					'email_body' => $typeFactory->plainmediumtext($parsed_fields['body']),
					'email_plaintext' => $typeFactory->plainmediumtext($parsed_fields['plaintext']),
					'email_html' => $typeFactory->plainmediumtext($parsed_fields['html']),
				];
			}
		}

		return $data;
	}

	function getProvidedFields()
	{
		return [
			'title',
			'title_unstemmed',
			'language',
			'creation_date',
			'modification_date',
			'date',
			'contributors',
			'description',
			'filename',
			'filetype',
			'filesize',
			'hits',

			'gallery_id',
			'file_comment',
			'file_content',
			'ocr_content',

			'parent_view_permission',
			'parent_object_id',
			'parent_object_type',

			'email_subject',
			'email_from',
			'email_sender',
			'email_recipient',
			'email_date',
			'email_content_type',
			'email_body',
			'email_plaintext',
			'email_html',
		];
	}

	function getGlobalFields()
	{
		return [
			'title' => true,
			'description' => true,
			'date' => true,
			'filename' => true,

			'file_comment' => false,
			'file_content' => false,
			'ocr_content' => false,
		];
	}
}
