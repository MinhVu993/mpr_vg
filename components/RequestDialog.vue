<template>
    <v-dialog v-model="dialog" fullscreen persistent hide-overlay>
        <v-card class="request-page">
            <v-toolbar dark color="#01a6be">
                <v-btn color="warning" small class="mr-3" dark @click="onCloseDialog">
                    <v-icon>mdi-arrow-left-bold</v-icon>
                </v-btn>
                <v-toolbar-title>{{ dialogTitle }}</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn @click="setLocale('vi')" text>VI</v-btn>
                    <v-btn @click="setLocale('en')" text>EN</v-btn>
                    <v-btn @click="setLocale('cn')" text class="mr-5">中文</v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <VfaAppFlow v-if="flowEmpno" v-show="true" ref="AppFlow" :locale="$i18n.locale" :key="flowKey"
            :appCode="getAppCode" :empno="flowEmpno" :location="flowLocation" @sendAppFlow="handleAppFlow"
            class="mb-2"></VfaAppFlow>
            <v-app-bar v-if="showFlowSign" class="flow-sign-container mb-3" flat dense>
                <v-icon color="info" class="mr-2">mdi-information-outline</v-icon>
                <div class="flow-sign-text text-subtitle-2">
                    {{ getCurrentFlowSign }}
                </div>
            </v-app-bar>
            <v-row>
                <v-col cols="12" md="2">
                    <v-autocomplete class="ml-6 mb-3" :key="autocompleteKey" v-if="isChange" v-model="reqId"
                    :items="item_req_id" :label="$t('Req ID')" outlined dense :rules="[rules.required]" required
                    hide-details @change="getDataByReqId" item-text="text" item-value="id"
                    background-color="#fff9c4"></v-autocomplete>
                </v-col>
            </v-row>
            <v-card-text v-if="showNewYearSection" style="background-color: #fff9c4;" class="mb-4">
                <v-row class="new-year-section">
                    <v-col cols="12">
                        <div class="d-flex align-center mb-2">
                            <v-icon color="primary" size="32" class="mr-3">mdi-calendar-star</v-icon>
                            <div class="text-h6 font-weight-bold">
                                {{ $t("New Year Recruitment") }}
                            </div>
                        </div>
                        
                        <div class="ml-11">
                            <v-radio-group v-model="form.newYearRecruitment" column hide-details :disabled="disableNewYearRecruitment" >
                                <v-radio :label="$t('Continue Recruitment')" :value=1 class="mb-3 custom-radio" color="red"></v-radio>
                                <v-radio :label="$t('Cancel Recruitment')" :value=0 class="custom-radio" color="red"></v-radio>
                            </v-radio-group>
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-text class="approval-history-section" v-if="showAmendmentSection">
                <v-row style="background-color: #fff9c4;">
                    <v-col cols="12" md="4">
                        <v-combobox :disabled="disAmendent" v-model="form.changeReason" :items="formattedChangeReasons"
                        item-text="text" item-value="id" return-object :label="$t('Change Reason')" outlined dense
                        hide-details clearable></v-combobox>
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-if="showChangeQuantityField" :disabled="disAmendent" v-model="form.changeQty"
                        :label="$t('Change quantity') + ` (${$t('Min')}: ${editData?.recruited || 0}, ${$t('Max')}: ${form.quantity || 0})`"
                        type="number" :min="editData?.recruited || 0" :max="form.quantity || 0" outlined dense
                        :rules="[minQuantityRule, maxQuantityRule]" :error-messages="changeQtyError" persistent-hint
                        hide-details="auto" :hint="getChangeQuantityHint"></v-text-field>
                    </v-col>
                    <v-col cols="12" class="equal-height" v-show="showLanguageAndComputerSkills">
                        <div class="mb-4">
                            <div class="d-flex align-center mb-2">
                                <v-icon left color="primary">mdi-translate</v-icon>
                                <div class="text-subtitle-1 font-weight-medium">
                                    {{ $t("Foreign Language Proficiency") }}
                                </div>
                            </div>
                            <v-row class="ml-1">
                                <v-col cols="12" md="4">
                                    <v-radio-group v-model="form.either_change" row dense hide-details>
                                        <div class="subtitle-2">{{ $t("Either:") }}</div>
                                        <v-radio :disabled="disAmendent"
                                        v-for="(level, index) in getLanguageLevels('either')"
                                        :key="'either-' + index" :value="level.value"
                                        :label="getLocalizedLabel(level)" hide-details class="mr-4"></v-radio>
                                    </v-radio-group>
                                </v-col>
                                
                                <v-col cols="12" md="4">
                                    <v-radio-group v-model="form.english_change" row hide-details dense>
                                        <div class="subtitle-2">{{ $t("English:") }}</div>
                                        <v-radio :disabled="disAmendent"
                                        v-for="(level, index) in getLanguageLevels('en')" :key="'english-' + index"
                                        :value="level.value" :label="getLocalizedLabel(level)" hide-details
                                        class="mr-4"></v-radio>
                                    </v-radio-group>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-radio-group v-model="form.chinese_change" row hide-details dense>
                                        <div class="subtitle-2">{{ $t("Chinese:") }}</div>
                                        <v-radio :disabled="disAmendent"
                                        v-for="(level, index) in getLanguageLevels('cn')" :key="'chinese-' + index"
                                        :value="level.value" :label="getLocalizedLabel(level)" hide-details
                                        class="mr-4"></v-radio>
                                    </v-radio-group>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-checkbox :disabled="disAmendent" v-model="form.tranlator_change"
                                    :label="$t('Translator')" hide-details class="mr-4"></v-checkbox>
                                </v-col>
                            </v-row>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex align-center mb-2">
                                <v-icon left color="primary">mdi-desktop-classic</v-icon>
                                <div class="text-subtitle-1 font-weight-medium">
                                    {{ $t("Computer Skills") }}
                                </div>
                            </div>
                            
                            <v-row class="ml-2">
                                <v-col cols="12" class="d-flex flex-wrap py-0">
                                    <v-checkbox :disabled="disAmendent"
                                    v-for="skill in ['Word', 'Excel', 'Access', 'PowerPoint', 'Internet', 'None', 'Other']"
                                    :key="skill" v-model="form.computerSkills_change" :value="skill"
                                    :label="$t(skill)" dense hide-details class="mr-4 mb-1 mt-2"></v-checkbox>
                                </v-col>
                                <v-textarea :disabled="disAmendent" v-if="form.computerSkills_change.includes('Other')"
                                v-model="form.otherComputerSkills_change" :label="$t('Other Skills')" dense outlined
                                class="mt-5" rows="1" hide-details auto-grow></v-textarea>
                            </v-row>
                        </div>
                    </v-col>
                    <v-col cols="12">
                        <v-textarea :disabled="disAmendent" v-model="form.remark_change" outlined
                        :label="$t('Change remark')" rows="1" hide-details auto-grow></v-textarea>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-text>
                <v-row>
                    <v-col cols="12" md="2">
                        <v-text-field disabled :value="displayReqId" :label="$t('Req ID')" outlined dense
                        hide-details></v-text-field>
                    </v-col>
                    <v-col cols="1">
                        <v-autocomplete :disabled="formDisabled" v-model="user.div" :items="itemsDiv"
                        :label="$t('Division')" outlined :required="true" :rules="[rules.required]" dense
                        hide-details></v-autocomplete>
                    </v-col>
                    <v-col cols="2">
                        <v-autocomplete :disabled="formDisabled" v-model="user.dept" :items="itemsDept"
                        :label="$t('Dept')" outlined dense :rules="[rules.required]" required hide-details
                        clearable></v-autocomplete>
                    </v-col>
                    <v-col cols="1" md="2">
                        <v-autocomplete :disabled="formDisabled" v-model="user.unit" :items="filteredUnitItems"
                        item-text="display" item-value="dept_code" :label="$t('Unit')" outlined dense
                        :rules="[rules.required]" required hide-details clearable
                        :no-data-text="!user.div || !user.dept ? $t('Please select Division and Department first') : $t('No data available')"></v-autocomplete>
                    </v-col>
                    
                    <v-col cols="12" md="2">
                        <v-autocomplete :disabled="formDisabled" v-model="form.suppType" :items="suppTypeItems"
                        :label="$t('Supp.Type')" :rules="[rules.required]" required item-text="name" item-value="id"
                        outlined dense hide-details clearable></v-autocomplete>
                    </v-col>
                    <v-col cols="12" md="1">
                        <v-autocomplete :disabled="formDisabled" v-model="form.pos" :items="posItems"
                        :label="$t('Type Apply')" item-text="name" :rules="[rules.required]" required
                        item-value="id" outlined dense hide-details clearable
                        @change="handleTypeApplyChange"></v-autocomplete>
                    </v-col>
                    <v-col cols="12" md="1" v-if="isHrManager && ![1, 3].includes(Number(form.pos))">
                        <v-autocomplete background-color="#fff9c4" color="#000000" v-model="form.sub_position"
                        :items="sub_positionItems" item-text="name" :disabled="!!editData?.sub_pos" item-value="id"
                        :label="$t('Sub Position')" outlined dense hide-details clearable></v-autocomplete>
                    </v-col>
                    <v-col cols="2" md="1" v-if="showLevelField">
                        <v-text-field :disabled="formDisabled || !showLevelField" v-model="form.level"
                        :label="$t('Level')" outlined dense hide-details></v-text-field>
                    </v-col>
                    <v-col cols="1" md="1">
                        <v-text-field :disabled="formDisabled" v-model="form.quantity" :label="$t('Quantity request')"
                        type="number" min="1" :rules="[rules.required]" outlined dense hide-details="auto" required
                        @input="(val) => (form.quantity = val > 0 ? val : '')"></v-text-field>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="2" md="2">
                        <v-menu v-model="dateRequestMenu" :close-on-content-click="false" transition="scale-transition"
                        offset-y>
                        <template v-slot:activator="{ on, attrs }">
                            <v-text-field :disabled="formDisabled" v-model="form.recruitment.requestDate"
                            :label="$t('Req.date')" readonly outlined dense v-bind="attrs" v-on="on"
                            hide-details prepend-inner-icon="mdi-calendar"></v-text-field>
                        </template>
                        <v-date-picker v-model="form.recruitment.requestDate" @input="dateRequestMenu = false"
                        :min="getEffectiveDate()"></v-date-picker>
                    </v-menu>
                </v-col>
                <v-col cols="4" md="6">
                    <v-card outlined :class="{ 'error--text': formErrors.recruitment }"
                    class="pa-2 custom-card-hover">
                    <v-row dense>
                        <v-col cols="12" class="d-flex flex-wrap align-center">
                            <span class="font-weight-medium">{{ $t("Reason") }}</span>
                            <v-radio-group :disabled="formDisabled" v-model="form.recruitment.reason" row dense
                            hide-details :rules="[reasonSelectionRule]">
                            <v-radio v-for="reason in dataRecruitmentReason" :key="reason.id"
                            :value="reason.id" :label="getReasonLabel(reason)" dense class="mr-2"
                            @change="handleReasonChange(reason.id)">
                        </v-radio>
                    </v-radio-group>
                    <v-autocomplete class="ml-1"
                    :disabled="formDisabled || form.recruitment.reason !== 3"
                    v-model="form.recruitment.otherReasonSelected" :items="otherReasonItems"
                    item-text="text" item-value="id" :label="$t('Please select')" outlined dense
                    hide-details clearable @change="handleOtherReasonSelect"></v-autocomplete>
                </v-col>
                <v-col cols="12" v-if="form.recruitment.reason === 2"> <!-- For2-->
                    <v-text-field :rules="[rules.required]" required :disabled="formDisabled"
                    v-model="form.recruitment.oldempno" :label="$t('Old empno')" outlined dense
                    hide-details></v-text-field>
                </v-col>
                
            </v-row>
        </v-card>
    </v-col>
    <v-col cols="12" md="4">
        <v-file-input :disabled="formDisabled" v-model="form.attachments.dimissionList"
        :label="$t('Dimission list file')" outlined dense
        :error="form.recruitment.reason === 2 && !form.attachments.dimissionList"
        :required="form.recruitment.reason === 2"
        :rules="form.recruitment.reason === 2 ? [rules.required] : []" hide-details="auto"
        @change="previewFile('dimissionList', $event)" prepend-inner-icon="mdi-file-account"
        :clearable="true" accept=".doc,.docx,.pdf,.xls,.xlsx"></v-file-input>
        <div v-show="filePreview.dimissionList && form.recruitment.reason === 2"
        class="file-preview mt-2">
        <v-chip small  class="mb-1">
            <v-icon left small>{{ getFileIcon(form.attachments.dimissionList?.type ||
                getFileTypeFromName(form.attachments.dimissionList) ) }}</v-icon>
                {{ getFileName("dimissionList") }}
            </v-chip>
            <div class="d-flex align-center justify-space-between">
                <span class="caption grey--text">{{
                    form.attachments.dimissionList_size || ""
                }}</span>
                <v-btn x-small text color="primary" @click="openFile('dimissionList')"> <v-icon small
                    left>mdi-eye</v-icon>
                    {{ $t("View") }} </v-btn>
                    <v-btn :disabled="formDisabled" x-small text color="error"
                    @click="clearFile('dimissionList')"> <v-icon small left>mdi-delete</v-icon> {{
                        $t("Delete") }} </v-btn>
                    </div>
                </div>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete :disabled="formDisabled" v-model="form.educationLevel"
                :items="educationLevelItems" :rules="[rules.required]" required
                :label="$t('Education Level')" item-text="name" item-value="id" outlined dense hide-details
                clearable></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="6" md="8">
                <v-textarea :disabled="formDisabled" auto-grow v-model="localizedSpecialization"
                :rules="[rules.required]" required :placeholder="$t('Professional qualifications')"
                :label="$t('Professional qualifications')" outlined dense rows="1" hide-details
                @input="handleSpecializationInput"></v-textarea>
            </v-col>
            <v-col cols="12" sm="6" md="2">
                <v-autocomplete :disabled="formDisabled" v-model="form.workExperience" :items="workExpItems"
                :rules="[rules.required]" required :label="$t('Work Experience')" item-text="name"
                item-value="id" outlined dense hide-details clearable></v-autocomplete>
            </v-col>
        </v-row>
        <div class="mt-2">
            <v-row>
                <v-col cols="12" :md="getJobDescColWidth">
                    <v-textarea :disabled="formDisabled" auto-grow v-model="form.jobDesc" outlined
                    :label="$t('Basic Job Description')" :rules="[rules.required]" required rows="1"
                    hide-details></v-textarea>
                </v-col>
                <v-col cols="12" md="2" v-if="form.pos !== 1">
                    <v-file-input :disabled="formDisabled" v-model="form.attachments.jobDesc"
                    :label="$t('Job description file')" outlined dense :error="form.pos === 1"
                    :error-messages="form.pos === 1 ? $t('Job description file is required') : ''" :required="form.pos === 1"
                    hide-details @change="previewFile('jobDesc', $event)" v-show="form.pos !== 1"
                    :clearable="true" accept=".doc,.docx,.pdf"></v-file-input>
                    <div v-if="filePreview.jobDesc" class="file-preview mt-2">
                        <v-chip small  class="mb-1">
                            <v-icon left small>{{ getFileIcon(form.attachments.jobDesc?.type ||
                                getFileTypeFromName(form.attachments.jobDesc) ) }}</v-icon>
                                {{ getFileName("jobDesc") }}
                            </v-chip>
                            <div class="d-flex align-center justify-space-between">
                                <span class="caption grey--text">{{
                                    form.attachments.jobDesc_size || ""
                                }}</span>
                                <v-btn x-small text color="primary" @click="openFile('jobDesc')">
                                    <v-icon small left>mdi-eye</v-icon>
                                    {{ $t("View") }}
                                </v-btn>
                                <v-btn :disabled="formDisabled" x-small text color="error"
                                @click="clearFile('jobDesc')">
                                <v-icon small left>mdi-delete</v-icon>
                                {{ $t("Delete") }}
                            </v-btn>
                        </div>
                    </div>
                </v-col>
            </v-row>
        </div>
        <div class="mt-2">
            <v-row>
                <v-col cols="12" class="equal-height">
                    <div class="mb-4" v-show="form.pos !== 1">
                        <div class="d-flex align-center mb-2">
                            <v-icon left color="primary">mdi-translate</v-icon>
                            <div class="text-subtitle-1 font-weight-medium">
                                {{ $t("Foreign Language Proficiency") }}
                            </div>
                        </div>
                        <v-row class="ml-1">
                            <v-col cols="12" md="4">
                                <v-radio-group :disabled="formDisabled" v-model="form.either" row dense
                                hide-details>
                                <div class="subtitle-2">{{ $t("Either:") }}</div>
                                <v-radio :disabled="formDisabled"
                                v-for="(level, index) in getLanguageLevels('either')"
                                :key="'either-' + index" :value="level.value"
                                :label="getLocalizedLabel(level)" hide-details class="mr-4"></v-radio>
                            </v-radio-group>
                        </v-col>
                        
                        <v-col cols="12" md="4" :rules="[rules.required]" required>
                            <v-radio-group :disabled="formDisabled" v-model="form.english" row hide-details
                            dense>
                            <div class="subtitle-2">{{ $t("English:") }}</div>
                            <v-radio :disabled="formDisabled"
                            v-for="(level, index) in getLanguageLevels('en')"
                            :key="'english-' + index" :value="level.value"
                            :label="getLocalizedLabel(level)" hide-details class="mr-4"></v-radio>
                        </v-radio-group>
                    </v-col>
                    <v-col cols="12" md="4" :rules="[rules.required]" required>
                        <v-radio-group :disabled="formDisabled" v-model="form.chinese" row hide-details
                        dense>
                        <div class="subtitle-2">{{ $t("Chinese:") }}</div>
                        <v-radio :disabled="formDisabled"
                        v-for="(level, index) in getLanguageLevels('cn')"
                        :key="'chinese-' + index" :value="level.value"
                        :label="getLocalizedLabel(level)" hide-details class="mr-4"></v-radio>
                    </v-radio-group>
                </v-col>
                <v-col cols="12" md="4">
                    <v-checkbox :disabled="formDisabled" v-model="form.tranlator"
                    :label="$t('Translator')" hide-details class="mr-4"></v-checkbox>
                </v-col>
            </v-row>
        </div>
        <div class="mb-4" v-show="form.pos !== 1">
            <div class="d-flex align-center mb-2">
                <v-icon left color="primary">mdi-desktop-classic</v-icon>
                <div class="text-subtitle-1 font-weight-medium">
                    {{ $t("Computer Skills") }}
                </div>
            </div>
            
            <v-row class="ml-2">
                <v-col cols="12" class="d-flex flex-wrap py-0">
                    <v-checkbox
                    v-for="skill in ['Word', 'Excel', 'Access', 'PowerPoint', 'Internet', 'None', 'Other',]"
                    :key="skill" :disabled="formDisabled" v-model="form.computerSkills"
                    :value="skill" :label="$t(skill)" dense hide-details
                    class="mr-4 mb-1 mt-2"></v-checkbox>
                </v-col>
                <v-textarea v-if="form.computerSkills.includes('Other')" :disabled="formDisabled"
                v-model="form.otherComputerSkills" :label="$t('Other Skills')" dense outlined
                class="mt-5" rows="1" hide-details auto-grow></v-textarea>
            </v-row>
        </div>
        <div class="mt-6">
            <div class="d-flex align-center mb-2">
                <v-icon left color="primary">mdi-shield-account</v-icon>
                <div class="text-subtitle-1 font-weight-medium">
                    {{ $t("Workplace Safety") }}
                </div>
            </div>
            <v-radio-group :disabled="formDisabled" v-model="form.safetyLevels" row dense
            class="ml-4" :rules="[rules.required]" required hide-details>
            <v-radio v-for="(level, index) in safetyLevels" :key="'safety-' + index"
            :value="level.id" :label="level.name" class="mr-4" hide-details></v-radio>
        </v-radio-group>
        <v-col cols="12">
            <v-textarea :disabled="formDisabled" v-model="form.remark" outlined
            :label="$t('Remark')" rows="1" hide-details auto-grow></v-textarea>
        </v-col>
        <v-col cols="12">
            <v-text-field 
            outlined 
            readonly 
            dense
            :label="$t('Note')" 
            :value="noteText" 
            hide-details
            :append-icon="(form.req_id_oldyear || (editData && editData.req_id_oldyear)) ? 'mdi-open-in-new' : ''"
            @click:append="viewOriginalRequest"
            color="primary"
            style="cursor: pointer;"
            >
        </v-text-field>
    </v-col>
