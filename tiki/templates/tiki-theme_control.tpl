{*Smarty template*}
<a class="pagetitle" href="tiki-theme_control.php">{tr}Theme Control Center: categories{/tr}</a>

<!-- the help link info -->
  
      {if $feature_help eq 'y'}
<a href="http://tikiwiki.org/tiki-index.php?page=ThemeControl" target="tikihelp" class="tikihelp" title="{tr}Tikiwiki.org help{/tr}: {tr}ThemeControl{/tr}">
<img border='0' src='img/icons/help.gif' alt='{tr}help{/tr}' /></a>{/if}

<!-- link to tpl -->

      {if $feature_view_tpl eq 'y'}
<a href="tiki-edit_templates.php?template=templates/tiki-theme_control.tpl" target="tikihelp" class="tikihelp" title="{tr}View tpl{/tr}: {tr}ThemeControl tpl{/tr}">
<img border='0' src='img/icons/info.gif' alt='edit tpl' /></a>{/if}

<!-- begin -->

<br /><br />
<div class="simplebox">
<b>{tr}Theme is selected as follows{/tr}:</b><br />
1. {tr}If a theme is assigned to the individual object that theme is used.{/tr}<br />
2. {tr}If not then if a theme is assigned to the object's category that theme is used{/tr}<br />
3. {tr}If not then a theme for the section is used{/tr}<br />
4. {tr}If none of the above was selected the user theme is used{/tr}<br />
5. {tr}Finally if the user didn't select a theme the default theme is used{/tr}<br />
</div>
<br /><br />
<a class="linkbut" href="tiki-theme_control_objects.php">{tr}Control by Objects{/tr}</a>
 <a class="linkbut" href="tiki-theme_control_sections.php">{tr}Control by Sections{/tr}</a>
<h2>{tr}Assign themes to categories{/tr}</h2>
<form action="tiki-theme_control.php" method="post">
<table class="normal">
<tr>
  <td class="formcolor">{tr}Category{/tr}</td>
  <td class="formcolor">{tr}Theme{/tr}</td>
  <td class="formcolor">&nbsp;</td>
</tr>
<tr>
  <td class="formcolor">
    <select name="categId">
      {section name=ix loop=$categories}
      <option value="{$categories[ix].categId|escape}">{$categories[ix].name}</option>
      {/section}
    </select>
  </td>
  <td class="formcolor">
    <select name="theme">
      {section name=ix loop=$styles}
      <option value="{$styles[ix]|escape}">{$styles[ix]}</option>
      {/section}
    </select>
  </td>
  <td class="formcolor">
    <input type="submit" name="assigcat" value="{tr}assign{/tr}" />
  </td>
</tr>
</table>
</form> 

<h2>{tr}Assigned categories{/tr}</h2>
<table class="findtable">
<tr><td class="findtable">{tr}Find{/tr}</td>
   <td class="findtable">
   <form method="get" action="tiki-theme_control.php">
     <input type="text" name="find" value="{$find|escape}" />
     <input type="submit" value="{tr}find{/tr}" name="search" />
     <input type="hidden" name="sort_mode" value="{$sort_mode|escape}" />
   </form>
   </td>
</tr>
</table>
<form action="tiki-theme_control.php" method="post">
<table class="normal">
<tr>
<td class="heading"><input type="submit" name="delete" value="{tr}del{/tr}" /></td>
<td class="heading" ><a class="tableheading" href="tiki-theme_control.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}category{/tr}</a></td>
<td class="heading" ><a class="tableheading" href="tiki-theme_control.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'theme_desc'}theme_asc{else}theme_desc{/if}">{tr}theme{/tr}</a></td>
</tr>
{cycle values="odd,even" print=false}
{section name=user loop=$channels}
<tr>
<td class="{cycle advance=false}">
<input type="checkbox" name="categ[{$channels[user].categId}]" />
</td>
<td class="{cycle advance=false}">{$channels[user].name}</td>
<td class="{cycle}">{$channels[user].theme}</td>
</tr>
{/section}
</table>
</form>
<div class="mini">
<div align="center">
{if $prev_offset >= 0}
[<a class="prevnext" href="tiki-theme_control.php?find={$find}&amp;offset={$prev_offset}&amp;sort_mode={$sort_mode}">{tr}prev{/tr}</a>]&nbsp;
{/if}
{tr}Page{/tr}: {$actual_page}/{$cant_pages}
{if $next_offset >= 0}
&nbsp;[<a class="prevnext" href="tiki-theme_control.php?find={$find}&amp;offset={$next_offset}&amp;sort_mode={$sort_mode}">{tr}next{/tr}</a>]
{/if}
{if $direct_pagination eq 'y'}
<br />
{section loop=$cant_pages name=foo}
{assign var=selector_offset value=$smarty.section.foo.index|times:$maxRecords}
<a class="prevnext" href="tiki-theme_control.php?tasks_useDates={$tasks_useDates}&amp;find={$find}&amp;offset={$selector_offset}&amp;sort_mode={$sort_mode}">
{$smarty.section.foo.index_next}</a>&nbsp;
{/section}
{/if}
</div>
</div>
