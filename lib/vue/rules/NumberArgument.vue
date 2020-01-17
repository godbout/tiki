<template>
	<div>
		<input type="number" @change="_onChange" :value="value">
	</div>
</template>

<script>
	export default {
		name: 'number-argument',
		methods: {
			_onChange({target: {value: newValue}})
			{
				this.$emit('change', newValue);
			},
		},
		props: {
			value: {
				type: null,
				required: true,
			},
		}
	}
</script>
