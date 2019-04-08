{tikimodule
	decorations="{$module_params.decorations}"
	error="{$error}" 
	flip="{$module_params.flip}"
	nobox="{$module_params.nobox}"
	nonums="{$module_params.nonums}"
	notitle="{$module_params.notitle}"
	overflow="{$module_params.overflow}"
	title="{$module_params.title}"
	style="{$module_params.style}"
	}
{if empty($error)}
<div class="mod-git_detail">
	<p>
		<span class="label">{tr}Last updated{/tr}</span>
		<span class="branch">(GIT {$content.branch}:{$content.commit.hash|substring:0:5}):</span>
		<span class="date">{$content.author.date|tiki_long_datetime}</span>
	</p>
</div>
{/if}
{/tikimodule}
