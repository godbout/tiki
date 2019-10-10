{* $Id$ *}
{strip}
	{tikimodule error=$module_params.error title=$tpl_module_title name="switch_lang" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
		{if $mode eq 'flags'}
			<div class="flags">
				{foreach $languages as $flagarray}
					{$val=$flagarray.value|escape}
					{$langname=$flagarray.name|escape}
					{$flag=$flagarray.flag|escape}
					{$class=$flagarray.class|escape}
					{if $flag neq ''}
						{icon href="tiki-switch_lang.php?language=$val" alt="$langname" title="$langname" _id="img/flags/$flag.png" height=11 class="icon $class"}
					{else}
						{button _text="$langname" href="tiki-switch_lang.php?language=$val" _title="$langname" _class="$class"}
					{/if}
				{/foreach}
			</div>
		{elseif $mode eq 'words' || $mode eq 'abrv'}
			<ul>
				{section name=ix loop=$languages}
					<li>
						{if $mode eq 'words'}
							<a title="{$languages[ix].name|escape}" class="linkmodule {$languages[ix].class}" href="tiki-switch_lang.php?language={$languages[ix].value|escape}">
								{$languages[ix].name|escape}
							</a>
						{else}
							<a title="{$languages[ix].name|escape}" class="linkmodule {$languages[ix].class}" href="tiki-switch_lang.php?language={$languages[ix].value|escape}">
								{$languages[ix].value|escape}
							</a>
						{/if}
					</li>
				{/section}
			</ul>
		{else}{* do menu as before is not flags or words *}
			<form method="get" action="tiki-switch_lang.php" target="_self">
				<select name="language" size="1" onchange="this.form.submit();" class="form-control">
					{section name=ix loop=$languages}
						<option value="{$languages[ix].value|escape}"
							{if $frontendLang eq $languages[ix].value} selected="selected"{/if}>
							{$languages[ix].name}
						</option>
					{/section}
				</select>
			</form>
		{/if}
	{/tikimodule}
{/strip}
