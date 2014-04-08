{extends 'layout_view.tpl'}

{block name="navigation"}
	{include file='tracker_actions.tpl'}
	<a class="btn btn-default" href="{service controller=tracker action=select_tracker}">{tr}Select Tracker{/tr}</a>
{/block}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
	{if ! $itemId}
		{if $trackerLogo}
			<div class="page-header media">
				<img src="{$trackerLogo|escape}" class="pull-left img-responsive img-rounded" alt="{$trackerName|escape}" height="64px" width="64px">
			</div>
		{/if}
		<form method="post" action="{service controller=tracker action=insert_item}" id="insertItemForm" {if ! $trackerId}display="hidden"{/if}>
			{trackerfields trackerId=$trackerId fields=$fields}
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="next" value="{service controller=tracker action=insert_item trackerId=$trackerId}">
				  {tr}Create another{/tr}
				</label>
			</div>
			<div class="submit">
				<input type="hidden" name="trackerId" value="{$trackerId|escape}">
				<input type="submit" class="btn btn-primary" value="{tr}Create{/tr}">
				{foreach from=$forced key=permName item=value}
					<input type="hidden" name="forced~{$permName|escape}" value="{$value|escape}">
				{/foreach}
			</div>
		</form>
	{else}
		{object_link type=trackeritem id=$itemId}
	{/if}
{/block}
