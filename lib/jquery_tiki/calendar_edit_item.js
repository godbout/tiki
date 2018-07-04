// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of
// authors. Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See
// license.txt for details. $Id: $

$(document).ready(function () {
	$("#allday").change(function () {
		if ($(this).prop("checked")) {
			$(".time").css("visibility", "hidden");
		} else {
			$(".time").css("visibility", "visible");
		}
	}).change();

	$("#durationBtn").click(function () {
		if ($(".duration.time:visible").length) {
			$(".duration.time").hide();
			$(".end.time").show();
			$(this).text(tr("Show duration"));
			$("#end_or_duration").val("end");
		} else {
			$(".duration.time").show();
			$(".end.time").hide();
			$(this).text(tr("Show end time"));
			$("#end_or_duration").val("duration");
		}
		return false;
	});

	var getEventTimes = function () {
		var out = {},
			start = parseInt($("#start").val()),
			end = parseInt($("#end").val());
		if (start) {
			out.start = new Date(start * 1000);
			out.start.setHours($("select[name=start_Hour]").val());
			out.start.setMinutes($("select[name=start_Minute]").val());
		} else {
			out.start = null;
		}
		if (end) {
			out.end = new Date(end * 1000);
			out.end.setHours($("select[name=end_Hour]").val());
			out.end.setMinutes($("select[name=end_Minute]").val());
		} else {
			out.end = null;
		}
		if (start && end) {
			out.duration = new Date(0);
			out.duration.setHours($("select[name=duration_Hour]").val());
			out.duration.setMinutes(
				$("select[name=duration_Minute]").val());
		} else {
			out.duration = null;
		}

		return out;
	};

	var fNum = function (num) {
		var str = "0" + num;
		return str.substring(str.length - 2);
	};

	$(".start.time select, .duration.time select, #start").change(
		function () {
			var times = getEventTimes(),
				$end = $("#end");

			if (times.duration) {
				times.end = new Date(
					times.start.getTime() + times.duration.getTime());
				$("select[name=end_Hour]").val(fNum(times.end.getHours()))
					.trigger(
						"chosen:updated");
				$("select[name=end_Minute]").val(
					fNum(times.end.getMinutes()))
					.trigger(
						"chosen:updated");
				$end.nextAll("input[type=text]")
					.datepicker(
						"setDate", $.datepicker.formatDate(
							$end.nextAll("input[type=text]").datepicker(
								"option",
								"dateFormat"
							), times.end))
					.datepicker("refresh");
				$end.val(times.end.getTime() / 1000);
			}

			$("#start").val(times.start.getTime() / 1000);
		});

	$(".end.time select, #end").change(function () {
		var times = getEventTimes(),
			s = times.start ? times.start.getTime() : null,
			e = times.end ? times.end.getTime() : null,
			$start = $("#start");

		if (e && e <= s) {
			$("select[name=start_Hour]").val(fNum(times.end.getHours()))
				.trigger(
					"chosen:updated");
			$("select[name=start_Minute]").val(fNum(times.end.getMinutes()))
				.trigger("chosen:updated");
			$start.nextAll("input[type=text]")
				.datepicker(
					"setDate", $.datepicker.formatDate(
						$start.nextAll("input[type=text]").datepicker(
							"option",
							"dateFormat"
						), times.end))
				.datepicker("refresh");
			$start.val(times.end.getTime() / 1000);
			s = e;
		}
		if (e) {
			times.duration = new Date(e - s);
			$("select[name=duration_Hour]").val(
				fNum(times.duration.getUTCHours()))
				.trigger("chosen:updated");
			$("select[name=duration_Minute]").val(
				fNum(times.duration.getUTCMinutes()))
				.trigger("chosen:updated");
		}
	}).change();	// set duration on load

	// recurring events
	var $recurrentCheckbox = $("#id_recurrent");

	$recurrentCheckbox.click(function () {
		if ($(this).prop("checked")) {
			$("#recurrenceRules").show();

			// set inputs for a new recurring rule
			var d = new Date(parseInt($("#start").val() * 1000));
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

	$.validator.classRuleSettings.date = false;
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

