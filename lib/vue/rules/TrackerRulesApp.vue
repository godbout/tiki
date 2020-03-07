<template>
    <div class="tracker-rules-container">
      <trackerrules/>
    </div>
</template>

<script>
	import TrackerRules from "./vue_TrackerRules.js";

	export default {
		name: "TrackerRulesApp",
		components: {
			trackerrules: TrackerRules
		},
		data: function () { return {
			fileId: this.$parent.fieldId,
			fieldType: this.$parent.fieldType,
			rules: this.$parent.rules,
			targetFields: this.$parent.targetFields,
			definitiion: this.$parent.definitiion,
		}; }
	};
</script>
