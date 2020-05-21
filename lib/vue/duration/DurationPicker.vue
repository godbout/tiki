<template>
	<div class="duration-picker">
		<div v-on:click="showModal">
			<DurationPickerAmounts :duration="store.state.duration" :amounts="getTotalAmounts"></DurationPickerAmounts>
		</div>
		<transition name="fade">
			<DurationPickerModal v-show="show" :handle-close-modal="handleCloseModal"></DurationPickerModal>
		</transition>
		<input type="hidden" :name="store.state.inputName" :value="getAmountsTotalStringified">
	</div>
</template>

<script>
	import DurationPickerAmounts from "./vue_DurationPickerAmounts.js";
	import DurationPickerModal from "./vue_DurationPickerModal.js";

	export default {
		name: "DurationPicker",
		components: {
			durationpickeramounts: DurationPickerAmounts,
			durationpickermodal: DurationPickerModal
		},
		data: function () {
			return {
				show: false,
				store: window[this.$parent.store],
				amounts: {}
			}
		},
		computed: {
			getTotalAmounts: function() {
				const totalDuration = this.store.getTotalDuration();
				const amounts = this.store.__calcDuration(totalDuration);
				return amounts;
			},
			getAmountsTotalStringified: function() {
				const totalDuration = this.store.getTotalDuration();
				const amounts = this.store.__calcDuration(totalDuration);
				return JSON.stringify(amounts);
			}
		},
		methods: {
			handleCloseModal: function () {
				this.show = false;
			},
			showModal: function () {
				this.show = true;
			}
		}
	};
</script>