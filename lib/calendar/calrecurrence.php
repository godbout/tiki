<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

if (! defined('weekInSeconds')) {
	define('weekInSeconds', 604800);
}
if (! defined('dayInSeconds')) {
	define('dayInSeconds', 86400);
}

/**
 *
 */
class CalRecurrence extends TikiLib
{
	private $id;
	private $calendarId;
	private $start;
	private $end;
	private $allday;
	private $locationId;
	private $categoryId;
	private $nlId;
	private $priority;
	private $status;
	private $url;
	private $lang;
	private $name;
	private $description;
	private $weekly;
	private $weekday;
	private $monthly;
	private $dayOfMonth;
	private $yearly;
	private $dateOfYear; // format is mmdd
	private $nbRecurrences;
	private $startPeriod;
	private $endPeriod;
	private $user;
	private $created;
	private $lastModif;
	private $initialItem;
	private $uid;
	private $uri;

	/**
	 * @param $param
	 */
	public function __construct($param = -1)
	{
		parent::__construct();
		if ($param > 0) {
			$this->setId($param);
		}
		$this->load();
	}

	public function load()
	{
		if ($this->getId() > 0) {
			$query = "SELECT calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, weekly, weekday, monthly, dayOfMonth,"
					 . "yearly, dateOfYear, nbRecurrences, startPeriod, endPeriod, user, created, lastModif, uri, uid FROM tiki_calendar_recurrence "
					 . "WHERE recurrenceId = ?";
			$result = $this->query($query, [(int)$this->getId()]);
			if ($row = $result->fetchRow()) {
				$this->setCalendarId($row['calendarId']);
				$this->setStart($row['start']);
				$this->setEnd($row['end']);
				$this->setAllday($row['allday']);
				$this->setLocationId($row['locationId']);
				$this->setCategoryId($row['categoryId']);
				$this->setNlId($row['nlId']);
				$this->setPriority($row['priority']);
				$this->setStatus($row['status']);
				$this->setUrl($row['url']);
				$this->setLang($row['lang']);
				$this->setName($row['name']);
				$this->setDescription($row['description']);
				$this->setWeekly($row['weekly'] == 1);
				$this->setWeekday($row['weekday']);
				$this->setMonthly($row['monthly'] == 1);
				$this->setDayOfMonth($row['dayOfMonth']);
				$this->setYearly($row['yearly'] == 1);
				$this->setDateOfYear($row['dateOfYear']);
				$this->setNbRecurrences($row['nbRecurrences']);
				$this->setStartPeriod($row['startPeriod']);
				$this->setEndPeriod($row['endPeriod']);
				$this->setUser($row['user']);
				$this->setCreated($row['created']);
				$this->setLastModif($row['lastModif']);
				$this->setUri($row['uri']);
				$this->setUid($row['uid']);
			}
		}
	}

	public function updateDetails($data) {
		$this->setCalendarId($data['calendarId']);
		$this->setStart(\DateTime::createFromFormat('U', $data['start'])->setTimezone(new \DateTimeZone('UTC'))->format('Hi'));
		$this->setEnd(\DateTime::createFromFormat('U', $data['end'])->setTimezone(new \DateTimeZone('UTC'))->format('Hi'));
		if (isset($data['newloc'])) {
			$this->setLocationId($data['newloc']);
		}
		if (isset($data['newcat'])) {
			$this->setCategoryId($data['newcat']);
		}
		if (isset($data['priority'])) {
			$this->setPriority($data['priority']);
		}
		if (isset($data['status'])) {
			$this->setStatus($data['status']);
		}
		if (isset($data['lang'])) {
			$this->setLang($data['lang']);
		}
		if (isset($data['nlId'])) {
			$this->setNlId($data['nlId']);
		}
		if (isset($data['url'])) {
			$this->setUrl($data['url']);
		}
		if (isset($data['name'])) {
			$this->setName($data['name']);
		}
		if (isset($data['description'])) {
			$this->setDescription($data['description']);
		}
		if (isset($data['user'])) {
			$this->setUser($data['user']);
		}
		if (isset($data['uri'])) {
			$this->setUri($data['uri']);
		}
		if (isset($data['uid'])) {
			$this->setUid($data['uid']);
		}
	}

