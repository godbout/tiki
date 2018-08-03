<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_together_info()
{
	return [
		'name' => tra('Together'),
		'documentation' => 'PluginTogether',
		'description' => tra('Collaborate in real time'),
		'iconname' => 'group',
		'introduced' => 12,
		'prefs' => [ 'wikiplugin_together' ],
		'additional' => tra('A service for collaborating on your website in real-time. TogetherJS lets users communicate,
			co-author, co-browse and guide each other. TogetherJS is implemented in Javascript; no software or plugins
			to install, and it is friendly with existing web pages, while still letting developers customize the
			experience.') . " " . tra("Note: TogetherJS is alpha-quality software. We do not recommend using it in
			production at this time."),
		'params' => [
			'buttonname' => [
				'required' => false,
				'name' => tra('Button Name'),
				'description' => tra('Set the button name. Default is CoWrite with TogetherJS'),
				'since' => '12.0',
				'filter' => 'text',
				'default' => tra('CoWrite with TogetherJS')
			],
		]
	];
}

function wikiplugin_together($data, $params)
{

	if (! isset($params['buttonname'])) {
		$params['buttonname'] = tra('CoWrite with TogetherJS');
	}
	TikiLib::lib('header')->add_jq_onready('
if(! window.startTogetherJS) {
	window.startTogetherJS = function() {
		var script = document.createElement("script");
		script.src = "https://togetherjs.com/togetherjs-min.js";
		script.async = false;
		document.getElementsByTagName("head")[0].appendChild(script);
		window.waitForTogetherJS();
	}
	window.waitForTogetherJS = function() {
		if(typeof TogetherJS === "undefined") {
			setTimeout(window.waitForTogetherJS, 300);
		} else {
			TogetherJS.config("getUserName", function () {
				return jqueryTiki.userRealName || jqueryTiki.username;
			});
			TogetherJS.config("getUserAvatar", function () {
				return jqueryTiki.userAvatar;
			});
			TogetherJS();
			waitForTogetherJSsession();
		}
	}
	window.waitForTogetherJSsession = function() {
		if(!TogetherJS.require || !(session = TogetherJS.require("session")) || !session.shareId) {
			setTimeout(window.waitForTogetherJSsession, 300);
		} else {
			if(m = window.location.href.match(/tiki-editpage.php\?page=([^&#]+)/)) {
				$.ajax({
					url: "tiki-ajax_services.php",
					dataType: "json",
					data: {
						controller: "edit_semaphore",
						action: "set",
						object_id: "togetherjs "+decodeURIComponent(m[1].replace(/\+/g, "%20")),
						value: session.shareId
					}
				});
			}
		}
	}
}

if(m = window.location.href.match(/tiki-editpage.php\?page=([^&#]+)/)) {
	$.ajax({
		url: "tiki-ajax_services.php",
		dataType: "json",
		data: {
			controller: "edit_semaphore",
			action: "get_value",
			object_id: "togetherjs "+decodeURIComponent(m[1].replace(/\+/g, "%20")),
		},
		success: function(data) {
			if(data) {
				sessionStorage.removeItem("togetherjs-session.status");
				window.location.hash = "&togetherjs="+data;
				startTogetherJS();
			}
		}
	});
}
		');

	return '<button onclick="window.startTogetherJS(this); return false;" class="btn btn-primary">' . $params['buttonname'] . '</button>';
}
