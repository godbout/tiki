{* $Id$ *}
<script type="text/javascript">document.addEventListener("DOMContentLoaded", function () {
		var xmpp_service_url = $.service("xmpp", "prebind");

		jQuery("<link>")
			.attr("rel", "stylesheet")
			.attr("href", "vendor_bundled/vendor/jcbrand/converse.js/css/converse.css")
			.appendTo("head");

		function tiki_initialize_conversejs() {
			converse.plugins.add('tiki', {
				'initialize': function () {
					var _converse = this._converse;
					_converse.api.listen.on('noResumeableSession', function (xhr) {
						feedback (tr("XMPP Module error") + ": " + xhr.statusText, "error", false);
						$("#conversejs").fadeOut("fast");
					});
				}
			});

			converse.initialize({
				bosh_service_url: "{$xmpp.server_http_bind}",
				jid: "{$xmpp.user_jid}",
				authentication: "prebind",
				prebind_url: xmpp_service_url,
				debug: true,
				whitelisted_plugins: ['tiki']
			});

		}

		jQuery.getScript("vendor_bundled/vendor/jcbrand/converse.js/dist/converse.js")
			.done(tiki_initialize_conversejs);
	})
	;
</script>
