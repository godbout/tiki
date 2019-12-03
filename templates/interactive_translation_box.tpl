<div class="intertrans text-center" id="intertrans-indicator">
	<div class="form-check">
		<label class="form-check-label text-white-50" for="intertrans-active">
			{tr}Toggle interactive translation{/tr}
			<label class="switch">
				<input type="checkbox" id="intertrans-active" name="intertrans-active">
				<span class="slider"></span>
			</label>
		</label>
		<a  href="#" class="btn text-white-50 tips my-2" title="{tr}Help{/tr}:{tr}Once checked, click on any string to translate it.{/tr}">
			{icon name="help"}
		</a>
		{if isset($smarty.session.interactive_translation_mode) && $smarty.session.interactive_translation_mode eq "on"}
			<a href="tiki-interactive_trans.php?interactive_translation_mode=off" class="btn btn-link tips my-2 text-white-50" title=":{tr}Turn off interactive translation{/tr}" id="disableTranslation">
				{icon name="off"}
			</a>
		{/if}
	</div>
</div>
<div class="modal fade intertrans" id="intertrans-modal" tabindex="-1" role="dialog" aria-labelledby="intertransModalLabel">
	<form method="post" action="tiki-interactive_trans.php" class="form">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="intertransModalLabel">
						{tr}Interactive Translation{/tr}
					</h4>
				</div>
				<div class="modal-body form-group row" id="intertrans-table">
					<table class="table table-condensed table-hover">
						<thead>
							<tr>
								<th>
									{tr}Original{/tr}
								</th>
								<th>
									{tr}Translation{/tr}
								</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<div class="text-center" id="intertrans-empty" style="display: none">
						{remarksbox type="note" title="{tr}Information{/tr}" close="n"}
							{tr}Couldn't find any translatable string.{/tr}
						{/remarksbox}
					</div>
				</div>
				<div class="modal-footer">
					<input id="intertrans-submit" type="submit" class="btn btn-secondary" value="{tr}Save translations{/tr}">
					<input id="intertrans-cancel" class="btn btn-primary" type="reset" value="{tr}Cancel{/tr}">
					<input id="intertrans-close" class="btn btn-primary" type="reset" value="{tr}Close{/tr}">
					<span id="intertrans-help" class="form-text">
						{tr}Changes will be applied on next page load only.{/tr}
					</span>
				</div>
			</div>
		</div>
	</form>
</div>
