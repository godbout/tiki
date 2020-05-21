<template>
	<div class="dp-history--container">
		<table class="table table-sm table-borderless">
			<thead class="thead-light">
				<tr>
					<th scope="col">#</th>
					<th scope="col">Start time</th>
					<th scope="col">Stop time</th>
					<th scope="col">Spent Time</th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<tr :class="{ active: store.state.activeTimestamp === index }" 
					v-for="(timestamp, index) in timestamps" :key="timestamp.id"
					v-on:click="selectTimestamp(index)">
					<th scope="row">
						<span>{{ index + 1 }}</span>
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
					<td>
						<span v-show="store.state.activeTimestamp === index && store.state.playing">
							<i class="far fa-clock fa-spin"></i>
						</span>
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