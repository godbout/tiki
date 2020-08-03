<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Yaml\Yaml;

class Tiki_Profile_Writer
{
    private $data;
    private $filePath;
    private $externalWriter;
    private $references = [];

    public function __construct($directory, $profileName)
    {
        $this->filePath = "$directory/$profileName.yml";
        $this->externalWriter = new Tiki_Profile_Writer_ExternalWriter("$directory/$profileName");
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            $this->data = Yaml::parse($content);
        } else {
            $this->data = [
                'permissions' => [],
                'preferences' => [],
                'objects' => [],
                'unknown_objects' => [],
            ];
        }
    }

    /**
     * Set the reference name for the next object to be added.
     * @param mixed $name
     */
    public function pushReference($name)
    {
        $this->references[] = $name;
    }

    /**
     * Write an external page content.
     * @param mixed $page
     * @param mixed $content
     * @param mixed $ext
     */
    public function writeExternal($page, $content, $ext = '.wiki')
    {
        $this->externalWriter->write($page . $ext, $content);
    }

    public function getExternalWriter()
    {
        return $this->externalWriter;
    }

    public function setPreference($name, $value)
    {
        $this->data['preferences'][$name] = $value;
        // Add a fake entry to record the inclusion timestamp, removed during clean-up
        $this->addFake('preference', $name);
    }

    public function getPreference($name)
    {
        if (isset($this->data['preferences'][$name])) {
            return $this->data['preferences'][$name];
        }
    }

    public function addFake($type, $id)
    {
        $this->addRawObject($type, null, $id, ['_is_fake' => true]);
    }

    /**
     * Adds an object to the profile based on the data in the current instance. If the added
     * object already exists within the profile, it will first be removed, allowing objects to
     * be refreshed in the profile under construction.
     * @param mixed $type
     * @param mixed $currentId
     */
    public function addObject($type, $currentId, array $data)
    {
        $reference = $this->getInternalReference($type, $currentId, $data);

        $this->addRawObject($type, $reference, $currentId, $data);

        // Search through currently unknown reference for this item
        $ref = $this->getReference($type, $currentId);
        $this->removeUnknown($type, $currentId, $ref);

        return $reference;
    }

    public function addPermissions($groupName, array $data)
    {
        $this->addFake('group', $groupName);

        $this->data['permissions'][$groupName] = $data;
    }

    private function addRawObject($type, $reference, $currentId, $data)
    {
        $this->clearObject($type, $currentId);

        $this->data['objects'][] = [
            'type' => $type,
            'ref' => $reference,
            '_id' => $currentId,
            '_timestamp' => time(),
            'data' => $data,
        ];
    }

    private function getInternalReference($type, $currentId, array $data)
    {
        // Objects already in use need to preserve their reference to preserve internal consistency
        if ($reference = $this->getObject($type, $currentId)) {
            array_shift($this->references);

            return $reference['ref'];
        }

        // Use the name specified by the user
        if ($reference = array_shift($this->references)) {
            return $reference;
        }

        // Find the object name property
        $candidates = [];
        $currentId = preg_replace('/[^\w]+/u', '', strtolower($currentId));

        foreach (['name', 'title'] as $key) {
            if (! empty($data[$key])) {
                $basename = preg_replace('/\W+/u', '_', strtolower($data[$key]));
                $candidates[] = $basename;
                $candidates[] = $type . '_' . $basename;
                $candidates[] = $type . '_' . $basename . '_' . $currentId;
            }
        }

        $candidates[] = $type . '_' . $currentId;

        // Find the first suitable candidate
        foreach ($candidates as $candidate) {
            if (! $this->isReferenceInUse($candidate)) {
                return $candidate;
            }
        }

        // Fall back to something unique, which should never really happen
        return uniqid();
    }

    private function isReferenceInUse($ref)
    {
        foreach ($this->data['objects'] as $info) {
            if ($info['ref'] == $ref) {
                return true;
            }
        }

        return false;
    }

    /**
     * When an object is being added, the previously unknown references within the object may be
     * resolved. This removed the unknwn object references and replaces them with a permanent key.
     * @param mixed $type
     * @param mixed $id
     * @param mixed $replacement
     */
    public function removeUnknown($type, $id, $replacement)
    {
        if (! is_array($this->data['unknown_objects'])) {
            return;
        }
        foreach ($this->data['unknown_objects'] as $key => $entry) {
            if ($entry['type'] == $type && $entry['id'] == $id) {
                $token = $entry['token'];
                if (is_array($this->data['objects'])) {
                    array_walk_recursive(
                        $this->data['objects'],
                        function (& $entry) use ($token, $replacement) {
                            if (is_string($entry)) {
                                $entry = str_replace($token, $replacement, $entry);
                            }
                        }
                    );
                }
                if (is_array($this->data['permissions'])) {
                    array_walk_recursive(
                        $this->data['permissions'],
                        function (& $entry) use ($token, $replacement) {
                            if (is_string($entry)) {
                                $entry = str_replace($token, $replacement, $entry);
                            }
                        }
                    );
                }

                $writer = $this->externalWriter;
                foreach ($writer->getFiles() as $file => $content) {
                    $content = str_replace($token, $replacement, $content);
                    $writer->write($file, $content);
                }

                unset($this->data['unknown_objects'][$key]);

                break;
            }
        }
    }

    /**
     * Provides the list of currently unknown objects.
     */
    public function getUnknownObjects()
    {
        if (is_array($this->data['unknown_objects'])) {
            return array_map(
                function ($entry) {
                    unset($entry['token']);

                    return $entry;
                },
                $this->data['unknown_objects']
            );
        }

        return;
    }

    /**
     * Obtain the replacement string for the given type and id. Type can also be a callback
     * or a helper name for complex transformations that require parsing and are called through
     * the declarative interfaces (plugins, field types, preferences, ...). Extra parameters are
     * provided in those cases since the type may depend on other arguments.
     * @param mixed $type
     * @param mixed $id
     */
    public function getReference($type, $id, array $parameters = [])
    {
        if (empty($id)) {
            // Empty strings, id=0, ... not valid references skip
            return $id;
        }

        // If we are provided with an anonymous function to handle special cases
        if ($type instanceof Closure) {
            return call_user_func($type, $this, $id, $parameters);
        } elseif (method_exists('Tiki_Profile_Writer_Helper', $type)) {
            return Tiki_Profile_Writer_Helper::$type($this, $id, $parameters);
        }

        // Let 'wiki page' or 'tracker item' be provided as type, no effect when profile types used
        $type = Tiki_Profile_Installer::convertTypeInvert($type);

        if (is_array($id)) {
            $parent = $this;

            return array_map(
                function ($value) use ($type, $parent, $parameters) {
                    return $parent->getReference($type, $value, $parameters);
                },
                $id
            );
        }

        // If it looks like a special parameter, leave as-is
        if (preg_match('/^\{+\$?\w+\}+$/', $id)) {
            return $id;
        }

        foreach ($this->data['objects'] as $object) {
            if ($object['type'] == $type && $object['_id'] == $id) {
                $name = trim($object['ref']);

                return "\$profileobject:{$name}\$";
            }
        }

        return $this->generateTemporaryReference($type, $id);
    }

    private function getObject($type, $id)
    {
        $type = Tiki_Profile_Installer::convertTypeInvert($type);
        foreach ($this->data['objects'] as $object) {
            if ($object['type'] == $type && $object['_id'] == $id) {
                return $object;
            }
        }

        return null;
    }

    public function isKnown($type, $id)
    {
        return ! is_null($this->getObject($type, $id));
    }

    public function getInclusionTimestamp($type, $id)
    {
        $object = $this->getObject($type, $id);

        if (! empty($object['_timestamp'])) {
            return $object['_timestamp'];
        }
    }

    public function formatExternalReference($symbol, $profile, $repository = null)
    {
        $symbol = trim($symbol);
        $profile = trim($profile);
        $repository = trim($repository);
        if ($repository) {
            $repository .= ':';
        }

        return "\$profileobject:$repository$profile:$symbol\$";
    }

    private function clearObject($type, $id)
    {
        $this->data['objects'] = array_filter(
            $this->data['objects'],
            function ($item) use ($type, $id) {
                return $item['type'] != $type || $item['_id'] != $id;
            }
        );
    }

    private function generateTemporaryReference($type, $id)
    {
        // Find existing entry for unknown reference
        if (is_array($this->data['unknown_objects'])) {
            foreach ($this->data['unknown_objects'] as $entry) {
                if ($entry['type'] == $type && $entry['id'] == $id) {
                    return $entry['token'];
                }
            }
        }

        // Or generate a new one
        $token = '$unknownobject:' . uniqid() . '$';
        $this->data['unknown_objects'][] = [
            'type' => $type,
            'id' => $id,
            'token' => $token,
        ];

        return $token;
    }

    /**
     * Write in-memory changes to the disk.
     */
    public function save()
    {
        file_put_contents($this->filePath, Yaml::dump($this->quoteArray($this->data), 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        $this->externalWriter->apply();
    }

    /**
     * quote strings that may be problematic in YAML
     * @param mixed $arr
     */
    public function quoteArray($arr)
    {
        array_walk_recursive($arr, 'Tiki_Profile_Writer::quoteString');

        return ($arr);
    }
    public function quoteString(&$data, $key)
    {
        if (strtolower($data) == 'yes' || strtolower($data) == 'no'
            || strpos($data, '{') !== false || strpos($data, '[') !== false
        ) {
            if (strpos($data, '"') === false) {
                $data = '"' . $data . '"';
            }
        }
    }

    /**
     * Removes all of the meta-data used while the profile is under construction.
     */
    public function clean()
    {
        array_walk(
            $this->data['objects'],
            function (& $entry) {
                unset($entry['_id']);
                unset($entry['_timestamp']);
            }
        );
        unset($this->data['unknown_objects']);

        // Remove fake preference entries
        $this->data['objects'] = array_filter(
            $this->data['objects'],
            function ($entry) {
                return empty($entry['data']['_is_fake']);
            }
        );
    }

    public function dump()
    {
        $clone = clone $this;
        $clone->clean();

        return Yaml::dump($clone->data, 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
}
