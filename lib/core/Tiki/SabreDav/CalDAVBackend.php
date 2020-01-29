<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\SabreDav;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\VObject;

use TikiLib;
use Perms;

/**
 * Tiki database CalDAV backend
 *
 * This backend is used to store calendar-data in a Tiki MySQL database
 */
class CalDAVBackend extends CalDAV\Backend\AbstractBackend
    implements
        CalDAV\Backend\SyncSupport,
        CalDAV\Backend\SubscriptionSupport,
        CalDAV\Backend\SchedulingSupport,
        CalDAV\Backend\SharingSupport {

    /**
     * We need to specify a max date, because we need to stop *somewhere*
     *
     * On 32 bit system the maximum for a signed integer is 2147483647, so
     * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
     * in 2038-01-19 to avoid problems when the date is converted
     * to a unix timestamp.
     */
    const MAX_DATE = '2038-01-01';

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     * Add your own properties by simply adding on to this array.
     *
     * Note that only string-based properties are supported here.
     *
     * @var array
     */
    public $propertyMap = [
        '{DAV:}displayname'                                   => 'name',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order'           => 'order',
        '{http://apple.com/ns/ical/}calendar-color'           => 'custombgcolor',
    ];

    /**
     * List of subscription properties, and how they map to database fieldnames.
     *
     * @var array
     */
    public $subscriptionPropertyMap = [
        '{DAV:}displayname'                                           => 'name',
        '{http://apple.com/ns/ical/}refreshrate'                      => 'refresh_rate',
        '{http://apple.com/ns/ical/}calendar-order'                   => 'order',
        '{http://apple.com/ns/ical/}calendar-color'                   => 'color',
        '{http://calendarserver.org/ns/}subscribed-strip-todos'       => 'strip_todos',
        '{http://calendarserver.org/ns/}subscribed-strip-alarms'      => 'strip_alarms',
        '{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'strip_attachments',
    ];

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     * @return array
     */
    function getCalendarsForUser($principalUri) {
        $user = PrincipalBackend::mapUriToUser($principalUri);

        $calendarlib = TikiLib::lib('calendar');
        $result = TikiLib::lib('calendar')->list_calendars();
        $result['data'] = Perms::filter([ 'type' => 'calendar' ], 'object', $result['data'], [ 'object' => 'calendarId' ], 'view_calendar');

        $calendars = [];
        foreach ($result['data'] as $row) {
            $components = ['VEVENT'];
            if (! empty($row['components'])) {
                $components = explode(',', $row['components']);
            }

            if (empty($row['timezone'])) {
                $row['timezone'] = TikiLib::lib('tiki')->get_display_timezone($user);
            }

            $calendar = [
                'id'                                                                 => [(int)$row['calendarId'], (int)$row['calendarInstanceId']],
                'uri'                                                                => $row['uri'] ?? $this->getCalendarUri($row['calendarId']),
                'principaluri'                                                       => PrincipalBackend::mapUserToUri($user),
                '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}getctag'                  => 'http://sabre.io/ns/sync/' . ($row['synctoken'] ?? '0'),
                '{http://sabredav.org/ns}sync-token'                                 => $row['synctoken'] ?? '0',
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp'         => new CalDAV\Xml\Property\ScheduleCalendarTransp($row['transparent'] ?? 'opaque'),
                'share-resource-uri'                                                 => '/ns/share/' . $row['id'],
            ];

            $calendar['share-access'] = $row['access'] ?? 1; // if no access is defined, user is the owner
            if ($calendar['share-access'] > 1) {
                // read-only is for backwards compatbility. Might go away in
                // the future.
                $calendar['read-only'] = (int)$calendar['share-access'] === \Sabre\DAV\Sharing\Plugin::ACCESS_READ;
            }

            foreach ($this->propertyMap as $xmlName => $dbName) {
                $calendar[$xmlName] = $row[$dbName] ?? '';
            }

            $calendars[] = $calendar;
        }

        return $calendars;
    }

    protected function mapCalendarUriToCalendar($calendarUri) {
        if (preg_match('#calendar-(.*)$#', $calendarUri, $m)) {
            $id = $m[1];
            if ($calendar = TikiLib::lib('calendar')->get_calendar($id)) {
                return $calendar;
            } else {
                throw new DAV\Exception\NotFound('Calendaruri does not exist in Tiki calendar database.');
            }
        } else {
            throw new DAV\Exception('Calendaruri is in invalid format.');
        }
    }

    protected function mapCalendarObjectUriToItem($objectUri) {
        $calendarlib = TikiLib::lib('calendar');
        if (preg_match('#calendar-object-(r?)(.*)$#', $objectUri, $m)) {
            if ($m[1]) {
                $item = new \CalRecurrence($m[2]);
            } else {
                $item = $calendarlib->get_item($m[2]);
            }
        } else {
            $item = $calendarlib->get_item_by_uri($objectUri);
        }
        if (! $item) {
            throw new DAV\Exception\NotFound('Objecturi not found.');
        }
        return $item;
    }

    protected function getCalendarUri($calendarId) {
        return 'calendar-'.$calendarId;
    }

    protected function getCalendarObjectUri($row_or_rec) {
        if (is_array($row_or_rec)) {
            if (! empty($row_or_rec['uri'])) {
                return $row_or_rec['uri'];
            } else {
                return 'calendar-object-'.$row_or_rec['calitemId'];
            }
        } else {
            if ($row_or_rec->getUri()) {
                return $row_or_rec->getUri();
            } else {
                return 'calendar-object-r'.$row_or_rec->getId();
            }
        }
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string
     */
    function createCalendar($principalUri, $calendarUri, array $properties) {
        global $access;
        $access->check_permission(['tiki_p_admin_calendar']);

        $user = PrincipalBackend::mapUriToUser($principalUri);

        $options = [];

        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        if (!isset($properties[$sccs])) {
            // Default value
            $options['components'] = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof CalDAV\Xml\Property\SupportedCalendarComponentSet)) {
                throw new DAV\Exception('The ' . $sccs . ' property must be of type: \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet');
            }
            $options['components'] = implode(',', $properties[$sccs]->getValue());
        }
        $transp = '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $options['transparent'] = $properties[$transp]->getValue();
        }
        $options['synctoken'] = 1;

        $name = $description = '';

        foreach ($this->propertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                switch($dbName) {
                    case 'name':
                        $name = $properties[$xmlName];
                        break;
                    case 'description':
                        $description = $properties[$xmlName];
                        break;
                    default:
                        $options[$dbName] = $properties[$xmlName];
                }
            }
        }

        $calendarId = TikiLib::lib('calendar')->set_calendar(0, $user, $name, $description, [], $options);

        return [
            $calendarId,
            0,
        ];
    }

    /**
     * Updates properties for a calendar.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        if ($instanceId == 0) {
            $this->ensureCalendarAccess($calendarId, $instanceId, 'admin_calendar');
        }

        $supportedProperties = array_keys($this->propertyMap);
        $supportedProperties[] = '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';

        $propPatch->handle($supportedProperties, function($mutations) use ($calendarId, $instanceId) {
            $calendar = TikiLib::lib('calendar')->get_calendar($calendarId);
            $user = $calendar['user'];
            $name = $calendar['name'];
            $description = $calendar['description'];
            $options = [];
            foreach ($mutations as $propertyName => $propertyValue) {

                switch ($propertyName) {
                    case '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' :
                        $options['transparent'] = $propertyValue->getValue();
                        break;
                    default :
                        $fieldName = $this->propertyMap[$propertyName];
                        switch ($fieldName) {
                            case 'name':
                                $name = $propertyValue;
                                break;
                            case 'description':
                                $description = $propertyValue;
                                break;
                            default:
                                $options[$fieldName] = $propertyValue;
                        }
                        break;
                }

            }
            TikiLib::lib('calendar')->set_calendar($calendarId, $user, $name, $description, [], $options, $instanceId);
            TikiLib::lib('calendar')->add_change($calendarId, "", 2);

            return true;
        });
    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @return void
     */
    function deleteCalendar($calendarId) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        if ($instanceId == 0) {
            /**
             * If the user is the owner of the calendar, we delete all data and all
             * instances.
             **/
            $this->ensureCalendarAccess($calendarId, $instanceId, 'admin_calendar');
            TikiLib::lib('calendar')->drop_calendar($calendarId);
        } else {
            /**
             * If it was an instance of a shared calendar, we only delete that
             * instance.
             */
            TikiLib::lib('calendar')->remove_calendar_instance($calendarId, null, $instanceId);
        }
    }

    protected function ensureCalendarAccess($calendarId, $instanceId, $tiki_permission, $instance_permission = null)
    {
        if ($instanceId > 0) {
            $instance = TikiLib::lib('calendar')->get_calendar_instance($instanceId);
            if ($instance_permission == 'read') {
                $instance_permission = [DAV\Sharing\Plugin::ACCESS_SHAREDOWNER, DAV\Sharing\Plugin::ACCESS_READ, DAV\Sharing\Plugin::ACCESS_READWRITE];
            } else {
                $instance_permission = [DAV\Sharing\Plugin::ACCESS_SHAREDOWNER, DAV\Sharing\Plugin::ACCESS_READWRITE];
            }
            if (! in_array($instance['access'], $instance_permission)) {
                throw new DAV\Exception\Forbidden(tra('Permission denied') . ": " . tra('shared calendar not writable'));
            }
        } else {
            if (is_array($calendarId)) {
                $perms = Perms::get($calendarId[0], $calendarId[1]);
            } else {
                $perms = Perms::get('calendar', $calendarId);
            }
            if (! $perms->$tiki_permission) {
                throw new DAV\Exception\Forbidden(tra('Permission denied') . ": " . 'tiki_p_'.$tiki_permission);
            }
        }
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     * @return array
     */
    function getCalendarObjects($calendarId) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'view_calendar', 'read');

        $objects = TikiLib::lib('calendar')->get_events($calendarId);
        $filtered = Perms::filter([ 'type' => 'event' ], 'object', $objects, [ 'object' => 'calitemId' ], 'view_events');

        $result = [];
        $recurrences = [];
        foreach ($filtered as $row) {
            if ($row['recurrenceId']) {
                $recurrences[] = $row['recurrenceId'];
                continue;
            }
            $calendardata = $this->constructCalendarData($row);
            $result[] = [
                'id'           => $row['calitemId'],
                'uri'          => $this->getCalendarObjectUri($row),
                'lastmodified' => (int)$row['lastModif'],
                'etag'         => '"' . md5($calendardata) . '"',
                'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
                'component'    => 'VEVENT',
            ];
        }
        $recurrences = array_unique($recurrences);
        foreach ($recurrences as $recurrenceId) {
            $rec = new \CalRecurrence($recurrenceId);
            $calendardata = $this->constructRecurringCalendarData($rec);
            $result[] = [
                'id'           => 'r'.$rec->getId(),
                'uri'          => $this->getCalendarObjectUri($rec),
                'lastmodified' => (int)$rec->getLastModif(),
                'etag'         => '"' . md5($calendardata) . '"',
                'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
                'component'    => 'VEVENT',
            ];
        }
        return $result;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    function getCalendarObject($calendarId, $objectUri) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'view_calendar', 'read');

        $row = $this->mapCalendarObjectUriToItem($objectUri);
        if (! is_array($row)) {
            $rec = $row;
            $row = ['calitemId' => $rec->getFirstItemId(), 'calendarId' => $rec->getCalendarId(), 'lastModif' => $rec->getLastModif()];
        } else {
            $rec = null;
        }

        $perms = Perms::get('event', $row['calitemId']);
        if (! $perms->view_events) {
            throw new DAV\Exception\Forbidden(tra('Permission denied') . ": " . 'tiki_p_view_events');
        }

        if ($row['calendarId'] != $calendarId) {
            return null;
        }

        if ($rec) {
            $calendardata = $this->constructRecurringCalendarData($rec);
        } else {
            $calendardata = $this->constructCalendarData($row);
        }

        return [
            'id'           => $rec ? 'r'.$rec->getId() : $row['calitemId'],
            'uri'          => $this->getCalendarObjectUri($rec ?? $row),
            'lastmodified' => (int)$row['lastModif'],
            'etag'         => '"' . md5($calendardata) . '"',
            'size'         => strlen($calendardata),
            'calendardata' => $calendardata,
            'component'    => 'VEVENT',
         ];
    }

    /**
     * Returns a list of calendar objects.
     *
     * This method should work identical to getCalendarObject, but instead
     * return all the calendar objects in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $calendarId
     * @param array $uris
     * @return array
     */
    function getMultipleCalendarObjects($calendarId, array $uris) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'view_calendar', 'read');

        $result = [];
        foreach (array_chunk($uris, 900) as $chunk) {
            $itemIdsOrUris = array_map(function($uri){
                return str_replace("calendar-object-", "", $uri);
            }, $chunk);
            $objects = TikiLib::lib('calendar')->get_events($calendarId, $itemIdsOrUris);
            $filtered = Perms::filter([ 'type' => 'event' ], 'object', $objects, [ 'object' => 'calitemId' ], 'view_events');
            $recurrences = [];
            foreach ($filtered as $row) {
                if ($row['recurrenceId']) {
                    $recurrences[] = $row['recurrenceId'];
                    continue;
                }
                $calendardata = $this->constructCalendarData($row);
                $result[] = [
                    'id'           => $row['calitemId'],
                    'uri'          => $this->getCalendarObjectUri($row),
                    'lastmodified' => (int)$row['lastModif'],
                    'etag'         => '"' . md5($calendardata) . '"',
                    'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
                    'calendardata' => $calendardata,
                    'component'    => 'VEVENT',
                ];
            }
            $recurrences = array_unique($recurrences);
            foreach ($recurrences as $recurrenceId) {
                $rec = new \CalRecurrence($recurrenceId);
                $calendardata = $this->constructRecurringCalendarData($rec);
                $result[] = [
                    'id'           => 'r'.$rec->getId(),
                    'uri'          => $this->getCalendarObjectUri($rec),
                    'lastmodified' => (int)$rec->getLastModif(),
                    'etag'         => '"' . md5($calendardata) . '"',
                    'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
                    'component'    => 'VEVENT',
                ];
            }
        }
        return $result;
    }


    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function createCalendarObject($calendarId, $objectUri, $calendarData) {
        global $user;

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'add_events', 'write');

        $calendar = TikiLib::lib('calendar')->get_calendar($calendarId);
        $timezone = TikiLib::lib('tiki')->get_display_timezone($calendar['user']);

        $data = Utilities::getDenormalizedData($calendarData, $timezone);
        $data['calendarId'] = $calendarId;
        $data['uri'] = $objectUri;

        if ($data['rec']) {
            if(empty($data['priority'])) {
                $data['priority'] = 0;
            }
            if(is_null($data['status'])) {
                $data['status'] = 1;
            }
            if(empty($data['lang'])) {
                $data['lang'] = 'en';
            }
            if(empty($data['nlId'])) {
                $data['nlId'] = 0;
            }
            $data['user'] = $user;
            $rec = $data['rec'];
            $rec->updateDetails($data);
            $rec->save(true);
            $rec->updateOverrides($data['overrides']);
        } else {
            TikiLib::lib('calendar')->set_item($user, 0, $data);
        }

        return '"' . $data['etag'] . '"';
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {
        global $user;

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $item = $this->mapCalendarObjectUriToItem($objectUri);
        if (! is_array($item)) {
            $rec = $item;
            $item = ['calitemId' => $rec->getFirstItemId()];
        } else {
            $rec = null;
        }

        $this->ensureCalendarAccess(['event', $item['calitemId']], $instanceId, 'change_events', 'write');

        $calendar = TikiLib::lib('calendar')->get_calendar($calendarId);
        $timezone = TikiLib::lib('tiki')->get_display_timezone($calendar['user']);

        $data = Utilities::getDenormalizedData($calendarData, $timezone);
        $data['calendarId'] = $calendarId;

        if ($rec) {
            if ($data['rec']) {
                $data['rec']->setId($rec->getId());
                $data['rec']->setUri($rec->getUri());
                $rec = $data['rec'];
            }
            $data['calitemId'] = $item['calitemId'];
            $rec->updateDetails($data);
            $rec->setUser($user);
            $rec->save(true);
            $rec->updateOverrides($data['overrides']);
        } else {
            TikiLib::lib('calendar')->set_item($user, $item['calitemId'], $data);
        }

        return '"' . $data['etag'] . '"';

    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return void
     */
    function deleteCalendarObject($calendarId, $objectUri) {
        global $user;

        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $item = $this->mapCalendarObjectUriToItem($objectUri);
        if (! is_array($item)) {
            $rec = $item;
            $item = ['calitemId' => $rec->getFirstItemId()];
        } else {
            $rec = null;
        }

        $this->ensureCalendarAccess(['event', $item['calitemId']], $instanceId, 'change_events', 'write');

        if ($rec) {
            $rec->delete(0);
        } else {
            TikiLib::lib('calendar')->drop_item($user, $item['calitemId']);
        }
    }

    protected function constructCalendarData($row, $serialize = true) {
        $vcalendar = Utilities::constructCalendarData($row);
        if ($serialize) {
            return $vcalendar->serialize();
        } else {
            return $vcalendar;
        }
    }

    protected function constructRecurringCalendarData($rec) {
        $vcalendar = $rec->constructVCalendar();
        $vevent = $vcalendar->VEVENT;
        $vevent->STATUS = Utilities::mapEventStatus($rec->getStatus());

        if ($firstItemId = $rec->getFirstItemId()) {
            // TODO: optimize this for N+1 query problem
            $item = TikiLib::lib('calendar')->get_item($firstItemId);
            foreach ($item['organizers'] as $user) {
                $vevent->add(
                    'ORGANIZER',
                    TikiLib::lib('user')->get_user_email($user),
                    [
                        'CN' => TikiLib::lib('tiki')->get_user_preference($user, 'realName'),
                    ]
                );
            }
            foreach ($item['participants'] as $par) {
                $vevent->add(
                    'ATTENDEE',
                    $par['email'],
                    [
                        'CN' => TikiLib::lib('tiki')->get_user_preference($par['username'], 'realName'),
                        'ROLE' => Utilities::mapAttendeeRole($par['role']),
                        'PARTSTAT' => $par['partstat'],
                    ]
                );
            }
        }

        $vevents = [$vevent];

        $changed_events = TikiLib::lib('calendar')->get_events($rec->getCalendarId(), [], null, null, null, $rec->getId(), 1);
        foreach ($changed_events as $row) {
            $eventcal = $this->constructCalendarData($row, false);
            $vcalendar->add($eventcal->VEVENT);
        }

        return $vcalendar->serialize();
    }

    protected function getRecurringEventWithChanges($rec) {
        $result = [];
        $calendardata = $this->constructRecurringCalendarData($rec);
        $result[] = [
            'id'           => 'r'.$rec->getId(),
            'uri'          => $this->getCalendarObjectUri($rec),
            'lastmodified' => (int)$rec->getLastModif(),
            'etag'         => '"' . md5($calendardata) . '"',
            'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
            'component'    => 'VEVENT',
        ];
        $changed_events = TikiLib::lib('calendar')->get_events($rec->getCalendarId(), [], null, null, null, $rec->getId(), 1);
        foreach ($changed_events as $row) {
            $calendardata = $this->constructCalendarData($row);
            $result[] = [
                'id'           => $row['calitemId'],
                'uri'          => $this->getCalendarObjectUri($row),
                'lastmodified' => (int)$row['lastModif'],
                'etag'         => '"' . md5($calendardata) . '"',
                'size'         => strlen($calendardata),  // TODO: add to Tiki: calendardata
                'component'    => 'VEVENT',
            ];
        }
        return $result;
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly adviced to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on a VEVENT.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interpret all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * This specific implementation (for the PDO) backend optimizes filters on
     * specific components, and VEVENT time-ranges.
     *
     * @param mixed $calendarId
     * @param array $filters
     * @return array
     */
    function calendarQuery($calendarId, array $filters) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'view_calendar', 'read');

        $componentType = null;
        $requirePostFilter = true;
        $timeRange = null;

        // if no filters were specified, we don't need to filter after a query
        if (!$filters['prop-filters'] && !$filters['comp-filters']) {
            $requirePostFilter = false;
        }

        // Figuring out if there's a component filter
        if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
            $componentType = $filters['comp-filters'][0]['name'];

            // Checking if we need post-filters
            if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['time-range'] && !$filters['comp-filters'][0]['prop-filters']) {
                $requirePostFilter = false;
            }
            // There was a time-range filter
            if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['time-range'])) {
                $timeRange = $filters['comp-filters'][0]['time-range'];

                // If start time OR the end time is not specified, we can do a
                // 100% accurate mysql query.
                if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
                    $requirePostFilter = false;
                }
            }

        }

        if ($timeRange && $timeRange['start']) {
            $start = $timeRange['start']->getTimeStamp();
        } else {
            $start = null;
        }
        if ($timeRange && $timeRange['end']) {
            $end = $timeRange['end']->getTimeStamp();
        } else {
            $end = null;
        }

        $objects = TikiLib::lib('calendar')->get_events($calendarId, [], $componentType, $start, $end);
        $filtered = Perms::filter([ 'type' => 'event' ], 'object', $objects, [ 'object' => 'calitemId' ], 'view_events');

        if ($requirePostFilter) {
            foreach ($filtered as $key => $row) {
                $row['calendarid'] = [$calendarId, $instanceId];
                $row['uri'] = $this->getCalendarObjectUri($row);
                if (!$this->validateFilterForObject($row, $filters)) {
                    unset($filtered[$key]);
                }
            }
        }

        $results = [];
        $recurrences = [];
        foreach ($filtered as $row) {
            if ($row['recurrenceId']) {
                $recurrences[] = $row['recurrenceId'];
                continue;
            }
            $results[] = $this->getCalendarObjectUri($row);
        }
        $recurrences = array_unique($recurrences);
        foreach ($recurrences as $recurrenceId) {
            $rec = new \CalRecurrence($recurrenceId);
            $results[] = $this->getCalendarObjectUri($rec);
        }

        return $results;
    }

    /**
     * Searches through all of a users calendars and calendar objects to find
     * an object with a specific UID.
     *
     * This method should return the path to this object, relative to the
     * calendar home, so this path usually only contains two parts:
     *
     * calendarpath/objectpath.ics
     *
     * If the uid is not found, return null.
     *
     * This method should only consider * objects that the principal owns, so
     * any calendars owned by other principals that also appear in this
     * collection should be ignored.
     *
     * @param string $principalUri
     * @param string $uid
     * @return string|null
     */
    function getCalendarObjectByUID($principalUri, $uid) {
        $user = PrincipalBackend::mapUriToUser($principalUri);
        $row = TikiLib::lib('calendar')->find_by_uid($user, $uid);
        if ($row) {
            $perms = Perms::get('event', $row['calitemId']);
            if ($perms->view_events) {
                if ($row['recurrenceId']) {
                    $rec = new \CalRecurrence($row['recurrenceId']);
                    return $this->getCalendarUri($row['calendarId']) . '/' . $this->getCalendarObjectUri($rec);
                } else {
                    return $this->getCalendarUri($row['calendarId']) . '/' . $this->getCalendarObjectUri($row);
                }
            }
        }
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property this is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param mixed $calendarId
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $this->ensureCalendarAccess($calendarId, $instanceId, 'view_calendar', 'read');

        $options = TikiLib::lib('calendar')->get_calendar_options($calendarId);

        if (empty($options['synctoken'])) {
            return null;
        }

        $result = [
            'syncToken' => $options['synctoken'],
            'added'     => [],
            'modified'  => [],
            'deleted'   => [],
        ];

        if ($syncToken) {
            $changes = TikiLib::lib('calendar')->get_changes($calendarId, $syncToken, $limit ?? -1);
            $filtered = Perms::filter([ 'type' => 'event' ], 'object', $changes, [ 'object' => 'calitemId' ], 'view_events');

            $recurrences = [];
            foreach ($filtered as $row) {

                if ($row['recurrenceId']) {
                    $recurrences[] = $row['recurrenceId'];
                    continue;
                }

                switch ($row['operation']) {
                    case 1 :
                        $result['added'][] = $this->getCalendarObjectUri($row);
                        break;
                    case 2 :
                        $result['modified'][] = $this->getCalendarObjectUri($row);
                        break;
                    case 3 :
                        $result['deleted'][] = $this->getCalendarObjectUri($row);
                        break;
                }

            }

            foreach ($recurrences as $recurrenceId) {
                $rec = new \CalRecurrence($recurrenceId);
                $result['modified'][] = $this->getCalendarObjectUri($rec);
            }
        } else {
            // No synctoken supplied, this is the initial sync.
            $events = TikiLib::lib('calendar')->get_events($calendarid);
            $filtered = Perms::filter([ 'type' => 'event' ], 'object', $events, [ 'object' => 'calitemId' ], 'view_events');
            
            $recurrences = [];
            foreach ($filtered as $row) {
                if ($row['recurrenceId']) {
                    $recurrences[] = $row['recurrenceId'];
                } else {
                    $result['added'][] = $this->getCalendarObjectUri($row);
                }
            }
            
            $recurrences = array_unique($recurrences);
            foreach ($recurrences as $recurrenceId) {
                $rec = new \CalRecurrence($recurrenceId);
                $result['added'][] = $this->getCalendarObjectUri($rec);
            }
        }

        return $result;
    }

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the subscription.
     *  * principaluri. The owner of the subscription. Almost always the same as
     *    principalUri passed to this method.
     *  * source. Url to the actual feed
     *
     * Furthermore, all the subscription info must be returned too:
     *
     * 1. {DAV:}displayname
     * 2. {http://apple.com/ns/ical/}refreshrate
     * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
     *    should not be stripped).
     * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
     *    should not be stripped).
     * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
     *    attachments should not be stripped).
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     * 9. {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     *    (should just be an instance of
     *    Sabre\CalDAV\Property\SupportedCalendarComponentSet, with a bunch of
     *    default components).
     *
     * @param string $principalUri
     * @return array
     */
    function getSubscriptionsForUser($principalUri) {
        $user = PrincipalBackend::mapUriToUser($principalUri);
        $subscriptions = TikiLib::lib('calendar')->get_subscriptions($user);

        foreach ($subscriptions as $row) {
            $subscription = [
                'id'           => $row['subscriptionId'],
                'uri'          => $this->getCalendarUri($row['calendarId']),
                'principaluri' => PrincipalBackend::mapUserToUri($row['user']),
                'source'       => $row['source'],
                'lastmodified' => $row['lastmodif'],

                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
            ];

            foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
                if (!is_null($row[$dbName])) {
                    $subscription[$xmlName] = $row[$dbName];
                }
            }

            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     * @param array $properties
     * @return mixed
     */
    function createSubscription($principalUri, $uri, array $properties) {
        $user = PrincipalBackend::mapUriToUser($principalUri);
        $calendar = $this->mapCalendarUriToCalendar($uri);

        if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
            throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
        }

        $data = [
            'user' => $user,
            'calendarId' => $calendar['calendarId'],
            'source' => $properties['{http://calendarserver.org/ns/}source']->getHref(),
        ];

        foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                $data[$dbName] = $properties[$xmlName];
            }
        }

        $subscriptionId = TikiLib::lib('calendar')->create_subscription($data);

        return $subscriptionId;
    }

    /**
     * Updates a subscription
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $subscriptionId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch) {
        $supportedProperties = array_keys($this->subscriptionPropertyMap);
        $supportedProperties[] = '{http://calendarserver.org/ns/}source';

        $propPatch->handle($supportedProperties, function($mutations) use ($subscriptionId) {

            $newValues = [];

            foreach ($mutations as $propertyName => $propertyValue) {

                if ($propertyName === '{http://calendarserver.org/ns/}source') {
                    $newValues['source'] = $propertyValue->getHref();
                } else {
                    $fieldName = $this->subscriptionPropertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
                }

            }

            TikiLib::lib('calendar')->update_subscription($subscriptionId, $newValues);
            return true;
        });
    }

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    function deleteSubscription($subscriptionId) {
        TikiLib::lib('calendar')->delete_subscription($subscriptionId);
    }

    /**
     * Returns a single scheduling object.
     *
     * The returned array should contain the following elements:
     *   * uri - A unique basename for the object. This will be used to
     *           construct a full uri.
     *   * calendardata - The iCalendar object
     *   * lastmodified - The last modification date. Can be an int for a unix
     *                    timestamp, or a PHP DateTime object.
     *   * etag - A unique token that must change if the object changed.
     *   * size - The size of the object, in bytes.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return array
     */
    function getSchedulingObject($principalUri, $objectUri) {
        $user = PrincipalBackend::mapUriToUser($principalUri);

        $row = TikiLib::lib('calendar')->get_scheduling_object($user, $objectUri);

        if (!$row) return null;

        return [
            'uri'          => $row['uri'],
            'calendardata' => $row['calendardata'],
            'lastmodified' => $row['lastmodif'],
            'etag'         => '"' . $row['etag'] . '"',
            'size'         => (int)$row['size'],
         ];
    }

    /**
     * Returns all scheduling objects for the inbox collection.
     *
     * These objects should be returned as an array. Every item in the array
     * should follow the same structure as returned from getSchedulingObject.
     *
     * The main difference is that 'calendardata' is optional.
     *
     * @param string $principalUri
     * @return array
     */
    function getSchedulingObjects($principalUri) {
        $user = PrincipalBackend::mapUriToUser($principalUri);

        $rows = TikiLib::lib('calendar')->get_scheduling_objects($user);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'calendardata' => $row['calendardata'],
                'uri'          => $row['uri'],
                'lastmodified' => $row['lastmodif'],
                'etag'         => '"' . $row['etag'] . '"',
                'size'         => (int)$row['size'],
            ];
        }

        return $result;
    }

    /**
     * Deletes a scheduling object
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return void
     */
    function deleteSchedulingObject($principalUri, $objectUri) {
        $user = PrincipalBackend::mapUriToUser($principalUri);
        TikiLib::lib('calendar')->delete_scheduling_object($user, $objectUri);
    }

    /**
     * Creates a new scheduling object. This should land in a users' inbox.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @param string $objectData
     * @return void
     */
    function createSchedulingObject($principalUri, $objectUri, $objectData) {
        $user = PrincipalBackend::mapUriToUser($principalUri);
        TikiLib::lib('calendar')->create_scheduling_object($user, $objectUri, $objectData);
    }

    /**
     * Updates the list of shares.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     * @return void
     */
    function updateInvites($calendarId, array $sharees) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        $currentInvites = $this->getInvites($calendarId);
        list($calendarId, $instanceId) = $calendarId;

        $calendarlib = TikiLib::lib('calendar');

        foreach ($sharees as $sharee) {
            if ($sharee->access === \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS) {
                // if access was set no NOACCESS, it means access for an
                // existing sharee was removed.
                $calendarlib->remove_calendar_instance($calendarId, $sharee->href);
                continue;
            }
            if (is_null($sharee->principal)) {
                // If the server could not determine the principal automatically,
                // we will mark the invite status as invalid.
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_INVALID;
            } else {
                // Because sabre/dav does not yet have an invitation system,
                // every invite is automatically accepted for now.
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;
            }
            foreach ($currentInvites as $oldSharee) {
                if ($oldSharee->href === $sharee->href) {
                    // This is an update
                    $sharee->properties = array_merge(
                        $oldSharee->properties,
                        $sharee->properties
                    );
                    $data = [
                        'access' => $sharee->access,
                        'share_name' => isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                        'share_invite_status' => $sharee->inviteStatus ?: $oldSharee->inviteStatus,
                    ];
                    $calendarlib->update_calendar_instance($calendarId, $share_href, $data);
                    continue 2;
                }
            }
            // If we got here, it means it was a new sharee
            $calendar = $calendarlib->get_calendar($calendarId);
            $data = [
                'calendarId' => $calendarId,
                'user' => PrincipalBackend::mapUriToUser($sharee->principal),
                'access' => $sharee->access,
                'name' => $calendar['name'],
                'uri' => \Sabre\DAV\UUIDUtil::getUUID(),
                'description' => $calendar['description'],
                'order' => $calendar['order'] ?? '',
                'color' => $calendar['custombgcolor'] ?? '',
                'timezone' => $calendar['timezone'] ?? '',
                'transparent' => 1,
                'share_href' => $sharee->href,
                'share_name' => isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                'share_invite_status' => $sharee->inviteStatus ?: \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
            ];
            $calendarlib->create_calendar_instance($data);
        }
    }

    /**
     * Returns the list of people whom a calendar is shared with.
     *
     * Every item in the returned list must be a Sharee object with at
     * least the following properties set:
     *   $href
     *   $shareAccess
     *   $inviteStatus
     *
     * and optionally:
     *   $properties
     *
     * @param mixed $calendarId
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    function getInvites($calendarId) {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to getInvites() is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $rows = TikiLib::lib('calendar')->get_calendar_instances($calendarId);

        $result = [];
        foreach ($rows as $row) {
            $result[] = new Sharee([
                'href'   => $row['share_href'],
                'access' => (int)$row['access'],
                /// Everyone is always immediately accepted, for now.
                'inviteStatus' => (int)$row['share_invites_tatus'],
                'properties'   =>
                    !empty($row['share_name'])
                    ? ['{DAV:}displayname' => $row['share_name']]
                    : [],
                'principal' => PrincipalBackend::mapUserToUri($row['user']),
            ]);
        }
        return $result;
    }

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    function setPublishStatus($calendarId, $value) {
        throw new DAV\Exception\NotImplemented('Not implemented');
    }
}
