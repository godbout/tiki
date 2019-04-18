{if $prefs.javascript_enabled !== 'y'}
	<div class="modal-footer">
		<a class="btn btn-primary" href="{$extra.referer}">
			{tr}Back{/tr}
		</a>
{/if}
		<div{if $prefs.javascript_enabled === 'y'} class="submit offset-md-3 col-md-9"{/if}>
			<input
				type="submit"
				class="btn {if !empty($confirmButtonClass)}{$confirmButtonClass}{else}btn-primary{/if}"
				value="{if !empty($confirmButton)}{$confirmButton}{else}{tr}OK{/tr}{/if}"
				onclick="confirmAction(event)"
			>
		</div>
{if $prefs.javascript_enabled !== 'y'}
	</div>
{/if}
