<template>
	<div class="dp-chronometer__container">
		<div class="dp-chronometer__group">
			<span class="dp-chronometer-btn unselectable" title="add time">
				<i class="fas fa-plus" v-on:click="addTimer"></i>
			</span>
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
		<DurationPickerHistory />
	</div>
</template>

<script>
	import DurationPickerHistory from "./vue_DurationPickerHistory.js";

	export default {
		name: "DurationPickerChronometer",
		components: {
			durationpickerhistory: DurationPickerHistory
		},
		data: function () {
			return {
				store: this.$parent.store,
				startId: false,
				startDuration: null,
				show: true,
				timestamp: null
			};
		},
		beforeDestroy: function () {
			cancelAnimationFrame(this.startId);
		},
		computed: {
			// calcSpentTime: function () {
			// 	return moment.duration(this.$parent.store.state.totalTime).format('h [hrs], m [min], s [sec]', 2);
			// },
		},
		methods: {
			startTimer: function () {
				if (this.startId) return;
				this.store.setPlaying(true);
				this.show = false;
				this.startTime = moment();
				this.timestamp = this.store.getTimestamp(this.store.state.activeTimestamp);

				this.startId = requestAnimationFrame(this.startChronometer);
			},
			startChronometer: function() {
				const momentCurrentTime = moment();
				const millisecondsDiff = momentCurrentTime.diff(this.startTime);
				this.store.setTimestamp('update', {
					...this.store.getTimestamp(this.store.state.activeTimestamp),
					startTime: this.startTime,
					stopTime: momentCurrentTime,
					spentTime: moment.duration(millisecondsDiff).add(this.timestamp.spentTime)
				});
				this.startId = requestAnimationFrame(this.startChronometer);
			},
			stopTimer: function () {
				cancelAnimationFrame(this.startId);
				this.startId = false;
				this.show = true;
				this.store.setPlaying(false);
			},
			resetTimer: function () {
				if (this.store.state.playing) return;
				if (confirm("Remove all timestamps?")) {
					cancelAnimationFrame(this.startId);
					this.startId = false;
					this.show = true;

					this.store.resetIntitialDuration();
				}
			},
			addTimer: function() {
				if (this.store.state.playing) return;
				this.store.setTimestamp('add', {
					spentTime: moment.duration(0)
				});
			}
		}
	};
</script>