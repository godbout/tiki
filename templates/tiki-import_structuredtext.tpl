{title help="ImportingPagesAdmin"}{tr}Import pages from a Structured Text Dump{/tr}{/title}

<form method="post" action="tiki-import_structuredtext.php" class="form-horizontal">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Name of the dump file (it has to be in dump/){/tr}</label>
		<div class="col-sm-7">
			<input type="text" name="path" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Overwrite existing pages if the name is the same{/tr}</label>
		<div class="col-sm-1">
			<div class="radio">
				<label>
					<input type="radio" name="crunch" value='y'> {tr}Yes{/tr}
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<div class="radio">
				<label>
					<input checked="checked" type="radio" name="crunch" value='n'> {tr}No{/tr}
				</label>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Remove previously existing page versions:{/tr}</label>
		<div class="col-sm-1">
			<div class="radio">
				<label>
					<input type="radio" name="remo" value='y'> {tr}Yes{/tr}
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<div class="radio">
				<label>
					<input checked="checked" type="radio" name="remo" value='n'> {tr}No{/tr}
				</label>
			</div>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary btn-sm" name="import" value="{tr}import{/tr}">
		</div>
	</div>
</form>
<br><br>

{if $result eq 'y'}
	<div class="table-responsive">
		<table class="table">
			<tr>
				<th>{tr}page{/tr}</th>
				<th>{tr}excerpt{/tr}</th>
				<th>{tr}Result{/tr}</th>
				<th>{tr}body{/tr}</th>
			</tr>

			{section name=ix loop=$lines}
				<tr>
					<td class="text">{$lines[ix].page}</td>
					<td class="text">{$lines[ix].ex}</td>
					<td class="text">{$lines[ix].msg}</td>
					<td class="text">{$lines[ix].body}</td>
				</tr>
			{/section}
		</table>
	</div>
{/if}
