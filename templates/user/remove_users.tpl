{extends 'layout_view.tpl'}
{block name="title"}
	{title}{$title|escape}{/title}
{/block}
{block name="content"}
	{include file='access/include_items.tpl'}
	<form method="post" id="confirm-action" class="confirm-action" action="{service controller=$confirmController action=$confirmAction}">
		{include file='access/include_hidden.tpl'}
		<div class="form-group row">
			<label class="col-form-label" for="remove_users">{icon name='users'} {tr}Remove users{/tr}</label>
			<div>
				<input class="form-control" type="checkbox" id="remove_users" name="remove_users" checked="checked" disabled="disabled">
			</div>
		</div>
		{if $prefs.feature_wiki_userpage == 'y'}
			<div class="form-group row">
				<label class="col-form-label" for="remove_pages">{icon name='admin_wiki'} {tr}Remove the users' pages{/tr}</label>
				<div>
					<input class="form-control" type="checkbox" id="remove_pages" name="remove_pages">
					<div class="form-text">
						{tr}Remove the user pages belonging to these users{/tr}
					</div>
				</div>
			</div>
		{/if}
		{if $prefs.feature_trackers eq 'y'}
			<div class="form-group row">
				<label class="col-form-label" for="remove_items">{icon name='trackers'} {tr}Delete user items from these trackers{/tr}</label>
				<span class="text-danger">{tr}Warning: Experimental{/tr} {icon name='warning'}</span>{* TODO remove warning before 15.0 *}
				<div>
					{object_selector_multi type='tracker' _separator="," _simplename="remove_items"}
					<div class="form-text">
						{tr}Select trackers here to have items in them which are "owned" by these users deleted{/tr}<br>
						{tr}Important: If you set trackers to store user's information, "User" and "Group" tracker items related to this user will be deleted automatically{/tr}
					</div>
				</div>
			</div>
		{/if}
		{if $prefs.feature_use_fgal_for_user_files eq 'y'}
			<div class="form-group row">
				<label class="col-form-label" for="remove_files">{icon name='file'} {tr}Delete user files{/tr}</label>
				<span class="text-danger">{tr}Warning: Experimental{/tr} {icon name='warning'}</span>{* TODO remove warning before 15.0 *}
				<div>
					<input class="form-control" type="checkbox" id="remove_files" name="remove_files">
					<div class="form-text">
						{tr}Delete the users' file galleries and all the files in them{/tr}
					</div>
				</div>
			</div>
		{/if}
		{if $prefs.feature_banning eq 'y'}
			<div class="form-group row">
				<label class="col-form-label" for="ban_users">{icon name='ban'} {tr}Ban users{/tr}</label>
				<div>
					<input class="form-control" type="checkbox" id="ban_users" name="ban_users">
					<div class="form-text">
						{tr}Checking this option and clicking OK will redirect you to a form where the selected users are marked for IP Banning.{/tr}
					</div>
				</div>
			</div>
		{/if}
		{include file='access/include_submit.tpl'}
	</form>
{/block}