	/**
	 * When updating the recurrence rule,
	 * we are offered the the option to update all the recurrent events already created
	 * (i.e. $updateManuallyChanged = true), or only the events for which the changes on the rules
	 * have no incidence on the changes done manually (i.e. fields changed in the rule are not the fields changed
	 * in the event)
	 */
	public function save($updateManuallyChangedEvents = false)
	{
		if (! $this->isValid()) {
			return false;
		}
		if ($this->getId() > 0) {
			return $this->update($updateManuallyChangedEvents);
		}
		return $this->create();
	}

	/**
	 * Validation before storing (or updating) to the database.
	 * returns true if succeeds, false otherwise
	 */
	public function isValid()
	{
		// should be related to a calendar
		if (! ($this->getCalendarId() > 0)) {
			return false;
		}
		// should have valid start and end date
		if (! ($this->isAllday())
			 && (! ($this->getStart() > 0) || ! ($this->getEnd() > 0) || ($this->getStart() > 2359) || ($this->getEnd() > 2359) || ($this->getStart() > $this->getEnd()))) {
			return false;
		}
		// should be recurrent on "some" basis
		if (! $this->isWeekly() && ! $this->isMonthly() && ! $this->isYearly()) {
			return false;
		}
		// recurrence should be correctly defined
		if (($this->isWeekly() && (is_null($this->getWeekday()) || $this->getWeekday() > 6 || $this->getWeekday() < 0 || $this->getWeekday() == ''))
			|| ($this->isMonthly() && (is_null($this->getDayOfMonth()) || $this->getDayOfMonth() > 31 || $this->getDayOfMonth() < 1 || $this->getDayOfMonth() == ''))
			|| ($this->isYearly() && (is_null($this->getDateOfYear()) || $this->getDateOfYear() > 1231 || $this->getDateOfYear() < 0101 || $this->getDateOfYear() == ''))
			 ) {
			return false;
		}
		// recurrence period should be defined
		if ((is_null($this->getNbRecurrences()) || ($this->getNbRecurrences() == '') || ($this->getNbRecurrences() == 0))
			&& (is_null($this->getEndPeriod()) || ($this->getEndPeriod() == '') || ($this->getEndPeriod() < $this->getStartPeriod())) ) {
			return false;
		}
		//
		if (is_null($this->getNlId())) {
			return false;
		}
		// should inform the language
		if (is_null($this->getLang()) || $this->getLang() == "") {
			return false;
		}
		// should have a name
		if (is_null($this->getName()) || $this->getName() == "") {
			return false;
		}
		return true;
	}

	/**
	 * @param null $fromTime
	 * @return mixed
	 */
	public function delete($fromTime = null)
	{
		global $user;
		$tx = TikiDb::get()->begin();

		if (is_null($fromTime)) {
			$fromTime = time();
		}

		$calendarlib = TikiLib::lib('calendar');
		$tiki_calendar_items = TikiDb::get()->table('tiki_calendar_items');

		$calItemIds = $tiki_calendar_items->fetchColumn('calItemId', [
			'recurrenceId' => $this->getId(),
			'start' => $tiki_calendar_items->greaterThan($fromTime),
		]);

		foreach ($calItemIds as $calItemId) {
			$calendarlib->drop_item($user, $calItemId, true);
		}

		// this seems to leave ones in the past alone by default but detatches them from the recurrence rule (odd)
		$query = "UPDATE tiki_calendar_items SET recurrenceId = NULL WHERE recurrenceId = ?";
		$bindvars = [(int)$this->getId()];
		$this->query($query, $bindvars);
		$query = "DELETE FROM tiki_calendar_recurrence WHERE recurrenceId = ?";
		$bindvars = [(int)$this->getId()];
		$ret = $this->query($query, $bindvars);

		$tx->commit();

		return $ret;
	}

