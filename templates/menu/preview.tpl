{extends 'layout_view.tpl'}
{block name="title"}
	{title}{$menuInfo.title|escape}{/title}
{/block}

{block name="content"}
	<h2>Smarty Code</h2>
	<pre id="preview_code">
	{ldelim}menu id={$menuId} type={$preview_type} css={$preview_css} bootstrap={$preview_bootstrap}{rdelim}</pre>{* <pre> cannot have extra spaces for indenting *}
	<div class="card">
		<div class="card-header">
			<h3 class="card-title">{$menuInfo.name|escape}</h3>
		</div>
		<div class="card-body clearfix">
			{if $preview_type eq 'horiz'}
				<nav class="navbar navbar-expand-lg {if $prefs.theme_navbar_color_variant eq 'dark'}navbar-dark bg-dark {else}navbar-light bg-light{/if}" role="navigation">
			{/if}
					{menu id=$menuId type=$preview_type css=$preview_css bootstrap=$preview_bootstrap}
			{if $preview_type eq 'horiz'}
				</nav>
			{/if}
		</div>
	</div>
{/block}
