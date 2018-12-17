{* $Id$ *}
{if isset($pagePermissions)}
	{tikimodule error=$module_params.error title=$tpl_module_title name="permissions" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle style=$module_params.style}
		{if $pagePermissions}
			{foreach from=$pagePermissions key=permission item=info}
				{assign var="permissionName" value=$permission|substr:7}
				{$permissionName}
				<span target="tikihelp" class="tikihelp" title="{$permissionName}<br/>
					{if !empty($info["global"])}
						<br/>
						<b>{tr}Global permissions{/tr}</b>
						<div>
							{', '|implode:$info['global']}
						</div>
						<br/>
					{/if}

					{if !empty($info["object"])}
						<b>{tr}Object permissions{/tr}</b>
						<table class='table table-striped'>
							<tr>
								<td><b>{tr}Object{/tr}</b></td>
								<td><b>{tr}Group{/tr}</b></td>
								<td><b>{tr}Reason{/tr}</b></td>
							</tr>
							{foreach from=$info["object"] item=objectInfo}
								<tr>
									<td>{$objectInfo["objectName"]}</td>
									<td>{$objectInfo["group"]}</td>
									<td>{$objectInfo["reason"]}</td>
								</tr>
							{/foreach}
						</table>
					{/if}
					{if !empty($info["category"])}
						<b>{tr}Category permissions{/tr}</b>
						<table class='table table-striped'>
							<tr>
								<td><b>{tr}Object{/tr}</b></td>
								<td><b>{tr}Group{/tr}</b></td>
								<td><b>{tr}Reason{/tr}</b></td>
							</tr>
							{foreach from=$info["object"] item=objectInfo}
								<tr>
									<td>{$objectInfo["objectName"]}</td>
									<td>{$objectInfo["group"]}</td>
									<td>{$objectInfo["reason"]}</td>
								</tr>
							{/foreach}
						</table>
					{/if}
				">{icon name="help"}</span>
				<br/>
			{/foreach}
		{else}
			{tr}No permissions to display{/tr}
		{/if}
	{/tikimodule}
{/if}
