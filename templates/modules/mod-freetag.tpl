{* $Id$ *}

{if $prefs.feature_freetags eq 'y' and $tiki_p_view_freetags eq 'y' and $tagid}
{if !isset($tpl_module_title)}{assign value="{tr}Folksonomy{/tr}" var="tpl_module_title"}{/if}
{tikimodule error=$module_params.error title=$tpl_module_title name="freetag" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

{include file="freetag_list.tpl" deleteTag="y"}

{if $tiki_p_freetags_tag eq 'y'}
{if !empty($freetag_error)}{$freetag_error}{/if}
<form name="addTags" method="post" action="{$smarty.server.REQUEST_URI}">
<input type="text" name="addtags"{if !empty($freetag_msg)} value="{$freetag_msg}"{/if} maxlength="40" />
{if $prefs.feature_antibot eq 'y' && $user eq ''}
<table>{include file="antibot.tpl"}</table>
{/if}
<input type="submit" name="Add" value="Add" />
{help url="Tags" desc='{tr}Put tags separated by spaces. For tags with more than one word, use no spaces and put words together or enclose them with double quotes{/tr}'}
</form>
{/if}
{/tikimodule}
{/if}
