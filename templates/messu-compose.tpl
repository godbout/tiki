{title help="Inter-User Messages" admpage="messages"}{tr}Compose message{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}
{include file='messu-nav.tpl'}

{if $allowMsgs ne 'y'}
	<div class="card">
		<div class="card-body">
			{icon name='information' style="vertical-align:middle" align="left"} {tr}If you want people to be able to reply to you, enable <a href='tiki-user_preferences.php'>Allow messages from other users</a> in your preferences.{/tr}
		</div>
	</div>
{/if}

{if $prefs.user_selector_realnames_messu == 'y'}
	{jq}$(".username").tiki("autocomplete", "userrealname", {multiple: true, mustMatch: true, multipleSeparator: ";"});{/jq}
{else}
	{jq}$(".username").tiki("autocomplete", "username", {multiple: true, mustMatch: true, multipleSeparator: ";"});{/jq}
{/if}
<form action="messu-compose.php" method="post" role="form">
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="mess-composeto">{tr}To{/tr}
			{help url="Inter-User+Messages#Composing_messages" desc="{tr}To: Multiple addresses can be separated with a semicolon (';') or comma (','){/tr}"}
		</label>
		<div class="col-sm-10">
			<input type="text" class="username form-control" name="to" id="mess-composeto" value="{$to|escape}">
			<input type="hidden" name="replyto_hash" value="{$replyto_hash|escape}">
			<input type="hidden" name="reply" value="{$reply}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="mess-composecc">{tr}CC{/tr}
			{help url="Inter-User+Messages#Composing_messages" desc="{tr}CC: Multiple addresses can be separated with a semicolon (';') or comma (','){/tr}"}
		</label>
		<div class="col-sm-10">
			<input type="text" class="username form-control" name="cc" id="mess-composecc" value="{$cc|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="mess-composebcc">{tr}BCC{/tr}
			{help url="Inter-User+Messages#Composing_messages" desc="{tr}BCC: Multiple addresses can be separated with a semicolon (';') or comma (','){/tr}"}
		</label>
		<div class="col-sm-10">
			<input type="text" class="form-control username" name="bcc" id="mess-composebcc" value="{$bcc|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="mess-prio">{tr}Priority{/tr}</label>
		<div class="col-sm-10">
			<select name="priority" id="mess-prio" class="form-control">
				<option value="1" {if $priority eq 1}selected="selected"{/if}>1: {tr}Lowest{/tr}</option>
				<option value="2" {if $priority eq 2}selected="selected"{/if}>2: {tr}Low{/tr}</option>
				<option value="3" {if $priority eq 3}selected="selected"{/if}>3: {tr}Normal{/tr}</option>
				<option value="4" {if $priority eq 4}selected="selected"{/if}>4: {tr}High{/tr}</option>
				<option value="5" {if $priority eq 5}selected="selected"{/if}>5: {tr}Very High{/tr}</option>
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="mess-subj">{tr}Subject{/tr}</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" name="subject" id="mess-subj" value="{$subject|escape}" maxlength="255">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="broadcast-body">{tr}Body{/tr}</label>
		<div class="col-sm-10">
			<textarea class="form-control" rows="20" name="body">{$body|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-10 offset-sm-2">
			{* no js confirmation or ticket needed since the preview is sent to another page *}
			<label for="replytome" class="ml-2">
				<input type="checkbox" name="replytome" id="replytome">
				{tr}Reply-to my email{/tr}
				{help url="User+Information" desc="{tr}Reply-to my email{/tr}{tr}The user will be able to reply to you directly via email.{/tr}"}
			</label>
			<label for="bccme" class="ml-2">
				<input type="checkbox" name="bccme" id="bccme">
				{tr}Send me a copy{/tr}
				{help url="User+Information" desc="{tr}Send me a copy:{/tr}{tr}You will be sent a copy of this email.{/tr}"}
			</label>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-10 offset-sm-2">
			<input type="submit" class="btn btn-secondary" name="preview" value="{tr}Send{/tr}">
		</div>
	</div>
</form>
