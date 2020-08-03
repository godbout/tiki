<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_subscribenewsletter_info()
{
    return [
        'name' => tra('Subscribe to newsletter'),
        'documentation' => 'PluginSubscribeNewsletter',
        'description' => tra('Allow users to subscribe to a newsletter'),
        'prefs' => ['feature_newsletters', 'wikiplugin_subscribenewsletter'],
        'body' => tra('Invitation message'),
        'iconname' => 'articles',
        'introduced' => 5,
        'tags' => [ 'basic' ],
        'params' => [
            'nlId' => [
                'required' => true,
                'name' => tra('Newsletter ID'),
                'description' => tra('Identification number of the Newsletter that you want to allow the users to subscribe to'),
                'since' => '5.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'newsletter',
            ],
            'thanks' => [
                'required' => false,
                'name' => tra('Confirmation Message'),
                'description' => tra('Confirmation message after posting form. The plugin body is then the button label.'),
                'since' => '5.0',
                'filter' => 'wikicontent',
            ],
            'button' => [
                'required' => false,
                'name' => tra('Button'),
                'description' => tra('Button label. The plugin body is then the confirmation message'),
                'since' => '5.0',
                'filter' => 'wikicontent',
            ],
            'wikisyntax' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Wiki Syntax'),
                'description' => tra('Choose whether the output should be parsed as wiki syntax'),
                'since' => '6.0',
                'filter' => 'int',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'inmodule' => [
                'required' => false,
                'name' => tra('In Module'),
                'description' => tra('Display the newsletter subscription form in module view (if included in a Tiki module)'),
                'since' => '19.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'usecaptcha' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Use captcha'),
                'description' => tra('Captcha verification for anonymous visitors (yes by default). Turning off this option may lead to have this newsletter list filled by Spambot'),
                'since' => '22.0',
                'filter' => 'int',
                'default' => 1,
                'options' => [
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
        ],
    ];
}
function wikiplugin_subscribenewsletter($data, $params)
{
    global $prefs, $user;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    global $nllib;
    include_once('lib/newsletters/nllib.php');
    extract($params, EXTR_SKIP);
    if ($prefs['feature_newsletters'] != 'y') {
        return tra('Feature disabled');
    }
    if (empty($nlId)) {
        return tra('Incorrect param');
    }
    $info = $nllib->get_newsletter($nlId);
    if (empty($info) || $info['allowUserSub'] != 'y') {
        return tra('Incorrect param');
    }

    if (! $userlib->user_has_perm_on_object($user, $nlId, 'newsletter', 'tiki_p_subscribe_newsletters')) {
        return;
    }

    if ($user) {
        $alls = $nllib->get_all_subscribers($nlId, false);
        foreach ($alls as $all) {
            if (strtolower($all['db_email']) == strtolower($user)) {
                return;
            }
        }
    }

    if ($prefs['feature_jquery_validation'] === 'y') {
        $js = '
			$("form[name=wpSubscribeNL]").validate({
				rules: {
					wpEmail: {
						required: true,
						email: true,
					},
				},
				submitHandler: function(form, event){return process_submit(form, event);}
			});
		';
        TikiLib::lib('header')->add_jq_onready($js);
    }

    $wpSubscribe = '';
    $wpError = '';
    $subscribeEmail = '';
    $useCaptcha = $params['usecaptcha'];
    if ($params['usecaptcha'] !== 0) {	// To keep previous behaviour with previous versions where the parameter doesn't exist
        $useCaptcha = 1;
    }
    if (isset($_REQUEST['wpSubscribe']) && $_REQUEST['wpNlId'] == $nlId) {
        $captchalib = TikiLib::lib('captcha');
        if ($useCaptcha != 0 && ! $user && $prefs['feature_antibot'] == 'y' && ! $captchalib->validate()) {
            $wpError = $captchalib->getErrors();
        } elseif (! $user && empty($_REQUEST['wpEmail'])) {
            $wpError = tra('Invalid Email');
        } elseif (! $user && ! validate_email($_REQUEST['wpEmail'], $prefs['validateEmail'])) {
            $wpError = tra('Invalid Email');
            $subscribeEmail = $_REQUEST['wpEmail'];
        } elseif (($user && $nllib->newsletter_subscribe($nlId, $user, 'y', 'n'))
            || (! $user && $nllib->newsletter_subscribe($nlId, $_REQUEST['wpEmail'], 'n', $info['validateAddr']))) {
            $wpSubscribe = 'y';
            $smarty->assign('subscribeThanks', empty($thanks) ? $data : $thanks);
        } else {
            $wpError = tra('Already subscribed');
        }
    }
    $smarty->assign_by_ref('wpSubscribe', $wpSubscribe);
    $smarty->assign_by_ref('wpError', $wpError);
    $smarty->assign('subscribeEmail', $subscribeEmail);
    $smarty->assign('subcribeMessage', empty($button) ? $data : $button);
    $smarty->assign('inmodule', !empty($inmodule) ? "moduleSubscribeNL" : "");
    $smarty->assign_by_ref('subscribeInfo', $info);
    $smarty->assign('useCaptcha', $useCaptcha);
    $res = $smarty->fetch('wiki-plugins/wikiplugin_subscribenewsletter.tpl');
    if (isset($params["wikisyntax"]) && $params["wikisyntax"] == 1) {
        return $res;
    }   		// if wikisyntax != 1 : no parsing of any wiki syntax

    return '~np~' . $res . '~/np~';
}
