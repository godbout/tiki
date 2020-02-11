var casper = require('casper').create();

var filename = casper.cli.options.filename;
if (! filename) {
	casper.echo('No filename option passed', 'ERROR').exit();
}

var url = casper.cli.options.htmlfile;
if (! url) {
	casper.echo('No exporthtmlfile option passed', 'ERROR').exit();
}

casper.start(url, function(){
	this.page.injectJs('temp/do_' + filename + '.js');
});

casper.then(function() {
	var graphStyle = this.evaluate(function() {
		return document.querySelector('#graph svg').style;
	});

	width = parseInt(graphStyle.minWidth.replace('px', ''));
	height = parseInt(graphStyle.minHeight.replace('px', ''));

	var clipRect = {
		top: 0,
		left: 0,
		width: width,
		height: height
	};
	var imgOptions = {
		format: 'png',
		quality: 75
	}
	this.capture('temp/diagram_' + filename + '.png', clipRect, imgOptions);
});

casper.run();
