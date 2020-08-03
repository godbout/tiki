<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 *
 */
class GeoLib
{
    /**
     * @param $type
     * @param $itemId
     * @return array
     */
    public function get_coordinates($type, $itemId)
    {
        $attributelib = TikiLib::lib('attribute');

        $attributes = $attributelib->get_attributes($type, $itemId);

        if (isset($attributes['tiki.geo.lat'], $attributes['tiki.geo.lon'])) {
            $coords = [
                'lat' => $attributes['tiki.geo.lat'],
                'lon' => $attributes['tiki.geo.lon'],
            ];

            if ($coords['lat'] == 0 && $coords['lon'] == 0) {
                return;
            }

            if (! empty($attributes['tiki.geo.google.zoom'])) {
                $coords['zoom'] = $attributes['tiki.geo.google.zoom'];
            }

            return $coords;
        }
    }

    /**
     * @param $type
     * @param $itemId
     * @return string
     */
    public function get_coordinates_string($type, $itemId)
    {
        if ($coords = $this->get_coordinates($type, $itemId)) {
            return $this->build_location_string($coords);
        }
    }

    /**
     * @param array $coords
     * @return string
     */
    public function build_location_string($coords)
    {
        $string = '';

        if (isset($coords['lat']) && isset($coords['lon'])) {
            $string = "{$coords['lon']},{$coords['lat']}";

            if (isset($coords['zoom'])) {
                $string .= ",{$coords['zoom']}";
            }
        }
        
        return $string;
    }

    /**
     * @param $type
     * @param $itemId
     * @param $coordinates
     */
    public function set_coordinates($type, $itemId, $coordinates)
    {
        if (is_string($coordinates)) {
            $coordinates = $this->parse_coordinates($coordinates);
        }

        if (isset($coordinates['lat'], $coordinates['lon'])) {
            $attributelib = TikiLib::lib('attribute');
            $attributelib->set_attribute($type, $itemId, 'tiki.geo.lat', $coordinates['lat']);
            $attributelib->set_attribute($type, $itemId, 'tiki.geo.lon', $coordinates['lon']);

            if (isset($coordinates['zoom'])) {
                $attributelib->set_attribute($type, $itemId, 'tiki.geo.google.zoom', $coordinates['zoom']);
            }
        }
    }

    /**
     * @param $string
     * @return array
     */
    public function parse_coordinates($string)
    {
        if (preg_match("/^(-?\d*(\.\d+)?),(-?\d*(\.\d+)?)(,(\d+))?$/", $string, $parts)) {
            $coords = [
                'lat' => $parts[3],
                'lon' => $parts[1],
            ];

            if (isset($parts[6])) {
                $coords['zoom'] = $parts[6];
            }

            return $coords;
        }
    }

    /**
     * @param $where
     * @return array|bool
     */
    public function geocode($where)
    {
        global $prefs;

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(
            [
                'address' => $where,
                'sensor' => 'false',
                'key' => $prefs['gmap_key'],
            ],
            '',
            '&'
        );

        $response = TikiLib::lib('tiki')->httprequest($url);
        $data = json_decode($response);

        if ($data->status !== 'OK') {
            return [
                'error' => $data->error_message,
                'status' => $data->status,
            ];
        }

        $first = reset($data->results);

        return [
            'status' => 'OK',
            'accuracy' => 500,
            'label' => $first->formatted_address,
            'lat' => $first->geometry->location->lat,
            'lon' => $first->geometry->location->lng,
            'address_components' => $first->address_components,
        ];
    }

    /**
     * @param $geo
     * @return array|bool
     */
    public function geofudge($geo)
    {
        if (! $geo) {
            return false;
        }
        if (empty($geo["lon"]) || empty($geo["lat"])) {
            return ["lon" => 0, "lat" => 0];
        }
        $geo["lon"] = $geo["lon"] + mt_rand(0, 10000) / 8000;
        $geo["lat"] = $geo["lat"] + mt_rand(0, 10000) / 10000;

        return $geo;
    }

    /**
     * @param $itemId
     * @param $geo
     */
    public function setTrackerGeo($itemId, $geo)
    {
        global $prefs;
        $trklib = TikiLib::lib('trk');
        $item = $trklib->get_tracker_item($itemId);
        $fields = $trklib->list_tracker_fields($item['trackerId']);
        foreach ($fields["data"] as $f) {
            if ($f["type"] == 'G' && $f["options_array"][0] == 'y') {
                $fieldId = $f["fieldId"];
                $options_array = $f["options_array"];
                $pointx = $geo['lon'];
                $pointy = $geo['lat'];
                $pointz = $prefs["gmap_defaultz"];

                break;
            }
        }
        if (isset($fieldId)) {
            $ins_fields["data"][$fieldId] = ['fieldId' => $fieldId, 'options_array' => $options_array, 'value' => "$pointx,$pointy,$pointz", 'type' => 'G'];
            $res = $trklib->replace_item($item['trackerId'], $itemId, $ins_fields);
        }
    }

    public function get_default_center()
    {
        global $prefs;
        $coords = $this->parse_coordinates($prefs['gmap_defaultx'] . ',' . $prefs['gmap_defaulty'] . ',' . $prefs['gmap_defaultz']);
        $center = ' data-geo-center="' . smarty_modifier_escape($this->build_location_string($coords)) . '" ';

        return $center;
    }
}
