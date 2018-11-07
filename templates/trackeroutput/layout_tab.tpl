{tabset name="tracker_section_select"}
	{foreach $sections as $pos => $sect}
		{tab name=$sect.heading}
			<dl class="row mx-0">
				{if ! $pos}
					{if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
						{assign var=ustatus value=$info.status|default:"p"}
						<dt title="{tr}Status{/tr}" class="col-sm-3">{tr}Status{/tr}</dt>
						<dd class="col-sm-9">
							{icon name=$status_types.$ustatus.iconname}
							{$status_types.$ustatus.label}
						</dd>
					{/if}
				{/if}
				{foreach from=$sect.fields item=field}
					<dt title="{$field.name|tra|escape}" class="col-sm-3">{$field.name|tra|escape}</dt>
					<dd class="col-sm-9">{trackeroutput field=$field item=$item_info showlinks=n list_mode=n}</dd>
				{/foreach}
			</dl>
		{/tab}
	{/foreach}
{/tabset}
