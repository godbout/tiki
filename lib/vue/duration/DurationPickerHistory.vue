<template>
	<div class="dp-history--container">
		<table class="table">
			<thead class="thead-light">
				<tr>
					<th scope="col">#</th>
					<th scope="col">Start time</th>
					<th scope="col">Stop time</th>
					<th scope="col">Spent Time</th>
				</tr>
			</thead>
			<tbody>
				<tr :class="{ active: store.state.activeTimestamp === index }" 
					v-for="(timestamp, index) in timestamps" :key="timestamp.id"
					v-on:click="selectTimestamp(index)">
					<th scope="row">
						<span>{{ index + 1 }}</span>
						<span v-if="index !== 0" class="dp-history--remove" v-on:click="deleteTimestamp($event, timestamp)" title="delete timestamp">
							<i class="fas fa-trash"></i>
						</span>
					</th>
					<td>
						<span v-if="!timestamp.startTime">--</span>
						<span v-if="timestamp.startTime">{{ formatTime(timestamp.startTime) }}</span>
					</td>
					<td>
						<span v-if="!timestamp.stopTime">--</span>
						<span v-if="timestamp.stopTime">{{ formatTime(timestamp.stopTime) }}</span>
					</td>
					<td>
						<span v-if="!timestamp.spentTime">--</span>
						<span v-if="timestamp.spentTime">{{ formatDuration(timestamp.spentTime) }}</span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
	export default {
		name: "DurationPickerHistory",
		data: function () {
			return {
				store: this.$parent.store,
				timestamps: this.$parent.store.state.timestamps
			};
		},
		methods: {
			deleteTimestamp: function ($event, timestamp) {
				$event.stopPropagation();
				if (this.store.state.playing) return;
				if (confirm(`Remove timestamp ${timestamp.spentTime.format('h [hrs], m [min], s [sec]')}?`)) {
					this.store.setTimestamp('delete', timestamp);
				}
			},
			formatTime: function (time) {
				return moment(time).format('YYYY-MM-DD HH:mm:ss');
			},
			formatDuration: function (duration) {
				return moment.duration(duration).format('h [hrs], m [min], s [sec]');
			},
			selectTimestamp: function (index) {
				if (this.store.state.playing) return;
				this.store.setActiveTimestamp(index);
			}
		}
	};
</script>