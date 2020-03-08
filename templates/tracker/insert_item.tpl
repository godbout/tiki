{extends 'layout_view.tpl'}

{block name="navigation"}
	{include file='tracker_actions.tpl'}
	<a class="btn btn-primary" href="{service controller=tracker action=select_tracker}">{tr}Select Tracker{/tr}</a>
{/block}

{block name="title"}
	{title}{$title|escape}{/title}
{/block}

{block name="content"}
	<div class="previewTrackerItem"></div>
	{if ! $itemId}
		{if $trackerLogo}
			<div class="page_header media">
				<img src="{$trackerLogo|escape}" class="float-left img-fluid rounded" alt="{$trackerName|escape}" height="64px" width="64px">
			</div>
		{/if}
		<form method="post" action="{service controller=tracker action=insert_item format=$format editItemPretty=$editItemPretty suppressFeedback=$suppressFeedback}" id="insertItemForm{$trackerId|escape}" {if ! $trackerId}display="hidden"{/if}>
			{ticket}
			{trackerfields trackerId=$trackerId fields=$fields status=$status format=$format editItemPretty=$editItemPretty}
			{if ! $modal}
				<div class="form-check">
					<label>
						<input type="hidden" name="next" value="{$next}">
						<input type="checkbox" class="form-check-input" name="next" value="{service controller=tracker action=insert_item trackerId=$trackerId next=$next}">
						{tr}Create another{/tr}
					</label>
				</div>
			{/if}
			{if !$user and $prefs.feature_antibot eq 'y'}
				{include file='antibot.tpl'}
			{/if}
			<div class="submit">
				<input type="button" class="btn btn-primary previewItemBtn" title="{tr}Preview your changes.{/tr}" name="preview" value="{tr}Preview{/tr}">
				<input type="hidden" name="trackerId" value="{$trackerId|escape}">
				<input
					type="submit"
					class="btn btn-primary"
					value="{tr}Create{/tr}"
					onclick="checkTimeout();needToConfirm=false;"
				>
				{foreach from=$forced key=permName item=value}
					<input type="hidden" name="forced~{$permName|escape}" value="{$value|escape}">
				{/foreach}
			</div>
		</form>
	{else}
		{object_link type=trackeritem id=$itemId}
	{/if}
{/block}
