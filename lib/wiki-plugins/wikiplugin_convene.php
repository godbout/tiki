<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_convene_info()
{
	return [
		'name' => tra('Convene'),
		'documentation' => 'PluginConvene',
		'description' => tra('Suggest meeting dates and times and vote to select one.'),
		'introduced' => 9,
		'prefs' => ['wikiplugin_convene','feature_calendar'],
		'body' => tra('Convene data generated from user input'),
		'iconname' => 'group',
		'filter' => 'rawhtml_unsafe',
		'tags' => [ 'basic' ],
		'params' => [
			'title' => [
				'required' => false,
				'name' => tra('Title'),
				'description' => tra('Title for the event'),
				'since' => '9.0',
				'default' => tra('Convene'),
			],
			'calendarid' => [
				'required' => false,
				'name' => tra('Calendar ID'),
				'description' => tra('ID number of the site calendar in which to store the date for the events with the most votes'),
				'since' => '9.0',
				'filter' => 'digits',
				'default' => 1,
				'profile_reference' => 'calendar',
			],
			'minvotes' => [
				'required' => false,
				'name' => tra('Minimum Votes'),
				'description' => tra('Minimum number of votes needed to show Add-to-Calendar icon, so that new users do
					not see a potentially confusing icon before the convene has enough information on it'),
				'since' => '10.3',
				'filter' => 'digits',
				'default' => 3,
			],
			'dateformat' => [
				'required' => false,
				'name' => tra('Date-Time Format'),
				'description' => tra('Display date and time in short or long format, according to the site wide setting'),
				'since' => '9.0',
				'filter' => 'alpha',
				'default' => '',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Short'), 'value' => 'short'],
					['text' => tra('Long'), 'value' => 'long']
				]
			],
			'adminperms' => [
				'required' => false,
				'name' => tra('Observe Admin Permissions'),
				'description' => tra("Only admins can edit or delete other users' votes and dates. N.B. This is a guide only as if a user can edit the page they can change this setting, it is intended to make the plugin esier to use for most users."),
				'since' => '9.0',
				'filter' => 'alpha',
				'default' => 'y',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n']
				]
			],
			'avatars' => [
				'required' => false,
				'name' => tra('Show user profile pictures'),
				'description' => tra("Show user's profile pictures next to their names."),
				'since' => '9.0',
				'filter' => 'alpha',
				'default' => 'y',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Yes'), 'value' => 'y'],
					['text' => tra('No'), 'value' => 'n']
				]
			],
		]
	];
}

