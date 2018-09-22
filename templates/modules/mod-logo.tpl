{* $Id$ *}
{strip}
	{tikimodule error=$module_params.error title=$tpl_module_title name="logo" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
		{if $module_params.src}
			<div {if $module_params.bgcolor ne ''} style="background-color: {$module_params.bgcolor};"{/if} class="position-relative float-left {$module_params.class_image|escape}">
				<a href="{$module_params.link}" title="{$module_params.title_attr|escape}">
					<img src="{$module_params.src}" alt="{$module_params.alt_attr|escape}" style="max-width: 100%; height: auto">
				</a>
				{if $tiki_p_admin eq "y"}<a class="btn btn-primary btn-sm bottom mb-3 ml-1 mr-1 mt-3 position-absolute opacity50 tips" href="tiki-admin.php?page=look&cookietab=2&highlight=sitelogo_src#feature_sitelogo_childcontainer" style="top: 0; right: 0" title="{tr}Change the logo{/tr}: {tr}Click to change or upload new logo{/tr}">{icon name="image"}</a>{/if}
			</div>
		{/if}
		{if !empty($module_params.sitetitle) or !empty($module_params.sitesubtitle)}
			<div class="float-left {$module_params.class_titles|escape}">
				{if !empty($module_params.sitetitle)}
					<h1 class="sitetitle">
						<a href="{$module_params.link}">
							{tr}{$module_params.sitetitle|escape}{/tr}
						</a>
					</h1>
				{/if}
				{if !empty($module_params.sitesubtitle)}
					<h2 class="sitesubtitle">{tr}{$module_params.sitesubtitle|escape}{/tr}</h2>
				{/if}
			</div>
		{/if}
	{/tikimodule}
{/strip}
