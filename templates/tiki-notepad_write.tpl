{title help="Notepad"}{tr}Write a note{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}

<div class="t_navbar">
	{button href="tiki-notepad_list.php" class="btn btn-primary" _text="{tr}Notes{/tr}"}
</div>

<form action="tiki-notepad_write.php" method="post">
	<input type="hidden" name="parse_mode" value="{$info.parse_mode|escape}">
	<input type="hidden" name="noteId" value="{$noteId|escape}">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Name{/tr}</label>
		<div class="col-sm-7">
			<input type="text" name="name" size="40" value="{$info.name|escape}" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Data{/tr}</label>
		<div class="col-sm-7">
			{textarea rows="20" cols="80" name="data" _simple="y" class="form-control"}{$info.data}{/textarea}
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</div>
</form>
