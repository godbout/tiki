<div class="navbars">
	<h2>Navbar</h2>
	<div class="row">
		<div class="col-sm-8 col-md-9">
			<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
				<a class="navbar-brand" href="#">Menu</a>
				<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="navbar-nav mr-auto">
						<li class="nav-item active">
							<a href="javascript:void(0);" class="nav-link">Link <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
							<a href="javascript:void(0);" class="nav-link">Link</a>
						</li>
						<li class="nav-item dropdown">
							<a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
								Dropdown
							</a>
							<div class="dropdown-menu" aria-labelledby="navbarDropdown">
								<a class="dropdown-item" href="javascript:void(0);">Action</a>
								<a class="dropdown-item" href="javascript:void(0);">Another action</a>
								<a class="dropdown-item" href="javascript:void(0);">Something else here</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="javascript:void(0);">Separated link</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="javascript:void(0);">One more separated link</a>
							</div>
						</li>
					</ul>
					<form class="form-inline my-2 my-lg-0">
						<input type="text" class="form-control mr-sm-2" placeholder="Search" aria-label="Search">
						<button type="submit" class="btn btn-primary my-2 my-sm-0">Submit</button>
					</form>
<!--
					<ul class="navbar-nav navbar-right mr-auto">
						<li><a href="javascript:void(0);">Link</a></li>
						<li class="dropdown">
							<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
								Dropdown
							</a>
							<ul class="dropdown-menu">
								<li class="dropdown-item"><a href="javascript:void(0);">Action</a></li>
								<li class="dropdown-item"><a href="javascript:void(0);">Another action</a></li>
								<li class="dropdown-item"><a href="javascript:void(0);">Something else here</a></li>
								<li role="separator" class="dropdown-divider"></li>
								<li class="dropdown-item"><a href="javascript:void(0);">Separated link</a></li>
							</ul>
						</li>
					</ul>
-->
				</div>
			</nav>
		</div>

		<div class="col-sm-4 col-md-3">
			<div class="input">
				<p class="picker" data-selector=".bg-dark" data-element="background-color">
					<label for="tc-navbar-bg-color">Background:</label>
					<input id="tc-navbar-bg-color" data-selector=".bg-dark" data-element="background-color" data-var="$navbar-dark-bg" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p class="picker" data-selector=".navbar-dark .navbar-nav .nav-link" data-element="color">
					<label for="tc-navbar-link-color">Text color:</label>
					<input id="tc-navbar-link-color" data-selector=".navbar-dark .navbar-nav .nav-link" data-element="color" data-var="$navbar-dark-link-color" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p class="picker" data-selector=".navbar-dark .navbar-nav .active > .nav-link" data-element="color">
					<label for="tc-navbar-active-link-color">Active menu:</label>
					<input id="tc-navbar-active-link-color" data-selector=".navbar-dark .navbar-nav .active > .nav-link" data-element="color" data-var="$navbar-dark-link-active-bg" type="text">
					<span class="input-group-addon"><i></i></span>
				</p>
				<p>
					<label for="tc-navbar-border-radius">Border radius:</label>
					<input id="tc-navbar-border-radius" class="nocolor" data-selector=".navbar" data-element="border-radius" data-var="$border-radius" type="text">
				</p>
			</div>
		</div>

	</div>
</div>
