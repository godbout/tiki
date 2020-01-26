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


	export default {
		name: "tracker-rules",
		components: {},
		data()
		{
			return {
				conditionsoutput: {},
				actionoutput: {},
				elseoutput: {},
				conditionsData: this.$parent.rules.conditions,
				conditionsColumns: {
					targets: [
						{
							target_id: "field.value",
							label: "Field value",
							type_id: "Text",
						},
						{
							target_id: "field.showing",
							label: "Field Showing",
							type_id: "Boolean",
						},
					],
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
					],
				},
				actionsData: this.$parent.rules.actions,
				actionsColumns: {
					targets: null,
					// besides array list names, everything else follows convention
					// https://github.com/FGRibreau/sql-convention
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
				elseData: this.$parent.rules.else,
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
		mounted: function () {

			// remove types of conditions this field can not do
			let fieldType = this.$parent.fieldType,
				toDelete = [], typesToAllow = [];

			this.conditionsColumns.operators = this.$parent.definitiion.operators;
			this.conditionsColumns.types     = this.$parent.definitiion.types;
			this.actionsColumns.operators    = this.$parent.definitiion.actions;
			this.actionsColumns.types        = this.$parent.definitiion.types;

			//this.conditionsColumns.targets[0].type_id = fieldType;

			if (this.$parent.targetFields !== undefined) {
				let fields = this.$parent.targetFields,
					conditionsTargets = [],
					actionsTargets = [];

				fields.forEach(function (value) {
					conditionsTargets.push({
						target_id: "tracker_field_" + value.permName,
						label: value.name,
						type_id: value.argumentType,
					});
					actionsTargets.push({
						target_id: "tracker_field_" + value.permName,
						label: value.name,
						type_id: "Field",
					});
				});

				this.conditionsColumns.targets = conditionsTargets;
				this.actionsColumns.targets    = actionsTargets;
			}
		}
	};
</script>
