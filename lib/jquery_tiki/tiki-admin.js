(function ($) {
	$(document).on('change', '.preference :checkbox:not(.pref-reset)', function () {
		var childBlock = $(this).data('tiki-admin-child-block')
			, childMode = $(this).data('tiki-admin-child-mode')
			, checked = $(this).is(':checked')
			, disabled = $(this).prop('disabled')
			, $depedencies = $(this).parents(".adminoption").find(".pref_dependency")
			, childrenElements = null
			;
		var childrenElements = $(this).parents('.adminoptionbox').nextAll('.adminoptionboxchild').eq(0).find(':input[id^="pref-"]');

		if (childBlock) {
			childrenElements = $(childBlock).find(':input[id^="pref-"]');
		}

		if (childMode === 'invert') {
			// FIXME: Should only affect childBlock, not $depedencies. From r54386
			checked = ! checked;
		}

		if (disabled && checked) {
			$(childBlock).show('fast');
			$depedencies.show('fast');
		} else if (disabled || ! checked) {
			/* Only hides child preferences if they are all at default values.
			Purpose questioned in https://sourceforge.net/p/tikiwiki/mailman/tikiwiki-cvs/thread/F2DE8896807BF045932776107E2E783D350674DB%40CT20SEXCHP02.FONCIERQC.INTRA/#msg36171225
			 */
			var hideBlock = true;
			childrenElements.each(function( index ) {
				var value = $( this ).val();
				var valueDefault = $( this ).siblings('span.pref-reset-wrapper').children('.pref-reset').attr('data-preference-default');

				if (typeof valueDefault != 'undefined' && value != valueDefault) {
					hideBlock = false;
				}
			});

			if (hideBlock) {
				$(childBlock).hide('fast');
				$depedencies.hide('fast');
			}
		} else {
			$(childBlock).show('fast');
			$depedencies.show('fast');
		}
	});

	$(document).on('click', '.pref-reset-wrapper a', function () {
		var box = $(this).closest('span').find(':checkbox');
		box.click();
		$(this).closest('span').children( ".pref-reset-undo, .pref-reset-redo" ).toggle();
		return false;
	});
	
	$(document).on('click', '.pref-reset', function() {
		var c = $(this).prop('checked');
		var $el = $(this).closest('.adminoptionbox').find('input:not(:hidden),select,textarea')
			.not('.system').attr( 'disabled', c )
			.css("opacity", c ? .6 : 1 );
		var defval = $(this).data('preference-default');

		if ($el.is(':checkbox')) {
			$(this).data('preference-default', $el.prop('checked') ? 'y' : 'n');
			$el.prop('checked', defval === "y");
		} else {
			$(this).data('preference-default', $el.val());
			$el.val(defval);
		}
		$el.change();
		if (jqueryTiki.chosen) {
			$el.trigger("chosen:updated");
		}
	});

	$(document).on('change', '.preference select', function () {
		var childBlock = $(this).data('tiki-admin-child-block')
			, selected = $(this).val()
			, childMode = $(this).data('tiki-admin-child-mode')
			;

		$(childBlock).hide();
		$(childBlock + ' .modified').show();
		$(childBlock + ' .modified').parent().show();

		if (selected && /^[\w-]+$/.test(selected)) {
			$(childBlock).filter('.' + selected).show();
		}
		if (childMode === 'notempty' && selected.length) {
			$(childBlock).show();
		}
	});

	$(document).on('change', '.preference :radio', function () {
		var childBlock = $(this).data('tiki-admin-child-block');

		if ($(this).prop('checked')) {
			$(childBlock).show('fast');
			$(this).closest('.preference').find(':radio').not(this).change();
		} else {
			$(childBlock).hide();
		}
	});

	$(function () {
		$('.preference :checkbox, .preference select, .preference :radio').change();
	});

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
			if (jqueryTiki.validate) {
				$pluginAliasAdmin.closest("form").validate({
					rules: {
						plugin_alias: "required",
						implementation: "required"
					}
				});
			}

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
