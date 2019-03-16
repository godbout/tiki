{if count($files)}
	{foreach $files as $file}
		<img class="wp-preview" src="{$file}"/>
	{/foreach}
	{if $param.download === 1}
		<br />
		<a class="wp-preview" href="{$original_file_download_link}">{tr}Download original file{/tr}</a>
	{/if}
{/if}