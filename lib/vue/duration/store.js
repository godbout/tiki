var DurationPickerStore = function() {
	let lastPositiveFormated = null;
	let token = null;

	const __calcToken = function (units = ["years","months","weeks","days","hours","minutes","seconds","milliseconds"]) {
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
		const token = '{' + formatedUnits.join(',') + ',"[milliseconds]": S}';
		return token;
	};

	const __calcDuration = function (amounts) {
		const momentDuration = moment.duration(amounts);
		const formated = momentDuration
			.format(token, {
				useToLocaleString: false,
				groupingSeparator: "",
				usePlural: false,
				trim: false,
				trunc: true
			});
		const isNegative = momentDuration.asMilliseconds() < 0;
		let newAmounts = JSON.parse(formated);
		// Cache formated
		if (!isNegative) lastPositiveFormated = formated;
		// Replace negative format with last known positive
		if (isNegative) newAmounts = JSON.parse(lastPositiveFormated);

		return newAmounts;
	};

	return {
		state: {
			duration: {
				amounts: {"years":0,"months":0,"weeks":0,"days":0,"hours":0,"minutes":0,"seconds":0,"milliseconds":0},
				units: ["years","months","weeks","days","hours","minutes","seconds","milliseconds"]
			},
			inputName: 'duration'
		},
		setInputName(name) {
			this.state.inputName = name;
		},
		setInitialDuration(duration) {
			token = __calcToken(duration.units);
			this.state.duration.amounts = __calcDuration(duration.value);
			this.state.duration.units = duration.units;
		},
		setDuration(value) {
			this.state.duration.amounts = __calcDuration(value);
		},
		setDurationValue(value, unit) {
			const clonedAmounts = Object.assign(this.state.duration.amounts);
			clonedAmounts[unit] = value;
			this.state.duration.amounts = __calcDuration(clonedAmounts);
		},
		getAmountAfter(unit) {
			const index = this.state.duration.units.findIndex(el => el === unit);
			let nextIndex = index + 1;
			if (nextIndex === this.state.duration.units.length) {
				nextIndex = 0;
			}
			const nextUnit = this.state.duration.units[nextIndex];
			return {
				value: this.state.duration.amounts[nextUnit],
				unit: nextUnit
			};
		},
		getAmountBefore(unit) {
			const index = this.state.duration.units.findIndex(el => el === unit);
			let prevIndex = index - 1;
			if (prevIndex < 0) {
				prevIndex = this.state.duration.units.length - 1;
			}
			const prevUnit = this.state.duration.units[prevIndex];
			return {
				value: this.state.duration.amounts[prevUnit],
				unit: prevUnit
			};
		}
	};
};
