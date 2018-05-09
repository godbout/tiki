{title help='WebHelp' url='tiki-create_webhelp.php'}{tr}Create WebHelp{/tr}{/title}
{if $generated eq 'y'}
	<div class="t_navbar">
		<span class="button btn btn-primary">
			<a class="link" href="whelp/{$dir}/index.html">{tr}View generated WebHelp.{/tr}</a>
		</span>
	</div>
{/if}
{if $output ne ''}
	<div class="card"><div class="card-body">
		{$output}
	</div></div>
{/if}
<form method="post" action="tiki-create_webhelp.php" class="form-horizontal">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Structure{/tr}</label>
		<div class="col-sm-7">
			{$struct_info.pageName|default:"{tr}No structure{/tr}."}
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Directory{/tr}</label>
		<div class="col-sm-7">
			<input type="text" id="dir" name="dir" value="{$struct_info.pageName}" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Top page{/tr}</label>
		<div class="col-sm-7">
			<input type="text" id="top" name="top" value="{$struct_info.pageName}" class="form-control">
		</div>
	</div>

	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary btn-sm" {if !$struct_info.pageName}disabled='disabled'{/if} name="create" value="{tr}Create{/tr}">
		</div>
	</div>
	<input type="hidden" name="name" value="{$struct_info.pageName}">
	<input type="hidden" name="struct" value="{$struct_info.page_ref_id}">
</form>
