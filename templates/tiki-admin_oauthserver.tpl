{title help=""}{tr}OAuth Server{/tr}{/title}

<div id="tiki-admin_oauthserver">
	<h2>{tr}List of clients{/tr}</h2>

	{foreach $client_list as $key => $entity }
	<form action="{$client_modify_url}" method="POST" class="js-oauth-client">
		<div class="row">
			<input type="hidden" 
				name="identifier"
				value="{$entity->getIdentifier()}"
				/>

			<div class="col-sm-12">
				<h3>{$entity->getName()}</h3>
			</div>

			<div class="form-group col-md-6 col-sm-12">
				<label for="oauth-name">{tr}Name{/tr}</label>
				<input type="text" class="form-control" 
					name="name"
					value="{$entity->getName()}"
					/>
			</div>
			<div class="form-group col-md-6 col-sm-12">
				<label for="oauth-client_id">{tr}Client_id{/tr}</label>
				<input type="text" class="form-control" 
					name="client_id"
					value="{$entity->getClientId()}"
					/>
			</div>
			<div class="form-group col-sm-12">
				<label for="oauth-client_secret">{tr}Client_secret{/tr}</label>
				<input type="text" class="form-control" 
					name="client_secret"
					style="font-family: monospace;"
					value="{$entity->getClientSecret()}"
					/>
			</div>
			<div class="form-group col-sm-12">
				<label for="oauth-redirect_uri">{tr}Redirect_uri{/tr}</label>
				<input type="text" class="form-control" 
					name="redirect_uri"
					value="{$entity->getRedirectUri()}"
					/>
			</div>
			<div class="col-sm-12">
				<div class="btn-group float-right">
					<button type="submit" class="btn btn-success" value="1">{tr}Save{/tr}</button>
					<button type="submit" class="btn btn-danger" name="delete" value="1">{tr}Delete{/tr}</button>
				</div>
			</div>
		</div>
		<hr />
	</form>
	{/foreach}
</div>