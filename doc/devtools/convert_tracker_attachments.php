#!/usr/bin/php
<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if ($argc < 3) {
	$helpMsg = "\nUsage: php doc/devtools/convert_tracker_attachments.php trackerId fieldId [fileGalId] ['remove']\n";
	$helpMsg .= "\nExamples: \n\t\tphp doc/devtools/convert_tracker_attachments.php 1 2";
	$helpMsg .= "\n\t\tphp doc/devtools/convert_tracker_attachments.php 1 2 2\n\n";
	exit($helpMsg);
}

require_once('tiki-setup.php');

$context = new Perms_Context("admin");

$trackerId = $argv[1];
$fieldId = $argv[2];

if (! isset($trackerId)) {
	echo "Error: Missing trackerId\n";
	exit(1);
}

if (! isset($fieldId)) {
	echo "Error: Missing fieldId\n";
	exit(1);
}

if (isset($argv[3])) {
	$galleryId = $argv[3];
} else {
	$galleryId = 0;
}

if (isset($argv[4]) && $argv[4] === 'remove') {
	$remove = true;
} else {
	$remove = false;
}

/**
 * @param $trackerId
 * @param $fieldId
 * @param int $galleryId
 * @throws Services_Exception
 * @throws Exception
 */
function convertAttachments($trackerId, $fieldId, $galleryId = 0, $remove = false)
{
	global $prefs;

	$trklib = TikiLib::lib('trk');
	$trackerUtilities = new Services_Tracker_Utilities;
	$definition = Tracker_Definition::get($trackerId);

	// Check if tracker and fieldId are valid
	$fgField = $trackerUtilities->getFieldsFromIds($definition, [$fieldId]);

	if (! $fgField || $fgField[0]['type'] !== 'FG') {
		echo "Error: Invalid fieldId {$fieldId} for trackerId {$trackerId}\n";
		exit(1);
	}
	$fgField = $fgField[0];

	if (! $galleryId && isset($fgField['options_map']['galleryId'])) {
		$galleryId = $fgField['options_map']['galleryId'];
	}

	if (! $galleryId) {
		$galleryId = $prefs['fgal_root_id'];
	}

// Check if its a valid file gallery
	try {
		$fileUtilities = new Services_File_Utilities;
		$galInfo = $fileUtilities->checkTargetGallery($galleryId);
	} catch (Services_Exception $e) {
		echo "Error: {$e->getMessage()}\n";
		exit(1);
	}

	$items = $trackerUtilities->getItems(['trackerId' => $trackerId]);
	$failedAttIds = [];
	$itemsFailed = 0;
	$itemsProcessed = 0;
	$attachmentsProcessed = 0;


	foreach ($items as $item) {
		$itemId = $item['itemId'];


		$itemObject = Tracker_Item::fromId($itemId);

		if (! $itemObject || $itemObject->getDefinition() !== $definition) {
			continue;
		}

		$atts = $trklib->list_item_attachments($itemId, 0, -1, 'comment_asc', '');
		$fileIdList = [];

		$numAttachments = sizeof($atts['data']);

		if ($numAttachments === 0) {
			echo "Tracker Item {$itemId} skipped (no attachments)\n";
			continue;
		} else {
			echo "Updating tracker item {$itemId}:\n";
			$ess = $numAttachments > 1 ? 's' : '';
			echo "- Found {$numAttachments} attachment$ess\n";
		}

		foreach ($atts['data'] as $attachment) {
			$attachment = $trklib->get_item_attachment($attachment['attId']);

			if (! $attachment) {
				echo "- Warning: Unable to get item attachment with attId {$attachment['attId']}\n";
				continue;
			}

			$name = $attachment['filename'];
			$size = $attachment['filesize'];
			$type = $attachment['filetype'];
			$description = $attachment['longdesc'];
			if ($attachment['comment']) {
				$description .= "\nComment\n" . $attachment['comment'];
			}
			$data = $attachment['data'];

			try {
				$fileId = $fileUtilities->uploadFile($galInfo, $name, $size, $type, $data, null, null, null, $description);
			} catch (Exception $e) {
				$fileId = false;
				echo "Error: File  {$attachment['filename']} on item  {$itemId} could not be saved\n";
				echo "{$e->getMessage()}\n";
			}

			if ($fileId !== false) {
				array_push($fileIdList, $fileId);
				echo "- Attachment {$attachment['filename']} uploaded to file gallery\n";
			} else {
				echo "- Failed to upload attachment {$attachment['filename']} to file gallery\n";
				$failedAttIds[] = $attachment['attId'];
			}
		}

		if (empty($fileIdList)) {
			echo "No files were uploaded to the file gallery\n";
			echo "Tracker Item {$itemId} skipped\n";
			continue;
		}

		$input = new JitFilter([
			'trackerId' => $trackerId,
			'itemId' => $itemId,
		]);

		$processedFields = $itemObject->prepareInput($input);

		$fields = [];

		foreach ($processedFields as $key => $field) {
			$permName = $field['permName'];
			$fields[$permName] = isset($field['value']) ? $field['value'] : '';

			if ($field['fieldId'] == $fieldId && $field['type'] == 'FG') {
				$fields[$permName] = empty($fields[$permName]) ? implode(',', $fileIdList) : $fields[$permName] . ',' . implode(',', $fileIdList);
			}
		}

		$result = $trackerUtilities->updateItem(
			$definition,
			[
				'itemId' => $itemId,
				'status' => '',
				'fields' => $fields,
			]
		);

		if ($result !== false) {
			$itemsProcessed++;
			if ($remove) {
				foreach ($atts['data'] as $attachment) {
					if (! in_array($attachment['attId'], $failedAttIds)) {
						$trklib->remove_item_attachment($attachment['attId'], $itemId);
						$attachmentsProcessed++;
					} else {
						echo "(Attachment {$attachment['attId']} {$attachment['filename']} not removed)\n";
						$numAttachments--;
					}
				}
				echo "Tracker item {$itemId} updated successfully and $numAttachments attachment$ess removed\n";
			} else {
				$attachmentsProcessed += $numAttachments;
				echo "Tracker item {$itemId} updated successfully\n";
			}
		} else {
			echo "Tracker item {$itemId} update failed\n";
			$itemsFailed++;
		}
	}

	$failCount = count($failedAttIds);
	$op = $remove ? "moved" : "copied";

	echo "\nConvert completed:\n    {$itemsProcessed} processed ({$itemsFailed} failed) and\n    {$attachmentsProcessed} attachments {$op} ({$failCount} failed)\n\n";
}

convertAttachments($trackerId, $fieldId, $galleryId, $remove);

exit(0);
