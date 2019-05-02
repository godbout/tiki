{assign var=hasPopup value='n'}
{capture name=popup}
	<div class="card">
		<table class="table table-bordered item">
			{foreach from=$popupFields|default:null item=field}
				{if $field.isPublic eq 'y'
				and ($field.isHidden eq 'n' or $field.isHidden eq 'c' or $field.isHidden eq 'p' or $field.isHidden eq 'a' or $tiki_p_admin_trackers eq 'y' or $tiki_p_admin eq 'y')
				and $field.type ne 'x' and $field.type ne 'h' and ($field.type ne 'p' or $field.options_array[0] ne 'password')
				and (empty($field.visibleBy) or in_array($default_group, $field.visibleBy) or tiki_p_admin_trackers eq 'y' or $tiki_p_admin eq 'y')}
					<tr><th>{$field.name|escape}</th><td>{trackeroutput field=$field item=$popupItem showpopup=n showlinks=n}</td></tr>
					{$hasPopup = 'y'}
				{/if}
			{/foreach}
		</table>
	</div>
{/capture}
{if $hasPopup eq 'y'}
	{popup text=$smarty.capture.popup fullhtml="1" hauto=true vauto=true trigger="hover"}
{/if}
