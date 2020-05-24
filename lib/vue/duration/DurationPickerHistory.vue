<template>
	<div class="dp-history--container">
		<table class="table table-sm table-borderless">
			<thead class="thead-light">
				<tr>
					<th scope="col">#</th>
					<th scope="col" style="width: 68%">Title, Description</th>
					<th scope="col" style="width: 25%">Spent Time</th>
					<th scope="col" style="width: 8%"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(timestamp, index) in timestamps" :key="timestamp.id"
					:class="{ active: store.state.activeTimestamp === index }"
					v-on:click="selectTimestamp(index)">
					<th scope="row">
						<span>{{ index + 1 }}</span>
					</th>
					<td>
						<div style="font-weight: 500">{{ timestamp.title }}</div>
						<p style="color: #495057;">{{ timestamp.description }}</p>
						<div v-if="timestamp.startTime">Last start time: <span class="font-italic">{{ formatTime(timestamp.startTime) }}<span></div>
						<div v-if="timestamp.stopTime">Last stop time: <span class="font-italic">{{ formatTime(timestamp.stopTime) }}<span></div>
					</td>
					<td>
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
				return moment(time).format('MMM Do, HH:mm:ss');
			},
			formatDuration: function (duration) {
				return moment.duration(duration).format('h [hrs], m [min], s [sec]');
			},
			selectTimestamp: function (index) {
				this.store.setActiveTimestamp(index);
			}
		}
	};
</script>