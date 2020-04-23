<template>
		<div class="duration-picker" v-click-outside="handleCloseModal">
				<template v-for="(dUnit) in store.state.duration.units">
						<div class="dp-amount--container" v-on:click="handleOpenModal(dUnit, $event)" :key="dUnit">
								<DurationPickerAmount :value="store.state.duration.amounts[dUnit]" :unit="dUnit"></DurationPickerAmount>
						</div>
				</template>
		<input type="hidden" :name="store.state.inputName" :value="getMilliseconds">
		<DurationPickerModal v-if="show" :initialUnit="unit" :handleCloseModal="handleCloseModal"></DurationPickerModal>
		</div>
</template>

<script>
import DurationPickerAmount from "./vue_DurationPickerAmount.js";
import DurationPickerModal from "./vue_DurationPickerModal.js";

export default {
	name: "DurationPicker",
	components: {
		durationpickeramount: DurationPickerAmount,
		durationpickermodal: DurationPickerModal
	},
	data: function () {
		return {
			show: false,
			unit: '',
			store: eval(this.$parent.store)
		}
	},
	computed: {
		getMilliseconds: function() {
			return this.store.state.duration.milliseconds;
		}
	},
	methods: {
		handleCloseModal: function () {
			this.show = false;
		},
		handleOpenModal: function (unit) {
			this.unit = unit;
			this.show = true;
		}
	},
	directives: {
		clickOutside: {
			bind: function (el, binding, vnode) {
				el.clickOutsideEvent = function (event) {
					// here I check that click was outside the el and his childrens
					if (!(el == event.target || el.contains(event.target))) {
						// and if it did, call method provided in attribute value
						vnode.context[binding.expression](event);
					}
				};
				document.body.addEventListener('click', el.clickOutsideEvent)
			},
			unbind: function (el) {
				document.body.removeEventListener('click', el.clickOutsideEvent)
			},
		}
	}
};
</script>