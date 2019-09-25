{* This is intended as an example of how to present Elasticsearch facets, known now as aggregations.

Example wiki page "chart" contents:

{CUSTOMSEARCH(wiki="charts tpl")}
  {facet name="tracker_field_testUser"}
  {facet name="deep_categories_under_1"}
  {OUTPUT(template="templates/examples/search/facet_charts.tpl")}
    {chart type="pie" title="Users" colors="orange:yellow:red:purple:grey:blue:green:pink:black" class="col-sm-4" size="300:600"}
    {chart type="bar" title="Countries" colors="#888:#aaa:#ccc:#eee:#888:#aaa:#ccc:#eee:#888:#aaa:#ccc:#eee" class="col-sm-8" size="300:400"}
  {OUTPUT}
{CUSTOMSEARCH}

Example wiki page "chart tpl" contents for the form:

{literal}<div  class="row"><div class="col-sm-4 offset-sm-4"><div class="input-group">
  {input _filter="content" type="text" class="form-control" placeholder="Search..."}
  <div class="input-group-append">
    {input type="submit" value="Go" class="btn btn-primary"}
    {input _filter="content" type="hidden" _field="tracker_status" id="tracker_status"}
    {input _filter="content" type="hidden" _field="tracker_field_eventCategory" id="deep_categories_under_2"}
  </div></div></div></div>
{/literal}

 *}

{if not empty($facets)}
	<pre style="display: none;" class="facets-data">{$facets|var_dump}</pre>
	<pre style="display: none;" class="charts-data">{$chart|var_dump}</pre>

	{if empty($container)}
		{$containerClass = 'row'}
	{else}
		{$containerClass = $container.class}
	{/if}
	{$i = 0}
	<div class="{$containerClass}">
		{foreach $facets as $facet}
			{if count($facet.options) gt 0}
				{if not empty($chart.title) and not empty($chart.type) and not empty($chart.colors)}
					{$chart = [$chart]}{* if there is only one chart then it will not be in an array *}
				{/if}
				{if not isset($chart[$i].class)}{$chart[$i].class = 'col-sm-12'}{/if}
				<div class="{$chart[$i].class|escape}">
					<label class="h3">
						{if not empty($chart[$i].title)}
							{$chart[$i].title|escape}
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

					{if not isset($chart[$i].type)}{$chart[$i].type = 'bar'}{/if}
					{if not isset($chart[$i].id)}{$chart[$i].id = 'chart_'|cat:$i}{/if}
					{if not isset($chart[$i].size)}
						{$chart[$i].size = ['','']}
					{else}
						{$chart[$i].size = ':'|explode:$chart[$i].size}
					{/if}

					{if not isset($chart[$i].colors)}
						{$col = []}
					{else}
						{$col = ':'|explode:$chart[$i].colors}
					{/if}

					{if not empty($chart[$i].hcolors)}
						{$hcol = ':'|explode:$chart[$i].hcolors}
					{else}
						{$hcol = $col}
					{/if}

					{$datasets = [
						'data'                  => $values,
						'backgroundColor'       => $col
					]}
					{if $hcol}{$datasets.hoverBackgroundColor = $hcol}{/if}
					{if $chart[$i].title}{$datasets.label = $chart[$i].title|escape}{/if}

					{$data = ['data' => ['labels' => $labels,'datasets' => [$datasets]]]}

					{$options = ['responsive' => true, 'maintainAspectRatio' => false]}{* some handy defaults (not working as expected) *}
					{$data.options = $options}

					<pre style="display: none;" class="data-options">{$data|var_dump}</pre>

					{wikiplugin _name='chartjs' type=$chart[$i].type id=$chart[$i].id width=$chart[$i].size[0] height=$chart[$i].size[1] debug=1}
						{$data|json_encode}
					{/wikiplugin}
				</div>
			{/if}
			{$i = $i + 1}
		{/foreach}
	</div>
{/if}
