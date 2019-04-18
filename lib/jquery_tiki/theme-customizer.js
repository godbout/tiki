//
//  Theme Customizer v2.0.0 - from sg-app.js and sg-web.js
//

var changed = Array();
var TC = function() {
	this.lessVars = {};
	this.nestedLoopText = '';
	this.nestedIdentation = '';
	this.data = '';
	this.cssValidator = true;
	this.cssLineError = 0;
	this.cssCustomText = '';
};

TC.prototype = {
	constructor: TC,
	// check the attributes that contains what we are looking for
	checkAttribs: function(node, key) {
		if ($.isArray(node.attributes)) {
			for (var i = 0; i < node.attributes.length; i++) { this.checkForDupes(node.attributes[i], key); }
		} else {
			for (i in node.attributes) { this.checkForDupes(node.attributes[i], key); }
		}
		if (node.nested) this.checkAttribs(node.nested, key);
	},

	// check for duplicates
	checkForDupes: function(node, key) {
		// colors
		var reg = node.match(/(#[A-Fa-f0-9]+)/);
		if (reg && node.indexOf('\9') === -1) {
			if (! this.lessVars.colors[reg[0]]) this.lessVars.colors[reg[0]] = [];
			this.lessVars.colors[reg[0]].push(key);
		}

		// fonts
		reg = node.match(/font-family:\s(.+)/);
		if (reg && node.indexOf('inherit') === -1) {
			if (! this.lessVars.fonts[reg[1]]) this.lessVars.fonts[reg[1]] = [];
			this.lessVars.fonts[reg[1]].push(key);
		}

		// size
		reg = node.match(/.*\s(-?[0-9]+px)$/);
		if (reg) {
			if (! this.lessVars.size[' ' + reg[1]]) this.lessVars.size[' ' + reg[1]] = [];
			this.lessVars.size[' ' + reg[1]].push(key);
		}
	},

	// check the results after compiling duplicates
	checkResults: function(node, json, name, min) {
		var var_count = 0;
		var var_name = '', j;
		for (var k in json) {
			if (json.hasOwnProperty(k) && json[k].length >= min) {
				var_count++;
				var_name = name + var_count;
				this.data += var_name + ': ' + k + ';\n';
				for (var i = 0; i < json[k].length; i++) {
					if ($.isArray(node[json[k][i]].attributes)) {
						for (j = 0; j < node[json[k][i]].attributes.length; j++) {
							node[json[k][i]].attributes[j] = node[json[k][i]].attributes[j].replace(k, var_name);
						}
					} else {
						for (j in node[json[k][i]].attributes) {
							node[json[k][i]].attributes[j] = node[json[k][i]].attributes[j].replace(k, var_name);
						}
					}
				}
			}
		}
	},

	// main checking function that uses the 3 above
	checkForVars: function(node, log) {
		if (typeof log === "undefined") log = false;

		this.lessVars.colors = {};
		this.lessVars.fonts = {};
		this.lessVars.size = {};
		this.lessVars.named = {};

		for (var k in node) {
			if ($.inArray(node[k].selector,changed) != -1) {
				if (node.hasOwnProperty(k) && node[k].selector && node[k].attributes) {
					if (node[k].var) {
						// names vars take first attribute value
						this.lessVars.named[node[k].var] = node[k].attributes[Object.keys(node[k].attributes)[0]];
						this.data += node[k].var + ': ' + node[k].attributes[Object.keys(node[k].attributes)[0]] + ';\n';
					} else {
						this.checkAttribs(node[k], k);
					}
				}
			}
		}

		this.checkResults(node, this.lessVars.colors, '@color', 5);
		this.checkResults(node, this.lessVars.fonts, '@font', 3);
		this.checkResults(node, this.lessVars.size, '@size', 10);
		if (log) console.log(JSON.stringify(this.lessVars, null, 2));
	},

	// loop through the nested nodes
	nestLoop: function(node, tab) {
		if (typeof tab === "undefined") tab = true;

		for (var k in node.nested) {
			if (tab) this.nestedIdentation += '  ';
			if (node.nested.hasOwnProperty(k)) {
				this.nestedLoopText += this.nestedIdentation + '  ' + node.nested[k].selector + ' {\n';
				if ($.isArray(node.nested[k].attributes)) {
					for (var i = 0; i < node.nested[k].attributes.length; i++) {
						this.nestedLoopText += this.nestedIdentation + '    ' + node.nested[k].attributes[i] + ';\n';
					}
				} else {
					for (i in node.nested[k].attributes) {
						this.nestedLoopText += this.nestedIdentation + '    ' + i + ': ' + node.nested[k].attributes[i] + ';\n';
					}
				}
				if (node.nested[k].nested) this.nestLoop(node.nested[k]);
				this.nestedLoopText += this.nestedIdentation + '  }\n';
				this.nestedIdentation = this.nestedIdentation.slice(0, -2);
			}
		}
	},

	// looking for duplicate selectors in nested nodes
	DSnestLoop: function(node, source, undef) {
		if (typeof undef === "undefined") undef = true;

		var match = false;
		for (var k in node) {
			if (node.hasOwnProperty(k)) {
				if (undef) {
					for (var key in source) {
						match = false;
						if (source.hasOwnProperty(key) && node[k].selector !== source[key].selector) {
							match = true;
						} else {
							if (node[k].nested) {
								source[key].nested ? this.DSnestLoop(node[k].nested, source[key].nested) : this.DSnestLoop(node[k].nested, source[key], false);
							}
						}
					}
				} else {
					match = true;
				}
				if (match) {
					if (!undef) {
						source.nested = {};
						source.nested = node;
					} else {
						$.extend(source, node);
					}
					delete(node);
				}
			}
		}
	},

	// pass 1: separate multiple selectors
	separateMultipleSelectors: function(node, log) {
		if (typeof log === "undefined") log = false;

		for (var k in node) {
			if (node.hasOwnProperty(k) && node[k].selector) {
				if (node[k].selector.indexOf(',') === -1 && node[k].selector.indexOf('@') === -1) {
					var selectors = [];
					var char = node[k].selector[0];
					var sel_count = 0;
					var wildcard = false;

					for (var i = 1; i < node[k].selector.length; i++) {
						switch(node[k].selector[i]) {
							case ' ':
								if (! wildcard) {
									selectors[sel_count++] = char;
									char = '';
								} else {
									wildcard = false;
									char += ' ';
								}
								break;
							case '(':
							case '>':
							case '+':
							case '~':
								wildcard = true;
							case '.':
							case '#':
							case ':':
								if (! wildcard && node[k].selector[i - 1] !== ' ') {
									if (node[k].selector[i] !== ':' || node[k].selector[i - 1] !== ':') {
										selectors[sel_count++] = char;
										char = '&';
									}
								}
							default:
								char += node[k].selector[i];
						}
					}

					selectors[sel_count++] = char;
					var less = {};
					if (sel_count > 1) {
						node[k].selector = selectors[0];
						for (i = sel_count - 1; i > 0; i--) {
							less[k + '-' + i] = {};
							less[k + '-' + i].selector = selectors[i];
							less[k + '-' + i].attributes = [];
							if (i + 1 === sel_count) {
								less[k + '-' + i].attributes = node[k].attributes;
							} else if (i + 1 < sel_count) {
								less[k + '-' + i].nested = {};
								less[k + '-' + i].nested[k + '-' + (i + 1)] = less[k + '-' + (i + 1)];
								delete less[k + '-' + (i + 1)];
							}
						}
						node[k].nested = less;
						node[k].attributes = [];
					}
					if (log) console.log(JSON.stringify(node[k], null, 2));
				}
			}
		}
	},

	// pass 2: combine duplicate selectors
	combineDuplicateSelectors: function(node, log) {
		if (typeof log === "undefined") log = false;

		for (var k in node) {
			if (node.hasOwnProperty(k) && node[k].selector && node[k].nested) {
				var down = 1;
				while (node[k - down] === undefined || node[k].selector === node[k - down].selector) {
					if (node[k - down] !== undefined) {
						if (! node[k - down].nested) {
							node[k - down].nested = node[k].nested;
						} else {
							for (var key in node[k].nested) {
								if (node[k].nested.hasOwnProperty(key)) {
									for (var kkey in node[k - down].nested) {
										if (node[k - down].nested.hasOwnProperty(kkey)) {
											var match = false;
											if (node[k].nested[key].selector !== node[k - down].nested[kkey].selector) {
												match = true;
											} else {
												if (!node[k - down].nested[kkey].nested) {
													if (!node[k].nested[key].nested) {
														node[k - down].nested[kkey].attributes = node[k - down].nested[kkey].attributes.concat(node[k].nested[key].attributes);
													} else {
														node[k - down].nested[kkey].nested = node[k].nested[key].nested;
													}
												} else {
													this.DSnestLoop(node[k].nested, node[k - down].nested);
												}
												break;
											}
										}
									}
									if (match) {
										node[k - down].nested[key] = {};
										node[k - down].nested[key] = node[k].nested[key];
									}
								}
							}
						}
						delete node[k];
						break;
					}
					down++;
				}
			}
			if (log) console.log(JSON.stringify(node[k], null, 2));
		}
	},

	// pass 3: add it to the file
	constructFile: function(node, log) {
		if (typeof log === "undefined") log = false;

		for (var k in node) {
			if (node.hasOwnProperty(k)) {
				if ($.inArray(node[k].selector, changed) != -1) {
					if (node[k].selector) {
						this.data += node[k].selector + ' {\n';
						if (node[k].attributes) {
							if ($.isArray(node[k].attributes)) {
								for (var i = 0; i < node[k].attributes.length; i++) {
									this.data += '  ' + node[k].attributes[i] + ';\n';
								}
							} else {
								for (i in node[k].attributes) {
									if (node[k].attributes.hasOwnProperty(i)) {
										this.data += '  ' + i + ': ' + node[k].attributes[i] + ';\n';
									}
								}
							}
						}
						this.nestedLoopText = '';
						this.nestedIdentation = '';
						if (node[k].nested) {
							this.nestLoop(node[k], false);
							this.data += this.nestedLoopText;
						}
					}
					if (node[k].comment) {
						if ($.isArray(node[k].comment)) {
							for (i = 0; i < node[k].comment.length; i++) {
								if (node[k].selector) this.data += '  ';
								this.data += node[k].comment[i] + '\n';
							}
						} else {
							if (node[k].selector) this.data += '  ';
							this.data += node[k].comment + '\n';
						}

						if (node[k].attributes && !node[k].selector) this.data += node[k].attributes + ';\n';
					}
					if (node[k].selector) this.data += '}\n';
					if (log) console.log(JSON.stringify(node[k], null, 2));
				}
			}
		}
	},

	// generate less file from all json sources and save it locally
	generateLessFile: function(sources) {
		// add generated variables based on # of times same color, font, size appears
		if (sources.constructor !== Array) sources = [sources];
		this.data = "/*\n";
		this.data += "==========================================================================\n";
		this.data += "Theme Customizer Generated LESS Variables\n";
		this.data += "==========================================================================\n";
		this.data += "*/\n";

		var node = $.extend(true, {}, sources[0]);
		this.checkForVars(node);

		// next up the CSS from all sources
		for (var i = 0; i < sources.length; i++) {
			this.data += "/*\n";
			this.data += "==========================================================================\n";
			this.data += "Theme Customizer Generated LESS\n";
			this.data += "==========================================================================\n";
			this.data += "*/\n";

			if (i !== 0) node = $.extend(true, {}, sources[i]);
			this.separateMultipleSelectors(node);
			this.combineDuplicateSelectors(node);
			this.constructFile(node);
		}

		// and finally lets save and download file
		var properties = {type: 'plain/text'};
		try {
			file = new File([this.data], 'themecustomizer.less', properties);
		} catch (e) {
			file = new Blob([this.data], properties);
		}
		url = URL.createObjectURL(file);
		document.getElementById('generate-var').href = url;
	},

	generateLessVariables: function(sources) {
		// add generated variables based on # of times same color, font, size appears
		if (sources.constructor !== Array) sources = [sources];
		this.data = "/*\n";
		this.data += "==========================================================================\n";
		this.data += "Theme Customizer Generated LESS Variables\n";
		this.data += "==========================================================================\n";
		this.data += "*/\n";

		var node = $.extend(true, {}, sources[0]);
		this.checkForVars(node);

		return this.data;
	},

	// generate custom css file from all json sources
	generateCSS: function(sources) {
		if (sources.constructor !== Array) sources = [sources];
		this.data = '';

		for (var i = 0; i < sources.length; i++) {
			this.data += "/*\n";
			this.data += "=================================================\n";
			this.data += "Theme Customizer Generated CSS\n";
			this.data += "=================================================\n";
			this.data += "*/\n";

			var node = $.extend(true, {}, sources[i]);
			this.constructFile(node);
		}

		return this.data;
	},

	// css file to json conversion, easier to manipulate json data compare to a css 'text' file
	toJSON: function(data, base) {
		if (typeof base === "undefined") base = false;

		var comment = /(\/\*[\s\S]*?\*\/)/g;
		var selector = /([^\s\;\{\}][^\;\{\}]+)\{|(\})/g;
		var attributes = /([^\s\;\{\}][^\;\{\}]+\;)/g;

		var json = {};
		var obj = {};
		var count = 0;
		var sel = false;
		var match = null;
		var nested_json = {};
		var nested_count = 0;
		var nested = false;

		var all = /(\/\*[\s\S]*?\*\/)|([^\s\;\{\}][^\;\{\}]+)\{|(\})|([^\s\;\{\}][^\;\{\}]+)/g;
		if (base) all = /(\/\*[\s\S]*?\*\/)|([^\s\;\{\}][^\;\{\}]+)\{|(\})|([^\s\;\{\}][^\;\{\}]+\;)/g;
	//	var all = /(\/\*[\s\S]*?\*\/)|([^\s\;\{\}][^\;\{\}]*(?=\{))|(\})|([^\;\{\}]+\;(?!\s*\*\/))/gmi;

		while ((match = all.exec(data)) != null) {
			if (typeof match[1] !== "undefined") {
				if (sel) {
					if (obj['comment'] === undefined) {
						obj['comment'] = [match[1]];
					} else {
						obj['comment'].push(match[1]);
					}
				} else {
					obj = {};
					obj['comment'] = match[1];
					json[count++] = obj;
				}
			}

			else if (typeof match[2] !== "undefined") {
				if (obj['selector'] && (obj['selector'].indexOf('@media') > -1 || obj['selector'].indexOf('@keyframes') > -1 || obj['selector'].indexOf('@-webkit-keyframes') > -1)) {
					nested_json = {};
					nested_count = 0;
					nested_json['selector'] = obj['selector'];
					nested = true;
				}
				obj = {};
				obj['selector'] = match[2].replace('}', ' ').trim();
				base ? obj['attributes'] = [] : obj['attributes'] = {};
				sel = true;
			}

			else if (typeof match[3] !== "undefined") {
				if (nested) {
					if ((nested_count > 0) && (nested_json.nested[(count) + '-' + (nested_count - 1)] === obj)) {
						json[count++] = nested_json;
						nested = false;
					} else {
						if (nested_count === 0) nested_json['nested'] = {};
						nested_json.nested[(count) + '-' + (nested_count++)] = obj;
					}
				} else {
					json[count++] = obj;
				}
				sel = false;
			}

			else if (typeof match[4] !== "undefined") {
				if (base) {
					attr = match[4].replace(';', '');
					if (obj['attributes'] === undefined) {
						obj['attributes'] = attr;
					} else if (match[4].indexOf('base64') > -1) {
						var pop = obj['attributes'].pop();
						obj['attributes'].push(pop + ';' + attr);
					} else {
						obj['attributes'].push(attr);
					}
				} else {
					match[4] = match[4].trim();
					if (match[4].slice(-1) === ';') match[4] = match[4].slice(0, -1);
					attr = match[4].split(':');
					if (typeof attr[1] !== "undefined") {
						obj['attributes'][attr[0]] = attr[1].trim();
					} else {
						obj['attributes'][attr[0]] = '';
					}
				}
			}
		}

		return json;
	},

	// update css from json values, more stuff to do here
	toCSS: function(node) {
		var text = '';
		var lines = 1;
		this.cssValidator = true;

		for (var key in node) {
			if (node.hasOwnProperty(key)) {
				text += node[key].selector + ' {\n';
				lines++;
				for (var k in node[key].nested) {
					if (node[key].nested.hasOwnProperty(k)) {
						text += '  ' + node[key].nested[k].selector + ' {\n';
						lines++;
						for (var i in node[key].nested[k].attributes) {
							if (node[key].nested[k].attributes.hasOwnProperty(i)) {
								var old = $(node[key].nested[k].selector).css(k);
								$(node[key].nested[k].selector).css(i, node[key].nested[k].attributes[i]);
								var newer = $(node[key].nested[k].selector).css(k);
								if (old === newer) {
									if (newer !== node[key].nested[k].attributes[k]) {
										this.cssValidator = false;
										this.cssLineError = lines;
									}
								}
								text += '    ' + i + ': ' + node[key].nested[k].attributes[i] + ';\n';
								lines++;
//								console.log('nested selector: ' + node[key].nested[k].selector);
//								console.log('nested attribute: ' + i);
//								console.log('nested value: ' + node[key].nested[k].attributes[i]);
							}
						}
						text += '  }\n';
						lines++;
					}
				}
				for (k in node[key].attributes) {
					if (node[key].attributes.hasOwnProperty(k)) {
						if (node[key].selector.indexOf('@') === -1) {
							old = $(node[key].selector).css(k);
							$(node[key].selector).css(k, node[key].attributes[k]);
							newer = $(node[key].selector).css(k);
							if (old === newer) {
								if (newer !== node[key].attributes[k]) {
									this.cssValidator = false;
									this.cssLineError = lines;
//									console.log(old);
//									console.log(newer);
//									console.log(node[key].attributes[k]);
								}
							}
						}
						text += '  ' + k + ': ' + node[key].attributes[k] + ';\n';
						lines++;
//						console.log('selector: ' + node[key].selector);
//						console.log('attribute: ' + k);
//						console.log('value: ' + node[key].attributes[k]);
					}
				}
				text += '}\n\n';
				lines = lines + 2;
			}
		}
	}
};

/*
 * from sg-web.js
 */

//
//  Theme Customizer - Web page stuff
//


var tc = new TC();
var baseJSON = {};
var inputJSON = {};



// change text color for input fields depending on background color
function getContrast(color) {
	if (color === 'transparent') return 'black';

	color = color.replace('#', '');
	if (color.length === 3) color = color.charAt(0) + color.charAt(0) + color.charAt(1) + color.charAt(1) + color.charAt(2) + color.charAt(2);

	var r = parseInt(color.substr(0, 2), 16);
	var g = parseInt(color.substr(2, 2), 16);
	var b = parseInt(color.substr(4, 2), 16);
	var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;

	return (yiq >= 128) ? 'black' : 'white';
}



// return a jquery selector
function getSelector(el) {
	return el.attr('data-selector') ? el.attr('data-selector') : "";
}



// takes a 3 char color value and returns a 6 char color value
function longColorValue(color){
	color = color.replace('#', '');
	if (color.length === 3) {
		color = color.charAt(0) + color.charAt(0) + color.charAt(1) + color.charAt(1) + color.charAt(2) + color.charAt(2);
	}
	return '#' + color;
}



// convert rgb value to hex
function toHex(rgb) {
	if (rgb === 'transparent') return rgb;
	var regex = /([0-9]+)/g;
	var matches = rgb.match(regex);

	var hex = matches.map(function(x) {
		return parseInt(x, 10);
	});

	if (hex.length > 3) {
		if ((hex[0] === 0) && (hex[1] === 0) && (hex[2] === 0) && (hex[3] === 0)) return 'transparent';
	}

	return "#" + ((1 << 24) + (hex[0] << 16) + (hex[1] << 8) + hex[2]).toString(16).slice(1);
}



// equivalent of darken/lighten in css which is unusable in jquery
function lightenDarkenColor(color, amount) {
	var isColor = false;

	if (color[0] === "#") {
		color = color.slice(1);
		isColor = true;
		if (getContrast(color) === 'black') { amount = -Math.abs(amount); }
	}

	var num = parseInt(color, 16);

	var r = (num >> 16) + amount;
	if (r > 255) r = 255;
	else if (r < 0) r = 0;

	var b = ((num >> 8) & 0x00FF) + amount;
	if (b > 255) b = 255;
	else if (b < 0) b = 0;

	var g = (num & 0x0000FF) + amount;
	if (g > 255) g = 255;
	else if (g < 0) g = 0;

	return (isColor ? "#" : "") + String("000000" + (g | (b << 8) | (r << 16)).toString(16)).slice(-6);
}



// stuff repeated in next 2 functions
function checkInputValues(el, selector) {
	var bfa = el.attr('data-element'); // browser friendly attribute
	if (bfa === 'border-color') bfa = 'border-top-color';

	// get all the relevant example elements for this control
	var $selected = $(el).parents(".row:first").find("> div:first").find(selector);

	if ($selected.length === 0) $selected = $(selector);

	if ($selected.css(bfa).indexOf('rgb(') > -1) {
		css = $selected.css(bfa);
		part1 = css.slice(0, css.indexOf('rgb('));
		part2 = css.slice(css.indexOf('rgb('));
		el.val( part1 + toHex(part2) );
	} else if (bfa === 'padding') {
		if ($selected.css('padding-top') === $selected.css('padding-left') &&
				$selected.css('padding-top') === $selected.css('padding-bottom') &&
				$selected.css('padding-top') === $selected.css('padding-right')) {
			el.val( $selected.css('padding-top') );
		} else {
			el.val( $selected.css('padding-top') + ' ' + $selected.css('padding-right') + ' ' + $selected.css('padding-bottom') + ' ' + $selected.css('padding-left') );
		}
	} else if (bfa === 'border-radius') {
		if ($selected.css('border-top-left-radius') === $selected.css('border-top-right-radius') &&
				$selected.css('border-top-left-radius') === $selected.css('border-bottom-right-radius') &&
				$selected.css('border-top-left-radius') === $selected.css('border-bottom-left-radius')) {
			el.val( $selected.css('border-top-left-radius') );
		} else {
			el.val( $selected.css('border-top-left-radius') + ' ' + $selected.css('border-top-right-radius') + ' ' + $selected.css('border-bottom-right-radius') + ' ' + $selected.css('border-bottom-left-radius') );
		}
	} else if (bfa === 'border') {
		el.val( $selected.css('border-top-width') + ' ' + $selected.css('border-top-style') + ' ' + toHex($selected.css('border-top-color')) );
	} else {
		el.val( $selected.css(bfa) );
	}

	if ( selector === '.customizer .icons .sample span' ) {
		var width = 0;
		$selected.css('width', '');
		$selected.each(function() {
			if ($(this).width() > width) width = $(this).width();
		});
		$selected.each(function() {
			$(this).width(width);
			$(this).height(width);
		});
	}
}



// change default values of all input fields on pageload
function defaultInputValues(pageload) {
	if (typeof pageload === "undefined") pageload = true;
	var count = 0;
	$('.input input').each(function() {
		var obj = {};
		var selector = getSelector($(this));
		var bfa = $(this).attr('data-element'); // browser friendly attribute
		if (bfa === 'border-color') bfa = 'border-top-color';

		if (! $(this).hasClass('nocolor')) {
			$(this).val( toHex($(selector).css(bfa)) );
			if ($(this).val() === 'transparent') {
				$(this).css('background-color', 'rgba(0, 0, 0, 0)');
			} else {
				$(this).css('background-color', '#' + $(this).val());
			}
			$(this).css('color', getContrast($(this).val()));
			if (!pageload) $(this).closest('.picker').colorpicker('setValue', $(this).val());
		} else {
			checkInputValues($(this), selector);
			if ( $(this).hasClass('check') ) {
				if ($(this).val() === $(this).attr('data-check')) $(this).prop('checked', true);
			}
		}

		obj['selector'] = selector;
		obj['attributes'] = {};
		obj['attributes'][$(this).attr("data-element")] = $(this).val();
		obj['var'] = $(this).data("var");

		sel = false;
		for (var k in inputJSON) {
			if (inputJSON.hasOwnProperty(k) && inputJSON[k].selector === obj.selector) {
				sel = true;
				for (var key in obj.attributes) {
					if (obj.attributes.hasOwnProperty(key)) {
						inputJSON[k].attributes[key] = obj.attributes[key];
					}
				}
				inputJSON[k].var = obj.var;
				break;
			}
		}
		if (!sel) inputJSON[count++] = obj;
	});
}



// update value of an input field (and json equivalent)
function updateInputValue(el) {
	var selector = getSelector(el);
	var bfa = el.attr('data-element'); // browser friendly attribute
	if (bfa === 'border-color') bfa = 'border-top-color';

	if (! el.hasClass('nocolor')) {
		el.val( toHex($(selector).css(bfa)) );
		el.css('background-color', '#' + el.val());
		el.css('color', getContrast(el.val()));
	} else {
		checkInputValues(el, selector);
	}

	for (var k in inputJSON) {
		if (inputJSON[k].selector === selector) {
			inputJSON[k].attributes[el.attr("data-element")] = el.val();
			break;
		}
	}
	if ($.inArray(selector, changed) == -1) changed.push(selector);
	saveCustomizerCSS();
}



// save custom CSS to localStorage
function saveCustomizerCSS() {
	if ( $('.keep-changes').prop('checked') ) {
		localStorage.setItem('customizerData', JSON.stringify(inputJSON));
		localStorage.setItem('changedData', JSON.stringify(changed));
	}
}



// load custom CSS from localStorage
function loadCustomizerCSS() {
	inputJSON = JSON.parse(localStorage.getItem('customizerData'));
	changed = JSON.parse(localStorage.getItem('changedData'));
}



// delete stored data
function deleteData() {
	localStorage.removeItem('customizerData');
	localStorage.removeItem('changedData');
}



// stuff to fix for TC to work with all themes / browsers
function themeFix() {
	var $body = $('body');
	$('.header_outer, footer').css('background', $body.css('background'));
	$('.header_outer, footer').css('background-color', $body.css('background-color'));
	$('.customizer .tables .table-bordered .tb').css('border', '1px solid ' + $('.table-bordered > tbody > tr > td').css('border-top-color'));
	$('.customizer h1, .customizer h2').css('border-bottom-color', $('h1').css('color'));
	$('.customizer .btn-primary').css('border-color', $('.customizer .btn-primary').css('border-bottom-color'));

	if ($('table').css('background-color') === 'rgba(0, 0, 0, 0)') {
		if ($('#middle.container').css('background-color') !== 'rgba(0, 0, 0, 0)') {
			$('tbody > tr:nth-of-type(even), .table-bordered tr').css('background-color', $('#middle.container').css('background-color'));
		} else {
			$('tbody > tr:nth-of-type(even), .table-bordered tr').css('background-color', $body.css('background-color'));
		}
	} else {
		$('tbody > tr:nth-of-type(even), .table-bordered tr').css('background-color', $('table').css('background-color'));
	}

	if ($('.customizer .navbar-dark .navbar-nav .nav-link').css('background-color') === 'rgba(0, 0, 0, 0)') {
		$('.customizer .navbar-dark .navbar-nav .nav-link').css('background-color', $('.customizer .navbar-dark').css('background-color'));
	}

	var border = ['btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-light', 'btn-dark', 'alert-primary', 'alert-secondary', 'alert-success', 'alert-info', 'alert-warning', 'alert-danger'];
	for (var i = 0; i < border.length; i++) {
		var sel = $('.customizer .' + border[i]);
		if (sel.css('border-top-color').indexOf('rgba') > -1) sel.css('border-color', sel.css('background-color'));
	}

	var font = $body.css('font-family').split(',');
	for (i = 0; i < 3; i++) {
		if (i >= font.length) {
			$('.fonts .font' + (i + 1)).css('font-family', 'Arial');
			$('.fonts .ff' + (i + 1)).append('Arial');
		} else {
			$('.fonts .font' + (i + 1)).css('font-family', font[i]);
			$('.fonts .ff' + (i + 1)).append(font[i]);
		}
	}
}



(function($) {
	// parse original css to json
	var base_css = '';
	var customizer_css = '';

	$.when(
		$.get('./themes/default/css/default.css', function(data) {
			base_css = data;
		}),

		$.get('./themes/base_files/css/theme-customizer.css', function(data) {
			customizer_css = data;
		})
	).then(function() {
		baseJSON = tc.toJSON(base_css + customizer_css, true);
//		console.log(JSON.stringify(baseJSON, null, 2));
	});

	var $themeCustomizer = $('.customizer');
	if ($themeCustomizer.length) themeFix();
	$('.customizer .tc-form').submit(false);


	// theme customizer's footer autotoc
	$themeCustomizer.find('h2').each(function () {
		var text = $(this).text();
		$(this).attr('id', text);
		$('.tc-footer .dropdown-menu').append('<a href="#' + text + '" class="dropdown-item">' + $(this).text() + '</a></li>');
	});


	// set some values if stored data exist
	if (localStorage.getItem('customizerData') !== null) {
		$('.keep-changes').prop('checked', true);
		loadCustomizerCSS();
		tc.toCSS(inputJSON);
		$('textarea[name=header_custom_css]').val(tc.generateCSS([inputJSON]));
	}
	defaultInputValues();


	// update css when out of focus
	$('.input input').on('focusout', function() {
		var check = $(this).val();
		if (!$(this).hasClass('nocolor')) check = longColorValue(check);
		$(getSelector($(this))).css($(this).attr('data-element'), $(this).val());
		updateInputValue($(this));
		check !== $(this).val() ? $(this).parent().addClass('error') : $(this).parent().removeClass('error');
	});


	// update css on change for checkbox
	$('.input .check').on('change', function() {
		$(this).prop('checked') ? $(this).val($(this).attr('data-check')) : $(this).val($(this).attr('data-default'));
		$(getSelector($(this))).css($(this).attr('data-element'), $(this).val());
		updateInputValue($(this));
	});


	// generate custom css when clicked
	$('.generate-custom-css').click(function() {
		$('textarea[name=header_custom_css]').val(tc.generateCSS([inputJSON]));
		$('html, body').animate({
			scrollTop: $('textarea[name=header_custom_css]').offset().top
		}, 200);
		return false;
	});


	// TODO generate SCSS variables when clicked

	// apply lighter color on hover for links
	$('.customizer p > a').hover(
		function() {
			$(this).css('color', lightenDarkenColor($('#tc-link-color').val(), 50));
		}, function() {
			$(this).css('color', $('#tc-link-color').val());
		}
	);


	// apply lighter color on hover for buttons
	$('.customizer .btn, .customizer .icons span').each(function() {
		var id = '#tc-btn-primary-bg-color';
		if ( $(this).hasClass('btn-secondary') ) id = '#tc-btn-secondary-bg-color';
		if ( $(this).hasClass('btn-success') ) id = '#tc-btn-success-bg-color';
		if ( $(this).hasClass('btn-info') ) id = '#tc-btn-info-bg-color';
		if ( $(this).hasClass('btn-warning') ) id = '#tc-btn-warning-bg-color';
		if ( $(this).hasClass('btn-danger') ) id = '#tc-btn-danger-bg-color';
		if ( $(this).hasClass('btn-light') ) id = '#tc-btn-light-bg-color';
		if ( $(this).hasClass('btn-dark') ) id = '#tc-btn-dark-bg-color';
		if ( $(this).hasClass('fas') ) id = '#tc-icon-bg-color';
		if ( $(this).hasClass('fab') ) id = '#tc-icon-bg-color';
		$(this).hover(
			function() {
				$(this).css('background-color', lightenDarkenColor($(id).val(), 50));
			}, function() {
				$(this).css('background-color', $(id).val());
			}
		);
	});


	// storage behaviour when checking/unchecking
	$('.keep-changes').on('change', function() {
		$(this).prop('checked') ? saveCustomizerCSS() : deleteData();
	});


	// update css when using colorpicker
	$('.picker').colorpicker().on('changeColor', function(e) {
		var selector = getSelector( $(e.target) );
		$(selector).css(e.target.dataset.element, e.color.toHex());
		$(this).find('input').css('background-color', e.color.toHex());
		$(this).find('input').css('color', getContrast(e.color.toHex()));
		for (var key in inputJSON) {
			if (inputJSON[key].selector === selector) {
				inputJSON[key].attributes[e.target.dataset.element] = e.color.toHex();
				if ($.inArray(selector, changed) == -1) changed.push(selector);
				saveCustomizerCSS();
				break;
			}
		}
	});
})(jQuery);
