{title help="tests"}{tr}TikiTests Replay Configuration{/tr}{/title}

<h2 class='pagetitle'>TikiTest:{$filename}</h2>
<br/>
{if $summary eq 'y' and is_array($result) and sizeof($result) gt 0}
	{if $test_success eq $test_count}
		<b><font color="green">{tr}Success{/tr}</font></b>
	{else}
		<b><font color="red">{tr}Failure{/tr}</font></b> {$test_success}/{$test_count}
	{/if}
{else}
	{include file='tiki-tests_menubar.tpl'}
	<fieldset>
		<legend>{tr}Options{/tr}</legend>
		<form action="tiki_tests/tiki-tests_replay.php" method="post">
			<div class="form-check">
			<label class="form-check-label">{tr}Summary mode{/tr}<input type="checkbox" class="form-check-input" name="summary" value="y" {if $summary eq 'y'} checked="checked"{/if}></label><br>
			<label class="form-check-label">{tr}Show Page Differences{/tr}<input type="checkbox" class="form-check-input" name="show_page" value="y" {if $show_page eq 'y'} checked="checked"{/if}><br>
			<label class="form-check-label">{tr}Show Tidy Errors and Warnings{/tr}<input type="checkbox" class="form-check-input" name="show_tidy" value="y" {if $show_tidy eq 'y'} checked="checked"{/if}><br>
			<label class="form-check-label">{tr}Show POST Data{/tr}<input type="checkbox" class="form-check-input" name="show_post" value="y" {if $show_post eq 'y'} checked="checked"{/if}><br>
			<label class="form-check-label">{tr}Use Current Session/Log out{/tr}<input type="checkbox" class="form-check-input" name="current_session" value="y" {if $current_session eq 'y'} checked="checked"{/if}><br>
			</div>
			<input type="hidden" name="filename" value="{$filename}">
			<center><input type="submit" class="btn btn-primary btn-sm" name="action" value="{tr}Replay{/tr}"></center>
		</form>
	</fieldset>
	{if is_array($result) and sizeof($result) gt 0}
		<fieldset>
			<legend>{tr}Results{/tr}</legend>
			<div class="table-responsive">
				<table class="table">
					{foreach from=$result item=r}
						<tr>
							<th style="width:10%">{tr}Request:{/tr}&nbsp;{$r.method}</th><td>{$r.url}</td>
						</tr>
						{if isset($r.post) and $show_post}
							<tr>
								<th colspan="4">{tr}Post Variables{/tr}</th>
							</tr>
							{foreach from=$r.post item=p key=k}
								<tr>
									<td colspan="2">{$k}</td><td colspan="2">{$p}</td>
								</tr>
							{/foreach}
						{/if}
						<tr>
							<td colspan="4">
								<div class="table-responsive">
									<table class="table">
										{if $show_tidy}
											<tr><th colspan="2">{tr}Tidy Results{/tr}&nbsp;{tr}Reference{/tr}</th><th colspan="2">{tr}Tidy Results{/tr}&nbsp;{tr}Replay{/tr}</th></tr>
											<tr>
												<td colspan="2" style="width:50%"><pre>{$r.ref_error_msg|escape:"html"}</pre></td>
												<td colspan="2" style="width:50%"><pre>{$r.replay_error_msg|escape:"html"}</pre></td>
											</tr>
										{/if}
										{if $r.html}
											{if $show_page}
												<tr><th colspan="4" border="1">{tr}Results{/tr}</th></tr>
												{$r.html}
											{else}
												<tr>
													<th colspan="1">{tr}Results{/tr}</th>
													<td colspan="3" style="color: red; font-weight: bold">{tr}The pages are different{/tr}</td>
												</tr>
											{/if}
										{else}
											<tr>
												<th colspan="1">{tr}Results{/tr}</th>
												<td colspan="3" style="color: green; font-weight: bold">{tr}The pages are identical{/tr}</td>
											</tr>
										{/if}
									</table>
								</div>
							</td>
						</tr>
					{/foreach}
				</table>
			</div>
		</fieldset>
	{/if}
{/if}
