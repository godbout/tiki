{* $Id$ *}
{if $prefs.feature_contribution eq 'y'}
	{if count($contributions) gt 0}
		<div class="form-group row">
		{if $contribution_needed eq 'y'}
			<span class="mandatory_note highlight">
		{/if}
			<label for="contributions" class="col-sm-3 col-form-label">{tr}Type of contribution:{/tr}</label>
		{if $prefs.feature_contribution_mandatory eq 'y'}
			<strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>
		{/if}
		{if $contribution_needed eq 'y'}
			</span>
		{/if}
			<div class="col-sm-6">
				<select id="contributions" name="contributions[]" multiple="multiple" size="5" class="form-control">
					{section name=ix loop=$contributions}
						<option value="{$contributions[ix].contributionId|escape}"{if $contributions[ix].selected eq 'y'} selected="selected"{/if} >{if $contributions[ix].contributionId > 0}{$contributions[ix].name|escape}{/if}</option>
						{assign var="help" value=$help|cat:$contributions[ix].name|cat:": "|cat:$contributions[ix].description|cat:"<br>"}
					{/section}
				</select>
				<a title="{tr}Help{/tr}" {popup text=$help|replace:'"':"'" width=500}>{icon name='help'}</a>
			</div>
		</div>
		{if $prefs.feature_contributor_wiki eq 'y' and $section eq 'wiki page' and empty($in_comment)}
			<div class="form-group row">
				<label for='contributors' class="col-sm-3 col-form-label">{tr}Contributors{/tr}</label>
				<div class="col-sm-6">
					<select id="contributors" name="contributors[]" multiple="multiple" size="5" class="form-control">
						{foreach key=userId item=u from=$users}
							{if $u ne $user}<option value="{$userId}"{if !empty($contributors) and in_array($userId, $contributors)} selected="selected"{/if}>{$u}</option>{/if}
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
	{elseif $tiki_p_admin eq 'y'}
		{tr}No contribution records found.{/tr}
	{/if}
{/if}
