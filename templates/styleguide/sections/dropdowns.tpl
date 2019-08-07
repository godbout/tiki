<div class="dropdowns">
	<h2>Dropdowns</h2>
	<div class="row">
		<div class="col-sm-8 col-md-9">
			<div class="dropdown">
				<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Dropdown
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenu1">
					<a class="dropdown-item" href="javascript:void(0);">Action</a>
					<a class="dropdown-item" href="javascript:void(0);">Another action</a>
					<a class="dropdown-item" href="javascript:void(0);">Something else here</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="javascript:void(0);">Separated link</a>
				</div>
			</div>
		</div>

		<div class="col-sm-4 col-md-3">
			<div class="input">
				<p class="picker" data-selector=".dropdown-menu" data-element="background-color">
					<label for="tc-dropdown-bg-color">Background:</label>
					<input id="tc-dropdown-bg-color" data-selector=".dropdown-menu" data-element="background-color" data-var="@dropdown-bg" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p class="picker" data-selector=".dropdown-menu .dropdown-item" data-element="color">
					<label for="tc-dropdown-text-color">Text color:</label>
					<input id="tc-dropdown-text-color" data-selector=".dropdown-menu .dropdown-item" data-element="color" data-var="@dropdown-link-color" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p>
					<label for="tc-dropdown-border-radius">Border radius:</label>
					<input id="tc-dropdown-border-radius" class="nocolor" data-selector=".dropdown-menu" data-element="border-radius" type="text">
				</p>
			</div>
		</div>
	</div>
</div>
