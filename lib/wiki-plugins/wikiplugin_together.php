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
			co-author, co-browse and guide each other. TogetherJS is implemented in JavaScript; no software or plugins
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
			'serverurl' => [
				'required' => false,
				'name' => tra('Server URL'),
				'description' => tra('Hub server URL address if the default one is not working or you are willing to host your own hub server.'),
				'since' => '21.0',
				'filter' => 'text',
				'default' => ''
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
	'.(! empty($params['serverurl']) ? "window.TogetherJSConfig_hubBase = ".json_encode($params['serverurl']).";\n" : '').'
	window.TogetherJSConfig_on_ready = function() {
		if(m = window.location.href.match(/tiki-editpage.php\?page=([^&#]+)/)) {
			var session = TogetherJS.require("session");
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
	window.startTogetherJS = function() {
		if(typeof TogetherJS === "undefined") {
			setTimeout(window.startTogetherJS, 300);
		} else {
			TogetherJS.config("getUserName", function () {
				return jqueryTiki.userRealName || jqueryTiki.username;
			});
			TogetherJS.config("getUserAvatar", function () {
				return jqueryTiki.userAvatar;
			});
			TogetherJS();
		}
	}
	window.loadTogetherJS = function() {
		var script = document.createElement("script");
		script.src = "https://togetherjs.com/togetherjs-min.js";
		script.async = false;
		document.getElementsByTagName("head")[0].appendChild(script);
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
				var key = "togetherjs-session.status";
				var status = sessionStorage.getItem(key);
				if (status) {
					status = JSON.parse(status);
					if( !status.running || status.shareId != data ) {
						status.shareId = data;
						status.running = true;
						sessionStorage.setItem(key, JSON.stringify(status));
					}
				}
				if (!sessionStorage.getItem(key)) {
					window.location.hash = "&togetherjs="+data;
				}
			}
			loadTogetherJS();
		}
	});
} else {
	loadTogetherJS();
}
		');

	return '<button onclick="window.startTogetherJS(); return false;" class="btn btn-primary">' . $params['buttonname'] . '</button>';
}
