{* $Id$ *}
{extends 'layout_edit.tpl'}
{title admpage="wiki" help="Using Wiki Pages#List_Pages"}{tr}Pages{/tr}{/title}

{block name=content}
	{if $tiki_p_edit == 'y'}
		<div class="row">
			<div class="col-sm-12">
				<h2>{tr}Experiment with plugin LIST{/tr}</h2>
				<hr>
				<div class="preview_contents">
					{$listparsed}
				</div>
				<hr>
			</div>
		</div>
		<form method="post" class="form-horizontal">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group row">
						<div class="row">
							<div class="col-sm-6 offset-sm-6">
								<input class="btn btn-secondary btn-sm" type="submit" name="quickedit" value="{tr}Test Plugin LIST{/tr}">
							</div>
							<div class="col-sm-6 col-sm-pull-6">
								<label for="comment">Plugin LIST content:</label>
							</div>
						</div>
						<textarea class="form-control" rows="5" name="editwiki" id="editwiki">{$listtext}</textarea>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3"></div>
				<div class="col-sm-9">
					<input class="btn btn-secondary " type="submit" name="quickedit" value="{tr}Test Plugin LIST{/tr}">
				</div>
			</div>
		</form>
	{/if}
{/block}
