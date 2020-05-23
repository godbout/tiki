<template>
	<div class="dp-modal--backdrop">
		<div class="dp-modal--container">
			<div class="dp-amount--input__container">
				<div class="dp-amount--input__header">
					<span class="dp-amount--input__header--btn unselectable" :class="{ active: store.state.view === 'editor'}" v-on:click="gotoEditor">
						Duration picker
						<i class="fas fa-edit"></i>
					</span>
					<span class="dp-amount--input__header--btn unselectable" :class="{ active: store.state.view === 'chronometer'}" v-if="store.state.chronometer" v-on:click="gotoChronometer">
						Start/Stop
						<i class="fas fa-stopwatch"></i>
					</span>
					<div class="dp-amount--input__close" v-on:click="handleCloseModal" title="close">
						<i class="fas fa-times"></i>
					</div>
				</div>
				<DurationPickerEditor v-show="store.state.view === 'editor'" />
				<DurationPickerHistory v-if="store.state.chronometer" v-show="store.state.view === 'chronometer'" />
				<DurationPickerChronometer v-if="store.state.chronometer" />
			</div>
		</div>
	</div>
</template>

<script>
	import DurationPickerEditor from "./vue_DurationPickerEditor.js";
	import DurationPickerChronometer from "./vue_DurationPickerChronometer.js";
	import DurationPickerHistory from "./vue_DurationPickerHistory.js";

	export default {
		name: "DurationPickerModal",
		components: {
			durationpickerchronometer: DurationPickerChronometer,
			durationpickereditor: DurationPickerEditor,
			durationpickerhistory: DurationPickerHistory
		},
		data: function () {
			return {
				store: this.$parent.store,
			}
		},
		props: {
			handleCloseModal: Function
		},
		methods: {
			gotoEditor: function() {
				this.store.setView('editor')
			},
			gotoChronometer: function() {
				this.store.setView('chronometer')
			}
		}
	};
</script>