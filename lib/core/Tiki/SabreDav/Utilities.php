<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\DAV;
use Sabre\VObject;
use TikiLib;

class Utilities {
  static function checkUploadPermission($galleryDefinition) {
    $canUpload = TikiLib::lib('filegal')->can_upload_to($galleryDefinition->getInfo());
    if (! $canUpload) {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkCreatePermission($galleryDefinition) {
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $galleryDefinition->getInfo());
    if ($perms['tiki_p_create_file_galleries'] != 'y') {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkDeleteGalleryPermission($galleryDefinition) {
    global $user, $prefs;

    $info = $galleryDefinition->getInfo();
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $info);

    $mygal_to_delete = ! empty($user) && $info['type'] === 'user' && $info['user'] !== $user && $perms['tiki_p_userfiles'] === 'y' && $info['parentId'] !== $prefs['fgal_root_user_id'];

    if ($perms['tiki_p_admin_file_galleries'] != 'y' && ! $mygal_to_delete) {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function checkDeleteFilePermission($galleryDefinition) {
    $perms = TikiLib::lib('tiki')->get_perm_object('', 'file gallery', $galleryDefinition->getInfo());
    if ($perms['tiki_p_remove_files'] != 'y' && $perms['tiki_p_admin_file_galleries'] != 'y') {
      throw new DAV\Exception\Forbidden('Permission denied.');
    }
  }

  static function parseContents($name, $data) {
    if (is_resource($data)) {
      $content = stream_get_contents($data);
    } else {
      $content = (string)$data;
    }

    $filesize = strlen($content);
    $mime = TikiLib::lib('mime')->from_content($name, $content);

    return compact('content', 'filesize', 'mime');
  }

  /**
   * Parses some information from calendar objects, used for optimized
   * calendar-queries and field mapping to Tiki. RRULE parsing partially
   * supports RFC 5545 as Tiki does not handle all of the specification.
   *
   * @param string $calendarData
   * @return array
   */
  static function getDenormalizedData($calendarData, $timezone) {
    $vObject = VObject\Reader::read($calendarData);
    $componentType = null;
    $component = null;
    $uid = null;
    foreach ($vObject->getComponents() as $component) {
      if ($component->name !== 'VTIMEZONE') {
        $componentType = $component->name;
        $uid = (string)$component->UID;
        break;
      }
    }
    if (!$componentType) {
      throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
    }

    $result = [
      'etag'           => md5($calendarData),
      'size'           => strlen($calendarData),
      'componenttype'  => $componentType,
      'uid'            => $uid,
    ];
    $result = array_merge(
      $result,
      self::getDenormalizedDataFromComponent($component, $timezone)
    );

    // check for individual instances changed in recurring events
    $result['overrides'] = [];
    foreach ($vObject->getComponents() as $component) {
      if ($component->name !== 'VEVENT') {
        continue;
      }
      if ($component->{'RECURRENCE-ID'}) {
        $result['overrides'][] = self::getDenormalizedDataFromComponent($component, $timezone);
      }
    }

    // Destroy circular references to PHP will GC the object.
    $vObject->destroy();

    return $result;
  }

  static function getDenormalizedDataFromComponent($component, $timezone) {
    $firstOccurence = null;
    $lastOccurence = null;
    $rec = null;

    if ($component && $component->name == 'VEVENT') {
      $firstOccurence = $component->DTSTART->getDateTime()->getTimeStamp();
      if (isset($component->DTEND)) {
        $lastOccurence = $component->DTEND->getDateTime()->getTimeStamp();
      } elseif (isset($component->DURATION)) {
        $endDate = clone $component->DTSTART->getDateTime();
        $endDate = $endDate->add(VObject\DateTimeParser::parse($component->DURATION->getValue()));
        $lastOccurence = $endDate->getTimeStamp();
      } elseif (!$component->DTSTART->hasTime()) {
        $endDate = clone $component->DTSTART->getDateTime();
        $endDate = $endDate->modify('+1 day');
        $lastOccurence = $endDate->getTimeStamp();
      } else {
        $lastOccurence = $firstOccurence;
      }
      if (isset($component->RRULE)) {
        $rec = new \CalRecurrence;
        $parts = $component->RRULE->getParts();
        switch ($parts['FREQ']) {
          case "WEEKLY":
            if (isset($parts['BYDAY'])) {
              $weekdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
              $weekday = array_search($parts['BYDAY'], $weekdays);
            } else {
              $weekday = $component->DTSTART->getDateTime()->format('w');
            }
            $rec->setWeekly(true);
            $rec->setWeekday($weekday);
            $rec->setMonthly(false);
            $rec->setYearly(false);
            break;
          case "MONTHLY":
            if (isset($parts['BYMONTHDAY'])) {
              $monthday = $parts['BYMONTHDAY'];
            } else {
              $monthday = $component->DTSTART->getDateTime()->format('j');
            }
            $rec->setWeekly(false);
            $rec->setMonthly(true);
            $rec->setDayOfMonth($parts['BYMONTHDAY']);
            $rec->setYearly(false);
            break;
          case "YEARLY":
            if (isset($parts['BYMONTH'])) {
              $month = $parts['BYMONTH'];
            } else {
              $month = $component->DTSTART->getDateTime()->format('n');
            }
            if (isset($parts['BYMONTH'])) {
              $monthday = $parts['BYMONTHDAY'];
            } else {
              $monthday = $component->DTSTART->getDateTime()->format('j');
            }
            $rec->setWeekly(false);
            $rec->setMonthly(false);
            $rec->setYearly(true);
            $rec->setDateOfYear(str_pad($month, 2, '0', STR_PAD_LEFT) . str_pad($monthday, 2, '0', STR_PAD_LEFT));
            break;
        }
        $rec->setStartPeriod(\TikiDate::getStartDay($firstOccurence, $timezone));
        if (isset($parts['COUNT'])) {
          $rec->setNbRecurrences($parts['COUNT']);
        } else {
          $rec->setEndPeriod(\TikiDate::getStartDay(strtotime($parts['UNTIL']), $timezone));
        }
        $rec->setLang('en');
        $rec->setNlId(0);
        $rec->setAllday(0);
      }

      // Ensure Occurence values are positive
      if ($firstOccurence < 0) $firstOccurence = 0;
      if ($lastOccurence < 0) $lastOccurence = 0;
    }

    $result = [
      'start'          => $firstOccurence,
      'end'            => $lastOccurence,
      'rec'            => $rec,
    ];

    if (isset($component->{'RECURRENCE-ID'})) {
      $result['recurrenceStart'] = $component->{'RECURRENCE-ID'}->getDateTime()->getTimeStamp();
    }

    if (isset($component->CREATED)) {
      $result['created'] = $component->CREATED->getDateTime()->getTimeStamp();
    }
    if (isset($component->DTSTAMP)) {
      $result['lastmodif'] = $component->DTSTAMP->getDateTime()->getTimeStamp();
    }
    if (isset($component->{'LAST-MODIFIED'})) {
      $result['lastmodif'] = $component->{'LAST-MODIFIED'}->getDateTime()->getTimeStamp();
    }
    if (isset($component->SUMMARY)) {
      $result['name'] = $component->SUMMARY;
    }
    if (isset($component->DESCRIPTION)) {
      $result['description'] = $component->DESCRIPTION;
    }
    if (isset($component->LOCATION)) {
      $result['newloc'] = $component->LOCATION;
    }
    if (isset($component->CATEGORIES)) {
      $cats = explode(',', $component->CATEGORIES);
      $result['newcat'] = $cats[0];
    }
    if (isset($component->PRIORITY)) {
      $result['priority'] = $component->PRIORITY;
    }
    if (isset($component->STATUS)) {
      $result['status'] = self::reverseMapEventStatus($component->STATUS);
    }
    if (isset($component->URL)) {
      $result['url'] = $component->URL;
    }
    if (isset($component->ORGANIZER)) {
      $result['organizers'] = [];
      $result['real_organizers'] = [];
      foreach ($component->ORGANIZER as $organizer) {
        $email = preg_replace("/MAILTO:\s*/i", "", (string)$organizer);
        $user = TikiLib::lib('user')->get_user_by_email($email);
        if ($user) {
          $result['organizers'][] = $user;
        }
        $cn = (string)$organizer->CN;
        if (empty($cn)) {
          $result['real_organizers'][] = $email;
        } else {
          $result['real_organizers'][] = "$cn <$email>";
        }
      }
    }
    if (isset($component->ATTENDEE)) {
      // participants is used by calendarlib to store in Tiki - these are mapped attendees to Tiki users
      $result['participants'] = [];
      foreach ($component->ATTENDEE as $attendee) {
        $email = preg_replace("/MAILTO:\s*/i", "", (string)$attendee);
        $user = TikiLib::lib('user')->get_user_by_email($email);
        if ($user) {
          $participant = [
            'username' => $user,
            'email' => $email,
          ];
          $role = self::reverseMapAttendeeRole($attendee['ROLE']);
          if ($role) {
            $participant['role'] = $role;
          }
          if (isset($attendee['PARTSTAT'])) {
            $participant['partstat'] = $attendee['PARTSTAT'];
          }
          $result['participants'][] = $participant;
        }
      }
      // fetch attendees as they are for later reference like RSVP actions via Cypht
      $result['attendees'] = [];
      foreach ($component->ATTENDEE as $attendee) {
        $email = preg_replace("/MAILTO:\s*/i", "", (string)$attendee);
        $cn = (string)$attendee->CN;
        if (empty($cn)) {
          $cn = $email;
        }
        $result['attendees'][] = "$cn <$email>";
      }
    }

    return $result;
  }

  static function mapEventStatus($event_status) {
    switch ($event_status) {
      case '0':
        return 'TENTATIVE';
      case '1':
        return 'CONFIRMED';
      case '2':
        return 'CANCELLED';
    }
    return '';
  }

  static function reverseMapEventStatus($event_status) {
    switch ($event_status) {
      case 'TENTATIVE':
        return '0';
      case 'CONFIRMED':
        return '1';
      case 'CANCELLED':
        return '2';
    }
    return '';
  }

  static function mapAttendeeRole($role) {
    switch ($role) {
      case '0':
        return 'CHAIR';
      case '1':
        return 'REQ-PARTICIPANT';
      case '2':
        return 'OPT-PARTICIPANT';
      case '3':
        return 'NON-PARTICIPANT';
    }
    return '';
  }

  static function reverseMapAttendeeRole($role) {
    switch ($role) {
      case 'CHAIR':
        return '0';
      case 'REQ-PARTICIPANT':
        return '1';
      case 'OPT-PARTICIPANT':
        return '2';
      case 'NON-PARTICIPANT':
        return '3';
    }
    return '';
  }
}
