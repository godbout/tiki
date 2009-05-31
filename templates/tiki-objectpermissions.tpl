{* $Id$ *}

{title help="Permission"}{tr}Assign permissions to {/tr}{tr}{$objectType|escape}{/tr}: {$objectName|escape}{/title}

<div class="navbar">
  {button href="$referer" _text="{tr}Back{/tr}"}
</div>

{tabset name='tabs_objectpermissions'}

{tab name='{tr}View Permissions{/tr}'}

{if $prefs.feature_tabs neq 'y'}
	<h2>{tr}View Permissions{/tr}</h2>
{/if}
{if $filegals_manager eq ''}
{remarksbox type="warning" title="{tr}Warning{/tr}"}{tr}These permissions override any global permissions or category permissions affecting this object.{/tr}<br />
{if $tiki_p_admin eq 'y'}{tr}To edit global permissions <a class="rbox-link" href="tiki-admingroups.php">click here</a>.{/tr}{/if}
{/remarksbox}
{/if}
<h2>{tr}Current permissions for this object{/tr}</h2>
<table class="normal">
<tr>
	<th>{tr}Permission{/tr}</th>
	<th>{tr}Group{/tr}</th>
</tr>
{cycle values="odd,even" print=false}
{section  name=pg loop=$page_perms}
<tr>
<td class="{cycle advance=false}" title="{$page_perms[pg].permName}">{$page_perms[pg].permName|escape}<br /><i>{tr}{$page_perms[pg].permDesc|escape}{/tr}</i></td>
<td class="{cycle advance=false}">{$page_perms[pg].groupName}</td>
</tr>
{sectionelse}
<tr><td colspan="4" class="odd">{if !empty($categ_perms)}{tr}No individual permissions, category permissions apply{/tr}{else}{tr}No individual permissions, category permissions apply{/tr}{/if}</td></tr>
{/section}
</table>

<br/>

<h2>{tr}Current permissions for categories that this object belongs to{/tr}:</h2>
{if !empty($page_perms) && !empty($categ_perms)}
{remarksbox type="warning" title="{tr}Warning{/tr}"}{tr}These permissions do not apply. Special permissions apply.{/tr}
{/remarksbox}
{/if}
<table class="normal">
<tr>
	<th>{tr}Permission{/tr}</th>
	<th>{tr}Group{/tr}</th>
	<th>{tr}Category{/tr}</th>
</tr>
{cycle print=false values="even,odd"}
{section  name=x loop=$categ_perms}
	{section name=y loop=$categ_perms[x]}
<tr class="{cycle advance=true}">
  <td class="{cycle advance=false}">{$categ_perms[x][y].permName|escape}<br /><i>{$categ_perms[x][y].permDesc}</i></td>
  <td class="{cycle advance=false}">{$categ_perms[x][y].groupName|escape}</td>
  <td class="{cycle advance=false}">{$categ_perms[x][0].catpath}</td>
</tr>
	{/section}
{sectionelse}
<tr><td colspan="3">{if empty($page_perms)}{tr}No category permissions; global permissions apply{/tr}{else}{tr}No category permissions; special permissions apply{/tr}{/if}</td></tr>
{/section}
</table>
{/tab}

{tab name='{tr}Edit Permissions{/tr}'}

{if $prefs.feature_tabs neq 'y'}
	<h2>{tr}Edit Permissions{/tr}</h2>
{/if}
<form method="post" action="tiki-objectpermissions.php{if $filegals_manager neq ''}?filegals_manager={$filegals_manager|escape}{/if}">
{if $filegals_manager eq ''}
{remarksbox type="warning" title="{tr}Warning{/tr}"}{tr}These permissions override any global permissions or category permissions affecting this object.{/tr}<br />
{if $tiki_p_admin eq 'y'}{tr}To edit global permissions <a class="rbox-link" href="tiki-admingroups.php">click here</a>.{/tr}{/if}
{/remarksbox}
{/if}
<h2>{tr}Current permissions for this object{/tr}</h2>
<table class="normal">
<tr>
	<th>
		{if $page_perms}
			{select_all checkbox_names='checked[]'}
		{/if}
	</th>
	<th>{tr}Permissions{/tr}</th>
	<th>{tr}Groups{/tr}</th>
	<th style="width:20px">{tr}Action{/tr}</th>
</tr>
{cycle values="odd,even" print=false}
{section  name=pg loop=$page_perms}
<tr>
<td class="{cycle advance=false}">
	<input type="checkbox" name="checked[]" value="{$page_perms[pg].permName|cat:' '|cat:$page_perms[pg].groupName|escape}" />
