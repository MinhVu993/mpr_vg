<template>
    <v-card class="elevation-3 my-4">
        <v-card-text>
            <strong>{{ $t("Signature flow:") }}</strong>
            <v-stepper flat>
                <v-stepper-header >
                    <template v-for="(flow, index) in appFlow">
                        <v-stepper-step color="orange" class="font-weight-regular" complete>
                            {{ flow.lvl_name[$i18n.locale] }} <br>
                            {{ flow.managers.map(m => m.name).join(', ') }}
                        </v-stepper-step>
                        <v-divider v-if="index < appFlow.length - 1" />
                    </template>
                </v-stepper-header>
            </v-stepper>
        </v-card-text>
    </v-card>
</template>
<script>
export default {
    props: {
        appCode: {
            type: String,
            required: true,
        },
        customDept: {
            type: String,
            default: ''
        },
        customDivision: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            appFlow: [],
            cachedEmpno: ''
        }
    },
    mounted() {
        this.getFlow()
    },
    methods: {
        async getFlow() {
            let params = {
                empno: this.$session.get('mpr').empno,
                app_code: this.appCode,
                custom_data: JSON.stringify({
                    custom_dept: this.customDept,
                    custom_division: this.customDivision,
                }),
            };
            await this.$axios
                .post("http://gmo021.cansportsvg.com/api/vfa/testFlow", params)
                .then((res) => {
                    this.appFlow = res.data.flow_data;
                    this.$emit('sendAppFlow', this.appFlow)
                })
                .catch((err) => {
                    console.log(err);
                });
        },
    }
}
</script>
<i18n>
    {
      "en": {
        "Signature flow:":"Signature flow:"
      },
      "cn": {
        "Signature flow:": "签名流程:"
      },
      "vi": {
        "Signature flow:": "Luồng chữ ký:"
      }
    }
  </i18n>
  <style>

</style>