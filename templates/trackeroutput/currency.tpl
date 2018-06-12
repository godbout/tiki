{strip}
{if $field.value != ''}
	<div style="display:inline" id="tracker_currency_field_{$data.id}">
	{if $field.options_array[2]}
		<span class="formunit">{$field.options_array[2]|escape}</span>
	{/if}
	{if empty($field.options_array[4])}
		{assign var=locale value='en_US'}
	{else}
		{assign var=locale value=$field.options_array[4]}
	{/if}
	{if $field.currency}
		{assign var=currency value=$field.currency}
	{elseif empty($field.options_array[5])}
		{assign var=currency value='USD'}
	{else}
		{assign var=currency value=$field.options_array[5]}
	{/if}
	{if empty($field.options_array[6])}
		{assign var=part1a value='%(!#10n'}
		{assign var=part1b value='%(#10n'}
	{else}
		{assign var=part1a value='%(!#10'}
		{assign var=part1b value='%(#10'}
	{/if}
	{if (isset($context.reloff) and $context.reloff gt 0) and ($field.options_array[7] ne 1)}
		{assign var=format value=$part1a|cat:$field.options_array[6]}
		{$field.amount|money_format:$locale:$currency:$format:0}
	{else}
		{assign var=format value=$part1b|cat:$field.options_array[6]}
		{$field.amount|money_format:$locale:$currency:$format:1}
	{/if}
	{if $field.options_array[3]}
		<span class="formunit">{$field.options_array[3]|escape}</span>
	{/if}
	</div>
	{if $data.conversions}
		<div class="d-none" style="position:absolute; background:white">
		{foreach from=$data.conversions key=currency item=amount}
			{if (isset($context.reloff) and $context.reloff gt 0) and ($field.options_array[7] ne 1)}
				{assign var=format value=$part1a|cat:$field.options_array[6]}
				{$amount|money_format:$locale:$currency:$format:0}
			{else}
				{assign var=format value=$part1b|cat:$field.options_array[6]}
				{$amount|money_format:$locale:$currency:$format:1}
			{/if}
			<br>
		{/foreach}
		</div>
	{/if}
{/if}
{/strip}
{if $data.conversions}
{jq}
$('#tracker_currency_field_{{$data.id}}').hover(function(){
	$(this).next().removeClass('d-none');
},function(){
	$(this).next().addClass('d-none');
});
{/jq}
{/if}