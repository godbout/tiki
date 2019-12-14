{$filters}
<div id="{$trackercalendar.id|escape}"></div>
{jq}
	var data = {{$trackercalendar|json_encode}};
	$('#' + data.id).each(function () {
		var cal = this;
		var storeEvent = function(event) {
			var request = {
				itemId: event.id,
				trackerId: data.trackerId
			}, end = event.end;

			if (! end) {
				end = event.start;
			}

			// Events after drop/resize it looses the timezone info (ambiguous-zone)
			// Re-add the utc offset will make the date to timestamp conversion to use the correct UTC datetime.
			request['fields~' + data.begin] = event.start.utcOffset(data.utcOffset, true).unix();
			request['fields~' + data.end] = end.utcOffset(data.utcOffset, true).unix();
			request['fields~' + data.resource] = event.resourceId;

			$.post($.service('tracker', 'update_item'), request, null, 'json');
		};

		$(this).fullCalendar({
			themeSystem: 'bootstrap4',
			schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
			timeFormat: data.timeFormat,
			header: {
				left: 'prevYear,prev,next,nextYear today',
				center: 'title',
				right: data.views
			},
			editable: true,
			height: 'auto',
			timezone: '{{$prefs.display_timezone}}',
			//theme: true, TODO: add support of jQuery UI theme to the plugin's PHP
			events: $.service('tracker_calendar', 'list', $.extend(data.filterValues, {
				trackerId: data.trackerId,
				colormap: data.colormap,
				beginField: data.begin,
				endField: data.end,
				resourceField: data.resource,
				coloringField: data.coloring,
				filters: data.body
			})),
			resources: data.resourceList,
			minTime: data.minHourOfDay,
			maxTime: data.maxHourOfDay,
			monthNames: [ "{tr}January{/tr}", "{tr}February{/tr}", "{tr}March{/tr}", "{tr}April{/tr}", "{tr}May{/tr}", "{tr}June{/tr}", "{tr}July{/tr}", "{tr}August{/tr}", "{tr}September{/tr}", "{tr}October{/tr}", "{tr}November{/tr}", "{tr}December{/tr}"],
			monthNamesShort: [ "{tr}Jan.{/tr}", "{tr}Feb.{/tr}", "{tr}Mar.{/tr}", "{tr}Apr.{/tr}", "{tr}May{/tr}", "{tr}June{/tr}", "{tr}July{/tr}", "{tr}Aug.{/tr}", "{tr}Sep.{/tr}", "{tr}Oct.{/tr}", "{tr}Nov.{/tr}", "{tr}Dec.{/tr}"],
			dayNames: ["{tr}Sunday{/tr}", "{tr}Monday{/tr}", "{tr}Tuesday{/tr}", "{tr}Wednesday{/tr}", "{tr}Thursday{/tr}", "{tr}Friday{/tr}", "{tr}Saturday{/tr}"],
			dayNamesShort: ["{tr}Sun{/tr}", "{tr}Mon{/tr}", "{tr}Tue{/tr}", "{tr}Wed{/tr}", "{tr}Thu{/tr}", "{tr}Fri{/tr}", "{tr}Sat{/tr}"],
			buttonText: {
				timelineDay: "{tr}resource day{/tr}",
				timelineMonth: "{tr}resource month{/tr}",
				timelineYear: "{tr}resource year{/tr}",
				timelineWeek: "{tr}resource week{/tr}",
				listDay: "{tr}list day{/tr}",
				listMonth: "{tr}list month{/tr}",
				listYear: "{tr}list year{/tr}",
				listWeek: "{tr}list week{/tr}",
				list: "{tr}list{/tr}",
				today: "{tr}today{/tr}",
				agendaMonth: "{tr}agenda month{/tr}",
				agendaWeek: "{tr}agenda week{/tr}",
				agendaDay: "{tr}agenda day{/tr}"
			},
			allDayText: "{tr}all-day{/tr}",
			firstDay: data.firstDayofWeek,
			weekends: data.weekends,
			slotDuration: data.slotDuration,
			defaultView: data.dView,
			defaultDate: data.dDate,
			eventOverlap: data.eventOverlap,
			eventAfterRender : function( event, element, view ) {
				element.popover({trigger: 'hover focus', title: event.title, content: event.description, html: true, container: 'body', placement:'bottom', boundary: 'viewPort'});
			},
			eventClick: function(event) {
				if (data.url) {
					var actualURL = data.url;
					actualURL += actualURL.indexOf("?") === -1 ? "?" : "&";

					if (data.trkitemid === "y" && data.addAllFields === "n") {	// "simple" mode
						actualURL += "itemId=" + event.id;
					} else {
						var lOp='';
						var html = $.parseHTML( event.description ) || [];

						// Store useful data values to the URL for Wiki Argument Variable
						// use and to javascript session storage for JQuery use
						actualURL += "trackerid=" + event.trackerId;
						if( data.trkitemid == 'y' ) {
							actualURL = actualURL + "&itemId=" + event.id;
						}
						else {
							actualURL = actualURL + "&itemid=" + event.id;
						}
						actualURL = actualURL + "&title=" + event.title;
						actualURL = actualURL + "&end=" + event.end;
						actualURL = actualURL + "&start=" + event.start;
						if (data.useSessionStorage) {
							sessionStorage.setItem( "trackerid", event.trackerId);
							sessionStorage.setItem( "title", event.title);
							sessionStorage.setItem( "start", event.start);
							sessionStorage.setItem( "itemid", event.id);
							sessionStorage.setItem( "end", event.end);
							sessionStorage.setItem( "eventColor", event.color);
						}

						// Capture the description HTML as variables
						// with the label being the variable name
						$.each( html, function( i, el ) {
							if( isEven( i ) == true ) {
								lOp = el.textContent.replace( ' ', '_' );
							}
							else {
								actualURL = actualURL + "&" + lOp + "=" + el.textContent;
								if (data.useSessionStorage) {
									sessionStorage.setItem( lOp, el.textContent);
								}
							}
						});
					}

					location.href=actualURL;
					return false;

				} else if (event.editable && event.trackerId) {
					var info = {
						trackerId: event.trackerId,
						itemId: event.id
					};
					$.openModal({
						remote: $.service('tracker', 'update_item', info),
						size: "modal-lg",
						title: event.title,
						open: function () {
							$('form:not(.no-ajax)', this)
								.addClass('no-ajax') // Remove default ajax handling, we replace it
								.submit(ajaxSubmitEventHandler(function (data) {
									$(this).parents(".modal").modal("hide")
									$(cal).fullCalendar('refetchEvents');
								}));
						}
					});
					return false;
				} else {
					return true;
				}

			},
			dayClick: function( date, jsEvent, view ) {
				if (data.canInsert) {
					var info = {
						trackerId: data.trackerId
					};
					info[data.beginFieldName] = date.unix();
					info[data.endFieldName] = date.add(1, 'h').unix();
					if (data.url) {
						$('<a href="#"/>').attr('href', data.url);
					} else {
						$.openModal({
							remote: $.service('tracker', 'insert_item', info),
							size: "modal-lg",
							title: data.addTitle,
							open: function () {
								$('form:not(.no-ajax)', this)
									.addClass('no-ajax') // Remove default ajax handling, we replace it
									.submit(ajaxSubmitEventHandler(function (data) {
										$(this).parents(".modal").modal("hide")
										$(cal).fullCalendar('refetchEvents');
									}));
							}
						});
					}
				}

				return false;
			},
			eventResize: storeEvent,
			eventDrop: storeEvent
		});

		$( document ).ready(function() {
			addFullCalendarPrint('#' + data.id, '#calendar-pdf-btn');
		});
	});
	function isEven(x) { return (x%2)==0; }
{/jq}
{if $pdf_export eq 'y' and $pdf_warning eq 'n'}
	<a id="calendar-pdf-btn" data-html2canvas-ignore="true"  href="#" style="float: right; display: none">{icon name="pdf"} {tr}Export as PDF{/tr}</a>
{/if}
