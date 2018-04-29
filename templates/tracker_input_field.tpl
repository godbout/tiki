<div class="form-group">
	{if $field_type eq 'c'}
		<div class="form-check">
			<label>
				{$field_input} {$field_name} {$mandatory_sym}
			</label>
		</div>
	{else}
		<label class="col-form-label field_{$permname}" for="ins_{$field_id}">{$field_name} {$mandatory_sym}</label>
		{$field_input}
	{/if}
	{$description}
</div>