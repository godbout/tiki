<template>
	<div class="dp-amount--input__container">
		<div class="dp-amount--input__header">
			<!-- <div class="dp-toggle-mode unselectable" v-if="store.state.chronometer" v-on:click="toggleMode">{{ mode }}</div> -->
			<div class="dp-amount--input__close" v-on:click="handleCloseModal" title="close">x</div>
		</div>
		<div v-if="store.state.chronometer">
			<span><strong>Time entry {{store.state.activeTimestamp + 1}}:</strong></span>
			<DurationPickerAmounts :duration="store.state.duration" :amounts="getAmounts"></DurationPickerAmounts>
		</div>
		<DurationPickerEditor />
		<DurationPickerChronometer v-if="store.state.chronometer" />
		<!-- <DurationPickerEditor v-show="!show" :initial-unit="initialUnit" />
		<DurationPickerChronometer v-if="store.state.chronometer" v-show="show" /> -->
	</div>
</template>

<script>
	import DurationPickerEditor from "./vue_DurationPickerEditor.js";
	import DurationPickerChronometer from "./vue_DurationPickerChronometer.js";
	import DurationPickerAmounts from "./vue_DurationPickerAmounts.js";

	export default {
		name: "DurationPickerModal",
		components: {
			durationpickerchronometer: DurationPickerChronometer,
			durationpickereditor: DurationPickerEditor,
			durationpickeramounts: DurationPickerAmounts
		},
		data: function () {
			return {
				// mode: 'Switch to chronometer',
				show: false,
				store: this.$parent.store,
			}
		},
		props: {
			handleCloseModal: Function
		},
		computed: {
			getAmounts: function() {
				const index = this.store.state.activeTimestamp;
				const duration = this.store.state.timestamps[index].spentTime;
				const amounts = this.store.__calcDuration(duration);
				return amounts;
			}
		},
		methods: {
			toggleMode: function() {
				// if (this.show) {
				// 	this.mode = 'Switch to chronometer';
				// } else {
				// 	this.mode = 'Switch to editor';
				// }
				this.show = !this.show;
			}
		}
	};
</script>