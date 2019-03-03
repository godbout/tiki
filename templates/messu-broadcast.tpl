{title help='Inter-User Messages' url='messu-broadcast.php' admpage="messages"}{tr}Broadcast message{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}
{include file='messu-nav.tpl'}

<form class="form-horizontal" role="form" action="messu-broadcast.php" method="post">
	{ticket}
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="broadcast-group">{tr}Group{/tr}</label>
		<div class="col-sm-10">
			<select name="groupbr" id="broadcast-group" class="form-control">
				<option value=""{if $groupbr eq ''} selected="selected"{/if} />
				{if $tiki_p_broadcast_all eq 'y'}
					<option value="all"{if $groupbr eq 'All'} selected="selected"{/if}>{tr}All users{/tr}</option>
				{/if}
				{foreach item=groupName from=$groups}
					<option value="{$groupName|escape}"{if ! $sent && $groupbr eq $groupName} selected="selected"{/if}>{$groupName|escape}</option>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="broadcast-priority">{tr}Priority{/tr}</label>
		<div class="col-sm-10">
			<select name="priority" id="broadcast-priority" class="form-control">
				<option value="1" {if $priority eq 1}selected="selected"{/if}>1 -{tr}Lowest{/tr}-</option>
				<option value="2" {if $priority eq 2}selected="selected"{/if}>2 -{tr}Low{/tr}-</option>
				<option value="3" {if $priority eq 3}selected="selected"{/if}>3 -{tr}Normal{/tr}-</option>
				<option value="4" {if $priority eq 4}selected="selected"{/if}>4 -{tr}High{/tr}-</option>
				<option value="5" {if $priority eq 5}selected="selected"{/if}>5 -{tr}Very High{/tr}-</option>
			</select>
			<input type="hidden" name="replyto_hash" value="{$replyto_hash|escape}">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="broadcast-subject">{tr}Subject{/tr}</label>
		<div class="col-sm-10">
			<input type="text" name="subject" class="form-control" id="broadcast-subject" value="{$subject|escape}" maxlength="255">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="broadcast-body">{tr}Body{/tr}</label>
		<div class="col-sm-10">
			<textarea class="form-control" rows="20" id="broadcast-body" name="body">{$body|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-10 offset-sm-2">
			{* no js confirmation or ticket needed since the preview is sent to another page *}
			<input type="submit" class="btn btn-secondary" name="preview" value="{tr}Send{/tr}">
		</div>
	</div>
</form>
