<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\File;

use Tiki\FileGallery\File as TikiFile;
use Tiki\FileGallery\File;
use Tiki\Package\VendorHelper;

class DiagramHelper
{
	const DRAW_IO_IMAGE_EXPORT_SERVICE_URL = 'https://exp.draw.io/ImageExport4/export';
	const DRAW_IO_IMAGE_FORMAT = 'png';
	const FETCH_IMAGE_CONTENTS_TIMEOUT = 5;

	/**
	 * Get diagram as image given a file ID or diagram contents.
	 * If the requested file or contents are cached, they will be immediately returned, otherwise they will be fetched if Tiki is configured for it.
	 * @param $diagramContent
	 * @return bool|false|string
	 */
	public static function getDiagramAsImage($diagramContent)
	{
		global $prefs, $cachelib;
		$fileIdentifier = md5($diagramContent);
		$content = $cachelib->getCached($fileIdentifier, 'diagram');

		if (! $content && $prefs['fgal_use_drawio_services_to_export_images'] === 'y') {
			$content = self::getDiagramAsImageFromExternalService('<mxfile>' . $diagramContent . '</mxfile>');

			if (! empty($content)) {
				$cachelib->cacheItem($fileIdentifier, $content, 'diagram');
			}
		}

		return $content;
	}

	/**
	 * Get an array of diagrams based on the XML content or file_id which will retrieve the File XML contents
	 * @param $identifier
	 * @param $page string Return specific page from the diagram
	 * @return array|bool
	 */
	public static function getDiagramsFromIdentifier($identifier, $page = '')
	{
		$rawXmlContent = $identifier;

		if (is_int($identifier)) {
			$file = File::id($identifier);

			if (empty($file)) {
				return false;
			}

			$rawXmlContent = $file->data();
		}

		$diagramRoot = simplexml_load_string($rawXmlContent);
		$diagrams = [];

		foreach ($diagramRoot->diagram as $diagram) {
			$diagramName = (string) $diagram->attributes()->name;

			if (! empty($page) && $page != $diagramName) {
				continue;
			}

			$diagrams[] = $diagram->asXML();
		}

		return $diagrams;
	}

	/**
	 * Check if file is a diagram
	 *
	 * @param $fileId
	 * @return bool
	 */
	public static function isDiagram($fileId)
	{
		$file = TikiFile::id($fileId);
		$type = $file->getParam('filetype');
		$data = trim($file->getContents());

		if (in_array($type, ['text/plain', 'text/xml']) && (strpos($data, '<mx') === 0)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if needed core files exist in order to enable Diagrams
	 * @return bool
	 */
	public static function isPackageInstalled()
	{
		return VendorHelper::getAvailableVendorPath('diagram', '/tikiwiki/diagram/js/app.min.js') !== false;
	}

	/**
	 * Parse diagram raw data
	 * @param $data
	 * @return string
	 */
	public static function parseData($data)
	{
		return preg_replace('/\s+/', ' ', $data);
	}

	/**
	 * Get diagram as PNG from DRAWIO external service
	 * @param $rawXml
	 * @return bool|string
	 */
	private static function getDiagramAsImageFromExternalService($rawXml)
	{
		$jsonPayload = json_encode([
			'format'    => self::DRAW_IO_IMAGE_FORMAT,
			'embedXml'  => '0',
			'base64'    => '1',
			'xml'       => $rawXml,
		]);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_URL, self::DRAW_IO_IMAGE_EXPORT_SERVICE_URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::FETCH_IMAGE_CONTENTS_TIMEOUT);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPayload);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonPayload)
		]);

		return curl_exec($curl);
	}
}
