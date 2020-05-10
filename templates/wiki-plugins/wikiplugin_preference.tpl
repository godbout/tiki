<form action="{$url}" method="post">
	{foreach from=$names item=name}
		{preference show_tags=false name=$name}
	{/foreach}
	<div class="text-center">
		<button type="submit" class="btn btn-primary btn-sm">Save</span></button>
	</div>
</form>
