{if $editUrl}
	{button _href=$editUrl _text='{tr}Edit Event{/tr}' _id='calitem_'|cat:$field.fieldId _class='btn btn-default btn-sm'}

	{jq}
		$('#calitem_{{$field.fieldId}}').click($.clickModal(
			{
				size: "modal-lg",
				success: function (data) {
					// cool
				}
			},
			"{{$editUrl}}"
		));
	{/jq}
{/if}
{$datePickerHtml}
