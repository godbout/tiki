{title}{tr}Discounts{/tr}{/title}

{tabset}

{tab name="{tr}List{/tr}"}
	<div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th class="text-center">{tr}Code{/tr}</th>
					<th class="text-center">{tr}Value{/tr}</th>
					<th class="text-center">{tr}Created{/tr}</th>
					<th class="text-center">{tr}Maximum{/tr}</th>
					<th class="text-center">{tr}Comment{/tr}</th>
					<th class="text-center"></th>
				</tr>
			</thead>

			{foreach from=$discounts.data item=discount}
				<tr>
					<td class="text text-center">{$discount.code|escape}</td>
					<td class="text text-center">{$discount.value|escape}{if !strstr($discount.value, '%')} {$prefs.payment_currency|escape}{/if}</td>
					<td class="date text-center">{$discount.created|tiki_short_date}</td>
					<td class="text text-center">{$discount.max|escape}</td>
					<td class="text text-center">{$discount.comment|escape}</td>
					<td class="action text-center">
						{actions}
							{strip}
								<action>
									{self_link id=$discount.id cookietab=2 _icon_name='edit' _menu_text='y' _menu_icon='y'}
										{tr}Edit{/tr}
									{/self_link}
								</action>
								<action>
									{self_link del=$discount.id _icon_name='edit' _menu_text='y' _menu_icon='y'}
										{tr}Delete{/tr}
									{/self_link}
								</action>
							{/strip}
						{/actions}
					</td>
				</tr>
			{foreachelse}
				{norecords _colspan=6}
			{/foreach}
		</table>
	</div>
	{pagination_links cant=$discounts.cant step=$discounts.max offset=$discounts.offset}{/pagination_links}
{/tab}

{capture name=tabtitle}{if empty($info.id)}{tr}Create{/tr}{else}{tr}Edit{/tr}{/if}{/capture}
{tab name=$smarty.capture.tabtitle}
	<form method="post" action="tiki-discount.php">
		<br>
		{if !empty($info.id)}<input type="hidden" name="id" value="{$info.id}">{/if}
		<div class="form-group row">
			<label class="col-sm-3 col-form-label">{tr}Code{/tr}</label>
			<div class="col-sm-7">
				<input type="text" id="code" name="code" {if !empty($info.code)}value="{$info.code|escape}"{/if} class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label">{tr}Value{/tr}</label>
			<div class="col-sm-7">
				<input type="text" id="code" name="code" {if !empty($info.code)}value="{$info.code|escape}"{/if} class="form-control">
				<div class="form-text">
					{tr}{$prefs.payment_currency|escape}{/tr} {tr} or {/tr}
				</div>
				<input type="text" id="percent" name="percent" {if !empty($info.percent)} value="{$info.percent|escape}"{/if} class="form-control">
				<div class="form-text">
					%
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label">{tr}Maximum time the discount can be used in the first phase of payment{/tr}</label>
			<div class="col-sm-7">
				<input type="text" id="max" name="max" {if !empty($info.max)} value="{$info.max|escape}"{/if} class="form-control">
				<div class="form-text">
					{tr}-1 for unlimited{/tr}
				</div>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label">{tr}Comment{/tr}</label>
			<div class="col-sm-7">
				<input type="text" id="comment" name="comment" {if !empty($info.comment)} value="{$info.comment|escape}"{/if} class="form-control">
			</div>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label"></label>
			<div class="col-sm-7">
				<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
			</div>
		</div>
	</form>
{/tab}

{/tabset}
