<template>
	<div class="dp-chronometer__container">
		<div class="dp-chronometer__group">
			<span class="dp-chronometer-btn unselectable" title="start">
				<i class="fas fa-play" v-show="show" v-on:click="startTimer"></i>
			</span>
			<span class="dp-chronometer-btn unselectable" title="pause">
				<i class="fas fa-pause" v-show="!show" v-on:click="stopTimer"></i>
			</span>
			<span class="dp-chronometer-btn unselectable" title="reset">
				<i class="fas fa-undo-alt" v-on:click="resetTimer"></i>
			</span>
		</div>
		<div class="dp-chronometer__group">
			<span>total time:</span>
			<span class="dp-chronometer__info">{{ calcSpentTime }}</span>
		</div>
		<DurationPickerHistory />
	</div>
</template>

<script>
	import DurationPickerHistory from "./vue_DurationPickerHistory.js";

	export default {
		name: "DurationPickerChronometer",
		components: {
			durationpickerhistory: DurationPickerHistory,
		},
		data: function () {
			return {
				store: this.$parent.store,
				startId: false,
				startTime: null,
				stopTime: null,
				initialAmounts: this.$parent.store.state.duration.value,
				intervalTime: null,
				initialDurationMilliseconds: 0,
				millisecondsDiff: 0,
				initialTime: 0,
				show: true,
				timestamps: this.$parent.store.state.timestamps
			};
		},
		beforeDestroy: function () {
			cancelAnimationFrame(this.startId);
		},
		computed: {
			calcSpentTime: function () {
				return moment.duration(this.$parent.store.state.totalTime).format('h [hrs], m [min], s [sec]', 2);
			}
		},
		methods: {
			startTimer: function () {
				if (this.startId) return;
				this.show = false;
				this.initialDurationMilliseconds = this.store.state.duration.value;
				this.initialTime = this.$parent.store.state.totalTime;
				this.startTime = moment();
				this.startId = requestAnimationFrame(this.startChronometer);
			},
			startChronometer: function() {
				this.intervalTime = moment();
				this.millisecondsDiff = this.intervalTime.diff(this.startTime);
				this.store.setDuration(this.initialDurationMilliseconds.clone().add(moment.duration(this.millisecondsDiff)));
				this.store.setTotalTime(this.initialTime.clone().add(moment.duration(this.millisecondsDiff)));
				this.startId = requestAnimationFrame(this.startChronometer);
			},
			stopTimer: function () {
				cancelAnimationFrame(this.startId);
				this.startId = false;
				this.show = true;
				this.stopTime = this.intervalTime;
				this.store.setTimestamp('add', {
					startTime: this.startTime,
					stopTime: this.stopTime,
					spentTime: moment(this.stopTime).diff(this.startTime)
				});
			},
			resetTimer: function () {
				if (this.timestamps.length > 0 && confirm("Remove all timestamps?")) {
					cancelAnimationFrame(this.startId);
					this.startId = false;
					this.show = true;

					this.store.setDuration(this.initialAmounts);
					this.store.setTimestamp('delete_all');
					this.initialTime = moment.duration(0);
					this.store.setTotalTime(moment.duration(0));
				}
			}
		}
	};
</script>