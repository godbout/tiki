{title help=""}{tr}Consent screen{/tr}{/title}

<div id="tiki-admin_oauthserver">
	<form method="post" action="{$authorize_url}">
		{ticket}

		<p>{'The app "%0" is asking to access your information.'|tr_if:$client->getName()}</p>
		<p>{tr}Do you really want to proceed{/tr}</p>

		<input type="hidden" name="authorize_url" value="{$authorize_url}" />
		<input type="hidden" name="response_type" value="{$response_type}" />
		<input type="hidden" name="client_id"     value="{$client->getIdentifier()}" />
		<input type="hidden" name="redirect_uri"  value="{$redirect_uri}" />
		<input type="hidden" name="scope"         value="{$scope}" />

		<div class="btn-group">
			<button type="submit" class="btn btn-lg btn-danger">{tr}Yes{/tr}</button>
			<a href="/" class="btn btn-lg btn-warning">{tr}No{/tr}</a>
		</div>
	</form>
</div>