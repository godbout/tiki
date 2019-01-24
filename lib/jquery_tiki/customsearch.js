/* (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 * $Id$
 *
 * Custom search js helper function, mainly to maintain the search state in the url hash
 *
 * N.B. Only works for a single customsearch instance per page with the id #customsearch_0
 */


(function ($) {

	var $csForm, $formInputs;

	$(document).on("formSearchReady", function () {

		$csForm = $('#customsearch_0');
		$(".jscal input, .chosen-search-input", $csForm).addClass("ignore");

		$formInputs = $("input[type!=Submit]:not(.ignore), select:not(.ignore)", $csForm);
		$formInputs.each(function () {	// record defaults for search inputs to save cluttering up the location.hash
			var $this = $(this);
			if ($this.is("input[type=checkbox]")) {
				$this.data("default", $this.prop("checked"));
			} else {
				$this.data("default", $this.val());
			}
		});

		// FIXME currently only works with the default id, but avoid js error here when it's customsearch_42 or something
		customsearch_0 = typeof customsearch_0 === "object" ? customsearch_0 : {};

		$csForm.off("submit").submit(function () {

			// process other custom search functions before sumbitting here

			customsearch_0.load();
			return false;
		});

		$("#sortby", $csForm).change(function () {
			customsearch_0.sort_mode = $(this).val();
			// remove the representation of this select in the search
			delete customsearch_0.searchdata.sortby;
			$csForm.submit();
			return false;
		});

		$("#max", $csForm).change(function () {
			customsearch_0.maxRecords = $(this).val();
			delete customsearch_0.searchdata.max;
			$csForm.submit();
		});

		getHash();
	});


	$(document).on("pageSearchReady", function () {
		// results loaded
		var $csResults = $("#customsearch_0_results");

		if (typeof lozad !== "undefined") {
			lozad('.lozad', {
				load: function (el) {
					el.src = el.dataset.src;
					el.onload = function () {
						el.classList.add('lozadFade');
					}
				}
			}).observe();
		}

		$('.facets ul').registerFacet()
			.tiki_popover();


		// sticky facets
		var $facets = $(".facets"),
			pos = $facets.offset();

		if (pos !== undefined) {
			var width = $facets.css("width"),
				topOffset = 70,
				footer = $("footer"),
				footPos = footer.offset().top,
				facetHeight = $facets.height();

			$window.scroll(function () {
				var windowpos = $window.scrollTop();
				var top = 60;
				if (windowpos > pos.top - topOffset) {
					if (footPos - windowpos - topOffset < facetHeight) {
						top = footPos - windowpos - facetHeight - 10;
					} else {
						top = 60;
					}
					$facets.css({
						position: "fixed",
						top: top + "px",
						width: width
					});
					//console.log(footPos - windowpos);
				} else {
					$facets.css({
						position: "inherit",
						top: "auto",
						width: width
					});
				}

			});
		}

		// update the url hash with the current returned search results
		setHash();

		return true;
	});

	function setHash()
	{
		var ser = "";

		$formInputs.each(function () {
			var $this = $(this),
				defaultValue = $this.data("default");

			if ($this.is("input[type=checkbox]")) {
				if ($this.prop("checked") !== defaultValue) {
					if ($this.attr("id")) {
						ser += $this.attr("id") + "=1&";
					} else {
						ser += "." + $this.prop("className") + "=1&";
					}
				}
			} else {
				var currentValue = $this.val();
				if ((typeof currentValue === "string" && currentValue !== defaultValue) || JSON.stringify(currentValue) !== JSON.stringify(defaultValue)) {
					var val = encodeURIComponent(currentValue).replace("%20", "+");
					if ($this.attr("id")) {
						ser += $this.attr("id") + "=" + val + "&";
					} else {
						ser += "." + $this.prop("className") + "=" + val + "&";
					}
				}
			}
		});

		var pagenum = parseInt($('.pagination .active').text());
		if (pagenum > 1) {        // offset
			var max = $("#max", $csForm).val();
			ser += "offset=" + (pagenum - 1) * max;
		}

		var $sortLink = $("th a .icon-sort-up,th a .icon-sort-down", ".customsearch_results").parent();
		if ($sortLink.length) {
			var sort_mode = $sortLink.attr("onclick").match(/\.sort_mode='(.*?)'/);
			if (sort_mode) {
				ser += "sort_mode=" + sort_mode[1];
			}
		}

		window.location.hash = ser.replace(/&$/, "");
	}

	function getHash()
	{
		var key, e, a, r, d, q, hashKey;
		if (location.hash) {
			// from http://stackoverflow.com/questions/4197591/parsing-url-hash-fragment-identifier-with-javascript - thanks :)
			var hashParams = {};
			a = /\+/g;
			r = /([^&;=]+)=?([^&;]*)/g;
			d = function (s) {
				return decodeURIComponent(s.replace(a, " "));
			};
			q = window.location.hash.substring(1);

			while (e = r.exec(q)) {
				hashParams[d(e[1])] = d(e[2]);
			}

			var triggerIt = false, $el, selector;
			customsearch_0.quiet = true;

			for (hashKey in hashParams) {
				if (hashParams.hasOwnProperty(hashKey)) {
					if (hashKey.indexOf(".") === 0) {
						selector = hashKey + ":first";
					} else {
						selector = "#" + hashKey;
					}
					$el = $(selector, $csForm);
					var value = hashParams[hashKey];
					if ($(selector + "[type=checkbox]").length) {
						triggerIt = true;
						$el.prop("checked", value !== "").trigger('change');
					} else {
						if ($el.length) {
							triggerIt = true;
							if ($el.prop("multiple") && value.indexOf(",") > -1) {
								value = value.split(",");
							}
							$el.val(value).trigger('change').trigger("chosen:updated");
						} else {
							if (hashKey === "offset") {
								triggerIt = true;
								customsearch_0.offset = value;
							} else if (hashKey === "sort_mode") {
								triggerIt = true;
								// value is what the sort mode will change to if clicked
								if (value.match(/_asc$/)) {
									value = value.replace(/_asc$/, '_desc')
								} else {
									value = value.replace(/_desc$/, '_asc')
								}
								customsearch_0.sort_mode = value;
							}
						}
					}
				}
			}
			customsearch_0.quiet = false;
			if (triggerIt) {
				$csForm.submit();
			}
		} else {
			if (window.location.search) {
				var params = {};
				a = /\+/g;
				r = /([^&;=]+)=?([^&;]*)/g;
				d = function (s) {
					return decodeURIComponent(s.replace(a, " "));
				};
				q = window.location.search.substring(1);

				while (e = r.exec(q)) {
					params[d(e[1])] = d(e[2]);
				}

				customsearch_0.offset = 0;

				if (params.q) {	//  && params.q != ""
					$("#search", $csForm).val(params.q).trigger("change");
					delete params.q;
				}

				if (params.t) {	//  type from portal pages
					$("label:contains(" + params.t + ") > input", $csForm).prop("checked", true).trigger("change");
					delete params.t;
				}

				$.each(params, function (k, v) {
					var $el = $("[name='" + k + "']");
					if (k !== "q" && $el.length) {
						$el.val(v).trigger("chosen:updated");
					}
				});

				$("select", $csForm).trigger("chosen:updated");
				$csForm.submit();

			} else {
				if (window.location.pathname.indexOf("cat") === 1) {
					$csForm.submit();
				}
			}
		}
		$csForm.trigger("chosen:updated");
	}

}(jQuery));
