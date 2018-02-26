{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $schedulerId}
<form class="simple" method="post" action="{service controller=scheduler action=reset}">
	<p>{tr}Reset the scheduler, will mark this run as failed, allowing it to be executed again by the cron.{/tr}</p>
	<div class="submit">
		<input type="hidden" name="confirm" value="1">
		<input type="hidden" name="schedulerId" value="{$schedulerId|escape}">
		<input type="hidden" name="startTime" value="{$startTime|escape}">
		<input type="submit" class="btn btn-default" value="{tr}Reset{/tr}">
	</div>
</form>
{else}
<a href="tiki-admin_schedulers.php">{tr}Back to schedulers list{/tr}
{/if}
{/block}
