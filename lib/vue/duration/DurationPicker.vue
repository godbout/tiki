<template>
    <div class="duration-picker" v-click-outside="handleCloseModal">
        <DurationPickerAmounts v-on:unit="loadAmount" :duration="store.state.duration"></DurationPickerAmounts>
        <DurationPickerModal v-show="show" :initial-unit="unit" :handle-close-modal="handleCloseModal"></DurationPickerModal>
        <input type="hidden" :name="store.state.inputName" :value="getValue">
    </div>
</template>

<script>
import DurationPickerAmounts from "./vue_DurationPickerAmounts.js";
import DurationPickerModal from "./vue_DurationPickerModal.js";

export default {
    name: "DurationPicker",
    components: {
        durationpickeramounts: DurationPickerAmounts,
        durationpickermodal: DurationPickerModal
    },
    data: function () {
        return {
            show: false,
            unit: '',
            store: window[this.$parent.store]
        }
    },
    computed: {
        getValue: function() {
            return JSON.stringify(this.store.state.duration.amounts);
        }
    },
    methods: {
        handleCloseModal: function () {
            this.show = false;
        },
        loadAmount: function (unit) {
            this.unit = unit;
            this.show = true;
        }
    },
    directives: {
        clickOutside: {
            bind: function (el, binding, vnode) {
                el.clickOutsideEvent = function (event) {
                    // here I check that click was outside the el and his childrens
                    if (!(el === event.target || el.contains(event.target))) {
                        // and if it did, call method provided in attribute value
                        vnode.context[binding.expression](event);
                    }
                };
                document.body.addEventListener('click', el.clickOutsideEvent)
            },
            unbind: function (el) {
                document.body.removeEventListener('click', el.clickOutsideEvent)
            },
        }
    }
};
</script>