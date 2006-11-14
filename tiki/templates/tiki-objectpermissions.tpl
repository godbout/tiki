{* $Header: /cvsroot/tikiwiki/tiki/templates/tiki-objectpermissions.tpl,v 1.16 2006-11-14 13:42:56 sylvieg Exp $ *}
<h1>{tr}Assign permissions to {/tr}{tr}{$objectType|escape}{/tr} {$objectName|escape}</h1>
<a href="{$referer}" class="linkbut">{tr}back{/tr}</a>
<div>
<h2>{tr}Current permissions for this object{/tr}:</h2>
<table class="normal">
<tr><td class="heading">{tr}group{/tr}</td><td class="heading">{tr}permission{/tr}</td><td class="heading">{tr}action{/tr}</td></tr>
{cycle values="odd,even" print=false}
{section  name=pg loop=$page_perms}
<tr><td class="{cycle advance=false}">{$page_perms[pg].groupName}</td>
<td class="{cycle advance=false}">{$page_perms[pg].permName}</td>
<td class="{cycle advance=true}"><a class="link" href="tiki-objectpermissions.php?referer={$referer|escape:"url"}&amp;action=remove&amp;objectName={$objectName}&amp;objectId={$objectId}&amp;objectType={$objectType}&amp;permType={$permType}&amp;page={$page|escape:"url"}&amp;perm={$page_perms[pg].permName}&amp;group={$page_perms[pg].groupName}" title="{tr}Delete{/tr}"><img src="pics/icons/cross.png" width="16" height="16" alt="{tr}delete{/tr}" border="0" /></a></td></tr>
{sectionelse}
<tr><td colspan="3" class="odd">{tr}No individual permissions global permissions apply{/tr}</td></tr>
{/section}
</table>
<h2>{tr}Assign permissions to this object{/tr}</h2>
<div class="simplebox">{tr}Tip: hold down CTRL to select multiple{/tr}</div>
<form method="post" action="tiki-objectpermissions.php">
<input type="hidden" name="page" value="{$page|escape}" />
<input type="hidden" name="referer" value="{$referer|escape}" />
<input type="hidden" name="objectName" value="{$objectName|escape}" />
<input type="hidden" name="objectType" value="{$objectType|escape}" />
<input type="hidden" name="objectId" value="{$objectId|escape}" />
<input type="hidden" name="permType" value="{$permType|escape}" />
<input type="submit" name="assign" value="{tr}assign{/tr}" />

<select name="perm[]" multiple="multiple" size="{$perms|@count}">
{section name=prm loop=$perms}
<option value="{$perms[prm].permName|escape}">{$perms[prm].permName|escape}</option>
{/section}
</select>
{tr}to group{/tr}:
<select name="group[]" multiple="multiple" size="{$perms|@count}">
{section name=grp loop=$groups}
<option value="{$groups[grp].groupName|escape}" {if $groupName eq $groups[grp].groupName }selected="selected"{/if}>{$groups[grp].groupName|escape}</option>
{/section}
</select>
</form>
</div>
