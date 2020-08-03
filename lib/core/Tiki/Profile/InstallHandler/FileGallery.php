<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_FileGallery extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = [
            'owner' => 'admin',
            'public' => 'n',
            'galleryId' => null,
            'parent' => 1,
            'visible' => 'n',		// fgal default is y so set here so it gets set only if specified in flags[]
        ];

        $conversions = [
            'owner' => 'user',
            'max_rows' => 'maxRows',
            'parent' => 'parentId',
        ];

        $columns = [
            'id',
            'icon',
            'name',
            'size',
            'description',
            'created',
            'hits',
            'lastDownload',
            'lockedby',
            'modified',
            'author',
            'last_user',
            'comment',
            'files',
            'backlinks',
            'deleteAfter',
            'checked',
            'share',
            'source',
            'ocr_state',
            'explorer',		// not really a column, but follows the same pattern
            'path',			// also
            'slideshow',	// also
        ];

        $data = $this->obj->getData();

        $data = Tiki_Profile::convertLists($data, ['flags' => 'y']);

        $column = isset($data['column']) ? $data['column'] : [];
        $popup = isset($data['popup']) ? $data['popup'] : [];

        if (in_array('name', $column) && in_array('filename', $column)) {
            $data['show_name'] = 'a';
            unset($column[array_search('name', $column)], $column[array_search('filename', $column)]);
            unset($columns[array_search('name', $columns)]);
        } elseif (in_array('name', $column)) {
            $data['show_name'] = 'n';
            unset($column[array_search('name', $column)]);
            unset($columns[array_search('name', $columns)]);
        } elseif (in_array('filename', $column)) {
            $data['show_name'] = 'f';
            unset($column[array_search('filename', $column)]);
            unset($columns[array_search('name', $columns)]);
        }
        $both = array_intersect($column, $popup);
        if ($column || $popup) {
            $hide = array_diff($columns, array_merge($column, $popup));
        } else {
            $hide = [];			// use defaults if nothing set
        }

        $column = array_diff($column, $both);
        $popup = array_diff($popup, $both);

        foreach ($both as $value) {
            $data["show_$value"] = 'a';
        }
        foreach ($column as $value) {
            $data["show_$value"] = 'y';
        }
        foreach ($popup as $value) {
            $data["show_$value"] = 'o';
        }
        foreach ($hide as $value) {
            $data["show_$value"] = 'n';
        }

        unset($data['popup']);
        unset($data['column']);

        $data = array_merge($defaults, $data);

        foreach ($conversions as $old => $new) {
            if (array_key_exists($old, $data)) {
                $data[$new] = $data[$old];
                unset($data[$old]);
            }
        }

        $this->replaceReferences($data);

        if (! empty($data['name'])) {
            $filegallib = TikiLib::lib('filegal');
            $data['galleryId'] = $filegallib->getGalleryId($data['name'], $data['parentId']);
        }

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['name'])) {
            return false;
        }

        return $this->convertMode($data);
    }

    private function convertMode($data)
    {
        if (! isset($data['mode'])) {
            return true; // will duplicate if already exists
        }
        switch ($data['mode']) {
            case 'update':
                if (empty($data['galleryId'])) {
                    throw new Exception(tra('File gallery does not exist') . ' ' . $data['name']);
                }

                break;
            case 'create':
                if (! empty($data['galleryId'])) {
                    throw new Exception(tra('File gallery already exists') . ' ' . $data['name']);
                }

                break;
        }

        return true;
    }

    public function _install()
    {
        $filegallib = TikiLib::lib('filegal');

        $input = $this->getData();
        $files = [];
        if (! empty($input['init_files'])) {
            $files = (array) $input['init_files'];
            unset($input['init_files']);
        }

        if (isset($input['mode'])) {
            unset($input['mode']);
        }

        $galleryId = $filegallib->replace_file_gallery($input);

        if ($galleryId && count($files)) {
            $gal_info = $filegallib->get_file_gallery_info($galleryId);

            foreach ($files as $url) {
                //Validate if its an URL
                if ($filegallib->get_info_from_url($url)) {
                    $this->upload($gal_info, $url);
                } else {
                    $this->uploadFile($gal_info, $url);
                }
            }
        }

        return $galleryId;
    }

    public function upload($gal_info, $url)
    {
        $filegallib = TikiLib::lib('filegal');
        if ($filegallib->lookup_source($url)) {
            return;
        }

        $info = $filegallib->get_info_from_url($url);

        if (! $info) {
            return;
        }

        $fileId = $filegallib->upload_single_file($gal_info, $info['name'], $info['size'], $info['type'], $info['data']);

        if ($fileId === false) {
            return;
        }

        $filegallib->attach_file_source($fileId, $url, $info);
    }

    /**
     * Upload a file, based on the profile definition
     *
     * @param $gal_info
     * @param $file
     */
    public function uploadFile($gal_info, $file)
    {
        $filegallib = TikiLib::lib('filegal');

        $data = false;
        $profile_url = $this->obj->getProfile()->getProfilePath();
        $fileUrl = $profile_url . '/' . $file['filename'];
        if (file_exists($fileUrl)) {
            $data = file_get_contents($fileUrl);
        }
        if (! $data) {
            return;
        }

        $filegallib->upload_single_file($gal_info, $file['filename'], $file['filesize'], $file['filetype'], $data);
    }

    public static function export(Tiki_Profile_Writer $writer, $galId, $withParents = false, $deep = false, $includeFiles = false)
    {
        $filegallib = TikiLib::lib('filegal');
        $info = $filegallib->get_file_gallery_info($galId);
        $default = $filegallib->default_file_gallery();

        if (! $info) {
            return false;
        }

        $out = [
            'name' => $info['name'],
            'visible' => $info['visible'],
        ];

        if ($includeFiles) {
            // Get all files from galleries
            $files = $filegallib->get_files_info_from_gallery_id($galId);

            $out['init_files'] = array_map(function ($file) {
                return [
                    '_fileId' => $file['fileId'],
                    'filename' => $file['filename'],
                    'filesize' => $file['filesize'],
                    'filetype' => $file['filetype']
                ];
            }, $files);

            // Get all files from galleries
            foreach ($files as $file) {
                $file_data = $filegallib->get_file($file['fileId']);
                if (empty($file['data'])) {
                    // get the file from the filesystem
                    $fileObject = new \Tiki\FileGallery\File($file_data);
                    $wrapper = $fileObject->getWrapper();
                    $file['data'] = $wrapper->getContents();
                }
                $writer->writeExternal($file['filename'], $file['data'], '');
            }
        }

        if ($info['parentId'] > 3) { // up to 3, standard/default galleries
            $out['parent'] = $writer->getReference('file_gallery', $info['parentId']);
        } else {
            $out['parent'] = $info['parentId'];
        }

        // Include any simple field whose value is different from the default
        $simple = ['description', 'public', 'type', 'lockable', 'archives', 'quota', 'image_max_size_x', 'image_max_size_y', 'backlinkPerms', 'wiki_syntax', 'sort_mode', 'maxRows', 'max_desc', 'subgal_conf', 'default_view', 'template'];
        foreach ($simple as $field) {
            if ($info[$field] != $default[$field]) {
                $out[$field] = $info[$field];
            }
        }

        $popup = [];
        $column = [];
        foreach ($info as $field => $value) {
            if (isset($default[$field]) && $value == $default[$field]) {
                continue; // Skip default values
            }

            if (substr($field, 0, 5) == 'show_') {
                $short = substr($field, 5);
                if ($value == 'a' || $value == 'o') {
                    $popup[] = $short;
                }
                if ($value == 'a' || $value == 'y') {
                    $column[] = $short;
                }
            }
        }

        if (! empty($popup)) {
            $out['popup'] = $popup;
        }

        if (! empty($column)) {
            $out['column'] = $column;
        }

        $writer->addObject('file_gallery', $galId, $out);

        if ($deep) {
            $table = $filegallib->table('tiki_file_galleries');
            $children = $table->fetchColumn('galleryId', [
                'parentId' => $galId,
            ]);

            foreach ($children as $id) {
                self::export($writer, $id, false, $deep);
            }
        }

        if ($withParents && $info['parentId'] > 3) {
            self::export($writer, $info['parentId'], $withParents, false);
        }


        return true;
    }

    /**
     * Remove file gallery
     *
     * @param string $fileGallery
     *
     * @throws Exception
     * @return bool
     */
    public function remove($fileGallery)
    {
        if (! empty($fileGallery)) {
            $filegallib = TikiLib::lib('filegal');
            $galleryId = $filegallib
                ->table('tiki_file_galleries')
                ->fetchOne('galleryId', ['name' => $fileGallery]);
            if (! empty($galleryId)) {
                $result = $filegallib->remove_file_gallery($galleryId);

                return $result && $result->numRows();
            }
        }

        return false;
    }

    /**
     * Get current file gallery data
     *
     * @param array $fileGallery
     * @return mixed
     */
    public function getCurrentData($fileGallery)
    {
        $fileGalleryName = ! empty($fileGallery['name']) ? $fileGallery['name'] : '';
        if (! empty($fileGalleryName)) {
            $filegallib = TikiLib::lib('filegal');
            $fileGalleryData = $filegallib->table('tiki_file_galleries')->fetchFullRow(['name' => $fileGalleryName]);
            $fileGalleryId = ! empty($fileGalleryData['galleryId']) ? $fileGalleryData['galleryId'] : 0;
            $filesInfo = $filegallib->get_files_info_from_gallery_id($fileGalleryId);
            if (! empty($filesInfo)) {
                $fileGalleryData['files'] = $filesInfo;
            }

            return $fileGalleryData;
        }

        return false;
    }
}
