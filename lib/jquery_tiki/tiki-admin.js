(function ($) {


	$(function () {
		// highlight the admin icon (anchors)
		var $anchors = $(".adminanchors li a, .admbox"),
			bgcol = $anchors.is(".admbox") ? $anchors.css("background-color") : $anchors.parent().css("background-color");

		$("input[name=lm_criteria]").keyup( function () {
			var criterias = this.value.toLowerCase().split( /\s+/ ), word, text;
			$anchors.each( function() {
				var $parent = $(this).is(".admbox") ? $(this) : $(this).parent();
				if (criterias && criterias[0]) {
					text = $(this).attr("data-alt").toLowerCase();
					for( var i = 0; criterias.length > i; ++i ) {
						word = criterias[i];
						if ( word.length > 0 && text.indexOf( word ) == -1 ) {
							$parent.css("background", "");
							return;
						}
					}
					$parent.css("background", "radial-gradient(white, " + bgcol + ")");
				} else {
					$parent.css("background", "");
				}
			});
		});
	});

	// AJAX plugin list load for admin/textarea/plugins
	var pluginSearchTimer  = null;
	$("#pluginfilter").change(function (event) {
		var filter = $(this).val();
		if (filter.length > 2 || !filter) {
			if (pluginSearchTimer) {
				clearTimeout(pluginSearchTimer);
				pluginSearchTimer = null;
			}
			$("#pluginlist").load($.service("plugin", "list"), {
				filter: filter
			}, function (response, status, xhr) {
				if (status === "error") {
					$("#pluginfilter").showError(xhr);
				}
				$(this).tikiModal();
			}).tikiModal(tr("Loading..."));
		}
	}).keydown(function (event) {
		if (event.which === 13) {
			event.preventDefault();
			$(this).change();
		} else if (! pluginSearchTimer) {
			pluginSearchTimer = setTimeout(function () {
				$("#pluginfilter").change();
			}, 1000);
		}
	});

	// Plugin Alias management JS

	var $pluginAliasAdmin = $("#contentadmin_textarea-plugin_alias");

	if ($pluginAliasAdmin.length) {
		/**
		 * General purpose param adding icons
		 */
		$('.add-param', $pluginAliasAdmin).click(function () {
			var $fieldset = $(this).closest("fieldset"),
				// for composed args/params (fieldset) the template comes after the one for a new param,
				// so we need closestDescendent, not :first
				$template = $fieldset.closestDescendent(".param.d-none"),
				$clone = $template.clone(),
				index = $fieldset.find(".param:visible").length;

			$clone.find('input:not(.chosen-search-input)').each(function () {
				$(this).attr('name', $(this).attr('name').replace('__NEW__', index));
			}).val('').find('label').each(function () {
				$(this).attr('for', $(this).attr('for').replace('__NEW__', index));
			});

			$clone.find(".d-none").addBack().removeClass("d-none");

			if (jqueryTiki.autocomplete && jqueryTiki.ui) {
				var plugin = $("#implementation").data("plugin");

				if (plugin) {
					// get the param names
					var params = $.map(plugin.params, function(element,index) {return index});

					$clone.find("input.sparam-name").autocomplete({
						minLength: 1,
						source: params,
						select: function(e, ui) {
							var $defInput = $(this).closest(".param").find("input.sparam-default"),
								options = [],
								param = plugin.params[ui.item.value];

							// collect the options if any
							$.each(param.options, function (k, v) {
								options.push(v.value)
							});

							// set the default as the default
							$defInput.val(param.default);

							// autocomplete the defaults on the options
							if (options) {
								$defInput.autocomplete({
									minLength: 1,
									source: options
								});
							}
						}

					});
				}
			}

			$template.parent().append($clone);	//  .tiki_popover() doesn't work as the title has been removeed on page
				// load... FIXME?;

			return false;
		});

		$($pluginAliasAdmin).on("click", ".delete-param", function () {
			$(this).popover("hide").parents(".param").remove();
		});

		setTimeout(function () {
/*
			if (jqueryTiki.validate) {
				$pluginAliasAdmin.closest("form").validate({
					rules: {
						plugin_alias: "required",
						implementation: "required"
					}
				});
			}
*/

			$("#plugin_alias").change(function () {
				var $this = $(this),
					val = $this.val().toLowerCase(),
					$pluginName = $("#plugin_name");

				if (!$pluginName.val()) {
					$pluginName.val(val.replace(/\s+/g, " ").replace(
						/\w\S*/g, function (txt) {
								return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
							}
						)
					);
				}
				$this.val(val.replace(/\W+/g, ""));
			});

			$("#implementation").change(function () {
				var val = $("#implementation").val();
				if (val) {
					$.getJSON(
						$.service("plugin", "list", {
							filter: val,
							title: val	// to get it back later
						}),
						function (data) {
							if (data && data.plugins[data.title]) {
								var plugin = data.plugins[data.title];

								if (plugin.prefs) {
									$("#plugin_deps").val(plugin.prefs.join(","));
								}

								$("#implementation").data("plugin", plugin);
							}
						}
					);
				}
			}).change();

		}, 500);

	}


})(jQuery);
