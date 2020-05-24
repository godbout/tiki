<template>
	<div class="dp-editor--container">
		<div class="dp-amount--editor__header">
			<DurationPickerTitle v-if="store.state.chronometer" />
			<DurationPickerAmounts :duration="store.state.duration" :amounts="getAmounts"></DurationPickerAmounts>
		</div>
		<div class="dp-amount--input__wrapper" v-on:keydown="handleTabKeys">
			<div class="dp-amount--input__left-section">
				<input
					ref="input"
					class="numeric"
					type="number"
					placeholder="__"
					min="-1"
					v-on:input="handleInput"
					v-on:keypress="handleKeypress"
					:value="convertValue"
				>
				<div class="dp-amount--input__label">{{ store.state.activeUnit }}</div>
				<div class="dp-amount--input__controls">
					<div class="dp-amount--input__btn unselectable" title="-5"
						v-on:mousedown="startArithmetic(-5)"
						v-on:mouseleave="stopArithmetic"
						v-on:mouseup="stopArithmetic"
					><i class="fas fa-angle-double-down"></i></div>
					<div class="dp-amount--input__btn unselectable" title="-1"
						v-on:mousedown="startArithmetic(-1)"
						v-on:mouseleave="stopArithmetic"
						v-on:mouseup="stopArithmetic"
					><i class="fas fa-angle-down"></i></div>
					<div class="dp-amount--input__btn unselectable" title="+1"
						v-on:mousedown="startArithmetic(1)"
						v-on:mouseleave="stopArithmetic"
						v-on:mouseup="stopArithmetic"
					><i class="fas fa-angle-up"></i></div>
					<div class="dp-amount--input__btn unselectable" title="+5"
						v-on:mousedown="startArithmetic(5)"
						v-on:mouseleave="stopArithmetic"
						v-on:mouseup="stopArithmetic"
					><i class="fas fa-angle-double-up"></i></div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import DurationPickerAmounts from "./vue_DurationPickerAmounts.js";
	import DurationPickerTitle from "./vue_DurationPickerTitle.js";

	export default {
		name: "DurationPickerEditor",
		components: {
			durationpickeramounts: DurationPickerAmounts,
			durationpickertitle: DurationPickerTitle
		},
		data: function () {
			return {
				currentValue: 0,
				interval: false,
				store: this.$parent.store
			}
		},
		props: {
			initialUnit: String,
		},
		mounted: function () {
			this.$nextTick(function () {
				this.$refs.input.focus();
			});
		},
		// updated: function () {
		// 	this.$nextTick(function () {
		// 		this.$refs.input.focus();
		// 	})
		// },
		beforeDestroy: function () {
			clearInterval(this.interval);
		},
		computed: {
			convertValue: function() {
				const amounts = this.store.__calcDuration(this.store.getTimestamp(this.store.state.activeTimestamp).spentTime);
				this.currentValue = amounts[this.store.state.activeUnit];
				return amounts[this.store.state.activeUnit];
			},
			getAmounts: function() {
				const index = this.store.state.activeTimestamp;
				const duration = this.store.state.timestamps[index].spentTime;
				const amounts = this.store.__calcDuration(duration);
				return amounts;
			}
		},
		methods: {
			handleKeypress: function (e) {
				if (e.target.value.length > 3) {
					// e.preventDefault();
				}
			},
			handleInput: function (e) {
				let value = parseInt(e.target.value, 10);
				if (isNaN(value)) {
					value = 0;
				}
				this.store.setDurationValue(value - this.currentValue, this.store.state.activeUnit);
			},
			handleClickUnit: function(unit) {
				this.$refs.input.focus();
				this.store.setActiveUnit(unit);
			},
			handleTabKeys: function(e) {
				if (e.shiftKey && e.which === 9) {
					e.preventDefault();
					this.prevAmount();
				} else if (e.which === 9) {
					e.preventDefault();
					this.nextAmount();
				}
			},
			nextAmount: function() {
				const unit = this.store.getAmountAfter(this.store.state.activeUnit);
				this.store.setActiveUnit(unit);
			},
			prevAmount: function() {
				const unit = this.store.getAmountBefore(this.store.state.activeUnit);
				this.store.setActiveUnit(unit);
			},
			handleArithmetic: function (step) {
				this.store.setDurationValue(step, this.store.state.activeUnit);
			},
			startArithmetic: function (step) {
				if (!this.interval) {
					this.handleArithmetic(step);
					this.interval = setInterval(this.handleArithmetic, 180, step);	
				}
			},
			stopArithmetic: function () {
				clearInterval(this.interval);
				this.interval = false;
				this.$refs.input.focus();
			}
		}
	};
</script>