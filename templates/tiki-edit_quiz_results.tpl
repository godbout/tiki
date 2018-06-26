{title url="tiki-edit_quiz_results.php?quizId=$quizId"}{tr}Edit quiz results{/tr}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-list_quizzes.php" class="btn btn-info" _text="{tr}List Quizzes{/tr}"}
	{button href="tiki-quiz_stats.php" class="btn btn-info" _text="{tr}Quiz Stats{/tr}"}
	{button href="tiki-quiz_stats_quiz.php?quizId=$quizId" class="btn btn-info" _text="{tr}This Quiz Stats{/tr}"}
	{button href="tiki-edit_quiz.php?quizId=$quizId" class="btn btn-primary" _text="{tr}Edit this Quiz{/tr}"}
	{button href="tiki-edit_quiz.php" class="btn btn-primary" _text="{tr}Admin Quizzes{/tr}"}
</div>

<h2>
	{tr}Create/edit questions for quiz:{/tr} <a href="tiki-edit_quiz.php?quizId={$quiz_info.quizId}" class="pageTitle">{$quiz_info.name}</a>
</h2>

<form action="tiki-edit_quiz_results.php" method="post">
	<input type="hidden" name="quizId" value="{$quizId|escape}">
	<input type="hidden" name="resultId" value="{$resultId|escape}">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}From Points{/tr}</label>
		<div class="col-sm-7">
			<input type="text" name="fromPoints" value="{$fromPoints|escape}" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}To Points{/tr}</label>
		<div class="col-sm-7">
			<input type="text" name="toPoints" value="{$toPoints|escape}" class="form-control">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Answer{/tr}</label>
		<div class="col-sm-7">
			<textarea name="answer" rows="10" cols="40" class="form-control">{$answer|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</div>
</form>

<h2>{tr}Results{/tr}</h2>

{include file='find.tpl'}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>
				<a href="tiki-edit_quiz_results.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'fromPoints_desc'}fromPoints_asc{else}fromPoints_desc{/if}">{tr}From Points{/tr}</a>
			</th>
			<th>
				<a href="tiki-edit_quiz_results.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'toPoints_desc'}toPoints_asc{else}toPoints_desc{/if}">{tr}To Points{/tr}</a>
			</th>
			<th>
				<a href="tiki-edit_quiz_results.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'position_desc'}answer_asc{else}answer_desc{/if}">{tr}Answer{/tr}</a>
			</th>
			<th></th>
		</tr>

		{section name=user loop=$channels}
			<tr>
				<td class="integer">{$channels[user].fromPoints}</td>
				<td class="integer">{$channels[user].toPoints}</td>
				<td class="text">{$channels[user].answer|truncate:230:"(...)":true|escape|nl2br}</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="tiki-edit_quiz_results.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;resultId={$channels[user].resultId}">
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-edit_quiz_results.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].resultId}">
									{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=4}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
