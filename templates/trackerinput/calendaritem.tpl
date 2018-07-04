{if $data.editUrl}
	{if not empty($item.itemId)}
		{button _href=$data.editUrl _text="{tr}Edit Event{/tr}" _id='calitem_'|cat:$field.fieldId _class='btn btn-default btn-sm'}
	{/if}
	{jq}
		$('#calitem_{{$field.fieldId}}').click($.clickModal(
			{
				size: "modal-lg",
				open: function (data) {
					// prevent default modal submit button handling
					$(".submit", this).removeClass("submit");
				}
			},
			"{{$data.editUrl}}"
		));
	{/jq}
{/if}
{$datePickerHtml}
