{* $Header: /cvsroot/tikiwiki/tiki/templates/modules/mod-last_modif_pages.tpl,v 1.16 2003-11-24 00:07:24 zaufi Exp $ *}

{if $feature_wiki eq 'y'}
{tikimodule title="{tr}Last changes{/tr}" name="last_modif_pages"}
   <table  border="0" cellpadding="0" cellspacing="0">
    {section name=ix loop=$modLastModif}
     <tr>
      {if $nonums != 'y'}
        <td class="module" valign="top">{$smarty.section.ix.index_next})</td>
      {/if}
      <td class="module">&nbsp;
       <a class="linkmodule" href="tiki-index.php?page={$modLastModif[ix].pageName|escape:"url"}" title="{$modLastModif[ix].lastModif|tiki_short_datetime}, by {$modLastModif[ix].user}{if (strlen($modLastModif[ix].pageName) > $maxlen) && ($maxlen > 0)}, {$modLastModif[ix].pageName}{/if}">
        {if $maxlen > 0}{* 0 is default value for maxlen eq to 'no truncate' *}
         {$modLastModif[ix].pageName|truncate:$maxlen:"...":true}
        {else}
         {$modLastModif[ix].pageName}
        {/if}
       </a>
      </td>
     </tr>
    {/section}
   </table>
{/tikimodule}
{/if}
