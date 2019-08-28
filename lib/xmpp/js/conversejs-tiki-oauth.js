converse.plugins.add("tiki-oauth", {
	"initialize": function () {
		var _converse = this._converse;
		var plugin = this;
		var provider = (_converse.user_settings.oauth_providers || {}).tiki;
		var error = window.error
			? window.error
			: (window.feedback
				? function(msg){ feedback(msg, 'error'); }
				: function(msg){ console.error(msg); }
			);

		if(!provider) {
			return;
		}

		var endpoint = provider.authorize_url;
		endpoint = endpoint + '&client_id=' + provider.client_id;

		var xhr = new XMLHttpRequest();
		xhr.open('POST', endpoint, false);
		xhr.onload = function () {
			var token = xhr.responseURL;
			token = token.substring(token.indexOf('?') + 1);
			token = token.split(/&amp;/);
			token = token.map(function(piece){
				return [
					piece.substring(0, piece.indexOf('=')),
					piece.substring(piece.indexOf('=') + 1)
				]
			})
			token = token.filter(function(piece){
				return piece.length === 2 && piece[0] === 'access_token';
			});
			if (token.length === 1 && token[0].length === 2 ) {
				plugin.set_connection(token[0][1]);
			}
		};
		xhr.onerror = xhr.onabort = xhr.ontimeout = error;
		xhr.send(null);
	},

	"set_connection": function(access_token) {
		var _converse = this._converse;
		_converse.password = access_token;
		_converse.promises.connectionInitialized.then(function() {
			_converse.connection
				.mechanisms
				.OAUTHBEARER
				.prototype
				.priority = 100
		});
	}
});