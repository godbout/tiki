{* $Id$ *}
{extends "layout_view.tpl"}

{block name="title"}
	{title help="Webmail"}{tr}Mail-in accounts{/tr}{/title}
{/block}

{block name="content"}
	<table class="table table-striped table-hover">
		<tr>
			<th>{tr}Account{/tr}</th>
			<th>{tr}Allow{/tr}</th>
			<th>{tr}Attach{/tr}</th>
			<th>{tr}HTML{/tr}</th>
			<th>{tr}Leave{/tr}</th>
			<th></th>
		</tr>

		{foreach $accounts as $account}
			<tr>
				<td>
					<a href="{bootstrap_modal controller=mailin action=replace_account accountId=$account.accountId}">
						<strong>{$account.account|escape}</strong>
					</a>
					<div>{$mailin_types[$account.type].name|escape}</div>
					{if $account.active neq 'y'}
						<span class="label label-warning">{tr}Disabled{/tr}</span>
					{/if}
					{if $account.categoryId}
						<div class="text-muted">
							{tr}Auto-category:{/tr}
							{object_link type=category id=$account.categoryId}
						</div>
					{/if}
					{if $account.namespace}
						<div class="text-muted">
							{tr}Auto-namespace:{/tr}
							{object_link type="wiki page" id=$account.namespace}
						</div>
					{/if}
				</td>
				<td>
					{if $account.anonymous eq 'y'}<span class="label label-info">{tr}Anonymous{/tr}</span>{/if}
					{if $account.admin eq 'y'}<span class="label label-warning">{tr}Administrator{/tr}</span>{/if}
				</td>
				<td>{if $account.attachments eq 'y'}{icon name="ok"}{/if}</td>
				<td>{if $account.save_html eq 'y'}{icon name="ok"}{/if}</td>
				<td>{if $account.leave_email eq 'y'}{icon name="ok"}{/if}</td>

				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="{bootstrap_modal controller=mailin action=replace_account accountId=$account.accountId}"
									onclick="$('[data-toggle=popover]').popover('hide');"
								>
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="{bootstrap_modal controller=mailin action=remove_account accountId=$account.accountId}"
									onclick="$('[data-toggle=popover]').popover('hide');"
								>
									{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{/foreach}
	</table>
	<a href="{bootstrap_modal controller=mailin action=replace_account}" class="btn btn-primary">{icon name="add"} {tr}Add Account{/tr}</a>
	{button _icon_name="cog" _text="{tr}Admin Mail-in Routes{/tr}" _type="link" href="tiki-admin_mailin_routes.php"}

	<h2>{tr}Check Mail-in accounts{/tr}</h2>
	<form action="tiki-admin_mailin.php" method="post">
		<div class="form-group row">
			<div class="offset-md-3 col-md-9">
				<div class="form-check">
					<label class="form-check-label">
						<input type="checkbox" class="form-check-input" name="mailin_autocheck" value="y" {if $prefs.mailin_autocheck eq 'y'}checked{/if}>
						{tr}Check automatically{/tr}
					</label>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label for="mailin_autocheckFreq" class="col-form-label col-md-3">{tr}Frequency{/tr}</label>
			<div class="col-md-3">
				<input type="text" name="mailin_autocheckFreq" value="{$prefs.mailin_autocheckFreq|escape}" class="form-control">
				<div class="form-text">
					{tr}minutes{/tr}
				</div>
			</div>
		</div>
		<div class="submit offset-md-3 col-md-9">
			<input type="submit" name="set_auto" value="{tr}Set{/tr}" class="btn btn-secondary">
			<a class="btn btn-link" href="tiki-mailin.php">{tr}Check Manually Now{/tr}</a>
		</div>
	</form>
{/block}
