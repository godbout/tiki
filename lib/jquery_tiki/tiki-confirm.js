/** $Id$
 *
 * To facilitate popup confirmation forms and related checking of security
 * timeout for state-changing actions, and to capture all form inputs when ajax
 * is used
 */

/**
 * Onclick method used on form submit or anchor elements when a popup
 * confirmation form is desired, and to capture all form inputs when ajax
 * is used
 *
 * - for non-ajax forms, typically used for state-changing actions that cannot
 * easily be undone, where a confirmation is advisable.
 * - for ajax forms, captures all form inputs when triggering ajax services
 * modal with a form submission so that the inputs don't need to be added to
 * the bootstrap_modal smarty function as parameters. In this case, it is not
 * being used to produce a confirmation popup since the ajax function should do
 * that
 * - should also be used for non-ajax anchors that trigger a state-changing
 * action since requests that change the database should not be GET requests.
 * - not needed for ajax anchors as popup confirmations are provided through
 * ajax and any ticket will be freshly created in the ajax popup
 * - all form inputs or anchor url parameters are converted to form inputs in
 * the popup confirmation form.
 * - for select elements in non-ajax forms, if only some options need to be
 * confirmed, the confirm-popup class should be added to those option elements
 * and the confirm text added to a data attribute called data-confirm-text
 * for that option element
 *
 * When the form or anchor action is an ajax service:
 *
 * 	- the formaction attribute of the submit element or the action attribute
 * of the form must be set using the bootstrap_modal smarty function
 * 	- for a submit button related to a select element:
 * 		- the name attribute of the select element must be set to action
 * 			(name=action)
 * 		- the select option value being submitted should be the action value
 * 			only (e.g., remove_users)
 * 		- the submit element's formaction attribute value or the form's
 * 			action attribute value will be used for the first part of the
 * 			services url, ie without the action specified - eg
 * 			{bootstrap_modal controller=user}
 * 		- the above requirements for a submitted select value (ie
 * name=action, value contains only the action, rest of url in formaction or
 * form action attribute) is necessary for ajax services to work when
 * javascript is not enabled
 *
 *
 * @param title		string		Confirmation text. Default is tr('Complete
 *     this action?'). Not used for ajax services since the service will
 *     provide the text
 * @param ticket	string		Security token. Usually only needed for
 *     anchors since the function will get the token from the form inputs when
 *     it's a form
 * @returns {boolean}
 */
function confirmPopup(title, ticket) {
	if (! this.event) {
		return false;
	}
	this.event.preventDefault();
	var el = this.event.currentTarget, ajax = isAjaxRequest(el);
	// used when the bootstrap_modal smarty function is used with a form in order to capture all form inputs
	// no need to check timeout here since the ajax function should produce a fresh ticket
	if (ajax && el.form) {
		var target = $('.modal.fade:not(.show)').first(),
			// look for action specified in formaction attribute of the clicked element first, the action
			// attribute of the form second
			formAction = $(el).attr('formaction') || $(el.form).attr('action');
		$.post(formAction, $(el.form).serialize(),
			function (data)
			{
				$('.modal-content', target).html(data);
				target.modal().trigger('tiki.modal.redraw');
			});
		return false;
	//this section for non-ajax submissions
	} else if (checkTimeout()){
		if (el.form) {
			// If the submit only needs to be confirmed if certain select options are chosen, then the
			// confirm-popup class is added to the options that should be confirmed in addition to adding
			// the onclick method confirmPopup() to the submit element. In this case, bypass confirmation if
			// such an option has not been selected
			var optionConfirm = $(el.form).find('select > option.confirm-popup'),
				selected = $(el.form).find('select > option.confirm-popup:selected');
			// proceed if there is not a select element that has options with the confirm-popup class
			// or there is and an option with the confirm-popup class has been selected
			if (! optionConfirm.length || selected.length) {
				var formId = $(el.form).attr('id') ? $(el.form).attr('id')
						+ '-confirm-popup' : 'confirm-popup',
					formName = $(el.form).attr('name') ? $(el.form).attr('name')
						+ '-confirm-popup' : 'confirm-popup',
					newForm = $('<form/>', {name : formName, id : formId,
						action : $(el.form).attr('action'), method : 'post'}),
					inputs = $(el.form).find('input, textarea, select > option:selected');
				$.each(inputs, function () {
					if (this.type !== 'submit' && (this.type !== 'checkbox' || this.checked === true)
						&& (this.type !== 'radio' || this.checked === true))
					{
						var name = this.tagName === 'OPTION' ? $(this).parent('select').attr('name') : this.name;
						newForm.append($('<input />', {type: 'hidden', name: name, value: this.value}));
					}
				});
				if (el.name) {
					newForm.append($('<input />', {type: 'hidden', name: el.name,
						value: el.value}));
				}
				if (selected.length) {
					$.each(selected, function (key, item) {
						if ($(selected[key]).data('confirm-text')) {
							title = $(selected[key]).data('confirm-text');
							return false;
						}
					});
				}
				simpleConfirmForm(el, newForm, title, ticket).modal();
			//
			} else {
				$(el.form).submit();
			}
		//if a link was clicked
		} else if (el.tagName === 'A') {
			var newForm = $('<form/>', {id : 'confirm-popup', action : el.pathname,
					method : 'post'}),
				params = el.search.substr(1).split('&');
			if (params) {
				for (var i = 0; i < params.length; i++) {
					var parampair = params[i].split("=")
					newForm.append($('<input />', {type: 'hidden', name: decodeURIComponent(parampair[0]),
						value: decodeURIComponent(parampair[1])}));
				}
			}
			simpleConfirmForm(el, newForm, title, ticket).modal();
		}
	}
}

