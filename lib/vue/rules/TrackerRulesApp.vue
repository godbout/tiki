<template>
    <div class="container mx-auto p-4">
      <h3>Field Behaviours</h3>
      <trackerrules/>
    </div>
</template>

<script>
	import TrackerRules from "./vue_TrackerRules.js";

	export default {
		name: "TrackerRulesApp",
		components: {
			trackerrules: TrackerRules
		}
	};
</script>
