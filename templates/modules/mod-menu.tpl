{* $Id$ *}

{tikimodule error=$module_error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip|default:null decorations=$module_params.decorations|default:null nobox=$module_params.nobox|default:null notitle=$module_params.notitle|default:null type=$module_type}
	{if $module_params.bootstrap|default:null neq 'n'}
		{if $module_params.type|default:null eq 'horiz' OR !empty($module_params.navbar_brand)}
			<nav class="{if !empty($module_params.navbar_class)}{$module_params.navbar_class}{else}navbar navbar-expand-lg {/if} {if $prefs.theme_navbar_color_variant eq 'dark'}navbar-dark bg-dark {else}navbar-light bg-light{/if}" role="navigation">
				{if $module_params.navbar_brand neq ''}
					<a class="navbar-brand" href="index.php">
						<img id="logo-header" src="{$module_params.navbar_brand}" alt="Logo">
					</a>
				{/if}
				{if empty($module_params.navbar_toggle) or $module_params.navbar_toggle neq 'n'}
						<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#mod-menu{$module_position}{$module_ord} .navbar-collapse" aria-controls="mod-menu{$module_position}{$module_ord}" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>

					<div class="collapse navbar-collapse">
						{if $prefs.menus_edit_icon eq 'y' AND $tiki_p_admin eq 'y' AND $module_params.id neq '42'}
							<div class="edit-menu">
								<a href="tiki-admin_menu_options.php?menuId={$module_params.id}" title="{tr}Edit this menu{/tr}">{icon name="edit"}</a>
							</div>
						{/if}
						{menu params=$module_params bootstrap=navbar}
					</div>
				{else}
					<div>
						{menu params=$module_params bootstrap=navbar}
					</div>
				{/if}
			</nav>
		{else}
			{if $prefs.menus_edit_icon eq 'y' AND $tiki_p_admin eq 'y' AND $module_params.id neq '42'}
				<div class="edit-menu">
					<a href="tiki-admin_menu_options.php?menuId={$module_params.id}" title="{tr}Edit this menu{/tr}">{icon name="edit"}</a>
				</div>
			{/if}
			{menu params=$module_params bootstrap=basic}
		{/if}
	{else}{* non bootstrap legacy menus *}
		<div class="clearfix {if !empty($module_params.menu_class)}{$module_params.menu_class}{/if}"{if !empty($module_params.menu_id)} id="{$module_params.menu_id}"{/if}>
			{menu params=$module_params}
		</div>
	{/if}
{/tikimodule}
