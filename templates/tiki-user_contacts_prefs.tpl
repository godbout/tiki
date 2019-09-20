{title help="User Contacts Prefs"}{tr}User Contacts Preferences{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}
<div class="t_navbar mb-4">
	{button href="tiki-contacts.php" class="btn btn-info" _icon_name="users" _text="{tr}Contacts{/tr}"}
</div>

{tabset name="contact_prefs"}
	{tab name="{tr}Options{/tr}"}
		<h2>{tr}Options{/tr}</h2>
		<form method='post' action='tiki-user_contacts_prefs.php'>
			<div class="form-group row">
				<label class="col-sm-4">{tr}Default View:{/tr}</label>
				<div class="col-sm-8">
					<input type='radio' name='user_contacts_default_view' value='list' {if $user_contacts_default_view eq 'list'}checked="checked"{/if}>
					{tr}List View{/tr}
				</div>
				<div class="col-sm-8 offset-sm-4">
					<input type='radio' name='user_contacts_default_view' value='group' {if $user_contacts_default_view neq 'list'}checked="checked"{/if}>
					{tr}Group View{/tr}
				</div>
			</div>
			<div class="form-group row">
				<input type='submit' class="btn btn-primary" name='prefs' value="{tr}Change preferences{/tr}">
			</div>
		</form>
	{/tab}

	{tab name="{tr}Manage Fields{/tr}"}
		<h2>{tr}Manage Fields{/tr}</h2>
		<form method='post' action='tiki-user_contacts_prefs.php'>
			<div class="table-responsive">
				<table class="table">
					<tr>
						<th colspan="2">{tr}Order{/tr}</th>
						<th>{tr}Field{/tr}</th>
						<th></th>
					</tr>

					{foreach from=$exts item=ext key=k name=e}
						<tr>
							<td width="2%">
								{if not $smarty.foreach.e.first}
									<a href="?ext_up={$ext.fieldId}" class="tips" title=":{tr}Up{/tr}">
										{icon name='up'}</a>
								{/if}
							</td>
							<td width="2%">
								{if not $smarty.foreach.e.last}
									<a href="?ext_down={$ext.fieldId}" class="tips" title=":{tr}Down{/tr}">
										{icon name='down'}
									</a>
								{/if}
							</td>
							<td>{tr}{$ext.fieldname|escape}{/tr}</td>
							<td class="action">
								{if $ext.flagsPublic eq 'y'}
									<a href="?ext_private={$ext.fieldId}" style="margin-left:20px;" class="tips" title=":{tr}Private{/tr}">
										{icon name='user'}
									</a>
								{else}
									<a href="?ext_public={$ext.fieldId}" style="margin-left:20px;" class="tips" title=":{tr}Public{/tr}">
										{icon name='group'}
									</a>
								{/if}
								{if $ext.show eq 'y'}
									<a href="?ext_hide={$ext.fieldId}" style="margin-left:20px;" class="tips" title=":{tr}Hide{/tr}">
										{icon name='ban'}
									</a>
								{else}
									<a href="?ext_show={$ext.fieldId}" style="margin-left:20px;" class="tips" title=":{tr}Show{/tr}">
										{icon name='view'}
									</a>
								{/if}
								<a href="?ext_remove={$ext.fieldId}" style="margin-left:20px;" class="tips" title=":{tr}Remove{/tr}">
									{icon name='remove'}
								</a>
							</td>
						</tr>
					{/foreach}
				</table>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-1">{tr}Add:{/tr}</label>
				<div class="input-group col-sm-7">
					<input type='text' class="form-control" name='ext_add' />
					<div class="input-group-append">
						<input type='submit' class="btn btn-primary" name='add_fields' value="{tr}Add{/tr}">
					</div>
				</div>
			</div>
		</form>
	{/tab}
{/tabset}