</div>
</v-col>
<v-card-text class="approval-history-section">
    <div class="d-flex align-center mb-2">
        <v-icon color="primary" class="mr-2">mdi-history</v-icon>
        <span class="text-subtitle-1 font-weight-medium">{{ $t("Approval History") }}</span>
    </div>
    
    <v-expansion-panels flat>
        <v-expansion-panel>
            <v-expansion-panel-header>
                <div class="d-flex align-center">
                    {{ $t("Current Approval History") }}
                </div>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
                <v-stepper v-model="approvalStepper" class="approval-stepper" flat>
                    <v-stepper-header>
                        <v-stepper-step v-if="editData" :complete="true" :editable="true"
                        color="primary" :step="0" :key="'submitter-step'"
                        class="submitter-step" style="pointer-events: none;">
                        <div>
                            <div class="caption font-weight-bold">
                                {{ $t('Submitter') }}
                            </div>
                            <div class="caption">
                                {{
                                    (() => {
                                        try {
                                            let s = editData?.submitter;
                                            if (typeof s === 'string') {
                                                s = JSON.parse(s);
                                            }
                                            return s ? `${s.empno || ''} - ${s.name || ''} -
                                                            ${s.dept_sumt || ''}` : '';
                                        } catch (e) {
                                            console.error('Error parsing submitter data:', e);
                                            return '';
                                        }
                                    })()
                                }}
                            </div>
                            <div class="caption">
                                {{ editData.created_at || '' }}
                            </div>
                        </div>
                    </v-stepper-step>
                    <template v-for="(step, idx) in approvalStepperSteps">
                        <v-stepper-step :complete="step.status === 'approved'"
                        :editable="false"
                        :rules="step.shouldShowError ? [() => false] : []"
                        :color="getApprovalStatusColor(step.status)" step
                        :error-icon="step.shouldShowError ? 'mdi-cancel' : ''"
                        complete-icon="mdi-check" :key="idx" v-if="isLevel1Approved">
                        <template>
                            <div class="caption">{{ step.levelName }}</div>
                            <div class="caption">
                                {{ step.approver?.empno }} - {{ step.approver?.name }} -
                                {{ getApprovalStatusText(step.status) }}
                            </div>
                            <div class="caption">{{ step.processed_at }}</div>
                            <div class="approval-record-container caption">
                                <div v-if="step.reason_denied"
                                class="record-item error--text">
                                <v-icon small color="error"
                                class="mr-1">mdi-close</v-icon>
                                <span class="font-italic">{{ step.reason_denied
                                }}</span>
                            </div>
                            <div v-if="step.return_reason"
                            class="record-item warning--text">
                            <v-icon small color="#FFA500"
                            class="mr-1">mdi-undo-variant</v-icon>
                            <span class="font-italic">{{ step.return_reason
                            }}</span>
                        </div>
                        <div v-if="step.approval_record"
                        class="record-item success--text">
                        <v-icon small color="success"
                        class="mr-1">mdi-check</v-icon>
                        <span class="font-italic">{{ step.approval_record
                        }}</span>
                    </div>
                </div>
            </template>
        </v-stepper-step>
        <v-divider v-if="idx < approvalStepperSteps.length - 1"
        :key="`div-${idx}`"></v-divider>
    </template>
</v-stepper-header>
</v-stepper>
</v-expansion-panel-content>
</v-expansion-panel>
</v-expansion-panels>
</v-card-text>

<v-row class="dialog-actions">
    <v-col cols="12" class="d-flex justify-end align-center">
        <template v-if="isEdit && canApproveItem">
            <v-btn color="#01a6be" class="mr-3 white--text" @click="openAcceptDialog">
                <v-icon left>mdi-check</v-icon> {{ $t("Accept") }}
            </v-btn>
            
            <v-btn v-if="editData && editData.status !== 'declined' && !isNewYearProcessing" 
            color="#FFA500"
            class="mr-3 white--text" @click="openReturnDialog">
            <v-icon left>mdi-undo</v-icon> {{ $t("Return") }}
        </v-btn>
        
        <v-btn v-if="!isNewYearProcessing" 
        color="red darken-2" class="mr-6 white--text" @click="openDenyDialog">
        <v-icon left>mdi-close</v-icon> {{ $t("Deny") }}
    </v-btn>
</template>
<v-btn color="grey" class="mr-3 white--text" @click="dialog = false">
    <v-icon left>mdi-close-circle</v-icon> {{ $t("Cancel") }}
