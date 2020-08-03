<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Multilingual_MachineTranslation_BingTranslateWrapper implements Multilingual_MachineTranslation_Interface
{
    const AUTH_URL = 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13';
    const TRANSLATE_URL = 'http://api.microsofttranslator.com/V2/Http.svc/Translate';

    private $clientId;
    private $clientSecret;
    private $sourceLang;
    private $targetLang;

    public function __construct($clientId, $clientSecret, $sourceLang, $targetLang)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }

    public function getSupportedLanguages()
    {
        return [
         'ar' => 'Arabic',
         'bg' => 'Bulgarian',
         'ca' => 'Catalan',
         'zh' => 'Chinese',
         'cs' => 'Czech',
         'da' => 'Danish',
         'nl' => 'Dutch',
         'en' => 'English',
         'et' => 'Estonian',
         'fi' => 'Finnish',
         'fr' => 'French',
         'de' => 'German',
         'el' => 'Greek',
         'he' => 'Hebrew',
         'hi' => 'Hindi',
         'hu' => 'Hungarian',
         'id' => 'Indonesian',
         'it' => 'Italian',
         'ja' => 'Japanese',
         'ko' => 'Korean',
         'lv' => 'Latvian',
         'lt' => 'Lithuanian',
         'no' => 'Norwegian',
         'fa' => 'Persian',
         'pl' => 'Polish',
         'pt' => 'Portuguese',
         'ro' => 'Romanian',
         'ru' => 'Russian',
         'sk' => 'Slovak',
         'sl' => 'Slovenian',
         'es' => 'Spanish',
         'sv' => 'Swedish',
         'th' => 'Thai',
         'tr' => 'Turkish',
         'uk' => 'Ukrainian',
         'vi' => 'Vietnamese',
        ];
    }

    public function translateText($html)
    {
        return $this->getTranslationFromBing($html);
    }


    private function getTranslationFromBing($html)
    {
        try {
            $access = $this->getAccessToken();
            $params = [
                'appId' => '',
                'to' => $this->targetLang,
                'text' => $html,
            ];

            if ($this->sourceLang != Multilingual_MachineTranslation::DETECT_LANGUAGE) {
                $params['from'] = $this->sourceLang;
            }

            $dom = $this->performRequest(self::TRANSLATE_URL, $access, $params);

            return $dom->documentElement->textContent;
        } catch (Exception $e) {
            // Mostly netork errors, ignore
            return $html;
        }
    }

    private function performRequest($url, $access, $data)
    {
        $tikilib = TikiLib::lib('tiki');
        $url = $url . '?' . http_build_query($data, '', '&');

        $client = $tikilib->get_http_client();
        $client->setHeaders(['Authorization' => "Bearer $access"]);
        $client->setUri($url);
        $response = $client->send();
        $xml = $response->getBody();

        $dom = new DOMDocument;
        $dom->loadXML($xml);

        return $dom;
    }

    private function getAccessToken()
    {
        $tikilib = TikiLib::lib('tiki');

        if (isset($_SESSION['bing_translate_access'])) {
            $entry = $_SESSION['bing_translate_access'];
            if ($entry['expiry'] <= $tikilib->now) {
                unset($_SESSION['bing_translate_access']);

                return $this->obtainAccessToken();
            }

            return $entry['token'];
        }

        return $this->obtainAccessToken();
    }

    private function obtainAccessToken()
    {
        $tikilib = TikiLib::lib('tiki');
        $client = $tikilib->get_http_client();
        $client->setUri(self::AUTH_URL);
        $client->getRequest()->getPost()->set('client_id', $this->clientId);
        $client->getRequest()->getPost()->set('client_secret', $this->clientSecret);
        $client->getRequest()->getPost()->set('scope', 'http://api.microsofttranslator.com');
        $client->getRequest()->getPost()->set('grant_type', 'client_credentials');

        $client->setMethod(Laminas\Http\Request::METHOD_POST);
        $response = $client->send();

        $data = json_decode($response->getBody());

        $_SESSION['bing_translate_access'] = [
            'token' => $data->access_token,
            'expiry' => $tikilib->now + $data->expires_in,
        ];

        return $data->access_token;
    }
}
