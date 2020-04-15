<div class="form-row align-items-center">
  <div class="col-auto"> {* Prevent input from overflowing in narrow screens *}
    <input type="number" class="currency_number numeric form-control" name="{$control.field|escape}_from"
  {if $control.meta.size}size="{$control.meta.size|escape}" maxlength="{$control.meta.size|escape}"{/if}
  value="{$control.from|escape}" id="{$control.field|escape}_from" step="0.01">
  </div>
  <div class="col-auto">
    {if $control.meta.currencies}
      <select name="{$control.field|escape}_from_currency" id="{$control.field|escape}_from_currency" class="currency_code form-control">
      <option value=""></option>
        {foreach from=$control.meta.currencies item=c}
          <option value="{$c}" {if $c eq $control.fromCurrency}selected{/if}>{$c}</option>
        {/foreach}
      </select>
    {/if}

    {if $control.meta.error}
      {$control.meta.error}
    {/if}
  </div>
</div>
<div class="form-row align-items-center">
  <div class="col-auto"> {* Prevent input from overflowing in narrow screens *}
    <input type="number" class="currency_number numeric form-control" name="{$control.field|escape}_to"
  {if $control.meta.size}size="{$control.meta.size|escape}" maxlength="{$control.meta.size|escape}"{/if}
  value="{$control.to|escape}" id="{$control.field|escape}_to" step="0.01">
  </div>
  <div class="col-auto">
    {if $control.meta.currencies}
      <select name="{$control.field|escape}_to_currency" id="{$control.field|escape}_to_currency" class="currency_code form-control">
      <option value=""></option>
        {foreach from=$control.meta.currencies item=c}
          <option value="{$c}" {if $c eq $control.toCurrency}selected{/if}>{$c}</option>
        {/foreach}
      </select>
    {/if}

    {if $control.meta.error}
      {$control.meta.error}
    {/if}
  </div>
</div>