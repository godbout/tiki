{title help="FeaturedLinksAdmin"}{tr}OAuth Server{/tr}{/title}

<h2>{tr}List of clients{/tr}</h2>

{foreach $client_list as $key => $entity }
<form action="{$client_update_url}" method="POST">
	<div class="row">
		<input type="hidden" 
			id="oauth-identifier"
			name="identifier"
			value="{$entity->getIdentifier()}"
			/>

		<div class="col-sm-12">
			<h3>{$entity->getName()}</h3>
		</div>

		<div class="form-group col-md-6 col-sm-12">
			<label for="oauth-name">{tr}Name{/tr}</label>
			<input type="text" class="form-control" 
				id="oauth-name"
				name="name"
				value="{$entity->getName()}"
				/>
		</div>
		<div class="form-group col-md-6 col-sm-12">
			<label for="oauth-client_id">{tr}Client_id{/tr}</label>
			<input type="text" class="form-control" 
				id="oauth-client_id"
				name="client_id"
				value="{$entity->getClientId()}"
				/>
		</div>
		<div class="form-group col-sm-12">
			<label for="oauth-client_secret">{tr}Client_secret{/tr}</label>
			<input type="text" class="form-control" 
				id="oauth-client_secret"
				name="client_secret"
				value="{$entity->getClientSecret()}"
				/>
		</div>
		<div class="form-group col-sm-12">
			<label for="oauth-redirect_uri">{tr}Redirect_uri{/tr}</label>
			<input type="text" class="form-control" 
				id="oauth-redirect_uri"
				name="redirect_uri"
				value="{$entity->getRedirectUri()}"
				/>
		</div>
		<div class="col-sm-12">
			<div class="btn-group float-right">
				<button type="submit" class="btn btn-success" name="save" value="1">{tr}Save{/tr}</button>
				<button type="submit" class="btn btn-danger" name="delete" value="1">{tr}Delete{/tr}</button>
			</div>
		</div>
	</div>
	<hr />
</form>
{/foreach}
