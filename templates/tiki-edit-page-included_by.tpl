<ul>
  {foreach from=$included_by item=include}
  <li>
    {object_type type=$include.type}:
    {object_link type=$include.type objectId=$include.itemId class="alert-link"}
    {if $include.start || $include.end} - {/if}
    {if $include.start}
    {tr}from{/tr} "{$include.start}"
    {/if}
    {if $include.end}
    {tr}to{/tr} "{$include.end}"
    {/if}

  </li>
  {/foreach}
</ul>