	/**
	 * @return bool
	 */
	private function create()
	{
		$query = "INSERT INTO tiki_calendar_recurrence (calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
				 . "weekly, weekday, monthly, dayOfMonth,yearly, dateOfYear, nbRecurrences, startPeriod, endPeriod, user, created, lastModif, uri, uid) "
				 . "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$now = $this->now;
		$bindvars = [
						$this->getCalendarId(),
						$this->getStart(),
						$this->getEnd(),
						$this->isAllday() ? 1 : 0,
						$this->getLocationId(),
						$this->getCategoryId(),
						$this->getNlId(),
						$this->getPriority(),
						$this->getStatus(),
						$this->getUrl(),
						$this->getLang(),
						$this->getName(),
						$this->getDescription(),
						$this->isWeekly() ? 1 : 0,
						$this->getWeekday(),
						$this->isMonthly() ? 1 : 0,
						$this->getDayOfMonth(),
						$this->isYearly() ? 1 : 0,
						$this->getDateOfYear(),
						$this->getNbRecurrences(),
						$this->getStartPeriod(),
						$this->getEndPeriod(),
						$this->getUser(),
						$now,
						$now,
						$this->getUri(),
						$this->getUid(),
					 ];
		$result = $this->query($query, $bindvars);
		if ($result) {
			$this->setId($this->GetOne("SELECT `recurrenceId` FROM `tiki_calendar_recurrence` WHERE `created`=?", [$now]));
			if ($this->getId() > 0) {
				// create the recurrent events
				$this->createEvents();
				return true;
			}
		}
		return false;
	}

	/**
	 * @param bool $updateManuallyChangedEvents
	 * @return bool
	 */
	private function update($updateManuallyChangedEvents = false)
	{
		$query = "UPDATE tiki_calendar_recurrence SET calendarId = ?, start = ?, end = ?, allday = ?, locationId = ?, categoryId = ?, nlId = ?, priority = ?, status = ?, "
				 . "url = ?, lang = ?, name = ?, description = ?, weekly = ?, weekday = ?, monthly = ?, dayOfMonth = ?, yearly = ?, dateOfYear = ?, nbRecurrences = ?, "
				 . "startPeriod = ?, endPeriod = ?, user = ?, lastModif = ?, uri = ?, uid = ? WHERE recurrenceId = ?";
		$now = time();
		$bindvars = [
						$this->getCalendarId(),
						$this->getStart(),
						$this->getEnd(),
						$this->isAllday() ? 1 : 0,
						$this->getLocationId(),
						$this->getCategoryId(),
						$this->getNlId(),
						$this->getPriority(),
						$this->getStatus(),
						$this->getUrl(),
						$this->getLang(),
						$this->getName(),
						$this->getDescription(),
						$this->isWeekly() ? 1 : 0,
						$this->getWeekday(),
						$this->isMonthly() ? 1 : 0,
						$this->getDayOfMonth(),
						$this->isYearly() ? 1 : 0,
						$this->getDateOfYear(),
						$this->getNbRecurrences(),
						$this->getStartPeriod(),
						$this->getEndPeriod(),
						$this->getUser(),
						$now,
						$this->getUri(),
						$this->getUid(),
						$this->getId()
					 ];
		$oldRec = new CalRecurrence($this->getId()); // we'll need old version to compare fields.
		$result = $this->query($query, $bindvars);
		if ($result) {
			// update the recurrent events, according to the way to handle the already changed events
			$this->updateEvents($updateManuallyChangedEvents, $oldRec);
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function createEvents()
	{
		global $user;

		$vcalendar = $this->constructVCalendar();
		$start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
		$end = $this->getEndPeriod();
		if (! $end) {
			$end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
		}

		$expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end));
		$tx = TikiDb::get()->begin();
		foreach ($expanded->VEVENT as $vevent) {
			$data = [
				'calendarId'   => $this->getCalendarId(),
				'start'        => $vevent->DTSTART->getDateTime()->getTimeStamp(),
				'end'          => $vevent->DTEND->getDateTime()->getTimeStamp(),
				'locationId'   => $this->getLocationId(),
				'categoryId'   => $this->getCategoryId(),
				'nlId'         => $this->getNlId(),
				'priority'     => $this->getPriority(),
				'status'       => $this->getStatus(),
				'url'          => $this->getUrl(),
				'lang'         => $this->getLang(),
				'name'         => $this->getName(),
				'description'  => $this->getDescription(),
				'user'         => $this->getUser(),
				'created'      => $this->getCreated(),
				'lastmodif'    => $this->getCreated(),
				'allday'       => $this->isAllday(),
				'recurrenceId' => $this->getId(),
				'changed'      => 0,
			];
			TikiLib::lib('calendar')->set_item($user, null, $data, [], true);
		}
		$tx->commit();
	}

