<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class OAuthLib extends TikiDb_Bridge
{
    public function is_authorized($provider_key)
    {
        return ! is_null($this->retrieve_token($provider_key));
    }

    public function do_request($provider_key, $arguments)
    {
        $configuration = $this->get_configuration($provider_key);

        if (! $configuration) {
            return false;
        }

        $access = $this->retrieve_token($provider_key);

        if (is_object($access)) {
            $client = $access->getHttpClient($configuration);

            if (isset($configuration['secretAsGet'])) {
                $client->getRequest()->getQuery()->set($configuration['secretAsGet'], $access->getTokenSecret());
            }
        } else {
            $client = TikiLib::lib('tiki')->get_http_client();
        }

        $client->setUri($arguments['url']);

        if (isset($configuration['oauth2Token'])) {
            $client->getRequest()->getQuery()->set('access_token', $configuration['oauth2Token']);
        }

        if (isset($arguments['post'])) {
            $client->setMethod(Laminas\Http\Request::METHOD_POST);
            foreach ($arguments['post'] as $key => $value) {
                $client->getRequest()->getPost()->set($key, $value);
            }
        }

        if (isset($arguments['patch'])) {
            $client->setMethod(Laminas\Http\Request::METHOD_PATCH);
            foreach ($arguments['patch'] as $key => $value) {
                $client->getRequest()->getPost()->set($key, $value);
            }
        }

        if (isset($arguments['get'])) {
            foreach ($arguments['get'] as $key => $value) {
                $client->getRequest()->getQuery()->set($key, $value);
            }
        }

        if (isset($arguments['delete'])) {
            $client->setMethod(Laminas\Http\Request::METHOD_DELETE);
        }

        try {
            $response = $client->send();

            return $response;
        } catch (Laminas\Http\Exception\ExceptionInterface $e) {
            return null;
        }
    }

    public function request_token($provider_key)
    {
        try {
            $consumer = $this->get_consumer($provider_key);

            if ($consumer) {
                $_SESSION['OAUTH_REQUEST_' . $provider_key] = serialize($consumer->getRequestToken());
                $consumer->redirect();
            }
        } catch (Laminas\Oauth\Exception\ExceptionInterface $e) {
            $oauth_ex = $e->getPrevious();
            $prevErr = '';
            if ($oauth_ex != null) {
                $prevErr = $oauth_ex->getMessage();
            }
            die($e->getMessage() . '. Origin: ' . $prevErr);
        }
    }

    public function request_access($provider_key)
    {
        $consumer = $this->get_consumer($provider_key);
        $key = 'OAUTH_REQUEST_' . $provider_key;

        if ($consumer && isset($_SESSION[$key])) {
            try {
                $accessToken = $consumer->getAccessToken($_GET, unserialize($_SESSION[$key]));

                $this->store_token($provider_key, $accessToken);

                unset($_SESSION[$key]);
            } catch (Laminas\OAuth\Exception\ExceptionInterface $e) {
                $oauth_ex = $e->getPrevious();
                $prevErr = '';
                if ($oauth_ex != null) {
                    $prevErr = $oauth_ex->getMessage();
                }
                die($e->getMessage() . '. Origin: ' . $prevErr);
            }
        }
    }

    private function store_token($provider_key, $accessToken)
    {
        $tikilib = TikiLib::lib('tiki');

        $tikilib->set_preference('oauth_token_' . $provider_key, serialize($accessToken));
    }

    private function retrieve_token($provider_key)
    {
        $config = $this->get_configuration($provider_key);

        if (! empty($config['oauth2Token'])) {
            return $config['oauth2Token'];
        }

        if (! empty($config['accessToken']) && ! empty($config['accessTokenSecret'])) {
            $token = new Laminas\OAuth\Token\Access();
            $token->setParams(
                [
                    'oauth_token' => $config['accessToken'],
                    'oauth_token_secret' => $config['accessTokenSecret'],
                ]
            );

            return $token;
        }
        $tikilib = TikiLib::lib('tiki');

        $token = $tikilib->get_preference('oauth_token_' . $provider_key);

        return $token ? unserialize($token) : null;
    }

    private function get_configuration($provider_key)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $servicelib = TikiLib::lib('service');
        $callback = $servicelib->getUrl(
            [
                'controller' => 'oauth',
                'action' => 'callback',
                'oauth_callback' => $provider_key,
            ]
        );

        switch ($provider_key) {
            case 'vimeo':
                return [
                'callbackUrl' => $tikilib->tikiUrl($callback),
                'siteUrl' => 'https://api.vimeo.com/oauth',
                'requestTokenUrl' => 'https://api.vimeo.com/oauth/request_token',
                'accessTokenUrl' => 'https://api.vimeo.com/oauth/access_token',
                'authorizeUrl' => 'https://api.vimeo.com/oauth/authorize',
                'consumerKey' => $prefs['vimeo_consumer_key'],
                'consumerSecret' => $prefs['vimeo_consumer_secret'],
                'oauth2Token' => $prefs['vimeo_access_token'],
                ];
            case 'zotero':
                return [
                'callbackUrl' => $tikilib->tikiUrl($callback),
                'siteUrl' => 'https://www.zotero.org/oauth',
                'requestTokenUrl' => 'https://www.zotero.org/oauth/request',
                'accessTokenUrl' => 'https://www.zotero.org/oauth/access',
                'authorizeUrl' => 'https://www.zotero.org/oauth/authorize',
                'consumerKey' => $prefs['zotero_client_key'],
                'consumerSecret' => $prefs['zotero_client_secret'],
                'secretAsGet' => 'key', // Tiki-specific
                ];
        }
    }

    private function get_consumer($provider_key)
    {
        if ($configuration = $this->get_configuration($provider_key)) {
            $consumer = new Laminas\OAuth\Consumer($configuration);
            $httpClient = TikiLib::lib('tiki')->get_http_client();
            $consumer->setHttpClient($httpClient);

            return $consumer;
        }
    }
}
