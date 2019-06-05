{literal}
	<div id="workSpace" class="gantteditor"></div>

	<script type="text/javascript">
		function loadFromLocalStorage() {
			// Set a new resourceUrl
			ge.resourceUrl = 'vendor_bundled/vendor/robicch/jquery-gantt/res/';
			var ret;
			if (!ret || !ret.tasks || ret.tasks.length == 0) {
				ret= {/literal}{$ganttProject}{literal};
			}
			return ret;
		}

		function saveGanttOnServer() {
			var prj = ge.saveProject();
			var deletedTask = prj.deletedTaskIds;
			var task = prj.tasks;
			var pageUrl = window.location.href;

			if (task != '' || deletedTask != '') {
				$('#ganttSaveProject').attr("disabled", true);
				$('#ganttLoading').show();

				$.ajax({
					type: "POST",
					url: pageUrl,
					data: {
						'trackerId':'{/literal}{$trackerId}{literal}',
						'tasks':task,
						'deletedIds':deletedTask,
						'ticket':'{/literal}{$ticket}{literal}'
					}
				}).done(function() {
					window.location.href = pageUrl;
				});
			}
		}
{/literal}
		function loadI18n() {
			GanttMaster.messages = {
				"CANNOT_WRITE" : "{tr}No permission to change the following task:{/tr}",
				"CHANGE_OUT_OF_SCOPE" : "{tr}Project update not possible as you lack rights for updating a parent project.{/tr}",
				"START_IS_MILESTONE" : "{tr}Start date is a milestone.{/tr}",
				"END_IS_MILESTONE" : "{tr}End date is a milestone.{/tr}",
				"TASK_HAS_CONSTRAINTS" : "{tr}Task has constraints.{/tr}",
				"GANTT_ERROR_DEPENDS_ON_OPEN_TASK" : "{tr}Error: there is a dependency on an open task.{/tr}",
				"GANTT_ERROR_DESCENDANT_OF_CLOSED_TASK" : "{tr}Error: due to a descendant of a closed task.{/tr}",
				"TASK_HAS_EXTERNAL_DEPS" : "{tr}This task has external dependencies.{/tr}",
				"GANNT_ERROR_LOADING_DATA_TASK_REMOVED" : "{tr}GANNT_ERROR_LOADING_DATA_TASK_REMOVED{/tr}",
				"CIRCULAR_REFERENCE" : "{tr}Circular reference.{/tr}",
				"CANNOT_DEPENDS_ON_ANCESTORS" : "{tr}Cannot depend on ancestors.{/tr}",
				"INVALID_DATE_FORMAT" : "{tr}The data inserted are invalid for the field format.{/tr}",
				"GANTT_ERROR_LOADING_DATA_TASK_REMOVED" : "{tr}An error has occurred while loading the data. A task has been trashed.{/tr}",
				"CANNOT_CLOSE_TASK_IF_OPEN_ISSUE" : "{tr}Cannot close a task with open issues{/tr}",
				"TASK_MOVE_INCONSISTENT_LEVEL" : "{tr}You cannot exchange tasks of different depth.{/tr}",
				"GANTT_QUARTER_SHORT" : "{tr}Quarter{/tr}",
				"GANTT_SEMESTER_SHORT" : "{tr}Sem{/tr}",
				"CANNOT_MOVE_TASK" : "{tr}Cannot move task{/tr}",
				"PLEASE_SAVE_PROJECT" : "{tr}Please save your project{/tr}",
				"ERROR_SETTING_DATES": "{tr}Error: date settings{/tr}",
				"CANNOT_DEPENDS_ON_DESCENDANTS": "{tr}Cannot depend on descendants{/tr}",
				"GANTT_SEMESTER":"Semester",
				"GANTT_SEMESTER_SHORT":"s.",
				"GANTT_QUARTER":"Quarter",
				"GANTT_QUARTER_SHORT":"q.",
				"GANTT_WEEK":"Week",
				"GANTT_WEEK_SHORT":"w."
			};
		}
	</script>
{literal}
	<div id="gantEditorTemplates" style="display:none;">
		<div class="__template__" type="GANTBUTTONS">
		<!--
		<div class="ganttButtonBar noprint">
			<div class="buttons">
				<button onclick="$('#workSpace').trigger('undo.gantt');return false;" class="button textual icon requireCanWrite" title="undo"><span class="teamworkIcon">&#39;</span></button>
				<button onclick="$('#workSpace').trigger('redo.gantt');return false;" class="button textual icon requireCanWrite" title="redo"><span class="teamworkIcon">&middot;</span></button>
				<span class="ganttButtonSeparator requireCanWrite requireCanAdd"></span>
				<button onclick="$('#workSpace').trigger('addAboveCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanAdd" title="insert above"><span class="teamworkIcon">l</span></button>
				<button onclick="$('#workSpace').trigger('addBelowCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanAdd" title="insert below"><span class="teamworkIcon">X</span></button>
				<span class="ganttButtonSeparator requireCanWrite requireCanInOutdent"></span>
				<button onclick="$('#workSpace').trigger('outdentCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanInOutdent" title="un-indent task"><span class="teamworkIcon">.</span></button>
				<button onclick="$('#workSpace').trigger('indentCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanInOutdent" title="indent task"><span class="teamworkIcon">:</span></button>
				<span class="ganttButtonSeparator requireCanWrite requireCanMoveUpDown"></span>
				<button onclick="$('#workSpace').trigger('moveUpCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanMoveUpDown" title="move up"><span class="teamworkIcon">k</span></button>
				<button onclick="$('#workSpace').trigger('moveDownCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanMoveUpDown" title="move down"><span class="teamworkIcon">j</span></button>
				<span class="ganttButtonSeparator requireCanDelete"></span>
				<button onclick="$('#workSpace').trigger('deleteFocused.gantt');return false;" class="button textual icon delete requireCanWrite" title="Delete"><span class="teamworkIcon">&cent;</span></button>
				<span class="ganttButtonSeparator"></span>
				<button onclick="$('#workSpace').trigger('expandAll.gantt');return false;" class="button textual icon " title="EXPAND_ALL"><span class="teamworkIcon">6</span></button>
				<button onclick="$('#workSpace').trigger('collapseAll.gantt'); return false;" class="button textual icon " title="COLLAPSE_ALL"><span class="teamworkIcon">5</span></button>
				<span class="ganttButtonSeparator"></span>
				<button onclick="$('#workSpace').trigger('zoomMinus.gantt'); return false;" class="button textual icon " title="zoom out"><span class="teamworkIcon">)</span></button>
				<button onclick="$('#workSpace').trigger('zoomPlus.gantt');return false;" class="button textual icon " title="zoom in"><span class="teamworkIcon">(</span></button>
				<span class="ganttButtonSeparator"></span>
				<button onclick="print();return false;" class="button textual icon " title="Print"><span class="teamworkIcon">p</span></button>
				<span class="ganttButtonSeparator"></span>
				<button onclick="ge.gantt.showCriticalPath=!ge.gantt.showCriticalPath; ge.redraw();return false;" class="button textual icon requireCanSeeCriticalPath" title="CRITICAL_PATH"><span class="teamworkIcon">&pound;</span></button>
				<span class="ganttButtonSeparator requireCanSeeCriticalPath"></span>
				<button onclick="ge.splitter.resize(.1);return false;" class="button textual icon" ><span class="teamworkIcon">F</span></button>
				<button onclick="ge.splitter.resize(50);return false;" class="button textual icon" ><span class="teamworkIcon">O</span></button>
				<button onclick="ge.splitter.resize(100);return false;" class="button textual icon"><span class="teamworkIcon">R</span></button>
				<span class="ganttButtonSeparator"></span>
				<button onclick="$('#workSpace').trigger('fullScreen.gantt');return false;" class="button textual icon" title="fullscreen" id="fullscrbtn"><span class="teamworkIcon">@</span></button>
				<button onclick="ge.element.toggleClass('colorByStatus' );return false;" class="button textual icon"><span class="teamworkIcon">&sect;</span></button>
				&nbsp; &nbsp; &nbsp; &nbsp;
				<button onclick="saveGanttOnServer();" id="ganttSaveProject" class="btn btn-primary requireWrite" title="{tr}Save{/tr}">{tr}Save{/tr}</button>
				&nbsp;
				<img id="ganttLoading" src="../../img/spinner.gif" title="{tr}Loading{/tr}" alt="{tr}Loading{/tr}"/>
				<button class="button login" title="login/enroll" onclick="loginEnroll($(this));" style="display:none;">login/enroll</button>
				<button class="button opt collab" title="Start with Twproject" onclick="collaborate($(this));" style="display:none;"><em>collaborate</em></button>
			</div>
		</div>
		-->
		</div>
{/literal}
		<div class="__template__" type="TASKSEDITHEAD">
			<!--
			<table class="gdfTable" cellspacing="0" cellpadding="0">
				<thead>
				<tr style="height:40px">
					<th class="gdfColHeader" style="width:35px; border-right: none"></th>
					<th class="gdfColHeader" style="width:25px;">{tr}Status{/tr}</th>
					<th class="gdfColHeader gdfResizable" style="width:100px;">{tr}code/short name{/tr}</th>
					<th class="gdfColHeader gdfResizable" style="width:300px;">{tr}name{/tr}</th>
					<th class="gdfColHeader" align="center" style="width:17px;" title="{tr}Start date is a milestone.{/tr}"><span class="teamworkIcon" style="font-size: 8px;">^</span></th>
					<th class="gdfColHeader gdfResizable" style="width:80px;">{tr}start{/tr}</th>
					<th class="gdfColHeader"  align="center" style="width:17px;" title="{tr}End date is a milestone.{/tr}"><span class="teamworkIcon" style="font-size: 8px;">^</span></th>
					<th class="gdfColHeader gdfResizable" style="width:80px;">{tr}End{/tr}</th>
					<th class="gdfColHeader gdfResizable" style="width:50px;">{tr}dur.{/tr}</th>
					<th class="gdfColHeader gdfResizable" style="width:20px;">%</th>
					<th class="gdfColHeader gdfResizable requireCanSeeDep" style="width:50px;">{tr}depe.{/tr}</th>
					<th class="gdfColHeader gdfResizable" style="width:1000px; text-align: left; padding-left: 10px;">{tr}assignees{/tr}</th>
				</tr>
				</thead>
			</table>
			-->
		</div>
{literal}
		<div class="__template__" type="TASKROW">
			<!--
			<tr taskId="(#=obj.id#)" class="taskEditRow (#=obj.isParent()?'isParent':''#) (#=obj.collapsed?'collapsed':''#)" level="(#=level#)">
				<th class="gdfCell edit" align="right" style="cursor:pointer;"><span class="taskRowIndex">(#=obj.getRow()+1#)</span> <span class="teamworkIcon" style="font-size:12px;" >e</span></th>
				<td class="gdfCell noClip" align="center"><div class="taskStatus cvcColorSquare" status="(#=obj.status#)"></div></td>
				<td class="gdfCell"><input type="text" name="code" value="(#=obj.code?obj.code:''#)" placeholder="code/short name"></td>
				<td class="gdfCell indentCell" style="padding-left:(#=obj.level*10+18#)px;">
					<div class="exp-controller" align="center"></div>
					<input type="text" name="name" value="(#=obj.name#)" placeholder="name">
				</td>
				<td class="gdfCell" align="center"><input type="checkbox" name="startIsMilestone"></td>
				<td class="gdfCell"><input type="text" name="start"  value="" class="date"></td>
				<td class="gdfCell" align="center"><input type="checkbox" name="endIsMilestone"></td>
				<td class="gdfCell"><input type="text" name="end" value="" class="date"></td>
				<td class="gdfCell"><input type="text" name="duration" autocomplete="off" value="(#=obj.duration#)"></td>
				<td class="gdfCell"><input type="text" name="progress" class="validated" entrytype="PERCENTILE" autocomplete="off" value="(#=obj.progress?obj.progress:''#)" (#=obj.progressByWorklog?"readOnly":""#)></td>
				<td class="gdfCell requireCanSeeDep"><input type="text" name="depends" autocomplete="off" value="(#=obj.depends#)" (#=obj.hasExternalDep?"readonly":""#)></td>
				<td class="gdfCell taskAssigs">(#=obj.getAssigsString()#)</td>
			</tr>
			-->
		</div>
{/literal}
		<div class="__template__" type="TASKEMPTYROW">
			<!--
			<tr class="taskEditRow emptyRow" >
				<th class="gdfCell" align="right"></th>
				<td class="gdfCell noClip" align="center"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell"></td>
				<td class="gdfCell requireCanSeeDep"></td>
				<td class="gdfCell"></td>
			</tr>
			-->
		</div>
{literal}
		<div class="__template__" type="TASKBAR">
			<!--
			<div class="taskBox taskBoxDiv" taskId="(#=obj.id#)" >
				<div class="layout (#=obj.hasExternalDep?'extDep':''#)">
					<div class="taskStatus" status="(#=obj.status#)"></div>
					<div class="taskProgress" style="width:(#=obj.progress>100?100:obj.progress#)%; background-color:(#=obj.progress>100?'red':'rgb(153,255,51);'#);"></div>
					<div class="milestone (#=obj.startIsMilestone?'active':''#)" ></div>
					<div class="taskLabel"></div>
					<div class="milestone end (#=obj.endIsMilestone?'active':''#)" ></div>
				</div>
			</div>
			-->
		</div>
{/literal}
		<div class="__template__" type="CHANGE_STATUS">
			<!--
			<div class="taskStatusBox">
				<div class="taskStatus cvcColorSquare" status="STATUS_ACTIVE" title="{tr}active{/tr}"></div>
				<div class="taskStatus cvcColorSquare" status="STATUS_DONE" title="{tr}completed{/tr}"></div>
				<div class="taskStatus cvcColorSquare" status="STATUS_FAILED" title="{tr}failed{/tr}"></div>
				<div class="taskStatus cvcColorSquare" status="STATUS_SUSPENDED" title="{tr}suspended{/tr}"></div>
				<div class="taskStatus cvcColorSquare" status="STATUS_UNDEFINED" title="{tr}undefined{/tr}"></div>
			</div>
			-->
		</div>
{literal}
		<div class="__template__" type="TASK_EDITOR">
			<!--
			<div class="ganttTaskEditor">
				<h2 class="taskData">{/literal}{tr}Task editor{/tr}{literal}</h2>
				<form method="post">
					<table  cellspacing="1" cellpadding="5" width="100%" class="taskData table" border="0">
						<tr>
							<td width="200" style="height: 80px"  valign="top">
								<label for="code">{/literal}{tr}code/short name{/tr}{literal}</label><br>
								<input type="text" name="code" id="code" value="" size=15 class="formElements" autocomplete='off' maxlength=255 style='width:100%' oldvalue="1">
							</td>
							<td colspan="3" valign="top">
								<label for="name" class="required">{/literal}{tr}name{/tr}{literal}</label><br>
								<input type="text" name="name" id="name"class="formElements" autocomplete='off' maxlength=255 style='width:100%' value="" required="true" oldvalue="1">
							</td>
						</tr>
						<tr class="dateRow">
							<td nowrap="">
								<div style="position:relative">
									<label for="start">{/literal}{tr}start{/tr}{literal}</label>&nbsp;&nbsp;&nbsp;&nbsp;
									<input type="checkbox" id="startIsMilestone" name="startIsMilestone" value="yes">
									&nbsp;<label for="startIsMilestone">{/literal}{tr}is milestone{/tr}{literal}</label>&nbsp;<br>
									<input type="text" name="begin" id="start" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
									<span title="calendar" id="starts_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>
								</div>
							</td>
							<td nowrap="">
								<label for="end">{/literal}{tr}End{/tr}{literal}</label>&nbsp;&nbsp;&nbsp;&nbsp;
								<input type="checkbox" id="endIsMilestone" name="endIsMilestone" value="yes">
								&nbsp;<label for="endIsMilestone">{/literal}{tr}is milestone{/tr}{literal}</label>&nbsp;
								<br><input type="text" name="end" id="end" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
								<span title="calendar" id="ends_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>
							</td>
							<td nowrap="" >
								<label for="duration" class="">{/literal}{tr}Days{/tr}{literal}</label><br>
								<input type="text" name="duration" id="duration" size="4" class="formElements validated durationdays" title="Duration is in working days." autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DURATIONDAYS">&nbsp;
							</td>
						</tr>
						<tr>
							<td  colspan="2">
								<label for="status" class=" ">{/literal}{tr}status{/tr}{literal}</label><br>
								<select id="status" name="status" class="taskStatus" status="(#=obj.status#)" onchange="$(this).attr('STATUS',$(this).val());">
									<option value="STATUS_ACTIVE" class="taskStatus" status="STATUS_ACTIVE" >{/literal}{tr}active{/tr}{literal}</option>
									<option value="STATUS_SUSPENDED" class="taskStatus" status="STATUS_SUSPENDED" >{/literal}{tr}suspended{/tr}{literal}</option>
									<option value="STATUS_DONE" class="taskStatus" status="STATUS_DONE" >{/literal}{tr}completed{/tr}{literal}</option>
									<option value="STATUS_FAILED" class="taskStatus" status="STATUS_FAILED" >{/literal}{tr}failed{/tr}{literal}</option>
									<option value="STATUS_UNDEFINED" class="taskStatus" status="STATUS_UNDEFINED" >{/literal}{tr}undefined{/tr}{literal}</option>
								</select>
							</td>
							<td valign="top" nowrap>
								<label>{/literal}{tr}progress{/tr}{literal}</label><br>
								<input type="text" name="progress" id="progress" size="7" class="formElements validated percentile" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="PERCENTILE">
							</td>
						</tr>
						<tr>
							<td colspan="4">
								<label for="description">{/literal}{tr}Description{/tr}{literal}</label><br>
								<textarea rows="3" cols="30" id="description" name="description" class="formElements" style="width:100%"></textarea>
							</td>
						</tr>
					</table>

					<h2>{/literal}{tr}Assignments{/tr}{literal}</h2>
					<table  cellspacing="1" cellpadding="0" width="100%" id="assigsTable">
						<tr>
							<th style="width:100px;">{/literal}{tr}name{/tr}{literal}</th>
							<th style="width:70px;">{/literal}{tr}Role{/tr}{literal}</th>
							<th style="width:30px;">{/literal}{tr}est.wklg.{/tr}{literal}</th>
							<th style="width:30px;"></th>
						</tr>
					</table>

					<input type="hidden" name="trackerItemId" value="(#=obj.id#)" />
					<input type="hidden" name="trackerId" value="{/literal}{$trackerId}{literal}" />
					<input type="hidden"name="ticket" value="{/literal}{$ticket}{literal}" />

					<div style="text-align: right; padding-top: 20px">
						<button type="submit" id="saveButton" class="button first">{/literal}{tr}Save{/tr}{literal}</button>
					</div>
				</form>
			</div>
			-->
		</div>

		<div class="__template__" type="ASSIGNMENT_ROW">
			<!--
			<tr taskId="(#=obj.task.id#)" assId="(#=obj.assig.id#)" class="assigEditRow" >
				<td ><select name="resourceId" class="formElements"></select></td>
				<td ><select type="select" name="roleId" class="formElements"></select></td>
				<td ><input type="text" name="effort" value="(#=getMillisInHoursMinutes(obj.assig.effort)#)" size="5" class="formElements"></td>
				<td align="center"><span class="teamworkIcon delAssig del" style="cursor: pointer">d</span></td>
			</tr>
			-->
		</div>
{/literal}
		<div class="__template__" type="RESOURCE_EDITOR">
			<!--
			<div class="resourceEditor" style="padding: 5px;">
				<h2></h2>
				<table  cellspacing="1" cellpadding="0" width="100%" id="resourcesTable">
					<tr>
						<th style="width:100px;">{tr}name{/tr}</th>
						<th style="width:30px;" id="addResource"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
					</tr>
				</table>
				<div style="text-align: right; padding-top: 20px"><button id="resSaveButton" class="button big">{tr}Save{/tr}</button></div>
			</div>
			-->
		</div>
{literal}
		<div class="__template__" type="RESOURCE_ROW">
			<!--
			<tr resId="(#=obj.id#)" class="resRow">
				<td><input type="text" name="name" value="(#=obj.name#)" style="width:100%;" class="formElements"></td>
				<td align="center"><span class="teamworkIcon delRes del" style="cursor: pointer">d</span></td>
			</tr>
			-->
		</div>
	</div>
{/literal}