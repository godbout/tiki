<template>
	<div class="dp-amount--input__container">
		<div class="dp-amount--input__header">
			<div class="dp-toggle-mode unselectable" v-if="store.state.chronometer" v-on:click="toggleMode">{{ mode }}</div>
			<div class="dp-amount--input__close" v-on:click="handleCloseModal" title="close">x</div>
		</div>
		<DurationPickerEditor v-show="!show" :initial-unit="initialUnit" />
		<DurationPickerChronometer v-if="store.state.chronometer" v-show="show" />
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
				mode: 'Switch to chronometer',
				show: false,
				store: this.$parent.store
			}
		},
		props: {
			initialUnit: String,
			handleCloseModal: Function
		},
		methods: {
			toggleMode: function() {
				if (this.show) {
					this.mode = 'Switch to chronometer';
				} else {
					this.mode = 'Switch to editor';
				}
				this.show = !this.show;
			}
		}
	};
</script>