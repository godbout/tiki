{* Display the list of available translations for an object and manage its translations *}
{** Currently works for the following object types: 'article' and 'wiki page' **}
{strip}
{if empty($submenu) || $submenu neq 'y'}
	<div class="btn-group">
		{if $prefs.lang_available_translations_dropdown neq 'y' }
			{* For all object types: First show the translate icon and on hover the language of the current object *}
			{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
			<a href="#" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" title="{tr}Translations{/tr}">
				{icon name="translate"}
			</a>
		{else}
			<div class="dropdown">
				{* For all object types: Show everything as a dropdown for visibility *}
				{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
				<button class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown">
					{icon name="translate"} {$trads[0].langName|escape} ({$trads[0].lang|escape})
				</button>
		{/if}
{else}
	{if ! $js}<ul class="cssmenu_horiz"><li>{/if}
	<a tabindex="-1" href="#">
		{icon name="translate"} {tr}Translation...{/tr}
	</a>
{/if}
	{* ..than on hover first show the list of translations including the current language highlighted *}
	{if empty($trads[0].lang)}
		<div class="dropdown-menu dropdown-menu-right" role="menu">
			<h6 class="dropdown-header">
				{tr}Translations{/tr}
			</h6>
			<div role="separator" class="dropdown-divider"></div>
			<div class="dropdown-item">
				<em>{tr}No language assigned{/tr}</em>
			</div>
			<div role="separator" class="dropdown-divider"></div>
			{if $object_type eq 'wiki page' and ($tiki_p_edit eq 'y' or (!$user and $prefs.wiki_encourage_contribution eq 'y')) and !$lock}
				<a class="dropdown-item" href="tiki-edit_translation.php?page={$page|escape}">
					{tr}Set page language{/tr}
				</a>
			{elseif $object_type eq 'article' and $tiki_p_edit_article eq 'y'}
				<a class="dropdown-item" href="tiki-edit_article.php?articleId={$articleId|escape}">
					{tr}Set article language{/tr}
				</a>
				<div role="separator" class="dropdown-divider"></div>
			{/if}
		</div>
	{else}
		<div class="dropdown-menu dropdown-menu-right" role="menu">
			<h6 class="dropdown-header">
				{tr}Translations{/tr}
			</h6>
			<div role="separator" class="dropdown-divider"></div>
			{* First the language of the object *}
			{if $object_type eq 'wiki page'}
				<a href="tiki-index.php?page={$trads[0].objName|escape:url}&amp;no_bl=y" class="dropdown-item tips selected" title="{tr}Current language:{/tr} {$trads[0].objName}">
					<em>{$trads[0].langName|escape} ({$trads[0].lang|escape})</em>
				</a>
			{elseif $object_type eq 'article'}
				<a href="tiki-read_article.php?articleId={$trads[0].objId}" title="{tr}Current language:{/tr} {$trads[0].objName}" class="dropdown-item tips selected">
					<em>{$trads[0].langName|escape} ({$trads[0].lang|escape})</em>
				</a>
			{/if}
			{* Show the list of available translations *}
			{section name=i loop=$trads}
				{* For wiki pages *}
				{if $object_type eq 'wiki page' and $trads[i] neq $trads[0]}
					<a href="tiki-index.php?page={$trads[i].objName|escape}&no_bl=y" title="{tr}View:{/tr} {$trads[i].objName}" class="dropdown-item tips {$trads[i].class}">
						{$trads[i].langName|escape} ({$trads[i].lang|escape})
					</a>
				{/if}
				{* For articles *}
				{if $object_type eq 'article' and $trads[i] neq $trads[0]}
					<a  href="tiki-read_article.php?articleId={$trads[i].objId}" title="{tr}View:{/tr} {$trads[i].objName}" class="dropdown-item tips {$trads[i].class}">
						{$trads[i].langName|escape} ({$trads[i].lang|escape})
					</a>
				{/if}
			{/section}
			{* For wiki pages only: Show a link to view all translations on a single page *}
			{if $object_type eq 'wiki page' and $prefs.feature_multilingual_one_page eq 'y' and $translationsCount gt 1}
				<div role="separator" class="dropdown-divider"></div>
				<a href="tiki-all_languages.php?page={$trads[0].objName|escape:url}&no_bl=y" title=":{tr}Show all translations of this page on a single page{/tr}" class="dropdown-item tips">
					{tr}All languages{/tr}
				</a>
			{/if}
			{* For wiki pages only: List of machine translation candidates if feature is switched on *}
			{if $object_type eq 'wiki page' and $prefs.feature_machine_translation eq 'y'}
				<div role="separator" class="dropdown-divider"></div>
				<h6 class="dropdown-header">
					{tr}Machine translations{/tr}
				</h6>
			{* List machine translation candidates for available language of the site *}
				{foreach from=$langsCandidatesForMachineTranslation item=mtl}
					<a href="tiki-index.php?machine_translate_to_lang={$mtl.lang|escape}&page={$page|escape:"quotes"}&no_bl=y" title="{$mtl.langName|escape} ({$mtl.lang|escape})" class="dropdown-item tips">
						{$mtl.langName|escape} *
					</a>
				{/foreach}
			{/if}
			{* Translation maintenance *}
			{capture}
				{if $object_type eq 'wiki page' and $tiki_p_edit eq 'y'}
					<div role="separator" class="dropdown-divider"></div>
					<a class="dropdown-item tips" href="tiki-edit_translation.php?page={$trads[0].objName|escape:url}&amp;no_bl=y" title=":{tr}Translate page{/tr}">
						{tr}Translate{/tr}
					</a>
					<a href="{bootstrap_modal controller=translation action=manage type='wiki page' source=$page}" class="dropdown-item attach_detach_translation tips" data-object_type="wiki page" data-object_id="{$page|escape:'quotes'}" title=":{tr}Manage page translations{/tr}">
						{tr}Manage translations{/tr}
					</a>
				{elseif $object_type eq 'article' and $tiki_p_edit_article eq 'y'}
					<div role="separator" class="dropdown-divider"></div>
					<a class="dropdown-item" href="tiki-edit_article.php?translationOf={$articleId}" title="{tr}Translate article{/tr}">
						{tr}Translate{/tr}
					</a>
					<a href="{bootstrap_modal controller=translation action=manage type=article source=$articleId}" class="dropdown-item attach_detach_translation tips" data-object_id="{$articleId|escape:'quotes'}" data-object_type="article" title="{tr}Manage article translations{/tr}">
						{tr}Manage translations{/tr}
					</a>
				{/if}
			{/capture}
			{if !empty($smarty.capture.default)}{* Only display the header if there's content *}
				{$smarty.capture.default}
			{/if}
		</div>
	{/if}
	{if ! $js}</li></ul>{/if}
{if empty($submenu) || $submenu neq 'y'}
	{if $prefs.lang_available_translations_dropdown eq 'y' }
		</div>
	{/if}
	</div>
{/if}
{/strip}
