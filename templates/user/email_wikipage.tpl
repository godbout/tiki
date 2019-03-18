{extends 'layout_view.tpl'}
{block name="title"}
	{title}{$title|escape}{/title}
{/block}
{block name="content"}
	{include file='access/include_items.tpl'}
	<form method="post" id="confirm-action" class="confirm-action" action="{service controller=$confirmController action=$confirmAction}">
		{include file='access/include_hidden.tpl'}
		<div class="form-group row">
			<label class="col-form-label col-md-12">{tr}Email this wiki page{/tr}</label>
			<div class="col-md-12">
				<input class="form-control" type="text" name="wikiTpl">
				<div class="form-text">{tr}Enter page name.
						The wiki page must have a page description, which is used as the subject of the email.
						Enable the page descriptions feature at Control Panels &gt; Wiki.{/tr}
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-md-12">{tr}Bcc{/tr}</label>
			<div class="col-md-12">
				<input class="form-control" type="text" name="bcc">
				<div class="form-text">{tr}Enter a valid email to send a blind copy to (optional).{/tr}</div>
			</div>
		</div>
		{include file='access/include_submit.tpl'}
	</form>
{/block}