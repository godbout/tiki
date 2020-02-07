{* $Id$ *}
<div class="table-responsive">
	<table cellpadding="0" cellspacing="0" border="0" class="table normal table-striped table-hover" style="table-layout: fixed">
		<tr>
			<th style="min-width: 22em;"><a href="{$myurl}?sort_mode={if $sort_mode eq 'start_desc'}start_asc{else}start_desc{/if}">{tr}Start{/tr}</a></th>
			<th><a href="{$myurl}?sort_mode={if $sort_mode eq 'end_desc'}end_asc{else}end_desc{/if}">{tr}End{/tr}</a></th>
			<th><a href="{$myurl}?sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a></th>
			<th></th>
		</tr>
		{if $listevents|@count eq 0}{norecords _colspan=4}{/if}

		{foreach from=$listevents item=event}
			{assign var=calendarId value=$event.calendarId}
			<tr class="{cycle}{if $event.start <= $smarty.now and $event.end >= $smarty.now} selected{/if} vevent">
				<td class="date">
					<div class="row">
						<div class="dtstart col-sm-7 text-nowrap" title="{$event.start|tiki_short_date:'n'}">
							<a href="{$myurl}?todate={$event.start}" title="{tr}Change Focus{/tr}">{$event.start|tiki_short_date:'n'}</a>
						</div>
						<div class="dtstart-time col-sm-5 text-right text-nowrap">
							{if $event.allday}{tr}All day{/tr}{else}{$event.start|tiki_short_time}{/if}
						</div>
					</div>
				</td>
				<td class="date">
					<div class="row">
						{if $event.start|tiki_short_date:'n' ne $event.end|tiki_short_date:'n'}
							<div class="dtend col-sm-7 text-nowrap" title="{$event.end|tiki_short_date:'n'}">
								<a href="{$myurl}?todate={$event.end}" title="{tr}Change Focus{/tr}">{$event.end|tiki_short_date:'n'}</a>
							</div>
						{/if}
						<div class="dtstart-time col-sm-5 text-right text-nowrap">
							{if $event.start ne $event.end and $event.allday ne 1}{$event.end|tiki_short_time}{/if}
						</div>
					</div>
				</td>
				<td style="word-wrap:break-word; {if $infocals.$calendarId.custombgcolor ne ''}background-color:#{$infocals.$calendarId.custombgcolor};{/if}">
					<a class="link" href="tiki-calendar_edit_item.php?viewcalitemId={$event.calitemId}" title="{tr}View{/tr}">
					{if $infocals.$calendarId.customfgcolor ne ''}<span style="color:#{$infocals.$calendarId.customfgcolor};">{/if}
					<span class="summary">{$event.name|escape}</span></a><br>
					<span class="description" style="font-style:italic">{$event.parsed}</span>
					{if $event.web}
						<br><a href="{$event.web}" target="_other" class="calweb" title="{$event.web}"><img src="img/icons/external_link.gif" width="7" height="7" alt="&gt;"></a>
						{if $infocals.$calendarId.customfgcolor ne ''}</span>{/if}
					{/if}
				</td>
				<td class="action">
					{if $event.modifiable eq "y"}
						{actions}
							{strip}
								<action>
									<a href="tiki-calendar_edit_item.php?calitemId={$event.calitemId}">
										{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
									</a>
								</action>
								<action>
									<a class="text-danger" href="tiki-calendar_edit_item.php?calitemId={$event.calitemId}&amp;delete=1">
										{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
									</a>
								</action>
							{/strip}
						{/actions}
					{/if}
				</td>
			</tr>
		{/foreach}
	</table>
</div>
