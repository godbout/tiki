{*$Id$*}
{title}{tr}Contact Us{/tr}{/title}

{if !$sent}
	<h2>{tr}Send a message to us{/tr}</h2>
	<form class="form form-horizontal" method="post" action="tiki-contact.php">
		{ticket}
		<input type="hidden" name="to" value="{$prefs.contact_user|escape}">
		{if $prefs.contact_priority_onoff eq 'y'}
			<div class="form-group row">
				<label for="priority" class="col-sm-3 col-form-label">{tr}Priority:{/tr}</label>
				<div class="col-sm-9">
					<select id="priority" name="priority" class="form-control">
						<option value="1" {if $priority eq 1}selected="selected"{/if}>1 -{tr}Lowest{/tr}-</option>
						<option value="2" {if $priority eq 2}selected="selected"{/if}>2 -{tr}Low{/tr}-</option>
						<option value="3" {if $priority eq 3}selected="selected"{/if}>3 -{tr}Normal{/tr}-</option>
						<option value="4" {if $priority eq 4}selected="selected"{/if}>4 -{tr}High{/tr}-</option>
						<option value="5" {if $priority eq 5}selected="selected"{/if}>5 -{tr}Very High{/tr}-</option>
					</select>
				</div>
			</div>
		{/if}
		{if $user eq ''}
			<div class="form-group row">
				<label for="from" class="col-sm-3 col-form-label">{tr}Your email{/tr}:</label>
				<div class="col-sm-9">
					<input type="text" id="from" name="from" value="{$from|escape}" class="form-control">
				</div>
			</div>
		{/if}
		<div class="form-group row">
			<label for="subject" class="col-sm-3 col-form-label">{tr}Subject:{/tr}</label>
			<div class="col-sm-9">
				<input type="text" id="subject" name="subject" value="{$subject|escape}" class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label for="body" class="col-sm-3 col-form-label">{tr}Message:{/tr}</label>
			<div class="col-sm-9">
				{textarea rows="20" name="body" id="body" class="form-control" _simple='y' _toolbars='n'}{$body|escape}{/textarea}
			</div>
		</div>
		{if $prefs.feature_antibot eq 'y' && $user eq ''}
			{include file='antibot.tpl' td_style="form"}
		{/if}
		<div class="form-group text-center">
			<input type="submit" class="btn btn-primary" name="send" value="{tr}Send{/tr}">
		</div>
	</form>
{/if}

{if strlen($email)>0}
	<h2>{tr}Contact us by email{/tr}</h2>
	{tr}Click here to send us an email:{/tr} {mailto text="$email" address="$email0" encode="javascript" extra='class="link"'}
{else}
	<p><a class="link" href="tiki-contact.php">{tr}Send another message{/tr}</a></p>
{/if}
