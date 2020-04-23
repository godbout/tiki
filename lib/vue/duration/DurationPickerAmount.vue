<template>
	<div class="dp-amount--preview" :title="unit">
		<span class="dp-amount--preview__value">{{ value }}</span>
		<span class="dp-amount--preview__unit">{{ unit.charAt(0) }}</span>
	</div>
</template>

<script>
export default {
	name: "DurationPickerAmount",
	props: {
		value: Number,
		unit: String
	}
};
</script>