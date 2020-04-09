<?php

class ProgressBar implements SplObserver
{
	const EXTRACT_TABLE_REGEX = '/CREATE ((FULLTEXT INDEX|INDEX) \w+ ON|(TABLE)) `?(\w+)`?/';

	public function __construct()
	{
		$this->generateModal();
	}

	public function update(SplSubject $installer)
	{
		$queries = $installer->queries;
		$this->updateProgressBar($queries['executed'], $queries['total'] ?: 1);

		$sql = $queries['currentStmt'];
		if (! empty($sql) && preg_match(self::EXTRACT_TABLE_REGEX, $sql, $match)) {
			$label_id = false;
			$table_name = $match[4];

			if (strtoupper($match[3]) === 'TABLE') {
				$label_id = 'table_name';
			} elseif (in_array(strtoupper($match[2]), ['FULLTEXT INDEX', 'INDEX'])) {
				$label_id = 'table_index';
			}

			$label_id && $this->updateLabels($label_id, $table_name);
		}

		//preg_match('/CREATE\sTABLE\s`([a-zA-Z_]+)`.*/'
		//preg_match('/CREATE\sFULLTEXT\s[a-zA-Z\s]*\s([a-z_]*)\(/'
	}


	public function updateProgressBar($current, $total)
	{
		$percent = (int)($current / $total * 100) . "%";

		$scripts = <<<JS
		<script class="progress_bar_script">
			var element = parent.document.getElementById("progress_database_status");
			element.style.width = "{$percent}";
			var progress_status_element = parent.document.getElementById("progress_database_status_percentage");
			progress_status_element.innerHTML = "{$percent}";
		</script>
JS;
		echo $scripts;
		flush();
	}

	public function updateLabels($targetElement, $content)
	{
		$scripts = <<<JS
		<script class="progress_bar_script">
			var element = parent.document.getElementById("{$targetElement}");
			if(element) {
				element.innerHTML = "{$content}";
			}
		</script>
JS;
		echo $scripts;
		flush();
	}

	public function generateModal()
	{
		// Style
		$container_layout_style = ";margin: 0;padding: 0;height: 100%;width: 100%; position: absolute; z-index: 10000;";
		$progressbar_presentation_style = ";display: flex;flex-direction: column;align-items: center;justify-content: center;background: #737373;font-weight: 0.9em;font-family: Raleway, Arial, Helvetica, sans-serif;";
		$progressbar_wrapper_style = "width: 50%;max-width: 400px;background: #fff;padding: 20px;margin: 0; overflow:hidden; border-radius: 0.25rem;";
		$progressbar_h_style = "margin-top: 0; margin-bottom: 0.5rem;font-family: inherit;font-weight: 500;line-height: 1.2; font-size: 2rem;";
		$progressbar_h1_style = ";font-size:2rem;";
		$progressbar_h3_style = ";font-size:1.3rem;margin-bottom:1rem;";
		$progressbar_header = "text-align: center; font-family: Raleway, Arial, Helvetica, sans-serif;";
		// $progressbar_progress = "position: relative;width: 100%;height: 16px;background: rgb(30, 173, 230);";
		// $progressbar_progress_status = "position: absolute;height: 100%;width: 0%;background: #143c64;";

		$progress_wrapper_style = ";display: -webkit-box;display: -ms-flexbox;display: flex;height: 1rem;overflow: hidden;font-size: .75rem;background-color: #e9ecef;border-radius: .25rem;";
		$progress_bar_style_style = ";display: -webkit-box;display: -ms-flexbox;display: flex;-webkit-box-orient: vertical;-webkit-box-direction: normal;-ms-flex-direction: column;flex-direction: column;-webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;color: #fff;text-align: center;background-color: #007bff;transition: width .6s ease;";
		$progress_bar_striped_style = ";background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size: 1rem 1rem;";
		$progress_bar_bg_info_style = ";background-color: #17a2b8 !important;";
		$progress_bar_animated = ";-webkit-animation: progress-bar-stripes 1s linear infinite;animation: progress-bar-stripes 1s linear infinite;";


		$progressbar_footer = "text-align: center;";
		$building_patches_style = "margin: 4px 0px 8px;text-align: left;color: #212529;";
		$progressbar_footer_start = "float: left;font-size: 1rem;font-weight: 400;line-height: 1.5;color: #212529;";
		$progressbar_footer_end = "float: right;font-size: 1rem;font-weight: 400;line-height: 1.5;color: #212529;";
		$progressbar_footer_table_name = "display: inline;";

		$page_content = <<<HTML
		<div style="{$container_layout_style}" id="progressBar">
				<div class="progressbar" style="{$progressbar_presentation_style} {$container_layout_style}">
					<div class="progressbar_wrapper" style="{$progressbar_wrapper_style}">
						<div class="progressbar_header" style="{$progressbar_header}">
							<img src="img/tiki/Tiki_WCG.png" alt="Tiki Logo" style=""/>
							<h1 style="{$progressbar_h_style}{$progressbar_h1_style}">Database Installation</h1>
						</div>
						<div class="progress_body" style="">
							<div class="database" style="margin-bottom: 1rem">
								<h3 style="{$progressbar_h_style}{$progressbar_h3_style}">Table creation status</h3>
								<div class="progress" style="{$progress_wrapper_style}">
									<div class="progress-bar progress-bar-striped bg-info" id="progress_database_status" style="{$progress_bar_style_style}{$progress_bar_striped_style}{$progress_bar_bg_info_style}{$progress_bar_animated}"></div>
								</div>
								<div class="footer" style="{$progressbar_footer}">
									<div class="start" style="{$progressbar_footer_start}">0 %</div>
									<div class="table_name" style="{$progressbar_footer_table_name}"><strong id="table_name" >tiki_database</strong></div>
									<div class="end" id="progress_database_status_percentage" style="{$progressbar_footer_end}">0 %</div>
								</div>
							</div>
							<div class="patches" id="patches" style="">
								<h3 style="{$progressbar_h_style}{$progressbar_h3_style}">Patch creation status</h3>
								<div class="tables_indexing">
								<p style="{$building_patches_style}">Indexing tables <strong><span id="table_index" style="color:#d44950;">...</span></strong></p>
								</div> 
								<div class="building_patches">
									<p style="{$building_patches_style}">Building patches <strong><span id="build_patch" style="color:#d44950;">...</span></strong></p>
								</div>
								<div class="building_scripts">
								<p style="{$building_patches_style}">Building scripts <strong><span id="build_script" style="color:#d44950;">...</span></strong></p>
								</div>   
							</div>
						</div>
					</div>
				</div>
			</div>
			<script class="progress_bar_script">
				parent.document.body.style.margin= 0;
				parent.document.body.style.width = '100vw';
				parent.document.body.style.height = '100vh';
				parent.document.body.style.padding= 0;
				parent.document.body.fontFamily = "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\", \"Noto Color Emoji\";";
			</script>
HTML;

		echo $page_content;
		flush();
	}
}