</v-btn>
<v-btn v-show="showSaveButton" color="success" :disabled="disableSaveButton"
@click="confirmSubmitDialog = true"> <v-icon left>mdi-send</v-icon>
{{ $t("Submit")}}
</v-btn>
</v-col>
</v-row>
</v-row>
</div>
</v-card-text>
</v-card>
<v-dialog v-model="confirmAcceptDialog" max-width="500px">
    <v-card>
        <v-card-title style="background-color: #01a6be; color: white;">
            <v-icon left dark>mdi-check</v-icon>
            {{ $t("Confirm Action") }}
            <v-spacer></v-spacer>
            <v-btn-toggle v-model="selectedLanguage" group class="mr-4" dark>
                <v-btn x-small value="vi">VI</v-btn>
                <v-btn x-small value="cn">中文</v-btn>
            </v-btn-toggle>
            <v-btn icon dark @click="confirmAcceptDialog = false"> <v-icon>mdi-close</v-icon> </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-textarea v-model="form.approval_record" outlined :label="$t('Approval record')" rows="3"
            hide-details auto-grow :placeholder="$t('Please provide approval comments')"></v-textarea>
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmAcceptDialog = false">
                {{ $t("Cancel") }}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="#01a6be" dark @click="confirmAccept">
                {{ $t("Confirm") }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<!-- Deny Dialog with Reason -->
<v-dialog v-model="confirmDenyDialog" max-width="500px">
    <v-card>
        <v-card-title class="error white--text">
            <v-icon left dark>mdi-alert</v-icon>
            {{ $t("Deny Request") }}
            <v-spacer></v-spacer>
            <v-btn-toggle v-model="selectedLanguage" group class="mr-4" dark>
                <v-btn x-small value="vi">VI</v-btn>
                <v-btn x-small value="cn">中文</v-btn>
            </v-btn-toggle>
            <v-btn icon dark @click="confirmDenyDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-textarea v-if="selectedLanguage === 'vi'" v-model="denyReason.vi"
            :label="$t('Return reason VietNamese')" :placeholder="$t('Please provide a VietNamese reason')"
            outlined rows="1" hide-details class="mb-2"></v-textarea>
            <v-textarea v-if="selectedLanguage === 'cn'" v-model="denyReason.cn"
            :label="$t('Return reason Chinese')" :placeholder="$t('Please provide a Chinese reason')"
            outlined hide-details rows="1"></v-textarea>
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmDenyDialog = false">
                {{ $t("Cancel") }}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="error" @click="confirmDeny" :disabled="!isDenyReasonValid">
                {{ $t("Confirm Denial") }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<!-- Return Dialog -->
<v-dialog v-model="confirmReturnDialog" max-width="500px" transition="dialog">
    <v-card elevation="2" class="rounded">
        <v-card-title class="warning white--text">
            <v-icon left dark>mdi-alert</v-icon>
            {{ $t("Return Request") }}
            <v-spacer></v-spacer>
            <v-btn-toggle v-model="selectedLanguage" group class="mr-4" dark>
                <v-btn x-small value="vi">VI</v-btn>
                <v-btn x-small value="cn">中文</v-btn>
            </v-btn-toggle>
            <v-btn icon dark @click="confirmReturnDialog = false"> <v-icon>mdi-close</v-icon> </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-textarea v-if="selectedLanguage === 'vi'" v-model="returnReason.vi"
            :label="$t('Return reason VietNamese')" :placeholder="$t('Please provide a VietNamese reason')"
            outlined rows="1" hide-details maxlength="500" class="mb-2"></v-textarea>
            
            <v-textarea v-if="selectedLanguage === 'cn'" v-model="returnReason.cn"
            :label="$t('Return reason Chinese')" :placeholder="$t('Please provide a Chinese reason')"
            outlined rows="1" hide-details maxlength="500" class="mb-2"></v-textarea>
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmReturnDialog = false"> {{ $t("Cancel") }} </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="warning" @click="confirmReturn" :disabled="!isReturnReasonValid"> 
                {{ $t("Confirm Return") }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<!-- Submit Confirmation Dialog -->
<v-dialog v-model="confirmSubmitDialog" max-width="500px">
    <v-card>
        <v-card-title style="background-color: #01a6be; color: white;">
            <v-icon left dark>mdi-send</v-icon>
            {{ $t("Confirm Action") }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="confirmSubmitDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            {{ $t("Are you sure you want to submit this request?") }}
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmSubmitDialog = false"> {{ $t("Cancel") }} </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="success" @click="confirmSubmit">
                {{ $t("Confirm") }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<RequestDialog 
v-if="viewOriginal" 
v-model="viewOriginal" 
:edit-data="originalData" 
:is-edit="true" 
:is-change="false" 
/>
</v-dialog>

</template>
<script>
//  import VfaAppFlow from "../../../@global-component/vfa-comp";
import VfaAppFlow from 'D:/source/@global-component/vfa-comp';
export default {
    components: { VfaAppFlow },
    name: "RequestDialog",
    props: {
        value: Boolean,
        editData: {
            type: Object,
            default: () => null,
        },
        isEdit: {
            type: Boolean,
            default: false,
        },
        isChange: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            otherReasonTab: 0,
            api: "http://gmo021.cansportsvg.com/api/vg-mpr-temp/",
            storageUrl: "http://gmo021.cansportsvg.com/api/storage/app/vg-mpr/",
            dateKeyInMenu: false,
            dateRequestMenu: false,
            autocompleteKey: 0,
            activeUser: {
                empno: "",
                name: "",
                dept: "",
                dept_sumt: "",
                division: "",
                location: "",
                email: "",
            },
            user: {
                dept: "",
                div: "",
                unit: "",
            },
            rules: {
                required: (value) => !!value,
            },
            dept: [],
            departmentData: [],
            itemsDiv: [],
            itemsDept: [],
            itemUnits: [],
            item_changeReason: [],
            iSuppType: [
            {
                id: 1,
                cn: "適用於越籍課長級(含)以下幹部/職員/工人",
                en: "Applicable to Vietnamese Section Chief level (inclusive) and below – supervisor/Staff/Workers",
                vi: "Áp dụng đối với CNV, cán bộ Việt Nam cấp Trưởng ngành trở xuống",
            },
            {
                id: 2,
                cn: "適用於越籍資深課長級(含)以上幹部",
                en: "Applicable to Vietnamese Senior Section Chief level (inclusive) and above – supervisor",
                vi: "Áp dụng đối với cán bộ Việt Nam cấp Trưởng ngành thâm niên trở lên",
            },
            ],
            flow_sign: [
            {
                id: 1,
                cn: "填表人➜ 單位主管 部級主管 ➜ 處級主管 ➜ 人力資源部 (適用於越籍課長級(含)以下幹部/職員/工人)",
                en: "Submitter ➜ Department manager ➜ Division manager ➜ HR Department (Applicable to Vietnamese Section Chief level (inclusive) and below – supervisor/Staff/Workers)",
                vi: "Người Điền Biểu ➜ Chủ Quản Đơn Vị ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR  (Áp dụng đối với CNV, cán bộ Việt Nam cấp Trưởng ngành trở xuống)",
            },
            {
                id: 2,
                cn: "填表人 ➜ 部級主管 ➜ 處級主管 ➜人力資源部 ➜ 管理處 ➜ 總經理室 (適用於越籍資深課長級(含)以上幹部)",
                en: "Submitter ➜ Department manager ➜ Division manager ➜ HR Department ➜ Management Department ➜ General Manager's Office (Applicable to Vietnamese Senior Section Chief level (inclusive) and above – supervisor)",
                vi: "Người Điền Biểu ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR ➜ Khu quản lý  ➜ Văn Phòng TGĐ (Áp dụng đối với cán bộ Việt Nam cấp Trưởng ngành thâm niên trở lên)",
            },
            ],
            selectedFlowSign: null,
            reqId: "",
            itemsReqId: [],
            form: {
                newYearRecruitment: "",
                keyInDate: new Date().toISOString().substr(0, 10),
                req_id: this.generateReqId(),
                suppType: "",
                // change request
                
                either_change: "",
                english_change: "",
                chinese_change: "",
                tranlator_change: false,
                otherComputerSkills_change: "",
                computerSkills_change: [],
                remark_change: "",
                //
                education: {
                    below12: false,
                    level12: false,
                    intermediate: false,
                    college: false,
                    university: false,
                },
                experience: {
                    notRequired: false,
                    lessThan1: false,
                    years1to2: false,
                    years2to3: false,
                    moreThan3: false,
                },
                specialization: "",
                recruitment: {
                    reason: "",
                    oldempno: "",
                    otherReasonSelected: "",
                    requestDate: (() => {
                        const d = new Date();
                        d.setDate(d.getDate() + 7);             // cộng thêm 7 ngày
                        return d.toISOString().substr(0, 10);   // lấy yyyy-mm-dd
                    })(),
                },
                staffCount: {
                    workers: 0,
                    staff: 0,
                    engineers: 0,
                    leader: 0,
                    tranlator: 0,
                    level: "",
                },
                jobDesc: "",
                attachments: {
                    jobDesc: undefined,
                    jobDesc_url: "",
                    jobDesc_name: "",
                    jobDesc_size: "",
                    dimissionList: undefined,
                    dimissionList_url: "",
                    dimissionList_name: "",
                    dimissionList_size: "",
                },
                english: "",
                chinese: "",
                either: "",
                
                computerSkills: [],
                otherComputerSkills: "",
                safetyLevels: "",
                remark: "",
                dataMpr: [],
                pos: "",
                level: "",
                quantity: "",
                educationLevel: "",
                workExperience: "",
                changeQty: "",
                changeReason: "",
                tranlator: false,
            },
            formErrors: {
                education: false,
                experience: false,
                specialization: false,
                recruitment: false,
                staffCount: false,
                jobDesc: false,
                dimissionList: false,
            },
            filePreview: {
                jobDesc: false,
                dimissionList: false,
            },
            workExp: [],
            pos: [],
            eduReq: [],
            lang: [],
            wps: [],
            parsedLanguageLevels: [],
            parsedSafetyLevels: [],
            defaultWpsSafety: "",
            dataAppFlowChanged: [],
            confirmAcceptDialog: false,
            confirmDenyDialog: false,
            confirmReturnDialog: false,
            confirmSubmitDialog: false,
            denyReason: { vi: "", cn: "" },
            returnReason: { vi: "", cn: "" },
            returnToLevel: "",
            dataFlow: "",
            isLoadingFlow: false,
            flowDataTimestamp: "",
            lastEditDataId: "",
            subItems: [],
            approvalHistoryHeaders: [
            {
                text: this.$t("Level"),
                value: "level",
                align: "center",
                width: "80px",
                sortable: false,
            },
            {
                text: this.$t("Type"),
                value: "type",
                align: "center",
                sortable: false,
            },
            {
                text: this.$t("Approver"),
                value: "approver",
                width: "250px",
                sortable: false,
            },
            {
                text: this.$t("Status"),
                value: "status",
                align: "center",
                sortable: false,
            },
            {
                text: this.$t("Date"),
                value: "processed_at",
                align: "center",
                sortable: false,
            },
            ],
            approvalStepper: 1,
            selectedItemForAccept: "",
            isLoadingReqId: false,
            dataRecruitmentReason: [],
            changeQtyError: '',
            selectedLanguage: 'cn',
            unitNames: [],
            localizedApprovalRecords: {},
            otherReasonTimers: { vi: null, en: null, cn: null },
            programmaticUpdating: { vi: false, en: false, cn: false },
            lastEditedOtherReason: null,
            otherReasonDebounceMs: 800,
            otherReason: [],
            viewOriginal: false, 
            originalData: null,
        };
    },
    watch: {
        
        isChange: {
            immediate: true,
            async handler(newVal) {
                if (newVal) {
                    const newReqIdChange = await this.generateReqIdChange();
                    this.form.req_id_change = newReqIdChange;
                } else {
                    this.form.req_id_change = '';
                }
            }
        },
        "form.suppType"(newVal) {
            if (newVal) {
                this.selectedFlowSign = this.flow_sign.find(
                (f) => f.id === parseInt(newVal)
                );
            } else {
                this.selectedFlowSign = null;
            }
            // Reset position selection when suppType changes
            if (newVal === 2 && this.form.pos === 1) {
                this.form.pos = "";
            }
        },
        value(newVal) {
            if (newVal && !this.editData) {
                this.clearForm();
                this.user = {
                    dept: "",
                    div: "",
                    unit: "",
                };
                this.resetActiveUser();
            }
        },
        editData: {
            async handler(newData) {
                if (newData) {
                    await this.getDataModel();
                    this.populateForm(newData);
                    const submitter = typeof newData.submitter === "string" ? JSON.parse(newData.submitter) : newData.submiterInfo;
                    if (submitter) {
                        this.activeUser.name = submitter.name || "";
                        this.activeUser.dept_sumt = submitter.dept_sumt || "";
                    }
                    if (this.isEdit && this.activeUser) {
                        await this.fetchDataFlow();
                    }
                }
            },
            immediate: true,
            deep: true,
        },
        "user.div": {
            handler(newDiv) {
                if (newDiv) {
                    const dept = this.departmentData.find((d) => d.department === newDiv);
                    this.itemsDept = dept ? dept.units.map((u) => u.name) : [];
                } else {
                    this.itemsDept = [];
                }
                // Reset unit khi division thay đổi
                this.user.unit = "";
            },
            immediate: true,
        },
        "user.dept": {
            handler(newDept) {
                if (newDept && this.user.div) {
                    const dept = this.departmentData.find(
                    (d) => d.department === this.user.div
                    );
                    if (dept) {
                        const unit = dept.units.find((u) => u.name === newDept);
                        this.itemUnits = unit ? unit.sub_units : [];
                    }
                } else {
                    this.itemUnits = [];
                }
                // Reset unit khi department thay đổi
                this.user.unit = "";
            },
        },
        
        "form.quantity": {
            handler(newQuantity) {
                if (!this.form.pos) return;
                
                const qty = parseInt(newQuantity);
                switch (this.form.pos) {
                    case 1:
                    this.form.staffCount.workers = qty;
                    break;
                    case 2:
                    this.form.staffCount.staff = qty;
                    break;
                    case 3: // THÊM CASE CHO LEADER
                    this.form.staffCount.leader = qty;
                    this.form.staffCount.level = this.form.level;
                    break;
                    case 4:
                    this.form.staffCount.engineers = qty;
                    break;
                    case 5:
                    this.form.staffCount.tranlator = qty;
                    break;
                }
            },
        },
        "form.level": {
            handler(newLevel) {
                if (this.form.pos === 3) {  // SỬA: Thay đổi từ 4 thành 3
                    this.form.staffCount.level = newLevel;
                }
            },
        },
        '$i18n.locale': {
            immediate: true,
            async handler(newLocale) {
                // Force cập nhật các computed properties
                this.$nextTick(() => {
                    if (this._computedWatchers) {
                        Object.keys(this._computedWatchers).forEach(key => {
                            if (this._computedWatchers[key]) {
                                this._computedWatchers[key].update();
                            }
                        });
                    }
                });
                
                // Cập nhật dữ liệu được localized
                this.updateLocalizedData();
                
                // Force re-render toàn bộ component
                this.$forceUpdate();
            }
        },
        "form.pos"() {
            this.updateFormErrors();
        },
        "form.recruitment.reason"() {
            this.updateFormErrors();
        },
        "form.safetyLevels": {
            handler(newVal) { },
            deep: true,
        },
        safetyLevels: {
            handler(newVal) { },
            deep: true,
        },
        "form.either": {
            handler(newVal) {
                if (newVal) {
                    this.form.english = "";
                    this.form.chinese = "";
                }
            },
        },
        "form.english": {
            handler(newVal) {
                if (newVal) {
                    // Nếu chọn english thì xóa either và chinese
                    this.form.either = "";
                    this.form.chinese = "";
                }
            },
        },
        "form.chinese": {
            handler(newVal) {
                if (newVal) {
                    this.form.either = "";
                    this.form.english = "";
                }
            },
        },
        "form.either_change": {
            handler(newVal) {
                if (newVal) {
                    // If either is selected, clear English and Chinese
                    this.form.english_change = "";
                    this.form.chinese_change = "";
                }
            },
        },
        
        "form.english_change": {
            handler(newVal) {
                if (newVal) {
                    // If English is selected, clear either and Chinese
                    this.form.either_change = "";
                    this.form.chinese_change = "";
                }
            },
        },
        
        "form.chinese_change": {
            handler(newVal) {
                if (newVal) {
                    // If Chinese is selected, clear either and English
                    this.form.either_change = "";
                    this.form.english_change = "";
                }
            },
        },
        isEdit(newVal, oldVal) {
            if (newVal !== oldVal) {
                this.dataFlow = null;
                this.flowDataTimestamp = null;
                this.dataAppFlowChanged = [];
            }
        },
    },
    methods: {
        async viewOriginalRequest() {
            const oldReqId = this.form.req_id_oldyear || (this.editData && this.editData.req_id_oldyear);
            if (!oldReqId) return;
            try {
                this.loading = true;
                const res = await this.$axios.get(`${this.api}getDataByReqId/${oldReqId}`);
                if (res.data && res.data.success && res.data.data) {
                    this.originalData = res.data.data;
                    this.viewOriginal = true;
                } else {
                    this.$notify({
                        title: "Error",
                        text: this.$t("Original request not found"),
                        type: "error",
                    });
                }
            } catch (e) {
                console.error(e);
                this.$notify({
                    title: "Error",
                    text: this.$t("Error opening original request"),
                    type: "error",
                });
            } finally {
                this.loading = false;
            }
        },
        handleOtherReasonSelect(id) {
            if (!id) {
                this.form.recruitment.otherReasonSelected = '';
                return;
            }
            this.form.recruitment.reason = 3;
            
            const item = this.otherReasonItems.find(i => i.id === id);
            if (!item) return;
            
            if (!this.form.recruitment.isOther_reason) {
                this.form.recruitment.isOther_reason = { vi: "", en: "", cn: "" };
            }
            const localeKey = this.$i18n.locale || "vi";
            if (!this.programmaticUpdating) this.programmaticUpdating = { vi: false, en: false, cn: false };
            this.programmaticUpdating[localeKey] = true;
            this.form.recruitment.isOther_reason[localeKey] = item.text;
        },
        async onOtherReasonInput(lang, value) {
            // Debug: kiểm tra input tới handler
            console.debug('onOtherReasonInput called', { lang, value, programmatic: this.programmaticUpdating[lang] });
            
            if (this.programmaticUpdating[lang]) {
                // nếu là update chương trình -> cho qua (reset flag)
                this.programmaticUpdating[lang] = false;
                console.debug('Ignored programmatic update for', lang);
                return;
            }
            
            // lưu giá trị ngay lập tức (v-model cũng làm điều này nhưng giữ cho nhất quán)
            this.form.recruitment.isOther_reason[lang] = value;
            
            // nếu rỗng -> clear tất cả và hủy timers
            if (!value || value.toString().trim() === "") {
                clearTimeout(this.otherReasonTimers.vi);
                clearTimeout(this.otherReasonTimers.en);
                clearTimeout(this.otherReasonTimers.cn);
                
                this.programmaticUpdating.vi = true;
                this.programmaticUpdating.en = true;
                this.programmaticUpdating.cn = true;
                
                this.form.recruitment.isOther_reason.vi = "";
                this.form.recruitment.isOther_reason.en = "";
                this.form.recruitment.isOther_reason.cn = "";
                
                // reset flags nhanh sau clear để không chặn user tiếp theo
                setTimeout(() => {
                    this.programmaticUpdating.vi = false;
                    this.programmaticUpdating.en = false;
                    this.programmaticUpdating.cn = false;
                }, 50);
                return;
            }
            
            const text = value.toString().trim();
            if (!text) return;
            
            // debounce per-language
            clearTimeout(this.otherReasonTimers[lang]);
            this.otherReasonTimers[lang] = setTimeout(async () => {
                try {
                    if (lang === "vi") {
                        const [resEn, resCn] = await Promise.allSettled([
                        this.translateText(text, "en"),
                        this.translateText(text, "cn")
                        ]);
                        // xử lý kết quả an toàn
                        if (resEn.status === 'fulfilled' && resEn.value) {
                            this.programmaticUpdating.en = true;
                            this.form.recruitment.isOther_reason.en = resEn.value;
                        }
                        if (resCn.status === 'fulfilled' && resCn.value) {
                            this.programmaticUpdating.cn = true;
                            this.form.recruitment.isOther_reason.cn = resCn.value;
                        }
                    } else if (lang === "en") {
                        const [resVi, resCn] = await Promise.allSettled([
                        this.translateText(text, "vi"),
                        this.translateText(text, "cn")
                        ]);
                        if (resVi.status === 'fulfilled' && resVi.value) {
                            this.programmaticUpdating.vi = true;
                            this.form.recruitment.isOther_reason.vi = resVi.value;
                        }
                        if (resCn.status === 'fulfilled' && resCn.value) {
                            this.programmaticUpdating.cn = true;
                            this.form.recruitment.isOther_reason.cn = resCn.value;
                        }
                    } else if (lang === "cn") {
                        const [resVi, resEn] = await Promise.allSettled([
                        this.translateText(text, "vi"),
                        this.translateText(text, "en")
                        ]);
                        if (resVi.status === 'fulfilled' && resVi.value) {
                            this.programmaticUpdating.vi = true;
                            this.form.recruitment.isOther_reason.vi = resVi.value;
                        }
                        if (resEn.status === 'fulfilled' && resEn.value) {
                            this.programmaticUpdating.en = true;
                            this.form.recruitment.isOther_reason.en = resEn.value;
                        }
                    }
                } catch (e) {
                    console.error("Translate error (onOtherReasonInput):", e);
                }
            }, this.otherReasonDebounceMs || 800);
        },
        
        onOtherReasonClear(lang) {
            // user clicked clear on one textarea -> clear all
            clearTimeout(this.otherReasonTimers.vi);
            clearTimeout(this.otherReasonTimers.en);
            clearTimeout(this.otherReasonTimers.cn);
            
            // prevent triggering translation handlers as programmatic change
            this.programmaticUpdating.vi = true;
            this.programmaticUpdating.en = true;
            this.programmaticUpdating.cn = true;
            
            this.form.recruitment.isOther_reason.vi = "";
            this.form.recruitment.isOther_reason.en = "";
            this.form.recruitment.isOther_reason.cn = "";
            
            setTimeout(() => {
                this.programmaticUpdating.vi = false;
                this.programmaticUpdating.en = false;
                this.programmaticUpdating.cn = false;
            }, 50);
            
            this.otherReasonTimers.vi = null;
            this.otherReasonTimers.en = null;
            this.otherReasonTimers.cn = null;
        },
        async translateApprovalRecord(text, targetLang) {
            if (!text) return '';
            
            try {
                const langMap = { vi: 'vi', en: 'en', cn: 'zh-tw' };
                const tgt = langMap[targetLang] || targetLang;
                
                const payload = {
                    text: text,
                    targetLangs: [tgt],
                    model: "qwen3-8b",
                    source: "hrm"
                };
                
                const response = await this.$axios.post(
                "http://gmo021.cansportsvg.com:49200/api/translate",
                payload,
                { headers: { "Content-Type": "application/json" } }
                );
                
                if (response.status === 200 && response.data && response.data.data && response.data.data.translation) {
                    return response.data.data.translation[tgt] || text;
                }
                return text;
            } catch (e) {
                console.error('Translation error:', e);
                return text;
            }
        },
        
        // Thêm một phương thức để quản lý cached translations
        getCachedTranslation(originalText, locale) {
            if (!this.localizedApprovalRecords[originalText]) {
                this.localizedApprovalRecords[originalText] = {};
            }
            return this.localizedApprovalRecords[originalText][locale];
        },
        
        setCachedTranslation(originalText, locale, translation) {
            if (!this.localizedApprovalRecords[originalText]) {
                this.localizedApprovalRecords[originalText] = {};
            }
            this.localizedApprovalRecords[originalText][locale] = translation;
        },
        getLocalizedFromMultiLang(value) {
            if (!value) return "";
            try {
                const obj = typeof value === "string" ? JSON.parse(value) : value;
                if (typeof obj === "string") return obj;
                return obj[this.$i18n.locale] || obj.vi || obj.en || obj.cn || "";
            } catch (e) {
                // không phải JSON, trả thẳng
                return value;
            }
        },
        getEffectiveDate() {
            const today = new Date();
            today.setDate(today.getDate() + 7); // Cộng thêm 7 ngày
            return today.toISOString().substr(0, 10); // YYYY-MM-DD
        },
        
        onCloseDialog() {
            this.dialog = false;
            this.reqId = null;
            this.autocompleteKey++;
            this.form.level = "";
        },
        getReasonLabel(reason) {
            try {
                const parsedName = JSON.parse(reason.name);
                return parsedName[this.$i18n.locale] || parsedName.en;
            } catch (e) {
                console.error("Error parsing reason name:", e);
                return reason.name;
            }
        },
        async getReq_id() {
            let params = {
                div: this.activeUser.division,
                status: "completed",
            };
            const response = await this.$axios.post(this.api + "getReqId", params);
            this.itemsReqId = response.data;
        },
        resetAmendent() {
            this.form.either_change = "";
            this.form.english_change = "";
            this.form.chinese_change = "";
            this.form.tranlator_change = false;
            this.form.otherComputerSkills_change = "";
            this.form.computerSkills_change = [];
            this.form.remark_change = "";
        },
        async getDataByReqId() {
            this.resetAmendent();
            const req_id = this.reqId;
            try {
                const response = await this.$axios.get(`${this.api}getDataByReqId/${req_id}`);
                if (response.data && response.data.data) {
                    const dataMpr = response.data.data;
                    
                    const historyFields = [
                    'change_req',
                    'either_change',
                    'english_change',
                    'chinese_change',
                    'computerSkills_change',
                    'remark_change',
                    'tranlator_change'
                    ];
                    
                    historyFields.forEach(field => {
                        if (dataMpr[field]) {
                            try {
                                const historyArray = JSON.parse(dataMpr[field]);
                                if (Array.isArray(historyArray) && historyArray.length > 0) {
                                    dataMpr[field] = historyArray[historyArray.length - 1];
                                }
                            } catch (e) {
                                console.error(`Error parsing ${field}:`, e);
                            }
                        }
                    });
                    
                    if (!dataMpr.hold && dataMpr.resumed !== 1) {
                        this.item_changeReason = this.item_changeReason.filter(item => item.id !== 5);
                    }
                    if (dataMpr.resumed === 1) {
                        this.item_changeReason = this.item_changeReason.filter(item => item.id !== 4 && item.id !== 5);
                    }
                    if (dataMpr.hold === 1) {
                        this.item_changeReason = this.item_changeReason.filter(item => item.id !== 4);
                    }
                    // Populate form with processed data
                    this.populateForm(dataMpr);
                    
                    // Handle submitter info
                    const submitter = JSON.parse(dataMpr.submitter);
                    if (submitter) {
                        this.$nextTick(() => {
                            this.user.div = submitter.div;
                            this.$nextTick(() => {
                                this.user.dept = submitter.dept;
                                this.$nextTick(() => {
                                    this.user.unit = submitter.unit;
                                });
                            });
                        });
                    }
                    
                    this.dataMpr = dataMpr;
                    this.editData = dataMpr;
                    this.approvalStepper = 0;
                }
            } catch (error) {
                console.error("Error fetching data by req_id:", error);
                this.$notify({
                    title: "Error",
                    text: this.$t("Error loading request data"),
                    type: "error",
                });
            }
        },
        async getChangeReason() {
            try {
                const res = await this.$axios.get(this.api + "getChangeReasonData");
                this.item_changeReason = res.data.ChangeData;
            } catch (error) {
                console.error("Error fetching change reasons:", error);
                this.item_changeReason = [];
            }
        },
        getCurrentDate() {
            return new Date().toISOString().substr(0, 10);
        },
        async handleLanguageChange(targetLang) {
            try {
                // localStorage.setItem("preferred-locale", targetLang);
                if (this.$i18n && typeof this.$i18n.locale !== "undefined") {
                    this.$i18n.locale = targetLang;
                } else if (this.$i18n && this.$i18n.global && this.$i18n.global.locale) {
                    this.$i18n.global.locale.value = targetLang;
                }
                await this.$nextTick();
                this.updateLocalizedData();
                const currentId =
                this.form.changeReason && (this.form.changeReason.id ?? (typeof this.form.changeReason === "number" ? this.form.changeReason : null));
                if (currentId != null) {
                    const matched = this.formattedChangeReasons.find((i) => i.id === currentId);
                    if (matched) {
                        this.form.changeReason = { ...matched };
                    }
                }
                await this.helpTrans(targetLang).catch(() => { });
                if (this._computedWatchers) {
                    Object.keys(this._computedWatchers).forEach((key) => {
                        if (this._computedWatchers[key]) this._computedWatchers[key].update();
                    });
                }
                this.$forceUpdate();
            } catch (e) {
                console.error("handleLanguageChange error:", e);
            }
        },
        async helpTrans(target) {
            if (
            !this.form.specialization &&
            !this.form.jobDesc &&
            !this.form.otherComputerSkills &&
            !this.form.remark
            )
            return;
            
            try {
                if (this.form.specialization) {
                    if (typeof this.form.specialization === "string") {
                        this.form.specialization = {
                            [this.$i18n.locale]: this.form.specialization,
                        };
                    }
                    
                    let sourceText;
                    let sourceLocale;
                    const availableLocales = Object.keys(this.form.specialization);
                    sourceLocale =
                    availableLocales.find((locale) => locale !== target) || target;
                    sourceText = this.form.specialization[sourceLocale];
                    
                    if (!sourceText || availableLocales.length === 1) {
                        sourceText = Object.values(this.form.specialization)[0];
                    }
                    
                    await this.translateText(sourceText, target, "specialization");
                }
                
                if (this.form.jobDesc) {
                    await this.translateText(this.form.jobDesc, target, "jobDesc");
                }
                if (this.form.otherComputerSkills) {
                    await this.translateText(
                    this.form.otherComputerSkills,
                    target,
                    "otherComputerSkills"
                    );
                }
                if (this.form.remark) {
                    await this.translateText(this.form.remark, target, "remark");
                }
            } catch (error) {
                console.error("Translation error:", error);
                this.$notify({
                    title: this.$t("error"),
                    text: error.message || "Translation failed",
                    type: "error",
                });
            }
        },
        async translateText(sourceText, target, field) {
            let targetKey = "";
            switch (target) {
                case "vi":
                targetKey = "vi";
                break;
                case "en":
                targetKey = "en";
                break;
                case "cn":
                targetKey = "zh-tw";
                break;
                default:
                return sourceText;
            }
            
            try {
                const payload = {
                    text: sourceText,
                    targetLangs: [targetKey],
                    model: "qwen3-8b",
                    source: "hrm"
                };
                
                const response = await this.$axios.post(
                "http://gmo021.cansportsvg.com:49200/api/translate",
                payload,
                { headers: { "Content-Type": "application/json" } }
                );
                
                if (response.status === 200 && response.data && response.data.data && response.data.data.translation) {
                    const translated = response.data.data.translation[targetKey] || sourceText;
                    if (field === "specialization") {
                        this.form.specialization = {
                            ...this.form.specialization,
                            [target]: translated,
                        };
                    } else if (field) {
                        this.form[field] = translated;
                    }
                    return translated;
                }
                return sourceText;
            } catch (err) {
                console.error("translateText error:", err);
                return sourceText;
            }
        },
        async setLocale(tg) {
            try {
                // Lưu ngôn ngữ vào localStorage
                // localStorage.setItem("preferred-locale", tg);
                
                // Set locale cho i18n
                this.$i18n.locale = tg;
                
                // Đợi next tick để đảm bảo Vue cập nhật DOM
                await this.$nextTick();
                await this.handleLanguageChange(tg);
                // Force cập nhật các computed properties
                if (this._computedWatchers) {
                    Object.keys(this._computedWatchers).forEach(key => {
                        if (this._computedWatchers[key]) {
                            this._computedWatchers[key].update();
                        }
                    });
                }
                
                // Force re-render component
                this.$forceUpdate();
                
                // Emit event để các component khác biết về thay đổi ngôn ngữ  
                this.$root.$emit('languageChanged', tg);
                
            } catch (error) {
                console.error('Error setting locale:', error);
            }
        },
        
        updateLocalizedData() {
            if (this.wps && this.wps.length > 0) {
                this.parsedSafetyLevels = this.wps.map((item) => {
                    try {
                        const wpsParsed = JSON.parse(item.wps_name);
                        const fullData = {
                            id: item.id,
                            name: wpsParsed,
                        };
                        
                        return {
                            id: item.id,
                            name: wpsParsed[this.$i18n.locale] || wpsParsed.en,
                            fullData: fullData,
                        };
                    } catch (e) {
                        console.error("Error parsing WPS data:", e);
                        return {
                            id: item.id,
                            name: "Error",
                            fullData: {
                                id: item.id,
                                name: { en: "Error", vi: "Lỗi", cn: "错误" },
                            },
                        };
                    }
                });
            }
            
            if (this.lang && this.lang.lang) {
                try {
                    const parsedLevels = JSON.parse(this.lang.lang);
                    this.parsedLanguageLevels = parsedLevels;
                    
                    this.$set(this, "parsedLanguageLevels", [...parsedLevels]);
                } catch (e) {
                    console.error("Error parsing language data:", e);
                }
            }
        },
        async getDataModel() {
            const location = this.activeUser?.location;
            if (!location) {
                console.error("Location not provided");
                return;
            }
            let params = {
                loc: location,
            };
            try {
                const response = await this.$axios.post(this.api + "getDataModel", params);
                this.dataRecruitmentReason = response.data.dataRecReason;
                this.workExp = response.data.dataWorkExp;
                this.pos = response.data.dataTypeApply;
                this.subItems = response.data.dataSubPos;
                this.eduReq = response.data.dataEduReq;
                this.lang = response.data.dataLang;
                this.unitNames = response.data.dataDeptHrm;
                this.otherReason = response.data.dataOtherReason;
                response.data.dataWps;
                if (this.lang && this.lang.lang) {
                    try {
                        this.parsedLanguageLevels = JSON.parse(this.lang.lang);
                    } catch (e) {
                        console.error("Error parsing language data:", e);
                        this.parsedLanguageLevels = [];
                    }
                }
                this.wps = response.data.dataWps;
                
                this.parsedSafetyLevels = this.wps.map((item) => {
                    try {
                        const wpsParsed = JSON.parse(item.wps_name);
                        const fullData = {
                            id: item.id,
                            name: wpsParsed,
                        };
                        return {
                            id: item.id,
                            name: wpsParsed[this.$i18n.locale] || wpsParsed.en,
                            fullData: fullData,
                        };
                    } catch (e) {
                        console.error("Error parsing WPS data:", e);
                        return {
                            id: item.id,
                            name: "Error",
                            fullData: {
                                id: item.id,
                                name: { en: "Error", vi: "Lỗi", cn: "错误" },
                            },
                        };
                    }
                });
                this.dept = response.data.dataDeptCode;
                
                const deptMap = new Map();
                this.dept.forEach((item) => {
                    if (!deptMap.has(item.department)) {
                        deptMap.set(item.department, {
                            department: item.department,
                            units: new Map(),
                        });
                    }
                    const deptData = deptMap.get(item.department);
                    if (!deptData.units.has(item.sub_department)) {
                        deptData.units.set(item.sub_department, new Set());
                    }
                    if (!item.end_date && item.sub_division) {
                        deptData.units.get(item.sub_department).add(item.sub_division);
                    }
                });
                this.departmentData = Array.from(deptMap.values()).map((dept) => ({
                    department: dept.department,
                    units: Array.from(dept.units.entries()).map(
                    ([unitName, subUnits]) => ({
                        name: unitName,
                        sub_units: Array.from(subUnits),
                    })
                    ),
                }));
                this.itemsDiv = [
                ...new Set(
                this.departmentData
                .filter((dept) => dept.department === this.activeUser.division)
                .map((dept) => dept.department)
                ),
                ];
            } catch (error) {
                console.error("Error fetching work experience data:", error);
            }
        },
        async saveDB() {
            try {
                let requestId;
                if (this.isChange) {
                    requestId = this.reqId;
                } else {
                    if (!this.editData) {
                        this.form.req_id = await this.generateReqId();
                    }
                    requestId = this.form.req_id;
                }
                
                const formData = new FormData();
                formData.append("req_id", requestId);
                const isAmendmentRequest = this.form.changeReason
                
                if (isAmendmentRequest) {
                    formData.append("status_amendent", "waiting_dept");
                    formData.append("is_amendment", "true");
                    // Giữ nguyên status gốc nếu đã tồn tại
                    if (this.editData && this.editData.status) {
                        formData.append("status", this.editData.status);
                    } else {
                        formData.append("status", "waiting_dept");
                    }
                } else {
                    formData.append("status", "waiting_dept");
                    formData.append("is_amendment", "false");
                    formData.append("status_amendent", null);
                }
                this.formErrors = {
                    education: false,
                    experience: false,
                    specialization: false,
                    recruitment: false,
                    staffCount: false,
                };
                
                if (Object.values(this.formErrors).some((error) => error)) {
                    this.$notify({
                        title: "Error",
                        text: this.$t("Please fill all required fields"),
                        type: "error",
                    });
                    return;
                }
                
                const submitterData = {
                    empno: this.activeUser.empno,
                    name: this.activeUser.name,
                    dept_sumt: this.activeUser.dept_sumt,
                    high_dept: this.activeUser.division,
                    email: this.activeUser.email,
                    dept: this.user.dept,
                    div: this.user.div,
                    unit: this.user.unit,
                };
                
                if (this.form.newYearRecruitment !== "" && this.form.newYearRecruitment !== null && this.form.newYearRecruitment !== undefined) {
                    formData.append("activeRct", this.form.newYearRecruitment);
                }
                // Basic info
                formData.append("loc", this.activeUser.location);
                formData.append("key_in_date", this.form.keyInDate);
                formData.append("submitter", JSON.stringify(submitterData));
                formData.append("status", "waiting_dept");
                formData.append("supp_type", this.form.suppType);
                
                formData.append("edu", this.form.educationLevel);
                formData.append("sub_pos", this.form.sub_position || "");
                if (this.form.specialization) {
                    if (typeof this.form.specialization === 'string') {
                        formData.append('spec', this.form.specialization);
                    } else if (this.form.specialization[this.$i18n.locale]) {
                        formData.append('spec', this.form.specialization[this.$i18n.locale]);
                    }
                }
                formData.append("w_exp", this.form.workExperience);
                // formData.append("reason_exp", this.form.recruitment.reason);
                const reasonExp = this.form.recruitment.reason || (this.form.recruitment.otherReasonSelected ? 3 : "");
                formData.append("reason_exp", reasonExp ? String(reasonExp) : "");
                formData.append("old_emp", this.form.recruitment.oldempno);
                formData.append("isOther_reason", this.form.recruitment.otherReasonSelected);
                formData.append("date_req", this.form.recruitment.requestDate);
                formData.append("qty_req", JSON.stringify(this.form.staffCount));
                
                let totalQty = this.form.quantity;
                const changeReq = {
                    change_reason: this.form.changeReason,
                    change_qty: this.form.changeQty,
                };
                
                if (this.isChange && changeReq.change_qty) {
                    totalQty = changeReq.change_qty;
                }
                
                formData.append("total_qty", totalQty);
                formData.append("pos", this.form.pos);
                formData.append("jd", this.form.jobDesc);
                
                // Handle attachments
                if (this.form.attachments.jobDesc instanceof File) {
                    const originalName = this.form.attachments.jobDesc.name;
                    const fileExt = originalName.split('.').pop();
                    const newFileName = `${requestId}_jobdesc.${fileExt}`;
                    
                    // Create new File object with custom name
                    const renamedJobDescFile = new File(
                    [this.form.attachments.jobDesc],
                    newFileName,
                    { type: this.form.attachments.jobDesc.type }
                    );
                    formData.append("jobDescFile", renamedJobDescFile);
                } else if (this.editData?.att_jd && this.filePreview.jobDesc) {
                    formData.append("att_jd", this.editData.att_jd);
                }
                
                if (this.form.attachments.dimissionList instanceof File) {
                    const originalName = this.form.attachments.dimissionList.name;
                    const fileExt = originalName.split('.').pop();
                    const newFileName = `${requestId}_dimission.${fileExt}`;
                    
                    // Create new File object with custom name
                    const renamedDimissionFile = new File(
                    [this.form.attachments.dimissionList],
                    newFileName,
                    { type: this.form.attachments.dimissionList.type }
                    );
                    formData.append("dimissionFile", renamedDimissionFile);
                } else if (this.editData?.att_dis && this.filePreview.dimissionList) {
                    formData.append("att_dis", this.editData.att_dis);
                }
                
                // Language skills
                if (this.form.either) {
                    formData.append("either_skill", this.form.either);
                    formData.append("eng_skill", "");
                    formData.append("chn_skill", "");
                } else if (this.form.english) {
                    formData.append("either_skill", "");
                    formData.append("eng_skill", this.form.english);
                    formData.append("chn_skill", "");
                } else if (this.form.chinese) {
                    formData.append("either_skill", "");
                    formData.append("eng_skill", "");
                    formData.append("chn_skill", this.form.chinese);
                } else {
                    formData.append("either_skill", "");
                    formData.append("eng_skill", "");
                    formData.append("chn_skill", "");
                }
                
                // Computer skills
                const computerSkillsData = {
                    skills: this.form.computerSkills,
                    others: this.form.otherComputerSkills,
                };
                formData.append("comp_skill", JSON.stringify(computerSkillsData));
                
                formData.append("req_id_change", this.form.req_id_change || "");
                
                formData.append("change_req", JSON.stringify(changeReq));
                
                if (this.form.either_change) {
                    formData.append("either_change", this.form.either_change);
                    formData.append("english_change", "");
                    formData.append("chinese_change", "");
                } else if (this.form.english_change) {
                    formData.append("either_change", "");
                    formData.append("english_change", this.form.english_change);
                    formData.append("chinese_change", "");
                } else if (this.form.chinese_change) {
                    formData.append("either_change", "");
                    formData.append("english_change", "");
                    formData.append("chinese_change", this.form.chinese_change);
                } else {
                    formData.append("either_change", "");
                    formData.append("english_change", "");
                    formData.append("chinese_change", "");
                }
                
                formData.append("tranlator_change", this.form.tranlator_change ? 1 : 0);
                
                const computerSkillsDataChange = {
                    skills: this.form.computerSkills_change,
                    others: this.form.otherComputerSkills_change,
                };
                formData.append("computerSkills_change", JSON.stringify(computerSkillsDataChange));
                formData.append("remark_change", this.form.remark_change || "");
                
                // Safety levels and remarks
                const formattedWps = this.form.safetyLevels ? [this.form.safetyLevels] : [];
                formData.append("wps", formattedWps);
                formData.append("remark", this.form.remark || "");
                formData.append("tranlator", this.form.staffCount.tranlator ? 1 : 0);
                
                let hold = 0, resumed = 0, hold_date = '', resumed_date = '';
                
                if (this.form.changeReason?.id === 4) {
                    hold = 1;
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    hold_date = tomorrow.toISOString();
                } else if (this.form.changeReason?.id === 5) {
                    resumed = 1;
                    resumed_date = new Date(Date.now() + 86400000).toISOString(); // Add 1 day in ms
                    hold = this.editData?.hold;
                    hold_date = this.editData?.hold_date;
                }
                formData.append('hold', hold);
                formData.append('resumed', resumed);
                formData.append('hold_date', hold_date);
                formData.append('resumed_date', resumed_date);
                
                const defaultApproval = [
                { level: 1, type: "dept", approver: { empno: "", name: "", dept: "" }, status: "waiting" },
                { level: 2, type: "div", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 3, type: "hr_rec_sf", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 4, type: "hr", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 5, type: "ad", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 6, type: "gm", approver: { empno: "", name: "", dept: "" }, status: "" }
                ];
                if (!isAmendmentRequest || !this.form.newYearRecruitment) {
                    // Chỉ set approval cho đơn gốc
                    formData.append("approval", JSON.stringify(defaultApproval));
                } else {
                    // Giữ nguyên approval gốc nếu có
                    if (this.editData && this.editData.approval) {
                        formData.append("approval", this.editData.approval);
                    }
                }
                // Flow and email data
                formData.append("dataFlow", JSON.stringify(this.dataAppFlowChanged));
                formData.append("emailList", JSON.stringify(this.listEmailDeptManager));
                const isCancellationRequest = this.form.changeReason?.id === 2;
                // Kiểm tra newYearRecruitment có giá trị không (khác rỗng và khác null)
                const isNewYearDecision = this.form.newYearRecruitment !== "" && this.form.newYearRecruitment != null;
                
                if (isCancellationRequest || isNewYearDecision) {
                    const recruitedCount = parseInt(this.editData?.recruited || 0);
                    
                    const denyTempData = {
                        fullfilldate: this.getCurrentDate(),
                        fulfilled: "yes",
                        recruited: recruitedCount,
                        stocknumber: 0,
                    };
                    if (isCancellationRequest) {
                        denyTempData.status_amendent = "declined";
                    }
                    
                    formData.append("deny_temp", JSON.stringify(denyTempData));
                }
                const response = await this.$axios.post(
                this.api + "saveManpower",
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                }
                );
                
                if (response.data.success) {
                    this.$notify({
                        title: "Success",
                        text: this.$t("Request submitted successfully"),
                        type: "success",
                    });
                    this.dialog = false;
                    this.$emit("requestUpdated");
                    this.clearForm();
                } else {
                    this.$notify({
                        title: "Error",
                        text: response.data.message || this.$t("Error submitting request"),
                        type: "error",
                    });
                }
                
            } catch (error) {
                console.error("Error saving request:", error);
                this.$notify({
                    title: "Error",
                    text: this.$t("Error submitting request") + ": " + error.message,
                    type: "error",
                });
            }
        },
        clearForm() {
            const oldForm = { ...this.form };
            this.form = {
                keyInDate: new Date().toISOString().substr(0, 10),
                req_id: "",
                req_id_change: "",
                req_id_oldyear: "",
                suppType: "",
                computerSkills_change: [],
                english_change: "",
                chinese_change: "",
                either_change: "",
                tranlator_change: false,
                remark_change: "",
                otherComputerSkills_change: "",
                education: {
                    below12: false,
                    level12: false,
                    intermediate: false,
                    college: false,
                    university: false,
                },
                experience: {
                    notRequired: false,
                    lessThan1: false,
                    years1to2: false,
                    years2to3: false,
                    moreThan3: false,
                },
                specialization: "",
                recruitment: {
                    reason: "",
                    oldempno: "",
                    isOther_reason: {
                        vi: "",
                        en: "",
                        cn: ""
                    },
                    requestDate: (() => {
                        const d = new Date();
                        d.setDate(d.getDate() + 7);
                        return d.toISOString().substr(0, 10);
                    })(),
                },
                staffCount: {
                    workers: 0,
                    staff: 0,
                    engineers: 0,
                    leader: 0,
                    tranlator: 0,
                    level: "",
                },
                jobDesc: "",
                attachments: {
                    jobDesc: undefined,
                    jobDesc_url: "",
                    jobDesc_name: "",
                    jobDesc_size: "",
                    dimissionList: undefined,
                    dimissionList_url: "",
                    dimissionList_name: "",
                    dimissionList_size: "",
                },
                english: "",
                chinese: "",
                either: "",
                
                computerSkills: [],
                otherComputerSkills: "",
                safetyLevels: "",
                remark: "",
                dataMpr: [],
                pos: "",
                level: "",
                quantity: "",
                educationLevel: "",
                workExperience: "",
                changeQty: "",
                changeReason: "",
            };
            
            this.formErrors = {
                education: false,
                experience: false,
                specialization: false,
                recruitment: false,
                staffCount: false,
                jobDesc: false,
                dimissionList: false,
            };
            
            this.filePreview = {
                jobDesc: false,
                dimissionList: false,
            };
            this.denyReason = {
                vi: '',
                cn: ''
            };
            this.returnReason = {
                vi: '',
                cn: ''
            };
            if (this.isChange) {
                this.generateReqIdChange().then((newReqId) => {
                    this.form.req_id_change = newReqId;
                });
            } else {
                this.generateReqId().then((newReqId) => {
                    this.form.req_id = newReqId;
                });
            }
            
        },
        resetActiveUser() {
            // Lấy lại thông tin user từ session khi tạo đơn mới
            const sessionUser = this.$session.get("mpr");
            if (sessionUser) {
                this.activeUser = {
                    empno: sessionUser.empno,
                    name: sessionUser.name,
                    dept_sumt: sessionUser.dept,
                    division: sessionUser.hight_dept,
                    location: sessionUser.location,
                    email: sessionUser.email,
                };
            }
        },
        handleTypeApplyChange(value) {
            this.showLevelField = value === 3;
            
            if (value !== 3) {
                this.form.level = "";
            }
            
            if (value === 1) {
                this.clearFile("jobDesc");
            }
            
            // Reset staff count
            this.form.staffCount = {
                workers: 0,
                staff: 0,
                engineers: 0,
                leader: 0,
                tranlator: 0,
                level: "",
            };
            
            if (value) {
                const qty = parseInt(this.form.quantity);
                
                switch (value) {
                    case 1: // Worker
                    this.form.staffCount.workers = qty;
                    break;
                    case 2: // Staff
                    this.form.staffCount.staff = qty;
                    break;
                    case 3: // Leader - THÊM CASE MỚI
                    this.form.staffCount.leader = qty;
                    this.form.staffCount.level = this.form.level;
                    break;
                    case 4: // Engineer
                    this.form.staffCount.engineers = qty;
                    break;
                    case 5: // Translator
                    this.form.staffCount.tranlator = qty;
                    break;
                }
                
            }
        },
        clearFile(fileType) {
            this.form.attachments[fileType] = null; // Change undefined to null
            this.form.attachments[`${fileType}_url`] = "";
            this.form.attachments[`${fileType}_name`] = "";
            this.form.attachments[`${fileType}_size`] = "";
            this.filePreview[fileType] = false;
        },
        previewFile(fileType, event) {
            if (this.form.attachments[fileType]) {
                
                this.filePreview[fileType] = true;
            }
        },
        getFileIcon(mimeType) {
            if (!mimeType) return "mdi-file-outline";
            
            if (mimeType.includes("pdf")) return "mdi-file-pdf";
            if (mimeType.includes("word") || mimeType.includes("msword")) {
                return "mdi-file-word";
            }
            if (mimeType.includes("excel") || mimeType.includes("spreadsheet")) {
                return "mdi-file-excel";
            }
            if (mimeType.includes("image")) return "mdi-file-image";
            
            return "mdi-file-outline";
        },
        formatFileSize(size) {
            if (!size) return "0 KB";
            
            if (size < 1024) return size + " B";
            if (size < 1024 * 1024) return Math.round(size / 1024) + " KB";
            return Math.round((size / (1024 * 1024)) * 10) / 10 + " MB";
        },
        openFile(fileType) {
            const file =
            fileType === "jobDesc"
            ? this.form.attachments.jobDesc
            : this.form.attachments.dimissionList;
            if (!file) return;
            
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL, "_blank");
        },
        getFileName(fileType) {
            const attachment = this.form.attachments[fileType];
            if (attachment instanceof File) {
                return attachment.name;
            } else if (typeof attachment === "string") {
                return decodeURIComponent(attachment.split("/").pop());
            } else if (this.form.attachments[`${fileType}_name`]) {
                return this.form.attachments[`${fileType}_name`];
            }
            return "";
        },
        
        getFileTypeFromName(fileName) {
            if (!fileName || typeof fileName !== "string") return "";
            
            if (fileName.toLowerCase().endsWith(".pdf")) return "application/pdf";
            if (
            fileName.toLowerCase().endsWith(".doc") ||
            fileName.toLowerCase().endsWith(".docx")
            )
            return "application/msword";
            if (
            fileName.toLowerCase().endsWith(".xls") ||
            fileName.toLowerCase().endsWith(".xlsx")
            )
            return "application/vnd.ms-excel";
            if (fileName.toLowerCase().match(/\.(jpg|jpeg|png|gif)$/))
            return "image/jpeg";
            
            return "application/octet-stream";
        },
        openFile(fileType) {
            const file = this.form.attachments[fileType];
            
            if (file instanceof File) {
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL, "_blank");
                return;
            }
            if (typeof file === "string" && file.trim() !== "") {
                window.open(`${this.storageUrl}${file}`, "_blank");
                return;
            }
            if (
            this.form.attachments[`${fileType}_url`] &&
            this.form.attachments[`${fileType}_url`].trim() !== ""
            ) {
                window.open(this.form.attachments[`${fileType}_url`], "_blank");
                return;
            }
        },
        populateForm(data) {
            if (!data) return;
            this.form.req_id_oldyear = data.req_id_oldyear || "";
            // 1. Parse submitter info
            if (data.activeRct !== null) {
                // Parse thành number để đảm bảo kiểu dữ liệu đúng
                this.form.newYearRecruitment = parseInt(data.activeRct);
            }
            const submitter = JSON.parse(data.submitter);
            if (submitter) {
                this.activeUser.name = submitter.name || "";
                this.activeUser.dept_sumt = submitter.dept_sumt || "";
                
                this.$nextTick(() => {
                    this.user.div = submitter.div;
                    this.$nextTick(() => {
                        this.user.dept = submitter.dept;
                        this.$nextTick(() => {
                            this.user.unit = submitter.unit;
                        });
                    });
                });
            }
            if (this.isChange) {
                this.form.changeReason = "";
                this.form.changeQty = "";
                this.form.either_change = "";
                this.form.english_change = "";
                this.form.chinese_change = "";
                this.form.tranlator_change = false;
                this.form.computerSkills_change = [];
                this.form.otherComputerSkills_change = "";
                this.form.remark_change = "";
            } else {
                // 2. Handle change request fields 
                if (data.change_req) {
                    try {
                        const changeReqHistory = JSON.parse(data.change_req);
                        if (Array.isArray(changeReqHistory) && changeReqHistory.length > 0) {
                            const lastChangeReq = changeReqHistory[changeReqHistory.length - 1];
                            if (lastChangeReq && lastChangeReq.change_reason) {
                                const changeId = lastChangeReq.change_reason.id;
                                // find the item in formattedChangeReasons so v-combobox can show text property
                                const matched = this.formattedChangeReasons.find(i => i.id === changeId);
                                this.form.changeReason = matched ? { ...matched } : { id: changeId };
                                this.form.changeQty = lastChangeReq.change_qty || "";
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing change_req', e);
                    }
                }
                
                // 3. Handle either_change
                if (data.either_change) {
                    try {
                        const eitherHistory = JSON.parse(data.either_change);
                        if (Array.isArray(eitherHistory) && eitherHistory.length > 0) {
                            this.form.either_change = eitherHistory[eitherHistory.length - 1].toString();
                        }
                    } catch (e) {
                        console.error('Error parsing either_change:', e);
                    }
                }
                
                // 4. Handle english_change
                if (data.english_change) {
                    try {
                        const englishHistory = JSON.parse(data.english_change);
                        if (Array.isArray(englishHistory) && englishHistory.length > 0) {
                            this.form.english_change = englishHistory[englishHistory.length - 1].toString();
                        }
                    } catch (e) {
                        console.error('Error parsing english_change:', e);
                    }
                }
                
                // 5. Handle chinese_change
                if (data.chinese_change) {
                    try {
                        const chineseHistory = JSON.parse(data.chinese_change);
                        if (Array.isArray(chineseHistory) && chineseHistory.length > 0) {
                            this.form.chinese_change = chineseHistory[chineseHistory.length - 1].toString();
                        }
                    } catch (e) {
                        console.error('Error parsing chinese_change:', e);
                    }
                }
                
                // 6. Handle tranlator_change
                if (data.tranlator_change) {
                    try {
                        const translatorHistory = JSON.parse(data.tranlator_change);
                        if (Array.isArray(translatorHistory) && translatorHistory.length > 0) {
                            this.form.tranlator_change = translatorHistory[translatorHistory.length - 1] === 1;
                        }
                    } catch (e) {
                        console.error('Error parsing tranlator_change:', e);
                    }
                }
                
                // 7. Handle computerSkills_change
                if (data.computerSkills_change) {
                    try {
                        const computerSkillsHistory = JSON.parse(data.computerSkills_change);
                        if (Array.isArray(computerSkillsHistory) && computerSkillsHistory.length > 0) {
                            const lastComputerSkills = computerSkillsHistory[computerSkillsHistory.length - 1];
                            this.form.computerSkills_change = lastComputerSkills.skills || [];
                            this.form.otherComputerSkills_change = lastComputerSkills.others || '';
                        }
                    } catch (e) {
                        console.error('Error parsing computerSkills_change:', e);
                    }
                }
                
                // 8. Handle remark_change
                if (data.remark_change) {
                    try {
                        const remarkHistory = JSON.parse(data.remark_change);
                        if (Array.isArray(remarkHistory) && remarkHistory.length > 0) {
                            this.form.remark_change = remarkHistory[remarkHistory.length - 1];
                        }
                    } catch (e) {
                        console.error('Error parsing remark_change:', e);
                    }
                }
            }
            
            // 9. Basic fields
            this.reqId = data.req_id;
            this.form.keyInDate = data.key_in_date;
            this.form.req_id = data.req_id;
            this.form.suppType = data.supp_type;
            this.form.specialization = data.spec;
            this.form.jobDesc = data.jd;
            this.form.remark = data.remark || "";
            this.form.sub_position = data.sub_pos;
            this.form.educationLevel = data.edu;
            this.form.workExperience = data.w_exp;
            this.form.pos = data.pos != null ? Number(data.pos) : "";
            this.form.cancel_date = data.cancel_date || "";
            this.form.tranlator = data.tranlator;
            this.form.safetyLevels = data.wps;
            
            // 10. Handle recruitment info
            this.form.recruitment = {
                reason: "", // clear initial value
                oldempno: data.old_emp || "",
                requestDate: data.date_req,
                otherReasonSelected: "",  // clear initial value
                isOther_reason: { vi: "", en: "", cn: "" }
            };
            
            try {
                // Xử lý reason_exp
                if (data.reason_exp && data.reason_exp !== "3") {
                    // Nếu là lý do thông thường (không phải Other)
                    this.form.recruitment.reason = parseInt(data.reason_exp);
                    this.form.recruitment.otherReasonSelected = "";
                }
                
                // Xử lý isOther_reason 
                if (data.isOther_reason) {
                    let otherId = null;
                    
                    // Kiểm tra nếu isOther_reason có thể parse thành JSON
                    try {
                        const parsed = JSON.parse(data.isOther_reason);
                        if (typeof parsed === 'object') {
                            this.form.recruitment.isOther_reason = parsed;
                        } else {
                            otherId = parseInt(data.isOther_reason);
                        }
                    } catch (e) {
                        // Nếu không parse được, có thể là ID
                        otherId = parseInt(data.isOther_reason);
                    }
                    
                    if (!isNaN(otherId)) {
                        this.form.recruitment.reason = 3; // Set reason là Other
                        this.form.recruitment.otherReasonSelected = otherId;
                        
                        // Tìm và set text cho other reason
                        const found = (this.otherReason || []).find(r => Number(r.id) === Number(otherId));
                        if (found) {
                            try {
                                const parsedName = typeof found.reason_name === "string" ?
                                JSON.parse(found.reason_name) : found.reason_name;
                                const localeKey = this.$i18n.locale || "vi";
                                
                                if (!this.form.recruitment.isOther_reason) {
                                    this.form.recruitment.isOther_reason = { vi: "", en: "", cn: "" };
                                }
                                this.form.recruitment.isOther_reason[localeKey] =
                                parsedName[localeKey] || parsedName.en || "";
                            } catch (e) {
                                console.error('Error parsing other reason name:', e);
                            }
                        }
                    }
                }
            } catch (e) {
                console.error("Error handling recruitment data:", e);
            }
            // 11. Handle staff count
            try {
                let qtyData = typeof data.qty_req === "string" ? JSON.parse(data.qty_req) : data.qty_req;
                this.form.staffCount = {
                    workers: parseInt(qtyData.workers) || 0,
                    staff: parseInt(qtyData.staff) || 0,
                    engineers: parseInt(qtyData.engineers) || 0,
                    leader: parseInt(qtyData.leader) || 0,
                    tranlator: parseInt(qtyData.tranlator) || 0,
                    level: qtyData.level || ""
                };
                
                // LUÔN gán level nếu có trong dữ liệu
                if (qtyData.level) {
                    this.form.level = qtyData.level;
                }
                
                // Set form quantity based on position type
                if (this.form.pos === 1) {
                    this.form.quantity = this.form.staffCount.workers;
                } else if (this.form.pos === 2) {
                    this.form.quantity = this.form.staffCount.staff;
                } else if (this.form.pos === 3) {  // SỬA: Thêm case cho pos === 3
                    this.form.quantity = this.form.staffCount.leader;
                    this.form.level = this.form.staffCount.level;
                } else if (this.form.pos === 4) {
                    this.form.quantity = this.form.staffCount.engineers;
                } else if (this.form.pos === 5) {
                    this.form.quantity = this.form.staffCount.tranlator;
                }
                
                if (data.total_qty) {
                    this.form.quantity = parseInt(data.total_qty);
                }
            } catch (e) {
                console.error("Error parsing quantity data:", e);
                this.form.quantity = "";
            }
            
            
            // 12. Language skills
            this.form.english = (data.eng_skill ?? "").toString();
            this.form.chinese = (data.chn_skill ?? "").toString();
            this.form.either = (data.either_skill ?? "").toString();
            
            // 13. Computer skills
            try {
                const computerSkills = JSON.parse(data.comp_skill);
                this.form.computerSkills = computerSkills?.skills || [];
                this.form.otherComputerSkills = computerSkills?.others || "";
            } catch (e) {
                console.error('Error parsing comp_skill:', e);
                this.form.computerSkills = [];
                this.form.otherComputerSkills = "";
            }
            
            // 14. Handle attachments
            if (data.att_jd) {
                this.form.attachments.jobDesc = null;
                this.form.attachments.jobDesc_url = `${this.storageUrl}${data.att_jd}`;
                this.form.attachments.jobDesc_name = decodeURIComponent(data.att_jd.split("/").pop());
                this.filePreview.jobDesc = true;
            } else {
                this.clearFile("jobDesc");
            }
            
            if (data.att_dis) {
                this.form.attachments.dimissionList = null;
                this.form.attachments.dimissionList_url = `${this.storageUrl}${data.att_dis}`;
                this.form.attachments.dimissionList_name = decodeURIComponent(data.att_dis.split("/").pop());
                this.filePreview.dimissionList = true;
            } else {
                this.clearFile("dimissionList");
            }
        },
        getLanguageLevels(type) {
            if (!this.lang) return [];
            
            const langData = this.lang.find((l) => l.lang_code === type);
            if (!langData || !langData.lang) return [];
            
            try {
                const levels = JSON.parse(langData.lang);
                return levels.map((level, index) => ({
                    value: (index + 1).toString(),
                    en: level.en,
                    vi: level.vi,
                    cn: level.cn,
                    label: level[this.$i18n.locale] || level.en,
                }));
            } catch (e) {
                console.error(`Error parsing ${type} language levels:`, e);
                return [];
            }
        },
        getLocalizedLabel(level) {
            return level[this.$i18n.locale] || level.en || level.label;
        },
        handleAppFlow(appFlow) {
            
            this.dataAppFlow = appFlow.map((item, index) => ({
                level: "level" + (index + 1),
                managers: item.managers.map((x) => {
                    return {
                        email: x.email,
                        empno: x.empno,
                        name: x.name,
                        dept: x.dept_code,
                        deputies: x.deputies,
                    };
                }),
            }));
            this.dataAppFlowChanged = this.dataAppFlow;
            const level1 = this.dataAppFlowChanged.find(
            (item) => item.level === "level1"
            );
            
            if (level1) {
                this.listEmailDeptManager = level1.managers.reduce((acc, manager) => {
                    acc.push(manager.email);
                    manager.deputies
                    .filter((deputy) => deputy.status === true)
                    .forEach((deputy) => acc.push(deputy.email));
                    return acc;
                }, []);
            }
        },
        async openAcceptDialog() {
            if (!this.editData) return;
            await this.fetchDataFlow();
            this.selectedItemForAccept = this.editData;
            this.confirmAcceptDialog = true;
        },
        async openDenyDialog() {
            if (!this.editData) return;
            
            this.denyReason = {
                vi: '',
                cn: ''
            }; await this.fetchDataFlow();
            this.confirmDenyDialog = true;
        },
        async openReturnDialog() {
            if (!this.editData) return;
            this.returnReason = {
                vi: "",
                cn: ""
            };
            this.returnToLevel = null;
            await this.fetchDataFlow();
            this.confirmReturnDialog = true;
        },
        async fetchDataFlow() {
            try {
                this.isLoadingFlow = true;
                
                let submitterInfo = this.activeUser;
                if (this.editData && this.editData.submitter) {
                    try {
                        const parsedSubmitter =
                        typeof this.editData.submitter === "string"
                        ? JSON.parse(this.editData.submitter)
                        : this.editData.submitter;
                        submitterInfo = {
                            empno: parsedSubmitter.empno || parsedSubmitter.empNo,
                            name: parsedSubmitter.name,
                            dept_sumt: parsedSubmitter.dept_sumt,
                            division: parsedSubmitter.high_dept || parsedSubmitter.division,
                            location: parsedSubmitter.location || this.activeUser.location,
                        };
                    } catch (e) {
                        console.error("Error parsing submitter info:", e);
                    }
                }
                
                const params = {
                    id: this.editData.id,
                    empno: submitterInfo.empno,
                    name: submitterInfo.name,
                    dept: submitterInfo.dept_sumt,
                    high_dept: submitterInfo.division,
                    app_code: "mpr",
                    location: submitterInfo.location,
                };
                params._t = new Date().getTime();
                const res = await this.$axios.post(
                this.api + "getDataFlowForItem",
                params
                );
                if (res.data) {
                    this.dataFlow = res.data.flow_data;
                    
                    this.flowDataTimestamp = new Date().getTime();
                    this.$emit("flow-data-fetched", {
                        id: this.editData.id,
                        data: res.data.flow_data,
                        
                    });
                } else {
                    console.warn("No flow data in response:", res.data);
                }
            } catch (error) {
                console.error("Error fetching flow data:", error);
                this.$store.dispatch(
                "showError",
                this.$t("Failed to get approval flow data")
                );
            } finally {
                this.isLoadingFlow = false;
            }
        },
        async confirmAccept() {
            try {
                const flowData = await this.getFlowDataForItem(this.selectedItemForAccept);
                
                // Format approval record based on current level
                const statusToLevel = {
                    waiting_dept: "dept",
                    waiting_div: "div", 
                    waiting_hr_rec_sf: "hr_rec_sf",
                    waiting_hr: "hr",
                    waiting_ad: "ad",
                    waiting_gm: "gm"
                };
                
                // Determine which status to use (amendment or original)
                const isAmendment = !!this.selectedItemForAccept.status_amendent;
                const currentStatus = isAmendment ?
                this.selectedItemForAccept.status_amendent :
                this.selectedItemForAccept.status;
                
                const levelKey = statusToLevel[currentStatus];
                
                // Parse existing approval records
                let approvalRecords = [];
                if (this.selectedItemForAccept.approval_record) {
                    try {
                        approvalRecords = JSON.parse(this.selectedItemForAccept.approval_record);
                        if (!Array.isArray(approvalRecords)) {
                            approvalRecords = [approvalRecords];
                        }
                    } catch (e) {
                        console.error("Error parsing approval records:", e);
                        approvalRecords = [];
                    }
                }
                
                // Check if this is dept level approval for new year recruitment
                const isActiveRct = this.selectedItemForAccept.activeRct == 1 || 
                this.selectedItemForAccept.activeRct === "1" || 
                this.selectedItemForAccept.activeRct === true;
                
                if (levelKey === "dept" && isActiveRct) {
                    // Create new record for new year recruitment at dept level
                    const newRecord = {
                        dept: "年度結算作業確認於新年度繼續招募，產生新單",
                        div: "",
                        hr_rec_sf: "",
                        hr: "",
                        ad: "",
                        gm: ""
                    };
                    approvalRecords.push(newRecord);
                } else {
                    // Update the last record for current level
                    const currentRecord = approvalRecords[approvalRecords.length - 1];
                    if (currentRecord && levelKey) {
                        currentRecord[levelKey] = this.form.approval_record || "";
                    }
                }
                
                const params = {
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: flowData,
                        id: this.selectedItemForAccept.id,
                        status: this.selectedItemForAccept.status,
                        status_amendent: this.selectedItemForAccept.status_amendent,
                        approval: this.selectedItemForAccept.approval,
                        approval_record: JSON.stringify(approvalRecords),
                        activeRct: this.form.newYearRecruitment,
                    },
                };
                
                // Add sub_position if user is HR manager  
                if (this.isHrManager) {
                    params.dataReq.sub_pos = this.form.sub_position || null;
                }
                
                const res = await this.$axios.post(this.api + "acceptRequest", params);
                
                if (res.data.success) {
                    this.$notify({
                        title: "Success",
                        text: isAmendment ?
                        this.$t("Amendment request accepted successfully") :
                        this.$t("Request accepted successfully"),
                        type: "success"
                    });
                    
                    this.confirmAcceptDialog = false;
                    this.selectedItemForAccept = null;
                    await this.$emit("requestUpdated");
                    this.dialog = false;
                }
                
            } catch (error) {
                console.error("Error accepting request:", error);
                this.$notify({
                    title: "Error", 
                    text: this.$t("Failed to accept request"),
                    type: "error"
                });
            }
        },
        
        async confirmDeny() {
            if (!this.denyReason.vi && !this.denyReason.cn) {
                this.$store.dispatch("showWarning", this.$t("Please provide a reason"));
                return;
            }
            
            try {
                // Use new translate API to fill missing language
                const langMap = { vi: 'vi', cn: 'zh-tw' };
                
                if (this.denyReason.cn && !this.denyReason.vi) {
                    const payload = {
                        text: this.denyReason.cn,
                        targetLangs: ['vi'],
                        model: "qwen3-8b",
                        source: "hrm"
                    };
                    const res = await this.$axios.post("http://gmo021.cansportsvg.com:49200/api/translate", payload, { headers: { "Content-Type": "application/json" } });
                    if (res.status === 200 && res.data && res.data.data && res.data.data.translation) {
                        this.denyReason.vi = res.data.data.translation['vi'] || this.denyReason.cn;
                    }
                } else if (this.denyReason.vi && !this.denyReason.cn) {
                    const payload = {
                        text: this.denyReason.vi,
                        targetLangs: ['zh-tw'],
                        model: "qwen3-8b",
                        source: "hrm"
                    };
                    const res = await this.$axios.post("http://gmo021.cansportsvg.com:49200/api/translate", payload, { headers: { "Content-Type": "application/json" } });
                    if (res.status === 200 && res.data && res.data.data && res.data.data.translation) {
                        this.denyReason.cn = res.data.data.translation['zh-tw'] || this.denyReason.vi;
                    }
                }
                
                const response = await this.$axios.post(this.api + "denyRequest", {
                    id: this.editData.id,
                    reason: JSON.stringify(this.denyReason),
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: this.dataFlow,
                        id: this.editData.id,
                        status: this.editData.status,
                        status_amendent: this.editData.status_amendent,
                        approval: this.editData.approval,
                    },
                });
                
                if (response.data.success) {
                    this.$notify({
                        title: "Success",
                        text: this.$t("Request denied successfully"),
                        type: "success"
                    });
                    this.confirmDenyDialog = false;
                    this.$emit("requestUpdated");
                    this.dialog = false;
                }
            } catch (error) {
                console.error("Error denying request:", error);
                this.$notify({
                    title: "Error",
                    text: this.$t("Failed to deny request"),
                    type: "error"
                });
            }
        },
        async confirmReturn() {
            if (!this.returnReason.vi && !this.returnReason.cn) {
                this.$store.dispatch("showWarning", this.$t("Please provide a reason"));
                return;
            }
            
            try {
                if (this.returnReason.cn && !this.returnReason.vi) {
                    const payload = {
                        text: this.returnReason.cn,
                        targetLangs: ['vi'],
                        model: "qwen3-8b",
                        source: "hrm"
                    };
                    const res = await this.$axios.post("http://gmo021.cansportsvg.com:49200/api/translate", payload, { headers: { "Content-Type": "application/json" } });
                    if (res.status === 200 && res.data && res.data.data && res.data.data.translation) {
                        this.returnReason.vi = res.data.data.translation['vi'] || this.returnReason.cn;
                    }
                } else if (this.returnReason.vi && !this.returnReason.cn) {
                    const payload = {
                        text: this.returnReason.vi,
                        targetLangs: ['zh-tw'],
                        model: "qwen3-8b",
                        source: "hrm"
                    };
                    const res = await this.$axios.post("http://gmo021.cansportsvg.com:49200/api/translate", payload, { headers: { "Content-Type": "application/json" } });
                    if (res.status === 200 && res.data && res.data.data && res.data.data.translation) {
                        this.returnReason.cn = res.data.data.translation['zh-tw'] || this.returnReason.vi;
                    }
                }
                
                // Send return request with both languages
                const response = await this.$axios.post(this.api + "returnRequest", {
                    id: this.editData.id,
                    reason: JSON.stringify(this.returnReason),
                    returnToLevel: "waiting_dept",
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: this.dataFlow,
                        id: this.editData.id,
                        status: this.editData.status,
                        status_amendent: this.editData.status_amendent,
                        approval: this.editData.approval,
                    },
                });
                
                if (response.data.success) {
                    this.$notify({
                        title: "Success",
                        text: this.$t("Request returned successfully"),
                        type: "success"
                    });
                    this.confirmReturnDialog = false;
                    this.$emit("requestUpdated");
                    this.dialog = false;
                }
            } catch (error) {
                console.error("Error returning request:", error);
                this.$notify({
                    title: "Error",
                    text: this.$t("Failed to return request"),
                    type: "error"
                });
            }
        },
        confirmSubmit() {
            this.confirmSubmitDialog = false;
            this.saveDB();
        },
        generateReqId() {
            if (!this.api) {
                const now = new Date();
                const year = now.getFullYear().toString().slice(-2);
                const month = (now.getMonth() + 1).toString().padStart(2, "0");
                return Promise.resolve(`HR-MPR00-${year}${month}-0001`);
            }
            
            const now = new Date();
            const year = now.getFullYear().toString().slice(-2);
            const month = (now.getMonth() + 1).toString().padStart(2, "0");
            
            return this.$axios.get(`${this.api}getLatestReqId`, { params: { year, month, }, })
            .then((response) => {
                const latestNumber = response.data.latestNumber || 0;
                const nextNumber = (latestNumber).toString().padStart(4, "0");
                return `HR-MPR00-${year}${month}-${nextNumber}`;
            })
            .catch((error) => {
                console.error("Error getting latest req_id:", error);
                return `HR-MPR00-${year}${month}-0001`;
            });
        },
        generateReqIdChange() {
            if (!this.api) {
                return Promise.reject('API URL not defined');
            }
            
            const now = new Date();
            const year = now.getFullYear().toString().slice(-2);
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            
            return this.$axios
            .get(`${this.api}getLatestReqIdChange`, {
                params: {
                    year: year,
                    month: month
                }
            })
            .then((response) => {
                const latestNumber = response.data.latestNumber || 0;
                const nextNumber = (latestNumber + 1).toString().padStart(4, '0');
                return `HR-MPRA0-${year}${month}-${nextNumber}`;
            })
            .catch((error) => {
                console.error('Error generating change request ID:', error);
                return `HR-MPRA0-${year}${month}-0001`;
            });
        },
        updateFormErrors() {
            this.formErrors = {
                ...this.formErrors,
                jobDesc: this.form.pos === 1 && !this.form.attachments.jobDesc,
                dimissionList:
                this.form.recruitment.reason === 2 &&
                !this.form.attachments.dimissionList,
            };
        },
        handleSpecializationInput(value) {
            if (
            !this.form.specialization ||
            typeof this.form.specialization === "string"
            ) {
                this.form.specialization = {
                    [this.$i18n.locale]: value,
                };
            } else {
                this.form.specialization = {
                    ...this.form.specialization,
                    [this.$i18n.locale]: value,
                };
            }
        },
        handleReasonChange(reasonId) {
            if (reasonId === 3) {
                this.form.recruitment.oldempno = "";
                if (!this.form.recruitment.isOther_reason) {
                    this.form.recruitment.isOther_reason = { vi: "", en: "", cn: "" };
                }
                // Không clear otherReasonSelected
                // Không xóa file dimissionList
                return;
            }
            
            // Nếu chọn loại khác (không phải Other) -> clear các trường liên quan đến Other
            this.form.recruitment.otherReasonSelected = '';
            this.form.recruitment.oldempno = "";
            this.form.recruitment.isOther_reason = {
                vi: "",
                en: "",
                cn: ""
            };
            
            // Handle specific logic for replacement (id === 2)
            if (reasonId === 2) { // Replacement ID
                this.filePreview.dimissionList = !!this.form.attachments.dimissionList;
            } else {
                this.clearFile("dimissionList");
            }
        },
        getApprovalStatusColor(status) {
            const normalizedStatus = status.toLowerCase();
            switch (normalizedStatus) {
                case "waiting":
                return "warning";
                case "approved":
                return "success";
                case "rejected":
                return "error";
                case "processing":
                return "info";
                case "returned":
                return "amber";
                default:
                return "grey";
            }
        },
        getApprovalStatusText(status) {
            const normalizedStatus = status.toLowerCase();
            switch (normalizedStatus) {
                case "waiting":
                return this.$t("Waiting for approval");
                case "approved":
                return this.$t("Approved");
                case "rejected":
                return this.$t("Rejected");
                case "processing":
                return this.$t("Processing");
                case "returned":
                return this.$t("Returned");
                default:
                return this.$t("Unknown status");
            }
        },
        async getFlowDataForItem(item) {
            try {
                const params = {
                    id: item.id,
                    empno: this.activeUser.empno,
                    name: this.activeUser.name,
                    dept: this.activeUser.dept_sumt,
                    high_dept: this.activeUser.division,
                    app_code: "mpr",
                    location: this.activeUser.location,
                };
                const res = await this.$axios.post(
                this.api + "getDataFlowForItem",
                params
                );
                return res.data.flow_data || [];
            } catch (error) {
                console.error("Error fetching flow data for item:", error);
                return [];
            }
        },
    },
    computed: {
        isNewYearProcessing() {
            // 1. Đang hiện khung radio
            if (this.showNewYearSection) return true;
            
            // 2. Đã có dữ liệu activeRct (0: Hủy, 1: Tiếp tục)
            if (this.editData) {
                const val = this.editData.activeRct;
                // Kiểm tra kỹ val có tồn tại không (chấp nhận cả số 0)
                if (val !== null && val !== undefined && val !== '') {
                    return true;
                }
            }
            return false;
        },
        noteText() {
            const oldId = this.form.req_id_oldyear || (this.editData && this.editData.req_id_oldyear);
            if (oldId) {
                return `${this.$t("Based on")} ${oldId} ${this.$t("continue recruitment based on conditions")}`;
            }
            return this.editData?.note || ""; 
        },
        disableNewYearRecruitment() {
            if (!this.editData) return false;
            if (this.editData.req_id_oldyear && this.editData.req_id_oldyear.trim() !== '') {
                return true;
            }
            if ( this.editData.activeRct !== null) {
                return true;
            }
            return false;
        },
        showNewYearSection() {
            // 1. Kiểm tra cơ bản
            if (!this.editData || !this.activeUser?.empno) return false;
            
            // 2. Ẩn nếu là đơn con
            if (this.editData.req_id_oldyear && this.editData.req_id_oldyear.trim() !== '') {
                return false;
            }
            
            try {
                // 3. Logic hiển thị dựa trên hasNewYearData
                const hasNewYearData = this.editData.activeRct !== null && 
                this.editData.activeRct !== undefined && 
                this.editData.activeRct !== '';
                
                if (!hasNewYearData) {
                    // Nếu chưa chọn: Chỉ hiện khi đơn gốc đã hoàn thành
                    const validStatuses = ['completed'];
                    if (!validStatuses.includes(this.editData.status) && !validStatuses.includes(this.editData.status_amendent)) {
                        return false;
                    }
                }
                
                // 4. Lấy thông tin User hiện tại
                const currentUserEmpno = String(this.activeUser.empno).trim();
                
                // --- BỎ ĐOẠN CHẶN SUBMITTER CŨ ---
                // Lý do: Trong trường hợp Submitter cũng là Sếp (Level 1/2), họ cần thấy để thao tác/xem lại.
                // Việc disable chỉnh sửa đã được xử lý ở computed "disableNewYearRecruitment".
                
                /* const submitter = typeof this.editData.submitter === 'string' ? 
                JSON.parse(this.editData.submitter) : this.editData.submitter;
                const submitterEmpno = submitter.empno ? String(submitter.empno).trim() : "";
                if (currentUserEmpno === submitterEmpno) return false; 
                */
                
                // 5. Kiểm tra DataFlow (Quan trọng nhất)
                if (this.dataFlow && Array.isArray(this.dataFlow) && this.dataFlow.length > 0) {
                    // Lấy 2 cấp đầu tiên
                    const firstTwoLevels = this.dataFlow.slice(0, 2); 
                    
                    const isInFirstTwoLevels = firstTwoLevels.some(level => 
                    level.managers && level.managers.some(manager => 
                    // Kiểm tra Manager chính
                    String(manager.empno).trim() === currentUserEmpno || 
                    // Kiểm tra Deputy (Người ủy quyền)
                    (manager.deputies && manager.deputies.some(d => String(d.empno).trim() === currentUserEmpno && d.status === true))
                    )
                    );
                    
                    return isInFirstTwoLevels;
                }
                
                return false;
                
            } catch (e) {
                console.error('Error in showNewYearSection:', e);
                return false;
            }
        },
        reasonSelectionRule() {
            return (value) => {
                const hasRadio = !!(this.form?.recruitment?.reason);
                const hasAuto = !!(this.form?.recruitment?.otherReasonSelected);
                return (hasRadio || hasAuto) || this.$t("This field is required");
            };
        },
        otherReasonItems() {
            if (!Array.isArray(this.otherReason)) return [];
            return this.otherReason.map(r => {
                try {
                    const parsed = typeof r.reason_name === "string" ? JSON.parse(r.reason_name) : r.reason_name;
                    const text = parsed[this.$i18n.locale] || parsed.en || parsed.vi || parsed.cn || "";
                    return { id: r.id, text };
                } catch (e) {
                    return { id: r.id, text: r.reason_name || String(r.id) };
                }
            });
        },
        showLevelField() {
            return this.form.pos === 3;
        },
        filteredUnitItems() {
            if (!this.unitNames || !Array.isArray(this.unitNames)) return [];
            if (!this.user.div || !this.user.dept) return [];
            
            const filteredUnits = this.unitNames.filter(unit => {
                const dept = this.departmentData.find(d => d.department === this.user.div);
                if (!dept) return false;
                
                const deptUnit = dept.units.find(u => u.name === this.user.dept);
                if (!deptUnit) return false;
                
                // Kiểm tra xem unit có thuộc về dept này không
                return deptUnit.sub_units.includes(unit.dept_code);
            });
            
            return filteredUnits.map(unit => {
                let displayName = '';
                if (unit.name) {
                    try {
                        const parsedName = JSON.parse(unit.name);
                        displayName = parsedName[this.$i18n.locale] || parsedName.en || unit.name;
                    } catch (e) {
                        displayName = unit.name;
                    }
                }
                
                return {
                    dept_code: unit.dept_code,
                    display: `${unit.dept_code} - ${displayName}`,
                    name: displayName
                };
            });
        },
        isDenyReasonValid() {
            if (!this.denyReason) return false;
            return Boolean(
            (this.denyReason.vi && this.denyReason.vi.trim()) ||
            (this.denyReason.cn && this.denyReason.cn.trim())
            );
        },
        
        isReturnReasonValid() {
            if (!this.returnReason) return false;
            return Boolean(
            (this.returnReason.vi && this.returnReason.vi.trim()) ||
            (this.returnReason.cn && this.returnReason.cn.trim())
            );
        },
        getAppCode() {
            // Khi changeReason.id = 2 (hủy đơn), mặc định dùng mpr_flow2
            if (this.form.changeReason && this.form.changeReason.id === 2) {
                return 'mpr_flow2';
            }
            
            // Logic ban đầu: dựa trên suppType
            return this.form.suppType === 1 ? 'mpr_flow2' : 'mpr';
        },
        
        // Update flowKey to include changeReason for proper reactivity
        flowKey() {
            const changeReasonId = this.form.changeReason?.id || '';
            const suppType = this.form.suppType || '';
            const empno = this.flowEmpno || '';
            const locale = this.$i18n ? this.$i18n.locale : '';
            // include locale so component remounts when language changes
            return `${this.getAppCode}-${empno}-${suppType}-${changeReasonId}-${locale}`;
        },
        getChangeQuantityHint() {
            const minRecruitedQty = this.editData?.recruited || 0;
            const maxRequestQty = this.form.quantity || 0;
            
            return `${this.$t('Min')}: ${minRecruitedQty} (${this.$t('Change quantity cannot be less than recruited quantity')}), ${this.$t('Max')}: ${maxRequestQty} (${this.$t('Change quantity cannot be greater than or equal to request quantity')})`;
        },
        minQuantityRule() {
            return (value) => {
                if (!value) return true;
                const qty = parseInt(value);
                const originalQty = parseInt(this.editData?.recruited || 0);
                if (qty < originalQty) {
                    return this.$t('Change quantity cannot be less than recruited quantity');
                }
                return true;
            };
        },
        maxQuantityRule() {
            return (value) => {
                if (!value) return true;
                const changeQty = parseInt(value);
                const requestQty = parseInt(this.form.quantity || 0);
                if (changeQty >= requestQty) {
                    return this.$t('Change quantity cannot be greater than or equal to request quantity');
                }
                return true;
            };
        },
        isLevel1Approved() {
            if (!this.editData?.approval) return false;
            try {
                const approvalArr = typeof this.editData.approval === "string"
                ? JSON.parse(this.editData.approval)
                : this.editData.approval;
                
                const level1 = approvalArr.find(lv => lv.level === 1);
                return level1?.status === "approved";
            } catch (e) {
                console.error("Error checking level 1 approval status:", e);
                return false;
            }
        },
        
        showChangeQuantityField() {
            if (!this.form.changeReason || this.form.changeReason.id === 3) {
                return false;
            }
            return this.form.changeReason.id === 1;
        },
        displayReqIdChange() {
            if (!this.isChange) return '';
            return this.form.req_id_change || '';
        },
        showAmendmentSection() {
            if (this.isAmendmentReturned) {
                const submitter = JSON.parse(this.editData.submitter);
                return submitter.empno === this.activeUser.empno;
            }
            
            // Các trường hợp khác giữ nguyên
            return this.isChange || (this.isEdit && this.editData?.req_id_change && this.editData.req_id_change.trim() !== '');
        },
        item_req_id() {
            return this.itemsReqId.map((item) => {
                return {
                    id: item.req_id,
                    text: item.req_id,
                };
            });
        },
        formattedChangeReasons() {
            if (!Array.isArray(this.item_changeReason)) return [];
            return this.item_changeReason.map(item => {
                try {
                    const reasonObj = JSON.parse(item.reason || "{}");
                    const text = reasonObj[this.$i18n.locale] || reasonObj.en || Object.values(reasonObj)[0] || item.reason || "";
                    return {
                        id: item.id,
                        text,            // must be a string for display
                        raw: reasonObj   // keep raw if needed
                    };
                } catch (e) {
                    // fallback: use raw reason string
                    return {
                        id: item.id,
                        text: item.reason || String(item.id)
                    };
                }
            });
        },
        showFlowSign() {
            return this.form.suppType && this.flow_sign && this.flow_sign.length > 0;
        },
        getCurrentFlowSign() {
            const selectedFlow = this.flow_sign.find(
            (f) => f.id === parseInt(this.form.suppType)
            );
            if (!selectedFlow) return "";
            
            return selectedFlow[this.$i18n.locale] || selectedFlow.en;
        },
        disAmendent() {
            if (this.isEdit && this.editData.req_id_change) {
                const submitter = typeof this.editData.submitter === "string" ? JSON.parse(this.editData.submitter) : this.editData.submitter;
                const approvalData = typeof this.editData.approval === "string" ? JSON.parse(this.editData.approval) : this.editData.approval;
                const hasReturnReason = approvalData.some(level => level.return_reason);
                if (hasReturnReason) {
                    return submitter.empno !== this.activeUser.empno;
                }
            }
            if (this.isEdit) return true;
            if (this.isChange || !this.editData.req_id_change) return false;
            
            return !!this.editData && !this.isCreating;
        },
        formDisabled() {
            if(this.editData && this.editData.approval && 
            JSON.parse(this.editData.approval).some(level => level.status === 'returned')) {
                return false;
            }
            if (this.editData && !this.editData.req_id_change && 
            JSON.parse(this.editData.submitter).empno === this.activeUser.empno) {
                return true; 
            }
            
            if(this.isEdit) return true;
            if (this.isChange) return false;
            return !!this.editData && !this.isCreating;
        },
        disableSaveButton() {
            // 1. ƯU TIÊN CAO NHẤT: Kiểm tra New Year Recruitment
            // Nếu đang hiển thị form này, chỉ quan tâm việc đã chọn Radio hay chưa.
            // Không cần validate các trường khác của form gốc.
            if (this.showNewYearSection) {
                const val = this.form.newYearRecruitment;
                // Nếu val là rỗng, null hoặc undefined -> Disable (true)
                // Nếu val là 0 hoặc 1 -> Enable (false)
                return (val === "" || val === null || val === undefined);
            }
            
            // Biến dùng chung cho validation bên dưới
            const reasonSelected = !!(this.form.recruitment && (this.form.recruitment.reason || this.form.recruitment.otherReasonSelected));
            
            // 2. Trường hợp là Change Request (Đơn thay đổi)
            if (this.isChange) {
                const baseValidation =
                !this.form.pos ||
                !this.form.suppType ||
                !this.form.quantity ||
                !this.user.unit ||
                !this.form.educationLevel ||
                !this.form.workExperience ||
                !reasonSelected ||
                !this.form.specialization ||
                !this.form.jobDesc ||
                !this.form.safetyLevels;
                
                if (this.form.pos === 1) {
                    return baseValidation;
                }
                return (baseValidation ||
                !(this.form.computerSkills && this.form.computerSkills.length > 0) ||
                !(this.form.either || this.form.english || this.form.chinese));
            }
            if (this.editData && this.editData.approval &&
            JSON.parse(this.editData.approval).some(level => level.status === 'returned')) {
                return false; // Enable nút để sửa và gửi lại
            }
            if (this.editData && !this.editData.req_id_change &&
            JSON.parse(this.editData.submitter).empno === this.activeUser.empno) {
                return true; // Disable
            }
            if (this.isEdit) return true;
            const baseValidation =
            !this.form.pos ||
            !this.form.suppType ||
            !this.form.quantity ||
            !this.user.unit ||
            !this.form.educationLevel ||
            !this.form.workExperience ||
            !reasonSelected ||
            !this.form.specialization ||
            !this.form.jobDesc ||
            !this.form.safetyLevels;
            
            if (this.form.pos === 1) {
                return baseValidation;
            }
            
            return (baseValidation ||
            !(this.form.computerSkills && this.form.computerSkills.length > 0) ||
            !(this.form.either || this.form.english || this.form.chinese));
        },
        isCreating() {
            return !this.editData;
        },
        displayReqId() {
            if (this.isCreating) return this.form.req_id;
            return this.form.req_id || (this.editData && this.editData.req_id) || "";
        },
        
        displayName() {
            if (this.isCreating) return this.activeUser.name;
            if (this.editData && this.editData.submitter) {
                try {
                    const submitter =
                    typeof this.editData.submitter === "string"
                    ? JSON.parse(this.editData.submitter)
                    : this.editData.submitter;
                    return submitter.name || "";
                } catch (e) {
                    return "";
                }
            }
            return "";
        },
        keyInDate() {
            if (this.isCreating) return this.form.keyInDate;
            if (this.editData && this.editData.key_in_date) {
                return this.editData.key_in_date || "";
            }
            return "";
        },
        displayDept() {
            if (this.isCreating) return this.activeUser.dept_sumt;
            if (this.editData && this.editData.submitter) {
                try {
                    const submitter =
                    typeof this.editData.submitter === "string"
                    ? JSON.parse(this.editData.submitter)
                    : this.editData.submitter;
                    return submitter.dept_sumt || "";
                } catch (e) {
                    return "";
                }
            }
            return "";
        },
        showSaveButton() {
            if (this.showNewYearSection) {
                // Kiểm tra xem đã có dữ liệu activeRct chưa
                const hasNewYearData = this.editData && 
                this.editData.activeRct !== null && 
                this.editData.activeRct !== undefined && 
                this.editData.activeRct !== '';
                
                // Nếu ĐÃ CÓ dữ liệu (đã submit rồi) -> ẨN nút Submit (return false)
                // Để nhường chỗ cho các nút Accept/Deny hiện ra
                if (hasNewYearData) {
                    return false;
                }
                
                // Nếu CHƯA CÓ dữ liệu (lần đầu vào chọn) -> HIỆN nút Submit
                return true;
            }
            if (!this.editData) {
                return true;
            }
            if (this.isChange) {
                return true;
            }
            if (this.editData.approval) {
                try {
                    const approvalData = JSON.parse(this.editData.approval);
                    const hasReturnStatus = approvalData.some(level => level.status === 'returned');
                    if (hasReturnStatus) {
                        const submitter = JSON.parse(this.editData.submitter);
                        return submitter.empno === this.activeUser.empno;
                    }
                } catch (e) {
                    console.error('Error parsing approval data:', e);
                }
            }
            
            if (!this.editData.req_id_change) {
                const submitter = typeof this.editData.submitter === "string" ?
                JSON.parse(this.editData.submitter) : this.editData.submitter;
                return submitter.empno === this.activeUser.empno;
            }
            return false;
        },
        
        dialog: {
            get() {
                return this.value;
            },
            set(val) {
                this.$emit("input", val);
            },
        },
        workExpItems() {
            return this.workExp.map((exp) => {
                const expName = JSON.parse(exp.exp_name);
                return {
                    id: exp.id,
                    name: expName[this.$i18n.locale] || expName.en,
                };
            });
        },
        posItems() {
            
            const positions = this.pos.filter((type) => {
                if (this.form.suppType === 1) {
                    return true; // Include all positions
                } else if (this.form.suppType === 2) {
                    return type.id !== 1;
                }
                return true; // Default case, include all positions
            });
            
            return positions.map((type) => {
                try {
                    const typeName = JSON.parse(type.type_name.trim());
                    return {
                        id: type.id,
                        name: typeName[this.$i18n.locale] || typeName.en,
                    };
                } catch (e) {
                    console.error("Error parsing type name:", type.type_name, e);
                    return { id: type.id, name: `Type ${type.id}` };
                }
            });
        },
        educationLevelItems() {
            return this.eduReq.map((edu) => {
                const eduName = JSON.parse(edu.edu_name);
                return {
                    id: edu.id,
                    name: eduName[this.$i18n.locale] || eduName.en,
                };
            });
        },
        sub_positionItems() {
            if (!this.subItems) return [];
            
            
            return this.subItems
            .filter((type) => type.id >= 1 && type.id <= 9)
            .map((type) => {
                const sub_name = JSON.parse(type.sub_name.trim());
                return {
                    id: type.id,
                    name: sub_name[this.$i18n.locale] || sub_name.en,
                };
            });
            // return [];
        },
        suppTypeItems() {
            return this.iSuppType.map((type) => {
                return {
                    id: type.id,
                    name: type[this.$i18n.locale] || type.en,
                };
            });
        },
        safetyLevels() {
            if (!this.parsedSafetyLevels || this.parsedSafetyLevels.length === 0) {
                const levels = this.wps
                .map((item) => {
                    try {
                        const wpsParsed = JSON.parse(item.wps_name);
                        const fullData = {
                            id: item.id,
                            name: wpsParsed,
                        };
                        return {
                            id: item.id,
                            name: wpsParsed[this.$i18n.locale] || wpsParsed.en,
                            fullData: fullData,
                        };
                    } catch (e) {
                        console.error("Error parsing WPS data:", e);
                        return null;
                    }
                })
                .filter(Boolean);
                return levels;
            }
            return this.parsedSafetyLevels;
        },
        canApproveItem() {
            if (!this.editData || !this.activeUser) return false;
            
            const originalStatus = this.editData.status;
            const amendmentStatus = this.editData.status_amendent;
            
            try {
                const approvalData = this.editData.approval ? JSON.parse(this.editData.approval) : [];
                
                // Kiểm tra nếu có bất kỳ level nào có status là "returned"
                const hasReturnedStatus = approvalData.some(level => level.status === "returned");
                if (hasReturnedStatus) return false;
                
                if ((originalStatus === 'completed' || originalStatus === 'declined') &&
                (!amendmentStatus || amendmentStatus === 'completed' || amendmentStatus === 'declined')) {
                    return false;
                }
                
                if (!this.dataFlow || !Array.isArray(this.dataFlow)) return false;
                
                const statusToLevel = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                let currentLevel;
                
                if (amendmentStatus &&
                amendmentStatus !== 'completed' &&
                amendmentStatus !== 'declined') {
                    currentLevel = statusToLevel[amendmentStatus];
                } else {
                    currentLevel = statusToLevel[originalStatus];
                }
                
                if (currentLevel === undefined) return false;
                
                const currentFlow = this.dataFlow[currentLevel];
                if (!currentFlow?.managers) return false;
                
                return currentFlow.managers.some(manager => {
                    if (manager.empno === this.activeUser.empno) return true;
                    
                    return manager.deputies?.some(deputy =>
                    deputy.empno === this.activeUser.empno &&
                    deputy.status === true
                    );
                });
                
            } catch (error) {
                console.error("Error in canApproveItem:", error);
                return false;
            }
        },
        canReturn() {
            return this.returnToLevel && this.returnReason.trim();
        },
        getAvailableReturnLevels() {
            if (!this.editData) return [];
            
            const currentStatus = this.editData.status;
            const levels = [
            { value: "waiting_dept", text: this.$t("Department Level") },
            { value: "waiting_div", text: this.$t("Division Level") },
            { value: "waiting_hr_rec_sf", text: this.$t("HR Rec SF Level") },
            { value: "waiting_hr", text: this.$t("HR Level") },
            { value: "waiting_ad", text: this.$t("AD Level") },
            { value: "waiting_gm", text: this.$t("GM Level") },
            ];
            
            const statusLevels = {
                waiting_dept: 1,
                waiting_div: 2,
                waiting_hr_rec_sf: 3,
                waiting_hr: 4,
                waiting_ad: 5,
                waiting_gm: 6,
            };
            const currentLevel = statusLevels[currentStatus] || 0;
            
            return levels.filter((level) => statusLevels[level.value] < currentLevel);
        },
        getJobDescColWidth() {
            let width = 10;
            return width;
        },
        hasEitherSelected() {
            return this.form.either;
        },
        flowKey() {
            return `flow-${this.isEdit ? "edit" : "new"}-${this.isEdit && this.editData ? this.editData.id : "none"
            }-${this.flowEmpno}-${this.form.suppType}`;
        },
        flowEmpno() {
            if (this.isEdit && this.editData && this.editData.submitter) {
                try {
                    const submiterInfo =
                    typeof this.editData.submitter === "string"
                    ? JSON.parse(this.editData.submitter)
                    : this.editData.submitter;
                    return submiterInfo.empno || this.activeUser.empno;
                } catch (error) {
                    console.error("Error parsing submitter info:", error);
                    return this.activeUser.empno;
                }
            }
            return this.activeUser ? this.activeUser.empno : "";
        },
        
        flowLocation() {
            if (this.isEdit && this.editData && this.editData.submitter) {
                try {
                    const submiterInfo =
                    typeof this.editData.submitter === "string"
                    ? JSON.parse(this.editData.submitter)
                    : this.editData.submitter;
                    return submiterInfo.location || this.activeUser.location;
                } catch (error) {
                    console.error("Error parsing submitter location:", error);
                    return this.activeUser.location;
                }
            }
            return this.activeUser ? this.activeUser.location : "";
        },
        localizedSpecialization: {
            get() {
                if (!this.form.specialization) return "";
                if (typeof this.form.specialization === "string")
                return this.form.specialization;
                return this.form.specialization[this.$i18n.locale] || "";
            },
            set(value) {
                if (
                !this.form.specialization ||
                typeof this.form.specialization === "string"
                ) {
                    this.form.specialization = {
                        [this.$i18n.locale]: value,
                    };
                } else {
                    this.form.specialization = {
                        ...this.form.specialization,
                        [this.$i18n.locale]: value,
                    };
                }
            },
        },
        approvedHistory() {
            // Lấy lịch sử đã duyệt/reject/returned
            if (!this.editData || !this.editData.approval) return [];
            let approvalArr = [];
            try {
                approvalArr =
                typeof this.editData.approval === "string"
                ? JSON.parse(this.editData.approval)
                : this.editData.approval;
            } catch (e) {
                return [];
            }
            return approvalArr
            .filter((lv) => ["approved", "rejected"].includes(lv.status))
            .map((lv) => ({
                level: lv.level,
                type: this.$t(
                lv.type ? lv.type.charAt(0).toUpperCase() + lv.type.slice(1) : ""
                ),
                approver: lv.approver,
                status: lv.status,
                processed_at: lv.processed_at,
            }));
        },
        approvalStepperSteps() {
            if (!this.editData || !this.editData.approval) return [];
            
            try {
                let approvalArr = typeof this.editData.approval === "string" ?
                JSON.parse(this.editData.approval) : this.editData.approval;
                
                let approvalRecords = [];
                if (this.editData.approval_record) {
                    approvalRecords = JSON.parse(this.editData.approval_record);
                }
                
                const currentApprovalRecord = approvalRecords.length > 0 ?
                approvalRecords[approvalRecords.length - 1] : {};
                
                const levelNames = {
                    1: this.$t("Department Manager"),
                    2: this.$t("Division Manager"),
                    3: this.$t("HR Recruitment Staff"),
                    4: this.$t("HR Manager"),
                    5: this.$t("AD Manager"),
                    6: this.$t("GM Manager")
                };
                
                // Get translated approval records
                const translatedRecords = {};
                for (const key in currentApprovalRecord) {
                    const text = currentApprovalRecord[key];
                    if (text) {
                        // Check cache first
                        const cached = this.getCachedTranslation(text, this.$i18n.locale);
                        if (cached) {
                            translatedRecords[key] = cached;
                        } else {
                            // If not in cache, queue translation
                            this.translateApprovalRecord(text, this.$i18n.locale).then(translated => {
                                this.setCachedTranslation(text, this.$i18n.locale, translated);
                                translatedRecords[key] = translated;
                                // Force update to reflect changes
                                this.$forceUpdate();
                            });
                            // Use original text while waiting for translation
                            translatedRecords[key] = text;
                        }
                    }
                }
                
                return approvalArr
                .filter(lv => lv.status && ["waiting", "approved", "rejected"].includes(lv.status))
                .map(lv => {
                    const recordKey = lv.type || "";
                    const record = translatedRecords[recordKey] || "";
                    
                    const reasonDeniedText = this.getLocalizedFromMultiLang(lv.reason_denied);
                    const returnReasonText = this.getLocalizedFromMultiLang(lv.return_reason);
                    
                    return {
                        level: lv.level,
                        levelName: levelNames[lv.level] || lv.type || "",
                        approver: lv.approver,
                        status: lv.status,
                        processed_at: lv.processed_at,
                        reason_denied: reasonDeniedText,
                        return_reason: returnReasonText,
                        shouldShowError: lv.status === "rejected",
                        approval_record: record
                    };
                });
            } catch (e) {
                console.error("Error in approvalStepperSteps:", e);
                return [];
            }
        },
        isChangeRequest() {
            return this.editData;
        },
        dialogTitle() {
            if (this.isEdit) {
                return this.isChangeRequest
                ? this.$t("Edit request") + " - " + this.editData.req_id
                : this.$t("Change request");
            }
            return this.$t("Create new request");
        },
        showLanguageSkills() {
            return this.form.pos !== 1;
        },
        showComputerSkills() {
            return this.form.pos !== 1;
        },
        isHrManager() {
            try {
                if (!this.dataFlow || !Array.isArray(this.dataFlow)) return false;
                const empno = this.activeUser?.empno;
                if (!empno) return false;
                const currentStatus = this.editData?.status_amendent || this.editData?.status;
                const isHrLevel = ['waiting_hr_rec_sf', 'waiting_hr'].includes(currentStatus);
                const isHrUser = this.dataFlow.some(flow => {
                    if (!['hr_rec_sf', 'hr_manager'].includes(flow.lvl_code)) {
                        return false;
                    }
                    return flow.managers.some(manager => {
                        if (manager.empno === empno) {
                            return Array.isArray(manager.levels) &&
                            manager.levels.some(level => ['hr_rec_sf', 'hr_manager'].includes(level));
                        }
                        return Array.isArray(manager.deputies) &&
                        manager.deputies.some(d => d.empno === empno && d.status);
                    });
                });
                return isHrUser && isHrLevel;
                
            } catch (e) {
                console.error('isHrManager error:', e);
                return false;
            }
        },
        showLanguageAndComputerSkills() {
            if (!this.form.changeReason) return true;
            const id = this.form.changeReason.id;
            return ![1, 2, 4, 5].includes(id);
        },
    },
    mounted() {
        this.$nextTick(() => {
            const sessionUser = this.$session.get("mpr");
            if (sessionUser) {
                this.activeUser = {
                    empno: sessionUser.empno,
                    name: sessionUser.name,
                    dept_sumt: sessionUser.dept,
                    division: sessionUser.hight_dept,
                    location: sessionUser.location,
                    email: sessionUser.email,
                };
                this.getReq_id();
                this.getChangeReason();
                this.getDataModel().then(() => {
                    if (this.editData) {
                        this.populateForm(this.editData);
                    } else {
                        if (this.$props.user) {
                            this.user.div = this.$props.user.div || "";
                            this.user.dept = this.$props.user.dept || "";
                            this.user.unit = this.$props.user.unit || "";
                        }
                    }
                });
            }
        });
        if (!this.editData) {
            this.generateReqId().then((newReqId) => {
                this.form.req_id = newReqId;
            });
        }
    },
};
</script>

