#!/usr/bin/php
<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if ($argc < 3) {
	$helpMsg = "\nUsage: php doc/devtools/convert_tracker_attachments.php trackerId fieldId [fileGalId]\n";
	$helpMsg .= "\nExamples: \n\t\tphp doc/devtools/convert_tracker_attachments.php 1 2";
	$helpMsg .= "\n\t\tphp doc/devtools/convert_tracker_attachments.php 1 2 2\n\n";
	exit($helpMsg);
}

require_once('tiki-setup.php');

$context = new Perms_Context("admin");

$trackerId = $argv[1];
$fieldId = $argv[2];
$galleryId = $argv[3];

if (!isset($trackerId)) {
	echo "Error: Missing trackerId\n";
	exit(1);
}

if (!isset($fieldId)) {
	echo "Error: Missing fieldId\n";
	exit(1);
}

if (!isset($galleryId)) {
	$galleryId = 1;
}

// Check if tracker and fieldId are valid
$trklib = TikiLib::lib('trk');
$fgFields = $trklib->get_field_id_from_type($trackerId, 'FG', null, false);

if (empty($fgFields) || !in_array($fieldId, $fgFields)) {
	echo "Error: Invalid fieldId {$fieldId} for trackerId {$trackerId}\n";
	exit(1);
}

// Check if its a valid file gallery
try {
	$fileUtilities = new Services_File_Utilities;
	$galInfo = $fileUtilities->checkTargetGallery($galleryId);
} catch (Services_Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}

$items = $trklib->list_items($trackerId, 0, -1, 'lastModif_asc', '', '', '', '', '', '', '', null, false, true);

$trackerUtilities = new Services_Tracker_Utilities;

foreach ($items['data'] as $item) {
	$itemId = $item['itemId'];

	echo "Updating tracker item {$itemId}:\n";

	$definition = Tracker_Definition::get($trackerId);

	$itemInfo = $trklib->get_tracker_item($itemId);
	if (!$itemInfo || $itemInfo['trackerId'] != $trackerId) {
		continue;
	}

	$atts = $trklib->list_item_attachments($itemId, 0, -1, 'comment_asc', '');
	$fileIdList = [];

	$numAttachments = sizeof($atts['data']);
	echo "- Found {$numAttachments} attachment(s)\n";

	if ($numAttachments === 0) {
		echo "Tracker Item {$itemId} skipped\n";
		continue;
	}

	foreach ($atts['data'] as $attachment) {
		$attachment = $trklib->get_item_attachment($attachment['attId']);

		if (!$attachment) {
			echo "- Warning: Unable to get item attachment with attId {$attachment['attId']}\n";
			continue;
		}

		$name = $attachment['filename'];
		$size = $attachment['filesize'];
		$type = $attachment['filetype'];
		$data = $attachment['data'];
		$asuser = null;
		$imageX = 0;
		$imageY = 0;

		$fileId = $fileUtilities->uploadFile($galInfo, $name, $size, $type, $data, $asuser, $imageX, $imageY);

		if ($fileId !== false) {
			array_push($fileIdList, $fileId);
			echo "- Attachment {$attachment['filename']} uploaded to file gallery\n";
		} else {
			echo "- Failed to upload attachment {$attachment['filename']} to file gallery\n";
		}
	}

	if (empty($fileIdList)) {
		echo "No files were uploaded to the file gallery\n";
		echo "Tracker Item {$itemId} skipped\n";
		continue;
	}

	$itemObject = Tracker_Item::fromInfo($itemInfo);

	$input = new JitFilter([
		'trackerId' => $trackerId,
		'itemId' => $itemId
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
		echo "Tracker item {$itemId} updated successfully\n";
	} else {
		echo "Tracker item {$itemId} update failed\n";
	}
}
exit(0);