	/**
	 * Attempts to update expanded recurring events in db based off changes in the recurring event record.
	 * Matches events by old schedule (before update) and original start date.
	 * TODO: this currently does not support EXDATE exclusions (i.e. individual event deletes) from recurring schedule
	 * because we match by position in the event list. In the future, this can be expanded to support EXDATE exclusions
	 * in order to sync deleted events here and in Tiki\SabreDav\CalDAVBackend.
	 * @param bool $updateManuallyChangedEvents
	 * @param $oldRec
	 */
	public function updateEvents($updateManuallyChangedEvents, $oldRec)
	{
		global $user;

		$changedFields = $this->compareFields($oldRec);
		if (! $changedFields) {
			return;
		}
		
		$query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
				 . "user, created, lastModif, changed, recurrenceStart "
				 . "FROM tiki_calendar_items WHERE recurrenceId = ? ORDER BY start";
		$bindvars = [(int)$this->getId()];
		$existing = $this->fetchAll($query, $bindvars);

		$vcalendar = $oldRec->constructVCalendar();
		$start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
		$end = $oldRec->getEndPeriod();
		if (! $end) {
			$end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
		}
		$old_expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end));

		$vcalendar = $this->constructVCalendar();
		$start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
		$end = $oldRec->getEndPeriod();
		if (! $end) {
			$end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
		}
		$new_expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end));

		$tx = TikiDb::get()->begin();
		foreach ($new_expanded->VEVENT as $key => $vevent) {
			$found = false;
			if (! empty($old_expanded->VEVENT[$key]->DTSTART)) {
				$old_start = $old_expanded->VEVENT[$key]->DTSTART->getDateTime()->getTimeStamp();
				foreach ($existing as $row) {
					if (($row['recurrenceStart'] && $row['recurrenceStart'] == $old_start) || $row['start'] == $old_start) {
						$found = $row;
						break;
					}
				}
			}
			if (! $found) {
				// create it
				$data = [
					'calendarId'   => $this->getCalendarId(),
					'start'        => $vevent->DTSTART->getDateTime()->getTimeStamp(),
					'end'          => $vevent->DTEND->getDateTime()->getTimeStamp(),
					'locationId'   => $this->getLocationId(),
					'categoryId'   => $this->getCategoryId(),
					'nlId'         => $this->getNlId(),
					'priority'     => $this->getPriority(),
					'status'       => $this->getStatus(),
					'url'          => $this->getUrl(),
					'lang'         => $this->getLang(),
					'name'         => $this->getName(),
					'description'  => $this->getDescription(),
					'user'         => $this->getUser(),
					'created'      => $this->getCreated(),
					'lastmodif'    => $this->getCreated(),
					'allday'       => $this->isAllday(),
					'recurrenceId' => $this->getId(),
					'changed'      => 0,
				];
				TikiLib::lib('calendar')->set_item($user, null, $data, [], true);
			} elseif ($found['changed'] == 0 || $updateManuallyChangedEvents) {
				// update with changes
				foreach ($changedFields as $field) {
					if (substr($field, 0, 1) != "_") {
						$found[$field] = $this->$field;
					}
				}
				$changedFieldsOfEvent = $this->compareFieldsOfEvent($found, $oldRec);
				foreach ($changedFieldsOfEvent as $field) {
					if (substr($field, 0, 1) == "_") {
						$found['start'] = $vevent->DTSTART->getDateTime()->getTimeStamp();
						$found['end'] = $vevent->DTEND->getDateTime()->getTimeStamp();
						if ($found['changed']) {
							$found['recurrenceStart'] = $found['start'];
						}
						break;
					}
				}
				// keep changed flag as this event might still be changed and we only updated some of the fields here
				TikiLib::lib('calendar')->set_item($user, $found['calitemId'], $found, [], true);
			}
		}
		$tx->commit();
	}

	/**
	 * Update individual events in a recurring event series that were manually tweaked in clients.
	 */
	public function updateOverrides($events) {
		global $user;

		$query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
				 . "user, created, lastModif, changed, recurrenceStart "
				 . "FROM tiki_calendar_items WHERE recurrenceId = ? ORDER BY start";
		$bindvars = [(int)$this->getId()];
		$existing = $this->fetchAll($query, $bindvars);

		foreach ($events as $event) {
			foreach ($existing as $row) {
				if (($row['recurrenceStart'] && $row['recurrenceStart'] == $event['recurrenceStart']) || $row['start'] == $event['recurrenceStart']) {
					$event['calendarId'] = $row['calendarId'];
					TikiLib::lib('calendar')->set_item($user, $row['calitemId'], $event, [], true);
					break;
				}
			}
		}
	}

	/**
	 * @param $oldRec
	 * @return array
	 */
	public function compareFields($oldRec)
	{
		$result = [];
		if ($this->getCalendarId() != $oldRec->getCalendarId()) {
			$result[] = "calendarId";
		}
		if ($this->getStart() != $oldRec->getStart()) {
			$result[] = "_start";
		}
		if ($this->getEnd() != $oldRec->getEnd()) {
			$result[] = "_end";
		}
		if ($this->isAllday() != $oldRec->isAllday()) {
			$result[] = "allday";
		}
		if ($this->getLocationId() != $oldRec->getLocationId() && ! ($this->getLocationId() == '' && $oldRec->getLocationId() == 0)) {
			$result[] = "locationId";
		}
		if ($this->getCategoryId() != $oldRec->getCategoryId() && ! ($this->getCategoryId() == '' && $oldRec->getCategoryId() == 0)) {
			$result[] = "categoryId";
		}
		if ($this->getNlId() != $oldRec->getNlId()) {
			$result[] = "nlId";
		}
		if ($this->getPriority() != $oldRec->getPriority() && ! ($oldRec->getPriority() == '' && $oldRec->getPriority() == 0)) {
			$result[] = "priority";
		}
		if ($this->getStatus() != $oldRec->getStatus()) {
			$result[] = "status";
		}
		if ($this->getUrl() != $oldRec->getUrl()) {
			$result[] = "url";
		}
		if ($this->getLang() != $oldRec->getLang()) {
			$result[] = "lang";
		}
		if ($this->getName() != $oldRec->getName()) {
			$result[] = "name";
		}
		if ($this->getDescription() != $oldRec->getDescription()) {
			$result[] = "description";
		}
		if ($this->isWeekly() && ($this->getWeekday() != $oldRec->getWeekday())) {
			$result[] = "_weekday";
		}
		if ($this->isMonthly() && ($this->getDayOfMonth() != $oldRec->getDayOfMonth())) {
			$result[] = "_dayOfMonth";
		}
		if ($this->isYearly() && ($this->getDateOfYear() != $oldRec->getDateOfYear())) {
			$result[] = "_dateOfYear";
		}
		return $result;
	}

	/**
	 * @param $evt
	 * @param $oldRec
	 * @return array
	 */
	public function compareFieldsOfEvent($evt, $oldRec)
	{
		$result = [];
		if ($evt['calendarId'] != $oldRec->getCalendarId()) {
			$result[] = "calendarId";
		}
		if (TikiLib::date_format2('Hi', $evt['start']) != $oldRec->getStart()) {
			$result[] = "start";
		}
		// checking the end is double check : is it the right hour ? is it the same day ?
		if ((TikiLib::date_format2('Hi', $evt['end']) != $oldRec->getEnd()) || (TikiLib::date_format2('Ymd', $evt['start']) != TikiLib::date_format2('Ymd', $evt['end']))) {
			$result[] = "end";
		}
		if ($evt['allday'] != $oldRec->isAllday()) {
			$result[] = "allday";
		}
		if ($evt['locationId'] != $oldRec->getLocationId()) {
			$result[] = "locationId";
		}
		if ($evt['categoryId'] != $oldRec->getCategoryId()) {
			$result[] = "categoryId";
		}
		if ($evt['nlId'] != $oldRec->getNlId()) {
			$result[] = "nlId";
		}
		if ($evt['priority'] != $oldRec->getPriority()) {
			$result[] = "priority";
		}
		if ($evt['status'] != $oldRec->getStatus()) {
			$result[] = "status";
		}
		if ($evt['url'] != $oldRec->getUrl()) {
			$result[] = "url";
		}
		if ($evt['lang'] != $oldRec->getLang()) {
			$result[] = "lang";
		}
		if ($evt['name'] != $oldRec->getName()) {
			$result[] = "name";
		}
		if ($evt['description'] != $oldRec->getDescription()) {
			$result[] = "description";
		}
		if (TikiLib::date_format2('Hi', $evt['start']) != str_pad($oldRec->getStart(), 4, "0", STR_PAD_LEFT)) {
			$result[] = "_start";
		}
		if (TikiLib::date_format2('Hi', $evt['end']) != str_pad($oldRec->getEnd(), 4, "0", STR_PAD_LEFT)) {
			$result[] = "_end";
		}
		if ($oldRec->isWeekly()) {
			if (TikiLib::date_format2('w', $evt['start']) != $oldRec->getWeekday()) {
				$result[] = "_weekday";
			}
		} elseif ($oldRec->isMonthly()) {
			if (TikiLib::date_format2('d', $evt['start']) != $oldRec->getDayOfMonth()) {
				$result[] = "_dayOfMonth";
			}
		} elseif ($oldRec->isYearly()) {
			if (TikiLib::date_format2('md', $evt['start']) != $oldRec->getDateOfYear()) {
				$result[] = "_dateOfYear";
			}
		}
		return $result;
	}

	public function fillUid($uid) {
		$this->query("update `tiki_calendar_recurrence` set `uid` = ? where `recurrenceId` = ?", [$uid, $this->getId()]);
	}

	public function getFirstItemId() {
		$query = "SELECT calitemId FROM `tiki_calendar_items` WHERE recurrenceId = ? ORDER BY calitemId";
		$result = $this->query($query, [(int)$this->getId()]);
		if ($row = $result->fetchRow()) {
			return $row['calitemId'];
		}
		return null;
	}

	public function constructVCalendar() {
		static $calendar_timezones = [];
		if (isset($calendar_timezones[$this->getCalendarId()])) {
			$timezone = $calendar_timezones[$this->getCalendarId()];
		} else {
			$calendar = TikiLib::lib('calendar')->get_calendar($this->getCalendarId());
			$timezone = TikiLib::lib('tiki')->get_display_timezone($calendar['user']);
			$calendar_timezones[$this->getCalendarId()] = $timezone;
		}
		if ($this->isAllday()) {
			$startOffset = 0;
			$endOffset = 86399;
		} else {
			$startOffset = str_pad($this->getStart(), 4, '0', STR_PAD_LEFT);
			$startOffset = substr($startOffset, 0, 2) * 60 * 60 + substr($startOffset, -2) * 60;
			$endOffset = str_pad($this->getEnd(), 4, '0', STR_PAD_LEFT);
			$endOffset = substr($endOffset, 0, 2) * 60 * 60 + substr($endOffset, -2) * 60;
		}
		// peculiarity here is that start/end period are in user's timezone (dtzone) but start/end offsets are in UTC hours
		$dtzone = new DateTimeZone($timezone);
		$dtstart = DateTime::createFromFormat('U', $this->getStartPeriod() + $startOffset);
		$dtstart->setTimezone($dtzone);
		$dtstart->setTimestamp($dtstart->getTimestamp() + $dtstart->getOffset());
		$dtend = DateTime::createFromFormat('U', $this->getStartPeriod() + $endOffset);
		$dtend->setTimezone($dtzone);
		$dtend->setTimestamp($dtend->getTimestamp() + $dtend->getOffset());
		
		$data = [
			'CREATED' => DateTime::createFromFormat('U', $this->getCreated() ?? 0)->format('Ymd\THis\Z'),
			'DTSTAMP' => DateTime::createFromFormat('U', $this->getLastModif() ?? 0)->format('Ymd\THis\Z'),
			'LAST-MODIFIED' => DateTime::createFromFormat('U', $this->getLastModif() ?? 0)->format('Ymd\THis\Z'),
			'SUMMARY' => $this->getName(),
			'PRIORITY' => $this->getPriority(),
			'TRANSP' => 'OPAQUE',
			'DTSTART' => $dtstart,
			'DTEND'   => $dtend,
		];
		if (! empty($this->getUid())) {
			$data['UID'] = $this->getUid();
		}
		if (! empty($this->getDescription())) {
			$data['DESCRIPTION'] = $this->getDescription();
		}
		$locations = TikiLib::lib('calendar')->list_locations($this->getCalendarId());
		if (! empty($locations[$this->getLocationId()])) {
			$data['LOCATION'] = $locations[$this->getLocationId()];
		}
		$categories = TikiLib::lib('calendar')->list_categories($this->getCategoryId());
		if (! empty($categories[$this->getCategoryId()])) {
			$data['CATEGORIES'] = $categories[$this->getCategoryId()];
		}
		if (! empty($this->getUrl())) {
			$data['URL'] = $this->getUrl();
		}

		$weekdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
		if ($this->isWeekly()) {
			$rrule = 'FREQ=WEEKLY;BYDAY='.$weekdays[$this->getWeekday()];
		} elseif ($this->isMonthly()) {
			$rrule = 'FREQ=MONTHLY;BYMONTHDAY='.$this->getDayOfMonth();
		} elseif ($this->isYearly()) {
			$doy = $this->getDateOfYear();
			$day = substr($doy, -2);
			$month = substr($doy, 0, strlen($doy)-2);
			$rrule = 'FREQ=YEARLY;BYMONTH='.$month.';BYMONTHDAY='.$day;
		} else {
			$rrule = 'FREQ=DAILY';
		}
		if ($this->getNbRecurrences() > 0) {
			$rrule .= ';COUNT='.$this->getNbRecurrences();
		} else {
			$rrule .= ';UNTIL='.DateTime::createFromFormat('U', $this->getEndPeriod())->format('Ymd\THis\Z');
		}
		$data['RRULE'] = $rrule;

		$vcalendar = new Sabre\VObject\Component\VCalendar();
		$vevent = $vcalendar->add('VEVENT', $data);

		if ((string)$vevent->UID != $this->getUid()) {
			// save UID for Tiki-generated calendar events as this must not change in the future
			// SabreDav automatically generates UID value if none is present
			$this->fillUid((string)$vevent->UID);
		}

		return $vcalendar;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return [
		'id' => $this->getId(),
		'weekly' => $this->isWeekly(),
		'weekday' => $this->getWeekday(),
		'monthly' => $this->isMonthly(),
		'dayOfMonth' => $this->getDayOfMonth(),
		'yearly' => $this->isYearly(),
		'dateOfYear' => $this->getDateOfYear(),
		'dateOfYear_month' => floor($this->getDateOfYear() / 100),
		'dateOfYear_day' => $this->getDateOfYear() - 100 * floor($this->getDateOfYear() / 100),
		'nbRecurrences' => $this->getNbRecurrences(),
		'startPeriod' => $this->getStartPeriod(),
		'endPeriod' => $this->getEndPeriod(),
		'user' => $this->getUser(),
		'created' => $this->getCreated(),
		'lastModif' => $this->getLastModif()
		];
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param $value
	 */
	public function setId($value)
	{
		$this->id = $value;
	}

	public function getCalendarId()
	{
		return $this->calendarId;
	}

	/**
	 * @param $value
	 */
	public function setCalendarId($value)
	{
		$this->calendarId = $value;
	}

	public function getStart()
	{
		return $this->start;
	}

	/**
	 * @param $value
	 */
	public function setStart($value)
	{
		$this->start = $value;
	}

	public function getEnd()
	{
		return $this->end;
	}

	/**
	 * @param $value
	 */
	public function setEnd($value)
	{
		$this->end = $value;
	}

	public function isAllday()
	{
		return $this->allday;
	}

	/**
	 * @param $value
	 */
	public function setAllday($value)
	{
		$this->allday = $value;
	}

	public function getLocationId()
	{
		return $this->locationId;
	}

	/**
	 * @param $value
	 */
	public function setLocationId($value)
	{
		$this->locationId = $value;
	}

	public function getCategoryId()
	{
		return $this->categoryId;
	}

	/**
	 * @param $value
	 */
	public function setCategoryId($value)
	{
		$this->categoryId = $value;
	}

	public function getNlId()
	{
		return $this->nlId;
	}

	/**
	 * @param $value
	 */
	public function setNlId($value)
	{
		$this->nlId = $value;
	}

	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param $value
	 */
	public function setPriority($value)
	{
		$this->priority = $value;
	}

	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param $value
	 */
	public function setStatus($value)
	{
		$this->status = $value;
	}

	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param $value
	 */
	public function setUrl($value)
	{
		$this->url = $value;
	}

	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param $value
	 */
	public function setLang($value)
	{
		$this->lang = $value;
	}

	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param $value
	 */
	public function setName($value)
	{
		$this->name = $value;
	}

	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param $value
	 */
	public function setDescription($value)
	{
		$this->description = $value;
	}

	public function isWeekly()
	{
		return $this->weekly;
	}

	/**
	 * @param $value
	 */
	public function setWeekly($value)
	{
		$this->weekly = $value;
	}

	public function getWeekday()
	{
		return $this->weekday;
	}

	/**
	 * @param $value
	 */
	public function setWeekday($value)
	{
		$this->weekday = $value;
	}

	public function isMonthly()
	{
		return $this->monthly;
	}

	/**
	 * @param $value
	 */
	public function setMonthly($value)
	{
		$this->monthly = $value;
	}

	public function getDayOfMonth()
	{
		return $this->dayOfMonth;
	}

	/**
	 * @param $value
	 */
	public function setDayOfMonth($value)
	{
		$this->dayOfMonth = $value;
	}

	public function isYearly()
	{
		return $this->yearly;
	}

	/**
	 * @param $value
	 */
	public function setYearly($value)
	{
		$this->yearly = $value;
	}

	public function getDateOfYear()
	{
		return $this->dateOfYear;
	}

	/**
	 * @param $value
	 */
	public function setDateOfYear($value)
	{
		$this->dateOfYear = $value;
	}

	public function getNbRecurrences()
	{
		return $this->nbRecurrences;
	}

	/**
	 * @param $value
	 */
	public function setNbRecurrences($value)
	{
		$this->nbRecurrences = $value;
	}

	public function getStartPeriod()
	{
		return $this->startPeriod;
	}

	/**
	 * @param $value
	 */
	public function setStartPeriod($value)
	{
		$this->startPeriod = $value ? $value : $this->now;
	}

	public function getEndPeriod()
	{
		return $this->endPeriod;
	}

	/**
	 * @param $value
	 */
	public function setEndPeriod($value)
	{
		$this->endPeriod = $value;
	}

	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param $value
	 */
	public function setUser($value)
	{
		$this->user = $value;
	}

	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param $value
	 */
	public function setCreated($value)
	{
		$this->created = $value;
	}

	public function getLastModif()
	{
		return $this->lastModif;
	}

	/**
	 * @param $value
	 */
	public function setLastModif($value)
	{
		$this->lastModif = $value;
	}

	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param $value
	 */
	public function setUid($value)
	{
		$this->uid = $value;
	}

	public function getUri()
	{
		return $this->uri;
	}

	/**
	 * @param $value
	 */
	public function setUri($value)
	{
		$this->uri = $value;
	}
}
