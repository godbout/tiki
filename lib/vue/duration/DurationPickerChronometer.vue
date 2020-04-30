<template>
	<div class="dp-chronometer__container">
        <div class="dp-chronometer__group">
            <span class="dp-chronometer-btn unselectable" v-on:click="startTimer">start at:</span>
            <span class="dp-chronometer__info">{{ formatStartTime }}</span>
        </div>
        <div class="dp-chronometer__group">
            <span class="dp-chronometer-btn unselectable" v-on:click="stopTimer">stop at:</span>
            <span class="dp-chronometer__info">{{ formatStopTime }}</span>
        </div>
        <!-- <div class="dp-chronometer__group">
            <span>total time:</span>
            <span class="dp-chronometer__info">{{ formatedTotalTime }}</span>
        </div> -->
        <div class="dp-chronometer__group">
            <span class="dp-chronometer-btn unselectable" v-on:click="resetTimer">reset</span>
        </div>
	</div>
</template>

<script>
export default {
    name: "DurationPickerChronometer",
    data: function () {
        return {
            store: this.$parent.store,
            interval: false,
            startTime: '--',
            stopTime: '--',
            initialAmounts: this.$parent.store.state.duration.amounts,
            initialDurationMilliseconds: 0,
            initialTime: 0,
            totalTime: 0,
            formatedTotalTime: '--'
        };
    },
    beforeDestroy: function () {
        clearInterval(this.interval);
    },
    computed: {
        // calcSpentTime: function () {
        //     return moment.duration(this.initialTime + this.currentTime).format('h [hrs], m [min], s [sec]');
        // },
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
            if (!this.interval) {
                this.initialDurationMilliseconds = moment.duration(this.store.state.duration.amounts).asMilliseconds();
                // this.initialTime = this.totalTime;
                this.startTime = moment();

                this.interval = setInterval(() => {
                    let millisecondsDiff = moment().diff(this.startTime);
                    this.store.setDuration(this.initialDurationMilliseconds + millisecondsDiff);

                    // this.totalTime = this.initialTime + millisecondsDiff;
                }, 100);

                this.stopTime = '--';
            }
        },
        stopTimer: function () {
            clearInterval(this.interval);
            this.interval = false;
            
            this.stopTime = moment();

            // this.formatedTotalTime = moment.duration(this.totalTime).format('h [hrs], m [min], s [sec]');
        },
        resetTimer: function () {
            clearInterval(this.interval);
            this.interval = false;

            this.store.setDuration(this.initialAmounts);
            this.startTime = '--';
            this.stopTime = '--';
            // this.initialTime = 0;
            // this.totalTime = 0;
            // this.formatedTotalTime = '--';
        }
    }
};
</script>