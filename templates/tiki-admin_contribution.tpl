{* $Id$ *}

{title help="Contribution"}{tr}Admin Contributions{/tr}{/title}

{if ! empty($contribution)}
	<h2>{tr}Edit the contribution:{/tr} {$contribution.name|escape}</h2>
	<form enctype="multipart/form-data" action="tiki-admin_contribution.php" method="post" role="form">
		{ticket}
		<input type="hidden" name="contributionId" value="{$contribution.contributionId}">
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="name">{tr}Name{/tr}</label>
			<div class="col-sm-9">
					<input type="text" name="name" class="form-control" id="name" {if $contribution.name} value="{$contribution.name|escape}"{/if}>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label" for="description">{tr}Description{/tr}</label>
			<div class="col-sm-9">
				<input type="text" name="description" id="description" class="form-control" maxlength="250"{if $contribution.description} value="{$contribution.description|escape}"{/if}>
			</div>
		</div>
		<div class="form-group text-center">
			<input type="submit" class="btn btn-primary btn-sm" name="replace" value="{tr}Save{/tr}">
		</div>
	</form><br/>
{/if}

<h2>{tr}Settings{/tr}</h2>
<form action="tiki-admin_contribution.php?page=features" method="post" role="form">
	{ticket}
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for=feature_contribution_mandatory">
			{tr}Contributions are mandatory in wiki pages{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contribution_mandatory" id="feature_contribution_mandatory" {if $prefs.feature_contribution_mandatory eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for="feature_contribution_mandatory_forum">
			{tr}Contributions are mandatory in forums{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contribution_mandatory_forum" id="feature_contribution_mandatory_forum" {if $prefs.feature_contribution_mandatory_forum eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for="feature_contribution_mandatory_comment">
			{tr}Contributions are mandatory in comments{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contribution_mandatory_comment" id="feature_contribution_mandatory_comment" {if $prefs.feature_contribution_mandatory_comment eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for="feature_contribution_mandatory_blog">
			{tr}Contributions are mandatory in blogs{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contribution_mandatory_blog" id="feature_contribution_mandatory_blog" {if $prefs.feature_contribution_mandatory_blog eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for="feature_contribution_display_in_comment">
			{tr}Contributions are displayed in the comment/post{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contribution_display_in_comment" name="feature_contribution_display_in_comment" {if $prefs.feature_contribution_display_in_comment eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-6 form-check-label" for="feature_contributor_wiki">
			{tr}Contributors{/tr}
		</label>
		<div class="col-sm-6">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="feature_contributor_wiki" name="feature_contributor_wiki" {if $prefs.feature_contributor_wiki eq 'y'}checked="checked"{/if}>
			</div>
		</div>
	</div>
	<div class="form-group text-center">
		<input type="submit" class="btn btn-primary" name="setting" value="{tr}Save{/tr}">
	</div>
</form><br/>


<h2>{tr}Create a new contribution{/tr}</h2>

<form enctype="multipart/form-data" action="tiki-admin_contribution.php" method="post" role="form">
	{ticket}
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="new_contribution_name">{tr}Name{/tr}</label>
		<div class="col-sm-9">
			<input type="text" name="new_contribution_name" id="new_contribution_name" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="new_contribution_name">{tr}Description{/tr}</label>
		<div class="col-sm-9">
			<input type="text" name="description" class="form-control" maxlength="250">
		</div>
	</div>
	<div class="form-group text-center">
		<input type="submit" class="btn btn-primary" name="add" value="{tr}Add{/tr}">
	</div>
</form><br/>
<h2>{tr}List of contributions{/tr}</h2>
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>{tr}Name{/tr}</th>
			<th>{tr}Description{/tr}</th>
			<th></th>
		</tr>

		{section name=ix loop=$contributions}
			<tr>
				<td class="text">{$contributions[ix].name|escape}</td>
				<td class="text">{$contributions[ix].description|truncate|escape}</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="tiki-admin_contribution.php?contributionId={$contributions[ix].contributionId}">
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-admin_contribution.php?remove={$contributions[ix].contributionId}" onclick="confirmSimple(event, '{tr}Remove contribution?{/tr}', '{ticket mode=get}')">
									{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=3}
		{/section}
	</table>
</div>
