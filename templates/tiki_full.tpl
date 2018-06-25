{* $Id$ *}<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}>
	<head>
{include file='header.tpl'}
	</head>
	<body{html_body_attributes}>

{* Index we display a wiki page here *}
{if $prefs.feature_bidi eq 'y'}
<div dir="rtl">
{/if}
{if $prefs.feature_ajax eq 'y'}
{include file='tiki-ajax_header.tpl'}
{/if}
{if $is_slideshow eq 'y'}
<div class="reveal">
			<div class="slides">
{else}
<div id="main">
	<div id="tiki-center">
		<div id="role_main">
{/if}

			{$mid_data}
{if $is_slideshow eq 'y'}            
		</div>
  {else}    
  </div>
  
	</div>
</div>
{/if}
{if $prefs.feature_bidi eq 'y'}
</div>
{/if}
{include file='footer.tpl'}
	</body>
</html>
