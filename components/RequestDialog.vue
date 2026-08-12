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
            :appCode="getAppCode" :empno="flowEmpno" :location="flowLocation" 
            :customDept="user.unit || user.dept" :customDivision="user.div"
            :customUnit="user.unit"
            :approval="editData ? editData.approval : null"
            :status="editData ? editData.status : null"
            :status-amendent="editData ? editData.status_amendent : null"
            :status-endyear="editData ? editData.status_endyear : null"
            @sendAppFlow="handleAppFlow"
            class="mb-2"></VfaAppFlow>
            <v-app-bar v-if="showFlowSign" class="flow-sign-container mb-4" flat dense>
                <v-icon color="info" class="mr-2">mdi-information-outline</v-icon>
                <div class="flow-sign-text text-subtitle-2">
                    <div>{{ $t('Request_ori:') }}{{ getCurrentFlowSign }}</div>
                    <div v-if="isNewYearProcessing">
                        {{ $t('Request new year:') }} {{ getNewYearShortFlow }}
                    </div>
                </div>
            </v-app-bar>
            
            <div class="px-6 py-4">
                <v-card-text v-if="showNewYearSection" class="new-year-card mb-4">
                    <v-row class="new-year-section">
                        <v-col cols="12">
                            <div class="d-flex align-center mb-3">
                                <v-icon color="amber darken-3" size="28" class="mr-2">mdi-calendar-star</v-icon>
                                <div class="text-subtitle-1 font-weight-bold text-amber-900">
                                    {{ $t("New Year Recruitment") }}
                                </div>
                            </div>
                            
                            <div class="ml-9">
                                <v-radio-group v-model="form.newYearRecruitment" column hide-details :disabled="disableNewYearRecruitment" >
                                    <v-radio :label="$t('Continue Recruitment')" :value=1 class="mb-2 custom-radio" color="error"></v-radio>
                                    <v-radio :label="$t('Cancel Recruitment')" :value=0 class="custom-radio" color="error"></v-radio>
                                </v-radio-group>
                            </div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-text class="pa-0 mb-4" v-if="showAmendmentSection">
                    <div class="amendment-card">
                        <div class="d-flex align-center mb-4">
                            <v-icon color="orange darken-3" class="mr-2">mdi-pencil-box-multiple</v-icon>
                            <div class="text-subtitle-1 font-weight-bold orange--text text--darken-4 mb-0">{{ $t('Amendment Request Details') }}</div>
                        </div>
                        
                        <v-row>
                            <v-col cols="12" md="3">
                                <v-autocomplete v-if="isChange" class="shadow-sm" :key="autocompleteKey" v-model="reqId"
                                :items="item_req_id" :label="$t('Req ID')" outlined dense :rules="[rules.required]" required
                                hide-details @change="getDataByReqId" item-text="text" item-value="id"
                                background-color="#fffbeb"></v-autocomplete>
                                <v-text-field v-else :value="editData ? editData.req_id : ''" :label="$t('Req ID')"
                                outlined dense disabled hide-details background-color="#f5f5f5"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="5">
                                <v-combobox :disabled="disAmendent" v-model="form.changeReason" :items="formattedChangeReasons"
                                item-text="text" item-value="id" return-object :label="$t('Change Reason')" outlined dense
                                hide-details clearable @change="onChangeReason"></v-combobox>
                            </v-col>
                            <v-col cols="12" md="4" v-if="showChangeQuantityField">
                                <v-text-field :disabled="disAmendent" v-model="form.changeQty"
                                :label="$t('Change quantity') + ` (${$t('Min')}: ${editData?.recruited || 0}, ${$t('Max')}: ${form.quantity || 0})`"
                                type="number" :min="editData?.recruited || 0" :max="form.quantity || 0" outlined dense
                                :rules="[minQuantityRule, maxQuantityRule]" :error-messages="changeQtyError" persistent-hint
                                hide-details="auto" :hint="getChangeQuantityHint"></v-text-field>
                            </v-col>
                        </v-row>
                        
                        <div v-show="showAmendmentRequirementFields">
                            <v-row class="mt-2">
                                <v-col cols="12" sm="6" md="2">
                                    <v-autocomplete :disabled="disAmendent" v-model="form.educationLevel_change"
                                    :items="educationLevelItems"
                                    :label="$t('Education Level')" item-text="name" item-value="id" outlined dense hide-details
                                    clearable></v-autocomplete>
                                </v-col>
                                <v-col cols="12" sm="6" md="8">
                                    <v-textarea :disabled="disAmendent" auto-grow v-model="form.specialization_change"
                                    :placeholder="$t('Professional qualifications')"
                                    :label="$t('Professional qualifications')" outlined dense rows="1" hide-details></v-textarea>
                                </v-col>
                                <v-col cols="12" sm="6" md="2">
                                    <v-autocomplete :disabled="disAmendent" v-model="form.workExperience_change" :items="workExpItems"
                                    :label="$t('Work Experience')" item-text="name"
                                    item-value="id" outlined dense hide-details clearable></v-autocomplete>
                                </v-col>
                            </v-row>
                            
                            <v-row class="mt-2">
                                <v-col cols="12" :md="getJobDescColWidth">
                                    <v-textarea :disabled="disAmendent" auto-grow v-model="form.jobDesc_change" outlined
                                    :label="$t('Basic Job Description')" rows="1"
                                    hide-details></v-textarea>
                                </v-col>
                                <v-col cols="12" md="2" v-if="form.pos !== 1">
                                    <v-file-input :disabled="disAmendent" v-model="form.attachments.jobDesc_change"
                                    :label="$t('Job description file')" outlined dense
                                    hide-details @change="previewFile('jobDesc_change', $event)"
                                    :clearable="true" accept=".doc,.docx,.pdf"></v-file-input>
                                    <div v-if="filePreview.jobDesc_change" class="file-preview-card mt-2">
                                        <div class="d-flex align-center justify-space-between mb-1">
                                            <v-chip small class="bg-primary-light">
                                                <v-icon left small color="#01a6be">{{ getFileIcon(form.attachments.jobDesc_change?.type || getFileTypeFromName(form.attachments.jobDesc_change)) }}</v-icon>
                                                <span class="file-name-text">{{ getFileName("jobDesc_change") }}</span>
                                            </v-chip>
                                            <span class="caption grey--text">{{ form.attachments.jobDesc_change_size || "" }}</span>
                                        </div>
                                        <div class="d-flex align-center justify-end">
                                            <v-btn x-small text color="primary" @click="openFile('jobDesc_change')" class="mr-2"> <v-icon small left>mdi-eye</v-icon> {{ $t("View") }} </v-btn>
                                            <v-btn :disabled="disAmendent" x-small text color="error" @click="clearFile('jobDesc_change')"> <v-icon small left>mdi-delete</v-icon> {{ $t("Delete") }} </v-btn>
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>
                            
                            <div v-show="showLanguageAndComputerSkills" class="mt-6">
                                <div class="mb-4">
                                    <div class="d-flex align-center mb-2">
                                        <v-icon left color="orange darken-2" small>mdi-translate</v-icon>
                                        <div class="text-subtitle-2 font-weight-medium orange--text text--darken-4">
                                            {{ $t("Foreign Language Proficiency") }}
                                        </div>
                                    </div>
                                    <v-row class="ml-1">
                                        <v-col cols="12" md="4">
                                            <v-radio-group v-model="form.either_change" row dense hide-details>
                                                <div class="subtitle-2 mr-2">{{ $t("Either:") }}</div>
                                                <v-radio :disabled="disAmendent"
                                                v-for="(level, index) in getLanguageLevels('either')"
                                                :key="'either-' + index" :value="level.value"
                                                :label="getLocalizedLabel(level)" hide-details class="mr-3"></v-radio>
                                            </v-radio-group>
                                        </v-col>
                                        
                                        <v-col cols="12" md="4">
                                            <v-radio-group v-model="form.english_change" row hide-details dense>
                                                <div class="subtitle-2 mr-2">{{ $t("English:") }}</div>
                                                <v-radio :disabled="disAmendent"
                                                v-for="(level, index) in getLanguageLevels('en')" :key="'english-' + index"
                                                :value="level.value" :label="getLocalizedLabel(level)" hide-details
                                                class="mr-3"></v-radio>
                                            </v-radio-group>
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <v-radio-group v-model="form.chinese_change" row hide-details dense>
                                                <div class="subtitle-2 mr-2">{{ $t("Chinese:") }}</div>
                                                <v-radio :disabled="disAmendent"
                                                v-for="(level, index) in getLanguageLevels('cn')" :key="'chinese-' + index"
                                                :value="level.value" :label="getLocalizedLabel(level)" hide-details
                                                class="mr-3"></v-radio>
                                            </v-radio-group>
                                        </v-col>
                                        <v-col cols="12" md="4" class="pt-0">
                                            <v-checkbox :disabled="disAmendent" v-model="form.tranlator_change"
                                            :label="$t('Translator')" hide-details class="mt-0"></v-checkbox>
                                        </v-col>
                                    </v-row>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex align-center mb-2">
                                        <v-icon left color="orange darken-2" small>mdi-desktop-classic</v-icon>
                                        <div class="text-subtitle-2 font-weight-medium orange--text text--darken-4">
                                            {{ $t("Computer Skills") }}
                                        </div>
                                    </div>
                                    
                                    <v-row class="ml-2">
                                        <v-col cols="12" class="d-flex flex-wrap py-0">
                                            <v-checkbox :disabled="disAmendent"
                                            v-for="skill in ['Word', 'Excel', 'Access', 'PowerPoint', 'Internet', 'None', 'Other']"
                                            :key="skill" v-model="form.computerSkills_change" :value="skill"
                                            :label="$t(skill)" dense hide-details class="mr-4 mb-2"></v-checkbox>
                                        </v-col>
                                        <v-col cols="12" v-if="form.computerSkills_change.includes('Other')" class="pt-0">
                                            <v-textarea :disabled="disAmendent"
                                            v-model="form.otherComputerSkills_change" :label="$t('Other Skills')" dense outlined
                                            class="mt-2" rows="1" hide-details auto-grow></v-textarea>
                                        </v-col>
                                    </v-row>
                                </div>
                            </div>
                        </div>
                        
                        <v-row class="mt-2">
                            <v-col cols="12">
                                <v-textarea :disabled="disAmendent" v-model="form.remark_change" outlined
                                :label="$t('Change remark')" rows="1" hide-details auto-grow></v-textarea>
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
                <v-card-text class="px-1 py-1">
                    <div class="form-card">
                        <div class="form-card-header d-flex align-center mb-4">
                            <v-icon color="#01a6be" class="mr-2" size="24">mdi-card-text-outline</v-icon>
                            <span class="text-subtitle-1 font-weight-bold text-slate-800">{{ $t("Basic Request Information") }}</span>
                        </div>
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
                            :rules="form.recruitment.reason === 3 ? [rules.required] : []"
                            :required="form.recruitment.reason === 3"
                            hide-details="auto" clearable @change="handleOtherReasonSelect"></v-autocomplete>
                        </v-col>
                        <!-- Table of Resigned Employees Details (Chỉ hiện khi có dữ liệu từ file tải lên hoặc đơn cũ) -->
                        <v-col cols="12" v-if="form.recruitment.reason === 2 || showDimissionFileInput">
                            <v-alert dense type="info" outlined v-if="oldEmpRawText" class="mb-2">
                                <span class="text-caption">
                                    <b>{{ $t('Old Request Note') || 'Ghi chú nhân viên thôi việc' }}:</b> {{ oldEmpRawText }}
                                </span>
                            </v-alert>
                            
                            <v-card outlined class="pa-2" v-if="resignedEmpListDetails && resignedEmpListDetails.length > 0 && !(form.attachments.dimissionList || form.attachments.dimissionList_url)">
                            <div class="d-flex align-center justify-space-between px-2 py-1" @click="showResignedEmpTable = !showResignedEmpTable" style="cursor: pointer;">
                                <span class="text-caption font-weight-medium grey--text text--darken-2">
                                    <v-icon x-small left color="primary">mdi-account-group</v-icon>
                                    {{ $t('Resigned Employees List') }} ({{ resignedEmpListDetails.length }})
                                </span>
                                <v-btn icon x-small @click.stop="showResignedEmpTable = !showResignedEmpTable" :title="showResignedEmpTable ? $t('Collapse') : $t('Expand')">
                                    <v-icon x-small>{{ showResignedEmpTable ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                                </v-btn>
                            </div>
                            <v-expand-transition>
                                <div v-show="showResignedEmpTable">
                                    <v-simple-table dense  fixed-header class="mt-1">
                                        <template v-slot:default>
                                            <thead>
                                                <tr>
                                                    <th class="text-left">#</th>
                                                    <th class="text-left">{{ $t('EmpNo') }}</th>
                                                    <th class="text-left">{{ $t('Name') }}</th>
                                                    <th class="text-left">{{ $t('Department') }}</th>
                                                    <th class="text-center" v-if="!formDisabled"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, idx) in resignedEmpListDetails" :key="item.empno">
                                                    <td>{{ idx + 1 }}</td>
                                                    <td class="font-weight-bold primary--text">{{ item.empno }}</td>
                                                    <td>{{ item.name }} </td>
                                                    <td>{{ item.dept_name || item.dept_code }}</td>
                                                    <td class="text-center" v-if="!formDisabled">
                                                        <v-btn icon small color="error" @click="removeOldEmp(idx)">
                                                            <v-icon small>mdi-close-circle</v-icon>
                                                        </v-btn>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </template>
                                    </v-simple-table>
                                </div>
                            </v-expand-transition>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card>
        </v-col>
        
        <v-col cols="12" md="4">
            <v-file-input :disabled="formDisabled || !showDimissionFileInput" v-model="form.attachments.dimissionList"
            :label="form.recruitment.reason === 2 ? $t('Dimission list file') : $t('Attached file')" outlined dense
            hide-details="auto"
            @change="handleDimissionListChange($event)"
            @click:clear="clearFile('dimissionList')"
            prepend-inner-icon="mdi-file-account"
            :clearable="true" accept=".doc,.docx,.pdf,.xls,.xlsx"></v-file-input>
            <div class="mt-1 ml-1" v-if="templateFileName">
                <a :href="`${$router.options.base}${templateFileName}`" download class="text-caption" style="color: #00838F; text-decoration: underline;">
                    <v-icon x-small color="#00838F">mdi-download</v-icon> {{ $t("Download template") }}
                </a>
            </div>
            <div v-show="filePreview.dimissionList && showDimissionFileInput"
            class="file-preview-card mt-2">
            <div class="d-flex align-center justify-space-between mb-1">
                <v-chip small class="bg-primary-light">
                    <v-icon left small color="#01a6be">{{ getFileIcon(form.attachments.dimissionList?.type ||
                        getFileTypeFromName(form.attachments.dimissionList) ) }}</v-icon>
                        <span class="file-name-text">{{ getFileName("dimissionList") }}</span>
                    </v-chip>
                    <span class="caption grey--text">{{ form.attachments.dimissionList_size || "" }}</span>
                </div>
                <div class="d-flex align-center justify-end">
                    <v-btn x-small text color="primary" @click="openFile('dimissionList')" class="mr-2"> <v-icon small left>mdi-eye</v-icon> {{ $t("View") }} </v-btn>
                    <v-btn :disabled="formDisabled" x-small text color="error" @click="clearFile('dimissionList')"> <v-icon small left>mdi-delete</v-icon> {{ $t("Delete") }} </v-btn>
                </div>
            </div>
        </v-col>
    </v-row>
</div>

<!-- Qualifications & Job Description Card -->
<div class="form-card mt-4">
    <div class="form-card-header d-flex align-center mb-4">
        <v-icon color="#01a6be" class="mr-2" size="24">mdi-school-outline</v-icon>
        <span class="text-subtitle-1 font-weight-bold text-slate-800">{{ $t("Qualifications & Job Description") }}</span>
    </div>
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
    <v-row class="mt-3">
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
            <div v-if="filePreview.jobDesc" class="file-preview-card mt-2">
                <div class="d-flex align-center justify-space-between mb-1">
                    <v-chip small class="bg-primary-light">
                        <v-icon left small color="#01a6be">{{ getFileIcon(form.attachments.jobDesc?.type ||
                            getFileTypeFromName(form.attachments.jobDesc) ) }}</v-icon>
                            <span class="file-name-text">{{ getFileName("jobDesc") }}</span>
                        </v-chip>
                        <span class="caption grey--text">{{ form.attachments.jobDesc_size || "" }}</span>
                    </div>
                    <div class="d-flex align-center justify-end">
                        <v-btn x-small text color="primary" @click="openFile('jobDesc')" class="mr-2"> <v-icon small left>mdi-eye</v-icon> {{ $t("View") }} </v-btn>
                        <v-btn :disabled="formDisabled" x-small text color="error" @click="clearFile('jobDesc')"> <v-icon small left>mdi-delete</v-icon> {{ $t("Delete") }} </v-btn>
                    </div>
                </div>
            </v-col>
        </v-row>
    </div>
    
    <!-- Skills & Other Information Card -->
    <div class="form-card mt-4">
        <div v-show="form.pos !== 1">
            <div class="d-flex align-center mb-2">
                <v-icon left color="#01a6be" small>mdi-translate</v-icon>
                <div class="text-subtitle-2 font-weight-medium text-slate-700">
                    {{ $t("Foreign Language Proficiency") }}
                </div>
            </div>
            <v-row class="ml-1 mb-4">
                <v-col cols="12" md="4">
                    <v-radio-group :disabled="formDisabled" v-model="form.either" row dense hide-details>
                        <div class="subtitle-2 mr-2">{{ $t("Either:") }}</div>
                        <v-radio :disabled="formDisabled"
                        v-for="(level, index) in getLanguageLevels('either')"
                        :key="'either-' + index" :value="level.value"
                        :label="getLocalizedLabel(level)" hide-details class="mr-3"></v-radio>
                    </v-radio-group>
                </v-col>
                <v-col cols="12" md="4">
                    <v-radio-group :disabled="formDisabled" v-model="form.english" row hide-details dense>
                        <div class="subtitle-2 mr-2">{{ $t("English:") }}</div>
                        <v-radio :disabled="formDisabled"
                        v-for="(level, index) in getLanguageLevels('en')"
                        :key="'english-' + index" :value="level.value"
                        :label="getLocalizedLabel(level)" hide-details class="mr-3"></v-radio>
                    </v-radio-group>
                </v-col>
                <v-col cols="12" md="4">
                    <v-radio-group :disabled="formDisabled" v-model="form.chinese" row hide-details dense>
                        <div class="subtitle-2 mr-2">{{ $t("Chinese:") }}</div>
                        <v-radio :disabled="formDisabled"
                        v-for="(level, index) in getLanguageLevels('cn')"
                        :key="'chinese-' + index" :value="level.value"
                        :label="getLocalizedLabel(level)" hide-details class="mr-3"></v-radio>
                    </v-radio-group>
                </v-col>
                <v-col cols="12" md="4" class="pt-0">
                    <v-checkbox :disabled="formDisabled" v-model="form.tranlator"
                    :label="$t('Translator')" hide-details class="mt-0"></v-checkbox>
                </v-col>
            </v-row>
        </div>
        
        <div v-show="form.pos !== 1" class="mt-4">
            <div class="d-flex align-center mb-2">
                <v-icon left color="#01a6be" small>mdi-desktop-classic</v-icon>
                <div class="text-subtitle-2 font-weight-medium text-slate-700">
                    {{ $t("Computer Skills") }}
                </div>
            </div>
            <v-row class="ml-1 mb-4">
                <v-col cols="12" class="d-flex flex-wrap py-0">
                    <v-checkbox
                    v-for="skill in ['Word', 'Excel', 'Access', 'PowerPoint', 'Internet', 'None', 'Other']"
                    :key="skill" :disabled="formDisabled" v-model="form.computerSkills"
                    :value="skill" :label="$t(skill)" dense hide-details class="mr-4 mb-2"></v-checkbox>
                </v-col>
                <v-col cols="12" v-if="form.computerSkills.includes('Other')" class="pt-0">
                    <v-textarea :disabled="formDisabled"
                    v-model="form.otherComputerSkills" :label="$t('Other Skills')" dense outlined
                    class="mt-2" rows="1" hide-details auto-grow></v-textarea>
                </v-col>
            </v-row>
        </div>
        
        <div class="mt-4">
            <div class="d-flex align-center mb-2">
                <v-icon left color="#01a6be" small>mdi-shield-account</v-icon>
                <div class="text-subtitle-2 font-weight-medium text-slate-700">
                    {{ $t("Workplace Safety") }}
                </div>
            </div>
            <v-radio-group :disabled="formDisabled" v-model="form.safetyLevels" row dense
            class="ml-2" :rules="[rules.required]" required hide-details>
            <v-radio v-for="(level, index) in safetyLevels" :key="'safety-' + index"
            :value="level.id" :label="level.name" class="mr-4" hide-details></v-radio>
        </v-radio-group>
    </div>
    
    <v-row class="mt-4">
        <v-col cols="12">
            <v-textarea :disabled="formDisabled" v-model="form.remark" outlined
            :label="$t('Remark')" rows="2" hide-details auto-grow></v-textarea>
        </v-col>
        <v-col cols="12" v-if="form.req_id_oldyear || (editData && editData.req_id_oldyear)">
            <v-text-field 
            outlined 
            readonly 
            dense
            :label="$t('Note')" 
            :value="noteText" 
            hide-details
            :append-icon="'mdi-open-in-new'"
            @click:append="viewOriginalRequest"
            color="primary"
            class="note-text-field"
            >
        </v-text-field>
    </v-col>
</v-row>
</div>

<!-- Approval History panel -->
<div class="form-card mt-4">
    <div class="form-card-header d-flex align-center mb-4">
        <v-icon color="#01a6be" class="mr-2" size="24">mdi-history</v-icon>
        <span class="text-subtitle-1 font-weight-bold text-slate-800">{{ $t("Approval History") }}</span>
    </div>
    
    <v-expansion-panels flat class="approval-history-panels">
        <v-expansion-panel class="approval-history-panel">
            <v-expansion-panel-header class="py-3">
                <div class="d-flex align-center font-weight-medium text-subtitle-2 text-slate-700">
                    <v-icon left color="primary" small>mdi-clock-outline</v-icon>
                    {{ $t("Current Approval History") }}
                </div>
            </v-expansion-panel-header>
            <v-expansion-panel-content class="pa-0 pt-3">
                <v-stepper v-model="approvalStepper" class="approval-stepper transparent elevation-0" flat>
                    <v-stepper-header class="compact-stepper-header">
                        <v-stepper-step v-if="editData" :complete="true" :editable="true" color="#01a6be"
                        :step="0" :key="'submitter-step'" class="submitter-step compact-step"
                        style="pointer-events: none;" complete-icon="mdi-pencil-outline">
                        <div class="text-center font-weight-bold my-1" style="font-size: 0.8rem;">
                            {{ $t('Submitter') }}
                        </div>
                        <div class="caption text-center grey--text text--darken-2" style="line-height: 1.3;">
                            <div style="white-space: normal; font-weight: 500;">
                                {{
                                    (() => {
                                        try {
                                            let s = editData?.submitter;
                                            if (typeof s === 'string') {
                                                s = JSON.parse(s);
                                            }
                                            return s ? `${s.name || ''}` : '';
                                        } catch (e) {
                                            return '';
                                        }
                                    })()
                                }}
                            </div>
                            <div v-if="editData.created_at" class="grey--text text--lighten-1 font-italic mt-1" style="font-size: 0.7rem;">
                                ({{ editData.created_at.split(' ')[0] }})
                            </div>
                        </div>
                    </v-stepper-step>
                    
                    <v-divider :key="'div-submitter-current'"></v-divider>
                    
                    <template v-for="(step, idx) in approvalStepperSteps">
                        <v-stepper-step v-if="step.approver && step.approver.empno && (step.level === 1 || isLevel1Approved)"
                        :complete="step.status === 'approved'" :editable="false"
                        :rules="step.shouldShowError ? [() => false] : []"
                        :color="getApprovalStatusColor(step.status)" :step="idx + 1"
                        :error-icon="step.shouldShowError ? 'mdi-cancel' : ''"
                        complete-icon="mdi-check" :key="idx"
                        class="compact-step">
                        <div class="text-center font-weight-bold my-1 d-flex align-center justify-center" 
                        style="font-size: 0.8rem; min-height: 25px; line-height: 1.2;">
                        {{ step.levelName }}
                    </div>
                    <div class="caption text-center grey--text text--darken-2 mb-1" style="line-height: 1.3;">
                        <div style="white-space: normal; word-break: break-word; font-weight: 500;">
                            <template v-if="step.approver.empno === 'Pending'">
                                {{ step.approver.name }}
                            </template>
                            <template v-else>
                                {{ step.approver.empno }} - {{ step.approver.name }}
                            </template>
                        </div>
                        <div class="font-italic mt-1" :style="{ color: getApprovalStatusColor(step.status) }">
                            {{ getApprovalStatusText(step.status) }}
                            <div v-if="step.processed_at" class="grey--text text--lighten-1" style="font-size: 0.7rem;">
                                ({{ step.processed_at.split(' ')[0] }})
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column align-center w-100 mt-1" style="width: 100%">
                        <div v-if="step.reason_denied" class="caption error--text font-weight-bold px-2 py-1 rounded red lighten-5 d-flex align-center justify-center my-1" style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                            <v-icon x-small color="error" class="mr-1">mdi-close-circle-outline</v-icon>
                            <span>{{ step.reason_denied }}</span>
                        </div>
                        <div v-if="step.return_reason" class="caption warning--text font-weight-bold px-2 py-1 rounded orange lighten-5 d-flex align-center justify-center my-1" style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                            <v-icon x-small color="warning" class="mr-1">mdi-undo-variant</v-icon>
                            <span>{{ step.return_reason }}</span>
                        </div>
                        <div v-if="step.approval_record" class="caption primary--text px-2 py-1 rounded blue lighten-5 d-flex align-center justify-center my-1" style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                            <v-icon x-small color="primary" class="mr-1">mdi-comment-text-outline</v-icon>
                            <span>{{ step.approval_record }}</span>
                        </div>
                    </div>
                </v-stepper-step>
                <v-divider v-if="idx < approvalStepperSteps.length - 1" :key="`div-${idx}`"></v-divider>
            </template>
        </v-stepper-header>
    </v-stepper>
</v-expansion-panel-content>
</v-expansion-panel>

<template>
    <v-expansion-panel v-if="showPreviousApprovalHistory" class="approval-history-panel mt-2">
        <v-expansion-panel-header class="py-1" style="min-height: 48px;">
            <div class="d-flex align-center font-weight-bold text-subtitle-2">
                <v-icon left small>mdi-history</v-icon>
                {{ $t("Previous Approval History") }}
            </div>
        </v-expansion-panel-header>
        
        <v-expansion-panel-content class="pa-0 pt-0">
            <div v-for="(cycle, cIdx) in previousApprovalCycles" :key="`cycle-${cIdx}`" class="mb-2 mt-1">
                
                <div class="d-flex align-center mb-1 ml-2">
                    <v-chip color="primary" text-color="white" x-small label class="font-weight-bold mr-2 elevation-0">
                        {{ cycle.name }}
                    </v-chip>
                    <v-divider></v-divider>
                </div>
                
                <v-card outlined class="rounded-lg elevation-0 cycle-card mx-2">
                    <v-stepper v-model="previousApprovalStepper" class="approval-stepper transparent elevation-0 pa-0 ma-0" alt-labels>
                        <v-stepper-header class="compact-stepper-header">
                            
                            <v-stepper-step :key="`submitter-${cIdx}`" complete step="0" color="#1976D2" class="submitter-step compact-step" complete-icon="mdi-pencil-outline">
                                <div class="text-center font-weight-bold my-1" style="font-size: 0.8rem;">
                                    {{ $t('Submitter') }}
                                </div>
                                
                                <div class="caption text-center grey--text text--darken-2" style="line-height: 1.3;">
                                    <div style="white-space: normal; font-weight: 500;">
                                        {{
                                            (() => {
                                                try {
                                                    let s = editData?.submitter;
                                                    if (typeof s === 'string') s = JSON.parse(s);
                                                    return s ? `${s.name}` : '';
                                                } catch (e) { return ''; }
                                            })()
                                        }}
                                    </div>
                                    <div v-if="editData.created_at" class="text--lighten-1 font-italic mt-1" style="font-size: 0.7rem;">
                                        ({{ editData.created_at.split(' ')[0] }})
                                    </div>
                                </div>
                            </v-stepper-step>
                            
                            <v-divider :key="`div-submitter-${cIdx}`"></v-divider>
                            
                            <template v-for="(step, sIdx) in cycle.steps">
                                <v-stepper-step 
                                :key="`step-${cIdx}-${sIdx}`"
                                :complete="step.status === 'approved'" 
                                :step="sIdx + 1"
                                :rules="step.shouldShowError ? [() => false] : []"
                                :color="getApprovalStatusColor(step.status)"
                                :error-icon="step.shouldShowError ? 'mdi-close' : ''"
                                complete-icon="mdi-check"
                                class="compact-step"
                                >
                                <div class="text-center font-weight-bold my-1 d-flex align-center justify-center" 
                                style="font-size: 0.8rem; min-height: 25px; line-height: 1.2;">
                                {{ step.levelName }}
                            </div>
                            
                            <div class="caption text-center grey--text text--darken-2 mb-1" style="line-height: 1.3;">
                                <template v-if="step.approver && step.approver.empno">
                                    <div style="white-space: normal; word-break: break-word;">
                                        {{ step.approver.empno }} - {{ step.approver.name }}
                                    </div>
                                </template>
                                
                                <div class="font-italic mt-1" :style="{ color: getApprovalStatusColor(step.status) }">
                                    {{ getApprovalStatusText(step.status) }}
                                    <div v-if="step.processed_at" class="grey--text text--lighten-1" style="font-size: 0.7rem;">
                                        ({{ step.processed_at.split(' ')[0] }})
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column align-center w-100 mt-1" style="width: 100%">
                                
                                <div v-if="step.reason_denied" 
                                class="caption error--text font-weight-bold px-2 py-1 rounded red lighten-5 d-flex align-center justify-center my-1" 
                                style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                                <v-icon x-small color="error" class="mr-1">mdi-close-circle-outline</v-icon>
                                <span>{{ step.reason_denied }}</span>
                            </div>
                            
                            <div v-if="step.return_reason" 
                            class="caption warning--text font-weight-bold px-2 py-1 rounded orange lighten-5 d-flex align-center justify-center my-1" 
                            style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                            <v-icon x-small color="warning" class="mr-1">mdi-undo-variant</v-icon>
                            <span>{{ step.return_reason }}</span>
                        </div>
                        
                        <div v-if="step.approval_record" 
                        class="caption primary--text px-2 py-1 rounded blue lighten-5 d-flex align-center justify-center my-1" 
                        style="width: 100%; line-height: 1.2; font-size: 0.7rem; white-space: normal; text-align: center;">
                        <v-icon x-small color="primary" class="mr-1">mdi-comment-text-outline</v-icon>
                        <span>{{ step.approval_record }}</span>
                    </div>
                    
                </div>
            </v-stepper-step>
            
            <v-divider v-if="sIdx < cycle.steps.length - 1" :key="`div-${cIdx}-${sIdx}`"></v-divider>
        </template>
    </v-stepper-header>
</v-stepper>
</v-card>
</div>
</v-expansion-panel-content>
</v-expansion-panel>
</template>
</v-expansion-panels>
</div>
</v-card-text>

<v-row class="dialog-actions">
    <v-col cols="12" class="d-flex justify-end align-center">
        <template v-if="isEdit && canApproveItem">
            <v-btn color="#01a6be" class="mr-3 white--text" @click="openAcceptDialog">
                <v-icon left>mdi-check</v-icon> {{ $t("Accept") }}
            </v-btn>
            
            <v-btn 
            v-if="editData && editData.status !== 'declined' && 
            (!isNewYearProcessing || 
            (editData.status === 'waiting_div' || 
            editData.status_amendent === 'waiting_div' || 
            editData.status_endyear === 'waiting_div')
            )" 
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
</div>
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

<v-dialog v-model="dimissionPreviewDialog" max-width="1200px" persistent>
    <v-card>
        <v-card-title class="error white--text" v-if="dimissionPreviewHasError">
            <v-icon left dark>mdi-alert</v-icon>
            {{ $t('Invalid Employee IDs Found') || 'Mã nhân viên không hợp lệ' }}
        </v-card-title>
        <v-card-title class="primary white--text" v-else>
            <v-icon left dark>mdi-file-excel</v-icon>
            {{ $t('Review Employee IDs') || 'Xác nhận danh sách nhân viên' }}
        </v-card-title>
        <v-card-text class="pt-4">
            <div class="mb-4" v-if="dimissionPreviewHasError">
                <span class="error--text">{{ $t('The highlighted IDs have been used for another request. Please update the file.') || 'Các mã được tô đỏ đã được dùng cho đơn khác. Vui lòng cập nhật lại file.' }}</span>
                <br>
                <a :href="`${$router.options.base}Resign_template.xlsx`" download style="color: blue; text-decoration: underline;">
                    <v-icon x-small color="blue">mdi-download</v-icon> {{ $t('Download template here') || 'Tải file mẫu tại đây' }}
                </a>
            </div>
            <v-data-table
                :headers="dimissionPreviewHeaders"
                :items="dimissionPreviewData"
                class="elevation-1"
                hide-default-footer
                disable-pagination
                height="400"
                fixed-header
            >
                <template v-slot:item="{ item }">
                    <tr :class="{'error-row': item.hasError}">
                        <td>{{ item.stt }}</td>
                        <td>
                            {{ item.empno }}
                            <div v-if="item.errorMsg" class="text-caption error--text" style="line-height: 1.2;">{{ item.errorMsg }}</div>
                        </td>
                        <td>{{ item.name }}</td>
                        <td>{{ item.deptNo }}</td>
                        <td>{{ item.deptName }}</td>
                        <td>{{ item.position }}</td>
                        <td>{{ item.date }}</td>
                        <td class="text-center">
                            <v-icon v-if="item.hasError" color="error" >mdi-close-circle</v-icon>
                            <v-icon v-else color="success">mdi-check-circle</v-icon>
                        </td>
                    </tr>
                </template>
            </v-data-table>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="grey darken-1" outlined @click="closeDimissionPreview">
                {{ $t('Close') || 'Đóng' }}
            </v-btn>
            <v-btn color="success" v-if="!dimissionPreviewHasError" @click="confirmDimissionPreview">
                {{ $t('Confirm') || 'Xác nhận' }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>

</v-dialog>
</template>
<script>
// import VfaAppFlow from "../../../@global-component/vfa-comp";
// import VfaAppFlow from 'D:/source/@global-component/vfa-comp';
import VfaAppFlow from '@/components/AppFlow.vue';
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
            api: "/api/vg-mpr/",
            storageUrl: "/api/storage/app/vg-mpr/",
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
            settingHistory: [],
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
                cn: "填表人➜ 單位主管➜ 部級主管 ➜ 處級主管 ➜ 人力資源部 (適用於越籍課長級(含)以下幹部/職員/工人)",
                en: "Submitter ➜ Department manager ➜ Division manager ➜ HR Department (Applicable to Vietnamese Section Chief level (inclusive) and below Leader/Staff/Workers)",
                vi: "Người Điền Biểu ➜ Chủ Quản Đơn Vị ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR (Áp dụng đối với CNV, cán bộ Việt Nam cấp Trưởng ngành trở xuống)",
            },
            {
                id: 2,
                cn: "填表人 ➜ 單位主管 ➜ 部級主管 ➜ 處級主管 ➜ 人力資源部 ➜ 管理處 ➜ 總經理室 (適用於越籍資深課長級(含)以上幹部)",
                en: "Submitter ➜ Department manager ➜ Division manager ➜ HR Department (Applicable to Vietnamese Section Chief level (inclusive) and above Leader)",
                vi: "Người Điền Biểu ➜ Chủ Quản Đơn Vị ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR ➜ Khu quản lý ➜ Văn Phòng TGĐ (Áp dụng đối với cán bộ Việt Nam cấp Trưởng ngành thâm niên trở lên)",
            },
            ],
            selectedFlowSign: null,
            reqId: "",
            itemsReqId: [],
            oldEmpError: "",
            oldEmpRawText: "",
            resignedEmpInfo: null,
            oldEmpList: [],
            resignedEmpListDetails: [],
            showResignedEmpTable: true,
            dimissionPreviewDialog: false,
            dimissionPreviewData: [],
            dimissionPreviewHasError: false,
            form: {
                newYearRecruitment: null,
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
                educationLevel_change: null,
                specialization_change: "",
                workExperience_change: null,
                jobDesc_change: "",
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
                    jobDesc_change: undefined,
                    jobDesc_change_url: "",
                    jobDesc_change_name: "",
                    jobDesc_change_size: "",
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
                jobDesc_change: false,
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
        "activeUser.empno": {
            handler(newVal) {
                if (!newVal || newVal.trim() === '') {
                    console.warn('activeUser.empno is empty');
                    return;
                }
                // Force update approval history khi empno được set
                this.$forceUpdate();
            },
            immediate: true
        },
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
                this.resetActiveUser();
                this.user = {
                    dept: "",
                    div: this.activeUser.division,
                    unit: "",
                };
            }
        },
        editData: {
            async handler(newData) {
                if (newData) {
                    await this.getDataModel();
                    this.populateForm(newData);
                    const submitter = typeof newData.submitter === "string" ? JSON.parse(newData.submitter) : newData.submiterInfo;
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
        async handleOldEmpListChange(val) {
            let list = Array.isArray(val) ? val : [val];
            let cleanList = [];
            list.forEach(item => {
                if (typeof item === 'string') {
                    // Match potential EmpNos (5-7 alphanumeric chars)
                    const matches = item.match(/\b[a-zA-Z0-9]{5,7}\b/g);
                    if (matches) {
                        matches.forEach(m => {
                            const trimmed = m.toUpperCase();
                            if (!cleanList.includes(trimmed)) {
                                cleanList.push(trimmed);
                            }
                        });
                    }
                }
            });
            
            this.oldEmpList = cleanList;
            this.oldEmpError = ""; // Reset old error
            
            let finalCleanList = [];
            let newDetails = [];
            let hasAnyError = false;
            
            for (const empno of cleanList) {
                let hasErrorThisEmp = false;
                let errorMsg = "";
                
                // Check duplicate in resign-tracking
                try {
                    const checkRes = await this.$axios.post(this.api + 'checkResignEmpNo', {
                        empno: empno,
                        req_id: this.editData?.req_id || ''
                    });
                    if (checkRes.data && checkRes.data.exists) {
                        hasErrorThisEmp = true;
                        errorMsg = checkRes.data.message;
                    }
                } catch (e) {
                    console.error("Error checking resign empno:", e);
                }
                
                if (hasErrorThisEmp) {
                    this.$notify({
                        title: this.$t("Error"),
                        text: errorMsg,
                        type: "error",
                        duration: 7000
                    });
                    this.oldEmpError = this.oldEmpError ? (this.oldEmpError + ' \n ' + errorMsg) : errorMsg;
                    hasAnyError = true;
                    continue; // Bỏ qua nhân viên này
                }
                
                let detailObj = {
                    empno: empno,
                    name: "",
                    dept_code: "",
                    dept_name: "",
                    sex: "",
                    in_date: "",
                    error: "" // Không còn error nữa do đã bỏ qua bên trên
                };
                
                let isEmpValid = false;
                
                // Fetch emp details
                try {
                    const empRes = await this.$axios.post('http://gmo021.cansportsvg.com/api/emp/postByEmpNo', {
                        empno: empno,
                        loc: this.activeUser?.location || 'vg'
                    });
                    if (empRes.data && empRes.data.name) {
                        detailObj.name = empRes.data.name || "";
                        detailObj.dept_code = empRes.data.dept_code || "";
                        detailObj.dept_name = empRes.data.dept_name || "";
                        detailObj.sex = empRes.data.sex || "";
                        detailObj.in_date = empRes.data.in_date || "";
                        isEmpValid = true;
                    } else {
                        const notFoundMsg = (this.$t ? this.$t("Employee not found:") : "Không tìm thấy nhân viên:") + " " + empno;
                        this.$notify({
                            title: this.$t ? this.$t("Error") : "Lỗi",
                            text: notFoundMsg,
                            type: "error",
                            duration: 7000
                        });
                        this.oldEmpError = this.oldEmpError ? (this.oldEmpError + ' \n ' + notFoundMsg) : notFoundMsg;
                        hasAnyError = true;
                    }
                } catch (e) {
                    console.error("Error fetching employee info:", e);
                    const errMsg = (this.$t ? this.$t("Error fetching employee info for:") : "Lỗi khi lấy thông tin nhân viên:") + " " + empno;
                    this.$notify({
                        title: this.$t ? this.$t("Error") : "Lỗi",
                        text: errMsg,
                        type: "error",
                        duration: 7000
                    });
                    this.oldEmpError = this.oldEmpError ? (this.oldEmpError + ' \n ' + errMsg) : errMsg;
                    hasAnyError = true;
                }
                
                if (isEmpValid) {
                    finalCleanList.push(empno);
                    newDetails.push(detailObj);
                }

            }
            
            if (hasAnyError) {
                // Cập nhật lại danh sách nếu có mã bị xóa bỏ
                this.oldEmpList = finalCleanList;
            }
            this.form.recruitment.oldempno = finalCleanList.join(",");
            
            this.resignedEmpListDetails = newDetails;
            if (newDetails.length > 0) {
                this.showResignedEmpTable = true;
            } else {
                this.showResignedEmpTable = false;
                this.clearFile("dimissionList");
            }
        },
        handleOldEmpPaste(event) {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData).getData('text');
            if (!text) return;
            const pastedItems = text.split(/[\s,]+/).map(s => s.trim()).filter(Boolean);
            const combined = [...this.oldEmpList, ...pastedItems];
            this.handleOldEmpListChange(combined);
        },
        removeOldEmp(index) {
            this.oldEmpList.splice(index, 1);
            this.resignedEmpListDetails.splice(index, 1);
            this.form.recruitment.oldempno = this.oldEmpList.join(",");
            this.oldEmpError = this.resignedEmpListDetails.some(d => d.error) ? "Có mã nhân viên không hợp lệ" : "";
            if (this.resignedEmpListDetails.length === 0) {
                this.showResignedEmpTable = false;
                this.clearFile("dimissionList");
            }
        },
        // Thêm vào methods trong dialog.vue
        processAsyncTranslation(rawText) {
            if (!rawText) return "";
            
            // 1. Thử lấy từ JSON đa ngôn ngữ trước (nếu data đã chuẩn)
            let text = this.getLocalizedFromMultiLang(rawText);
            
            // 2. Nếu text lấy ra vẫn là chuỗi gốc (không phải JSON hoặc JSON chỉ có 1 ngôn ngữ)
            // Và ngôn ngữ hiện tại khác với ngôn ngữ gốc (ví dụ đang chọn EN mà text là VI)
            if (text && typeof text === 'string') {
                
                // Kiểm tra xem đã có trong cache chưa
                const cached = this.getCachedTranslation(text, this.$i18n.locale);
                if (cached) {
                    return cached;
                }
                
                // Nếu chưa có cache, gọi API dịch (chạy ngầm)
                // Lưu ý: Ta cần một cơ chế debounce hoặc check để không gọi API liên tục cho cùng 1 text
                if (!this._translationPending) this._translationPending = new Set();
                const cacheKey = `${text}_${this.$i18n.locale}`;
                
                if (!this._translationPending.has(cacheKey)) {
                    this._translationPending.add(cacheKey);
                    
                    this.translateApprovalRecord(text, this.$i18n.locale).then(translated => {
                        if (translated && translated !== text) {
                            this.setCachedTranslation(text, this.$i18n.locale, translated);
                            this.$forceUpdate(); // Quan trọng: Re-render lại để hiển thị text đã dịch
                        }
                        this._translationPending.delete(cacheKey);
                    }).catch(() => {
                        this._translationPending.delete(cacheKey);
                    });
                }
            }
            
            return text;
        },
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
                // Thử parse JSON
                const obj = (typeof value === 'string' && (value.startsWith('{') || value.startsWith('['))) 
                ? JSON.parse(value) 
                : value;
                if (typeof obj === 'object' && obj !== null) {
                    return obj[this.$i18n.locale] || obj['en'] || obj['vi'] || value;
                }
                return value; // Trả về chuỗi gốc nếu không phải object đa ngữ
            } catch (e) {
                return value; // Nếu lỗi parse, trả về chuỗi gốc
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
            this.form.educationLevel_change = null;
            this.form.specialization_change = "";
            this.form.workExperience_change = null;
            this.form.jobDesc_change = "";
            this.form.attachments.jobDesc_change = undefined;
            this.filePreview.jobDesc_change = false;
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
        onChangeReason(val) {
            if (!this.form || !this.form.attachments || !this.filePreview) return;
            const changeReasonId = (val && typeof val === 'object' && val.id !== undefined) ? val.id : val;
            if (changeReasonId !== 3) {
                this.form.educationLevel_change = null;
                this.form.specialization_change = "";
                this.form.workExperience_change = null;
                this.form.jobDesc_change = "";
                this.form.attachments.jobDesc_change = undefined;
                this.form.attachments.jobDesc_change_size = "";
                this.filePreview.jobDesc_change = false;
                this.form.either_change = "";
                this.form.english_change = "";
                this.form.chinese_change = "";
                this.form.tranlator_change = false;
                this.form.computerSkills_change = [];
                this.form.otherComputerSkills_change = "";
            }
            if (changeReasonId !== 1) {
                this.form.changeQty = "";
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
                this.form.changeReason && (this.form.changeReason.id !== undefined && this.form.changeReason.id !== null ? this.form.changeReason.id : (typeof this.form.changeReason === "number" ? this.form.changeReason : null));
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
                this.settingHistory = response.data.dataEndYear || [];
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
                .filter((dept) => {
                    if (this.isAdmin || this.editData) return true;
                    return dept.department === this.activeUser.division;
                })
                .map((dept) => dept.department)
                ),
                ];
            } catch (error) {
                console.error("Error fetching work experience data:", error);
            }
        },
        //
        //
        async saveDB(isAutoApprove = false) {
            try {
                let requestId;
                // 1. Xác định Request ID
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
                
                // 2. Xác định Loại yêu cầu
                const hasActiveRct = this.showNewYearSection && 
                this.form.newYearRecruitment !== "" && 
                this.form.newYearRecruitment !== null && 
                this.form.newYearRecruitment !== undefined;
                
                const isAmendmentRequest = this.form.changeReason;
                
                // 3. Logic xử lý trạng thái (Status Logic)
                if (hasActiveRct) {
                    // New Year Recruitment: Chỉ gửi activeRct, Backend tự set status_endyear
                    formData.append("activeRct", this.form.newYearRecruitment);
                } else if (isAmendmentRequest) {
                    // Amendment
                    formData.append("status_amendent", "waiting_dept");
                    formData.append("is_amendment", "true");
                    if (this.editData && this.editData.status) {
                        formData.append("status", this.editData.status);
                    } else {
                        formData.append("status", "waiting_dept");
                    }
                } else {
                    // Normal Request
                    formData.append("status", "waiting_dept");
                    formData.append("is_amendment", "false");
                    formData.append("status_amendent", null);
                }
                
                // 4. Validate Form (Bỏ qua nếu là New Year process)
                if (!hasActiveRct) {
                    this.formErrors = {
                        education: false,
                        experience: false,
                        specialization: false,
                        recruitment: false,
                        staffCount: false,
                    };
                    
                    if (this.form.recruitment.reason === 2) {
                        if (!this.resignedEmpListDetails || this.resignedEmpListDetails.length === 0) {
                            this.$notify({
                                title: "Error",
                                text: this.$t("Please upload dimission list file") || "Vui lòng tải lên danh sách thôi việc",
                                type: "error",
                            });
                            return;
                        }
                        if (this.oldEmpError) {
                            this.$notify({
                                title: "Error",
                                text: this.oldEmpError,
                                type: "error",
                            });
                            return;
                        }
                    }
                    
                    if (Object.values(this.formErrors).some((error) => error)) {
                        this.$notify({
                            title: "Error",
                            text: this.$t("Please fill all required fields"),
                            type: "error",
                        });
                        return;
                    }
                }
                
                // 5. Chuẩn bị dữ liệu Submitter
                let submitterData;
                
                if ((this.isEdit || this.isChange) && this.editData && this.editData.submitter) {
                    try {
                        submitterData = typeof this.editData.submitter === "string" 
                        ? JSON.parse(this.editData.submitter) 
                        : { ...this.editData.submitter };
                        
                        submitterData.div = this.user.div || submitterData.div;
                        submitterData.dept = this.user.dept || submitterData.dept;
                        submitterData.unit = this.user.unit || submitterData.unit;
                    } catch (e) {
                        console.error("Error parsing original submitter:", e);
                    }
                }
                
                if (!submitterData) {
                    submitterData = {
                        empno: this.activeUser.empno,
                        name: this.activeUser.name,
                        dept_sumt: this.activeUser.dept_sumt,
                        high_dept: this.activeUser.division,
                        email: this.activeUser.email,
                        dept: this.user.dept,
                        div: this.user.div,
                        unit: this.user.unit,
                    };
                }
                
                // 6. Append dữ liệu vào FormData
                formData.append("loc", this.activeUser.location);
                formData.append("key_in_date", this.form.keyInDate);
                formData.append("submitter", JSON.stringify(submitterData));
                
                if (!hasActiveRct && !isAmendmentRequest) {
                    formData.append("status", "waiting_dept");
                }
                
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
                
                const reasonExp = this.form.recruitment.reason || (this.form.recruitment.otherReasonSelected ? 3 : "");
                formData.append("reason_exp", reasonExp ? String(reasonExp) : "");
                if (this.resignedEmpListDetails && this.resignedEmpListDetails.length > 0) {
                    formData.append("old_emp", JSON.stringify(this.resignedEmpListDetails));
                } else {
                    formData.append("old_emp", this.form.recruitment.oldempno || "");
                }
                
                // Fix lỗi gửi chuỗi "null" cho isOther_reason
                let otherReasonValue = "";
                if (this.form.recruitment.otherReasonSelected) {
                    otherReasonValue = this.form.recruitment.otherReasonSelected;
                } else if (this.form.recruitment.reason === 3) {
                    if (this.form.recruitment.isOther_reason) {
                        otherReasonValue = JSON.stringify(this.form.recruitment.isOther_reason);
                    }
                }
                formData.append("isOther_reason", (otherReasonValue !== null && otherReasonValue !== undefined) ? otherReasonValue : "");
                
                formData.append("date_req", this.form.recruitment.requestDate);
                formData.append("qty_req", JSON.stringify(this.form.staffCount));
                
                let totalQty = this.form.quantity;
                const changeReq = {
                    change_reason: this.form.changeReason,
                    change_qty: this.form.changeQty,
                    change_edu: this.form.educationLevel_change,
                    change_spec: this.form.specialization_change,
                    change_w_exp: this.form.workExperience_change,
                    change_jd: this.form.jobDesc_change,
                };
                
                if (this.isChange && changeReq.change_qty) {
                    totalQty = changeReq.change_qty;
                }
                
                formData.append("total_qty", totalQty);
                formData.append("pos", this.form.pos);
                formData.append("jd", this.form.jobDesc);
                
                // 7. Files
                if (isAmendmentRequest) {
                    if (this.form.attachments.jobDesc_change instanceof File) {
                        const originalName = this.form.attachments.jobDesc_change.name;
                        const fileExt = originalName.split('.').pop();
                        const newFileName = `${requestId}_jobdesc_change.${fileExt}`;
                        const renamedJobDescFileChange = new File(
                        [this.form.attachments.jobDesc_change],
                        newFileName,
                        { type: this.form.attachments.jobDesc_change.type }
                        );
                        formData.append("changeJobDescFile", renamedJobDescFileChange);
                    } else if (this.filePreview.jobDesc_change && typeof this.form.attachments.jobDesc_change === 'string') {
                        formData.append("change_att_jd", this.form.attachments.jobDesc_change);
                    }
                }
                
                if (this.form.attachments.jobDesc instanceof File) {
                    const originalName = this.form.attachments.jobDesc.name;
                    const fileExt = originalName.split('.').pop();
                    const newFileName = `${requestId}_jobdesc.${fileExt}`;
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
                    const renamedDimissionFile = new File(
                    [this.form.attachments.dimissionList],
                    newFileName,
                    { type: this.form.attachments.dimissionList.type }
                    );
                    formData.append("dimissionFile", renamedDimissionFile);
                } else if (this.editData?.att_dis && this.filePreview.dimissionList) {
                    formData.append("att_dis", this.editData.att_dis);
                }
                
                // 8. Language Skills
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
                
                // 9. Computer Skills
                const computerSkillsData = {
                    skills: this.form.computerSkills,
                    others: this.form.otherComputerSkills,
                };
                formData.append("comp_skill", JSON.stringify(computerSkillsData));
                
                // 10. Change Request Info
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
                
                // 11. Other Fields
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
                    resumed_date = new Date(Date.now() + 86400000).toISOString();
                    hold = this.editData?.hold;
                    hold_date = this.editData?.hold_date;
                }
                formData.append('hold', hold);
                formData.append('resumed', resumed);
                formData.append('hold_date', hold_date);
                formData.append('resumed_date', resumed_date);
                
                // 12. Approval Config
                const defaultApproval = [
                { level: 1, type: "dept", approver: { empno: "", name: "", dept: "" }, status: "waiting" },
                { level: 2, type: "div", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 3, type: "hr_rec_sf", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 4, type: "hr", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 5, type: "ad", approver: { empno: "", name: "", dept: "" }, status: "" },
                { level: 6, type: "gm", approver: { empno: "", name: "", dept: "" }, status: "" }
                ];
                
                if (!isAmendmentRequest && !hasActiveRct) {
                    formData.append("approval", JSON.stringify(defaultApproval));
                } else {
                    if (this.editData && this.editData.approval) {
                        formData.append("approval", this.editData.approval);
                    }
                }
                
                formData.append("dataFlow", JSON.stringify(this.dataAppFlowChanged));
                formData.append("emailList", JSON.stringify(this.listEmailDeptManager));
                
                // 13. Cancellation Logic
                const isCancellationRequest = this.form.changeReason?.id === 2;
                
                if (isCancellationRequest) {
                    const recruitedCount = parseInt(this.editData?.recruited || 0);
                    const denyTempData = {
                        fullfilldate: this.getCurrentDate(),
                        fulfilled: "yes",
                        recruited: recruitedCount,
                        stocknumber: 0,
                        status_amendent: "declined"
                    };
                    formData.append("deny_temp", JSON.stringify(denyTempData));
                }
                
                // 14. CALL API
                const response = await this.$axios.post(
                this.api + "saveManpower",
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                }
                );
                
                // 15. HANDLE RESPONSE
                if (response.data.success) {
                    
                    // === LOGIC MỚI: XỬ LÝ AUTO APPROVE (Cho New Year) ===
                    if (isAutoApprove) {
                        if (response.data.data) {
                            // Cập nhật thông tin mới nhất từ server
                            this.selectedItemForAccept.approval = response.data.data.approval;
                            this.selectedItemForAccept.status_endyear = response.data.data.status_endyear;
                            
                            // [QUAN TRỌNG] Gán activeRct để hàm confirmAccept nhận diện được điều kiện
                            // chèn dòng chữ tiếng Hoa
                            this.selectedItemForAccept.activeRct = this.form.newYearRecruitment;
                            
                            // Đồng bộ lại editData (để UI không bị lệch)
                            if (this.editData) {
                                this.editData.approval = response.data.data.approval;
                                this.editData.status_endyear = response.data.data.status_endyear;
                                this.editData.activeRct = this.form.newYearRecruitment;
                            }
                        }
                        
                        // Gọi hàm duyệt ngay lập tức
                        await this.confirmAccept();
                        
                    } else {
                        // === LOGIC CŨ (Lưu thường) ===
                        this.$notify({
                            title: "Success",
                            text: this.$t("Request submitted successfully"),
                            type: "success",
                        });
                        this.dialog = false;
                        this.$emit("requestUpdated");
                        this.clearForm();
                    }
                    
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
                newYearRecruitment: null,
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
            
            if (fileType === 'dimissionList') {
                this.oldEmpList = [];
                this.resignedEmpListDetails = [];
                this.form.recruitment.oldempno = "";
                this.showResignedEmpTable = false;
                this.oldEmpError = "";
            }
        },
        async handleDimissionListChange(event) {
            this.previewFile('dimissionList', event);
            const file = this.form.attachments.dimissionList;
            if (!file) {
                this.clearFile('dimissionList');
                return;
            }

            if (file.name && file.name.match(/\.(xlsx|xls)$/i)) {
                try {
                    const XLSX = await import('xlsx');
                    const reader = new FileReader();
                    reader.onload = async (e) => {
                        try {
                            const data = new Uint8Array(e.target.result);
                            const workbook = XLSX.read(data, { type: 'array' });
                            const firstSheetName = workbook.SheetNames[0];
                            const worksheet = workbook.Sheets[firstSheetName];
                            
                            // range: 1 skips the first row (index 0)
                            const jsonData = XLSX.utils.sheet_to_json(worksheet, { range: 1 });
                            
                            if (jsonData.length > 0) {
                                const firstRowKeys = Object.keys(jsonData[0]).map(k => k.toUpperCase().trim());
                                const hasEmpno = firstRowKeys.some(k => k.includes('EMPNO') || k === 'EMP NO' || k === 'MÃ NV' || k === 'MÃ NHÂN VIÊN');
                                const hasName = firstRowKeys.some(k => k.includes('NAME') || k === 'TÊN');
                                const hasDate = firstRowKeys.some(k => k.includes('DIMISION DATE') || k.includes('DIMISSION DATE') || k.includes('DATE') || k.includes('NGÀY'));
                                
                                if (!hasEmpno || !hasName || !hasDate) {
                                    this.clearFile('dimissionList');
                                    const Swal = (await import('sweetalert2')).default;
                                    Swal.fire({
                                        icon: 'error',
                                        title: this.$t('Invalid Format'),
                                        html: `${this.$t('The uploaded file does not match the required template. Please use the specified format.')}<br><br><a href="${this.$router.options.base}Resign_template.xlsx" download style="color: blue; text-decoration: underline;">${this.$t('Download correct template here')}</a>`,
                                        confirmButtonText: this.$t('Close')
                                    });
                                    return;
                                }
                            }

                            
                            let extractedEmpnos = [];
                            jsonData.forEach(row => {
                                const empno = row['EMPNO'] || row['empno'] || row['Empno'];
                                if (empno) {
                                    let empnoStr = empno.toString().trim();
                                    if (empnoStr.length > 0 && !extractedEmpnos.includes(empnoStr)) {
                                        extractedEmpnos.push(empnoStr);
                                    }
                                }
                            });

                            if (extractedEmpnos.length > 0) {
                                let invalidEmpnos = {};
                                
                                const promises = extractedEmpnos.map(empno => 
                                    this.$axios.post(this.api + 'checkResignEmpNo', {
                                        empno: empno,
                                        req_id: this.editData?.req_id || ''
                                    }).then(res => {
                                        if (res.data && res.data.exists) {
                                            invalidEmpnos[empno] = res.data.req_id || 'Unknown';
                                        }
                                    }).catch(err => {
                                        console.error("Error checking empno", empno, err);
                                    })
                                );

                                await Promise.all(promises);

                                if (Object.keys(invalidEmpnos).length > 0) {
                                    this.dimissionPreviewHasError = true;
                                } else {
                                    this.dimissionPreviewHasError = false;
                                }

                                // Map the json data to our preview data format
                                this.dimissionPreviewData = jsonData.map((row, index) => {
                                    const empno = (row['EMPNO'] || row['empno'] || row['Empno'] || '').toString().trim();
                                    const hasError = invalidEmpnos.hasOwnProperty(empno);
                                    const usedInReqId = invalidEmpnos[empno] || '';
                                    let rawDate = row['DIMISION DATE'] || row['DIMISSION DATE'] || row['Dimission Date'] || '';
                                    let formattedDate = rawDate;
                                    if (typeof rawDate === 'number' && rawDate > 10000) {
                                        // Convert Excel serial date to JS Date
                                        const jsDate = new Date(Date.UTC(1899, 11, 30));
                                        jsDate.setUTCDate(jsDate.getUTCDate() + rawDate);
                                        const d = String(jsDate.getUTCDate()).padStart(2, '0');
                                        const m = String(jsDate.getUTCMonth() + 1).padStart(2, '0');
                                        const y = jsDate.getUTCFullYear();
                                        formattedDate = `${d}/${m}/${y}`;
                                    } else if (typeof rawDate === 'string' && rawDate.match(/^\d{4}-\d{2}-\d{2}/)) {
                                        // Fallback if it parses as YYYY-MM-DD
                                        const parts = rawDate.substr(0, 10).split('-');
                                        formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                                    }

                                    return {
                                        stt: row['STT'] || (index + 1),
                                        empno: empno,
                                        name: row['NAME_S'] || row['Name'] || row['NAME'] || '',
                                        deptNo: row['DEPT_NO'] || row['DEPT NO'] || row['Dept No'] || row['Mã bộ phận'] || '',
                                        deptName: row['DEPT NAME'] || row['DEPT_NAME'] || row['Dept Name'] || '',
                                        position: row['POSITION'] || row['Position'] || row['position'] || row['Chức vụ'] || row['CHỨC VỤ'] || '',
                                        date: formattedDate,
                                        hasError: hasError,
                                        errorMsg: hasError ? `${usedInReqId}` : ''
                                    };
                                }).filter(item => item.empno !== ''); // remove empty rows

                                this.dimissionPreviewDialog = true;
                            } else {
                                this.clearFile('dimissionList');
                                const Swal = (await import('sweetalert2')).default;
                                Swal.fire({
                                    icon: 'warning',
                                    title: this.$t ? this.$t('Warning') : 'Cảnh báo',
                                    text: this.$t ? this.$t('No employee IDs found in the Excel file.') : 'Không tìm thấy mã nhân viên nào trong file Excel.',
                                    confirmButtonText: this.$t ? this.$t('Close') : 'Đóng'
                                });
                            }
                        } catch (err) {
                            console.error("Error parsing excel", err);
                        }
                    };
                    reader.readAsArrayBuffer(file);
                } catch (error) {
                    console.error("Error loading xlsx", error);
                }
            }
        },
        closeDimissionPreview() {
            this.dimissionPreviewDialog = false;
            this.clearFile('dimissionList');
        },
        confirmDimissionPreview() {
            this.dimissionPreviewDialog = false;
            const validEmpnos = this.dimissionPreviewData.filter(x => !x.hasError).map(x => x.empno);
            const combined = [...new Set([...this.oldEmpList, ...validEmpnos])];
            this.handleOldEmpListChange(combined);
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
            if (data.activeRct !== null) {
                this.form.newYearRecruitment = parseInt(data.activeRct);
            }
            const submitter = JSON.parse(data.submitter);
            if (submitter) {
                this.user.div = submitter.div;
                this.$nextTick(() => {
                    this.user.dept = submitter.dept;
                    this.$nextTick(() => {
                        this.user.unit = submitter.unit;
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
                this.form.educationLevel_change = null;
                this.form.specialization_change = "";
                this.form.workExperience_change = null;
                this.form.jobDesc_change = "";
                this.form.attachments.jobDesc_change = undefined;
                this.filePreview.jobDesc_change = false;
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
                            if (lastChangeReq) {
                                this.form.educationLevel_change = lastChangeReq.change_edu !== undefined ? lastChangeReq.change_edu : null;
                                this.form.specialization_change = lastChangeReq.change_spec !== undefined ? lastChangeReq.change_spec : "";
                                this.form.workExperience_change = lastChangeReq.change_w_exp !== undefined ? lastChangeReq.change_w_exp : null;
                                this.form.jobDesc_change = lastChangeReq.change_jd !== undefined ? lastChangeReq.change_jd : "";
                                if (lastChangeReq.change_att_jd) {
                                    this.form.attachments.jobDesc_change = lastChangeReq.change_att_jd;
                                    this.filePreview.jobDesc_change = true;
                                }
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
            const oldEmpData = data.old_emp || data.old_empno || "";
            this.form.recruitment = {
                reason: "", // clear initial value
                oldempno: oldEmpData,
                requestDate: data.date_req,
                otherReasonSelected: "",  // clear initial value
                isOther_reason: { vi: "", en: "", cn: "" }
            };
            
            if (oldEmpData) {
                try {
                    const parsed = JSON.parse(oldEmpData);
                    if (Array.isArray(parsed)) {
                        const list = parsed.map(item => typeof item === 'object' ? item.empno : item);
                        this.handleOldEmpListChange(list);
                    } else if (typeof parsed === 'object' && parsed.empno) {
                        this.handleOldEmpListChange([parsed.empno]);
                    } else {
                        this.oldEmpRawText = oldEmpData;
                        this.handleOldEmpListChange(oldEmpData);
                    }
                } catch (e) {
                    this.oldEmpRawText = oldEmpData;
                    this.handleOldEmpListChange(oldEmpData);
                }
            } else {
                this.oldEmpList = [];
                this.resignedEmpListDetails = [];
            }
            
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
                            unit: parsedSubmitter.unit,
                            div: parsedSubmitter.div,
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
                    dept: submitterInfo.unit || submitterInfo.dept_sumt,
                    high_dept: submitterInfo.div || submitterInfo.division,
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
        //
        async confirmAccept() {
            try {
                const flowData = await this.getFlowDataForItem(this.selectedItemForAccept);
                
                const statusToLevel = {
                    waiting_dept: "dept",
                    waiting_div: "div", 
                    waiting_hr_rec_sf: "hr_rec_sf",
                    waiting_hr: "hr",
                    waiting_ad: "ad",
                    waiting_gm: "gm"
                };
                
                // --- SỬA LỖI: Xác định đúng trạng thái hiện tại (Ưu tiên status_endyear) ---
                let currentStatus = this.selectedItemForAccept.status;
                const endYearStatus = this.selectedItemForAccept.status_endyear;
                const amendmentStatus = this.selectedItemForAccept.status_amendent;
                
                if (endYearStatus && endYearStatus !== 'completed' && endYearStatus !== 'declined') {
                    currentStatus = endYearStatus; // Ưu tiên 1: End Year
                } else if (amendmentStatus && amendmentStatus !== 'completed' && amendmentStatus !== 'declined') {
                    currentStatus = amendmentStatus; // Ưu tiên 2: Amendment
                } else {
                    currentStatus = this.selectedItemForAccept.status; // Ưu tiên 3: Gốc
                }
                
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
                
                // --- LOGIC THÊM DÒNG CHỮ TIẾNG HOA ---
                // Khi đang ở cấp Dept (waiting_dept) VÀ là đơn Quyết toán năm (isActiveRct)
                if (levelKey === "dept" && isActiveRct) {
                    // Tạo record mới chứa dòng chữ xác nhận
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
                    // Logic cũ: Cập nhật record cuối cùng
                    if (approvalRecords.length === 0) {
                        approvalRecords.push({});
                    }
                    const currentRecord = approvalRecords[approvalRecords.length - 1];
                    if (currentRecord && levelKey) {
                        currentRecord[levelKey] = this.form.approval_record || "";
                    }
                }
                
                const params = {
                    user_empno: this.activeUser.group_empno || this.activeUser.empno,
                    dataReq: {
                        dataFlow: flowData,
                        id: this.selectedItemForAccept.id,
                        status: this.selectedItemForAccept.status,
                        status_amendent: this.selectedItemForAccept.status_amendent,
                        status_endyear: this.selectedItemForAccept.status_endyear, 
                        approval: this.selectedItemForAccept.approval,
                        approval_record: JSON.stringify(approvalRecords), 
                        activeRct: this.form.newYearRecruitment,
                    },
                };
                
                if (this.isHrManager) {
                    params.dataReq.sub_pos = this.form.sub_position || null;
                }
                
                const res = await this.$axios.post(this.api + "acceptRequest", params);
                
                if (res.data.success) {
                    this.$notify({
                        title: "Success",
                        text: this.$t("Request accepted successfully"),
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
                    user_empno: this.activeUser.group_empno || this.activeUser.empno,
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
                // --- Phần xử lý dịch tự động (giữ nguyên) ---
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
                
                // --- BỔ SUNG LOGIC XÁC ĐỊNH CẤP TRẢ VỀ ---
                let targetReturnLevel = "waiting_dept"; // Mặc định trả về cấp Dept (Cấp 1)
                
                // Nếu đang là quy trình New Year
                if (this.isNewYearProcessing) {
                    // Kiểm tra nếu đơn đang ở cấp Division (waiting_div)
                    if (this.editData.status === 'waiting_div' || this.editData.status_amendent === 'waiting_div') {
                        targetReturnLevel = "waiting_dept"; // Trả về cho Level 1 (Dept Manager)
                    }
                } else {
                    // Logic cho các trường hợp khác (giữ nguyên logic cũ hoặc lấy từ biến chọn trên UI nếu có)
                    targetReturnLevel = this.returnToLevel || "waiting_dept"; 
                }
                
                // Send return request
                const response = await this.$axios.post(this.api + "returnRequest", {
                    id: this.editData.id,
                    reason: JSON.stringify(this.returnReason),
                    returnToLevel: targetReturnLevel, // <--- Sử dụng biến đã tính toán
                    user_empno: this.activeUser.group_empno || this.activeUser.empno,
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
            if (this.showNewYearSection && (this.form.newYearRecruitment === 0 || this.form.newYearRecruitment === 1)) {
                this.selectedItemForAccept = this.editData;
                this.saveDB(true); 
            } else {
                this.saveDB(false);
            }
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
                dimissionList: false,
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
                this.clearFile("dimissionList");
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
                console.log(res.data);
                return res.data.flow_data || [];
            } catch (error) {
                console.error("Error fetching flow data for item:", error);
                return [];
            }
        },
    },
    computed: {
        dimissionPreviewHeaders() {
            return [
                { text: 'STT', value: 'stt' },
                { text: this.$t('EmpNo') || 'EMPNO', value: 'empno' },
                { text: this.$t('Name') || 'Name', value: 'name' },
                { text: this.$t('Dept No') || 'Dept No', value: 'deptNo' },
                { text: this.$t('Dept') || 'Dept Name', value: 'deptName' },
                { text: this.$t('Position') || 'Position', value: 'position' },
                { text: this.$t('Dimission Date') || 'Dimission Date', value: 'date' },
                { text: this.$t('Status') || 'Status', value: 'status' }
            ];
        },
        showPreviousApprovalHistory() {
            
            if (!this.editData) return false;
            try {
                const logs = typeof this.editData.logs === 'string' ? 
                JSON.parse(this.editData.logs) : this.editData.logs;
                return Array.isArray(logs) && logs.length > 0;
            } catch (e) {
                console.error('Error parsing logs:', e);
                return false;
            }
        },
        
        previousApprovalStepper() {
            return 1;
        },
        previousApprovalCycles() {
            if (!this.activeUser?.empno) return [];
            if (!this.editData || !this.editData.logs) return [];
            
            try {
                let logs = typeof this.editData.logs === 'string' ?
                JSON.parse(this.editData.logs) : this.editData.logs;
                
                if (!Array.isArray(logs) || logs.length === 0) return [];
                
                let approvalRecords = [];
                if (this.editData.approval_record) {
                    try {
                        approvalRecords = typeof this.editData.approval_record === 'string' 
                        ? JSON.parse(this.editData.approval_record) 
                        : this.editData.approval_record;
                    } catch (e) { console.error("Error parsing approval_record", e); }
                }
                
                const hasCurrentCycle = this.editData.approval && 
                this.editData.approval !== '[]' && 
                this.editData.approval !== 'null';
                
                const levelNames = {
                    1: this.$t("Department Manager"),
                    2: this.$t("Division Manager"),
                    3: this.$t("HR Recruitment Staff"),
                    4: this.$t("HR Manager"),
                    5: this.$t("AD Manager"),
                    6: this.$t("GM Manager")
                };
                
                return logs.map((cycleLogs, cycleIndex) => {
                    if (!Array.isArray(cycleLogs)) return null;
                    
                    const isLastRecordIndex = cycleIndex === (approvalRecords.length - 1);
                    const shouldHideRecord = isLastRecordIndex && hasCurrentCycle;
                    const cycleRecordObj = shouldHideRecord ? {} : (approvalRecords[cycleIndex] || {});
                    
                    const steps = cycleLogs
                    .filter(lv => {
                        if (!lv.approver || !lv.approver.empno) return false;
                        return lv.status && ["waiting", "approved", "rejected", "returned"].includes(lv.status);
                    })
                    .map(lv => {
                        const reasonDeniedText = this.processAsyncTranslation(lv.reason_denied);
                        const returnReasonText = this.processAsyncTranslation(lv.return_reason);
                        
                        let rawNote = cycleRecordObj[lv.type] || "";
                        
                        if (!rawNote && lv.approval_record && !shouldHideRecord) {
                            rawNote = lv.approval_record;
                        }
                        
                        if (rawNote && typeof rawNote === 'string' && rawNote.includes('SYSTEM (AUTO)')) {
                            if (lv.approver.empno !== 'SYSTEM') {
                                rawNote = ""; 
                            }
                        }
                        
                        const finalNote = this.processAsyncTranslation(rawNote);
                        
                        return {
                            level: lv.level,
                            levelName: levelNames[lv.level] || lv.type || "",
                            approver: lv.approver || { empno: '', name: '', dept: '' },
                            status: lv.status,
                            processed_at: lv.processed_at,
                            reason_denied: reasonDeniedText, 
                            return_reason: returnReasonText, 
                            shouldShowError: lv.status === "rejected",
                            approval_record: finalNote       
                        };
                    });
                    
                    return {
                        id: cycleIndex + 1,
                        name: `${this.$t('Cycle')} ${cycleIndex + 1}`,
                        steps: steps
                    };
                }).filter(cycle => cycle && cycle.steps.length > 0);
                
            } catch (e) {
                console.error("Error in previousApprovalCycles:", e);
                return [];
            }
        },
        
        getApprovalLogsData(item) {
            try {
                let logsData = [];
                if (item.logs) {
                    const parsedLogs = JSON.parse(item.logs);
                    const approvalRecords = item.approval_record ? JSON.parse(item.approval_record) : [];
                    
                    const hasCurrentCycle = item.approval && item.approval !== '[]' && item.approval !== 'null';
                    
                    if (Array.isArray(parsedLogs[0])) {
                        parsedLogs.forEach((cycle, cycleIndex) => {
                            const cycleApprovalRecord = approvalRecords[cycleIndex] || {};
                            
                            // LOGIC FIX:
                            // 1. Kiểm tra xem đây có phải là record cuối cùng trong mảng không
                            const isLastRecordSet = cycleIndex === (approvalRecords.length - 1);
                            
                            // 2. Nếu là record cuối VÀ đang có Current Cycle -> Record này thuộc về Current Cycle
                            // -> Ẩn nó khỏi History để tránh hiển thị sai/trùng lặp.
                            const shouldHideRecord = isLastRecordSet && hasCurrentCycle;
                            
                            const cycleData = cycle
                            .filter(log => log.approver && log.approver.empno)
                            .map((log, index) => {
                                const recordKey = log.type;
                                
                                // Áp dụng logic ẩn record tại đây
                                let approvalRecord = shouldHideRecord ? '' : (cycleApprovalRecord[recordKey] || '');
                                
                                // Giữ lại logic cũ (nếu có): Xóa record SYSTEM hiển thị sai ở user thường
                                if (approvalRecord && typeof approvalRecord === 'string' && approvalRecord.includes('SYSTEM (AUTO)')) {
                                    if (log.approver.empno !== 'SYSTEM' && log.approver.name !== 'SYSTEM (AUTO)') {
                                        approvalRecord = ''; 
                                    }
                                }
                                
                                return {
                                    level: index + 1,
                                    cycle: this.$t('Cycle') + ' ' + (cycleIndex + 1),
                                    type: this.getApprovalLevelName(log.type),
                                    empno: log.approver.empno,
                                    name: log.approver.name,
                                    dept: log.approver.dept,
                                    status: log.status || '',
                                    reason_denied: log.reason_denied || '',
                                    return_reason: log.return_reason || '',
                                    approval_record: approvalRecord,
                                    processed_at: log.processed_at,
                                    cycleIndex: cycleIndex + 1,
                                };
                            });
                            logsData = [...logsData, ...cycleData];
                        });
                    }
                }
                
                return logsData;
            } catch (error) {
                console.error('Error parsing approval logs:', error);
                return [];
            }
        },
        isNewYearProcessing() {
            if (this.showNewYearSection) return true;
            if (this.editData) {
                const val = this.editData.activeRct;
                if (val !== null && val !== undefined && val !== '') {
                    return true;
                }
            }
            return false;
        },
        noteText() {
            // SỬA: Đảo thứ tự, ưu tiên check editData trước để tránh hiển thị dữ liệu cũ khi đang await API
            const oldId = (this.editData && this.editData.req_id_oldyear) || this.form.req_id_oldyear;
            
            if (oldId) {
                return `${this.$t("Based on")} ${oldId} ${this.$t("continue recruitment based on conditions")}`;
            }
            // Nếu không có oldId, trả về note/remark gốc nếu có
            return this.editData?.note || "";
        },
        disableNewYearRecruitment() {
            if (!this.editData) return false;
            if (this.editData.req_id_oldyear && this.editData.req_id_oldyear.trim() !== '') {
                return true;
            }
            if( this.editData.approval && 
            JSON.parse(this.editData.approval).some(level => level.status === 'returned')) {
                return false;
            }
            if ( this.editData.activeRct !== null ) {
                return true;
            }
            return false;
        },
        showNewYearSection() {
            // 1. Kiểm tra dữ liệu cơ bản
            if (!this.editData || !this.activeUser?.empno) {
                return false;
            }
            
            // 2. Ẩn nếu là đơn con (đơn được tạo ra từ năm cũ)
            if (this.editData.req_id_oldyear && this.editData.req_id_oldyear.trim() !== '') {
                return false;
            }
            
            // KIỂM TRA THỜI GIAN
            if (!this.settingHistory || !Array.isArray(this.settingHistory)) {
                return false;
            }
            const currentYear = new Date().getFullYear();
            const today = new Date().toISOString().split('T')[0];
            const setting = this.settingHistory.find(s => parseInt(s.year) === currentYear);
            
            if (!setting || !setting.email_date || !setting.deadline_date) {
                return false;
            }
            
            const isWithinRange = (today >= setting.email_date) && (today <= setting.deadline_date);
            if (!isWithinRange) {
                return false;
            }
            
            // 3. Kiểm tra trạng thái hợp lệ
            const validStatuses = ['completed'];
            const currentStatus = this.editData.status;
            const amendmentStatus = this.editData.status_amendent;
            
            // Nếu trạng thái không nằm trong danh sách cho phép -> ẩn
            if (!validStatuses.includes(currentStatus) && !validStatuses.includes(amendmentStatus)) {
                return false;
            }
            
            // --- LOGIC MỚI: ƯU TIÊN HIỂN THỊ NẾU ĐÃ CÓ KẾT QUẢ ---
            // Nếu activeRct đã có giá trị (0 hoặc 1), cho phép hiển thị luôn để user xem kết quả
            const hasActiveRct = this.editData.activeRct !== null && 
            this.editData.activeRct !== undefined && 
            this.editData.activeRct !== '';
            
            if (hasActiveRct) {
                return true;
            }
            // -----------------------------------------------------
            
            // 4. Nếu chưa có kết quả (activeRct là null), kiểm tra xem người dùng có phải Manager không
            // Chỉ Manager level 1, 2 mới được thấy để thao tác chọn
            const currentUserEmpno = String(this.activeUser.empno).trim();
            
            if (this.dataFlow && Array.isArray(this.dataFlow) && this.dataFlow.length > 0) {
                const firstTwoLevels = this.dataFlow.slice(0, 2);
                
                const isInFirstTwoLevels = firstTwoLevels.some(level => {
                    if (!level.managers || !Array.isArray(level.managers)) {
                        return false;
                    }
                    
                    return level.managers.some(manager => {
                        if (String(manager.empno).trim() === currentUserEmpno) {
                            return true;
                        }
                        
                        if (manager.deputies && Array.isArray(manager.deputies)) {
                            return manager.deputies.some(deputy =>
                            String(deputy.empno).trim() === currentUserEmpno &&
                            deputy.status === true
                            );
                        }
                        
                        return false;
                    });
                });
                
                return isInFirstTwoLevels;
            }
            
            return false;
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
        selectedOtherReasonEnText() {
            if (!this.form.recruitment.otherReasonSelected || !this.otherReason) return "";
            const found = this.otherReason.find(r => r.id === this.form.recruitment.otherReasonSelected);
            if (!found) return "";
            try {
                const parsed = typeof found.reason_name === "string" ? JSON.parse(found.reason_name) : found.reason_name;
                return (parsed.en || "").toLowerCase();
            } catch (e) {
                return (found.reason_name || "").toLowerCase();
            }
        },
        templateFileName() {
            if (this.form.recruitment.reason === 2) return 'Resign_template.xlsx';
            if (this.form.recruitment.reason === 3) {
                const enText = this.selectedOtherReasonEnText;
                if (enText.includes("transfer")) return 'Transfer_template.xlsx';
                if (enText.includes("maternity")) return 'Maternity _template.xlsx';
                if (enText.includes("military")) return 'Military service_template.xlsx';
            }
            return null;
        },
        showDimissionFileInput() {
            return this.templateFileName !== null;
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
            return Number(this.form.suppType) === 1 ? 'mpr_flow2' : 'mpr';
        },
        
        // Update flowKey to include changeReason for proper reactivity
        flowKey() {
            const changeReasonId = this.form.changeReason?.id || '';
            const suppType = this.form.suppType || '';
            const empno = this.flowEmpno || '';
            const locale = this.$i18n ? this.$i18n.locale : '';
            const div = this.user.div || '';
            const unit = this.user.unit || '';
            // include locale so component remounts when language changes
            return `${this.getAppCode}-${empno}-${suppType}-${changeReasonId}-${locale}-${div}-${unit}`;
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
        getNewYearShortFlow() {
            const locale = this.$i18n.locale;
            
            // Chuỗi mũi tên thống nhất
            const arrow = ' ➜ '; 
            
            if (locale === 'cn' || locale === 'tw') { // Xử lý cho tiếng Trung (CN/TW)
                return `人力資源部${arrow}部級主管${arrow}處級主管`;
            } 
            else if (locale === 'en') { // Tiếng Anh
                return `HR Department${arrow}Department manager${arrow}Division manager`;
            } 
            else { // Mặc định là Tiếng Việt (vi)
                return `Bộ phận ADHR${arrow}Chủ quản bộ phận${arrow}Chủ Quản Cấp Sở`;
            }
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
            if (this.isChange) return true;
            
            if (this.isEdit && this.editData && this.editData.req_id_change) {
                return true;
            }
            
            if(this.editData && this.editData.approval && 
            JSON.parse(this.editData.approval).some(level => level.status === 'returned')) {
                return false;
            }
            if (this.editData && !this.editData.req_id_change && 
            JSON.parse(this.editData.submitter).empno === this.activeUser.empno) {
                return true; 
            }
            
            if(this.isEdit) return true;
            return !!this.editData && !this.isCreating;
        },
        disableSaveButton() {
            if (this.showNewYearSection) {
                const val = this.form.newYearRecruitment;
                return (val === "" || val === null || val === undefined);
            }
            const reasonSelected = !!(
                this.form.recruitment &&
                this.form.recruitment.reason &&
                (
                    this.form.recruitment.reason === 1 ||
                    (this.form.recruitment.reason === 2 && this.oldEmpList && this.oldEmpList.length > 0 && this.resignedEmpListDetails && this.resignedEmpListDetails.length > 0) ||
                    (this.form.recruitment.reason === 3 && this.form.recruitment.otherReasonSelected)
                )
            );
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
            // Nếu chọn Replacement (2) mà chưa có ds người thôi việc -> ẩn nút Submit
            if (
                this.form.recruitment?.reason === 2 &&
                (!this.oldEmpList || this.oldEmpList.length === 0 || !this.resignedEmpListDetails || this.resignedEmpListDetails.length === 0)
            ) {
                return false;
            }

            // LOGIC CHO PHẦN NEW YEAR RECRUITMENT
            if (this.showNewYearSection) {
                // 1. Nếu user hiện tại có quyền duyệt -> ẨN nút Submit
                if (this.canApproveItem) {
                    return false;
                }
                
                // 2. [ĐÃ SỬA] Kiểm tra nếu trạng thái là 'waiting_dept' ở status gốc HOẶC status_endyear
                // Nếu đang chờ ở dept (do mới tạo hoặc bị trả về), phải hiện nút Submit
                if (this.editData.status === 'waiting_dept' || this.editData.status_endyear === 'waiting_dept') {
                    return true;
                }
                
                // 3. Nếu đã có activeRct (đã submit rồi) và không phải trạng thái chờ sửa -> ẨN nút
                const hasNewYearData = this.editData &&
                this.editData.activeRct !== null &&
                this.editData.activeRct !== undefined &&
                this.editData.activeRct !== '';
                
                if (hasNewYearData) {
                    return false;
                }
                
                return true;
            }
            
            // --- CÁC LOGIC KHÁC GIỮ NGUYÊN ---
            if (!this.editData) {
                return true;
            }
            // ... (phần còn lại của hàm giữ nguyên)
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
                if (Number(this.form.suppType) === 1) {
                    return true; // Include all positions
                } else if (Number(this.form.suppType) === 2) {
                    // return type.id !== 1;
                    return type.id === 3;
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
            const endYearStatus = this.editData.status_endyear; // Cột mới
            
            try {
                const approvalData = this.editData.approval ? JSON.parse(this.editData.approval) : [];
                const hasReturnedStatus = approvalData.some(level => level.status === "returned");
                if (hasReturnedStatus) return false;
                
                // Nếu cả status gốc và amendment đã xong/từ chối, và KHÔNG có endYearStatus đang chạy -> return false
                if ((originalStatus === 'completed' || originalStatus === 'declined') &&
                (!amendmentStatus || amendmentStatus === 'completed' || amendmentStatus === 'declined') &&
                (!endYearStatus || endYearStatus === 'completed' || endYearStatus === 'declined')) {
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
                
                // Ưu tiên 1: End Year Status
                if (endYearStatus && 
                endYearStatus !== 'completed' && 
                endYearStatus !== 'declined') {
                    currentLevel = statusToLevel[endYearStatus];
                } 
                // Ưu tiên 2: Amendment Status
                else if (amendmentStatus &&
                amendmentStatus !== 'completed' &&
                amendmentStatus !== 'declined') {
                    currentLevel = statusToLevel[amendmentStatus];
                } 
                // Ưu tiên 3: Original Status
                else {
                    currentLevel = statusToLevel[originalStatus];
                }
                
                if (currentLevel === undefined) return false;
                
                const currentFlow = this.dataFlow[currentLevel];
                if (!currentFlow?.managers) return false;
                
                const checkEmp = (empnoStr) => {
                    if (!empnoStr) return false;
                    const e = empnoStr.toLowerCase();
                    return (this.activeUser.empno && e === this.activeUser.empno.toLowerCase()) || 
                    (this.activeUser.group_empno && e === this.activeUser.group_empno.toLowerCase());
                };
                
                return currentFlow.managers.some(manager => {
                    if (checkEmp(manager.empno)) return true;
                    
                    return manager.deputies?.some(deputy =>
                    checkEmp(deputy.empno) &&
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
            if (!this.activeUser?.empno) {
                console.warn('empno is empty, skipping approval history');
                return [];
            }
            
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
                
                const translatedRecords = {};
                for (const key in currentApprovalRecord) {
                    const text = currentApprovalRecord[key];
                    if (text) {
                        const cached = this.getCachedTranslation(text, this.$i18n.locale);
                        if (cached) {
                            translatedRecords[key] = cached;
                        } else {
                            this.translateApprovalRecord(text, this.$i18n.locale).then(translated => {
                                this.setCachedTranslation(text, this.$i18n.locale, translated);
                                translatedRecords[key] = translated;
                                this.$forceUpdate();
                            });
                            translatedRecords[key] = text;
                        }
                    }
                }
                
                const firstWaitingStep = approvalArr.find(lv => lv.status === 'waiting');
                
                return approvalArr
                .filter(lv => {
                    if (lv.approver && lv.approver.empno && ["approved", "rejected"].includes(lv.status)) {
                        return true;
                    }
                    if (firstWaitingStep && lv.level === firstWaitingStep.level) {
                        return true;
                    }
                    return false;
                })
                .map(lv => {
                    const recordKey = lv.type || "";
                    const record = translatedRecords[recordKey] || "";
                    
                    const reasonDeniedText = this.getLocalizedFromMultiLang(lv.reason_denied);
                    const returnReasonText = this.getLocalizedFromMultiLang(lv.return_reason);
                    
                    let approverInfo = lv.approver;
                    if (firstWaitingStep && lv.level === firstWaitingStep.level && (!lv.approver || !lv.approver.empno)) {
                        const flowIndex = lv.level - 1;
                        const currentFlow = this.dataFlow && this.dataFlow[flowIndex];
                        if (currentFlow && currentFlow.managers && currentFlow.managers.length > 0) {
                            const managerNames = currentFlow.managers.map(m => {
                                let nameText = m.name || m.empno;
                                const deputies = m.deputies || [];
                                const activeDeputies = deputies.filter(d => d.status);
                                if (activeDeputies.length > 0) {
                                    nameText += ` (${this.$t('Deputies')}: ` + activeDeputies.map(d => d.name || d.empno).join(', ') + `)`;
                                }
                                return nameText;
                            }).join(', ');
                            approverInfo = {
                                empno: 'Pending',
                                name: managerNames,
                                dept: ''
                            };
                        } else {
                            approverInfo = {
                                empno: 'Pending',
                                name: this.$t('Manager'),
                                dept: ''
                            };
                        }
                    }
                    
                    return {
                        level: lv.level,
                        levelName: levelNames[lv.level] || lv.type || "",
                        approver: approverInfo,
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
                ? this.$t("Edit request") + " [ " + this.editData.req_id + " ]"
                : this.$t("Change request") + " [ " + this.editData.req_id + " ]";
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
        showAmendmentRequirementFields() {
            if (!this.form.changeReason) return false;
            const id = this.form.changeReason.id;
            return id === 3;
        },
        showLanguageAndComputerSkills() {
            return this.showAmendmentRequirementFields;
        },
        isAdmin() {
            const sessionUser = this.$session.get("mpr");
            if (sessionUser && sessionUser.app_roles) {
                return sessionUser.app_roles.some(r => r.app === "mpr" && r.role === "admin");
            }
            return false;
        },
    },
    mounted() {
        this.$nextTick(() => {
            const sessionUser = this.$session.get("mpr");
            if (sessionUser) {
                // === KIỂM TRA EMPNO ===
                if (!sessionUser.empno || sessionUser.empno.trim() === '') {
                    console.warn('Session empno is empty');
                    return;
                }
                
                this.activeUser = {
                    empno: sessionUser.empno,
                    name: sessionUser.name,
                    dept_sumt: sessionUser.dept,
                    division: sessionUser.hight_dept,
                    location: sessionUser.location,
                    email: sessionUser.email,
                    group_empno: sessionUser.group_empno,
                };
                
                // === GỌI LẠI CÁC HÀNG CẢN EMPNO ===
                this.getReq_id();
                this.getChangeReason();
                this.getDataModel().then(() => {
                    if (this.editData) {
                        this.populateForm(this.editData);
                    }
                });
                
                this.$forceUpdate();
            } else {
                console.warn('No session user found');
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
            "Req.date": "Req. Date",
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
            "approved": "Approved",
            "returned": "Returned",
            "Unknown status": "Unknown status",
            "No data available": "No data available",
            "Submitter": "Submitter",
            "Rows per page": "Rows per page",
            "Cancel request": "Cancel request",
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
            "continue recruitment based on conditions": "recruitment conditions to continue",
            "Request new year:": "Annual Requirement Consolidation Settings Process: ",
            "Request_ori:":"Standard Application Process: ",
            "Cycle":"Cycle",
            "Other": "Other",
            "Note": "Note",
            "Waiting department": "Waiting for Department",
            "Waiting Division": "Waiting for Division",
            "Waiting HR Rec SF": "Waiting for HR Recruitment",
            "Waiting HR": "Waiting for HR",
            "No.": "No.",
            "Settings": "Settings",
            "Basic Request Information": "Basic Request Information",
            "Qualifications & Job Description": "Qualifications & Job Description",
            "Amendment Request Details": "Amendment Request Details",
            "Resigned Employee IDs (Type and press Enter or Comma)...": "Resigned Employee IDs (Type and press Enter or Comma)...",
            "Resigned Employees List": "Resigned Employees List",
            "In Date": "In Date",
            "Validation": "Validation",
            "Valid": "Valid",
            "EmpNo": "EmpNo",
            "Department": "Department",
            "Type_EmpNo_Hint": "Type employee ID then press <kbd>Enter</kbd> or comma <kbd>,</kbd> to add",
            "Download template": "Download template",
            "Download template here": "Download template here",
            "Download correct template here": "Download correct template here",
            "Invalid Employee IDs Found": "Invalid Employee IDs Found",
            "Review Employee IDs": "Review Employee IDs",
            "The highlighted IDs have been used for another request. Please update the file.": "The highlighted IDs have been used for another request. Please update the file.",
            "Invalid Format": "Invalid Format",
            "The uploaded file does not match the required template. Please use the specified format.": "The uploaded file does not match the required template. Please use the specified format.",
            "No employee IDs found in the Excel file.": "No employee IDs found in the Excel file.",
            "Please enter at least one employee ID": "Please enter at least one employee ID",
            "Close": "Close",
            "Employee not found:": "Employee not found:",
            "Error fetching employee info for:": "Error fetching employee info for:",
            "Dept No": "Dept No"
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
            "Req.date": "Ngày vào làm",
            "Expansion of Staffing": "Mở rộng biên chế",
            "Replacement": "Bổ sung thôi việc",
            "Old empno": "Mã số công nhân-nhân viên nghỉ việc",
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
            "approved": "Đã phê duyệt",
            "returned": "Đã trả về",
            "Unknown status": "Trạng thái không xác định",
            "No data available": "Không có dữ liệu",
            "Submitter": "Người nộp",
            "Rows per page": "Số hàng mỗi trang",
            "Cancel request": "Hủy yêu cầu",
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
            "continue recruitment based on conditions": "để tiếp tục tuyển dụng theo điều kiện cũ",
            "Request new year:": "Lưu trình tổng kết nhu cầu tuyển dụng năm: ",
            "Request_ori:":"Lưu trình phê duyệt tiêu chuẩn：",
            "Cycle":"Chu kỳ",
            "Waiting department": "Chờ bộ phận",
            "Waiting Division": "Chờ cấp sở",
            "Waiting HR Rec SF": "Chờ nhân viên tuyển dụng",
            "Waiting HR": "Chờ trưởng phòng nhân sự",
            "No.": "Số thứ tự",
            "Other": "Khác",
            "Note": "Ghi chú",
            "Settings": "Cài đặt",
            "Basic Request Information": "Thông tin cơ bản",
            "Qualifications & Job Description":"Yêu cầu về trình độ và mô tả công việc",
            "Amendment Request Details":"Chi tiết yêu cầu thay đổi",
            "Resigned Employee IDs (Type and press Enter or Comma)...": "Mã NV thôi việc (Gõ và nhấn Enter hoặc Phẩy)...",
            "Resigned Employees List": "Danh sách nhân viên thôi việc",
            "In Date": "Ngày vào làm",
            "Validation": "Kiểm tra",
            "Valid": "Hợp lệ",
            "EmpNo": "Mã NV",
            "Department": "Bộ phận",
            "Type_EmpNo_Hint": "Gõ mã nhân viên rồi nhấn <kbd>Enter</kbd> hoặc dấu phẩy <kbd>,</kbd> để thêm",
            "Dimission list file": "File danh sách thôi việc",
            "Download template": "Tải file mẫu",
            "Download template here": "Tải file mẫu tại đây",
            "Download correct template here": "Tải file mẫu chuẩn tại đây",
            "Invalid Employee IDs Found": "Mã nhân viên không hợp lệ",
            "Review Employee IDs": "Xác nhận danh sách nhân viên",
            "The highlighted IDs have been used for another request. Please update the file.": "Các mã được tô đỏ đã được dùng cho đơn khác. Vui lòng cập nhật lại file.",
            "Invalid Format": "Sai định dạng",
            "The uploaded file does not match the required template. Please use the specified format.": "File tải lên không đúng định dạng quy định. Phải sử dụng đúng định dạng.",
            "No employee IDs found in the Excel file.": "Không tìm thấy mã nhân viên nào trong file Excel.",
            "Please enter at least one employee ID": "Vui lòng nhập ít nhất một mã nhân viên",
            "Close": "Đóng",
            "Employee not found:": "Không tìm thấy nhân viên:",
            "Error fetching employee info for:": "Lỗi khi lấy thông tin nhân viên:",
            "Dept No": "Mã bộ phận"
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
            "approved": "已批准",
            "returned": "已退回",
            "Unknown status": "未知状态",
            "No data available": "暂无数据",
            "Submitter": "提交人",
            "Rows per page": "每页行数",
            "Cancel request": "取消请求",
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
            "continue recruitment based on conditions": "招募條件續招",
            "Request new year:": "年度需求結算流程：",
            "Request_ori:":"一般申請流程：",
            "Cycle": "週期",
            "Waiting department": "等待部門",
            "Waiting Division": "等待處級",
            "Waiting HR Rec SF": "等待招聘專員",
            "Waiting HR": "等待人資主管",
            "No.": "序号",
            "Other": "其他",
            "Note": "笔记",
            "Settings": "设置",
            "Basic Request Information": " 基本请求信息",
            "Qualifications & Job Description": "职位描述和资质要求",
            "Amendment Request Details":"更改请求详细信息",
            "Resigned Employee IDs (Type and press Enter or Comma)...": "離職工號（輸入後按 Enter 或逗號）...",
            "Resigned Employees List": "離職人員名單",
            "In Date": "到職日",
            "Validation": "驗證",
            "Valid": "有效",
            "EmpNo": "工號",
            "Department": "部門",
            "Type_EmpNo_Hint": "輸入員工工號，然後按 <kbd>Enter</kbd> 或逗號 <kbd>,</kbd> 添加",
            "Download template": "下載範本",
            "Download template here": "點此下載範本",
            "Download correct template here": "點此下載標準範本",
            "Invalid Employee IDs Found": "無效離職工號",
            "Review Employee IDs": "確認離職人員名單",
            "The highlighted IDs have been used for another request. Please update the file.": "標紅的工號已在其他申請單中使用，請更新檔案。",
            "Invalid Format": "格式錯誤",
            "The uploaded file does not match the required template. Please use the specified format.": "上傳的檔案不符合規定格式，請使用指定的標準格式。",
            "No employee IDs found in the Excel file.": "Excel檔案中未找到任何工號。",
            "Please enter at least one employee ID": "請至少輸入一個離職工號",
            "Close": "關閉",
            "Employee not found:": "找不到員工：",
            "Error fetching employee info for:": "獲取員工資訊時出錯：",
            "Dept No": "部門代碼"
        }
    }
</i18n>

<style scoped>
.error-row {
    background-color: #ffebee !important;
}
.error-row td {
    color: #d32f2f !important;
    font-weight: bold;
}

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
::v-deep .compact-stepper-header {
    height: auto !important;
    align-items: flex-start; /* Căn chỉnh các bước lên trên cùng */
    padding: 10px 0 !important;
}
::v-deep .compact-stepper-header {
    height: auto !important;
    align-items: flex-start; /* Căn chỉnh lên trên cùng để các cột cao thấp khác nhau không bị vỡ */
    padding: 10px 0 !important;
}

::v-deep .v-stepper__step.compact-step {
    padding: 10px 4px !important; 
    /* Tăng chiều rộng cơ sở lên để chứa tên dài tốt hơn */
    flex-basis: 180px !important; 
    min-width: 140px; /* Đảm bảo không bị co quá nhỏ */
}

::v-deep .v-expansion-panel-content__wrap {
    padding: 0 !important;
}


.amendment-card {
    background: linear-gradient(135deg, #fffdf9 0%, #fff3e0 100%);
    border: 1px dashed #ffb74d;
    border-left: 6px solid #f57c00;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px -3px rgba(245, 124, 0, 0.06), 0 2px 6px -2px rgba(245, 124, 0, 0.03);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.amendment-card:hover {
    box-shadow: 0 10px 25px -5px rgba(245, 124, 0, 0.1), 0 8px 10px -6px rgba(245, 124, 0, 0.05);
    border-color: #f57c00;
    transform: translateY(-2px);
}
</style>