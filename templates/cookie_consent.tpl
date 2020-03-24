{* $Id$ *}
{strip}
	{if $prefs.cookie_consent_mode eq 'dialog'}
		<div class="modal" tabindex="-1" role="dialog" id="{$prefs.cookie_consent_dom_id}">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">{tr}Cookie Consent{/tr}</h5>
					</div>
					<div class="modal-body">
			{else}
		<div id="{$prefs.cookie_consent_dom_id}" class="alert alert-primary col-sm-8 mx-auto" role="alert"
			{if $prefs.javascript_enabled eq 'y' and not empty($prefs.cookie_consent_mode)}
				style="display:none;" class="{$prefs.cookie_consent_mode}"
			{/if}
		>
	{/if}
		<form method="POST">
			<div class="description mb-3">
				{wiki}{tr}{$prefs.cookie_consent_description}{/tr}{/wiki}
			</div>
			<div class="row mx-0">
				{if !empty($prefs.cookie_consent_question)}
					<div class="col-sm-9">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="cookie_consent_checkbox" id="cookie_consent_checkbox">
							<label class="form-check-label question" for="cookie_consent_checkbox">
								{wiki}{tr}{$prefs.cookie_consent_question}{/tr}{/wiki}
							</label>
						</div>
					</div>
				{else}
					<input type="hidden" name="cookie_consent_checkbox" value="1">
				{/if}
				<div class="col-sm-3">
					<input type="submit" class="btn btn-success" id="cookie_consent_button" name="cookie_consent_button" value="{tr}{$prefs.cookie_consent_button}{/tr}">
				</div>
			</div>
		</form>
	{if $prefs.cookie_consent_mode eq 'dialog'}
		</div></div></div>
	{/if}
	</div>
	{jq}
		$("#cookie_consent_button").click(function(){
			if ($("input[name=cookie_consent_checkbox]:checked").length || $("input[name=cookie_consent_checkbox]:hidden").val()) {
				var exp = new Date();
				exp.setTime(exp.getTime()+(24*60*60*1000*{{$prefs.cookie_consent_expires}}));
				jqueryTiki.no_cookie = false;
				setCookieBrowser("{{$prefs.cookie_consent_name}}", exp.getTime(), "", exp);	// set to cookie value to the expiry time
				$(document).trigger("cookies.consent.agree");
				{{if $prefs.cookie_consent_mode eq 'dialog'}}
		$("#{{$prefs.cookie_consent_dom_id}}").modal("hide");
				{{else}}
		$("#{{$prefs.cookie_consent_dom_id}}").fadeOut("fast");
				{{/if}}
			}
			return false;
		});
	{/jq}
	{if $prefs.cookie_consent_mode eq 'banner'}
		{jq}
			setTimeout(function () {$("#{{$prefs.cookie_consent_dom_id}}").slideDown("slow");}, 500);
		{/jq}
	{elseif $prefs.cookie_consent_mode eq 'dialog'}
		{jq}
			setTimeout(function () {$("#{{$prefs.cookie_consent_dom_id}}").modal({backdrop: "static",keyboard:false,});}, 500);
		{/jq}
	{/if}
{/strip}
