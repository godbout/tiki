{* $Id$ *}
{title help="Scheduler" admpage="general" url="tiki-admin_schedulers.php"}{tr}Scheduler{/tr}{/title}
<div class="t_navbar mb-4">
	{if isset($schedulerinfo.id)}
		{button href="?add=1" class="btn btn-primary" _text="{tr}Add a new Scheduler{/tr}"}
	{/if}

</div>
{tabset name='tabs_admin_schedulers'}

{* ---------------------- tab with list -------------------- *}
{if $schedulers|count > 0}
	{tab name="{tr}Schedulers{/tr}"}
		<div id="admin_schedulers-div">
			<div class="{if $js}table-responsive {/if}ts-wrapperdiv">
				{* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
				<table id="admin_schedulers" class="table normal table-striped table-hover" data-count="{$schedulers|count}">
					<thead>
					<tr>

						<th>
							{tr}Name{/tr}
						</th>
						<th>
							{tr}Description{/tr}
						</th>
						<th>
							{tr}Task{/tr}
						</th>
						<th>
							{tr}Run Time{/tr}
						</th>
						<th>
							{tr}Status{/tr}
						</th>
						<th>
							{tr}Run only once{/tr}
						</th>
						<th>
							{tr}Re-Run{/tr}
						</th>
						<th>
							{*Reserved for stalled notices*}
						</th>
						<th id="actions"></th>
					</tr>
					</thead>

					<tbody>
					{section name=scheduler loop=$schedulers}
						{$scheduler_name = $schedulers[scheduler].name|escape}
						<tr>
							<td class="scheduler_name">
								<a class="link tips"
									href="tiki-admin_schedulers.php?scheduler={$schedulers[scheduler].id}{if $prefs.feature_tabs ne 'y'}#2{/if}"
									title="{$scheduler_name}:{tr}Edit scheduler settings{/tr}"
								>
									{$scheduler_name}
								</a>
							</td>
							<td class="scheduler_description">
								{$schedulers[scheduler].description|escape}
							</td>
							<td class="scheduler_task">
								{$schedulers[scheduler].task|escape}
							</td>
							<td class="scheduler_run_time">
								{$schedulers[scheduler].run_time|escape}
							</td>
							<td class="scheduler_status">
								{$schedulers[scheduler].status|escape|ucfirst}
							</td>
							<td class="scheduler_run_only_once">
								<input type="checkbox" {if $schedulers[scheduler].run_only_once}checked{/if} disabled>
							</td>
							<td class="scheduler_re_run">
								<input type="checkbox" {if $schedulers[scheduler].re_run}checked{/if} disabled>
							</td>
							<td class="scheduler_stalled">
								{if $schedulers[scheduler].stalled}
									<span class="label label-danger">{tr}Stalled{/tr}</span>
								{/if}
							</td>
							<td class="action">
								{actions}
									{strip}
										{if $schedulers[scheduler].stalled}
											<action>
												<a href="{bootstrap_modal controller=scheduler action=reset schedulerId=$schedulers[scheduler].id}">
													{icon name="undo" _menu_text='y' _menu_icon='y' alt="{tr}Reset{/tr}"}
												</a>
											</action>
										{else}
											<action>
												<a href="{service controller=scheduler action=run schedulerId=$schedulers[scheduler].id modal=1}" onclick="runNow(event)" disabled>
													{icon name="play" _menu_text='y' _menu_icon='y' alt="{tr}Run now{/tr}"}
												</a>
											</action>
											<action>
												<a href="{service controller=scheduler action=run_background schedulerId=$schedulers[scheduler].id}" onclick="runNowBackground(event)" disabled>
													{icon name="play" _menu_text='y' _menu_icon='y' alt="{tr}Run now{/tr} ({tr}Background{/tr})"}
												</a>
											</action>
										{/if}
										<action>
											<a href="tiki-admin_schedulers.php?scheduler={$schedulers[scheduler].id}">
												{icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
											</a>
										</action>
										<action>
											<a href="{query _type='relative' scheduler=$schedulers[scheduler].id logs='1'}">
												{icon name="log" _menu_text='y' _menu_icon='y' alt="{tr}Logs{/tr}"}
											</a>
										</action>
										<action>
											<a href="{bootstrap_modal controller=scheduler action=remove schedulerId=$schedulers[scheduler].id}">
												{icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
											</a>
										</action>
									{/strip}
								{/actions}
							</td>
						</tr>
					{/section}
					</tbody>
				</table>
			</div>
		</div>
	{/tab}
{/if}

{* ---------------------- tab with form -------------------- *}
<a id="tab2"></a>
{if isset($schedulerinfo.id) && $schedulerinfo.id}
	{$add_edit_scheduler_tablabel = "{tr}Edit scheduler{/tr}"}
	{$schedulename = "<i>{$schedulerinfo.name|escape}</i>"}
{else}
	{$add_edit_scheduler_tablabel = "{tr}Add a new scheduler{/tr}"}
	{$schedulename = ""}
{/if}

{tab name="{$add_edit_scheduler_tablabel} {$schedulename}"}
	<br><br>
	<div class="row">
		<div class="offset-sm-2 col-sm-10">
			{remarksbox type="note" title="{tr}Information{/tr}"}
			{tr}Use CRON format to enter the values in "Run Time":
				<br>
				Minute, Hour, Day of Month, Month, Day of Week
				<br>
				Eg. every 5 minutes: */5 * * * *{/tr}
			{/remarksbox}
		</div>
	</div>
	<form class="form form-horizontal" action="tiki-admin_schedulers.php" method="post"
			enctype="multipart/form-data" name="RegForm" autocomplete="off">
		{ticket}
		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="scheduler_name">{tr}Name{/tr} *</label>
			<div class="col-sm-10">
				<input type="text" id='scheduler_name' class="form-control" name='scheduler_name'
					value="{$schedulerinfo.name|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="scheduler_description">{tr}Description{/tr}</label>
			<div class="col-sm-10">
				<input type="text" id='scheduler_description' class="form-control" name='scheduler_description'
					value="{$schedulerinfo.description|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="scheduler_task">{tr}Task{/tr} *</label>
			<div class="col-sm-10">
				<select id="scheduler_task" name="scheduler_task" class="form-control">
					<option value=''></option>
					{html_options options=$schedulerTasks selected=$schedulerinfo.task}
				</select>
			</div>
		</div>

		{foreach from=$schedulerTasks key=commandName item=taskName}
			{scheduler_params name=$commandName params=$schedulerinfo.params}
		{/foreach}

		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="scheduler_time">{tr}Run Time{/tr} *</label>
			<div class="col-sm-10">
				<input type="text" id='scheduler_time' class="form-control" name='scheduler_time'
					value="{$schedulerinfo.run_time|escape}">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="scheduler_status">{tr}Status{/tr}</label>
			<div class="col-sm-10">
				<select id="scheduler_status" name="scheduler_status" class="form-control">
					schedulerStatus
					{html_options options=$schedulerStatus selected=$schedulerinfo.status}
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-2 form-check-label" for="scheduler_catch">{tr}Run if missed{/tr}</label>
			<div class="col-sm-10">
				<div class="form-check">
					<input type="checkbox" id="scheduler_rerun" class="form-check-input" name="scheduler_rerun"
						{if $schedulerinfo.re_run}checked{/if}>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-2 form-check-label" for="scheduler_catch">{tr}Run only once{/tr}</label>
			<div class="col-sm-10">
				<div class="form-check">
					<input type="checkbox" id="scheduler_run_only_once" class="form-check-input" name="scheduler_run_only_once"
						{if $schedulerinfo.run_only_once}checked{/if}>
				</div>
			</div>
		</div>
		<div class="form-group row">
			<div class="col-sm-10 offset-sm-3">
				{if isset($schedulerinfo.id) && $schedulerinfo.id}
					<input type="hidden" name="scheduler" value="{$schedulerinfo.id|escape}">
					<input type="hidden" name="editscheduler" value="1">
					<input type="submit" class="btn btn-secondary" name="save" value="{tr}Save{/tr}">
				{else}
					<input type="submit" class="btn btn-secondary" name="new_scheduler" value="{tr}Add{/tr}">
				{/if}
			</div>
		</div>

	</form>
{/tab}

	<a id="tab3"></a>
	{if isset($schedulerinfo.id) && $schedulerinfo.id}
		{tab name="{tr}Scheduler logs{/tr}"}
			<h2>{tr}Scheduler{/tr} {$schedulerinfo.name|escape} Logs</h2>
			<h3>{tr}Last {$numOfLogs} Logs{/tr}</h3>
			<table class="table normal table-striped table-hover">
				<thead>
				<tr>
					<th>ID</th>
					<th>Start Time</th>
					<th>End Time</th>
					<th>Status</th>
					<th>Output</th>
				</tr>
				</thead>
				<tbody>
				{section name=run loop=$schedulerruns}
					<tr>
						<td>{$schedulerruns[run].id}</td>
						<td>{$schedulerruns[run].start_time|tiki_short_datetime}</td>
						<td>{if $schedulerruns[run].end_time ne null}{$schedulerruns[run].end_time|tiki_short_datetime}{/if}</td>
						<td>
							{if $schedulerruns[run].status eq 'running'}
								<span class="badge badge-warning">{tr}Running{/tr}</span>
							{/if}
							{if $schedulerruns[run].status eq 'failed'}
								<span class="badge badge-danger">{tr}Failed{/tr}</span>

							{/if}
							{if $schedulerruns[run].status eq 'done'}
								<span class="badge badge-success">{tr}Done{/tr}</span>
							{/if}
						</td>
						<td>
							{if $schedulerruns[run].can_stop}
								<a class="btn btn-secondary btn-sm" href="{bootstrap_modal controller=scheduler action=reset schedulerId=$schedulerruns[run].scheduler_id startTime=$schedulerruns[run].start_time}">
								{icon name="undo" _menu_text='y' _menu_icon='y' alt="{tr}Reset{/tr}"}
								</a>
							{else}
								{$schedulerruns[run].output|nl2br}
							{/if}
						</td>
					</tr>
				{/section}
				</tbody>
			</table>
			{pagination_links cant=$cant step=$numrows offset=$offset}tiki-admin_schedulers.php?scheduler={$schedulerinfo.id}&cookietab=3{/pagination_links}
		{/tab}
	{/if}
{/tabset}

{jq}
	var selectedSchedulerTask = $('select[name="scheduler_task"]').val();
	$('div [data-task-name="'+selectedSchedulerTask+'"]').show();

	$('select[name="scheduler_task"]').on('change', function() {
		var taskName = this.value;
		$('div [data-task-name]:not([data-task-name="'+taskName+'"])').hide();
		$('div [data-task-name="'+taskName+'"]').show();
	});

	$('form[name="RegForm"]').validate({
		rules: {
			scheduler_time: {
				validate_cron_runtime: true
			}
		}
	});
{/jq}
