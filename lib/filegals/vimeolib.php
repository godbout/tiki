<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class VimeoLib
{
    private $oauth;

    /**
     * VimeoLib constructor.
     * @param OAuthLib $oauthlib
     */
    public function __construct($oauthlib)
    {
        $this->oauth = $oauthlib;
    }

    public function isAuthorized()
    {
        return $this->oauth->is_authorized('vimeo');
    }

    /**
     * Gets array of space and uploads left for the Vimeo account
     *
     * @return array
     */
    public function getQuota()
    {
        $data = $this->callMethod('/me');

        return $data['upload_quota'];
    }

    /**
     * Gets an upload ticket
     *
     * @return array
     */
    public function getTicket()
    {
        $data = $this->callMethod(
            '/me/videos',
            ['type' => 'streaming'],
            'post'
        );

        return $data;
    }

    public function complete($completeUri)
    {
        $data = $this->callMethod(
            $completeUri,
            [],
            'delete'
        );

        return $data;
    }

    public function setTitle($videoId, $title)
    {
        $data = $this->callMethod(
            '/videos/' . $videoId,
            [
                'name' => $title,
            ],
            'patch'
        );

        return $data;
    }

    public function deleteVideo($videoId)
    {
        $data = $this->callMethod(
            '/videos/' . $videoId,
            [],
            'delete'
        );

        return $data;
    }

    private function callMethod($method, array $arguments = [], $httpmethod = 'get')
    {
        $oldVal = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $response = $this->oauth->do_request(
            'vimeo',
            [
                'url' => 'https://api.vimeo.com' . $method,
                $httpmethod => $arguments,
            ]
        );

        ini_set('arg_separator.output', $oldVal);

        if ($httpmethod == 'delete' || $httpmethod == 'patch') {
            $headers = $response->getHeaders();

            return $headers->toArray();
        }

        return json_decode($response->getBody(), true);
    }
}
