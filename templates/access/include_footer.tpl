{if $prefs.javascript_enabled !== 'y'}
	<div class="modal-footer">
		<a class="btn btn-primary" href="{$extra.referer}">
			{tr}Back{/tr}
		</a>
		<input
			type='submit'
			form="confirm-action"
			class="btn {if !empty($confirmButtonClass)}{$confirmButtonClass}{else}btn-warning{/if}"
			value="{if !empty($confirmButton)}{$confirmButton}{else}{tr}OK{/tr}{/if}"
			onclick="confirmAction(event)"
		>
	</div>
{/if}
{* If js is enabled, the layouts/internal/modal.tpl will be used which already has buttons *}
