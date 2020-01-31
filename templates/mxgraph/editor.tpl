{* $Id$ *}
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=5,IE=9" ><![endif]-->
<!DOCTYPE html>
<html>
<head>
	<head>
		{include file='header.tpl'}
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	</head>
<body class="geEditor">
{if $headerlib}
	{$headerlib->output_js_config()}
	{$headerlib->output_js_files()}
	{$headerlib->output_js()}
	{* some js to enabled falsely detected js disabled browsers to be rechecked * disabled when in the installer *}
	{if $prefs.javascript_enabled eq 'n' and $prefs.disableJavascript eq 'n' and $smarty.server.SCRIPT_NAME|strpos:'tiki-install.php' === false}
	<script type="text/javascript">
		<!--//--><![CDATA[//><!--
		if (confirm("A problem occurred while detecting JavaScript on this page, click ok to retry.")) {ldelim}
			document.cookie = "javascript_enabled_detect=";
			location = location.href;
			{rdelim}
		//--><!]]>
	</script>
	{/if}
{/if}
</body>
</html>
