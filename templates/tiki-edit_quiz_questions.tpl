{* $Id$ *}

{* Copyright (c) 2002-2008 *}
{* All Rights Reserved. See copyright.txt for details and a complete list of authors. *}
{* Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details. *}

{title help="Quiz" url="tiki-edit_quiz_questions.php?quizId=$quizId"}{tr}Edit quiz questions{/tr}{/title}

<div class="t_navbar mb-4">
	{button href="tiki-list_quizzes.php" class="btn btn-info" _text="{tr}List Quizzes{/tr}"}
	{button href="tiki-quiz_stats.php" class="btn btn-info" _text="{tr}Quiz Stats{/tr}"}
	{button href="tiki-quiz_stats_quiz.php?quizId=$quizId" class="btn btn-info" _text="{tr}This Quiz Stats{/tr}"}
	{button href="tiki-edit_quiz.php?quizId=$quizId" class="btn btn-primary" _text="{tr}Edit this Quiz{/tr}"}
	{button href="tiki-edit_quiz.php" class="btn btn-primary" _text="{tr}Admin Quizzes{/tr}"}
</div>

<h2>{tr}Create/edit questions for quiz:{/tr} <a href="tiki-edit_quiz.php?quizId={$quiz_info.quizId}" >{$quiz_info.name|escape}</a></h2>
<br>
<form action="tiki-edit_quiz_questions.php" method="post">
	<input type="hidden" name="quizId" value="{$quizId|escape}">
	<input type="hidden" name="questionId" value="{$questionId|escape}">

	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Question{/tr}</label>
		<div class="col-sm-7">
			<textarea name="question" rows="5" cols="80" class="form-control">{$question|escape}</textarea>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Position{/tr}</label>
		<div class="col-sm-7">
			<select name="position" class="form-control">{html_options values=$positions output=$positions selected=$position}</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Question Type{/tr}</label>
		<div class="col-sm-7">
			<select name="questionType" class="form-control">{html_options options=$questionTypes selected=$type}</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		</div>
	</div>
</form>

<h2>{tr}Import questions from text{/tr}
	{if $prefs.feature_help eq 'y'}
		<a href="{$prefs.helpurl}Quiz+Question+Import" target="tikihelp" class="tikihelp text-info">
			{icon name='help'}
		</a>
	{/if}
</h2>

<!-- begin form area for importing questions -->
<form enctype="multipart/form-data" method="post" action="tiki-edit_quiz_questions.php?quizId={$quiz_info.quizId}">
	<div class="form-text">
		{tr}Instructions: Type, or paste your multiple choice questions below. Provide one line for the question, then provide as many answers on want on subsequent lines. Separate questions with a blank line. To indicate correct answers, you may initiate an answer with "*" (without the quotes). None, any or all the answers are possible to be marked as correct.{/tr}
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label">{tr}Input{/tr}</label>
		<div class="col-sm-7">
			<textarea class="form-control wikiedit" name="input_data" rows="30" cols="80" id='subheading'></textarea>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label"></label>
		<div class="col-sm-7">
			<input type="submit" class="wikiaction btn btn-primary" name="import" value="Import">
		</div>
	</div>
</form>

<!-- begin form for searching questions -->
<h2>{tr}Questions{/tr}</h2>
{include file='find.tpl'}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
	<table class="table table-striped table-hover">
		<tr>
			<th>
				<a href="tiki-edit_quiz_questions.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'questionId_desc'}questionId_asc{else}questionId_desc{/if}">{tr}ID{/tr}</a>
			</th>
			<th>
				<a href="tiki-edit_quiz_questions.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'position_desc'}position_asc{else}position_desc{/if}">{tr}Position{/tr}</a>
			</th>
			<th>
				<a href="tiki-edit_quiz_questions.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'question_desc'}question_asc{else}question_desc{/if}">{tr}Question{/tr}</a>
			</th>

			<th>{tr}Options{/tr}</th>
			<th>{tr}Maximum score{/tr}</th>
			<th></th>
		</tr>

		{section name=user loop=$channels}
			<tr>
				<td class="id">{$channels[user].questionId}</td>
				<td class="id">{$channels[user].position}</td>
				<td class="text">{$channels[user].question|escape}</td>
				<td class="integer">{$channels[user].options}</td>
				<td class="integer">{$channels[user].maxPoints}</td>
				<td class="action">
					{actions}
						{strip}
							<action>
								<a href="tiki-edit_question_options.php?quizId={$quizId}&amp;questionId={$channels[user].questionId}">
									{icon name='list' _menu_text='y' _menu_icon='y' alt="{tr}Options{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-edit_quiz_questions.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;questionId={$channels[user].questionId}">
									{icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
								</a>
							</action>
							<action>
								<a href="tiki-edit_quiz_questions.php?quizId={$quizId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove={$channels[user].questionId}">
									{icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
								</a>
							</action>
						{/strip}
					{/actions}
				</td>
			</tr>
		{sectionelse}
			{norecords _colspan=6}
		{/section}
	</table>
</div>

{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
<!-- tiki-edit_quiz_questions.tpl end -->