<i18n>
    {
        "en": {
            "Create new request": "Create new request",
            "Edit request": "Edit request",
            "Change request": "Change request",
            "General Requirements": "General Requirements",
            "Req ID": "Req ID",
            "Name": "Name",
            "Supp.Type": "Supply Type",
            "Division": "Division",
            "Dept": "Department",
            "Unit": "Unit",
            "Type Apply": "Position",
            "Level": "Level",
            "Quantity request": "Quantity",
            "Change Quantity": "Change Quantity",
            "Change Reason": "Change Reason",
            "Req.date": "",
            "Expansion of Staffing": "Expansion of Staffing",
            "Replacement": "Replacement",
            "Old empno": "Old empno",
            "Other reason": "Other reason",
            "Education Level": "Education Level",
            "Work Experience": "Work Experience",
            "Enter specialization requirements...": "Enter specialization requirements...",
            "Job Description": "Job Description",
            "Basic Job Description": "Basic Job Description",
            "Job description file": "Job Description File",
            "Dimission list file": "Dimission List File",
            "Dimission list file is required": "Dimission list file is required",
            "View": "View",
            "Skill Requirements": "Skill Requirements",
            "Foreign Language Proficiency": "Foreign Language Proficiency",
            "English:": "English:",
            "Chinese:": "Chinese:",
            "Either:": "Either:",
            "Computer Skills": "Computer Skills",
            "Word": "Word",
            "Excel": "Excel", 
            "Access": "Access",
            "PowerPoint": "PowerPoint",
            "Internet": "Internet",
            "Other Skills": "Other Skills",
            "Workplace Safety": "Workplace Safety",
            "Basic": "Basic",
            "Fair": "Fair",
            "Good": "Good",
            "Remark": "Remark",
            "Cancel": "Cancel",
            "Submit": "Submit",
            "Return": "Return",
            "Deny": "Deny", 
            "Accept": "Accept",
            "Confirm Action": "Confirm submit",
            "Are you sure you want to accept this request?": "Are you sure you want to accept this request?",
            "Are you sure you want to submit this request?": "Are you sure you want to submit this request?",
            "Deny Request": "Deny Request",
            "Reason": "Reason",
            "Please provide a reason for denying this request": "Please provide a reason for denying this request",
            "Confirm Denial": "Confirm Denial",
            "Return Request": "Return Request",
            "Return to level": "Return to Level",
            "Return reason": "Return Reason",
            "Confirm Return": "Confirm Return",
            "Unit Level": "Unit Level",
            "Department Level": "Department Level",
            "Division Level": "Division Level",
            "HR Level": "HR Level",
            "AD Level": "AD Level",
            "GM Level": "GM Level",
            "Language Changed": "Language Changed",
            "Interface language has been changed": "Interface language has been changed",
            "Please fill all required fields": "Please fill all required fields",
            "Request submitted successfully": "Request submitted successfully",
            "Failed to return request": "Failed to return request",
            "Not required": "Not required",
            "Sub Position": "Sub Position",
            "Professional qualifications": "Professional qualifications",
            "Approval History": "Approval History",
            "Level": "Level",
            "Type": "Type",
            "Approver": "Approver",
            "Status": "Status",
            "Date": "Date",
            "Waiting": "Waiting",
            "Waiting for approval": "Waiting for approval",
            "Approved": "Approved",
            "Rejected": "Rejected",
            "Processing": "Processing",
            "Returned": "Returned",
            "Unknown status": "Unknown status",
            "Cancel date":"Cancellation Date",
            "None":"None",
            "Unit Manager": "Unit Manager",
            "Department Manager": "Department Manager",
            "Division Manager": "Division Manager",
            "HR Manager": "HR Manager",
            "AD Manager": "AD Manager",
            "GM Manager": "GM Manager",
            "Translator": "Is this a demand for a translator?",
            "Confirm": "Confirm",
            "Previous Approval History": "Previous Approval History",
            "Current Approval History": "Current Approval History",
            "Amendment request": "Amendment request",
            "Change quantity": "Change quantity",
            "Change remark": "Change remark",
            "Change quantity cannot be less than original quantity": "Change quantity cannot be less than original quantity",
            "HR Recruitment Staff": "HR Recruitment Staff",
            "Change quantity cannot be less than recruited quantity": "Change quantity cannot be less than recruited quantity",
            "Change quantity cannot be greater than request quantity": "Change quantity cannot be greater than request quantity",
            "Change quantity cannot be greater than or equal to request quantity": "Change quantity cannot be greater than or equal to request quantity",
            "Min": "Min",
            "Max": "Max",
            "This field is required": "This field is required",
            "Delete": "Delete",
            "Please provide a Chinese reason": "Please provide a Chinese reason",
            "Approval record": "Approval record",
            "Please provide approval comments": "Please provide approval comments",
            "Return reason Chinese": "Return reason Chinese",
            "Please select Division and Department first": "Please select Division and Department first",
            "Please select":"Please select",
            "New Year Recruitment": "Please confirm whether the following replacement request should continue recruitment in the new year?",
            "Continue Recruitment": "Continue Recruitment",
            "Cancel Recruitment": "Cancel Recruitment",
            "Based on": "Based on",
            "continue recruitment based on conditions": "recruitment conditions to continue"
        },
        "vi": {
            "Create new request": "Tạo yêu cầu mới",
            "Edit request": "Chỉnh sửa yêu cầu", 
            "Change request": "Thay đổi yêu cầu",
            "General Requirements": "Yêu cầu chung",
            "Req ID": "Mã số đơn",
            "Name": "Tên",
            "Supp.Type": "Loại yêu cầu",
            "Division": "Cấp sở",
            "Dept": "Bộ phận",
            "Unit": "Đơn vị",
            "Type Apply": "Chức vụ",
            "Level": "Cấp bậc",
            "Quantity request": "Số lượng yêu cẩu",
            "Change Quantity": "Số lượng thay đổi",
            "Change Reason": "Lý do thay đổi",
            "Req.date": "Ngày yêu cầu công nhân - nhân viên đến",
            "Expansion of Staffing": "Mở rộng biên chế",
            "Replacement": "Bổ sung thôi việc",
            "Old empno": "MST",
            "Other reason": "Lý do khác",
            "Education Level": "Trình độ học vấn",
            "Work Experience": "Kinh nghiệm làm việc",
            "Enter specialization requirements...": "Nhập yêu cầu chuyên môn...",
            "Basic Job Description": "Mô tả công việc cơ bản",
            "Job description file": "Liệt kê công việc",
            "Dimission list file": "Mô tả danh sách thôi việc",
            "Dimission list file is required": "Yêu cầu file danh sách thôi việc",
            "View": "Xem",
            "Skill Requirements": "Yêu cầu về kỹ năng",
            "Foreign Language Proficiency": "Trình độ ngoại ngữ",
            "English:": "Tiếng Anh:",
            "Chinese:": "Tiếng Hoa:",
            "Either:": "Cả hai đều được:",
            "Computer Skills": "Trình độ vi tính",
            "Word": "Word",
            "Excel": "Excel", 
            "Access": "Access",
            "PowerPoint": "PowerPoint",
            "Internet": "Internet",
            "Other Skills": "Kỹ năng khác",
            "Workplace Safety": "An toàn lao động",
            "Basic": "Cơ bản",
            "Fair": "Khá",
            "Good": "Tốt",
            "Remark": "Ghi chú",
            "Cancel": "Hủy bỏ",
            "Submit": "Gửi",
            "Return": "Trả về",
            "Deny": "Từ chối", 
            "Accept": "Chấp nhận",
            "Confirm Action": "Xác nhận gửi yêu cầu",
            "Are you sure you want to accept this request?": "Bạn có chắc chắn muốn chấp nhận yêu cầu này?",
            "Are you sure you want to submit this request?": "Bạn có chắc chắn muốn gửi yêu cầu này không?",
            "Deny Request": "Từ chối yêu cầu",
            "Reason": "Lý do",
            "Please provide a reason for denying this request": "Vui lòng cung cấp lý do từ chối yêu cầu này",
            "Confirm Denial": "Xác nhận từ chối",
            "Return Request": "Trả về yêu cầu",
            "Return to level": "Trả về cấp bậc",
            "Return reason": "Lý do trả về",
            "Confirm Return": "Xác nhận trả về",
            "Unit Level": "Cấp đơn vị",
            "Department Level": "Cấp bộ phận",
            "Division Level": "Cấp sở",
            "HR Level": "Cấp nhân sự",
            "AD Level": "Cấp GM",
            "GM Level": "Tổng giám đốc",
            "Language Changed": "Đã thay đổi ngôn ngữ",
            "Interface language has been changed": "Ngôn ngữ giao diện đã được thay đổi",
            "Please fill all required fields": "Vui lòng điền đầy đủ các trường bắt buộc",
            "Request submitted successfully": "Gửi yêu cầu thành công",
            "Error submitting request": "Lỗi khi gửi yêu cầu",
            "Failed to get approval flow data": "Không thể lấy dữ liệu quy trình phê duyệt",
            "Request accepted successfully": "Chấp nhận yêu cầu thành công",
            "Failed to accept request": "Không thể chấp nhận yêu cầu",
            "Please provide a reason": "Vui lòng cung cấp lý do",
            "Request denied successfully": "Từ chối yêu cầu thành công",
            "Failed to deny request": "Không thể từ chối yêu cầu",
            "Request returned successfully": "Trả về yêu cầu thành công",
            "Failed to return request": "Không thể trả về yêu cầu",
            "Not required": "Không yêu cầu",
            "Sub Position": "Chức vụ phụ",
            "Professional qualifications": "Chuyên môn",
            "Approval History": "Lịch sử phê duyệt",
            "Level": "Cấp bậc",
            "Type": "Loại",
            "Approver": "Người phê duyệt",
            "Status": "Trạng thái",
            "Date": "Ngày",
            "Waiting": "Đang chờ",
            "Waiting for approval": "Đang chờ phê duyệt",
            "Approved": "Đã phê duyệt",
            "Rejected": "Đã từ chối",
            "Processing": "Đang xử lý",
            "Returned": "Đã trả về",
            "Unknown status": "Trạng thái không xác định",
            "Cancel date":"Chọn ngày hủy đơn",
            "None":"Không cần",
            "Unit Manager": "Chủ quản đơn vị",
            "Department Manager": "Chủ quản bộ phận",
            "Division Manager": "Chủ quản cấp sở",
            "HR Manager": "Chủ quản nhân sự",
            "AD Manager": "Chủ quản AD",
            "GM Manager": "Tổng giám đốc",
            "Translator": "Đây có phải là nhu cầu tuyển dụng phiên dịch  không?",
            "Confirm": "Xác nhận",
            "Previous Approval History": "Lịch sử phê duyệt",
            "Current Approval History": "Lịch sử phê duyệt hiện tại",
            "Amendment request": "Yêu cầu sửa đổi",
            "Change quantity": "Thay đổi số lượng",
            "Change remark": "Ghi chú thay đổi",
            "Change quantity cannot be less than original quantity": "Số lượng thay đổi không được nhỏ hơn số lượng ban đầu",
            "HR Recruitment Staff": "Nhân viên tuyển dụng",
            "Change quantity cannot be less than recruited quantity": "Số lượng thay đổi không được nhỏ hơn số lượng đã tuyển",
            "Change quantity cannot be greater than request quantity": "Số lượng thay đổi không được lớn hơn số lượng yêu cầu",
            "Change quantity cannot be greater than or equal to request quantity": "Số lượng thay đổi không được lớn hơn hoặc bằng số lượng yêu cầu",
            "Min": "Tối thiểu",
            "Max": "Tối đa",
            "This field is required": "Trường này là bắt buộc",
            "Delete": "Xóa",
            "Please provide a Chinese reason": "Vui lòng cung cấp lý do bằng tiếng Hoa",
            "Approval record": "Lịch sử phê duyệt",
            "Please provide approval comments": "Vui lòng cung cấp nhận xét phê duyệt",
            "Return reason Chinese": "Lý do trả về bằng tiếng Hoa",
            "Please select Division and Department first": "Vui lòng chọn Sở và Bộ phận trước",
            "Please select":"Vui lòng chọn",
            "New Year Recruitment": "Vui lòng xác nhận xem đơn bổ sung dưới đây có tiếp tục tuyển dụng trong năm mới hay không?",
            "Continue Recruitment": "Tiếp tục tuyển dụng",
            "Cancel Recruitment": "Hủy tuyển dụng",
            "Based on": "Dựa theo đơn",
            "continue recruitment based on conditions": "để tiếp tục tuyển dụng theo điều kiện cũ"
        },
        "cn": {
            "Create new request": "创建新请求",
            "Edit request": "编辑请求",
            "Change request": "更改请求",
            "General Requirements": "一般要求",
            "Req ID": "请求编号",
            "Name": "姓名",
            "Supp.Type": "增補類型",
            "Division": "處級",
            "Dept": "部門",
            "Unit": "申請單位",
            "Type Apply": "申請職位",
            "Level": "職級",
            "Quantity request": "需求人數",
            "Change Quantity": "更改数量",
            "Change Reason": "更改原因",
            "Req.date": "預計(需求)報到日",
            "Expansion of Staffing": "擴大編制",
            "Replacement": "離職補充",
            "Old empno": "工號",
            "Other reason": "其他原因",
            "Education Level": "教育程度",
            "Work Experience": "經驗",
            "Enter specialization requirements...": "输入专业要求...",
            "Job Description": "工作描述",
            "Basic Job Description": "基本工作描述",
            "Job description file": "職務說明書",
            "Dimission list file": "上傳離職名單",
            "Dimission list file is required": "需要上傳離職名單",
            "View": "查看",
            "Skill Requirements": "技能要求",
            "Foreign Language Proficiency": "語言能力",
            "English:": "英文：",
            "Chinese:": "中文",
            "Either:": "任一都可:",
            "Computer Skills": "電腦技能",
            "Word": "Word",
            "Excel": "Excel", 
            "Access": "Access",
            "PowerPoint": "PowerPoint",
            "Internet": "互联网",
            "Other Skills": "其他技能",
            "Workplace Safety": "職場安全衛生知識",
            "Basic": "基础",
            "Fair": "良好",
            "Good": "优秀",
            "Remark": "填入",
            "Cancel": "取消",
            "Submit": "提交",
            "Return": "退回",
            "Deny": "拒绝", 
            "Accept": "接受",
            "Confirm Action": "确认提交",
            "Are you sure you want to accept this request?": "您确定要接受此请求吗？",
            "Are you sure you want to submit this request?": "您确定要提交此请求吗？",
            "Deny Request": "拒绝请求",
            "Reason": "增補理由",
            "Please provide a reason for denying this request": "请提供拒绝此请求的原因",
            "Confirm Denial": "确认拒绝",
            "Return Request": "退回请求",
            "Return to level": "退回至级别",
            "Return reason": "退回原因",
            "Confirm Return": "确认退回",
            "Unit Level": "单位级别",
            "Department Level": "部门级别",
            "Division Level": "科室级别",
            "HR Level": "等HR",
            "AD Level": "等GM",
            "GM Level": "总经理级别",
            "Language Changed": "语言已更改",
            "Interface language has been changed": "界面语言已更改",
            "Please fill all required fields": "请填写所有必填字段",
            "Request submitted successfully": "请求提交成功",
            "Error submitting request": "提交请求时出错",
            "Failed to get approval flow data": "获取审批流程数据失败",
            "Request accepted successfully": "请求已成功接受",
            "Failed to accept request": "接受请求失败",
            "Please provide a reason": "请提供原因",
            "Request denied successfully": "请求已成功拒绝",
            "Failed to deny request": "拒绝请求失败",
            "Request returned successfully": "请求已成功退回",
            "Failed to return request": "退回请求失败",
            "Not required": "不需要",
            "Sub Position": "子职位",
            "Professional qualifications": "專業背景",
            "Approval History": "审批历史",
            "Level": "级别",
            "Type": "类型",
            "Approver": "审批人",
            "Status": "状态",
            "Date": "日期",
            "Waiting": "等待",
            "Waiting for approval": "等待审批",
            "Approved": "已批准",
            "Rejected": "已拒绝",
            "Processing": "处理中",
            "Returned": "已退回",
            "Unknown status": "未知状态",
            "Cancel date": "取消日期",
            "None":"不需要",
            "Unit Manager": "单位经理",
            "Department Manager": "部门经理",
            "Division Manager": "科室经理",
            "HR Manager": "人力资源经理",
            "AD Manager": "AD经理",
            "GM Manager": "总经理",
            "Translator": "這個需求是否為翻譯人員?",
            "Confirm": "確認",
            "Previous Approval History": "之前的审批历史",
            "Current Approval History": "当前审批历史",
            "Amendment request": "修改请求",
            "Change quantity": "更改数量",
            "HR Recruitment Staff": "人力资源招聘人员",
            "Change quantity cannot be less than recruited quantity": "更改数量不能少于已招聘数量",
            "Change quantity cannot be greater than request quantity": "更改数量不能大于请求数量",
            "Change quantity cannot be greater than or equal to request quantity": "更改数量不能大于或等于请求数量",
            "Min": "最少",
            "Max": "最多",
            "This field is required": "此字段为必填项",
            "Change remark": "其他意見",
            "Delete": "删除",
            "Please provide a Chinese reason": "请提供中文理由",
            "Approval record": "审批记录",
            "Please provide approval comments": "请提供审批意见",
            "Return reason Chinese": "退回理由中文",
            "Please select Division and Department first": "请先选择处级和部门",
            "Please select":"請選擇",
            "New Year Recruitment": " 請確認以下增補單是否要於新年度繼續招募？",
            "Continue Recruitment": "繼續招募",
            "Cancel Recruitment": "取消招募",
            "Based on": "依照",
            "continue recruitment based on conditions": "招募條件續招"
        }
    }
