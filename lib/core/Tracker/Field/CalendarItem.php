<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_CalendarItem extends Tracker_Field_JsCalendar
{
	/** @var AttributeLib $attributeLib */
	private $attributeLib;

	/** @var CalendarLib $calendarLib */
	private $calendarLib;

	public static function getTypes()
	{
		$def = [
			'CAL' => [
				'name' => tr('Date and Time (Calendar Item)'),
				'description' => tr('Associate calendar items with tracker items.'),
				'prefs' => ['trackerfield_calendaritem'],
				'tags' => ['advanced', 'experimental'],
				'warning' => tra('Experimental: (work in progress, use with care)'),
				'default' => 'n',
				'supported_changes' => ['f', 'j', 'CAL'],
				'params' => [
					'calendarId' => [
						'name' => tr('Calendar Id'),
						'description' => tr('Calendar to use for associated events'),
						'filter' => 'int',
						'profile_reference' => 'calendar',

					],
				],
			],
		];

		$parentDef = parent::getTypes();

		$def['CAL']['params'] = array_merge($def['CAL']['params'], $parentDef['j']['params']);

		return $def;
	}

	/**
	 * Tracker_Field_CalendarItem constructor.
	 * @param array $fieldInfo
	 * @param array $itemData
	 * @param Tracker_Definition $trackerDefinition
	 */
	function __construct($fieldInfo, $itemData, $trackerDefinition)
	{

		$this->attributeLib = TikiLib::lib('attribute');
		$this->calendarLib = TikiLib::lib('calendar');

		if ($fieldInfo['options_map']['calendarId']) {
			TikiLib::lib('relation')->add_relation(
				'tiki.calendar.attach',
				'tracker',
				$trackerDefinition->getConfiguration('trackerId'),
				'calendar',
				$fieldInfo['options_map']['calendarId'],
				true
			);
		}

		parent::__construct($fieldInfo, $itemData, $trackerDefinition);
	}

	function handleSave($value, $oldValue)
	{
		$calendarId = $this->getOption('calendarId');

		if ($calendarId && $value) {
			global $user, $language;

			/** @var TrackerLib $trklib */
			$trklib = TikiLib::lib('trk');

			$itemId = $this->getItemId();


			if ($itemId) {
				$trackerId = $this->getConfiguration('trackerId');
				$name = $trklib->get_isMain_value($trackerId, $itemId);
				$calitemId = $this->getCalendarItemId();// check it really exists

				if (! $this->calendarLib->get_calendarid($calitemId)) {
					$new = true;
					$calitemId = 0;
				}
				// save the event whether new or not as start time or the title/name might have changed
				$calitemId = $this->calendarLib->set_item(
					$user, $calitemId, [
					'calendarId' => $calendarId,
					'start'      => $value,
					'end'        => $new ? ($value + 3600) : null,
					//					'locationId',
					//					'categoryId',
					//					'nlId',
					//					'priority',
					//					'status',
					//					'url',
					'lang'       => $language,
					'name'       => $name,
					//					'description',
					//					'user',
					//					'created',
					//					'lastmodif',
					//					'allday',
					//					'recurrenceId',
					//					'changed'
				]
				);
				if ($new) {    // added a new one?
					$this->attributeLib->set_attribute(
						'trackeritem', $itemId, 'tiki.calendar.item',
						$calitemId
					);
				}
			}
			//$itemInfo = $calendarlib->get_item($calitemId);
		} else if (! $value && $oldValue && $itemId = $this->getItemId()) {
			// delete an item?
			$calitemId = $this->attributeLib->get_attribute('trackeritem', $itemId, 'tiki.calendar.item');
			if ($calitemId) {
				$this->calendarLib->drop_item($GLOBALS['user'], $calitemId);
				// also remove attribute
				$this->attributeLib->set_attribute('trackeritem', $itemId, 'tiki.calendar.item', '');
			}
		}

		return [
			'value' => $value,
		];
	}

	function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
	{
		$baseKey = $this->getBaseKey();

		$calitemId = $this->getCalendarItemId();

		return [
			$baseKey => $typeFactory->timestamp($this->getValue(), $this->getOption('datetime') == 'd'),
			"{$baseKey}_calitemid" => $typeFactory->numeric($calitemId),
		];
	}

	function getFieldData(array $requestData = [])
	{
		$data = parent::getFieldData($requestData);

		if (! empty($data['value']) && ! $this->getCalendarItemId()) {
			// corresponding calendar irtem missing, so create a new one
			$this->handleSave($data['value'], '');
		}

		return $data;
	}

	/**
	 * @param array $context
	 * @return string
	 * @throws Exception
	 */
	function renderInput($context = [])
	{
		global $tikiroot;

		/** @var Smarty_Tiki $smarty */
		$smarty = TikiLib::lib('smarty');

		$smarty->assign('datePickerHtml', parent::renderInput($context));

		$event = $this->calendarLib->get_item($this->getCalendarItemId());
		$perms = Perms::get([ 'type' => 'calendar', 'object' => $event['calendarId']]);

		if ($perms->change_events) {
			$editUrl = 'tiki-calendar_edit_item.php?fullcalendar=y&isModal=1&trackerItemId='. $this->getItemId() . '&calitemId=' . $event['calitemId'];
			$headerlib = TikiLib::lib('header');

			$headerlib->add_js_config('window.CKEDITOR_BASEPATH = "' . $tikiroot . 'vendor_bundled/vendor/ckeditor/ckeditor/";')
				->add_jsfile('vendor_bundled/vendor/ckeditor/ckeditor/ckeditor.js', true)
				->add_js('window.dialogData = [];', 1);
			// ->add_js('window.CKEDITOR.config._TikiRoot = "' . $tikiroot . '";', 1);
		} else {
			$editUrl = '';
		}

		return $this->renderTemplate('trackerinput/calendaritem.tpl', $context, ['editUrl' => $editUrl]);
	}

	function isValid($ins_fields_data)
	{
		return parent::isValid($ins_fields_data);
	}

	/**
	 * @return bool|string
	 */
	private function getCalendarItemId()
	{
		$calitemId = $this->attributeLib->get_attribute('trackeritem', $this->getItemId(), 'tiki.calendar.item');
		return $calitemId;
	}
}
