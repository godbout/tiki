<template>
	<div class="duration-picker">
		<DurationPickerAmounts v-on:unit="loadAmount" :duration="store.state.duration" :amounts="amounts"></DurationPickerAmounts>
		<DurationPickerModal v-show="show" :initial-unit="unit" :handle-close-modal="handleCloseModal"></DurationPickerModal>
		<input type="hidden" :name="store.state.inputName" :value="getValue">
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
				unit: '',
				store: window[this.$parent.store],
				amounts: {}
			}
		},
		computed: {
			getValue: function() {
				let amounts = this.store.__calcDuration(this.store.state.duration.value);
				this.amounts = amounts;
				return JSON.stringify(amounts);
			}
		},
		methods: {
			handleCloseModal: function () {
				this.show = false;
			},
			loadAmount: function (unit) {
				this.unit = unit;
				this.show = true;
			}
		}
	};
</script>