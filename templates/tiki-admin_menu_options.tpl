{* $Id$ *}
{title help="Menus" url="tiki-admin_menu_options.php?menuId=$menuId" admpage="general&amp;cookietab=3"}{tr}Menu:{/tr} {$editable_menu_info.name}{/title}

<div class="t_navbar mb-4">
	<a class="btn btn-link" href="tiki-admin_menus.php">
		{icon name="list"} {tr}List Menus{/tr}
	</a>
	{if $tiki_p_edit_menu eq 'y'}
		<a class="btn btn-primary" href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId}">
			{icon name="create"} {tr}Create menu option{/tr}
		</a>
		<a class="btn btn-primary" href="{bootstrap_modal controller=menu action=edit menuId=$menuId}">
			{icon name="edit"} {tr}Edit This Menu{/tr}
		</a>
		<a class="btn btn-primary" href="{service controller=menu action=export_menu_options menuId=$menuId}" title="{tr}Export menu options{/tr}">
			{icon name="export"} {tr}Export{/tr}
		</a>
		<a class="btn btn-primary no-ajax" href="{bootstrap_modal controller=menu action=import_menu_options menuId=$menuId}" title="{tr}Import menu options{/tr}">
			{icon name="import"} {tr}Import{/tr}
		</a>
	{/if}
</div>

