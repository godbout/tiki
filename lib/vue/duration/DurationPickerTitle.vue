<template>
	<div class="dp-editor--task-title__wrapper">
		<div class="dp-editor--task-title__input">
			<span>{{store.state.activeTimestamp + 1}}. </span>
			<input
				type="text"
				placeholder="Title"
				v-on:input="handleInputTitle"
				:value="getTitle"
			>
		</div>
		<div class="dp-editor--task-title__textarea">
			<textarea
				style="width: 100%"
				type="text"
				placeholder="Description"
				v-on:input="handleInputDescription"
				:value="getDescription"
			>
		</div>
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