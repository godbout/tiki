{* $Id$ *}
{title help="Mail notifications"}
	{if isset($smarty.request.objectName)}
		{tr}Object Watches:{/tr} {$smarty.request.objectName}
	{else}
		{tr}Object Watches{/tr}
	{/if}
{/title}
{if $isTop ne 'y'}
	<div class="mb-2">{tr}Groups watching:{/tr} {$group_watches|@count}</div>
{/if}

{if !empty($addedGroups) || !empty($deletedGroups) || !empty($addedGroupsDesc) || !empty($deletedGroupsDesc)}
	{remarksbox type="feedback" title="{tr}Success{/tr}"}
		<div class="clearfix">
			{if !empty($addedGroups) || !empty($deletedGroups)}
				<div style="float:left;clear:both;">
					{tr}Changes to groups watching:{/tr}
					<ul>
						{if !empty($addedGroups)}
							{foreach from=$addedGroups item=g}<li>{$g|escape}&nbsp;&nbsp;<em>added</em></li>{/foreach}
						{/if}
						{if !empty($deletedGroups)}
							{foreach from=$deletedGroups item=g}<li>{$g|escape}&nbsp;&nbsp;<em>removed</em></li>{/foreach}
						{/if}
					</ul>
				</div>
			{/if}
			{if !empty($addedGroupsDesc) || !empty($deletedGroupsDesc)}
			{if !empty($addedGroups) || !empty($deletedGroups)}
			<div style="float:left;padding-left:50px;">
				{else}
				<div style="float:left;">
					{/if}
					{tr}These changes to group watches:{/tr}
					<ul>
						{if !empty($addedGroupsDesc)}
							{foreach from=$addedGroupsDesc item=g}<li>{$g|escape}&nbsp;&nbsp;<em>added</em></li>{/foreach}
						{/if}
						{if !empty($deletedGroupsDesc)}
							{foreach from=$deletedGroupsDesc item=g}<li>{$g|escape}&nbsp;&nbsp;<em>removed</em></li>{/foreach}
						{/if}
					</ul>
					{if isset($tree)}
						{tr}were made to these descendants:{/tr}
						{$tree}
					{/if}
				</div>
				{/if}
		</div>
	{/remarksbox}
{/if}

<form method="post" action="{$smarty.server.REQUEST_URI|escape}">
	<input type="hidden" name="referer" value="{$referer|escape}">
	{ticket}
	{if isset($referer)}
		<div class="float-left">
			{button href="$referer" _class="btn btn-primary btn-sm" _text="{tr}Back{/tr}"}
		</div>
	{/if}
	<div class="float-left mb-2"><input type="submit" class="btn btn-primary btn-sm" name="assign" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}"></div>
	<div class="table-responsive">
		<table class="table">
			<tr>
				{if !empty($cat) && !empty($desc)}
					<th>{tr}Groups{/tr}</th>
					{if $isTop ne 'y'}
						<th>{tr}This Category{/tr}</th>
					{/if}
					<th>{tr}All Descendants{/tr}</th>
				{else}
				<th>
					{select_all checkbox_names='checked[]'}
				</th>
				<th style="width:100%">{tr}Groups{/tr}</th>
				{/if}
			</tr>

			{foreach from=$all_groups item=g key=i}
				{if $g ne 'Anonymous'}
					<tr>
						{if !empty($cat) && !empty($desc)}
							<td class="text"><label for="group_watch{$i}">{$g|escape}</label></td>
							{if $isTop ne 'y'}
								<td class="checkbox-cell"><div class="form-check"><input id="group_watch{$i}"type="checkbox" name="checked[]"
																						 value="{$g|escape}"{if in_array($g, $group_watches)} checked="checked"{/if}></div></td>
							{/if}
							<td class="text">
								<input id="group_watch{$i}" type="radio" name="{$g|escape}" value="cat_leave_desc" checked="checked">
								<label for="group_watch{$i}">Leave unchanged &nbsp;&nbsp;&nbsp;</label>
								<input id="group_watch{$i}" type="radio" name="{$g|escape}" value="cat_add_desc">
								<label for="group_watch{$i}">Add &nbsp;&nbsp;&nbsp;</label>
								<input id="group_watch{$i}" type="radio" name="{$g|escape}" value="cat_remove_desc">
								<label for="group_watch{$i}">Remove</label>
							</td>

						{else}
						<td class="checkbox-cell"><div class="form-check"><input id="group_watch{$i}" type="checkbox" name="checked[]" value="{$g|escape}"
										{if in_array($g, $group_watches)} checked="checked"{/if}></div></td>
						<td class="text"><label for="group_watch{$i}">{$g|escape}</label></td>
						{/if}
					</tr>
				{/if}
			{/foreach}
		</table>
	</div>
	<p>
		<div style="float: left; margin-right: 10px;">
			<input
				type="submit"
				class="btn btn-primary btn-sm"
				name="assign"
				title="{tr}Apply Changes{/tr}"
				value="{tr}Apply{/tr}"
			>
		</div>
	</p>
</form>
