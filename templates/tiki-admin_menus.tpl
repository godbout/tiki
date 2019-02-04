{* $Id$ *}
{title help="Menus" admpage="general&amp;cookietab=3"}{tr}Menus{/tr}{/title}

{if $tiki_p_admin eq 'y'}
	<div class="t_navbar mb-4">
		<a class="btn btn-primary" href="{bootstrap_modal controller=menu action=edit}">
			{icon name="create"} {tr}Create Menu{/tr}
		</a>
		{button href="tiki-admin_modules.php" _icon_name="modules" _type="link" _text="{tr}Modules{/tr}"}
	</div>
{/if}
{include file='find.tpl'}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-hover">
		<tr>
			<th>{self_link _sort_arg='sort_mode' _sort_field='menuId'}{tr}ID{/tr}{/self_link}</th>
			<th>{self_link _sort_arg='sort_mode' _sort_field='name'}{tr}Name{/tr}{/self_link}</th>
			<th>{self_link _sort_arg='sort_mode' _sort_field='type'}{tr}Type{/tr}{/self_link}</th>
			<th>{tr}Options{/tr}</th>
			<th></th>
		</tr>

		{section name=user loop=$channels}
			<tr>
				<td class="id">{$channels[user].menuId}</td>
				<td class="text">
					{if $tiki_p_edit_menu_option eq 'y' and $channels[user].menuId neq 42}
						<a class="link tips" href="tiki-admin_menu_options.php?menuId={$channels[user].menuId}" title=":{tr}Menu Options{/tr}">{$channels[user].name|escape}</a>
					{else}
						{$channels[user].name|escape}
					{/if}
					<span class="form-text">
						{$channels[user].description|escape|nl2br}
					</span>
				</td>
				<td class="text">{$channels[user].type}</td>
				<td><span class="badge badge-secondary">{$channels[user].options}</span></td>
				<td class="action">
					{actions}
						{strip}
							{if $channels[user].menuId neq 42}
								{if $tiki_p_edit_menu eq 'y'}
									<action>
										<a href="{bootstrap_modal controller=menu action=edit menuId=$channels[user].menuId}">
											{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
										</a>
									</action>
								{/if}
								{if $tiki_p_edit_menu_option eq 'y'}
									<action>
										<a href="tiki-admin_menu_options.php?menuId={$channels[user].menuId}">
											{icon name="list" _menu_text='y' _menu_icon='y' alt="{tr}Menu options{/tr}"}
										</a>
									</action>
								{/if}
								{if $tiki_p_edit_menu eq 'y'}
									<action>
										<a href="{bootstrap_modal controller=menu action=remove menuId=$channels[user].menuId}">
											{icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
										</a>
									</action>
								{/if}
							{else}
								{if $tiki_p_admin eq 'y'}
									<action>
										{button reset="y" menuId=$channels[user].menuId _text="{tr}RESET{/tr}" _auto_args="reset,menuId" _class="btn btn-warning btn-sm"}
									</action>
									<hr>
								{/if}
							{/if}
							{if $tiki_p_edit_menu eq 'y'}
								<action>
									<a href="{bootstrap_modal controller=menu action=clone menuId=$channels[user].menuId}">
										{icon name="copy" _menu_text='y' _menu_icon='y' alt="{tr}Clone{/tr}"}
									</a>
								</action>
							{/if}
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=5}
		{/section}
	</table>
</div>
{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
