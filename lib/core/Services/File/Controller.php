<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_File_Controller
{
    private $defaultGalleryId = 1;
    /**
     * @var Services_File_Utilities $utilities
     */
    private $utilities;

    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Services_Exception_Disabled('feature_file_galleries');
        }
        $this->defaultGalleryId = $prefs['fgal_root_id'];
        $this->utilities = new Services_File_Utilities;
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'file_galleries';
    }

    /**
     * Call to prepare the upload in modal dialog, and then after the upload has happened
     * Here we add a description if that's enabled
     *
     * @param JitFilter $input
     * @throws Exception
     * @return array
     */
    public function action_uploader($input)
    {
        $gal_info = $this->checkTargetGallery($input);
        $filegallib = TikiLib::lib('filegal');

        $perms = Perms::get('tracker', $input->trackerId->int());

        $util = new Services_Utilities();
        if ($util->isActionPost()) {
            if ($input->offsetExists('description')) {
                $files = $input->asArray('file');
                $descriptions = $input->asArray('description');

                foreach ($files as $c => $file) {
                    $fileInfo = $filegallib->get_file_info($file);

                    if (isset($descriptions[$c])) {
                        $filegallib->update_file($fileInfo['fileId'], [
                            'name' => $fileInfo['filename'],
                            'description' => $descriptions[$c],
                            'lastModifUser' => $fileInfo['asuser'],
                        ]);
                    }
                }
            }
        }

        return [
            'title' => tr('File Upload'),
            'galleryId' => $gal_info['galleryId'],
            'limit' => abs($input->limit->int()),
            'typeFilter' => $input->type->text(),
            'uploadInModal' => $input->uploadInModal->int(),
            'files' => $this->getFilesInfo((array) $input->file->int()),
            'image_max_size_x' => $input->image_max_size_x->text(),
            'image_max_size_y' => $input->image_max_size_y->text(),
            'addDecriptionOnUpload' => $input->addDecriptionOnUpload->int(),
            'admin_trackers' => $perms->admin_trackers,
            'requireTitle' => $input->requireTitle->text(),
        ];
    }

    public function action_upload($input)
    {
        if ($input->files->asArray()) {
            return [];
        }

        $gal_info = $this->checkTargetGallery($input);

        $fileId = $input->fileId->int();
        $asuser = $input->user->text();
        $title = $input->title->text();

        if (empty($asuser)) {
            $asuser = $GLOBALS['user'];
        }
        if (! $input->imagesize->word()) {
            $image_x = $input->image_max_size_x->text();
            $image_y = $input->image_max_size_y->text();
        } else {
            $image_x = $gal_info["image_max_size_x"];
            $image_y = $gal_info["image_max_size_y"];
        }
        if (isset($_FILES['data'])) {
            // used by $this->action_upload_multiple and file gallery Files fields (possibly others)
            if (is_uploaded_file($_FILES['data']['tmp_name'])) {
                $file = new JitFilter($_FILES['data']);
                $name = $file->name->text();
                $size = $file->size->int();
                $type = $file->type->text();

                $data = file_get_contents($_FILES['data']['tmp_name']);
            } else {
                $message = tr('File could not be uploaded:') . ' ';

                switch ($_FILES['data']['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message .= tr('No file arrived');

                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $message .= tr('File too large');

                        break;
                    default:
                        $message .= tr('Unknown errors');

                        break;
                }

                throw new Services_Exception_NotAvailable(tr($message));
            }
        } else {
            $name = $input->name->text();
            $size = $input->size->int();
            $type = $input->type->text();

            $data = $input->data->none();
            $data = base64_decode($data);
        }
        if (!$title) {
            $title = $name;
        }


        /* The above if/else sets $type using finfo_file(). The following uses finfo_buffer(), which gives a type different from that obtained from finfo_file() in the case of Outlook .msg files on PHP 5.6. In this case, finfo_file()'s result is better. It is not impossible that the technique below would give better results in other cases.
        See https://stackoverflow.com/questions/45243973/fileinfo-finfo-buffer-results-differ-from-finfo-file
        Chealer 2017-07-21
        $mimelib = TikiLib::lib('mime');
        $type = $mimelib->from_content($name, $data);
        */

        if (empty($name) || $size == 0 || empty($data)) {
            $message = tr('File could not be uploaded:') . ' ';
            $error = error_get_last();

            if (empty($error)) {
                $message .= tr('File empty');
            } else {
                $message = $error['message'];
            }

            throw new Services_Exception(tr($message), 406);
        }
        $util = new Services_Utilities();
        if ($util->isActionPost()) {
            if ($fileId) {
                $this->utilities->updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser, $title);
            } else {
                $fileId = $this->utilities->uploadFile($gal_info, $name, $size, $type, $data, $asuser, $image_x, $image_y, '', '', $title);
            }
        } else {
            $fileId = false;
        }

        if ($fileId === false) {
            throw new Services_Exception(tr('File could not be uploaded'), 406);
        }

        $cat_type = 'file';
        $cat_objid = $fileId;
        $cat_desc = null;
        $cat_name = $name;
        $cat_href = "tiki-download_file.php?fileId=$fileId";
        include('categorize.php');

        $util->setTicket();

        return [
            'size' => $size,
            'name' => $name,
            'title' => $title,
            'type' => $type,
            'fileId' => $fileId,
            'galleryId' => $gal_info['galleryId'],
            'md5sum' => md5($data),
            'ticket' => $util->getTicket()
        ];
    }

    /**
     * Uploads several files at once, currently from jquery_upload when file_galleries_use_jquery_upload pref is enabled
     *
     * @param JitFilter $input
     * @throws Services_Exception
     * @throws Services_Exception_NotAvailable
     * @return array
     */
    public function action_upload_multiple($input)
    {
        global $user;
        $filegallib = TikiLib::lib('filegal');
        $output = ['files' => []];
        $util = new Services_Utilities();

        if (isset($_FILES['files']) && is_array($_FILES['files']['tmp_name']) && $util->checkCsrf()) {
            // a few other params that are still arrays but shouldn't be (mostly)
            if (is_array($input->galleryId->asArray())) {
                $input->offsetSet('galleryId', $input->asArray('galleryId')[0]);
            }
            if (is_array($input->hit_limit->asArray())) {
                $input->offsetSet('hit_limit', $input->asArray('hit_limit')[0]);
            }
            if (is_array($input->isbatch->asArray())) {
                $input->offsetSet('isbatch', $input->asArray('isbatch')[0]);
            }
            if (is_array($input->deleteAfter->asArray())) {
                $input->offsetSet('deleteAfter', $input->asArray('deleteAfter')[0]);
            }
            if (is_array($input->deleteAfter_unit->asArray())) {
                $input->offsetSet('deleteAfter_unit', $input->asArray('deleteAfter_unit')[0]);
            }
            if (is_array($input->author->asArray())) {
                $input->offsetSet('author', $input->asArray('author')[0]);
            }
            if (is_array($input->user->asArray())) {
                $input->offsetSet('user', $input->asArray('user')[0]);
            }
            if (is_array($input->listtoalert->asArray())) {
                $input->offsetSet('listtoalert', $input->asArray('listtoalert')[0]);
            }

            for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {
                //if the file is an image and it contains a header 'Orientation'
                // we check that it has a good orientation otherwise we rotate the image
                if (extension_loaded('exif') && extension_loaded('gd')) {
                    if (strtolower(substr($_FILES['files']['type'][$i], 0, 5)) == 'image') {
                        $filePath = $_FILES['files']['tmp_name'][$i];
                        $exif = exif_read_data($_FILES['files']['tmp_name'][$i]);
                        if (! empty($exif['Orientation'])) {
                            $imageResource = imagecreatefromjpeg($filePath);
                            switch ($exif['Orientation']) {
                                case 3:
                                    $image = imagerotate($imageResource, 180, 0);

                                    break;
                                case 6:
                                    $image = imagerotate($imageResource, -90, 0);

                                    break;
                                case 8:
                                    $image = imagerotate($imageResource, 90, 0);

                                    break;
                                default:
                                    $image = $imageResource;
                            }
                            imagejpeg($image, $filePath, 90);
                        }
                    }
                }
                if (is_uploaded_file($_FILES['files']['tmp_name'][$i])) {
                    $_FILES['data']['name'] = $_FILES['files']['name'][$i];
                    $_FILES['data']['size'] = $_FILES['files']['size'][$i];
                    $_FILES['data']['type'] = $_FILES['files']['type'][$i];
                    $_FILES['data']['tmp_name'] = $_FILES['files']['tmp_name'][$i];

                    // do the actual upload
                    $file = $this->action_upload($input);

                    if (! empty($file['fileId'])) {
                        $file['info'] = $filegallib->get_file_info($file['fileId']);
                        // when stored in the database the file contents is here and should not be sent back to the client
                        $file['info']['data'] = null;
                        $file['syntax'] = $filegallib->getWikiSyntax($file['galleryId'], $file['info'], $input->asArray());
                    }

                    if ($input->isbatch->word() && stripos($_FILES['data']['type'], 'zip') !== false) {
                        $errors = [];
                        $perms = Perms::get(['type' => 'file', 'object' => $file['fileId']]);
                        if ($perms->batch_upload_files) {
                            try {
                                $filegallib->process_batch_file_upload(
                                    $file['galleryId'],
                                    $_FILES['files']['tmp_name'][$i],
                                    $user,
                                    '',
                                    $errors
                                );
                            } catch (Exception $e) {
                                Feedback::error($e->getMessage());
                            }
                            if ($errors) {
                                Feedback::error(['mes' => $errors]);
                            } else {
                                $file['syntax'] = tr('Batch file processed: "%0"', $file['name']);	// cheeky?
                            }
                        } else {
                            Feedback::error(tra('You don\'t have permission to upload zipped file packages'));
                        }
                    }


                    $output['files'][] = $file;
                } else {
                    throw new Services_Exception_NotAvailable(tr('File could not be uploaded.'));
                }
            }

            if ($input->autoupload->word()) {
                TikiLib::lib('user')->set_user_preference($user, 'filegals_autoupload', 'y');
            } else {
                TikiLib::lib('user')->set_user_preference($user, 'filegals_autoupload', 'n');
            }
        } else {
            throw new Services_Exception_NotAvailable(tr('File could not be uploaded.'));
        }
        $util->setTicket();
        $output['ticket'] = $util->getTicket();

        return $output;
    }

    public function action_browse($input)
    {
        try {
            $gal_info = $this->checkTargetGallery($input);
        } catch (Services_Exception $e) {
            $gal_info = null;
        }
        $input->replaceFilter('file', 'int');
        $type = $input->type->text();

        return [
            'title' => tr('Browse'),
            'galleryId' => $input->galleryId->int(),
            'limit' => $input->limit->int(),
            'files' => $this->getFilesInfo($input->asArray('file', ',')),
            'typeFilter' => $type,
            'canUpload' => (bool) $gal_info,
            'list_view' => (substr($type, 0, 6) == 'image/') ? 'thumbnail_gallery' : 'list_gallery',
        ];
    }

    public function action_thumbnail_gallery($input)
    {
        // Same as list gallery, different template
        return $this->action_list_gallery($input);
    }

    public function action_list_gallery($input)
    {
        $galleryId = $input->galleryId->int();

        /** @var UnifiedSearchLib $lib */
        $lib = TikiLib::lib('unifiedsearch');
        $query = $lib->buildQuery([
            'type' => 'file',
            'gallery_id' => (string) $galleryId,
        ]);

        if ($search = $input->search->text()) {
            $query->filterContent($search);
        }

        if ($typeFilter = $input->type->text()) {
            $query->filterContent($typeFilter, 'filetype');
        }

        $query->setRange($input->offset->int());
        $query->setOrder('title_asc');
        $result = $query->search($lib->getIndex());

        return [
            'title' => tr('Gallery List'),
            'galleryId' => $galleryId,
            'results' => $result,
            'plain' => $input->plain->int(),
            'search' => $search,
            'typeFilter' => $typeFilter,
        ];
    }

    public function action_remote($input)
    {
        global $prefs;
        if ($prefs['fgal_upload_from_source'] != 'y') {
            throw new Services_Exception(tr('Upload from source disabled.'), 403);
        }

        $gal_info = $this->checkTargetGallery($input);
        $url = $input->url->url();

        if (! $url) {
            return [
                'galleryId' => $gal_info['galleryId'],
            ];
        }

        $filegallib = TikiLib::lib('filegal');

        if ($file = $filegallib->lookup_source($url)) {
            return $file;
        }

        $info = $filegallib->get_info_from_url($url);

        if (! $info) {
            throw new Services_Exception(tr('Unable to retrieve file from remote site. Please try a different URL.'), 412);
        }

        if ($input->reference->int()) {
            $info['data'] = 'REFERENCE';
        }

        $fileId = $this->utilities->uploadFile($gal_info, $info['name'], $info['size'], $info['type'], $info['data']);

        if ($fileId === false) {
            throw new Services_Exception(tr('File could not be uploaded. Restrictions apply.'), 406);
        }

        $filegallib->attach_file_source($fileId, $url, $info, $input->reference->int());

        return [
            'size' => $info['size'],
            'name' => $info['name'],
            'type' => $info['type'],
            'fileId' => $fileId,
            'galleryId' => $gal_info['galleryId'],
            'md5sum' => md5($info['data']),
        ];
    }

    public function action_refresh($input)
    {
        global $prefs;
        if ($prefs['fgal_upload_from_source'] != 'y') {
            throw new Services_Exception(tr('Upload from source disabled.'), 403);
        }

        if ($prefs['fgal_source_show_refresh'] != 'y') {
            throw new Services_Exception(tr('Manual refresh disabled.'), 403);
        }

        $filegallib = TikiLib::lib('filegal');
        $ret = $filegallib->refresh_file($input->fileId->int());

        return [
            'success' => $ret,
        ];
    }

    /**
     * @param $input	string "name" for the filename to find
     * @return array	file info for most recent file by that name
     */
    public function action_find($input)
    {
        $filegallib = TikiLib::lib('filegal');
        $gal_info = $this->checkTargetGallery($input);

        $name = $input->name->text();

        $pos = strpos($name, '?');		// strip off get params
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }

        $info = $filegallib->get_file_by_name($gal_info['galleryId'], $name);

        if (empty($info)) {
            $info = $filegallib->get_file_by_name($gal_info['galleryId'], $name, 'filename');
        }
        unset($info['data']);

        return $info;
    }

    private function checkTargetGallery($input)
    {
        $galleryId = $input->galleryId->int() ?: $this->defaultGalleryId;

        // Patch for uninitialized utilities.
        //	The real problem is that setup is not called
        if ($this->utilities == null) {
            $this->utilities = new Services_File_Utilities;
        }

        return $this->utilities->checkTargetGallery($galleryId);
    }

    private function getFilesInfo($files)
    {
        return array_map(function ($fileId) {
            return TikiDb::get()->table('tiki_files')->fetchRow(['fileId', 'name' => 'filename', 'label' => 'name', 'type' => 'filetype'], ['fileId' => $fileId]);
        }, array_filter($files));
    }
}
