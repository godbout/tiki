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
				<tr v-for="(timestamp, index) in timestamps" :key="timestamp.spentTime">
					<th scope="row">{{ index + 1 }}</th>
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
		props: {
			timestamps: Array,
		},
		methods: {
			formatTime: function (time) {
				return moment(time).format('YYYY-MM-DD HH:mm:ss');
			},
			formatDuration: function (duration) {
				return moment.duration(duration).format('h [hrs], m [min], s [sec]', 2);
			}
		}
	};
</script>