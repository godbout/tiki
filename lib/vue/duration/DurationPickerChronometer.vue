<template>
	<div class="dp-chronometer__container">
		<div class="dp-chronometer__group">
			<span class="dp-chronometer-btn unselectable">
				<i class="fas fa-play" v-show="show" v-on:click="startTimer"></i>
			</span>
			<span class="dp-chronometer-btn unselectable">
				<i class="fas fa-pause" v-show="!show" v-on:click="stopTimer"></i>
			</span>
			<span class="dp-chronometer-btn unselectable" v-on:click="resetTimer">
				<i class="fas fa-undo-alt"></i>
			</span>
		</div>
		<div class="dp-chronometer__group">
			<span class="unselectable">start at:</span>
			<span class="dp-chronometer__info">{{ formatStartTime }}</span>
		</div>
		<div class="dp-chronometer__group">
			<span class="unselectable">stop at:</span>
			<span class="dp-chronometer__info">{{ formatStopTime }}</span>
		</div>
		<div class="dp-chronometer__group">
			<span>total time:</span>
			<span class="dp-chronometer__info">{{ calcSpentTime }}</span>
		</div>
	</div>
</template>

<script>
	export default {
		name: "DurationPickerChronometer",
		data: function () {
			return {
				store: this.$parent.store,
				startId: false,
				startTime: '--',
				stopTime: '--',
				initialAmounts: this.$parent.store.state.duration.amounts,
				intervalTime: null,
				initialDurationMilliseconds: 0,
				millisecondsDiff: 0,
				initialTime: 0,
				totalTime: 0,
				show: true,
				timestamps: []
			};
		},
		beforeDestroy: function () {
			cancelAnimationFrame(this.startId);
		},
		computed: {
			calcSpentTime: function () {
				return moment.duration(this.totalTime).format('h [hrs], m [min], s [sec]', 2);
			},
			formatStartTime: function () {
				if (this.startTime !== '--') {
					return moment(this.startTime).format('YYYY-MM-DD HH:mm:ss');
				} else {
					return this.startTime;
				}
			},
			formatStopTime: function () {
				if (this.stopTime !== '--') {
					return moment(this.stopTime).format('YYYY-MM-DD HH:mm:ss');
				} else {
					return this.stopTime;
				}
			}
		},
		methods: {
			startTimer: function () {
				if (!this.startId) {
					this.stopTime = '--';
					this.show = false;
					this.initialDurationMilliseconds = moment.duration(this.store.state.duration.amounts).asMilliseconds();
					this.initialTime = this.totalTime;
					this.startTime = moment();
					this.startId = requestAnimationFrame(this.startChronometer);
				}
			},
			startChronometer: function() {
				this.intervalTime = moment();
				this.millisecondsDiff = this.intervalTime.diff(this.startTime);
				this.store.setDuration(this.initialDurationMilliseconds + this.millisecondsDiff);
				this.totalTime = this.initialTime + this.millisecondsDiff;
				this.startId = requestAnimationFrame(this.startChronometer);
			},
			stopTimer: function () {
				cancelAnimationFrame(this.startId);
				this.startId = false;
				this.show = true;
				this.stopTime = this.intervalTime;
				this.recordTimestamp();
			},
			resetTimer: function () {
				cancelAnimationFrame(this.startId);
				this.startId = false;
				this.show = true;

				this.store.setDuration(this.initialAmounts);
				this.clearTimestamps();
				this.startTime = '--';
				this.stopTime = '--';
				this.initialTime = 0;
				this.totalTime = 0;
			},
			recordTimestamp: function () {
				this.timestamps.push({
					start: this.startTime,
					stop: this.stopTime,
					total: moment(this.stopTime).diff(this.startTime)
				});
			},
			clearTimestamps: function () {
				this.timestamps = [];
			}
		}
	};
</script>