{*$Id$*}
{if $tikiShowSuggestionsPopup}
	<div id="suggestionsPopup">
		<div class="sug-header">
			<div class="sug-button-close">
				<button id="suggestionsClosePopup" type="button" class="close">×</button>
			</div>
			<div class="sug-title">
				<h3 >
					<span>{tr}Tiki Suggestions{/tr}</span>
				</h3>
			</div>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix sug-body">
			<p>{tr}Do you need help with your Tiki?{/tr}<br/>
				{tr}Why not reach to a specialist:{/tr}
				<a target="_blank" title="{tr}Tiki Consultants{/tr}" alt="{tr}Tiki Consultants{/tr}" href="https://tiki.org/Consultants">https://tiki.org/Consultants</a>?</p>
		</div>
	</div>
{/if}

{jq}
	$(function() {
		$('.close').click(function() {
			let buttonId = $(this).attr('id');
			let warningTitle = $(this).siblings('.alert-heading').children('.rboxtitle').html();
			let warningTitleCheck = "{tr}Tiki Suggestions{/tr}";
			if (warningTitle == warningTitleCheck) {
				$.ajax({
					url: 'tiki-admin.php',
					data: {
						tikiSuggestion: false
					}
				});
			}
			if (buttonId == 'suggestionsClosePopup') {
				$('#suggestionsPopup').hide();
				$.ajax({
					url: 'tiki-admin.php',
					data: {
						tikiSuggestionPopup: false
					}
				});
			}
		});
	});
{/jq}
