{extends 'layout_view.tpl'}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $schedulerId}
	<form class="simple" method="post" action="{service controller=scheduler action=remove}">
		<p>{tr}{$message}{/tr}</p>
	</form>
{else}
	<a href="tiki-admin_schedulers.php">{tr}Back to tracker list{/tr}
{/if}
{/block}
