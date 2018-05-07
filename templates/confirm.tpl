{* $Id$ *}
<div class="card">
	{if !empty($confirmation_text)}
		<div class="card-header">
			{icon name='information' style="vertical-align:middle"} {$confirmation_text|escape}
		</div>
	{/if}
	{if !empty($confirm_detail)}
		{$confirm_detail}
	{/if}
	<div class="card-body">
		<form id='confirm' action="{$confirmaction|escape}" method="post">
			<div>
				{query _type='form_input' _keepall='y'}
				{ticket}
				{button href="#" _onclick="javascript:document.forms['confirm'].submit();return false;" _text="{tr}Confirm action{/tr}" _ajax="n"}
				{button href="#" _onclick="javascript:history.back(); return false;" _text="{tr}Go back{/tr}" _ajax="n"}
				{button href=$prefs.tikiIndex _text="{tr}Return to home page{/tr}"}
			</div>
		</form>
	</div>
</div>
