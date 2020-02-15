<template>
	<div class="tracker-rules">
		<div class="card mb-2">
			<div class="card-header">
				Conditions
			</div>
			<div class="card-body conditions">
				<ui-predicate v-model="conditionsData" :columns="conditionsColumns" @changed="onChangeConditions" @initialized="onChangeConditions"/>
			</div>
		</div>
		<div class="card mb-2">
			<div class="card-header">
				Actions
			</div>
			<div class="card-body actions">
				<ui-predicate v-model="actionsData" :columns="actionsColumns" @changed="onChangeActions" @initialized="onChangeActions"/>
			</div>
		</div>
		<div class="card mb-2">
			<div class="card-header">
				Else
			</div>
			<div class="card-body else">
				<ui-predicate v-model="elseData" :columns="actionsColumns" @changed="onChangeElse" @initialized="onChangeElse"/>
			</div>
		</div>

		<div class="card">
			<article class="message is-info">
				<div class="message-header">
					<p>Tips</p>
				</div>
				<div class="message-body">
					Tips: Use <code>alt + click</code> to create a sub-group.
				</div>
			</article>
		</div>

		<div class="card">
			<div class="card-header">
				Conditions Output
			</div>
			<div class="card-content">
				<textarea name="conditions" class="form-control" readonly="readonly">{{ conditionsoutput }}</textarea>
			</div>
		</div>
		<div class="card">
			<div class="card-header">
				Actions Output
			</div>
			<div class="card-content">
				<textarea name="actions" class="form-control" readonly="readonly">{{ actionoutput }}</textarea>
			</div>
		</div>
		<div class="card">
			<div class="card-header">
				Else Output
			</div>
			<div class="card-content">
				<textarea name="else" class="form-control" readonly="readonly">{{ elseoutput }}</textarea>
			</div>
		</div>
	</div>
</template>

<script>
	Vue.use(UIPredicate);

	import TextArgument from "./vue_TextArgument.js";
	import NumberArgument from "./vue_NumberArgument.js";
	import DateArgument from "./vue_DateArgument.js";
	import NoArgument from "./vue_NoArgument.js";
	import BoolArgument from "./vue_BoolArgument.js";
	import CollectionArgument from "./vue_CollectionArgument.js";


	export default {
		name: "tracker-rules",
		components: {},
		data()
		{
			return {
				conditionsoutput: {},
				actionoutput: {},
				elseoutput: {},
				conditionsData: null,
				conditionsColumns: {
					targets: null,
					// besides array list names, everything else follows convention
					// https://github.com/FGRibreau/sql-convention
					operators: null,
					types: null,
					logicalTypes: [
						{
							logicalType_id: "any",
							label: "Any",
						},
						{
							logicalType_id: "all",
							label: "All",
						},
						{
							logicalType_id: "none",
							label: "None",
						},
					],
					argumentTypes: [
						{
							argumentType_id: "DateTime",
							component: DateArgument,
						},
						{
							argumentType_id: "Text",
							component: TextArgument,
						},
						{
							argumentType_id: "Number",
							component: NumberArgument,
						},
						{
							argumentType_id: "Boolean",
							component: BoolArgument,
						},
						{
							argumentType_id: "Nothing",
							component: NoArgument,
						},
						{
							argumentType_id: "Collection",
							component: CollectionArgument,
						},
					],
				},
				actionsData: null,
				actionsColumns: {
					targets: null,
					operators: null,
					types: null,
					// TODO logicalTypes should be removed for actions
					logicalTypes: [
						{
							logicalType_id: "any",
							label: "Any",
						},
						{
							logicalType_id: "all",
							label: "All",
						},
						{
							logicalType_id: "none",
							label: "None",
						},
					],
					argumentTypes: [
						{
							argumentType_id: "Nothing",
							component: NoArgument,
						},
					],
				},
				elseData: null,
			};
		},
		methods: {
			// TODO probably near here: set new predicates target to this field as the default

			onChangeConditions(diff)
			{
				this.conditionsoutput = diff;
			},
			onChangeActions(diff)
			{
				this.actionoutput = diff;
			},
			onChangeElse(diff)
			{
				this.elseoutput = diff;
			},
		},
		beforeMount: function () {

			let fields = this.$parent.targetFields,
				field = {},
				thisvue = this,
				conditionsTargets = [],
				actionsTargets = [{
					target_id: "NoTarget",
					label: "",
					type_id: "Nothing",

				}];

			thisvue.conditionsColumns.operators = thisvue.$parent.definitiion.operators;
			thisvue.conditionsColumns.types     = thisvue.$parent.definitiion.types;
			thisvue.actionsColumns.operators    = thisvue.$parent.definitiion.actions;
			thisvue.actionsColumns.types        = thisvue.$parent.definitiion.types;

			if (fields !== undefined) {

				fields.forEach(function (value) {
					if (value.argumentType === "Collection") {
						value.fieldId += "[]";
					}
					conditionsTargets.push({
						target_id: "ins_" + value.fieldId,
						label: value.name,
						type_id: value.argumentType,
					});
					actionsTargets.push({
						target_id: "ins_" + value.fieldId,
						label: value.name,
						type_id: "Field",
					});

					if (value.fieldId === thisvue.$parent.fileId ||
						value.argumentType === "Collection" && value.fieldId === (thisvue.$parent.fileId + "[]")) {
						field = value;
					}
				});

				thisvue.conditionsColumns.targets = conditionsTargets;
				thisvue.actionsColumns.targets    = actionsTargets;
			}

			// set conditions field to thids one if nothing else set
			if (! thisvue.$parent.rules.conditions) {
				let operatorId = "";

				if (field.argumentType === "Text") {
					operatorId = "TextContains";
				} else if (field.argumentType === "Number") {
					operatorId = "NumberEquals";
				} else if (field.argumentType === "Boolean") {
					operatorId = "BooleanTrueFalse";
				} else if (field.argumentType === "DateTime") {
					operatorId = "DateTimeOn";
				} else if (field.argumentType === "Collection") {
					operatorId = "CollectionContains";
				}

				thisvue.conditionsData = {
					logicalType_id: "any",
					predicates: [{
						target_id: "ins_" + field.fieldId,
						operator_id: operatorId,
						argument: "",
					}]
				}
			} else {
				thisvue.conditionsData = thisvue.$parent.rules.conditions;
			}

			// validate the targets in case options have changed or fields deleted
			let getPredicates = function (predicates) {
				return predicates.filter(function (predicate) {
					let found = thisvue.actionsColumns.targets.find(function (target) {
						if (predicate.target_id === target.target_id) {
							return true;
						}
					});
					if (!found) {
						console.error("Tracker Field Rules: field " + predicate.target_id + " not found in actions");
					}
					return found;
				});
			};
			if (thisvue.$parent.rules.actions) {
				thisvue.actionsData = thisvue.$parent.rules.actions;
				thisvue.actionsData.predicates = getPredicates(thisvue.$parent.rules.actions.predicates);
			}
			if (thisvue.$parent.rules.else) {
				thisvue.elseData = thisvue.$parent.rules.else;
				thisvue.elseData.predicates = getPredicates(thisvue.$parent.rules.else.predicates);
			}
		}
	};
</script>
