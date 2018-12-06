{* This is intended as an example of how to present Elasticsearch facets, known now as aggregations.

Example wiki page "chart" contents:

 {CUSTOMSEARCH(wiki="charts tpl")}
   {facet name="tracker_status"}
   {facet name="deep_categories_under_2"}
   {OUTPUT(template="templates/examples/search/facet_charts.tpl")}
     {types 0="pie" 1="bar"}
     {titles 0="" 1="Product Types"}
     {sizes 0="400:400" 1="400:400"}
     {colors 0="green:yellow:grey" 1="#444:#666:#888:#aaa:#ccc:#eee"}
     {classes 0="float-left mr-2"}
   {OUTPUT}
 {CUSTOMSEARCH}

Example wiki page "chart tpl" contents for the form:

{literal}
  Any text: {input _filter="content" type="text"}
  {input type="Submit" value="Search"}
  {input _filter="content" type="hidden" _field="tracker_status" id="tracker_status"}
  {input _filter="content" type="hidden" _field="tracker_field_eventCategory" id="deep_categories_under_2"}
{/literal}

 *}

{if not empty($facets)}
	<pre style="display: none;">{$facets|var_dump}</pre>
	{if empty($types)}
		{$types = ['pie']}
	{/if}
	{if empty($titles)}
		{$titles = []}
	{/if}
	{if empty($colors)}
		{$colors = ['']}
	{/if}
	{if empty($hcolors)}
		{$hcolors = ['']}
	{/if}
	{if empty($classes)}
		{$classes = ['']}
	{/if}
	{if empty($ids)}
		{$ids = ['']}
	{/if}
	{if empty($sizes)}
		{$sizes = ['','']}
	{else}
		{$s = []}
		{foreach $sizes as $size}
			{$s[] = ':'|explode:$size}
		{/foreach}
		{$sizes = $s}
	{/if}
	{$i = 0}
	{if empty($container)}
		{$containerClass = ''}
	{else}
		{$containerClass = $container.class}
	{/if}
	<div class="{$containerClass}">
		{foreach $facets as $facet}
			{if count($facet.options) gt 0}
				{if not isset($classes[$i])}{$classes[$i] = $classes[0]}{/if}
				<div class="{$classes[$i]|escape}">
					<label class="h3">
						{if not empty($titles[$i])}
							{$titles[$i]|escape}
						{else}
							{$facet.label|replace:' (Tree)':''|tr_if|escape}
						{/if}
					</label>
					{$values = []}
					{$labels = []}
					{foreach from=$facet.options key=value item=label}
						{if strpos($label, 'trackeritem:0 ') !== false}
							{continue}
						{/if}
						{if preg_match('/(.*?)\s+\((\d+)\)/', $label|escape, $matches)}
							{$labels[] = $matches[1]}
							{$values[] = $matches[2]}
						{/if}
					{/foreach}

					{if not isset($types[$i])}{$types[$i] = $types[0]}{/if}
					{if not isset($ids[$i])}{$ids[$i] = $ids[0]}{/if}
					{if not isset($sizes[$i])}{$sizes[$i] = $sizes[0]}{/if}

					{if not isset($colors[$i])}
						{$col = ':'|explode:$colors[0]}
					{else}
						{$col = ':'|explode:$colors[$i]}
					{/if}

					{if empty($hcolors[$i]) and not empty($hcolors[0])}
						{$hcol = ':'|explode:$hcolors[0]}
					{elseif not empty($hcolors[$i])}
						{$hcol = ':'|explode:$hcolors[$i]}
					{else}
						{$hcol = $col}
					{/if}

					{$datasets = [
						'data'                  => $values,
						'backgroundColor'       => $col
					]}
					{if $hcol}{$datasets.hoverBackgroundColor = $hcol}{/if}
					{if $titles[$i]}{$datasets.label = $titles[$i]|escape}{/if}

					{$data = ['labels' => $labels,'datasets' => [$datasets]]}

					<pre style="display: none;">{$data|var_dump}</pre>

					{wikiplugin _name='chartjs' type=$types[$i] id=$ids[$i] width=$sizes[$i][0] height=$sizes[$i][1] debug=1}
						{$data|json_encode}
					{/wikiplugin}
				</div>
			{/if}
			{$i = $i + 1}
		{/foreach}
	</div>
{/if}
