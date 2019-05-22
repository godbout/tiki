{title}{tr}Admin FAQ:{/tr} {$faq_info.title}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-list_faqs.php" class="btn btn-info" _text="{tr}List FAQs{/tr}"}
	{button href="tiki-view_faq.php?faqId=$faqId" class="btn btn-info" _text="{tr}View FAQ{/tr}"}
	{button href="tiki-list_faqs.php?faqId=$faqId" class="btn btn-primary" _text="{tr}Edit this FAQ{/tr}"}
	{button href="tiki-faq_questions.php?faqId=$faqId" class="btn btn-primary" _text="{tr}New Question{/tr}"}
</div>

<h2>{if $questionId}{tr}Edit FAQ question{/tr}{else}{tr}Add FAQ question{/tr}{/if}</h2>
<br>
<form action="tiki-faq_questions.php" method="post" id="editpageform">
	<input type="hidden" name="questionId" value="{$questionId|escape}">
	<input type="hidden" name="faqId" value="{$faqId|escape}">

	<div class="form-group row mx-0">
		<label class="col-sm-3 col-form-label">{tr}Question{/tr}</label>
		<div class="col-sm-8">
			<textarea type="text" rows="2" cols="80" name="question" class="form-control" tabindex="1">{$question|escape}</textarea>
		</div>
	</div>
	<div class="form-group row mx-0">
		<label class="col-sm-3 col-form-label">{tr}Answer{/tr}</label>
		<div class="col-sm-8">
			{toolbars area_id="faqans"}
			<textarea id='faqans' type="text" rows="8" cols="80" name="answer" class="form-control" tabindex="2">{$answer|escape}</textarea>
		</div>
	</div>
	<div class="form-group row mx-0">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-8">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}" tabindex="3">
		</div>
	</div>
</form>

{* This is the area for choosing questions from the db... it really should support choosing options from the answers, but only show if there are existing questions *}
{if $allq}
	<h2> {tr}Use a question from another FAQ{/tr}</h2>
	<br>
	<form action="tiki-faq_questions.php" method="post">
		<input type="hidden" name="questionId" value="{$questionId|escape}">
		<input type="hidden" name="faqId" value="{$faqId|escape}">
		<div class="form-group row mx-0">
			<label class="col-sm-3 col-form-label">{tr}Filter{/tr}</label>
			<div class="col-sm-8">
				<div class="input-group">
					<input type="text" name="filter" id="filter" value="{$filter|escape}" class="form-control input-sm">
					<div class="input-group-append">
						<input type="submit" class="btn btn-info btn-sm" name="filteruseq" value="{tr}Filter{/tr}">
					</div>
				</div>
			</div>
		</div>
		<div class="form-group row mx-0">
			<label class="col-sm-3 col-form-label">{tr}Question{/tr}</label>
			<div class="col-sm-8">
				<select name="usequestionId" class="form-control">
					{section name=ix loop=$allq}
						{* Ok, here's where you change the truncation field for this field *}
						<option value="{$allq[ix].questionId|escape|truncate:20:"":true}">{$allq[ix].question|escape|truncate:110:"":true}</option>
					{/section}
				</select>
			</div>
		</div>
		<div class="form-group row mx-0">
			<label class="col-sm-3 col-form-label"></label>
			<div class="col-sm-8">
				<input type="submit" class="btn btn-primary btn-sm" name="useq" value="{tr}Use{/tr}">
			</div>
		</div>
	</form>
{/if}
<br>

{* next big chunk *}
<br>
<h2>{tr}FAQ questions{/tr}</h2>
{if $channels or ($find ne '')}
	{include file='find.tpl'}
{/if}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>
				<a href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'questionId_desc'}questionId_asc{else}questionId_desc{/if}">{tr}ID{/tr}</a>
			</th>
			<th>
				<a href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'question_desc'}question_asc{else}question_desc{/if}">{tr}Question{/tr}</a>
			</th>
			<th>{tr}Action{/tr}</th>
		</tr>

		{section name=user loop=$channels}
		<tr>
			<td class="id">{$channels[user].questionId}</td>
			<td class="text">{$channels[user].question|escape}</td>
			<td class="action">
				{actions}
					{strip}
						<action>
							<a href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;questionId={$channels[user].questionId}">
								{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
							</a>
						</action>
						<action>
							<a class="text-danger" href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].questionId}">
								{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
							</a>
						</action>
					{/strip}
				{/actions}
			</td>
		</tr>
		{sectionelse}
			{norecords _colspan=3}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}

{if count($suggested) > 0}

	<h2>{tr}Suggested questions{/tr}</h2>
	<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
		<table class="table table-striped table-hover">
			<tr>
				<th>{tr}Question{/tr}</th>
				<th>{tr}Answer{/tr}</th>
				<th></th>
			</tr>

			{section name=ix loop=$suggested}
				<tr>
					<td class="text">{$suggested[ix].question|escape} </td>
					<td class="text">{$suggested[ix].answer|escape}</td>
					<td class="action">
						{actions}
							{strip}
								<action>
									<a href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;approve_suggested={$suggested[ix].sfqId}">
										{icon name='ok' _menu_text='y' _menu_icon='y' alt="{tr}Approve{/tr}"}
									</a>
								</action>
								<action>
									<a class="text-danger" href="tiki-faq_questions.php?faqId={$faqId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove_suggested={$suggested[ix].sfqId}">
										{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
									</a>
								</action>
							{/strip}
						{/actions}
					</td>
				</tr>
			{/section}
		</table>
	</div>
{else}
	<h2>{tr}No suggested questions{/tr}</h2>
{/if}
