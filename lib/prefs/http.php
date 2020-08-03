<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_http_list()
{
    return [
        'http_port' => [
            'name' => tra('HTTP port'),
            'description' => tra('The port used to access this server; if not specified, port 80 will be used'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'default' => '',
            'shorthint' => tra('If not specified, port 80 will be used'),
        ],
        'http_skip_frameset' => [
            'name' => tra('HTTP lookup: skip framesets'),
            'description' => tra('When performing an HTTP request to an external source, verify if the result is a frameset and use heuristic to provide the real content.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'http_referer_registration_check' => [
            'name' => tra('Registration referrer check'),
            'description' => tra('Use the HTTP referrer to check registration POST is sent from same host. (May not work on some setups.)'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'http_header_frame_options' => [
            'name' => tra('HTTP header x-frame options'),
            'description' => tra('The x-frame-options HTTP response header can be used to indicate whether or not a browser should be allowed to render a page in a &lt;frame&gt;, &lt;iframe&gt; or &lt;object&gt;'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_frame_options_value' => [
            'name' => tra('Header value'),
            'type' => 'list',
            'options' => [
                'DENY' => tra('DENY'),
                'SAMEORIGIN' => tra('SAMEORIGIN'),
            ],
            'default' => 'DENY',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_frame_options',
            ],
        ],
        'http_header_xss_protection' => [
            'name' => tra('HTTP header x-xss-protection'),
            'description' => tra('The x-xss-protection header is designed to enable the cross-site scripting (XSS) filter built into modern web browsers'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_xss_protection_value' => [
            'name' => tra('Header value'),
            'type' => 'list',
            'options' => [
                '0' => '0',
                '1' => '1',
                '1;mode=block' => tra('1;mode=block'),
            ],
            'default' => '1;mode=block',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_xss_protection',
            ],
        ],
        'http_header_content_type_options' => [
            'name' => tra('HTTP header x-content-type-options'),
            'description' => tra('The x-content-type-options header is a marker used by the server to indicate that the MIME types advertised in the Content-Type headers should not be changed and be followed.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_content_security_policy' => [
            'name' => tra('HTTP header content-security-policy'),
            'description' => tra('The Content-Security-Policy header allows web site administrators to control resources the user agent is allowed to load for a given page.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_content_security_policy_value' => [
            'name' => tra('Header value'),
            'type' => 'text',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_content_security_policy',
            ],
        ],
        'http_header_strict_transport_security' => [
            'name' => tra('HTTP header strict-transport-security'),
            'description' => tra('The Strict-Transport-Security header (often abbreviated as HSTS) is a security feature that lets a web site tell browsers that it should only be communicated with using HTTPS, instead of using HTTP.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_strict_transport_security_value' => [
            'name' => tra('Header value'),
            'type' => 'text',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_strict_transport_security',
            ],
        ],
        'http_header_public_key_pins' => [
            'name' => tra('HTTP header public-key-pins'),
            'description' => tra('The public-key-pins header associates a specific cryptographic public key with a certain web server to decrease the risk of MITM attacks with forged certificates. If one or several keys are pinned and none of them are used by the server, the browser will not accept the response as legitimate, and will not display it.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_public_key_pins_value' => [
            'name' => tra('Header value'),
            'type' => 'textarea',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_public_key_pins',
            ],
        ],
    ];
}
