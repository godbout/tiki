<template>
	<div class="tracker-rules">
		<div class="card mb-2">
			<div class="card-header">
				Condition
			</div>
			<div class="card-body">
				<ui-predicate v-model="conditionsData" :columns="conditionsColumns" @changed="onChangeConditions" @initialized="onChangeConditions"/>
			</div>
		</div>
		<div class="card mb-2">
			<div class="card-header">
				Actions
			</div>
			<div class="card-body">
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
			<header class="card-header">
				<p class="card-header-title">
					Output </p>
			</header>
			<div class="card-content">
				<pre>{{ ast }}</pre>
			</div>
			<div class="card-content">
				<pre>{{ actionoutput }}</pre>
			</div>
		</div>
	</div>
</template>

<script>
	Vue.use(UIPredicate);

	import TextArgument from "./vue_TextArgument.js";
	import DateArgument from "./vue_DateArgument.js";
	import NullArgument from "./vue_NullArgument.js";


	export default {
		name: "tracker-rules",
		components: {},
		data()
		{
			return {
				ast: {},
				conditionsData: {
					logicalType_id: "all",
					predicates: [
						{
							"target_id": "field.value",
							"operator_id": "is",
							"argument": 42
						},
					],
				},
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
							type_id: "int",
						},
					],
					// besides array list names, everything else follows convention
					// https://github.com/FGRibreau/sql-convention
					operators: [
						{
							operator_id: "is",
							label: "is",
							argumentType_id: "smallString",
						},
						{
							operator_id: "contains",
							label: "Contains",
							argumentType_id: "smallString",
						},
						{
							operator_id: "isLowerThan",
							label: "<",
							argumentType_id: "number",
						},
						{
							operator_id: "isEqualTo",
							label: "=",
							argumentType_id: "number",
						},
						{
							operator_id: "isHigherThan",
							label: ">",
							argumentType_id: "number",
						},
						{
							operator_id: "is_date",
							label: "is",
							argumentType_id: "datepicker",
						},
						{
							operator_id: "isEmpty",
							label: "is empty",
							argumentType_id: "smallString",
						},
						{
							operator_id: "isNotEmpty",
							label: "is not empty",
							argumentType_id: "smallString",
						},
					],
					types: [
						{
							type_id: "int",
							operator_ids: ["isLowerThan", "isEqualTo", "isHigherThan", "isEmpty", "isNotEmpty"],
						},
						{
							type_id: "string",
							operator_ids: ["is", "contains", "isEmpty", "isNotEmpty"],
						},
						{
							type_id: "datetime",
							operator_ids: ["is_date", "isEmpty", "isNotEmpty"],
						},
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
							argumentType_id: "datepicker",
							component: DateArgument,
						},
						{
							argumentType_id: "smallString",
							component: TextArgument,
						},
						{
							argumentType_id: "number",
							component: TextArgument,
						},
					],
				},
				actionsData: {
					logicalType_id: "all",
					predicates: [
						{
							"target_id": "field.name",
							"operator_id": "show",
							"argument": null
						},
					],
				},
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
				this.ast = diff;
			},
			onChangeActions(diff)
			{
				this.actionoutput = diff;
			},
		}
	};
</script>