</td>
<td class="{cycle advance=false}">
	{$page_perms[pg].permName|escape}<br /><i>{tr}{$page_perms[pg].permDesc|escape}{/tr}</i>
</td>
<td class="{cycle advance=false}">
	{$page_perms[pg].groupName}
</td>
<td class="{cycle advance=true}"><a class="link" href="tiki-objectpermissions.php?referer={$referer|escape:"url"}&amp;action=remove&amp;objectName={$objectName}&amp;objectId={$objectId}&amp;objectType={$objectType}&amp;permType={$permType}&amp;page={$page|escape:"url"}&amp;perm={$page_perms[pg].permName}&amp;group={$page_perms[pg].groupName}{if $filegals_manager neq ''}&amp;filegals_manager={$filegals_manager|escape}{/if}" title="{tr}Delete{/tr}">{icon _id='cross' alt="{tr}Delete{/tr}"}</a></td></tr>
{sectionelse}
<tr><td colspan="4" class="odd">{if !empty($categ_perms)}{tr}No individual permissions, category permissions apply{/tr}{else}{tr}No individual permissions, category permissions apply{/tr}{/if}</td></tr>
{/section}
</table>

{if $page_perms}
	<div>
		{tr}Perform action with checked:{/tr} 
		<input type="image" name="delsel" src='pics/icons/cross.png' alt='{tr}Delete{/tr}' title='{tr}Delete{/tr}' />
		{if isset($inStructure)}
			{tr}and also to all pages of the sub-structure:{/tr} <input name="removestructure" type="checkbox" />
		{/if}
	</div>
{/if}

<br/>

<h2>{tr}Assign permissions to this object{/tr}</h2>

<input type="hidden" name="page" value="{$page|escape}" />
<input type="hidden" name="referer" value="{$referer|escape}" />
<input type="hidden" name="objectName" value="{$objectName|escape}" />
<input type="hidden" name="objectType" value="{$objectType|escape}" />
<input type="hidden" name="objectId" value="{$objectId|escape}" />
<input type="hidden" name="permType" value="{$permType|escape}" />
<div class="input_submit_container" style="text-align: center">
	<input type="submit" name="assign" value="{tr}Assign{/tr}" />
</div>

<table class="normal">
	<tr>
		<th>{tr}Permissions{/tr}</th>
		<th>{tr}Groups{/tr}</th>
	</tr>
<tr>
<td><table width="100%">
{cycle print=false values="even,odd"}
{section name=prm loop=$perms}
<tr class="{cycle advance=true}">
  <td class="{cycle advance=false}" title="{$perms[prm].permName|escape}">
    <input type="checkbox" name="perm[]" value="{$perms[prm].permName|escape}" title="{$perms[prm].permName|escape}"/>
  </td>
  <td class="{cycle advance=false}">
    {$perms[prm].permName|escape}
  </td>
  <td class="{cycle advance=false}">
    <div class="subcomment">{tr}{$perms[prm].permDesc|escape}{/tr}</div>
  </td>
  </tr>
{/section}
</table></td>
<td><table width="100%">
{cycle print=false values="even,odd"}
{section name=grp loop=$groups}
<tr class="{cycle advance=true}">
  <td class="{cycle advance=false}"><input type="checkbox" name="group[]" value="{$groups[grp].groupName|escape}" {if $groupName eq $groups[grp].groupName }checked{/if}/>&nbsp;{$groups[grp].groupName|escape}</td></tr>
{/section}
</table></td></tr>
</table>
<div class="input_submit_container" style="text-align: center">
	<input type="submit" name="assign" value="{tr}Assign{/tr}" />
</div>
{if ($objectType eq 'wiki' or $objectType eq 'wiki page') and !empty($inStructure)}
{tr}and also to all pages of the sub-structure:{/tr} <input name="assignstructure" type="checkbox" />
{/if}
<br /><br />

{button href="#" _onclick="javascript:flip('edithelpzone'); return false;" _text="{tr}Perms Help{/tr}"}

<div class="wiki-edithelp"  id='edithelpzone' >
{cycle print=false values="even,odd"}
<table class="normal">
{section name=prm loop=$perms}
<tr><td class="{cycle advance=false}">{$perms[prm].permName}</td><td class="{cycle}">{tr}{$perms[prm].permDesc}{/tr}</td></tr>
{/section}
</table>

</div>
</form>
{/tab}
{/tabset}
