{* $Header: /cvsroot/tikiwiki/tiki/templates/modules/mod-user_tasks.tpl,v 1.17 2007-10-04 22:17:47 nyloth Exp $ *}
{if $prefs.feature_tasks eq 'y' and $user}
{if !isset($tpl_module_title)}{assign var=tpl_module_title value="<a href='tiki-user_tasks.php'>{tr}User tasks{/tr}</a>"}{/if}
{tikimodule title=$tpl_module_title name="user_tasks" flip=$module_params.flip decorations=$module_params.decorations}
<table  border="0" cellpadding="0" cellspacing="0" width="100%">
<tr><td width="100%">
<form action="{$ownurl}" method="post">
<input style="font-size: 9px;" type="text" name="modTasksTitle" />
<input style="font-size: 9px;" type="submit" name="modTasksSave" value="{tr}Add{/tr}" />
</form>
</td></tr>
</table>
<form action="{$ownurl}" method="post">
<table  class="normal">
{* <table  border="0" cellpadding="0" cellspacing="0" width="100%"> *}
{section name=ix loop=$modTasks}
<tr><td width="100%" class="prio{$modTasks[ix].priority}">
{if $modTasks[ix].creator ne $user } 
&gt;&gt; 
{else}
<input  type="checkbox" name="modTasks[{$modTasks[ix].taskId}]" />
{/if}
{$modTasks[ix].taskId|tasklink:linkmodule} ({$modTasks[ix].percentage}%)</td></tr>
{sectionelse}
<tr><td class="module">&nbsp;</td></tr>
{/section}
</table>
<input style="font-size: 9px;" type="submit" name="modTasksCom" value="{tr}done{/tr}" />
<input style="font-size: 9px;" type="submit" name="modTasksDel" value="{tr}del{/tr}" />
</form>
{/tikimodule}
{/if}
