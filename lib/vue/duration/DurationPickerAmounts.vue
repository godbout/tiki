<template>
	<div>
		<div v-for="unit in duration.units" class="dp-amount--container" :key="unit">
			<div class="dp-amount--preview" :class="{ active: store.state.activeUnit === unit  }" :title="unit" v-on:click="setUnit(unit)">
				<span class="dp-amount--preview__value">{{ amounts[unit] }}</span>
				<span class="dp-amount--preview__unit">{{ unit.charAt(0) }}</span>
			</div>
		</div>
		<span>
			<i class="far fa-clock fa-spin" v-if="store.state.playing"></i>
		</span>
	</div>
</template>

<script>
	export default {
		name: "DurationPickerAmounts",
		data: function () {
			return {
				store: this.$parent.store,
			}
		},
		props: {
			duration: Object,
			amounts: Object
		},
		methods: {
			setUnit: function (unit) {
				this.store.setActiveUnit(unit);
			}
		}
	};
</script>