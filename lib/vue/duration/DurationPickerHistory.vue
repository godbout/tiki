<template>
	<div class="dp-history--container">
		<table class="table table-striped">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col">Start time</th>
					<th scope="col">Stop time</th>
					<th scope="col">Spent Time</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(timestamp, index) in timestamps" :key="timestamp.id">
					<th scope="row">
						<span>{{ index + 1 }}</span>
						<span class="dp-history--remove" v-on:click="deleteTimestamp(timestamp)">
							<i class="fas fa-trash"></i>
						</span>
					</th>
					<td>{{ formatTime(timestamp.startTime) }}</td>
					<td>{{ formatTime(timestamp.stopTime) }}</td>
					<td>{{ formatDuration(timestamp.spentTime) }}</td>
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
			deleteTimestamp: function (timestamp) {
				if (confirm(`Remove timestamp ${moment.duration(timestamp.spentTime).format('h [hrs], m [min], s [sec]', 2)}?`)) {
					this.store.setTimestamp('delete', timestamp);
				}
			},
			formatTime: function (time) {
				return moment(time).format('YYYY-MM-DD HH:mm:ss');
			},
			formatDuration: function (duration) {
				return moment.duration(duration).format('h [hrs], m [min], s [sec]', 2);
			}
		}
	};
</script>