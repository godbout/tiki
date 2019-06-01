var ge;
$(function() {
	ge = new GanttMaster();
	ge.resourceUrl = "vendor_bundled/vendor/robicch/jquery-gantt/res/";
	ge.set100OnClose=true;
	ge.init($("#workSpace"));
	loadI18n();
	delete ge.gantt.zoom;
	var project=loadFromLocalStorage();

	ge.loadProject(project);
	ge.checkpoint();
	ge.editor.element.oneTime(100, "cl", function () {
		$(this).find("tr.emptyRow:first").click();
	});

	$.JST.loadDecorator("RESOURCE_ROW", function(resTr, res) {
		resTr.find(".delRes").click(function() {
			$(this).closest("tr").remove();
		});
	});

	$.JST.loadDecorator("ASSIGNMENT_ROW", function(assigTr, taskAssig) {
		var resEl = assigTr.find("[name=resourceId]");
 		var opt = $("<option></option>");
		resEl.append(opt);
		for (var i=0; i < taskAssig.task.master.resources.length; i++) {
			var res = taskAssig.task.master.resources[i];
			opt = $("<option></option>");
			opt.val(res.id).html(res.name);
			if (taskAssig.assig.resourceId == res.id) {
				opt.attr("selected", "true");
			}
			resEl.append(opt);
		}

		var roleEl = assigTr.find("[name=roleId]");
		var opt = $("<option></option>");
		roleEl.append(opt);
		for (var i=0; i < taskAssig.task.master.roles.length; i++) {
			var role = taskAssig.task.master.roles[i];
			var optr = $("<option></option>");
			optr.val(role.id).html(role.name);
			if (taskAssig.assig.roleId == role.id) {
				optr.attr("selected", "true");
			}
			roleEl.append(optr);
		}

		if (taskAssig.task.master.permissions.canWrite && taskAssig.task.canWrite) {
			assigTr.find(".delAssig").click(function() {
				var tr = $(this).closest("[assId]").fadeOut(200, function() {
					$(this).remove()
				});
			});
		}
	});
});