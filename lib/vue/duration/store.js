var DurationPickerStore = function() {
	return {
		state: {
			chronometer: 0, // 0 or 1
			initialDuration: {
				value: {}, // Duration object
				units: ["years","months","weeks","days","hours","minutes","seconds","milliseconds"]
			},
			duration: {
				value: {}, // Duration object
				units: ["years","months","weeks","days","hours","minutes","seconds","milliseconds"]
			},
			token: null,
			inputName: '',
			timestamps: [],
			activeTimestamp: 0, // Array index
			activeUnit: 'hours',
			playing: false,
			view: 'editor' // 'editor' or 'chronometer'
		},
		setView(name) {
			this.state.view = name;
		},
		setPlaying(play) {
			this.state.playing = play;
		},
		setActiveUnit(unit) {
			this.state.activeUnit = unit;
		},
		setActiveTimestamp(index) {
			const length = this.state.timestamps.length;
			if (this.state.playing) return;
			if (index < 0 || index >= length) return;
			this.state.activeTimestamp = index;
		},
		setInputName(name) {
			this.state.inputName = name;
		},
		setInitialDuration(duration) {
			this.state.token = this.__calcToken(duration.units);
			this.state.duration.value = moment.duration(duration.value);
			this.state.duration.units = duration.units;
			this.state.chronometer = duration.chronometer;

			this.state.initialDuration.value = moment.duration(duration.value);
			this.state.initialDuration.units = duration.units;
			this.createTimestamp({
				spentTime: moment.duration(duration.value)
			});
		},
		resetIntitialDuration() {
			this.setActiveTimestamp(0);
			this.deleteAllTimestamp();
			this.state.duration.value = moment.duration(this.state.initialDuration.value);
			this.createTimestamp({
				spentTime: moment.duration(this.state.initialDuration.value)
			});
		},
		setDuration(value) {
			this.state.duration.value = value;
		},
		setDurationValue(value, unit) {
			const index = this.state.activeTimestamp;
			const duration = this.state.timestamps[index].spentTime.clone().add(value, unit);
			if (duration.asMilliseconds() >= 0) {
				this.state.timestamps[index].spentTime.add(value, unit);
			}
		},
		setTimestamp(action, timestamp) {
			if (action === 'add') {
				this.createTimestamp(timestamp);
				this.setActiveTimestamp(this.state.timestamps.length - 1);
			} else if (action === 'update') {
				this.updateTimestamp(timestamp);
			} else if (action === 'delete') {
				let index = this.state.timestamps.findIndex(el => el.id === timestamp.id);
				let spentTime = this.state.timestamps[index].spentTime;
				this.setDuration(this.state.duration.value.clone().subtract(moment.duration(spentTime)));

				this.setActiveTimestamp(0);
				this.deleteTimestamp(timestamp.id);
			} else if (action === 'delete_all') {
				this.deleteAllTimestamp();
			}
		},
		getAmountAfter(unit) {
			const index = this.state.duration.units.findIndex(el => el === unit);
			let nextIndex = index + 1;
			if (nextIndex === this.state.duration.units.length) {
				nextIndex = 0;
			}
			const nextUnit = this.state.duration.units[nextIndex];
			return nextUnit;
		},
		getAmountBefore(unit) {
			const index = this.state.duration.units.findIndex(el => el === unit);
			let prevIndex = index - 1;
			if (prevIndex < 0) {
				prevIndex = this.state.duration.units.length - 1;
			}
			const prevUnit = this.state.duration.units[prevIndex];
			return prevUnit;
		},
		getTotalDuration() {
			return this.state.timestamps.reduce((acc, currVal) => {
				return acc.clone().add(currVal.spentTime)
			}, moment.duration(0));
		},
		getTimestamp(index) {
			return this.state.timestamps[index];
		},
		getLastTimestamp() {
			return this.state.timestamps[this.state.timestamps.length - 1];
		},
		createTimestamp(timestamp) {
			// TO DO better id creation
			const newTimestamp = Object.assign(timestamp, {id: moment().valueOf()});
			this.state.timestamps.push(newTimestamp);
		},
		updateTimestamp(timestamp) {
			const index = this.state.timestamps.findIndex(el => el.id === timestamp.id);
			this.state.timestamps.splice(index, 1, timestamp);
		},
		deleteTimestamp(id) {
			const index = this.state.timestamps.findIndex(el => el.id === id);
			this.state.timestamps.splice(index, 1);
		},
		deleteAllTimestamp() {
			this.state.timestamps.splice(0, this.state.timestamps.length);
		},
		__calcToken(units = ["years","months","weeks","days","hours","minutes","seconds","milliseconds"]) {
			const durationLabels = {
				'milliseconds': 'S',
				'seconds': 's',
				'minutes': 'm',
				'hours': 'h',
				'days': 'd',
				'weeks': 'w',
				'months': 'M',
				'years': 'y'
			};
			const formatedUnits = units.map(unit => `"[${unit}]": ${durationLabels[unit]}`);
			const token = '{' + formatedUnits.join(',') + '}';
			return token;
		},
		__calcDuration(duration) {
			const amounts = duration
				.format(this.state.token, {
					useToLocaleString: false,
					groupingSeparator: "",
					usePlural: false,
					trim: false,
					trunc: true
				});
			return JSON.parse(amounts);
		}
	};
};
