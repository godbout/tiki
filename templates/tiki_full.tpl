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
		<div id="ss-settings-holder" title="Click for slideshow operations"><span class="fa fa-cogs" style="font-size:1.5rem;color:#666" id="ss-settings"></span></div>
		<div id="ss-options" class="d-flex flex-row justify-content-around align-content-end flex-wrap">
			<div class="p-2">
				<select id="showtheme" class="form-control">
					<option value="">Change Theme</option>
					<option value="black">Black: Black background, white text, blue links</option>
					<option value="blood">Blood: Dark gray background, dark text, maroon links</option>
					<option value="beige">Beige: Beige background, dark text, brown links</option>
					<option value="league">League: Gray background, white text, blue links</option>
					<option value="moon">Moon: Navy blue background, blue links</option>
					<option value="night">Night: Black background, thick white text, orange links</option>
					<option value="serif">Serif: Cappuccino background, gray text, brown links</option>
					<option value="simple">Simple: White background, black text, blue links</option>
					<option value="sky">Sky: Blue background, thin dark text, blue links</option>
					<option value="solarized">Solarized: Cream-colored background, dark green text, blue links</option>
				</select>
			</div>
			<div class="p-2">
				<select id="showtransition" class="form-control">
					<option value="">Change Transition</option>
					<option value="zoom">Zoom</option>
					<option value="fade">Fade</option>
					<option value="slide">Slide</option>
					<option value="convex">Convex</option>
					<option value="concave">Concave</option>
					<option value="">off</option>
				</select>
			</div>
			<div class="p-2"><a href="tiki-slideshow.php?page={$page}&pdf=1&landscape=1" target="_blank"><span class="fa fa-file-pdf-o"></span> Export PDF</a></div>				<div class="p-2"><a href="tiki-slideshow.php?page={$page}&pdf=1&printslides=1" target="_blank"><span class="fa fa-print"></span> Handouts</a></div>
			<div class="p-2"><a href="tiki-index.php?page={$page}"><span class="fa fa-sign-out"></span>&nbsp;Exit</a></div>
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

