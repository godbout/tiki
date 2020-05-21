<template>
	<div class="dp-modal--backdrop">
		<div class="dp-modal--container">
			<div class="dp-amount--input__container">
				<div class="dp-amount--input__header">
					<span class="dp-toggle-mode unselectable" v-on:click="toggleMode">Edit</span>
					<span class="dp-toggle-mode unselectable" v-if="store.state.chronometer" v-on:click="toggleMode">Timesheets</span>
					<div class="dp-amount--input__close" v-on:click="handleCloseModal" title="close">
						<i class="fas fa-times"></i>
					</div>
				</div>
				<DurationPickerEditor v-show="!showChronometer" />
				<DurationPickerChronometer v-if="store.state.chronometer" v-show="showChronometer" />
			</div>
		</div>
	</div>
</template>

<script>
	import DurationPickerEditor from "./vue_DurationPickerEditor.js";
	import DurationPickerChronometer from "./vue_DurationPickerChronometer.js";

	export default {
		name: "DurationPickerModal",
		components: {
			durationpickerchronometer: DurationPickerChronometer,
			durationpickereditor: DurationPickerEditor
		},
		data: function () {
			return {
				showChronometer: this.$parent.store.state.chronometer ? true : false,
				store: this.$parent.store,
			}
		},
		props: {
			handleCloseModal: Function
		},
		methods: {
			toggleMode: function() {
				this.showChronometer = !this.showChronometer;
			}
		}
	};
</script>