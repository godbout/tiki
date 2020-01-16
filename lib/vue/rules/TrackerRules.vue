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
	</div>
</template>

<script>
	Vue.use(UIPredicate);

	import TextArgument from "./vue_TextArgument.js";
	import DateArgument from "./vue_DateArgument.js";
	import NullArgument from "./vue_NullArgument.js";
	import BoolArgument from "./vue_BoolArgument.js";


	export default {
		name: "tracker-rules",
		components: {},
		data()
		{
			return {
				conditionsoutput: {},
				actionoutput: {},
				conditionsData: this.$parent.rules.conditions,
				conditionsColumns: {
					targets: [
						{
							target_id: "field.value",
							label: "Field value",
							type_id: "string",
						},
						{
							target_id: "field.showing",
							label: "Field Showing",
							type_id: "bool",
						},
					],
					// besides array list names, everything else follows convention
					// https://github.com/FGRibreau/sql-convention
					operators: [
						{
							operator_id: "is",
							label: "is",
							argumentType_id: "string",
						},
						{
							operator_id: "contains",
							label: "contains",
							argumentType_id: "string",
						},
						{
							operator_id: "isEmpty",
							label: "is empty",
							argumentType_id: "string",
						},
						{
							operator_id: "isNotEmpty",
							label: "is not empty",
							argumentType_id: "string",
						},
						{
							operator_id: "isLowerThan",
							label: "<",
							argumentType_id: "number",
						},
						{
							operator_id: "isEqualTo",
							label: "==",
							argumentType_id: "number",
						},
						{
							operator_id: "isNotEqualTo",
							label: "<>",
							argumentType_id: "number",
						},
						{
							operator_id: "isHigherThan",
							label: ">",
							argumentType_id: "number",
						},
						{
							operator_id: "on",
							label: "on",
							argumentType_id: "datetime",
						},
						{
							operator_id: "before",
							label: "before",
							argumentType_id: "datetime",
						},
						{
							operator_id: "after",
							label: "after",
							argumentType_id: "datetime",
						},
						{
							operator_id: "truefalse",
							label: "is",
							argumentType_id: "bool",
						},
					],
					types: [
						{
							type_id: "int",
							operator_ids: ["isLowerThan", "isEqualTo", "isHigherThan", "isNotEqualTo"],
						},
						{
							type_id: "string",
							operator_ids: ["is", "contains", "isEmpty", "isNotEmpty"],
						},
						{
							type_id: "datetime",
							operator_ids: ["on", "before", "after"],
						},
						{
							type_id: "bool",
							operator_ids: ["truefalse"],
						},
						// TODO add array type
					],
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
							argumentType_id: "datetime",
							component: DateArgument,
						},
						{
							argumentType_id: "string",
							component: TextArgument,
						},
						{
							argumentType_id: "number",
							component: TextArgument,
						},
						{
							argumentType_id: "bool",
							component: BoolArgument,
						},
					],
				},
				actionsData: this.$parent.rules.actions,
				actionsColumns: {
					targets: [
						{
							target_id: "field.name",
							label: "Name",
							type_id: "field",
						},
						{
							target_id: "field.description",
							label: "Descrition",
							type_id: "field",
						},
						{
							target_id: "field.date",
							label: "Date",
							type_id: "field",
						},
					],
					// besides array list names, everything else follows convention
					// https://github.com/FGRibreau/sql-convention
					operators: [
						{
							operator_id: "show",
							label: "Show",
							argumentType_id: "null",
						},
						{
							operator_id: "hide",
							label: "Hide",
							argumentType_id: "null",
						},
						{
							operator_id: "required",
							label: "Required",
							argumentType_id: "null",
						},
						{
							operator_id: "notRequired",
							label: "Not Required",
							argumentType_id: "null",
						},
					],
					types: [
						{
							type_id: "field",
							operator_ids: ["show", "hide", "required", "notRequired"],
						},
					],
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
							argumentType_id: "null",
							component: NullArgument,
						},
					],
				},
			};
		},
		methods: {
			onChangeConditions(diff)
			{
				this.conditionsoutput = diff;
			},
			onChangeActions(diff)
			{
				this.actionoutput = diff;
			},
		},
		mounted: function () {

			// remove types of conditions this field can not do
			let fieldType = this.$parent.fieldType;

			this.conditionsColumns.operators.forEach(function (value, index, array) {
				if (value.argumentType_id !== fieldType) {
					array.splice(index, 1);
				}
			});

			if (this.$parent.targetFields !== undefined) {
				let fields = this.$parent.targetFields,
					targets = [];

				fields.forEach(function (value) {
					targets.push({
						target_id: "tracker_field_" + value.permName,
						label: value.name,
						type_id: "field",
					});
				});

				this.actionsColumns.targets = targets;
			}
		}
	};
</script>
