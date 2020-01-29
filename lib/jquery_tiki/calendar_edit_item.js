// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of
// authors. Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See
// license.txt for details. $Id: $

$(document).ready(function () {

	var $start = $("#start"),
		$startPicker = $start.nextAll("input[type=text]"),
		$end = $("#end"),
		$endPicker = $end.nextAll("input[type=text]");

	//add browser timezone to hidden input
	$("input[name=tzoffset]").val((new Date()).getTimezoneOffset());

	$("#allday").change(function () {
		if ($(this).prop("checked")) {
			$(".time").css("visibility", "hidden");
			$startPicker.datepicker("disableTimepicker").datepicker("refresh");
			$endPicker.datepicker("disableTimepicker").datepicker("refresh");
		} else {
			$(".time").css("visibility", "visible");
			$startPicker.datepicker("enableTimepicker").datepicker("refresh");
			$endPicker.datepicker("enableTimepicker").datepicker("refresh");
		}
	}).change();

	$("#durationBtn").click(function () {
		if ($(".duration.time:visible").length) {
			$(".duration.time").hide();
			$(".end").show();
			$(this).text(tr("Show duration"));
			$("#end_or_duration").val("end");
		} else {
			$(".duration.time").show();
			$(".end").hide();
			$(this).text(tr("Show end time"));
			$("#end_or_duration").val("duration");
		}
		return false;
	});

	var getEventTimes = function () {
		var out = {},
			start = parseInt($start.val()),
			end = parseInt($end.val());
		if (start) {
			out.start = new Date(start * 1000);
		} else {
			out.start = null;
		}
		if (end) {
			out.end = new Date(end * 1000);
		} else {
			out.end = null;
		}
		if (start && end) {
			out.duration = ($("select[name=duration_Hour]").val() * 3600) +
				($("select[name=duration_Minute]").val() * 60);	// in seconds
		}

		return out;
	};

	var fNum = function (num) {
		var str = "0" + num;
		return str.substring(str.length - 2);
	};

	$(".duration.time select, #start").change(
		function () {
			var times = getEventTimes();

			if (times.duration) {
				times.end = new Date(
					times.start.getTime() + (times.duration * 1000)
				);

				$end.data("ignoreevent", true);

				$endPicker
					.datepicker(
						"setDate", times.end)
					.datepicker("refresh");
				$end.val(times.end.getTime() / 1000);
			}

			$start.val(times.start.getTime() / 1000);
		});

	$end.change(function (event) {
		var times = getEventTimes(),
			s = times.start ? times.start.getTime() : null,
			e = times.end ? times.end.getTime() : null;

		if ($end.data("ignoreevent")) {
			$end.removeData("ignoreevent");
			return;
		}
		if (e && e <= s) {
			$startPicker
				.datepicker(
					"setDate", times.end);
				//.datepicker("refresh");
			$start.val(times.end.getTime() / 1000);
			s = e;
		}
		if (e) {
			times.duration = (e - s) / 1000;
			$("select[name=duration_Hour]").val(
				fNum(Math.floor(times.duration / 3600)))
				.trigger("chosen:updated");
			$("select[name=duration_Minute]").val(
				fNum(Math.floor((times.duration % 3600) / 60)))
				.trigger("chosen:updated");
		} else {
			$("select[name=duration_Hour]").val(1).trigger("chosen:updated");
			$("select[name=duration_Minute]").val(0).trigger("chosen:updated");
		}
	}).change();	// set duration on load

	// recurring events
	var $recurrentCheckbox = $("#id_recurrent");

	$recurrentCheckbox.click(function () {
		if ($(this).prop("checked")) {
			$("#recurrenceRules").show();

			// set inputs for a new recurring rule
			var d = new Date(parseInt($start.val() * 1000));
			$("select[name=weekday]").val(d.getDay()).trigger(
				"chosen:updated");
			$("select[name=dayOfMonth]").val(d.getDate()).trigger(
				"chosen:updated");
			$("select[name=dateOfYear_day]").val(d.getDate()).trigger(
				"chosen:updated");
			$("select[name=dateOfYear_month]").val(d.getMonth() + 1)
				.trigger("chosen:updated");

		} else {
			$("#recurrenceRules").hide();
		}
	});

	if (typeof $.validator === "object") {
		$.validator.classRuleSettings.date = false;
	}

	if (typeof CKEDITOR === "object") {
		CKEDITOR.on("instanceReady", function (event) {
			// not sure why but the text area doesn't get it's display:none applied when using full calendar
			event.editor.element.$.hidden = true;
		});
	}

	var addParticipant = function(participant) {
		if ($('#participant_roles tr[data-user="'+participant+'"]').length == 0) {
			role_sel = $('<select/>').attr('name', 'save[participant_roles]['+participant+']')
				.addClass('form-control')
				.append('<option value="0">'+tr('chair')+'</option>')
				.append('<option value="1">'+tr('required participant')+'</option>')
				.append('<option value="2">'+tr('optional participant')+'</option>')
				.append('<option value="3">'+tr('non-participant')+'</option>');
			partstat_sel = $('<select/>').attr('name', 'save[participant_partstat]['+participant+']')
				.addClass('form-control')
				.append('<option value="NEEDS-ACTION">NEEDS-ACTION</option>')
				.append('<option value="ACCEPTED">ACCEPTED</option>')
				.append('<option value="TENTATIVE">TENTATIVE</option>')
				.append('<option value="DECLINED">DECLINED</option>');
			$('#participant_roles').append('<tr data-user="'+participant+'"><td>'+participant+'</td><td>'+partstat_sel[0].outerHTML+'</td><td>'+role_sel[0].outerHTML+'</td><td><a href="#" class="delete-participant"><span class="icon icon-remove fas fa-times"></span></a></td></tr>');
		}
	}

	$('#participant_roles').on('click', '.delete-participant', function(e) {
		e.preventDefault();
		var $tr = $(this).closest('tr');
		$('select[name="save[participants][]"]').find('option[value="'+$tr.data('user')+'"]').prop("selected", false);
		$tr.remove();
		return false;
	});

	$('select[name="save[participants][]"]').change(function() {
		var users = $(this).val();
		for (var i = 0, l = users.length; i < l; i++) {
			addParticipant(users[i]);
		}
		var $sel = $(this);
		$('#participant_roles tr').each(function(idx, tr) {
			var user = $(tr).data('user');
			if (! user) {
				return;
			}
			if ($sel.find("option[value='"+user+"']").length > 0 && users.indexOf(user) === -1) {
				$(tr).remove();
			}
		})
	});

	$('#invite_emails').on('click', function() {
		var email = $('#add_participant_email').val();
		if (email) {
			addParticipant(email);
		}
		$('#add_participant_email').val('');
	});

});


/**
 * Checks recurring dates are valid
 *
 * @param day
 * @param month
 */
function checkDateOfYear(day, month)
{
	var mName = [
		"-",
		tr("January"),
		tr("February"),
		tr("March"),
		tr("April"),
		tr("May"),
		tr("June"),
		tr("July"),
		tr("August"),
		tr("September"),
		tr("October"),
		tr("November"),
		tr("December")
	];
	var error = false;

	month = parseInt(month);
	day = parseInt(day);

	if (month === 4 || month === 6 || month === 9 || month === 11) {
		if (day === 31) {
			error = true;
		}
	}
	if (month === 2) {
		if (day > 29) {
			error = true;
		}
	}
	if (error) {
		$("#errorDateOfYear").text(
			tr("There's no such date as") + " " + day + " " + tr('of') + " "
			+ mName[month]).show();
	} else {
		$("#errorDateOfYear").text("").hide();
	}
}

// reset confirm
window.needToConfirm = false;
$("input, select, textarea", "#editcalitem").change(function () {
	window.needToConfirm = true;
});

