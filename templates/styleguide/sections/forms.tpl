<div class="forms">
	<h2>Forms</h2>
	<div class="row">
		<div class="col-sm-8 col-md-9">
			<form class="tc-form" method="post" action="#">
				<fieldset>
					<p class="form-group">
						<label for="tc-username-example">Username</label>
						<input id="tc-username-example" class="nocolor form-control" type="text" value="Username">
					</p>
					<p class="has-error form-group">
						<label for="tc-password-example">Password</label>
						<input id="tc-password-example" class="nocolor form-control" type="password">
						<label class="label label-warning">This field is required</label>
					</p>
					<p class="form-group">
						<input id="tc-remember-example" type="checkbox"> Remember me
					</p>
					<p class="form-group">
						<button class="btn btn-primary">Login</button>
					</p>
					<hr/>

					<p class="has-error form-group">
						<label for="tc-text-example">Text field</label>
						<input id="tc-text-example" class="nocolor form-control" type="text">
						<label class="label label-warning">This field is required</label>
					</p>
					<p class="form-group">
						<label for="tc-textarea-example">Textarea</label>
						<textarea id="tc-textarea-example" class="nocolor form-control" rows="3">This is a textarea field</textarea>
					</p>
					<p class="form-group">
						<label for="tc-select-example">Select</label> <select id="tc-select-example" class="nocolor form-control">
							<option>Option 1</option>
							<option>Option 2</option>
							<option>Option 3</option>
							<option>Option 4</option>
						</select>
					</p>
					<p class="form-group">
						<label for="tc-checkbox-example">Checkbox</label>
						<input id="tc-checkbox-example" type="checkbox">
						This is a checkbox
					</p>
					<p class="form-group">
						<label for="tc-radio-example">Radio</label>
						<input id="tc-radio-example" name="radio" type="radio">
						This is a radio button
					</p>
					<p><input name="radio" type="radio"> This is another radio button</p>
				</fieldset>
			</form>
		</div>

		<div class="col-sm-4 col-md-3">
			<div class="input">
				<p class="picker" data-selector=".form-control" data-element="background-color">
					<label for="tc-field-bg-color">Background:</label>
					<input id="tc-field-bg-color" data-selector=".form-control" data-element="background-color" data-var="@input-bg" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p class="picker" data-selector=".form-control" data-element="border-color">
					<label for="tc-field-border-color">Border:</label>
					<input id="tc-field-border-color" data-selector=".form-control" data-element="border-color" data-var="@input-border" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p class="picker" data-selector=".form-control" data-element="color">
					<label for="tc-field-text-color">Text:</label>
					<input id="tc-field-text-color" data-selector=".form-control" data-element="color" data-var="@input-color" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p>
					<label for="tc-field-padding">Padding:</label>
					<input id="tc-field-padding" class="nocolor" data-selector=".form-control" data-element="padding" type="text">
				</p>
			</div>
		</div>
	</div>
</div>