/**
 * Utility used by the confirmPopup() function to determine whether the url
 * associated with the clicked element is an ajax service based on the pattern
 * used for such urls: tiki-controller-action?query
 */
function isAjaxRequest(el) {
	var path = '', regex = new RegExp("^(tiki\-)(\\w+)(\-)(\\w+)(.*?)$");
	if (el.form) {
		path = $(el).attr('formaction') || $(el.form).attr('action');
	} else if (el.tagName === 'A') {
		path = $(el).attr('href');
	}
	return regex.test(path);
}

/**
 * Utility used by the confirmPopup() function to create and return
 * the popup form
 *
 * @param clickedElement	object		Element clicked
 * @param newForm			object		Form that has been started and that
 *     will be completed with this function
 * @param title				string		Confirmation text. Alternatively
 *     the function will look for a data-confirm-text attribute before using
 *     the default tr('Complete this action?')
 * @param ticket			string		Security token
 * @returns {object}
 */
function simpleConfirmForm(clickedElement, newForm, title, ticket) {
	// hide any popovers they may have contained the element that was clicked
	$('div.popover-body:visible').parent().hide();
	if (! title) {
		title = $(clickedElement).data('confirm-text') ? $(clickedElement).data('confirm-text')
			: tr('Complete this action?');
	}
	if (! ticket && ! $(newForm).find('input[name=ticket]').length && $(clickedElement).data('ticket')) {
		ticket = $(clickedElement).data('ticket');
	}
	if (! $(newForm).find('input[name=ticket]').length && ticket) {
		newForm.append($('<input />', {type: 'hidden', name: 'ticket', value: ticket}));
	}
	newForm.append($('<input />', {type: 'hidden', name: 'confirmForm', value: 'y'}));
	var target = $('.modal.fade:not(.in)').first();
	$('.modal-content', target).html(
		'<div class="modal-header">' +
		'<h4 class="modal-title" id="myModalLabel">' + title + '</h4>' +
		$(newForm).prop('outerHTML') +
		'</div>' +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-primary btn-dismiss" data-dismiss="modal">' + tr('Close') + '</button>' +
		'<input type="submit" class="btn btn-primary" value="' + tr('OK') +
		'" onclick="$(\'#' + $(newForm).attr('id') + '\').submit(); return false;"> ' +
		'</div>'
	);
	return target;
}

/**
 * Onclick method to capture all form inputs when triggering ajax services when
 * there are no modals involved
 *
 *  - the formaction attribute of the submit element or the action attribute of
 * the form must be set using the service smarty function
 */
function postForm () {
	event.preventDefault();
	var formAction = $(event.currentTarget).attr('formaction') || $(event.currentTarget.form).attr('action');
	$.post(formAction, $(event.currentTarget.form).serialize(), function (data) {});
	return false;
}

/**
 * Utility that checks whether the security ticket has timed out used in
 * function below
 *
 * @returns {boolean}
 */
$.fn.ticketTimeout = function() {
	// don't check timeout again if already check and expired so that the warning
	// only comes up the
	// first time the input element is clicked
	if ($(this).hasClass('already-warned')) {
		return true;
	} else {
		if (!checkTimeout()) {
			event.preventDefault();
			$(this).addClass('already-warned');
		}
		return false;
	}
}

/**
 * Used for a form that has a security ticket so that the user is warned that
 * the ticket is timed out before entering data into the form. listens for any
 * form and then checks whether the form has the ticket input before performing
 * the check.
 */
