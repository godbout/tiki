<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Tiki_Profile_ChannelList
 *
 * @package
 */
class Tiki_Profile_ChannelList
{
    private $channels = [];

    public static function fromConfiguration($string)
    {
        $list = new self;

        $string = str_replace("\r", '', $string);
        $lines = explode("\n", $string);

        foreach ($lines as $line) {
            $parts = explode(',', $line);
            if (count($parts) < 3) {
                continue;
            } elseif (count($parts) == 3) {
                $parts[] = 'Admins';
            }

            $parts = array_map('trim', $parts);
            list($name, $domain, $profile) = array_slice($parts, 0, 3);
            $groups = array_slice($parts, 3);

            $list->channels[$name] = [
                'domain' => $domain,
                'profile' => $profile,
                'groups' => $groups,
            ];
        }

        return $list;
    }

    public function canExecuteChannels(array $channelNames, array $groups, $skipInputCheck = false)
    {
        foreach ($channelNames as $channel) {
            if (! array_key_exists($channel, $this->channels)) {
                return false;
            }

            // At least one match is required
            if (count(array_intersect($groups, $this->channels[$channel]['groups'])) == 0) {
                return false;
            }

            // Checking against input if required (note that unlike normal groups, all must match)
            foreach ($this->channels[$channel]['groups'] as $g) {
                if ($skipInputCheck) {
                    break;
                }
                if (preg_match('/\$profilerequest\:(\w+)\$/', $g, $matches)) {
                    for ($i = 1, $count_matches = count($matches); $i < $count_matches; $i++) {
                        if (empty($_REQUEST[$matches[$i]])) {
                            return false;
                        }
                        $tocheck = str_replace($matches[0], $_REQUEST[$matches[$i]], $g);
                        if (! in_array($tocheck, $groups)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function getProfiles(array $channelNames)
    {
        $profiles = [];

        foreach ($channelNames as $channelName) {
            $info = $this->channels[$channelName];

            if ($profile = Tiki_Profile::fromNames($info['domain'], $info['profile'])) {
                $profiles[$channelName] = $profile;
            }
        }

        return $profiles;
    }

    public function addChannel($name, $domain, $profile, $groups)
    {
        $this->channels[ $name ] = [
            'domain' => $domain,
            'profile' => $profile,
            'groups' => $groups,
        ];
    }

    public function getConfiguration()
    {
        $out = '';
        foreach ($this->channels as $name => $info) {
            $parts = $info['groups'];
            array_unshift($parts, $name, $info['domain'], $info['profile']);

            $out .= implode(', ', $parts) . "\n";
        }

        return trim($out);
    }
}
