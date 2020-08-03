<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_zend_list()
{
    return [
        'zend_mail_smtp_server' => [
            'name' => tra('SMTP server'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
        ],
        'zend_mail_smtp_user' => [
            'name' => tra('Username'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
        ],
        'zend_mail_smtp_pass' => [
            'name' => tra('Password'),
            'type' => 'password',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
        ],
        'zend_mail_smtp_port' => [
            'name' => tra('Port'),
            'type' => 'text',
            'size' => '5',
            'perspective' => false,
            'default' => 25,
        ],
        'zend_mail_smtp_security' => [
            'name' => tra('Security'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                '' => tra('None'),
                'ssl' => tra('SSL'),
                'tls' => tra('TLS'),
            ],
            'default' => '',
        ],
        'zend_mail_handler' => [
            'name' => tra('Mail sender'),
            'description' => tra('Specify if Tiki should use Sendmail(the PHP mail() function), SMTP or File (Debug) (to debug email sending by means of storing emails as files on disk at ./temp/Mail_yyyymmddhhmmss_randomstring.tmp ) to send mail notifications.'),
            'type' => 'list',
            'options' => [
                'sendmail' => tra('Sendmail'),
                'smtp' => tra('SMTP'),
                'file' => tra('File (debug)'),
            ],
            'default' => 'sendmail',
        ],
        'zend_mail_smtp_auth' => [
            'name' => tra('Authentication'),
            'description' => tra('Mail server authentication'),
            'type' => 'list',
            'options' => [
                '' => tra('None'),
                'login' => tra('LOGIN'),
                'plain' => tra('PLAIN'),
                'crammd5' => tra('CRAM-MD5'),
            ],
            'default' => '',
        ],
        'zend_mail_smtp_helo' => [
            'name' => tra('Local server name'),
            'description' => tra('Name of the local server. Will be reported to SMTP relay on the HELO/EHLO line.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => 'localhost',
        ],
        'zend_mail_queue' => [
            'name' => tra('Mail delivery'),
            'description' => tr(
                'When set to Queue, messages will be stored in the database. Requires using the shell script %0 to be run for actual delivery. Only works with SMTP mail.',
                '<code>php console.php mail-queue:send</code>'
            ),
            'type' => 'list',
            'options' => [
                '' => tra('Send immediately'),
                'y' => tra('Queue')
            ],
            'default' => '',
        ],
        'zend_http_sslverifypeer' => [
            'name' => tra('Verify HTTPS certificates of remote servers'),
            'description' => tra('When set to enforce, the server will fail to connect over HTTPS to a remote server that do not have a SSL certificate that is valid and can be verified against the local list of Certificate Authority (CA)'),
            'type' => 'list',
            'options' => [
                '' => tra('Do not enforce verification'),
                'y' => tra('Enforce verification'),
            ],
            'default' => '',
        ],
        'zend_http_use_curl' => [
            'name' => tra('Use CURL for HTTP connections'),
            'description' => tra(
                'Use CURL instead of sockets for server to server HTTP connections, when sockets are not available.'
            ),
            'type' => 'flag',
            'default' => 'n',
            'extensions' => ['curl'],
        ],
        'zend_mail_redirect' => [
            'name' => tra('Catch-all email address'),
            'description' => tra('Tiki will send all emails to this email address instead of the target recipients. This will actually rewrite the recipient TO, CC and BCC email headers.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
        ],
    ];
}