$.fn.applyTicketTimeout = function () {
	// forms with tickets
	$('form').has('input[name=ticket]')
		.on('mousedown keydown', 'select, input:not([type=submit]), [type=submit]:not(.no-timeout), textarea', $.fn.ticketTimeout);
}

/**
 * Apply ticket timeout warnings to forms on regular pages (not popups or
 * modals). See documentation for applyTicketTimeout
 */
$(document).applyTicketTimeout();

/**
 * Apply ticket timeout warnings to forms on modals.
 */
$(document).on('tiki.modal.redraw', '.modal.fade', $.fn.applyTicketTimeout);

/**
 * Apply ticket timeout warnings to forms on popovers. For some reason
 * applyTicketTimeout doesn't work.
 */
$("[data-toggle='popover']").on('shown.bs.popover', function() {
	// set what happens when user clicks on the button
	$('form').has('input[name=ticket]').on('click', '[type=submit]:not(.no-timeout)', $.fn.ticketTimeout);
	return true;
}).on('hidden.bs.popover', function() {
	// clear listeners
	$('form').has('input[name=ticket]').off('click', '[type=submit]:not(.no-timeout)');
});

/**
 * Utility method used by the confirmPopup() method and the timeout warning
 * listeners just above that generates a popup warning and stops the click
 * event if the security timeout period has elapsed.
 *
 * The timeout period is determined by the site_security_timeout preference
 * setting
 *
 * @returns {boolean}
 */
function checkTimeout() {
	if ((($.now() - now.getTime()) / 1000) < jqueryTiki.securityTimeout) {
		return true;
	} else {
		event.preventDefault();
		feedback(
			[tr('The security ticket for this form has expired.') + ' '
			 + tr('To apply your changes, note or copy them, reload the page, re-enter them and retry submitting.')],
			'warning',
			true,
			tr('Security ticket timed out')
		);
		var target = $('.modal.fade:not(.show)').first();
		$('.modal-body', target).after(
			'<div class="modal-footer">' +
			'<a href="#" onclick="$.closeModal();return false;" class="btn btn-primary">'
			+ tr('Close this dialog') +
			'</a>' +
			'<a href="' + location.href + '" onclick="location.reload();return false;" class="btn btn-secondary">'
			+ tr('Reload now (discards changes)') +
			'</a>'+
			'</div>'
		);
		return false;
	}
}

/**
 * Use data posted from a popup modal as input for the ajax service action
 *
 * @param event
 */
function confirmAction(event) {
	//this is the ajax action once the confirm submit button is clicked
	event.preventDefault();
	if (typeof event.currentTarget !== 'undefined' && event.currentTarget.form !== 'undefined') {
		var targetForm = event.currentTarget.form;
	} else if (typeof event.target !== 'undefined' && event.target.form !== 'undefined') {
		var targetForm = event.target.form;
	}
	$.ajax({
		dataType: 'json',
		url: $(targetForm).attr('action'),
		type: 'POST',
		data: $(targetForm).serialize(),
		success: function (data) {
			if (!data) {
				$.closeModal();
				return;
			}
			var extra = data.extra || false, dataurl = data.url || false, dataError = data.error || false,
				strip = data.strip || false;
			if (extra) {
				/* Simply close modal. Feedback is added to the page without refreshing in the ajax service using the
				the standard Feedback class function send_headers(). Used when there is an error in submitting modal
				form */
				if (extra === 'close') {
					$.closeModal();
				//Close modal and refresh page. Feedback can be added to the refreshed page in the ajax service using
				//the Feedback class
				} else if (extra === 'refresh') {
					$.closeModal();
					//strip off anchor or query and anchor if specified
					if (strip) {
						if (strip === 'anchor' || strip === 'queryAndAnchor') {
							var href = document.location.href.replace(/#.*$/, "");
							document.location.href = document.location.href.replace(/#.*$/, "");
							if (strip === 'queryAndAnchor') {
								document.location.href = document.location.href.replace(/\?.*$/, "");
							}
						}
					} else {
						document.location.reload();
					}
				}
			}
			//send to another page, or to same page when reload is needed
			if (dataurl) {
				$.closeModal();
				document.location.assign(dataurl);
			}
			//send error
			if (dataError) {
				if (dataError === 'CSRF') {
					dataError = tr('Potential cross-site request forgery (CSRF) detected. Operation blocked. The security ticket may have expired - reloading the page may help.');
				}
				$.closeModal();
				feedback (
					dataError,
					'error'
				);
				console.log(dataError);
			}
			return false;
		}
	});
}