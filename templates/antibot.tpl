{* $Id$ *}
{if empty($user) || $user eq 'anonymous' || !empty($showantibot)}
	{$labelclass = 'col-md-3'}
	{$inputclass = 'col-md-9'}
	{$captchaclass = 'col-md-4 offset-md-3 mb-3'}
	{if $form === 'register'}
		{$labelclass = 'col-sm-4'}
		{$inputclass = 'col-sm-8'}
		{$captchaclass = 'col-md-4 offset-md-4 mb-3'}
	{/if}
	{if $form === 'moduleSubscribeNL'}
		{$labelclass = 'col-md-12'}
		{$inputclass = 'col-md-12'}
		{$captchaclass = 'col-md-12 mb-3'}
	{/if}
	<div class="antibot">
		{if $captchalib->type eq 'recaptcha' || $captchalib->type eq 'recaptcha20' || $captchalib->type eq 'recaptcha30'}
			<div class="form-group row clearfix">
				<div class="{$captchaclass}">
					{$captchalib->render()}
				</div>
			</div>
		{elseif $captchalib->type eq 'questions'}
			<input type="hidden" name="captcha[id]" id="captchaId" value="{$captchalib->generate()}">
			<div class="form-group row">
				<label class="{$labelclass} col-form-label">
					{$captchalib->render()}
					{if $showmandatory eq 'y' && $form ne 'register'} <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}
				</label>
				<div class="{if !empty($inputclass)}{$inputclass}{else}col-md-8 col-sm-9{/if}">
					<input class="form-control" type="text" maxlength="8" name="captcha[input]" id="antibotcode">
				</div>
			</div>
		{else}
			{* Default captcha *}
			<input type="hidden" name="captcha[id]" id="captchaId" value="{$captchalib->generate()}">
			<div class="form-group row">
				<label class="col-form-label {$labelclass}" for="antibotcode">{tr}Enter the code below{/tr}{if $showmandatory eq 'y' && $form ne 'register'} <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}</label>
				<div class="{if !empty($inputclass)}{$inputclass}{else}col-md-8 col-sm-9{/if}">
					<input class="form-control" type="text" maxlength="8" name="captcha[input]" id="antibotcode">
				</div>
			</div>
			<div class="clearfix visible-md-block"></div>
			<div class="form-group row">
				<div class="{$captchaclass}">
					{if $captchalib->type eq 'default'}
						<img id="captchaImg" src="{$captchalib->getPath()}" alt="{tr}Anti-Bot verification code image{/tr}" height="50">
					{else}
						{* dumb captcha *}
						{$captchalib->render()}
					{/if}
				</div>
				{if $captchalib->type eq 'default'}
					<div class="col-sm-3">
						{button _id='captchaRegenerate' _class='' href='#antibot' _text="{tr}Try another code{/tr}" _icon_name="refresh" _onclick="generateCaptcha();return false;"}
					</div>
				{/if}
			</div>
		{/if}
	</div>

	{jq rank=1}
		function antibotVerification(element, rule) {
			if (!jqueryTiki.validate) return;

			var form = $(".antibot").parents('form');
			if (!form.data("validator")) {
				form.validate({});
			}
			element.rules( "add", rule);
		}
	{/jq}

	{if $captchalib->type eq 'recaptcha'}
		{jq rank=1}
			var existCondition = setInterval(function() {
				if ($('#recaptcha_response_field').length) {
					clearInterval(existCondition);
					antibotVerification($("#recaptcha_response_field"), {required: true});
				}
			}, 100); // wait for captcha to load

		{/jq}
	{elseif $captchalib->type eq 'recaptcha20' || $captchalib->type eq 'recaptcha30'}
		{jq rank=1}
			var existCondition = setInterval(function() {
				if ($('#g-recaptcha-response').length) {
					clearInterval(existCondition);
					antibotVerification($("#g-recaptcha-response"), {required: true});
				}
			}, 100); // wait for captcha to load
		{/jq}
		{if $captchalib->type eq 'recaptcha30' && $form eq ''}
		{literal}
			<script>
				function genToken() {
					if($("#g-recaptcha-response").length){
						grecaptcha.ready(function() {
							grecaptcha.execute('{/literal}{$prefs.recaptcha_pubkey}{literal}', {action: 'login'})
									.then(function(token) {
										document.getElementById('g-recaptcha-response').value=token;
									});
						});
					}
				}
			</script>
		{/literal}
		{/if}
	{else}
		{jq rank=1}
			antibotVerification($("#antibotcode"), {
				required: true
			});
		{/jq}
	{/if}

{/if}
