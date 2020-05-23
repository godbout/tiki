<template>
	<div class="dp-editor--task-title__wrapper">
		<span>{{store.state.activeTimestamp + 1}}. </span>
		<input
			type="text"
			placeholder="Time entry title"
			v-on:input="handleInputTitle"
			:value="getTitle"
		>
		<textarea
			style="width: 100%"
			type="text"
			placeholder="Description"
			v-on:input="handleInputDescription"
			:value="getDescription"
		>
	</div>
</template>

<script>
	export default {
		name: "DurationPickerTitle",
		data: function () {
			return {
				store: this.$parent.store
			}
		},
		computed: {
			getTitle: function () {
				return this.store.getTimestamp(this.store.state.activeTimestamp).title
			},
			getDescription: function () {
				return this.store.getTimestamp(this.store.state.activeTimestamp).description
			}
		},
		methods: {
			handleInputTitle: function (e) {
				this.store.setTimestamp('update', {
					...this.store.getTimestamp(this.store.state.activeTimestamp),
					title: e.target.value
				});
			},
			handleInputDescription: function (e) {
				this.store.setTimestamp('update', {
					...this.store.getTimestamp(this.store.state.activeTimestamp),
					description: e.target.value
				});
			}
		}
	};
</script>