<template>
</template>

<script>
	export default {
		name: 'null-argument',
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
