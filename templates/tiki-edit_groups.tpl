{* $Id$ *}
<table id="group_table" class="table normal table-striped table-hover" >
	<thead>
	<tr>
		<th>{tr}Name{/tr}</th>
		<th>{tr}Description{/tr}</th>
		<th>{tr}Actions{/tr}</th>
	</tr>
	</thead>
	<tbody>
	{section name=group loop=$groups}
		{permission name=edit_grouplimitedinfo type=group object=$groups[group].groupName}
			<tr>
				<td class="text">
					{$groups[group].groupName|escape}
				</td>
				<td class="text">
					{$groups[group].groupDesc|escape}
				</td>

				{if $prefs.useGroupHome eq 'y'}
					<td class="text">
						{tr}{$groups[group].groupHome}{/tr}
					</td>
				{/if}

				<td class="action">
					{actions}
					{strip}
						<action>

							{if $groups[group].groupName neq 'Anonymous' and $groups[group].groupName neq 'Registered' and $groups[group].groupName neq 'Admins'}
								<a href="#" class="edit_group_a" data-id="{$groups[group].id}" data-name="{$groups[group].groupName}" data-description="{$groups[group].groupDesc}">
									{icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							{/if}

						</action>
					{/strip}
					{/actions}
				</td>
			</tr>
		{/permission}
	{/section}
	</tbody>
</table>
{jq}

	$(".edit_group_a").click(function($ele){
	var $dialog = $( "#modal_edit_group" );
	$dialog.appendTo("body").modal({backdrop:"static"});
	var id = $(this).data('id');
	var name = $(this).data('name');
	var desc = $(this).data('description');

	$("#modal_edit_group input[name=name]").val(name);
	$("#modal_edit_group textarea[name=desc]").val(desc);
	$("#modal_edit_group input[name=id]").val(id);
	$("#modal_edit_group .modal-title").html('{tr}Edit{/tr} '+name);
	});

	$("#edit_group_form").validate({
	// Specify validation rules
	rules: {
	// The key name on the left side is the name attribute
	// of an input field. Validation rules are defined
	// on the right side
	name: "required",
	},
	// Specify validation error messages
	messages: {
	name: "{tr}Name is required {/tr}",
	},
	// Make sure the form is submitted to the destination defined
	// in the "action" attribute of the form when valid
	submitHandler: function(form) {
	$.ajax({
	type: "POST",
	url: 'tiki-edit_groups.php',
	data: $(form).serialize(), // serializes the form's elements.
	success: function(data)
	{
	$( "#modal_edit_group" ).modal('hide');
	location.reload();
	},
	error: function(data){
	alert("{tr}Error saving group{/tr}");
	}
	});

	event.preventDefault();
	}
	});

{/jq}
<!-- Modal -->
<div class="modal" id="modal_edit_group" role="dialog">
	<form id="edit_group_form">

		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"> </h4>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id">
					<div class="form-group row">
						<label for="groups_group" class="col-form-label col-md-3">{tr}Group{/tr}</label>
						<div class="col-md-9">
							{if $groupname neq 'Anonymous' and $groupname neq 'Registered' and $groupname neq 'Admins'}
								<input type="text" name="name" id="groups_group" value="{$groupname|escape}" class="form-control">
							{else}
								<input type="hidden" name="name" id="groups_group" value="{$groupname|escape}">
								{$groupname|escape}
							{/if}
						</div>
					</div>
					<div class="form-group row">
						<label for="groups_desc" class="col-form-label col-md-3">{tr}Description{/tr}</label>
						<div class="col-md-9">
							<textarea rows="5" name="desc" id="groups_desc" class="form-control">{$groupdesc|escape}</textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button
							type="submit"
							class="btn btn-secondary"
					>
						{tr}Save{/tr}
					</button>
				</div>
			</div>

		</div>
	</form>

</div>
