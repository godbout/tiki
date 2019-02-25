{title help=""}{tr}OAuth Server{/tr}{/title}

<div id="tiki-admin_oauthserver">
	<h2>{tr}List of clients{/tr}</h2>

	{function
		name="printClientForm"
		htmlId=""
		entityId=""
		entityName=""
		entityName=""
		entityClientId=""
		entityClientSecret=""
		entityRedirectUri=""
	}
	<form {if $htmlId}id="$htmlId"{/if} action="{$client_modify_url}" method="POST" class="js-oauth-client">
		<div class="row">
			<input type="hidden" 
				name="id"
				value="{$entityId}"
				/>

			<div class="col-sm-12">
				<h3>{$entityName}</h3>
			</div>

			<div class="form-group col-md-6 col-sm-12">
				<label for="oauth-name">{tr}Name{/tr}</label>
				<input type="text" class="form-control" 
					name="name"
					value="{$entityName}"
					/>
			</div>
			{if $entityId}
			<div class="form-group col-md-6 col-sm-12">
				<label for="oauth-client_id">{tr}Client_id{/tr}</label>
				<input type="text" class="form-control" 
					name="client_id"
					value="{$entityClientId}"
					/>
			</div>
			<div class="form-group col-sm-12">
				<label for="oauth-client_secret">{tr}Client_secret{/tr}</label>
				<input type="text" class="form-control" 
					name="client_secret"
					style="font-family: monospace;"
					value="{$entityClientSecret}"
					/>
			</div>
			{/if}
			<div class="form-group col-sm-12">
				<label for="oauth-redirect_uri">{tr}Redirect_uri{/tr}</label>
				<input type="text" class="form-control" 
					name="redirect_uri"
					value="{$entityRedirectUri}"
					/>
			</div>
			<div class="col-sm-12">
				<div class="btn-group float-right">
					<button type="submit" class="btn btn-success" value="1">{$entityId|ternary:'Save':'Add'|tra}</button>
					<button type="submit" class="btn btn-danger" name="delete" value="1">{tr}Delete{/tr}</button>
				</div>
			</div>
		</div>
		<hr />
	</form>
	{/function}

	{foreach $client_list as $key => $entity }
		{call
			name="printClientForm"
			htmlId="entity-form-{$key}"
			entityId="{$entity->getId()}"
			entityName="{$entity->getName()}"
			entityClientId="{$entity->getClientId()}"
			entityClientSecret="{$entity->getClientSecret()}"
			entityRedirectUri="{$entity->getRedirectUri()}"
		}
	{/foreach}

	{call
		name="printClientForm"
		htmlId="new-entity-form"
		entityName="{$client_empty->getName()}"
		entityRedirectUri="{$client_empty->getRedirectUri()}"
	}

</div>