function wikiplugin_convene($data, $params)
{
	global $page;
	$headerlib = TikiLib::lib('header');
	$tikilib = TikiLib::lib('tiki');
	$smarty = TikiLib::lib('smarty');
	$smarty->loadPlugin('smarty_function_icon');
	$smarty->loadPlugin('smarty_modifier_userlink');
	$smarty->loadPlugin('smarty_modifier_avatarize');
	// perms for this object
	$currentObject = current_object();
	$perms = Perms::get($currentObject);
	//in case there is any feedback from a previous ajax action since this plugin does not refresh the page upon edit
	Feedback::send_headers();

	static $conveneI = 0;
	++$conveneI;
	$i = $conveneI;

	$params = array_merge(
		[
			'title'      => 'Convene',
			'calendarid' => 1,
			'minvotes'   => 3,
			"dateformat" => 'short',
			'adminperms' => 'y',
			'avatars' => 'y',
		],
		$params
	);

	extract($params, EXTR_SKIP);

	$dataString = $data . '';
	$dataArray = [];

	//start flat static text to prepared array
	$lines = explode("\n", trim($data));
	sort($lines);
	foreach ($lines as $line) {
		$line = trim($line);

		if (! empty($line)) {
			$parts = explode(':', $line);
			$dataArray[trim($parts[0])] = trim($parts[1]);
		}
	}

	$data = TikiFilter_PrepareInput::delimiter('_')->prepare($dataArray);
	//end flat static text to prepared array

	//start get users from array
	$users = [];
	foreach (end($data['dates']) as $user => $vote) {
		$users[] = $user;
	}
	//end get users from array


	//start votes summed together
	$votes = [];
	foreach ($data['dates'] as $stamp => $date) {
		foreach ($date as $vote) {
			if (empty($votes[$stamp])) {
				$votes[$stamp] = 0;
			}
			$votes[$stamp] += (int)$vote;
		}
	}
	//end votes summed together


	//start find top vote stamp
	$topVoteStamp = 0;
	foreach ($votes as $stamp => $vote) {
		if (! isset($votes[$topVoteStamp]) || (
				isset($votes[$topVoteStamp]) &&
				$vote > $votes[$topVoteStamp]
			)
		) {
			$topVoteStamp = $stamp;
		}
	}
	//end find top vote stamp


	//start reverse array for easy listing as table
	$rows = [];
	foreach ($data['dates'] as $stamp => $date) {
		foreach ($date as $user => $vote) {
			if (isset($rows[$user][$stamp])) {
				$rows[$user][$stamp] = [];
			}

			$rows[$user][$stamp] = $vote;
		}
	}
	//end reverse array for easy listing as table

	$result = "";

	//start date header
	$dateHeader = "";
	$deleteicon = smarty_function_icon(['name' => 'delete', 'iclass' => 'tips', 'ititle' => ':' . tr('Delete Date')], $smarty->getEmptyInternalTemplate());
	$tikiDate = new TikiDate();
	$gmformat = str_replace($tikiDate->search, $tikiDate->replace, $tikilib->get_short_datetime_format());

	$canEdit = $perms->edit;
	if ($params['adminperms'] !== 'y') {
		$canAdmin = $canEdit;
	} else 	if ($currentObject['type'] === 'wiki page') {
		$canAdmin = $perms->admin_wiki;
	} else if ($currentObject['type'] === 'trackeritem') {
		$canAdmin = $perms->admin_trackers;
	} else {
		$canAdmin = $perms->admin;	// global for other object types
	}

	foreach ($votes as $stamp => $totals) {
		$dateHeader .= '<td class="conveneHeader"><span class="tips" title="' . tr('UTC date time: %0', gmdate($gmformat, $stamp)) . '">';
		if (! empty($dateformat) && $dateformat == "long") {
			$dateHeader .= $tikilib->get_long_datetime($stamp);
		} else {
			$dateHeader .= $tikilib->get_short_datetime($stamp);
		}
		$dateHeader .= '</span>';
		$dateHeader .= ($canAdmin ? " <button class='conveneDeleteDate$i icon btn btn-primary btn-sm' data-date='$stamp'>$deleteicon</button>" : "") . "</td>";
	}
	$result .= "<tr class='conveneHeaderRow'>";

	$result .= "<td style='vertical-align: middle'>" . (
		$canEdit
			?
				"<input type='button' class='conveneAddDate$i btn btn-primary btn-sm' value='" . tr('Add Date') . "'/>"
			: ""
	) . "</td>";

	$result .= "$dateHeader
		</tr>";
	//end date header


	//start user list and votes
	$userList = "";
	foreach ($rows as $user => $row) {
		$userList .= "<tr class='conveneVotes conveneUserVotes$i'>";
		$editThisUser = $canAdmin || $user === $GLOBALS['user'];

		if ($params['avatars'] === 'y') {
			$avatar = " <div class='float-right'>" . smarty_modifier_avatarize($user) . '</div>';
			$rightPadding = 'padding-right: 45px; max-width: 10em; white-space: nowrap;overflow: hidden; text-overflow: ellipsis;';
		} else {
			$avatar = '';
			$rightPadding = '';
		}

		$userList .= "<td style='white-space: nowrap'>" . $avatar . ($editThisUser ? "<button class='conveneUpdateUser$i icon btn btn-primary btn-sm'>"
				. smarty_function_icon(['name' => 'pencil', 'iclass' => 'tips', 'ititle' => ':'
					. tr("Edit User/Save changes")], $smarty->getEmptyInternalTemplate())
				. "</button><button data-user='$user' title='" . tr("Delete User")
				. "' class='conveneDeleteUser$i icon btn btn-danger btn-sm'>"
				. smarty_function_icon(['name' => 'delete'], $smarty->getEmptyInternalTemplate()) . "</button> " : "")
				. "<div style='display:inline-block;$rightPadding'>" . smarty_modifier_userlink($user) . "</div></td>";

		foreach ($row as $stamp => $vote) {
			if ($vote == 1) {
				$class = "convene-ok text-center alert-success";
				$text = smarty_function_icon(['name' => 'ok', 'iclass' => 'tips', 'ititle' => ':' . tr('OK'), 'size' => 2], $smarty->getEmptyInternalTemplate());
			} elseif ($vote == -1) {
				$class = "convene-no text-center alert-danger";
				$text = smarty_function_icon(['name' => 'remove', 'iclass' => 'tips', 'ititle' => ':' . tr('Not OK'), 'size' => 2], $smarty->getEmptyInternalTemplate());
			} else {
				$class = "convene-unconfirmed text-center text-muted";
				$text = smarty_function_icon(['name' => 'help', 'iclass' => 'tips', 'ititle' => ':' . tr('Unconfirmed'), 'size' => 2], $smarty->getEmptyInternalTemplate());
			}

			$userList .= "<td class='$class'>" . $text
				. "<input type='hidden' name='dates_" . $stamp . "_" . $user . "' value='$vote' class='conveneUserVote$i form-control' />"
				. "</td>";
		}
		$userList .= "</tr>";
	}
	$result .= $userList;
	//end user list and votes


	//start add new user and votes
	$result .= "<tr class='conveneFooterRow'>";


	if (! empty($data['dates'])) {	// need a date before adding users
		$result .= "<td>";
		if ($canAdmin) {
			$result .= "<div class='btn-group'><input class='conveneAddUser$i form-control' value='' placeholder='"
					. tr("Username...") . "' style='float:left;width:72%;border-bottom-right-radius:0;border-top-right-radius:0;'>
						<input type='button' value='+' title='" . tr('Add User')
					. "' class='conveneAddUserButton$i btn btn-primary' /></div>";
		} else if ($canEdit) {
			$result .= "<div class='btn-group'><input class='conveneAddUser$i form-control' value='{$GLOBALS['user']}' disabled='disabled'" .
						" style='float:left;width:72%;border-bottom-right-radius:0;border-top-right-radius:0;'>" .
						"<input type='button' value='+' title='" . tr('Add User') .
						"' class='conveneAddUserButton$i btn btn-primary' /></div>";
		}
		$result .= "</td>";
	}
	//end add new user and votes


	//start last row with auto selected date(s)
	$lastRow = "";
	foreach ($votes as $stamp => $total) {
		$pic = "";
		if ($total == $votes[$topVoteStamp]) {
			$pic .= ($canEdit ? smarty_function_icon(['name' => 'ok', 'iclass' => 'tips alert-success', 'ititle' => ':' . tr("Selected Date"), 'size' => 2], $smarty->getEmptyInternalTemplate()) : "");
			if ($canEdit && $votes[$topVoteStamp] >= $minvotes) {
				$pic .= "<a class='btn btn-primary btn-sm' href='tiki-calendar_edit_item.php?todate=$stamp&calendarId=$calendarid' title='"
					. tr("Add as Calendar Event") . "'>"
					. smarty_function_icon(['name' => 'calendar'], $smarty->getEmptyInternalTemplate())
					. "</a>";
			}
		}

		$lastRow .= "<td class='conveneFooter'>" . $total . "&nbsp;$pic</td>";
	}
	$result .= $lastRow;

	$result .= "</tr>";
	//end last row with auto selected date(s)
	$smarty->loadPlugin('smarty_function_ticket');
	$ticket = smarty_function_ticket(['mode' => 'get'], $smarty->getEmptyInternalTemplate());


	$result = <<<FORM
			<form id='pluginConvene$i'>
				<input type="hidden" id="convene-ticket" name="ticket" value="$ticket">
			    <div class="table-responsive">
    				<table class="table table-bordered">$result</table>
    		    </div>
			</form>
FORM;

	$conveneData = json_encode(
		[
			"dates" => $data['dates'],
			"users" => $users,
			"votes" => $votes,
			"topVote" => $votes[$topVoteStamp],
			"rows" => $rows,
			"data" => $dataString,
		]
	);

	$n = '\n';
	$regexN = '/[\r\n]+/g';


	$headerlib->add_jq_onready(
		/** @lang JavaScript */
		<<<JQ

		var convene$i = $.extend({
			fromBlank: function(user, date) {
				lockPage(function () {
					if (!user || !date) return;
					this.data = "dates_" + Date.parseUnix(date) + "_" + user;
					this.save();
				}, this);
			},
			updateUsersVotes: function() {
				lockPage(function () {
					var data = [];
					$('.conveneUserVotes$i').each(function() {
						$('.conveneUserVote$i').each(function() {
							data.push($(this).attr('name') + ' : ' + $(this).val());
						});
					});
	
					this.data = data.join('$n');
	
					this.save();
				}, this);
			},
			addUser: function(user) {
				lockPage(function (user) {
					if (!user) return;
	
					var data = [];
	
					for(date in this.dates) {
						data.push("dates_" + date + "_" + user);
					}
	
					this.data += '$n' + data.join('$n');
	
					this.save();
				}, this, [user]);
			},
			deleteUser: function(user) {
				lockPage(function (user) {
					if (!user) return;
					var data = '';
	
					for(date in this.dates) {
						for(i in this.users) {
							if (this.users.hasOwnProperty(i) && this.dates.hasOwnProperty(date) && this.users[i] !== user) {
								data += 'dates_' + date + '_' + this.users[i] + ' : ' + this.dates[date][this.users[i]] + '$n';
							}
						}
					}
	
					this.data = data;
	
					this.save(true);
				}, this, [user]);
			},
			addDate: function(date) {
				// should already be locked by the click event
				if (!date) return;
				date = Date.parseUnix(date);
				var addedData = '';

				if (! this.users.length) {	// add current user if it's the first
					this.users.push(jqueryTiki.username);
				}
				for(var user in this.users) {
					if (this.users.hasOwnProperty(user)) {
						addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
					}
				}

				this.data = (this.data + '$n' + addedData).split($regexN).sort();

				//remove empty lines
				for(var line in this.data) {
					if (this.data.hasOwnProperty(line)) {
						if (!this.data[line]) this.data.splice(line, 1);
					}
				}

				this.data = this.data.join('$n');

				this.save();
			},
			deleteDate: function(date) {
				lockPage(function (date) {
					if (! date) return;
					date += '';
					var addedData = '';
	
					for(user in this.users) {
						if( this.users.hasOwnProperty(user)) {
							addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
						}
					}
	
					var lines = convene$i.data.split($regexN);
					var newData = [];
					for(var line in lines) {
						if (lines.hasOwnProperty(line)) {
							if (!(lines[line] + '').match(date)) {
								 newData.push(lines[line]);
							}
						}
					}
	
					this.data = newData.join('$n');
					this.save();
				}, this, [date]);
			},
			save: function(reload) {
				$("#page-data").tikiModal(tr("Loading..."));
				var content = $.trim(this.data);
				
				var needReload = reload !== undefined;
				var params = {
					page: "$page",
					content: content,
					index: $i,
					type: "convene",
					ticket: $('#convene-ticket').val(),
					params: {
						title: "$title",
						calendarid: $calendarid,
						minvotes: $minvotes
					}
				};
				$.post($.service("plugin", "replace"), params, function() {
					$.get($.service("wiki", "get_page", {page: "$page"}), function (data) {
						unlockPage();
						
						if (needReload) {
							history.go(0);
						} else {
							if (data) {
								var newForm = $("#pluginConvene$i", data);
								$("#pluginConvene$i", "#page-data").replaceWith(newForm);
							}
							initConvene$i();
							$("#page-data").tikiModal();
						}
					});

				})
				.fail(function (jqXHR) {
					$("#tikifeedback").showError(jqXHR);
				})
				.always(function () {
					unlockPage();
					$("#page-data").tikiModal();
				});
			}
		}, $conveneData);

		$(window).on('beforeunload', function () {
			unlockPage();
		});
		
		window.pageLocked = false;
		
		// set semaphore
		var lockPage = function (callback, context, args) {
			var theArgs = args || [];
			if (! window.pageLocked) {
				$.getJSON($.service("semaphore", "is_set"), {
						object_type: jqueryTiki.current_object.type,
						object_id: jqueryTiki.current_object.object
					},
					function (data) {
						if (data) {
							$("#tikifeedback").showError(tr("This page is being edited by another user. Please reload the page and try again later."));
							$("#page-data").tikiModal();
						} else {
							// no one else using it, so carry on...
							$.getJSON($.service("semaphore", "set"), {
								object_type: jqueryTiki.current_object.type,
								object_id: jqueryTiki.current_object.object
							}, function () {
								window.pageLocked = true;
								callback.apply(context, theArgs);
							});
								
						}
					}
				);
			} else {
				return callback.apply(context, theArgs);
			}			
		};
		
		// unset semaphore
		var unlockPage = function () {
			if (window.pageLocked) {
				// needs to be synchronous to prevent page unload while executing
				$.ajax($.service("semaphore", "unset"), {
					async: false,
					dataType: "json",
					data: {
						object_type: jqueryTiki.current_object.type,
						object_id: jqueryTiki.current_object.object
					},
					success: function () {
						window.pageLocked = false;
					}
				});
			}
		};

		var initConvene$i = function () {
			$('.conveneAddDate$i').click(function() {
				lockPage(function () {
					var dialogOptions = {
						modal: true,
						title: tr("Add Date"),
						buttons: {}
					};
	
					dialogOptions.buttons[tr("Add")] = function() {
						convene$i.addDate(o.find('input:first').val());
						o.dialog('close');
					};
	
					var o = $('<div><input type="text" style="width: 100%;" /></div>')
						.dialog(dialogOptions);
	
					o.find('input:first')
						.datetimepicker()
						.focus();
					return false;
				}, this);
			});

			$('.conveneDeleteDate$i')
				.click(function() {
					if (confirm(tr("Delete this date?"))) {
						convene$i.deleteDate($(this).data("date"));
					}
					return false;
				});

			$('.conveneDeleteUser$i')
				.click(function() {
					if (confirm(tr("Are you sure you want to remove this user's votes?") + "\\n" +
							tr("There is no undo"))) {
						convene$i.deleteUser($(this).data("user"));
					}
					return false;
				});

			$('.conveneUpdateUser$i').click(function() {
				if ($('.conveneDeleteUser$i.btn-danger').length) {
					var updateButton = $(this);
					lockPage(function () {
						
						updateButton.find(".icon").popover("hide");
						$('.conveneUpdateUser$i').not(updateButton).hide();
						// change the delete button into cancel
						$('.conveneDeleteUser$i')
							.removeClass("btn-danger").addClass("btn-muted")
							.attr("title", tr("Cancel"))
							.off("click").click(function () {
								history.go(0);
							})
							.find('.icon').setIcon("ban");
						
						$('.conveneDeleteDate$i').hide();
						$('.conveneMain$i').hide();
						updateButton.parent().parent()
							.addClass('convene-highlight')
							.find('td').not(':first')
							.addClass('conveneTd$i')
							.addClass('convene-highlight');
		
						updateButton.find('.icon').setIcon("save");
						var parent = updateButton.parent().parent();
						parent.find('.vote').hide();
						parent.find('input').each(function() {
							$('<select>' +
								'<option value="">-</option>' +
								'<option value="-1">' + tr('Not ok') + '</option>' +
								'<option value="1">' + tr('Ok') + '</option>' +
							'</select>')
								.val($(this).val())
								.insertAfter($(this))
								.change(function() {
									var cl = '', icon = '';
		
									switch($(this).val() * 1) {
										case 1:  
											cl = 'convene-ok alert-success';
											icon = 'ok';
											break;
										case -1:
											cl = 'convene-no alert-danger';
											icon = 'remove';
											break;
										default:
											cl = 'convene-unconfirmed alert-muted';
											icon = 'help';
									}
		
									$(this)
										.parent()
										.removeClass('convene-no convene-ok convene-unconfirmed alert-success alert-danger alert-muted')
										.addClass(cl)
										.find (".icon")
											.setIcon(icon);
									$(this)
										.parent()
										.find (".icon")
										.addClass("fa-2x");
									
									convene$i.updateUsers = true;
								})
								.parent().css({position: "relative"});
						});
						
					}, this);
				} else {
					$('.conveneUpdateUser$i').show();
					$('.conveneDeleteUser$i').show();
					$('.conveneDeleteDate$i').show();
					$(this).parent().parent()
						.removeClass('convene-highlight')
						.find('.conveneTd$i')
						.removeClass('convene-highlight');
	
					$('.conveneMain$i').show();
					$(this).find('span.icon-pencil');
					parent = $(this).parent().parent();
					parent.find('select').each(function(i) {
						parent.find('input.conveneUserVote$i').eq(i).val( $(this).val() );
	
						$(this).remove();
					});
	
					if (convene$i.updateUsers) {
						convene$i.updateUsersVotes();
					}
				}
				return false;
			});

			var addUsers$i = $('.conveneAddUser$i')
				.click(function() {
					if (!$(this).data('clicked')) {
						$(this)
							.data('initval', $(this).val())
							.val('')
							.data('clicked', true);
					}
				})
				.blur(function() {
					if (!$(this).val()) {
						$(this)
							.val($(this).data('initval'))
							.data('clicked', '');

					}
				})
				.keydown(function(e) {
					var user = $(this).val();

					if (e.which == 13) {//enter
						convene$i.addUser(user);
						return false;
					}
				});

			//ensure autocomplete works, it may not be available in mobile mode
            if (addUsers$i.autocomplete) {
				addUsers$i.tiki("autocomplete", "username");
            }

            $('.conveneAddUserButton$i').click(function() {
            	if ($('.conveneAddUser$i').val()) {
	                convene$i.addUser($('.conveneAddUser$i').val());
				} else {
					$('.conveneAddUser$i').val(jqueryTiki.username).focus()
				}
				return false;
            });

			$('#pluginConvene$i .icon').css('cursor', 'pointer');
		};
		initConvene$i();
JQ
	);

	return
	<<<RETURN
~np~
	<div class="card">
		<div class="card-header">
			<h3 class="card-title">$title</h3>
		</div>
		<div class="card-body">
		    $result
		</div>
	</div>
~/np~
RETURN;
}