{tabset name="admin_menu_options"}
{tab name="{tr}Manage menu{/tr} {$editable_menu_info.name}"}
	<div>
		<h2>{tr}Menu options{/tr} <span class="badge badge-secondary">{$cant_pages}</span></h2>

		<div class="navbar mb-4 clearfix">
			{button _text='{tr}Save Options{/tr}' _class='save_menu  btn btn-sm disabled float-left mb-2' _type='primary' _ajax='n' _auto_args='save_menu,page_ref_id'}
			<ol class="new-option">
				<li id="node_new" class="clearfix new row">
					<div class="col-sm-12">
						<div class="float-left label-group">
							<div class="input-group input-group-sm" style="max-width: 100%">
								<div class="input-group-append">
									<span class="input-group-text">{icon name='sort'}</span>
								</div>
								<input type="text" class="field-label form-control" value="" placeholder="{tr}New option{/tr}" readonly="readonly">
								<div class="input-group-append">
									<span class="tips input-group-text option-edit" title="|{tr}Check this if the option is an alternative to the previous one.{/tr}">
										<input type="checkbox" class="samepos">
										{$prevpos = $option.position}
									</span>
									<a href="javascript:void(0)" class="tips input-group-text " title="{tr}New option{/tr}|{tr}Drag this on to the menu area below{/tr}">
										{icon name='info'}
									</a>
								</div>
							</div>
						</div>
						<div class="float-left url-group hidden">
							<div class="input-group input-group-sm">
								<div class="input-group-prepend">
									<a href="javascript:void(0)" class="input-group-text" onclick='return false;'>
										{icon name='link'}
									</a>
								</div>
								<input type="text" class="field-url form-control" value="" placeholder="{tr}URL or ((page name)){/tr}">
								<div class="input-group-append">
									<a href="javascript:void(0)" class="input-group-text  option-edit">
										{icon name='edit' _menu_icon='y' alt="{tr}Details{/tr}"}
									</a>
									<a href="javascript:void(0)" class="input-group-text text-danger option-remove" disabled="disabled">
										{icon name='remove' _menu_icon='y' alt="{tr}Remove{/tr}"}
									</a>
								</div>
							</div>
						</div>
					</div>
				</li>
			</ol>
		</div>
		<form method="get" action="tiki-admin_menu_options.php">
			{ticket}
			<input type="hidden" name="find" value="{$find|escape}">
			<input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
			<input type="hidden" name="menuId" value="{$menuId}">
			<input type="hidden" name="offset" value="{$offset}">

			<div class="options-container">
				<ol id="options">
					{$prevpos = 0}
					{foreach $options as $option}
						<li class="row clearfix" id="node_{$option.optionId}" data-position="{$option.position}" data-parent="{$option.parent}" data-type="{$option.type}">
							<div class="col-sm-12">
								{if $option.name}
									{capture assign='tooltip'}{strip}
										{if $editable_menu_info.parse eq 'y'}
											{wiki}{$option.name}{/wiki}
										{else}
											{$option.name|escape}
										{/if}
										|
										<dl>
											{if $option.url}
												<dt>{tr}URL:{/tr}</dt>
												<dd>{$option.canonic|truncate:40:' ...'|escape}</dd>
											{/if}
											{if $option.section}
												<dt>{tr}Sections:{/tr}</dt>
												<dd>{$option.section}</dd>
											{/if}
											{if $option.perm}
												<dt>{tr}Permissions:{/tr}</dt>
												<dd>{$option.perm}</dd>
											{/if}
											{if $option.groupname}
												<dt>{tr}Groups:{/tr}</dt>
												<dd>{$option.groupname|escape}</dd>
											{/if}
											{if $option.class}
												<dt>{tr}Class:{/tr}</dt>
												<dd>{$option.class|escape}</dd>
											{/if}

											{if $prefs.feature_userlevels eq 'y' and not empty($option.userlevel)}
												{assign var=it value=$option.userlevel}
												<dt>{tr}User Level:{/tr}</dt>
												<dd>{$prefs.userlevels.$it}</dd>
											{/if}
											{if $prefs.menus_items_icons eq 'y' and $option.icon}
												<dt>{tr}Icon:{/tr}</dt>
												<dd>
													{if $prefs.theme_iconset eq 'legacy'}
														{icon _id=$option.icon _defaultdir=$prefs.menus_items_icons_path}
													{else}
														{icon name=$option.icon|replace:'48x48':''}{* remove size for legacy menu 42 icons *}
													{/if}
													&nbsp;
													{$option.icon|escape}
												</dd>
											{/if}
										</dl>
									{/strip}{/capture}
								{else}
									{$tooltip = "|{tr}separator{/tr}"}
								{/if}

								<div class="float-left label-group mr-4">
									<div class="input-group input-group-sm">
										<div class="input-group-append">
											<span class="input-group-text">{icon name='sort'}</span>
										</div>
										<input type="text" class="field-label form-control" value="{$option.name|escape}" placeholder="{tr}Label{/tr}">
										<div class="input-group-append">
											<span class="tips input-group-text option-edit" title="|{tr}Check this if the option is an alternative to the previous one.{/tr}">
												<input type="checkbox" class="samepos"{if $option.position eq $prevpos} checked="checked"{/if}>
												{$prevpos = $option.position}
											</span>
											<a href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId optionId=$option.optionId}" class="tips input-group-text" title='{$tooltip|escape}'>
												{icon name='info'}
											</a>
										</div>
									</div>
								</div>
								<div class="float-left url-group">
									<div class="input-group input-group-sm">
										<div class="input-group-append">
										<a href="{$option.sefurl|escape}" class="input-group-text tips confirm" title="|{tr}Test URL{/tr}">
											{icon name='link'}
										</a>
										</div>
										<input type="text" class="field-url form-control" value="{$option.canonic|escape}" placeholder="{tr}URL or ((page name)){/tr}">
										<div class="input-group-append">
											<a href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId optionId=$option.optionId}" class="tips input-group-text option-edit confirm" title="|{tr}Details{/tr}">
												{icon name='edit' _menu_icon='y' alt="{tr}Details{/tr}"}
											</a>
											<a href="#" class="tips input-group-text text-danger option-remove" title="|{tr}Remove Option{/tr}">
												{icon name='remove' _menu_icon='y' alt="{tr}Remove{/tr}"}
											</a>
										</div>
									</div>
								</div>
							</div>
							<ol class="child-options"></ol>
						</li>
					{foreachelse}

					{/foreach}
					{capture name='options'}select:function(event,ui){ldelim}ui.item.value='(('+ui.item.value+'))';{rdelim}{/capture}
					{autocomplete element='.field-url' type='pagename' options=$smarty.capture.options}
				</ol>
			</div>

		</form>

		{button _text='{tr}Save Options{/tr}' _class='save_menu  btn btn-sm disabled' _type='primary' _ajax='n' _auto_args='save_menu,page_ref_id'}

	</div>
{/tab}
{tab name="{tr}Preview and Deploy{/tr}"}
	<h2>{tr}Preview menu{/tr}</h2>


			<form action="{service controller='menu' action='preview'}" class="form-inline preview mb-4">
				<input type="hidden" name="menuId" value="{$menuId}">
				<div class="form-group col-sm-3">
					<label for="preview_type" class="col-form-label mr-2">{tr}Type:{/tr}</label>
					<select id="preview_type" class="form-control" name="preview_type">
						<option value="vert"{if $preview_type eq 'vert'} selected{/if}>{tr}Vertical{/tr}</option>
						<option value="horiz"{if $preview_type eq 'horiz'} selected{/if}>{tr}Horizontal{/tr}</option>
					</select>
				</div>
				<div class="col-sm-2">
					<div class="form-check">
						<label for="preview_bootstrap" class="form-check-label">
							Bootstrap
						</label>
						<input type="checkbox" id="preview_bootstrap" class="form-check-input ml-2" name="preview_bootstrap"{if $preview_bootstrap eq 'y'} checked="checked"{/if}>
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-check">
						<label for="preview_css" class="form-check-label">
							CSS
						</label>
						<input type="checkbox" id="preview_css" class="form-check-input ml-2" name="preview_css"{if $preview_css eq 'y'} checked="checked"{/if}>
					</div>
				</div>
				<div class="form-group col-sm-3">
					<label for="preview_position" class="col-form-label mr-2">
						{tr}Position{/tr}
					</label>
					<select id="preview_position" class="form-control">
						{foreach from=$module_zone_list key=code item=zone}
							<option value="{$code|escape}">{$zone.name|escape}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-sm-2">
					{button _text='{tr}Deploy{/tr}' _class='deploy_menu btn btn-primary' _type='default' _ajax='n'}
				</div>
			</form>


	<div class="preview-menu">
		&nbsp;
	</div>
{/tab}
{/tabset}
