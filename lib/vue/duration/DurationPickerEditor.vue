<template>
    <div class="dp-amount--input__wrapper" v-on:keydown="handleTabKeys">
        <div class="dp-amount--input__left-section">
            <input
                ref="input"
                class="numeric"
                type="number"
                placeholder="__"
                min="-1"
                v-on:input="handleInput"
                v-on:keypress="handleKeypress"
                :value="convertValue"
            >
            <div class="dp-amount--input__label">{{ unit }}</div>
            <div class="dp-amount--input__controls">
                <div class="dp-amount--input__btn unselectable" 
                    v-on:mousedown="startSubtraction"
                    v-on:mouseleave="stopSubtraction"
                    v-on:mouseup="stopSubtraction"
                >-</div>
                <div class="dp-amount--input__btn unselectable"
                    v-on:mousedown="startAddition"
                    v-on:mouseleave="stopAddition"
                    v-on:mouseup="stopAddition"
                >+</div>
            </div>
        </div>
        <div class="dp-amount--input__right-section">
            <template v-for="(dUnit) in duration.units">
                <span 
                    class="dp-amount--input__unit unselectable"
                    :class="{ active: dUnit === unit }"
                    :key="dUnit"
                    v-on:click="handleClickUnit(dUnit)"
                >{{ dUnit }}</span>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "DurationPickerEditor",
    data: function () {
        return {
            duration: this.$parent.store.state.duration,
            value: this.$parent.store.state.duration.amounts[this.initialUnit],
            unit: this.initialUnit,
            interval: false,
            store: this.$parent.store
        }
    },
    props: {
        initialUnit: String,
    },
    mounted: function () {
        this.$nextTick(function () {
            this.$refs.input.focus();
        });
    },
    updated: function () {
        this.$nextTick(function () {
            this.$refs.input.focus();
        })
    },
    beforeDestroy: function () {
        clearInterval(this.interval);
    },
    computed: {
        convertValue: function() {
            return this.store.state.duration.amounts[this.unit];
        }
    },
    watch: {
        initialUnit: function(newVal) {
            this.value = this.duration.amounts[newVal];
            this.unit = newVal;
        }
    },
    methods: {
        handleKeypress: function (e) {
            if (e.target.value.length > 3) {
                // e.preventDefault();
            }
        },
        handleInput: function (e) {
            let value = parseInt(e.target.value, 10);
            if (isNaN(value)) {
                value = 0;
            }
            this.store.setDurationValue(value, this.unit);
        },
        handleClickUnit: function(unit) {
            this.$refs.input.focus();
            this.value = this.duration.amounts[unit];
            this.unit = unit;
        },
        handleTabKeys: function(e) {
            if (e.shiftKey && e.which === 9) {
                e.preventDefault();
                this.prevAmount();
            } else if (e.which === 9) {
                e.preventDefault();
                this.nextAmount();
            }
        },
        nextAmount: function() {
            const { unit } = this.store.getAmountAfter(this.unit);
            this.unit = unit;
        },
        prevAmount: function() {
            const { unit } = this.store.getAmountBefore(this.unit);
            this.unit = unit;
        },
        handleSubtraction: function () {
            let value = parseInt(this.$refs.input.value, 10);
            let newValue = value - 1;
            this.store.setDurationValue(newValue, this.unit);
        },
        handleAddition: function () {
            let value = parseInt(this.$refs.input.value, 10);
            let newValue = value + 1;
            this.store.setDurationValue(newValue, this.unit);
        },
        startSubtraction: function () {
            if (!this.interval) {
                this.handleSubtraction();
                this.interval = setInterval(this.handleSubtraction, 180);	
            }
        },
        stopSubtraction: function () {
            clearInterval(this.interval);
            this.interval = false;
            this.$refs.input.focus();
        },
        startAddition: function () {
            if (!this.interval) {
                this.handleAddition();
                this.interval = setInterval(this.handleAddition, 180);
            }
        },
        stopAddition: function () {
            clearInterval(this.interval);
            this.interval = false;
            this.$refs.input.focus();
        }
    }
};
</script>