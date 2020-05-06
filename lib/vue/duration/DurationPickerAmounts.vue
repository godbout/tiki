<template>
	<div>
		<div v-for="unit in duration.units" class="dp-amount--container" :key="unit">
			<div class="dp-amount--preview" :title="unit" v-on:click="$emit('unit', unit)">
				<span class="dp-amount--preview__value">{{ duration.amounts[unit] }}</span>
				<span class="dp-amount--preview__unit">{{ unit.charAt(0) }}</span>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: "DurationPickerAmounts",
		props: {
			duration: Object
		}
	};
</script>