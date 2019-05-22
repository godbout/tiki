{title help="FAQs" admpage="faqs"}{tr}FAQs{/tr}{/title}

{tabset name='tabs_list_faqs'}
	{tab name="{tr}Available FAQs{/tr}"}
		<h2>{tr}Available FAQs{/tr}</h2>

		{if $channels or ($find ne '')}
			{include file='find.tpl'}
		{/if}

		<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
			<table class="table table-striped table-hover">
				<tr>
					<th>
						<a href="tiki-list_faqs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Title{/tr}</a>
					</th>
					<th style="text-align:right;">
						<a href="tiki-list_faqs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'hits_desc'}hits_asc{else}hits_desc{/if}">{tr}Visits{/tr}</a>
					</th>
					<th style="text-align:right;">
						<a href="tiki-list_faqs.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'questions_desc'}questions_asc{else}questions_desc{/if}">{tr}Questions / Suggested{/tr}</a>
					</th>
					{if $tiki_p_admin_faqs eq 'y'}
						<th></th>
					{/if}
				</tr>

				{section name=user loop=$channels}
					<tr>
						<td class="text">
							<a class="tablename" href="tiki-view_faq.php?faqId={$channels[user].faqId}">{$channels[user].title|escape}</a>
							<div class="subcomment">
								{$channels[user].description|escape|nl2br}
							</div>
						</td>
						<td class="integer">
							<span class="badge badge-secondary">{$channels[user].hits}</span>
						</td>
						<td class="integer">
							<span class="badge badge-secondary">{$channels[user].questions}</span> / <span class="badge badge-secondary">{$channels[user].suggested}</span>
						</td>
						{if $tiki_p_admin_faqs eq 'y'}
							<td class="action">
								{actions}
									{strip}
										<action>
											<a href="tiki-list_faqs.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;faqId={$channels[user].faqId}">
												{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
											</a>
										</action>
										<action>
											<a href="tiki-faq_questions.php?faqId={$channels[user].faqId}">
												{icon name='help' _menu_text='y' _menu_icon='y' alt="{tr}Questions{/tr}"}
											</a>
										</action>
										<action>
											<a href="tiki-list_faqs.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].faqId}">
												{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
											</a>
										</action>
									{/strip}
								{/actions}
							</td>
						{/if}
					</tr>
				{sectionelse}
					{if $tiki_p_admin_faqs eq 'y'}{norecords _colspan=5}{else}{norecords _colspan=4}{/if}
				{/section}
			</table>
		</div>

		{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
	{/tab}

	{if $tiki_p_admin_faqs eq 'y'}
		{tab name="{tr}Edit/Create{/tr}"}
			{if $faqId > 0}
				<h2>{tr}Edit this FAQ:{/tr} {$title}</h2>
				<div class="t_navbar mb-2">
					{button href="tiki-list_faqs.php" class="btn btn-primary" _text="{tr}Create new FAQ{/tr}"}
				</div>
			{else}
				<h2>{tr}Create New FAQ:{/tr}</h2>
			{/if}

			<form action="tiki-list_faqs.php" method="post">
				<input type="hidden" name="faqId" value="{$faqId|escape}">
				<div class="form-group row">
					<label class="col-form-label col-md-4">
						{tr}Title:{/tr}
					</label>
					<div class="col-md-8">
						<input type="text" class="form-control" name="title" value="{$title|escape}">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-md-4">
						{tr}Description:{/tr}
					</label>
					<div class="col-md-8">
						<textarea name="description" class="form-control">{$description|escape}</textarea>
					</div>
				</div>
				<div class="form-group row">
					<label class="col-form-label col-md-4">
						{tr}Users can suggest questions:{/tr}
					</label>
					<div class="col-md-8">
						<input type="checkbox" name="canSuggest" {if $canSuggest eq 'y'}checked="checked"{/if}>
					</div>
				</div>
				{include file='categorize.tpl'}
				<div class="row">
					<div class="form-group col-lg-12 clearfix">
						<div class="text-center">
							<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
						</div>
					</div>
				</div>
			</form>
		{/tab}
	{/if}
{/tabset}