</i18n>

<style scoped>
.request-page {
    background-color: #f8f9fa;
    min-height: 100vh;
}

/* Form Layout */
.v-row {
    margin: 0 -8px;
}

.v-col {
    padding: 4px 8px;
}

/* Form Controls */
.v-input--selection-controls {
    margin: 0;
    padding: 2px 4px;
}

/* Cards */
.custom-card-hover {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.custom-card-hover:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #bdbdbd;
}

/* File Preview */
.file-preview {
    background-color: #f5f5f5;
    border-radius: 4px;
    padding: 8px;
    margin-top: 8px;
}

/* Dialog Actions */
.dialog-actions {
    border-top: 1px solid rgba(0, 0, 0, 0.12);
    margin: 0;
    bottom: 0;
    z-index: 1;
}

.dialog-actions .v-btn {
    text-transform: none;
    font-weight: 500;
    height: 36px;
}

.flow-sign-container {
    background-color: #e3f2fd !important;
    border-radius: 4px;
    border: 1px solid #90caf9;
}

.flow-sign-text {
    color: #1976d2;
    font-weight: 400;
    line-height: 1.4;
    padding: 8px 0;
}

/* Approval Stepper */
.approval-stepper ::v-deep .v-stepper__header {
    box-shadow: none;
    height: auto !important;
}

.v-expansion-panels {
    background-color: #fff9c4 !important;
}

.v-expansion-panel-content {
    background-color: #fff9c4 !important;
}

.v-expansion-panel-header {
    background-color: #fff9c4 !important;
}

.other-reason-textarea ::v-deep .v-input__slot {
    min-height: 80px !important;
}

.other-reason-textarea ::v-deep textarea {
    line-height: 1.5;
}

.v-tabs-items {
    background-color: transparent !important;
}

.v-tab {
    text-transform: none !important;
    font-weight: 500;
}
/* Tăng kích thước chữ và icon của radio button */
::v-deep .custom-radio label {
    font-size: 18px !important; /* Chỉnh kích thước chữ tùy ý */
    font-weight: 500;
    color: #ff0000 !important; /* Làm đậm màu chữ nếu cần */
}

::v-deep .custom-radio .v-icon {
    font-size: 28px !important; /* Tăng kích thước nút tròn radio */
}
</style>