<template>
    <v-row>
        <v-col cols="12">
            <v-card>
                <div class="progress-bar" />
                <v-toolbar dense color="#00838F" dark>
                    <v-toolbar-title class="d-flex" style="align-items: center">
                        <v-icon left>mdi-google-classroom</v-icon>
                        {{ $t("Manpower listing") }}
                    </v-toolbar-title>
                    <v-spacer />
                    <v-text-field style="max-width: 250px; padding-right: 15px" outlined dense v-model="search"
                    @input="customSearch" clearable append-icon="mdi-magnify" :label="$t('Search')" single-line
                    hide-details></v-text-field>
                    <v-btn :title="$t('Reload')" class="mr-3" color="white" small light nuxt @click="getAllData">
                        <v-icon left>mdi-reload</v-icon>
                        {{ $t("Reload") }}
                    </v-btn>
                    <v-btn @click="openNewRequest" class="mr-3" :title="$t('New request')" color="warning" small light nuxt>
                        <v-icon left>mdi-plus</v-icon>
                        {{ $t("New request") }}
                    </v-btn>
                    <v-btn @click="openChangeRequest" :title="$t('Amendment request')" color="primary" small light nuxt>
                        <v-icon left>mdi-key-change</v-icon>
                        {{ $t("Amendment request") }}
                    </v-btn>
                </v-toolbar>
                <v-tabs background-color="#00838F" color="yellow" dark v-model="statusFilter"  center-active>
                    <v-tab>
                        {{ $t("All") }}
                        <!-- <v-badge :content="statusCounts().all > 9 ? '9+' : statusCounts().all.toString()" color="" :value="statusCounts().all" ></v-badge> -->
                    </v-tab>
                    <v-tab v-show="hasTodoData">
                        {{ $t("To Do") }}
                        <v-badge :content="statusCounts().todo.toString()" color="error" :value="statusCounts().todo" ></v-badge>
                    </v-tab>
                    <v-tab>
                        {{ $t("Pending") }}
                        <v-badge :content="statusCounts().processing.toString()" color="purple" :value="statusCounts().processing" ></v-badge>
                    </v-tab>
                    <v-tab>
                        {{ $t("Returned") }}
                        <v-badge color="orange" :content="statusCounts().return.toString()" :value="statusCounts().return" ></v-badge>
                    </v-tab>
                    <v-tab>
                        {{ $t("Approved") }}
                        <!-- <v-badge :content="statusCounts().completed > 9 ? '9+' : statusCounts().completed.toString()" color="" :value="statusCounts().completed" ></v-badge> -->
                    </v-tab>
                    <v-tab>
                        {{ $t("Rejected") }}
                        <v-badge :content="statusCounts().decline.toString()" color=""  :value="statusCounts().decline" ></v-badge>
                    </v-tab>
                    <v-spacer></v-spacer>
                    <v-menu v-model="yearMenu" :close-on-content-click="false" transition="scale-transition" offset-y
                    min-width="100">
                    <template v-slot:activator="{ on, attrs }">
                        <v-chip label v-bind="attrs" v-on="on" class="mr-4" color="#01a6be darken-4" dark>
                            <v-icon left>mdi-calendar</v-icon>
                            {{ $t('Select year') }}: {{ selectedYears.join(', ') || $t('All years') }}
                        </v-chip>
                    </template>
                    <v-card>
                        <v-list>
                            <v-list-item v-for="year in availableYears" :key="year">
                                <v-list-item-action>
                                    <v-checkbox v-model="selectedYears" :value="year" :label="year"
                                    color="#01a6be darken-2"></v-checkbox>
                                </v-list-item-action>
                            </v-list-item>
                        </v-list>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn text color="grey" @click="yearMenu = false">{{ $t('Close') }}</v-btn>
                            <v-btn text color="#01a6be darken-2" @click="clearYearSelection">{{ $t('Clear') }}</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-menu>
            </v-tabs>
            <v-data-table :headers="originalHeaders" :items="filteredData" :search="search" class="elevation-1"  
            :footer-props="{
                showFirstLastPage: true,
                firstIcon: 'mdi-arrow-collapse-left',
                lastIcon: 'mdi-arrow-collapse-right',
                prevIcon: 'mdi-minus',
                nextIcon: 'mdi-plus',
                'items-per-page-text': $t('Rows per page'),
            }">
            <template #[`header.submitter`]>
                <DsFilter :name="$t('Submitted by')" :in-items="filterItems.submitter"
                @changed="updateFilter('submitter', $event)" />
            </template>
            <template #[`header.key_in_date`]>
                <DsFilter :name="$t('Apply Date')" :in-items="filterItems.key_in_date"
                @changed="updateFilter('key_in_date', $event)" />
            </template>
            <template #[`header.unit`]>
                <DsFilter :name="$t('Unit code')" :in-items="filterItems.unit"
                @changed="updateFilter('unit', $event)" />
            </template>
            <template #[`header.unit_name`]>
                <DsFilter :name="$t('Unit name')" :in-items="filterItems.unit_name"
                @changed="updateFilter('unit_name', $event)" />
            </template>
            <template #[`header.pos`]>
                <DsFilter :name="$t('Position')" :in-items="filterItems.pos"
                @changed="updateFilter('pos', $event)" />
            </template>
            <template #[`header.sub_pos`]>
                <!-- {{ $t('Sub position') }} -->
                <DsFilter :name="$t('Sub position')" :in-items="filterItems.sub_pos"
                @changed="updateFilter('sub_pos', $event)" />
            </template>          
            <template #[`header.conditions`]>
                {{ $t('Conditions') }}
            </template>            
            <template #[`header.qty_req`]>
                <DsFilter :name="$t('Demand')" :in-items="filterItems.qty_req"
                @changed="updateFilter('qty_req', $event)" />
            </template>
            <template #[`header.req_id`]>
                <DsFilter :name="$t('ID')" :in-items="filterItems.req_id"
                @changed="updateFilter('req_id', $event)" />
            </template>
            <template #[`header.recruited`]>
                <!-- {{ $t('Recruited') }} -->
                <DsFilter :name="$t('Recruited')" :in-items="filterItems.recruited"
                @changed="updateFilter('recruited', $event)" />
            </template>
            <template #[`header.reason`]>
                <DsFilter :name="$t('Reason')" :in-items="filterItems.reason"
                @changed="updateFilter('reason', $event)" />
            </template>
            <template #[`header.resign`]>
                <!-- {{ $t('Resign') }} -->
                <DsFilter :name="$t('Resign')" :in-items="filterItems.resign"
                @changed="updateFilter('resign', $event)" />
            </template>
            <template #[`header.status`]>
                <DsFilter :name="$t('Approval Status')" :in-items="filterItems.status"
                @changed="updateFilter('status', $event)" />
            </template>
            <template #[`header.action`]>
                {{ $t('Print') }}
            </template>
            <template #[`header.recruited_status`]>
                <DsFilter :name="$t('Recruitment status')" 
                :in-items="filterItems.recruited_status"
                @changed="updateFilter('recruited_status', $event)" />
            </template>
            <template v-slot:[`item.sub_pos`]="{ item }">
                {{ getSubPositionName(item.sub_pos, true) }}
            </template>
            <template v-slot:[`item.recruited_status`]="{ item }">
                <v-card v-if="getRecruitmentStatus(item).show" 
                dark 
                :style="{ backgroundColor: getRecruitmentStatus(item).color }"
                outlined 
                class="pa-1" 
                :color="getRecruitmentStatus(item).color"
                >
                <div class="d-flex align-center flex-column">
                    <span class="text-body-2 font-weight-medium">
                        {{ getRecruitmentStatus(item).text }}
                    </span>
                    <span class="text-caption text-center">
                        {{ getRecruitmentStatus(item).detail }}
                    </span>
                </div>
            </v-card>
        </template>
        <template v-slot:[`item.submitter`]="{ item }">
            <v-btn v-if="item.submitter" color="blue" small text>
                <v-icon left>mdi-account</v-icon>
                {{ item.submiterInfo.name }}
            </v-btn>
            <br />
            <v-btn v-if="item.submitter" color="blue" small text>
                <v-icon left>mdi-card-account-details-outline</v-icon>
                {{ item.submiterInfo.empno }} - {{ item.submiterInfo.dept_sumt }}
            </v-btn>
        </template>
        <template v-slot:[`item.qty_req`]="{ item }">
            <div>
                {{ getDisplayQuantity(item.qty_req, item) }}
                <v-tooltip bottom v-if="hasQuantityChange(item) && !isAmendmentDeniedButOriginalValid(item) && item.status_amendent === 'completed'" color="warning">
                    <template v-slot:activator="{ on, attrs }">
                        <span v-bind="attrs" v-on="on">
                            <v-icon small color="warning" class="mx-1">mdi-arrow-right-bold</v-icon>
                            <span class="font-weight-bold">{{ getChangeQuantity(item) }}</span>
                        </span>
                    </template>
                    <span>{{ getChangeReason(item) }}</span>
                </v-tooltip>
            </div>
        </template>
        <template v-slot:[`item.recruited`]="{ item }">
            <!-- {{ item.recruited || 0 }} -->
            <div 
            style="cursor: pointer; color: blue;" 
            @click="showCandidatesDialog(item.candidates)">
            {{ item.recruited || 0 }}
        </div>
    </template>
    <template v-slot:[`item.resign`]="{ item }">
        <div 
        style="cursor: pointer; color: red;" 
        @click="showResignDialog(item.resignList)">
        {{ item.resign || 0 }}
    </div>
</template>
<template v-slot:[`item.status`]="{ item }">
    <div class="d-flex align-center flex-wrap">
        <v-tooltip bottom :disabled="!getReturnInfo(item).returned && !getNextApproverInfoForStatus(item)">
            <template v-slot:activator="{ on, attrs }">
                <v-chip 
                v-if="item.status" 
                label 
                outlined
                :color="getStatusColor(item.status, item)" 
                small 
                dark
                v-bind="attrs" 
                v-on="on" 
                class="mr-1 mb-1" 
                @click="openApprovalDetails(item)" 
                style="cursor: pointer"
                >
                {{ getStatusWithApprover(item.status, item) }}
            </v-chip>
        </template>
        <span v-if="getReturnInfo(item).returned">
            <strong>{{ $t('Return reason') }}:</strong> {{ getReturnInfo(item).reason }}<br>
            {{ getNextApproverInfoForStatus(item) }}
        </span>
        <span v-else-if="getNextApproverInfoForStatus(item)">
            {{ getNextApproverInfoForStatus(item) }}
        </span>
    </v-tooltip>
    
    <!-- Amendment Status Chip with Separate Tooltip -->
    <v-tooltip bottom :disabled="!getNextApproverInfoForAmendment(item)" v-if="item.status_amendent">
        <template v-slot:activator="{ on: amendmentOn, attrs: amendmentAttrs }">
            <v-chip 
            label 
            :color="getAmendmentStatusColor(item.status_amendent)" 
            small 
            outlined
            dark
            v-bind="amendmentAttrs"
            v-on="amendmentOn"
            class="mr-1 mb-1"
            @click="openApprovalDetails(item)"
            style="cursor: pointer"
            >
            {{ getAmendmentStatusWithApprover(item.status_amendent, item) }}
        </v-chip>
    </template>
    <span>
        {{ getNextApproverInfoForAmendment(item) }}
    </span>
</v-tooltip>

</div>
</template>
<template v-slot:[`item.action`]="{ item }">
    <v-icon @click="printDocument(item)">mdi-printer</v-icon>
</template>
<template v-slot:[`item.reason`]="{ item }">
    
    <template v-if="item.reason_exp === 3">
        <div class="d-flex align-center">
            <span class="text-truncate" style="max-width:150px;">
                {{  getLocalizedOtherReason(item.isOther_reason) }}
            </span>
            <v-btn x-small text color="primary" @click="openOtherReasonDialog(item)"><v-icon x-small>mdi-eye</v-icon></v-btn>
        </div>
    </template>
    <template v-else>
        {{ item.reasonInfo?.type }}
    </template>
</template>
<template v-slot:[`item.computer_skills`]="{ item }">
    <div v-if="item.computerSkillsInfo">
        <div>{{ item.computerSkillsInfo.selected.join(', ') }}</div>
        <div v-if="item.computerSkillsInfo.other" class="text-caption">
            {{ $t('Other:') }} {{ item.computerSkillsInfo.other }}
        </div>
    </div>
</template>
<template v-slot:[`item.pos`]="{ item }">
    <div>{{ getPositionName(item.pos) }}</div>
</template>
<template v-slot:[`item.req_id`]="{ item }">
    <v-btn text small color="#DD2C00" class="pa-0" @click="openDetail(item)"> {{ item.req_id }} </v-btn>
</template>
<template #[`footer.prepend`]>
    <v-btn small color="success" @click="exportExcel" :title="$t('Export to Excel')" class="mr-6" dark>
        <v-icon left>mdi-file-excel</v-icon>
        {{ $t("Export") }}
    </v-btn>
</template>
<template v-slot:[`body.append`]>
    <tr class="total-row">
        <td colspan="3" class="text-center">{{ $t('Total') }}</td>
        <template v-for="(header, index) in originalHeaders.slice(3)">
            <td 
            :key="index" 
            class="text-center"
            :class="{'font-weight-bold': ['qty_req', 'recruited', 'resign'].includes(header.value)}"
            >
            <template v-if="header.value === 'qty_req'">
                {{ totalSum.qty_req || 0 }}
            </template>
            <template v-else-if="header.value === 'recruited'">
                {{ totalSum.recruited || 0 }}
            </template>
            <template v-else-if="header.value === 'resign'">
                {{ totalSum.resign || 0 }}
            </template>
        </td>
    </template>
</tr>
</template>
<template v-slot:[`item.approval`]="{ item }">
    <v-btn small color="info" outlined @click="openApprovalDetails(item)">
        {{ $t('Approvals log') }}
    </v-btn>
</template>
<template v-slot:[`item.empno`]="{ item }">
    {{ item.empno }}
</template>
<template v-slot:[`item.name`]="{ item }">
    {{ item.name }}
</template>
<template v-slot:[`item.dept`]="{ item }">
    {{ item.dept }}
</template>
<template v-slot:[`item.conditions`]="{ item }">
    <!-- Language Display -->
    <div class="d-flex align-center flex-wrap mb-1">
        <template v-if="getLanguageRequirements(item).length > 0">
            <v-chip v-for="lang in getLanguageRequirements(item)" :key="lang.type" small label outlined class="mr-1" color="#01a6be" dark > {{ lang.type }}: {{ lang.level }}
            </v-chip>
        </template>
    </div>
    
    <!-- Computer Skills Display -->
    <div class="d-flex align-center flex-wrap">
        <template v-if="item.comp_skill">
            <!-- Regular computer skills -->
            <img v-for="skill in getComputerSkillImages(JSON.parse(item.comp_skill).skills)" :key="skill.name" :src="skill.src" :alt="skill.name" :title="skill.name" class="skill-icon mr-1" />
            <!-- Other computer skills -->
            <v-chip
            v-if="JSON.parse(item.comp_skill).skills.includes('Other')"
            small
            label
            color="info"
            outlined
            >
            <v-icon left x-small>mdi-laptop</v-icon>
            <span>{{ JSON.parse(item.comp_skill).others }}</span>
        </v-chip>
    </template>
</div>
</template>
</v-data-table>
</v-card>
</v-col>
<v-dialog v-model="otherReasonDialog" max-width="720px">
    <v-card>
        <v-card-title class="d-flex align-center">
            <span>{{ $t('Reason') }}</span>
            <v-spacer></v-spacer>
            <v-btn icon @click="otherReasonDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-text>
            <div class="tooltip-content" style="white-space:pre-line;">{{ otherReasonText }}</div>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text color="error" @click="otherReasonDialog = false"><v-icon x-small left>mdi-close</v-icon>{{ $t('Close') }} </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<RequestDialog v-model="appDialog" 
:edit-data="selectedItem" 
:is-edit="isEdit" 
:is-change="isChange"
@requestUpdated="getAllData" /> <!-- Add @requestUpdated handler -->

<!-- Accept Confirmation Dialog -->
<v-dialog v-model="confirmAcceptDialog" max-width="500px">
    <v-card>
        <v-card-title style="background-color: #01a6be; color: white;">
            <v-icon left dark>mdi-check</v-icon>
            {{ $t('Confirm Action') }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="confirmAcceptDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            {{ $t('Are you sure you want to accept this request?') }}
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmAcceptDialog = false">
                {{ $t('Cancel') }}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="#01a6be" @click="confirmAccept">
                {{ $t('Confirm') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>

<!-- Deny Dialog with Reason -->
<v-dialog v-model="confirmDenyDialog" max-width="500px">
    <v-card>
        <v-card-title class="error white--text">
            <v-icon left dark>mdi-alert</v-icon>
            {{ $t('Deny Request') }}
            <v-btn icon dark @click="confirmDenyDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-textarea v-model="denyReason" :label="$t('Reason')"
            :placeholder="$t('Please provide a reason for denying this request')" outlined rows="3"
            ></v-textarea>
        </v-card-text>
        <v-card-actions>
            <v-btn color="grey darken-1" text @click="confirmDenyDialog = false">
                {{ $t('Cancel') }}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn color="error" @click="confirmDeny" :disabled="!denyReason.trim()">
                {{ $t('Confirm Denial') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</v-dialog>
<v-dialog v-model="approvalDialog" max-width="1400px">
    <v-card>
        <v-card-title style="background-color: #00838F; color: white;">
            <v-icon dark left>mdi-history</v-icon>
            {{ $t('Approval History') }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="approvalDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pa-1">
            <v-expansion-panels v-model="approvalPanelsActive">
                <v-expansion-panel>
                    <v-expansion-panel-header class="current-cycle-header">
                        <div class="d-flex align-center justify-space-between w-100">
                            <div class="d-flex align-center">
                                <v-chip label color="warning" outlined dark class="mr-4" > {{ $t('Current Cycle') }} </v-chip>
                            </div>
                        </div>
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                        <v-data-table :headers="approvalHeaders" :items="currentApprovalData" hide-default-footer class="elevation-1" >
                            <template v-slot:[`item.type`]="{ item }">
                                {{ getApprovalLevelName(item.type) }}
                            </template>
                            <template v-slot:[`item.status`]="{ item }">
                                <v-chip small :color="getApprovalStatusColor(item.status)" dark label>
                                    {{ $t(item.status || 'waiting') }}
                                </v-chip>
                            </template>
                            <template v-slot:[`item.reason_denied`]="{ item }">
                                <span v-if="item.status === 'rejected' && item.reason_denied">{{ item.reason_denied }}</span>
                            </template>
                        </v-data-table>
                    </v-expansion-panel-content>
                </v-expansion-panel>
                <v-expansion-panel v-for="(cycle, cycleIndex) in groupedApprovalData" :key="cycleIndex">
                    <v-expansion-panel-header>
                        <div class="d-flex align-center">
                            <v-chip label outlined :color="getCycleColor(cycleIndex + 1)" class="mr-4" >
                                {{ $t('Cycle') }} {{ cycleIndex + 1 }}
                            </v-chip>
                        </div>
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                        <v-data-table :headers="approvalHeaders" :items="cycle" hide-default-footer class="elevation-1" >
                            <template v-slot:[`item.type`]="{ item }">
                                {{ item.type }}
                            </template>
                            <template v-slot:[`item.status`]="{ item }">
                                <v-chip small :color="getApprovalStatusColor(item.status)" dark label>
                                    {{ $t(item.status) }}
                                </v-chip>
                            </template>
                            <template v-slot:[`item.reason_denied`]="{ item }">
                                <span v-if="item.status === 'rejected' && item.reason_denied">{{ item.reason_denied }}</span>
                            </template>
                        </v-data-table>
                    </v-expansion-panel-content>
                </v-expansion-panel>
            </v-expansion-panels>
        </v-card-text>
    </v-card>
</v-dialog>

<!-- Return Dialog -->
<v-dialog v-model="confirmReturnDialog" max-width="500px" transition="dialog">
    <v-card elevation="2" class="rounded">
        <v-card-title class="warning white--text">
            <v-icon left dark>mdi-alert</v-icon>
            {{ $t('Return Request') }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="confirmReturnDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-textarea v-model="returnReason" :label="$t('Return reason')"
            :placeholder="$t('Please provide a reason for returning this request')" 
            outlined rows="3"  class="mb-4">
        </v-textarea>
    </v-card-text>
    <v-card-actions>
        <v-btn color="grey darken-1" text @click="confirmReturnDialog = false">
            {{ $t('Cancel') }}
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn color="warning" @click="confirmReturn" :disabled="!returnReason.trim()">
            {{ $t('Confirm Return') }}
        </v-btn>
    </v-card-actions>
</v-card>
</v-dialog>

<v-dialog v-model="cancelDialog" max-width="500">
    <v-card>
        <v-card-title class="#01a6be darken-4 white--text">
            {{ $t('Cancel request') }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="cancelDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-menu v-model="showCancelDatePicker" :close-on-content-click="false" transition="scale-transition"
            max-width="290px">
            <template v-slot:activator="{ on, attrs }">
                <v-text-field v-model="selectedCancelDate" :label="$t('Cancel date')" readonly outlined
                dense hide-details class="mb-4" v-bind="attrs" v-on="on"
                prepend-inner-icon="mdi-calendar" clearable
                @click:clear="selectedCancelDate = ''"></v-text-field>
            </template>
            <v-date-picker v-model="selectedCancelDate" @input="showCancelDatePicker = false"
            color="#01a6be"></v-date-picker>
        </v-menu>
        <v-textarea v-model="cancelReason" :label="$t('Cancel reason')" outlined hide-details="auto"
        rows="3" counter prepend-inner-icon="mdi-text-box" clearable></v-textarea>
    </v-card-text>
    <v-card-actions class="pb-4 px-4">
        <v-btn color="error" @click="cancelDialog = false" :disabled="loading">
            {{ $t('Cancel') }}
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn color="success" @click="updateCancelDate" :loading="loading">
            {{ $t('Update') }}
        </v-btn>
    </v-card-actions>
</v-card>
</v-dialog>
<v-dialog v-model="candidatesDialog" max-width="1000px">
    <v-card>
        <v-card-title class="pa-3 white--text" style="background-color: #00838F;">
            <span>{{ $t("Recruitment listing details") }}</span>
            <v-spacer></v-spacer>
            <v-btn icon dark @click="candidatesDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-data-table :headers="candidateHeaders" :items="parsedCandidates" :items-per-page="5" class="elevation-3 data-table" :footer-props="{ 'show-current-page': true, 'show-first-last-page': true }">
                <!-- <template v-slot:[`header.select`]>
                    <v-checkbox v-model="selectAll" color="primary" hide-details @click="toggleSelectAll" ></v-checkbox>
                </template> -->
                <template v-slot:[`item.select`]="{ item }">
                    <v-checkbox 
                    :input-value="isItemSelected(item)"
                    @change="toggleItemSelection(item)"
                    color="primary" 
                    hide-details 
                    dense>
                </v-checkbox>
            </template>
            <template v-slot:[`item.empno`]="{ item }">
                <div class="d-flex align-center">
                    {{ item.empno }}
                </div>
            </template>
            <template v-slot:[`item.name`]="{ item }">
                <div class="d-flex align-center">
                    {{ item.name }}
                </div>
            </template>
            <template v-slot:[`item.position`]="{ item }">
                <div class="d-flex align-center">
                    {{ getPositionName(item.position) }}
                </div>
            </template>
            <template v-slot:[`item.date`]="{ item }">
                <div class="d-flex align-center">
                    {{ formatDate(item.date) }}
                </div>
            </template>
            <template v-slot:[`header.empno`]> {{ $t('Empno') }} </template>
            <template v-slot:[`header.name`]> {{ $t('Name') }} </template>
            <template v-slot:[`header.date`]> {{ $t('In date') }} </template>
            <template v-slot:[`header.key_in`]>{{ $t('Key in') }} </template>
            <template v-slot:[`header.position`]>{{ $t('Position') }} </template>
            
            <!-- <template #[`footer.prepend`]>
                <div class="mr-2" style="width: 250px">
                    <v-select
                    v-model="transferMprId"
                    :items="mprOptions"
                    :label="$t('ID MPR')"
                    item-text="text"
                    item-value="value"
                    outlined
                    dense
                    hide-details
                    ></v-select>
                </div>
                <v-btn 
                color="primary" 
                class="mr-5"
                :disabled="selectedCandidates.length === 0 || !transferMprId"
                @click="transferCandidates"
                >
                <v-icon left>mdi-arrow-right</v-icon>
                {{ $t('Transfer') }}
            </v-btn>
            
        </template> -->
    </v-data-table>
</v-card-text>
</v-card>
</v-dialog>
<v-dialog v-model="resignDialog" max-width="1000px">
    <v-card>
        <v-card-title class="pa-2 white--text" style="background-color: #00838F;">
            <span>{{ $t("Resign Listing Details") }}</span>
            <v-spacer></v-spacer>
            <v-btn icon dark @click="resignDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-data-table 
            :headers="resignHeaders" 
            :items="parsedResignList" 
            :items-per-page="5"
            class="elevation-3 data-table" 
            :footer-props="{
                'show-current-page': true, 
                'show-first-last-page': true
            }"
            >
            <template v-slot:[`item.empno`]="{ item }">
                <div class="d-flex align-center">
                    {{ item.empno }}
                </div>
            </template>
            <template v-slot:[`item.name`]="{ item }">
                <div class="d-flex align-center">
                    {{ item.name }}
                </div>
            </template>
            <template v-slot:[`item.date`]="{ item }">
                <div class="d-flex align-center">
                    {{ formatDate(item.date) }}
                </div>
            </template>
            <template v-slot:[`item.position`]="{ item }">
                <div class="d-flex align-center">
                    {{ getPositionName(item.position) }}
                </div>
            </template>
            <template v-slot:[`header.empno`]> {{ $t('Empno') }} </template>
            <template v-slot:[`header.name`]> {{ $t('Name') }} </template>
            <template v-slot:[`header.date`]> {{ $t('Resign date') }} </template>
            <template v-slot:[`header.key_in`]>{{ $t('Key in') }} </template>
            <template v-slot:[`header.position`]>{{ $t('Position') }} </template>
        </v-data-table>
    </v-card-text>
</v-card>
</v-dialog>
<!-- Add this new dialog -->
<v-dialog v-model="rejectionDialog" max-width="800px">
    <v-card>
        <v-card-title class="error white--text">
            <v-icon left dark>mdi-alert-circle</v-icon>
            {{ $t('Rejection Details') }}
            <v-spacer></v-spacer>
            <v-btn icon dark @click="rejectionDialog = false">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-text class="pt-4">
            <v-data-table
            :headers="rejectionHeaders"
            :items="rejectionDetails"
            hide-default-footer
            class="elevation-3"
            >
            <template v-slot:[`item.index`]="{ item }">
                {{ item.index }}
            </template>
            <template v-slot:[`item.empno`]="{ item }">
                {{ item.empno }}
            </template>
            <template v-slot:[`item.name`]="{ item }">
                {{ item.name }}
            </template>
            <template v-slot:[`item.dept`]="{ item }">
                {{ item.dept }}
            </template>
            <template v-slot:[`item.reason_denied`]="{ item }">
                {{ item.reason_denied }}
            </template>
            <template v-slot:[`item.processed_at`]="{ item }">
                {{ item.processed_at }}
            </template>
        </v-data-table>
    </v-card-text>
</v-card>
</v-dialog>
</v-row>
</template>
<script>
import RequestDialog from '@/components/RequestDialog.vue'
import DSFilter from '@/components/DsFilter.vue'
import dayjs from "dayjs";
import * as XLSX from 'xlsx';

const formatters = {
    education(education, i18n) {
        if (!education || !education.id) return i18n.t('N/A');
        if (education.name) {
            const locale = i18n.locale || 'en';
            if (education.name[locale]) {
                return education.name[locale];
            }
        }
        const map = {
            1: i18n.t('Below 12/12'),
            2: i18n.t('12/12'),
            3: i18n.t('Intermediate'),
            4: i18n.t('College'),
            5: i18n.t('University')
        };
        
        return map[education.id] || i18n.t('N/A');
    }
};

export default {
    components: { DSFilter, RequestDialog },
    layout: '',
    data() {
        const defaultHeaders = [
        {text:'',value:'select',sortable:false,width:'30px'},
        { text: 'Req ID', value: 'req_id', width: '60px', align: 'center'},
        { text: 'Req date', value: 'date_req', width: '120px' },
        
        { text: 'Position', value: 'pos', width: '100px' },
        { text: 'Demand', value: 'qty_req', align: 'center', width: '100px' },
        { text: 'Reason', value: 'reason', width: '120px' },
        { text: 'Status', value: 'status', width: '120px' },
        { text: 'Recruited', value: 'recruited', align: 'center', width: '110px' },
        { text: 'Resign', value: 'resign', align: 'center', width: '100px' },
        { text: 'Action', value: 'action', sortable: false, width: '100px' }
        ];
        
        return {
            search: '',
            api: 'http://gmo021.cansportsvg.com/api/vg-mpr/',
            storageUrl: "http://gmo021.cansportsvg.com/api/storage/app/vg-mpr/",
            loading: false,
            cancelDialog: false,
            showCancelDatePicker: false,
            selectedCancelDate: '',
            cancelReason: '',
            selectIdMpr: null,
            appDialog: false,
            confirmAcceptDialog: false,
            confirmDenyDialog: false,
            selectedItemForAction: null,
            denyReason: '',
            dataMpr: [],
            filteredItems: [],
            selectedItem: null, 
            isEdit: false,
            isChange: false,
            selectedYears: [new Date().getFullYear().toString()],
            yearMenu: false,
            approvalDialog: false,
            selectedApprovalData: [],
            approvalHeaders: [
            { text: this.$t('Type'), value: 'type', align: 'center', sortable: false },
            { text: this.$t('Empno'), value: 'empno', align: 'center', width: '100px', sortable: false },
            { text: this.$t('Name'), value: 'name', width: '150px', sortable: false },
            { text: this.$t('Department'), value: 'dept', width: '150px', sortable: false },
            { text: this.$t('Status'), value: 'status', align: 'center', sortable: false },
            { text: this.$t('Reason Denied'), value: 'reason_denied', align: 'center', sortable: false },
            { text: this.$t('Return Reason'), value: 'return_reason', align: 'center', sortable: false },
            { text: this.$t('Approval Record'), value: 'approval_record', align: 'center', sortable: false },
            { text: this.$t('Date'), value: 'processed_at', align: 'center', sortable: false }
            ],
            dataFlow: '',
            selectedItemForAccept: null,
            activeUser: '',
            approvalFlowCache: {},
            generalFlowData: null,
            confirmReturnDialog: false,
            returnReason: '',
            pos: [],
            flowRequestInProgress: {}, 
            flowRequestQueue: {}, 
            flowCache: new Map(), 
            defaultHeaders,
            parsedCandidates: [],
            candidateHeaders: [
            // { text: '', value: 'select', sortable: false, width: '50px' },
            { text: 'Key in', value: 'key_in', align: 'start' },
            { text: 'Empno', value: 'empno', align: 'start' },
            { text: 'Name', value: 'name' },
            { text: 'Position', value: 'position' },
            { text: 'Actual date', value: 'date' }
            ],
            candidatesDialog: false,
            selectedCandidates: [],
            selectAll: false,
            transferMprId: '',
            rejectionDialog: false,
            rejectionDetails: [],
            rejectionHeaders: [
            { text: this.$t('No.'), value: 'index', align: 'center' },
            { text: this.$t('Empno'), value: 'empno', width: '100px' },
            { text: this.$t('Name'), value: 'name', width: '200px' },
            { text: this.$t('Department'), value: 'dept', width: '120px' },
            { text: this.$t('Date'), value: 'processed_at', width: '120px' },
            { text: this.$t('Reason Deny'), value: 'reason_denied' },
            ],
            statusFilter: 0, 
            filterValues: {}, 
            dataRecruitmentReason: [],
            unitNames: [],
            lang: [],
            subPos: [],
            sentNotifications: new Set(),
            resignDialog: false,
            parsedResignList: [],
            resignHeaders: [
            { text: 'Key in', value: 'key_in', align: 'start' },
            { text: 'Empno', value: 'empno', align: 'start' },
            { text: 'Name', value: 'name' },
            { text: 'Position', value: 'position' },
            { text: 'Resign date', value: 'date' }
            ],
            otherReasonDialog: false,
            otherReasonText: '',
            approvalPanelsActive:null
            
        }
    },
    computed: {
        currentApprovalData() {
            try {
                if (!this.selectedItem?.approval) return [];
                const approvalData = JSON.parse(this.selectedItem.approval);
                let currentApprovalRecord = {};
                
                if (this.selectedItem.approval_record) {
                    try {
                        const allApprovalRecords = JSON.parse(this.selectedItem.approval_record);
                        currentApprovalRecord = allApprovalRecords[allApprovalRecords.length - 1] || {};
                    } catch(e) {
                        console.error('Error parsing current approval record:', e);
                    }
                }
                
                return approvalData
                .filter(level => level.approver && level.approver.empno)
                .map((level, index) => {
                    const recordKey = level.type;
                    const approvalRecord = currentApprovalRecord[recordKey] || '';
                    
                    // Parse return_reason
                    let returnReasonText = '';
                    if (level.return_reason) {
                        try {
                            const parsedReason = JSON.parse(level.return_reason);
                            const currentLocale = this.$i18n.locale;
                            returnReasonText = parsedReason[currentLocale] || parsedReason.vi || parsedReason.cn || '';
                        } catch (e) {
                            returnReasonText = level.return_reason;
                        }
                    }
                    
                    // Parse reason_denied
                    let reasonDeniedText = '';
                    if (level.reason_denied) {
                        try {
                            const parsedDenied = JSON.parse(level.reason_denied);
                            const currentLocale = this.$i18n.locale;
                            reasonDeniedText = parsedDenied[currentLocale] || parsedDenied.vi || parsedDenied.cn || '';
                        } catch (e) {
                            reasonDeniedText = level.reason_denied;
                        }
                    }
                    
                    return {
                        level: index + 1,
                        cycle: this.$t('Current'),
                        type: this.getApprovalLevelName(level.type),
                        empno: level.approver.empno,
                        name: level.approver.name,
                        dept: level.approver.dept,
                        status: level.status || '',
                        reason_denied: reasonDeniedText,
                        return_reason: returnReasonText,
                        approval_record: approvalRecord,
                        processed_at: level.processed_at ? this.formatDate(level.processed_at) : '',
                        cycleIndex: 'current'
                    };
                });
            } catch (error) {
                console.error('Error parsing current approval data:', error);
                return [];
            }
            
        },
        groupedApprovalData() {
            try {
                const grouped = {};
                this.selectedApprovalData.forEach(item => {
                    if (item.cycleIndex && 
                    item.cycleIndex !== 'current' && 
                    typeof item.cycleIndex === 'number') { // Đảm bảo là số, không phải string
                        
                        const cycleIndex = item.cycleIndex - 1;
                        if (!grouped[cycleIndex]) {
                            grouped[cycleIndex] = [];
                        }
                        let returnReasonText = '';
                        if (item.return_reason) {
                            try {
                                const parsedReason = JSON.parse(item.return_reason);
                                const currentLocale = this.$i18n.locale;
                                returnReasonText = parsedReason[currentLocale] || parsedReason.vi || parsedReason.cn || '';
                            } catch (e) {
                                returnReasonText = item.return_reason;
                            }
                        }
                        
                        let reasonDeniedText = '';
                        if (item.reason_denied) {
                            try {
                                const parsedDenied = JSON.parse(item.reason_denied);
                                const currentLocale = this.$i18n.locale;
                                reasonDeniedText = parsedDenied[currentLocale] || parsedDenied.vi || parsedDenied.cn || '';
                            } catch (e) {
                                reasonDeniedText = item.reason_denied;
                            }
                        }
                        
                        grouped[cycleIndex].push({
                            ...item,
                            return_reason: returnReasonText,
                            reason_denied: reasonDeniedText,
                            processed_at: item.processed_at ? this.formatDate(item.processed_at) : ''
                        });
                    }
                });
                
                // Sắp xếp từng nhóm theo level
                Object.keys(grouped).forEach(cycleIndex => {
                    grouped[cycleIndex].sort((a, b) => a.level - b.level);
                });
                
                return Object.values(grouped);
            } catch (error) {
                console.error('Error in groupedApprovalData:', error);
                return [];
            }
        },
        defaultTab() {
            return this.hasTodoData ? 1 : 0;
        },
        hasTodoData() {
            if (!this.generalFlowData && Object.keys(this.approvalFlowCache).length === 0) {
                return false;
            }
            
            return this.dataFilteredByYear.some(item => {
                return this.canApproveItemForTodo(item);
            });
        },
        originalHeaders() {
            return this.getHRHeaders();
        },
        canReturn() {
            return this.returnReason.trim();
        },
        totalSum() {
            return {
                qty_req: this.filteredData.reduce((sum, item) => {
                    // Kiểm tra deny_temp trước VÀ status_amendent='completed'
                    if (item.deny_temp && 
                    item.deny_temp.trim() !== '' && 
                    item.status_amendent === 'completed') {
                        try {
                            const denyTempData = JSON.parse(item.deny_temp);
                            if (denyTempData.recruited !== undefined) {
                                return sum + (parseInt(denyTempData.recruited) || 0);
                            } else if (item.recruited !== undefined) {
                                return sum + (parseInt(item.recruited) || 0);
                            }
                        } catch (e) {
                            console.error("Error parsing deny_temp for total:", e);
                        }
                    }
                    
                    // Logic cũ cho change_req
                    if (item.change_req && item.status_amendent === 'completed') {
                        try {
                            const changeReq = typeof item.change_req === 'string' 
                            ? JSON.parse(item.change_req) 
                            : item.change_req;
                            
                            if (Array.isArray(changeReq)) {
                                const lastValidChange = [...changeReq]
                                .reverse()
                                .find(change => change.change_qty && change.change_qty.trim() !== '');
                                
                                if (lastValidChange) {
                                    return sum + (parseInt(lastValidChange.change_qty) || 0);
                                }
                            }
                        } catch (e) {
                            console.error("Error parsing change_req:", e);
                        }
                    }
                    
                    return sum + this.getDisplayQuantity(item.qty_req, item);
                }, 0),
                recruited: this.filteredData.reduce((sum, item) => {
                    return sum + (parseInt(item.recruited) || 0);
                }, 0),
                resign: this.filteredData.reduce((sum, item) => {
                    return sum + (parseInt(item.resign) || 0);
                }, 0),
            };
        },
        formattedData() {
            return this.dataMpr.map(item => {
                const submitter = JSON.parse(item.submitter || '{}');
                const education = JSON.parse(item.edu || '{}');
                let qtyReq;
                try {
                    qtyReq = typeof item.qty_req === 'string' ? JSON.parse(item.qty_req) : item.qty_req;
                } catch (e) {
                    console.error("Error parsing qty_req:", e);
                    qtyReq = { workers: 0, staff: 0, officers: 0, engineers: 0 };
                }
                const engSkill = JSON.parse(item.eng_skill || '[]');
                const chnSkill = JSON.parse(item.chn_skill || '[]');
                const eitherSkill = JSON.parse(item.either_skill || '[]');
                const workExp = JSON.parse(item.w_exp || '{}');
                const reasonExp =item.reason_exp ;
                const compSkill = JSON.parse(item.comp_skill || '{}');
                const position = JSON.parse(item.pos || '{}');
                
                const positions = Object.entries(qtyReq)
                .filter(([_, value]) => Number(value) > 0)
                .map(([key]) => key);
                
                const officerLevel = qtyReq.level || null;
                
                const formattedReason = this.formatReason(reasonExp);
                
                // Find matching unit name from unitNames array
                const unitCode = submitter.unit;
                const unitInfo = this.unitNames.find(u => u.dept_code === unitCode); // hoặc 'code' nếu đúng tên
                let unitName = '';
                if (unitInfo && unitInfo.name) {
                    try {
                        const nameObj = JSON.parse(unitInfo.name);
                        const locale = this.$i18n?.locale || 'vi';
                        unitName = nameObj[locale] || nameObj.vi || nameObj.en || '';
                    } catch (e) {
                        console.error('Cannot parse unitInfo.name:', e, unitInfo.name);
                        unitName = '';
                    }
                }
                
                
                return {
                    ...item,
                    submiterInfo: submitter,
                    unit: unitCode,
                    unit_name: unitName, // Add unit_name mapping
                    division: submitter.div,
                    edu_level: formatters.education(education, this.$i18n),
                    language: this.formatLanguageSkills(engSkill, chnSkill, eitherSkill),
                    workExpInfo: this.formatWorkExperience(workExp),
                    reasonInfo: {
                        ...formattedReason,
                        oldEmpNo: item.old_emp,
                        otherReason: item.isOther_reason
                    },
                    reason: formattedReason.type,
                    computerSkillsInfo: compSkill,
                    positionInfo: position,
                    qtyReq: qtyReq,
                    qtyInfo: {
                        total: this.getDisplayQuantity(item.qty_req),
                        details: typeof qtyReq === 'object' ?
                        `Workers: ${qtyReq.workers || 0}, Staff: ${qtyReq.staff || 0}, Engineers: ${qtyReq.engineers || 0}, Leader: ${qtyReq.leader || 0}, Tranlator: ${qtyReq.tranlator || 0}`:qtyReq.toString()
                    },
                    positions,
                    officerLevel
                };
            });
        },
        dataFilteredByYear() {
            if (!this.selectedYears.length) return this.formattedData;
            
            return this.formattedData.filter(item => {
                if (!item.key_in_date) return false;
                const itemYear = new Date(item.key_in_date).getFullYear().toString();
                return this.selectedYears.includes(itemYear);
            });
        },
        
        filteredData() {
            let result = this.dataFilteredByYear;
            
            // 1. Lọc theo role và quyền truy cập
            if (this.activeUser) {
                if (!['hr', 'admin'].includes(this.activeUser.role?.toLowerCase())) {
                    result = result.filter(item => {
                        const currentStatus = item.status_amendent || item.status;
                        if (['completed', 'declined'].includes(currentStatus)) {
                            return item.submiterInfo?.empno === this.activeUser.empno;
                        }
                        return item.submiterInfo?.empno === this.activeUser.empno || 
                        this.canApproveItemForTodo(item);
                    });
                }
            }
            
            // 2. Lọc theo tab status
            if (this.statusFilter === 1) {
                // Tab To Do
                result = result.filter(item => this.canApproveItemForTodo(item));
            } else if (this.statusFilter === 2) {
                // Tab Pending
                result = result.filter(item => 
                [ 'waiting_dept', 'waiting_div', 'waiting_hr_rec_sf', 
                'waiting_hr', 'waiting_ad', 'waiting_gm'].includes(item.status) ||
                [ 'waiting_dept', 'waiting_div', 'waiting_hr_rec_sf', 
                'waiting_hr', 'waiting_ad', 'waiting_gm'].includes(item.status_amendent)
                );
            } else if (this.statusFilter === 3) {
                // Tab Returned
                result = result.filter(item => 
                item.status === 'returned' || 
                item.status_amendent === 'returned' ||
                this.hasReturnedLevel(item)
                );
            } else if (this.statusFilter === 4) {
                // Tab Approved
                result = result.filter(item => 
                item.status === 'completed' || 
                item.status_amendent === 'completed'
                );
            } else if (this.statusFilter === 5) {
                // Tab Rejected
                result = result.filter(item =>  
                ['declined', 'cancelled'].includes(item.status) ||
                ['declined', 'cancelled'].includes(item.status_amendent)
                );
            }
            
            // 3. Lọc theo các filter values
            Object.entries(this.filterValues).forEach(([key, values]) => {
                if (values && values.length) {
                    result = result.filter(item => {
                        switch(key) {
                            case 'req_id':
                            return values.includes(item.req_id);
                            
                            case 'submitter':
                            return values.some(value => {
                                const empno = value.split(' - ')[0]; // Lấy phần empno từ chuỗi "empno - name"
                                return item.submiterInfo?.empno === empno;
                            });
                            
                            case 'date_req':
                            return values.includes(this.formatDate(item.date_req));
                            
                            case 'unit':
                            return values.includes(item.unit);
                            case 'unit_name':
                            return values.includes(item.unit_name);
                            
                            case 'pos':
                            return values.includes(this.getPositionName(item.pos));
                            
                            case 'reason':
                            return values.includes(item.reason);
                            
                            case 'status':
                            return values.includes(this.getStatusText(item.status)) ||
                            values.includes(this.getStatusText(item.status_amendent));
                            
                            case 'sub_pos':
                            const subPosName = this.getSubPositionName(item.sub_pos, true);
                            return values.some(subPos => subPosName.includes(subPos));
                            
                            case 'recruited':
                            return values.includes(item.recruited);
                            
                            case 'resign':
                            return values.includes(item.resign);
                            
                            case 'qty_req':
                            return values.includes(item.qtyInfo.total);
                            
                            case 'recruited_status':
                            const status = this.getRecruitmentStatus(item);
                            return status.show && values.includes(status.text);
                            
                            default:
                            return true;
                        }
                    });
                }
            });
            
            // 4. Lọc theo search query
            if (this.search) {
                const searchTerm = this.search.toLowerCase();
                result = result.filter(item => {
                    return (
                    String(item.id).toLowerCase().includes(searchTerm) ||
                    String(item.req_id).toLowerCase().includes(searchTerm) ||
                    String(item.date_req).toLowerCase().includes(searchTerm) ||
                    (item.submiterInfo?.name || '').toLowerCase().includes(searchTerm) ||
                    (item.unit || '').toLowerCase().includes(searchTerm) ||
                    (item.division || '').toLowerCase().includes(searchTerm) ||
                    this.getPositionName(item.pos).toLowerCase().includes(searchTerm) ||
                    (item.language || '').toLowerCase().includes(searchTerm) ||
                    (item.reason || '').toLowerCase().includes(searchTerm) ||
                    this.getStatusText(item.status).toLowerCase().includes(searchTerm) ||
                    this.getStatusText(item.status_amendent).toLowerCase().includes(searchTerm) ||
                    String(item.recruited || '').toLowerCase().includes(searchTerm) || 
                    String(item.resign || '').toLowerCase().includes(searchTerm) ||
                    String(item.qtyInfo?.total || '').toLowerCase().includes(searchTerm) ||
                    String(item.qtyInfo?.details || '').toLowerCase().includes(searchTerm) ||
                    (item.sub_pos ? this.getSubPositionName(item.sub_pos, true).toLowerCase().includes(searchTerm) : false)
                    );
                });
            }
            
            return result;
        },
        availableYears() {
            const currentYear = new Date().getFullYear();
            const years = [];
            const startYear = 2024; // Cố định năm bắt đầu là 2024
            const endYear = currentYear + 3; // Hiển thị thêm 3 năm kể từ năm hiện tại
            
            for (let i = startYear; i <= endYear; i++) {
                years.push(i.toString());
            }
            return years;
        },
        positionFilterItems() {
            const items = new Set();
            this.formattedData.forEach(item => {
                if (item.pos) {
                    const position = this.pos.find(p => p.id === parseInt(item.pos));
                    if (position && position.type_name) {
                        try {
                            const typeNameObj = JSON.parse(position.type_name.trim());
                            const locale = this.$i18n.locale || 'en';
                            const name = typeNameObj[locale] || typeNameObj['en'] || '';
                            if (name) items.add(name);
                        } catch (error) {
                            console.error('Error parsing position name:', error);
                        }
                    }
                }
            });
            return Array.from(items).sort();
        },
        mprOptions() {
            if (!this.dataMpr || this.dataMpr.length === 0) return [];
            return this.dataMpr.map(mpr => ({
                value: mpr.id,
                text: `${mpr.id} - ${mpr.req_id}`
            }));
        },
        filterItems() {
            return {
                unit_name: [...new Set(this.formattedData.map(item => item.unit_name))].filter(Boolean),
                req_id: [...new Set(this.formattedData.map(item => item.req_id))].filter(Boolean),
                submitter: [...new Set(this.formattedData.map(item => 
                item.submiterInfo?.empno ? `${item.submiterInfo.empno} - ${item.submiterInfo.name}` : ''
                ))].filter(Boolean),
                date_req: [...new Set(this.formattedData.map(item => this.formatDate(item.date_req)))].filter(Boolean),
                unit: [...new Set(this.formattedData.map(item => item.unit))].filter(Boolean),
                // division: [...new Set(this.formattedData.map(item => item.division))].filter(Boolean),
                pos: [...new Set(this.formattedData.map(item => this.getPositionName(item.pos)))].filter(Boolean),
                reason: [...new Set(this.formattedData.map(item => item.reason))].filter(Boolean),
                status: [...new Set(this.formattedData.flatMap(item => [
                this.getStatusText(item.status),
                this.getStatusText(item.status_amendent)
                ]))].filter(Boolean),
                sub_pos: [...new Set(this.subPos.map(sp => {
                    try {
                        const subNameObj = JSON.parse(sp.sub_name.trim());
                        const locale = this.$i18n.locale || 'en';
                        return subNameObj[locale] || subNameObj['en'] || '';
                    } catch (error) {
                        console.error('Error parsing sub position name:', error);
                        return '';
                    }
                }))].filter(Boolean),
                recruited: [...new Set(this.formattedData.map(item => item.recruited))].filter(Boolean),
                resign: [...new Set(this.formattedData.map(item => item.resign))].filter(Boolean),
                qty_req: [...new Set(this.formattedData.map(item => item.qtyInfo.total))].filter(Boolean),
                recruited_status: [
                ...new Set(this.formattedData
                .map(item => {
                    const status = this.getRecruitmentStatus(item);
                    return status.show ? status.text : null;
                })
                .filter(Boolean)
                )
                ],
            }
        },
    },
    methods: {
        openOtherReasonDialog(item) {
            try {
                this.otherReasonText = this.getLocalizedOtherReason(item.isOther_reason) || '';
            } catch (e) {
                console.error('openOtherReasonDialog error', e);
                this.otherReasonText = item.isOther_reason || '';
            }
            this.otherReasonDialog = true;
        },
        showResignDialog(resignList) {
            try {
                this.parsedResignList = JSON.parse(resignList || '[]');
                if (this.parsedResignList.length > 0) {
                    this.resignDialog = true;
                } else {
                    this.$notify({
                        title: this.$t('No Resigned Employees'),
                        text: this.$t('No resigned employees data available'),
                        type: 'info'
                    });
                }
            } catch (error) {
                this.$notify({
                    title: this.$t('Error'),
                    text: this.$t('Error loading resigned employees data'),
                    type: 'error'
                });
            }
        },
        getLocalizedOtherReason(otherReason) {
            try {
                // Nếu otherReason là string JSON
                let obj = otherReason;
                if (typeof otherReason === 'string') {
                    otherReason = otherReason.trim();
                    if (!otherReason) return '';
                    if (otherReason.startsWith('{')) {
                        obj = JSON.parse(otherReason);
                    } else {
                        return otherReason || '';
                    }
                }
                if (!obj || typeof obj !== 'object') return obj || '';
                
                const langs = ['vi', 'en', 'cn'];
                const locale = (this.$i18n && this.$i18n.locale) ? this.$i18n.locale : (localStorage.getItem('preferred-locale') || 'en');
                
                // If already has value for current locale, return it
                if (obj[locale] && obj[locale].trim()) return obj[locale];
                
                // find a source language with text
                const sourceLang = langs.find(k => obj[k] && obj[k].trim());
                const sourceText = (sourceLang && obj[sourceLang]) ? obj[sourceLang] : '';
                if (!sourceText) return '';
                
                // cache
                if (!this._otherReasonCache) this._otherReasonCache = new Map();
                const cacheKey = JSON.stringify(obj);
                if (!this._otherReasonCache.has(cacheKey)) {
                    this._otherReasonCache.set(cacheKey, Object.assign({}, obj));
                }
                const cached = this._otherReasonCache.get(cacheKey);
                if (cached[locale] && cached[locale].trim()) return cached[locale];
                
                const translate = async (text, target) => {
                    let code = "";
                    switch (target) {
                        case "vi": code = "secret-v"; break;
                        case "en": code = "secret-e"; break;
                        case "cn": code = "secret-c"; break;
                        default: return "";
                    }
                    try {
                        const formData = new FormData();
                        formData.append("type", code);
                        formData.append("string", text);
                        const response = await this.$axios.post("http://gmo021.cansportsvg.com/public/gg-trans/curl.php", formData);
                        if (response && response.status === 200) {
                            return (typeof response.data === 'string') ? response.data : (response.data?.result || '');
                        }
                    } catch (e) {
                        console.error("translate error:", e);
                    }
                    return "";
                };
                
                (async () => {
                    try {
                        const missing = langs.filter(k => !cached[k] || !cached[k].toString().trim());
                        for (const tgt of missing) {
                            if (tgt === sourceLang) continue;
                            const tr = await translate(sourceText, tgt);
                            if (tr) {
                                const cur = this._otherReasonCache.get(cacheKey) || {};
                                cur[tgt] = tr;
                                this._otherReasonCache.set(cacheKey, cur);
                            }
                        }
                        this.$forceUpdate();
                    } catch (e) {
                        console.error("background translate failed:", e);
                    }
                })();
                
                return sourceText;
            } catch (error) {
                console.error('Error getting localized other reason:', error);
                return otherReason || '';
            }
        },
        setDefaultTab() {
            this.statusFilter = this.defaultTab;
        },
        getSubPositionName(subPosId, showAll = false) {
            if (!subPosId) return '';
            try {
                const subPosition = this.subPos.find(sp => sp.id === parseInt(subPosId));
                if (!subPosition || !subPosition.sub_name) return '';
                const subNameObj = JSON.parse(subPosition.sub_name.trim());
                const locale = this.$i18n.locale || 'en';
                return subNameObj[locale] || subNameObj.en || '';
            } catch (error) {
                console.error('Error parsing sub position name:', error);
                return '';
            }
        },
        calculateDaysRemaining(holdDate) {
            const hold = new Date(holdDate);
            const holdDateOnly = new Date(hold.getFullYear(), hold.getMonth(), hold.getDate());
            
            const deadline = new Date(holdDateOnly);
            deadline.setDate(deadline.getDate() + 90);
            deadline.setHours(23, 59, 59, 999);
            
            const today = new Date();
            const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            
            // Calculate remaining days
            const remaining = Math.ceil((deadline - todayDateOnly) / (1000 * 60 * 60 * 24));
            return remaining;
        },
        getRecruitmentStatus(item) {
            
            const stocknumber = parseInt(item.stocknumber);
            const recruited = parseInt(item.recruited);
            const hold = parseInt(item.hold);
            const resumed = parseInt(item.resumed);
            const status = item.status;
            const statusAmendent = item.status_amendent;
            
            const isCompleted = (status === 'completed') && (!statusAmendent);
            
            if (item.deny_temp && statusAmendent === 'completed') {
                const denyTempData = JSON.parse(item.deny_temp);
                
                if (denyTempData.fullfilldate) {
                    return {
                        text: this.$t('Cancelled'),
                        detail: `(${this.formatDate(denyTempData.fullfilldate)})`,
                        show: true,
                        color: '#f44336', // Đỏ cảnh báo, hủy
                        dark: false,
                    };
                }
            }
            
            if (isCompleted) {
                if (stocknumber === 0) {
                    return {
                        text: this.$t('Closed'),
                        show: true,
                        color: '#6c757d', // Xám trung tính
                    };
                }
                if (stocknumber > recruited) {
                    return {
                        text: this.$t('Recruiting'),
                        show: true,
                        color: '#43A047', // Xanh lá, đang tuyển
                        dark: false,
                    };
                }
            }
            
            if (status === 'completed' && statusAmendent !== 'completed') {
                return {
                    text: this.$t('Recruiting'),
                    show: true,
                    color: '#43A047',
                    dark: false,
                };
            }
            if (status === 'completed' && statusAmendent === 'completed'&& hold ===0 && resumed ===0) {
                return {
                    text: this.$t('Recruiting'),
                    show: true,
                    color: '#E65100',
                    dark: false,
                };
            }
            
            if (statusAmendent === 'completed' && stocknumber > recruited) {
                return {
                    text: this.$t('Recruiting'),
                    detail: '',
                    show: true,
                    color: '#43A047',
                    dark: false,
                };
            }
            
            if (statusAmendent === 'completed' && stocknumber === recruited) {
                return {
                    text: this.$t('Closed'),
                    detail: '',
                    show: true,
                    color: '#6c757d', 
                };
            }
            
            if (hold === 1 && item.hold_date && resumed === 0) {
                const daysRemaining = this.calculateDaysRemaining(item.hold_date);
                let statusText = this.$t('Recruitment hold');
                let detailText = `(${this.formatDate(item.hold_date)} ${this.$t('hold')})`;
                
                if (daysRemaining > 0) {
                    detailText += ` ${this.$t('remaining')} \n${daysRemaining} ${this.$t('days')}`;
                    return {
                        text: statusText,
                        detail: detailText,
                        show: true,
                        color: '#E65100', // Màu vàng chuẩn cho hold còn hiệu lực
                        dark: false,
                    };
                } else {
                    if (item.status_amendent !== 'declined' && item.status !== 'declined') {
                        if (!this.sentNotifications.has(`declined_${item.id}`)) {
                            this.sentNotifications.add(`declined_${item.id}`);
                            this.handleExpiredHold(item);
                        }
                    }
                    detailText += `\n${this.$t('Hold period expired')}`;
                    return {
                        text: statusText,
                        detail: detailText,
                        show: true,
                        color: '#f44336', // Đỏ cảnh báo hold hết hạn
                        dark: false,
                    };
                }
            }
            
            if (resumed === 1 && item.resumed_date) {
                return {
                    text: this.$t('Recruiting'),
                    detail: `(${this.formatDate(item.hold_date)} - ${this.$t('Resumed')}, ${this.formatDate(item.resumed_date)} - ${this.$t('hold')})`,
                    show: true,
                    color: '#2196F3', // Xanh dương tiêu chuẩn cho recruiting resumed
                    dark: false,
                };
            }
            
            if (status === 'completed' && this.hasRequirementChange(item)) {
                return {
                    text: this.$t('Recruiting'),
                    detail: this.$t('#Change in hiring requirements'),
                    show: true,
                    color: '#FFC107', // Vàng cho thay đổi yêu cầu tuyển dụng
                    dark: false,
                };
            }
            
            return {
                text: '',
                detail: '',
                show: false,
                color: '',
                dark: false,
            };
        },
        async handleExpiredHold(item) {
            try {
                if (!this.sentNotifications.has(`email_${item.id}`)) {
                    this.sentNotifications.add(`email_${item.id}`);
                    await this.sendAutoCloseNotification(item);
                }
                await this.updateStatusToDeclined(item);
            } catch (error) {
                console.error('Error handling expired hold:', error);
            }
        },
        async updateStatusToDeclined(item) {
            try {
                // Xác định trạng thái hiện tại cần update
                const currentStatus = item.status_amendent;
                
                const response = await this.$axios.post(this.api + 'denyRequest', {
                    id: item.id,
                    reason: 'Auto declined due to hold period expired',
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: this.dataFlow || '',
                        id: item.id, 
                        status: item.status,
                        approval: item.approval,
                        status_amendent: currentStatus === 'completed' ? 'declined' : item.status_amendent,
                        auto_decline: true,
                        current_processing_status: currentStatus
                    }
                });
                
                if (response.data.success) {
                    // Refresh data after update
                    await this.getAllData();
                    console.log('Status updated to declined successfully');
                } else {
                    console.error('Failed to update status:', response.data.message);
                }
            } catch (error) {
                console.error('Error updating status to declined:', error);
                // Hiển thị thông báo lỗi cho user
                this.$notify({
                    title: this.$t('Error'),
                    text: this.$t('Failed to update status automatically'),
                    type: 'error'
                });
            }
        },
        async sendAutoCloseNotification(item) {
            if (this.sentNotifications.has(item.id)) {
                return;
            }
            try {
                const response = await this.$axios.post(this.api + 'sendAutoCloseEmail', {
                    id: item.id
                });
                if (response.data.success) {
                    this.sentNotifications.add(item.id);
                }
            } catch (error) {
                console.error('Error sending auto close notification:', error);
            }
        },
        hasRequirementChange(item) {
            try {
                if (!item.change_req) return false;
                
                const changeReq = typeof item.change_req === 'string' 
                ? JSON.parse(item.change_req) 
                : item.change_req;
                
                if (Array.isArray(changeReq) && changeReq.length > 0) {
                    const lastChange = changeReq[changeReq.length - 1];
                    return lastChange.change_reason && 
                    lastChange.change_reason.id === 3;
                }
                
                return false;
            } catch (error) {
                console.error('Error checking requirement change:', error);
                return false;
            }
        },
        
        formatDate(date) {
            if (!date) return '';
            try {
                return dayjs(date).format('MM-DD');
            } catch (error) {
                return date;
            }
        },
        getCycleColor(cycleIndex) {
            const colors = {
                1: '#01a6be darken-2',
                2: 'blue darken-2', 
                3: 'deep-orange darken-2',
                4: 'purple darken-2'
            };
            return colors[cycleIndex] || 'grey darken-2';
        },
        formatID(str) {
            if (!str) return "";
            return str.slice(-9);
        },
        statusCounts() {
            const all = this.dataFilteredByYear.length;
            let todo = 0, completed = 0, processing = 0, returned = 0, declined = 0;
            
            this.dataFilteredByYear.forEach(item => {
                if (this.canApproveItemForTodo(item)) {
                    todo++;
                }
                if ([ 'waiting_dept', 'waiting_div', 'waiting_hr_rec_sf','waiting_hr', 'waiting_ad', 'waiting_gm'].includes(item.status) ||
                ['waiting_dept', 'waiting_div', 'waiting_hr_rec_sf','waiting_hr', 'waiting_ad', 'waiting_gm'].includes(item.status_amendent)) {
                    processing++;
                }
                if (item.status === 'completed' || item.status_amendent === 'completed') {
                    completed++;
                }
                if (item.status === 'returned' || item.status_amendent === 'returned' || this.hasReturnedLevel(item)) {
                    returned++;
                }
                if (['declined', 'cancelled'].includes(item.status) || ['declined', 'cancelled'].includes(item.status_amendent)) {
                    declined++;
                }
            });
            
            return {
                all,
                todo,
                completed,
                processing,
                return: returned,
                decline: declined
            };
        },
        isInApprovalFlow() {
            if (!this.dataFlow || !this.activeUser?.empno) return false;
            
            return this.dataFlow.some(level => {
                const isManager = level.managers?.some(manager => 
                manager.empno === this.activeUser.empno
                );
                if (isManager) return true;
                return level.managers?.some(manager => 
                manager.deputies?.some(deputy => 
                deputy.empno === this.activeUser.empno && deputy.status
                )
                );
            });
        },
        
        getHRHeaders() {
            return [
            { text: 'ID', value: 'req_id',align: 'center' },
            { text: 'Submitted by', value: 'submitter',},
            { text: 'Apply Date', value: 'key_in_date',},
            { text: 'Unit code', value: 'unit',  },
            { text: 'Unit Name', value: 'unit_name', },
            { text: 'Position', value: 'pos',  },
            { text: 'Sub position', value: 'sub_pos',  },
            { text: 'Conditions', value: 'conditions', },
            { text: 'Demand', value: 'qty_req', align: 'center',  },
            { text: 'Reason', value: 'reason', },
            { text: 'Approval Status', value: 'status',},
            { text: 'Recruitment status', value: 'recruited_status', },
            { text: 'Recruited', value: 'recruited', align: 'center',  },
            { text: 'Resign', value: 'resign', align: 'center', },
            { text: 'Print', value: 'action', sortable: false,  }
            ];
        },
        formatDate(date) {
            return date ? dayjs(date).format('YYYY-MM-DD') : '';
        },
        getCandidatesCount(candidates) {
            try {
                const parsedCandidates = JSON.parse(candidates || '[]');
                return parsedCandidates.length;
            } catch (error) {
                console.error("Error parsing candidates:", error);
                return 0;
            }
        },
        clearSelection() {
            this.selectedCandidates = [];
            this.selectAll = false; 
        },
        showCandidatesDialog(candidates) {
            try {
                this.parsedCandidates = JSON.parse(candidates || '[]');
                if (this.parsedCandidates.length > 0) {
                    this.selectIdMpr = this.dataMpr.find(mpr => mpr.candidates === candidates);
                    this.clearSelection(); 
                    this.candidatesDialog = true;
                } else {
                    this.$notify({
                        title: 'No Candidates',
                        text: 'No candidates data available',
                        type: 'info'
                    });
                }
            } catch (error) {
                this.$notify({
                    title: 'Error',
                    text: 'Error loading candidates data', 
                    type: 'error'
                });
            }
        },
        async updateCancelDate() {
            if (!this.selectIdMpr) return;
            
            try {
                this.loading = true;
                const cancelData = {
                    date: this.selectedCancelDate,
                    reason: this.cancelReason
                };
                const response = await this.$axios.post(this.api + 'updateCancelDate', {
                    id: this.selectIdMpr.id,
                    cancel_date: JSON.stringify(cancelData)
                });
                if (response.data.success) {
                    this.cancelDialog = false;
                    this.selectedCancelDate = ''; 
                    this.cancelReason = '';
                    this.selectIdMpr = null;
                    await this.fetchData(); 
                }
            } catch (error) {
                console.error('Error updating cancel date:', error);
                this.$notify({
                    title: this.$t('Error'),
                    text: this.$t('Failed to update cancel date'),
                    type: 'error'
                });
            } finally {
                this.loading = false;
            }
        },
        async handleCancelDateClick(item) {
            this.selectIdMpr = item;
            if (item.cancel_date) {
                try {
                    const cancelData = JSON.parse(item.cancel_date);
                    this.selectedCancelDate = cancelData.date || '';
                    this.cancelReason = cancelData.reason || '';
                } catch (e) {
                    this.selectedCancelDate = '';
                    this.cancelReason = '';
                }
            } else {
                this.selectedCancelDate = '';
                this.cancelReason = '';
            }
            this.cancelDialog = true;
        },
        formatCancelDate(value) {
            if (!value) return '';
            try {
                const cancelData = JSON.parse(value);
                return cancelData.date || '';
            } catch (e) {
                return '';
            }
        },
        async getAllData() {
            let params = {
                loc: this.activeUser.location,
                dept: this.activeUser.dept,
                empno: this.activeUser.empno,
                high_dept:this.activeUser.hight_dept,
                role: this.activeUser.role,
            }
            try {
                const res = await this.$axios.post(this.api + 'getAllDataMpr', params);
                if (res.status === 200) {
                    this.dataMpr = res.data.dataMpr;
                    await this.$nextTick();
                    this.setDefaultTab();
                    
                } else {
                    throw new Error('Failed to fetch data');
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        },
        async fetchData() {
            let params = {
                loc:this.activeUser.location,
            }
            try {
                const res = await this.$axios.post(this.api + 'getDataModel', params);
                if (res.status === 200) {
                    this.dataRecruitmentReason = res.data.dataRecReason;
                    this.unitNames = res.data.dataDeptHrm
                    this.pos = res.data.dataTypeApply;
                    this.lang = res.data.dataLang;
                    this.subPos = res.data.dataSubPos;
                    this.flowCache.clear();
                    // await this.preloadFlowData();
                    this.filteredItems = [];
                } else {
                    throw new Error('Failed to fetch data');
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        },
        
        formatWorkExperience(exp) {
            if (!exp || !exp.id) return { text: 'N/A', color: 'grey' };
            
            const map = {
                1: { text: this.$t('Not Required'), color: 'grey' },
                2: { text: this.$t('< 1 year'), color: 'primary' },
                3: { text: this.$t('1-2 years'), color: 'info' },
                4: { text: this.$t('2-3 years'), color: 'warning' },
                5: { text: this.$t('> 3 years'), color: 'error' }
            };
            
            if (exp.name) {
                const locale = this.$i18n.locale || 'en';
                if (exp.name[locale]) {
                    return {
                        text: exp.name[locale],
                        color: map[exp.id]?.color || 'grey'
                    };
                }
            }
            
            return map[exp.id] || { text: 'N/A', color: 'grey' };
        },
        
        formatReason(reasonId) {
            try {
                // Kiểm tra nếu reasonId là 3 thì trả về object với type rỗng
                if (reasonId === 3) {
                    return {
                        type: '', // Để trống vì sẽ hiển thị isOther_reason
                        oldEmpNo: '',
                        requestDate: ''
                    };
                }
                
                const reasonObj = this.dataRecruitmentReason.find(reason => reason.id === parseInt(reasonId));
                if (!reasonObj) {
                    return { 
                        type: this.$t('Unknown reason'), 
                        oldEmpNo: '',
                        requestDate: '' 
                    };
                }
                
                const nameObj = JSON.parse(reasonObj.name);
                const locale = this.$i18n.locale || 'en';
                const localizedName = nameObj[locale] || nameObj.en || reasonObj.id;
                
                return {
                    type: localizedName,
                    oldEmpNo: '',
                    requestDate: ''
                };
            } catch (error) {
                console.error('Error formatting reason:', error);
                return { 
                    type: String(reasonId), 
                    oldEmpNo: '', 
                    requestDate: '' 
                };
            }
        },
        
        downloadFile(filename) {
            if (!filename) {
                this.$store.dispatch('showWarning', this.$t('No file attached'));
                return;
            }
            window.open(`${this.storageUrl}${filename}`, '_blank');
        },
        
        formatLanguageSkills(eng, chn, either) {
            const languages = [];
            if ((!eng || eng.length === 0) && (!chn || chn.length === 0) && (!either || either.length === 0)) {
                return null;
            }
            if (eng && eng.length > 0) {
                languages.push(this.$t('English'));
            }
            if (chn && chn.length > 0) {
                languages.push(this.$t('Chinese'));
            }
            if (either && either.length > 0) {
                languages.push(this.$t('Either - or'));
            }
            return languages.length > 0 ? languages.join('/') : this.$t('No required');
        },
        
        customSearch() {
            if (!this.search) {
                this.filteredItems = this.formattedData;
                return;
            }
            
            const searchTerm = this.search.toLowerCase();
            const searchFields = [
            'req_id',
            'date_req',
            'unit_name',
            'division',
            'reason',
            'recruited',
            'resign'
            ];
            
            this.filteredItems = this.formattedData.filter(item => {
                const matchesSearchTerm = field => {
                    const value = item[field];
                    if (Array.isArray(value)) {
                        return value.join(', ').toLowerCase().includes(searchTerm);
                    }
                    return String(value || '').toLowerCase().includes(searchTerm);
                };
                
                return searchFields.some(matchesSearchTerm) ||
                (item.submiterInfo?.name || '').toLowerCase().includes(searchTerm) ||
                this.getPositionName(item.pos).toLowerCase().includes(searchTerm) || // Kiểm tra vị trí chính
                (item.sub_pos ? this.getSubPositionName(item.sub_pos, true).toLowerCase().includes(searchTerm) : false) || // Kiểm tra sub position
                (item.computerSkillsInfo?.selected && Array.isArray(item.computerSkillsInfo.selected) && 
                item.computerSkillsInfo.selected.join(', ').toLowerCase().includes(searchTerm)) ||
                this.getStatusText(item.status).toLowerCase().includes(searchTerm) ||
                this.getStatusText(item.status_amendent).toLowerCase().includes(searchTerm);
            });
        },
        openDetail(item) {
            if (typeof item.qty_req === 'string' && item.qty_req.startsWith('{')) {
                try {
                    const qtyData = JSON.parse(item.qty_req);
                    item.total_qty = (
                    parseInt(qtyData.workers || 0) +
                    parseInt(qtyData.staff || 0) +
                    parseInt(qtyData.staff_lang || 0) +
                    parseInt(qtyData.engineers || 0) +
                    parseInt(qtyData.leader || 0)  +
                    parseInt(qtyData.tranlator || 0) 
                    );
                } catch (e) {
                    console.error("Error preprocessing quantity:", e);
                }
            }
            
            const parsedItem = {
                ...item,
                pos: item.pos ? parseInt(item.pos) : null,
                edu: item.edu ? parseInt(item.edu) : null,
                w_exp: item.w_exp ? parseInt(item.w_exp) : null,
                wps: item.wps ? JSON.parse(item.wps) : []
            };
            
            this.selectedItem = Object.assign({}, parsedItem);
            this.isChange = false;
            this.isEdit = true;
            this.appDialog = true;
        },
        handleYearSelect(date) {
            this.yearMenu = false;
            this.selectedYear = date.split('-')[0];
        },
        clearYearSelection() {
            this.selectedYears = [];
            this.yearMenu = false;
        },
        hasReturnedLevel(item) {
            if (!item.approval) return false;
            try {
                const approvalData = JSON.parse(item.approval);
                return approvalData.some(level => level.status === 'returned') && item.status === 'waiting_dept';
            } catch (error) {
                console.error('Error parsing approval data:', error);
                return false;
            }
            
        },
        printDocument(item) {
            if (item.qty_req && typeof item.qty_req === 'string') {
                try {
                    const qtyData = JSON.parse(item.qty_req);
                } catch (e) {
                    console.error("Error pre-processing qty_req for printing:", e);
                }
            }
            
            const routeData = this.$router.resolve({
                name: 'template-print-request',
                query: { id: item.id }
            });
            window.open(routeData.href, '_blank');
        },
        
        getLocalizedName(nameObj) {
            if (!nameObj) return '-';
            const locale = this.$i18n.locale || 'en';
            return nameObj[locale] || nameObj.en || '-';
        },
        getDisplayQuantity(qtyReq, item) {
            if (!qtyReq) return 0;
            
            try {
                // Kiểm tra deny_temp trước tiên VÀ status_amendent phải là 'completed'
                if (item && 
                item.deny_temp && 
                typeof item.deny_temp === 'string' && 
                item.deny_temp.trim() !== '' &&
                item.status_amendent === 'completed') {
                    try {
                        const denyTempData = JSON.parse(item.deny_temp);
                        // Nếu có deny_temp và status_amendent='completed', sử dụng recruited từ deny_temp hoặc recruited từ item
                        if (denyTempData.recruited !== undefined) {
                            return parseInt(denyTempData.recruited);
                        } else if (item.recruited !== undefined) {
                            return parseInt(item.recruited);
                        }
                    } catch (error) {
                        console.error('Error parsing deny_temp for quantity display:', error);
                    }
                }
                
                // Logic cũ cho trường hợp bình thường
                // if (this.hasQuantityChange({qty_req: qtyReq, change_req: item?.change_req})) {
                //     return parseInt(this.getChangeQuantity({qty_req: qtyReq, change_req: item?.change_req}));
                // }
                if (typeof qtyReq === 'string') {
                    if (qtyReq.startsWith('{')) {
                        const qtyData = JSON.parse(qtyReq);
                        return (
                        parseInt(qtyData.workers) +
                        parseInt(qtyData.staff) +
                        parseInt(qtyData.engineers) +
                        parseInt(qtyData.leader) +
                        parseInt(qtyData.tranlator)
                        );
                    } else {
                        return parseInt(qtyReq);
                    }
                }
                return parseInt(qtyReq);
            } catch (e) {
                console.error("Error parsing quantity:", e);
                return 0;
            }
        },
        
        calculateOriginalQuantity(qtyReq) {
            if (typeof qtyReq === 'string') {
                if (qtyReq.startsWith('{')) {
                    const qtyData = JSON.parse(qtyReq);
                    // Chỉ tính đến leader, bỏ qua tranlator và level
                    return (
                    parseInt(qtyData.workers || 0) +
                    parseInt(qtyData.staff || 0) +
                    parseInt(qtyData.staff_lang || 0) +
                    parseInt(qtyData.engineers || 0) +
                    parseInt(qtyData.leader || 0)
                    // Không tính: tranlator và level
                    );
                } else {
                    return parseInt(qtyReq) || 0;
                }
            }
            return parseInt(qtyReq) || 0;
        },
        
        isAmendmentDeniedButOriginalValid(item) {
            if (!item) return false;
            
            const isAmendment = !!(item.req_id_change);
            if (!isAmendment) return false;
            const hasLogs = !!(item.logs && item.logs.trim() !== '' && item.logs !== 'null');
            if (!hasLogs) return false;
            
            if (item.status === 'completed' && this.hasQuantityChange(item)) {
                // Kiểm tra xem amendment có bị từ chối không
                return this.isAmendmentDenied(item);
            }
            return false;
        },
        
        isAmendmentDenied(item) {
            if (!item.approval) return false;
            
            try {
                const approvalData = JSON.parse(item.approval);
                return approvalData.some(level => level.status === 'rejected');
            } catch (error) {
                console.error('Error parsing approval data:', error);
                return false;
            }
        },
        
        hasQuantityChange(item) {
            // Nếu có deny_temp VÀ status_amendent='completed', không hiển thị change quantity tooltip
            if (item.deny_temp && 
            item.deny_temp.trim() !== '' && 
            item.status_amendent === 'completed') {
                return false;
            }
            
            if (!item.change_req) return false;
            try {
                const changeReq = typeof item.change_req === 'string' 
                ? JSON.parse(item.change_req) 
                : item.change_req;
                
                return Array.isArray(changeReq) && changeReq.length > 0 && 
                changeReq.some(change => change.change_qty);
            } catch (e) {
                console.error("Error parsing change_req:", e);
                return false;
            }
        },
        
        getChangeQuantity(item) {
            try {
                const changeReq = typeof item.change_req === 'string' 
                ? JSON.parse(item.change_req) 
                : item.change_req;
                
                if (Array.isArray(changeReq)) {
                    const validChanges = changeReq.filter(change => change.change_qty);
                    if (validChanges.length > 0) {
                        return validChanges[validChanges.length - 1].change_qty;
                    }
                }
                else if (changeReq && changeReq.change_qty) {
                    return changeReq.change_qty;
                }
                return 0;
            } catch (e) {
                console.error("Error getting change quantity:", e);
                return 0;
            }
        },
        
        getChangeReason(item) {
            try {
                const changeReq = typeof item.change_req === 'string' 
                ? JSON.parse(item.change_req) 
                : item.change_req;
                
                if (Array.isArray(changeReq)) {
                    const validChanges = changeReq.filter(change => change.change_reason);
                    if (validChanges.length > 0) {
                        const lastChange = validChanges[validChanges.length - 1];
                        if (lastChange.change_reason) {
                            const reason = lastChange.change_reason;
                            if (reason.id && reason.text) {
                                return reason.text;
                            } 
                            else if (typeof reason === 'string') {
                                return reason;
                            }
                            else {
                                return JSON.stringify(reason);
                            }
                        }
                    }
                }
                else if (changeReq && changeReq.change_reason) {
                    const reason = changeReq.change_reason;
                    if (reason.id && reason.text) {
                        return reason.text;
                    } 
                    else if (typeof reason === 'string') {
                        return reason;
                    }
                    else {
                        return JSON.stringify(reason);
                    }
                }
                return this.$t('Changed quantity');
            } catch (e) {
                console.error("Error getting change reason:", e);
                return this.$t('Changed quantity');
            }
        },
        async openAcceptDialog(item) {
            this.selectedItemForAccept = item;
            try {
                const flowData = await this.getFlowDataForItem(item);
                if (flowData) {
                    this.dataFlow = flowData;
                    this.confirmAcceptDialog = true;
                }
            } catch (error) {
                console.error("Error in openAcceptDialog:", error);
            }
        },
        async confirmAccept() {
            if (!this.selectedItemForAccept) return;
            
            let params = {
                user_empno: this.activeUser.empno,
                dataReq: {
                    dataFlow: this.dataFlow,
                    id: this.selectedItemForAccept.id,
                    status: this.selectedItemForAccept.status,
                    status_amendent: this.selectedItemForAccept.status_amendent,
                    approval: this.selectedItemForAccept.approval,
                    
                }
            };
            const res = await this.$axios.post(this.api + "acceptRequest", params);
            if (res.status === 200) {
                await this.getAllData();
            }
            this.confirmAcceptDialog = false;
            this.selectedItemForAccept = null;
        },
        async openDenyDialog(item) {
            this.selectedItemForAction = item;
            this.denyReason = '';
            try {
                const flowData = await this.getFlowDataForItem(item);
                if (flowData) {
                    this.dataFlow = flowData;
                    this.confirmDenyDialog = true;
                }
            } catch (error) {
                console.error("Error in openDenyDialog:", error);
            }
        },
        async confirmDeny() {
            if (!this.denyReason.trim()) {
                return;
            }
            try {
                const response = await this.$axios.post(this.api + 'denyRequest', {
                    id: this.selectedItemForAction.id,
                    reason: this.denyReason,
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: this.dataFlow,
                        id: this.selectedItemForAction.id,
                        status: this.selectedItemForAction.status,
                        approval: this.selectedItemForAction.approval,
                        status_amendent: this.selectedItemForAction.status_amendent,
                    }
                });
                
                if (response.data.success) {
                    await this.getAllData();
                    this.confirmDenyDialog = false;
                    this.selectedItemForAction = null;
                    this.denyReason = '';
                }
            } catch (error) {
                console.error('Error denying request:', error);
            }
        },
        
        getApprovalStatusColor(status) {
            const normalizedStatus = status.toLowerCase();
            switch (normalizedStatus) {
                case 'waiting':
                return 'warning';
                case 'approved':
                return 'success';
                case 'rejected':
                return 'error';
                case 'processing':
                return 'info';
                case 'returned':
                return 'amber';
                default:
                return 'grey';
            }
        },
        canApproveItem(item) {
            if (!item || !this.activeUser) return false;
            
            const effectiveStatus = item.status_amendent || item.status;
            
            if (effectiveStatus === 'completed' || effectiveStatus === 'declined') return false;
            
            try {
                const flowData = this.approvalFlowCache[item.id] || this.generalFlowData;
                if (!flowData) return false;
                
                const statusToLevelCode = {
                    'waiting_gm': 'general_manager'
                };
                
                const currentLevelCode = statusToLevelCode[effectiveStatus];
                
                if (!currentLevelCode) return false;
                
                const currentLevel = flowData.find(level => level.lvl_code === currentLevelCode);
                if (!currentLevel?.managers) return false;
                
                return currentLevel.managers.some(manager => {
                    if (manager.empno === this.activeUser.empno) return true;
                    
                    if (manager.deputies && Array.isArray(manager.deputies)) {
                        return manager.deputies.some(deputy =>
                        deputy.empno === this.activeUser.empno && deputy.status === true
                        );
                    }
                    return false;
                });
                
            } catch (error) {
                console.error('Error in canApproveItem:', error);
                return false;
            }
        },
        canApproveItemForTodo(item)
        {
            // if (!item || !this.activeUser) return false;
            if ((item.status === 'completed' || item.status === 'declined') && 
            (!item.status_amendent || item.status_amendent === 'completed' || item.status_amendent === 'declined')) {
                return false;
            }
            const validStatuses = [
            'waiting_dept', 
            'waiting_div', 
            'waiting_hr_rec_sf', 
            'waiting_hr', 
            'waiting_ad', 
            'waiting_gm'
            ];
            
            // Kiểm tra xem có ít nhất một trong hai trạng thái thuộc danh sách hợp lệ
            if (!validStatuses.includes(item.status) && 
            (!item.status_amendent || !validStatuses.includes(item.status_amendent))) {
                return false;
            }
            
            try {
                const approval = JSON.parse(item.approval);
                
                // Nếu có status_amendent, sử dụng logic khác
                if (item.status_amendent) {
                    const statusToLevelCode = {
                        'waiting_dept': 'dept_manager',
                        'waiting_div': 'division_manager',
                        'waiting_hr_rec_sf': 'hr_rec_sf',
                        'waiting_hr': 'hr_manager',
                        'waiting_ad': 'smp_manager',
                        'waiting_gm': 'general_manager'
                    };
                    
                    const currentLevelCode = statusToLevelCode[item.status_amendent];
                    if (!currentLevelCode) return false;
                    
                    const flowData = this.approvalFlowCache[item.id] || this.generalFlowData;
                    if (!flowData || !Array.isArray(flowData)) return false;
                    
                    const currentFlowLevel = flowData.find(level => level.lvl_code === currentLevelCode);
                    if (!currentFlowLevel?.managers) return false;
                    
                    return currentFlowLevel.managers.some(manager => {
                        if (manager.empno === this.activeUser.empno) return true;
                        
                        if (manager.deputies && Array.isArray(manager.deputies)) {
                            return manager.deputies.some(deputy => 
                            deputy.empno === this.activeUser.empno && 
                            deputy.status === true
                            );
                        }
                        return false;
                    });
                }
                
                // Logic cũ cho status thông thường
                const currentLevel = approval.find(level => level.status === 'waiting');
                if (!currentLevel) return false;
                
                if (currentLevel.approver && currentLevel.approver.empno === this.activeUser.empno) {
                    return true;
                }
                
                const flowData = this.approvalFlowCache[item.id] || this.generalFlowData;
                if (!flowData || !Array.isArray(flowData)) return false;
                
                const statusToLevelCode = {
                    'waiting_dept': 'dept_manager',
                    'waiting_div': 'division_manager',
                    'waiting_hr_rec_sf': 'hr_rec_sf',
                    'waiting_hr': 'hr_manager',
                    'waiting_ad': 'smp_manager',
                    'waiting_gm': 'general_manager'
                };
                const currentLevelCode = statusToLevelCode[item.status];
                if (!currentLevelCode) return false;
                
                const currentFlowLevel = flowData.find(level => level.lvl_code === currentLevelCode);
                if (!currentFlowLevel?.managers) return false;
                
                return currentFlowLevel.managers.some(manager => {
                    if (manager.empno === this.activeUser.empno) return true;
                    
                    if (manager.deputies && Array.isArray(manager.deputies)) {
                        return manager.deputies.some(deputy => 
                        deputy.empno === this.activeUser.empno && 
                        deputy.status === true
                        );
                    }
                    return false;
                });
                
            } catch (error) {
                console.error('Error in canApproveItemForTodo:', error);
                return false;
            }
        },
        async fetchFlowData() {
            if (!this.activeUser) return;
            try {
                const res = await this.$axios.post(this.api + "getDataFlowForItem", {
                    empno: this.activeUser.empno,
                    name: this.activeUser.name,
                    dept_sumt: this.activeUser.dept,
                    high_dept: this.activeUser.hight_dept,
                });
                
                if (res.data) {
                    this.generalFlowData = res.data.flow_data;
                }
            } catch (error) {
                console.log("Error fetching general flow data:", error);
                this.statusFilter = 0; // Set to "All" tab in case of error
            }
        },
        getAmendmentStatusText(status, item = {}) {
            if (item && this.getReturnInfo && this.getReturnInfo(item).returned) {
                return "🔄" + this.$t("Returned");
            }
            const prefix = "🔄";
            switch (status) {
                
                case "waiting_dept":
                return prefix + this.$t("Pending Dept");
                case "waiting_div":
                return prefix + this.$t("Pending Div");
                case "waiting_hr_rec_sf":
                return prefix + this.$t("Pending HR Rec SF");
                case "waiting_hr":
                return prefix + this.$t("Pending HR");
                case "waiting_ad":
                return prefix + this.$t("Pending AD");
                case "waiting_gm":
                return prefix + this.$t("Pending GM");
                case "completed":
                return prefix + this.$t("Approved");
                case "declined":
                return prefix + this.$t("Rejected");
                case "returned":
                return prefix + this.$t("Returned");
                default:
                return prefix + this.$t("Amend - {status}", { status: status });
            }
        },
        
        getAmendmentStatusColor(status) {
            switch (status) {
                
                case "waiting_dept":
                return "deep-orange darken-1";
                case "waiting_div":
                return "pink darken-1";
                case "waiting_hr_rec_sf":
                return "orange darken-1";
                case "waiting_hr":
                return "purple darken-1";
                case "waiting_ad":
                return "indigo darken-1";
                case "waiting_gm":
                return "blue darken-1";
                case "completed":
                return "green darken-1";
                case "declined":
                return "red darken-1";
                case "returned":
                return "amber darken-1";
                default:
                return "grey darken-1";
            }
        },
        getStatusText(status, item = {}) {
            if (status === "declined") {
                return this.$t("Rejected");
            }
            if (item && this.getReturnInfo && this.getReturnInfo(item).returned) {
                return this.$t("Returned");
            }
            switch (status) {
                case "completed":
                return this.$t("Approved");
                
                case "waiting_dept":
                return this.$t("Pending Dept");
                case "waiting_div":
                return this.$t("Pending Div");
                case "waiting_hr_rec_sf":
                return this.$t("Pending HR Rec SF");
                case "waiting_hr":
                return this.$t("Pending HR");
                case "waiting_ad":
                return this.$t("Pending AD");
                case "waiting_gm":
                return this.$t("Pending GM");
                case "declined":
                return this.$t("Rejected");
                case "returned":
                return this.$t("Returned");
                default:
                return status || "";
            }
        },
        getStatusColor(status,item = {}) {
            switch (status) {
                
                case "waiting_dept":
                return "deep-orange";
                case "waiting_div":
                return "pink darken-1";
                case "waiting_hr_rec_sf":
                return "orange darken-2";
                case "waiting_hr":
                return "purple";
                case "waiting_ad":
                return "indigo";
                case "waiting_gm":
                return "blue darken-3";
                
                case "completed":
                return "green darken-2";
                case "declined":
                return "red darken-2";
                case "cancelled":
                return "grey darken-3";
                
                case "pending":
                return "lime darken-2";
                case "processing":
                return "#01a6be";
                case "returned":
                return "amber";
                
                default:
                return "grey";
            }
        },
        getCurrentLevel(status) {
            const statusLevels = {
                'waiting_dept': 1,
                'waiting_div': 2,
                'waiting_hr_rec_sf': 3,
                'waiting_hr': 4,
                'waiting_ad': 5,
                'waiting_gm': 6,
                'completed': 7,
                'declined': 0
            };
            return statusLevels[status] || 0;
        },
        
        async openReturnDialog(item) {
            this.selectedItemForAction = item;
            this.returnReason = '';
            try {
                const flowData = await this.getFlowDataForItem(item);
                if (flowData) {
                    this.dataFlow = flowData;
                    this.confirmReturnDialog = true;
                }
            } catch (error) {
                console.error("Error in openReturnDialog:", error);
            }
        },
        
        async confirmReturn() {
            if (!this.returnReason.trim()) {
                return;
            }
            
            try {
                const response = await this.$axios.post(this.api + 'returnRequest', {
                    id: this.selectedItemForAction.id,
                    reason: this.returnReason,
                    returnToLevel: 'waiting_dept', // Luôn trả đơn cho unit level
                    user_empno: this.activeUser.empno,
                    dataReq: {
                        dataFlow: this.dataFlow,
                        id: this.selectedItemForAction.id,
                        status: this.selectedItemForAction.status,
                        status_amendent: this.selectedItemForAction.status_amendent,
                        approval: this.selectedItemForAction.approval,
                    }
                });
                
                if (response.data.success) {
                    await this.getAllData();
                    this.confirmReturnDialog = false;
                    this.selectedItemForAction = null;
                    this.returnReason = '';
                }
            } catch (error) {
                console.error('Error returning request:', error);
                this.$store.dispatch('showError', this.$t('Failed to return request'));
            }
        },
        getNextApproverInfo(item) {
            try {
                if ((item.status === 'completed' || item.status === 'declined') && 
                (!item.status_amendent || item.status_amendent === 'completed' || item.status_amendent === 'declined')) {
                    return null;
                }
                
                let flowData = this.approvalFlowCache[item.id];
                
                if (!flowData) {
                    this.loadFlowDataForItem(item);
                    return this.$t('Loading approver info...');
                }
                
                const statusLevels = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                // Determine which status to use for getting approver info
                let effectiveStatus = null;
                
                // Priority: amendment status if it's in waiting state
                if (item.status_amendent && statusLevels.hasOwnProperty(item.status_amendent)) {
                    effectiveStatus = item.status_amendent;
                }
                // Otherwise use main status if it's in waiting state
                else if (item.status && statusLevels.hasOwnProperty(item.status)) {
                    effectiveStatus = item.status;
                }
                
                // If no effective status found, return null
                if (!effectiveStatus) return null;
                
                const currentLevel = statusLevels[effectiveStatus];
                const currentFlow = flowData[currentLevel];
                
                if (!currentFlow || !currentFlow.managers || currentFlow.managers.length === 0) {
                    return null;
                }
                
                const mainApprover = currentFlow.managers[0];
                const deputies = mainApprover.deputies || [];
                
                let approverText = `${this.$t('Manager')}: ${mainApprover.empno} - ${mainApprover.name}`;
                if (deputies.length > 0) {
                    const activeDeputies = deputies.filter(d => d.status);
                    if (activeDeputies.length > 0) {
                        approverText += `\n${this.$t('Deputies')}: ` +
                        activeDeputies.map(d => `${d.empno} - ${d.name}`).join('\n');
                    }
                }
                return approverText;
                
            } catch (error) {
                console.error('Error getting next approver info:', error);
                return null;
            }
        },
        getStatusWithApprover(status, item) {
            const statusText = this.$t(this.getStatusText(status, item));
            const approverInfo = this.getApproverName(status, item);
            
            if (approverInfo) {
                return `${statusText}`;
            }
            return statusText;
        },
        
        getAmendmentStatusWithApprover(status, item) {
            const statusText = this.$t(this.getAmendmentStatusText(status));
            const approverInfo = this.getApproverNameForAmendment(status, item);
            
            if (approverInfo) {
                return `${statusText}`;
            }
            return statusText;
        },
        
        getApproverName(status, item) {
            try {
                if (!status || status === 'completed' || status === 'declined') {
                    return null;
                }
                
                const statusLevels = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                if (!statusLevels.hasOwnProperty(status)) return null;
                
                let flowData = this.approvalFlowCache[item.id];
                if (!flowData) return null;
                
                const currentLevel = statusLevels[status];
                const currentFlow = flowData[currentLevel];
                
                if (!currentFlow || !currentFlow.managers || currentFlow.managers.length === 0) {
                    return null;
                }
                
                const mainApprover = currentFlow.managers[0];
                return mainApprover.name || mainApprover.empno;
                
            } catch (error) {
                console.error('Error getting approver name:', error);
                return null;
            }
        },
        
        getApproverNameForAmendment(status, item) {
            try {
                if (!status || status === 'completed' || status === 'declined') {
                    return null;
                }
                
                const statusLevels = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                if (!statusLevels.hasOwnProperty(status)) return null;
                
                let flowData = this.approvalFlowCache[item.id];
                if (!flowData) return null;
                
                const currentLevel = statusLevels[status];
                const currentFlow = flowData[currentLevel];
                
                if (!currentFlow || !currentFlow.managers || currentFlow.managers.length === 0) {
                    return null;
                }
                
                const mainApprover = currentFlow.managers[0];
                return mainApprover.name || mainApprover.empno;
                
            } catch (error) {
                console.error('Error getting amendment approver name:', error);
                return null;
            }
        },
        // Thêm method riêng để lấy thông tin cho status chính
        getNextApproverInfoForStatus(item) {
            try {
                if (!item.status || item.status === 'completed' || item.status === 'declined') {
                    return null;
                }
                
                const statusLevels = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                if (!statusLevels.hasOwnProperty(item.status)) return null;
                
                let flowData = this.approvalFlowCache[item.id];
                if (!flowData) {
                    this.loadFlowDataForItem(item);
                    return this.$t('Loading approver info...');
                }
                
                const currentLevel = statusLevels[item.status];
                const currentFlow = flowData[currentLevel];
                
                if (!currentFlow || !currentFlow.managers || currentFlow.managers.length === 0) {
                    return null;
                }
                
                const mainApprover = currentFlow.managers[0];
                const deputies = mainApprover.deputies || [];
                
                let approverText = `${this.$t('Manager')}: ${mainApprover.empno} - ${mainApprover.name}`;
                if (deputies.length > 0) {
                    const activeDeputies = deputies.filter(d => d.status);
                    if (activeDeputies.length > 0) {
                        approverText += `\n${this.$t('Deputies')}: ` +
                        activeDeputies.map(d => `${d.empno} - ${d.name}`).join('\n');
                    }
                }
                return approverText;
                
            } catch (error) {
                console.error('Error getting status approver info:', error);
                return null;
            }
        },
        
        // Thêm method riêng để lấy thông tin cho status_amendent
        getNextApproverInfoForAmendment(item) {
            try {
                if (!item.status_amendent || item.status_amendent === 'completed' || item.status_amendent === 'declined') {
                    return null;
                }
                
                const statusLevels = {
                    'waiting_dept': 0,
                    'waiting_div': 1,
                    'waiting_hr_rec_sf': 2,
                    'waiting_hr': 3,
                    'waiting_ad': 4,
                    'waiting_gm': 5
                };
                
                if (!statusLevels.hasOwnProperty(item.status_amendent)) return null;
                
                let flowData = this.approvalFlowCache[item.id];
                if (!flowData) {
                    this.loadFlowDataForItem(item);
                    return this.$t('Loading approver info...');
                }
                
                const currentLevel = statusLevels[item.status_amendent];
                const currentFlow = flowData[currentLevel];
                
                if (!currentFlow || !currentFlow.managers || currentFlow.managers.length === 0) {
                    return null;
                }
                
                const mainApprover = currentFlow.managers[0];
                const deputies = mainApprover.deputies || [];
                
                let approverText = `${this.$t('Manager')}: ${mainApprover.empno} - ${mainApprover.name}`;
                if (deputies.length > 0) {
                    const activeDeputies = deputies.filter(d => d.status);
                    if (activeDeputies.length > 0) {
                        approverText += `\n${this.$t('Deputies')}: ` +
                        activeDeputies.map(d => `${d.empno} - ${d.name}`).join('\n');
                    }
                }
                return approverText;
                
            } catch (error) {
                console.error('Error getting amendment approver info:', error);
                return null;
            }
        },
        loadFlowDataForItem(item) {
            if (this.approvalFlowCache[item.id]) return;
            
            this.getFlowDataForItem(item)
            .then(() => {
                this.$forceUpdate();
            })
            .catch(error => {
                console.error("Error loading flow data:", error);
            });
        },
        async getFlowDataForItem(item) {
            const itemId = item.id;
            
            if (this.approvalFlowCache[itemId]) {
                return this.approvalFlowCache[itemId];
            }
            
            if (this.flowRequestInProgress[itemId]) {
                return new Promise((resolve, reject) => {
                    if (!this.flowRequestQueue[itemId]) {
                        this.flowRequestQueue[itemId] = [];
                    }
                    this.flowRequestQueue[itemId].push({ resolve, reject });
                });
            }
            
            this.flowRequestInProgress[itemId] = true;
            
            const params = {
                id: itemId,
                empno: item.submiterInfo?.empno || item.submiterInfo?.empno,
                name: item.submiterInfo?.name,
                dept: item.submiterInfo?.dept_sumt,
                high_dept: item.submiterInfo?.high_dept,
            };
            
            try {
                const res = await this.$axios.post(this.api + "getDataFlowForItem", params);
                if (res.data && res.data.flow_data) {
                    this.approvalFlowCache[itemId] = res.data.flow_data;
                    
                    if (this.flowRequestQueue[itemId]) {
                        this.flowRequestQueue[itemId].forEach(request => {
                            request.resolve(res.data.flow_data);
                        });
                        delete this.flowRequestQueue[itemId];
                    }
                    
                    return res.data.flow_data;
                }
                return null;
            } catch (error) {
                if (this.flowRequestQueue[itemId]) {
                    this.flowRequestQueue[itemId].forEach(request => {
                        request.reject(error);
                    });
                    delete this.flowRequestQueue[itemId];
                }
                console.error("Error getting flow data:", error);
                throw error;
            } finally {
                delete this.flowRequestInProgress[itemId];
            }
        },
        
        getLevelName(status) {
            const statusLevels = {
                'waiting_dept': 1,
                'waiting_div': 2,
                'waiting_hr_rec_sf': 3,
                'waiting_hr': 4,
                'waiting_ad': 5,
                'waiting_gm': 6,
                'completed': 7,
                'declined': 0
            };
            return statusLevels[status] || 0;
        },
        
        async openApprovalDetails(item) {
            try {
                this.selectedItem = item;
                // Lấy dữ liệu lịch sử từ logs
                this.selectedApprovalData = this.getApprovalLogsData(item);
                // Sắp xếp dữ liệu lịch sử
                this.selectedApprovalData.sort((a, b) => {
                    if (a.cycleIndex !== b.cycleIndex) {
                        return a.cycleIndex - b.cycleIndex;
                    }
                    return a.level - b.level;
                });
                this.approvalPanelsActive = 0;
                this.approvalDialog = true;
            } catch (error) {
                console.error('Error opening approval details:', error);
            }
        },
        
        getApprovalLogsData(item) {
            try {
                let logsData = [];
                
                // Chỉ xử lý dữ liệu từ logs (lịch sử)
                if (item.logs) {
                    const parsedLogs = JSON.parse(item.logs);
                    const approvalRecords = item.approval_record ? JSON.parse(item.approval_record) : [];
                    
                    if (Array.isArray(parsedLogs[0])) {
                        parsedLogs.forEach((cycle, cycleIndex) => {
                            const cycleApprovalRecord = approvalRecords[cycleIndex] || {};
                            const cycleData = cycle
                            .filter(log => log.approver && log.approver.empno)
                            .map((log, index) => {
                                const recordKey = log.type;
                                const approvalRecord = cycleApprovalRecord[recordKey] || '';
                                
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
                                    cycleIndex: cycleIndex + 1, // Quan trọng: đảm bảo đây là số
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
        getReturnInfo(item) {
            if (!item.approval) return { returned: false, reason: '' };
            try {
                const approvalData = JSON.parse(item.approval);
                
                const returnedLevel = approvalData.find(level => level.status === 'returned');
                
                if (returnedLevel && returnedLevel.return_reason) {
                    let reason = returnedLevel.return_reason;
                    try {
                        const reasonObj = JSON.parse(reason);
                        const currentLang = this.$i18n?.locale || 'vi'; 
                        reason = reasonObj[currentLang] || reasonObj['vi'] || '';
                    } catch (e) {
                    }
                    return {
                        returned: true,
                        reason,
                        level: returnedLevel.level
                    };
                }
            } catch (error) {
                console.error('Error parsing approval data:', error);
            }
            return { returned: false, reason: '' };
        },
        getApprovalLevelName(type) {
            const types = {
                'dept': this.$t('Dept'),
                'div': this.$t('Div'),
                'hr_rec_sf': this.$t('Hr Rec SF'),
                'hr': this.$t('Hr'),
                'ad': this.$t('Ad'), 
                'gm': this.$t('Gm')
            };
            return types[type] || type;
        },
        
        getApprovalRecord(item, type) {
            try {
                if (!item.approval_record) return '';
                const records = JSON.parse(item.approval_record);
                return records[type] || '';
            } catch (error) {
                console.error('Error getting approval record:', error);
                return '';
            }
        },
        
        openNewRequest() {
            this.selectedItem = null; 
            this.isEdit = false;
            this.isChange = false; // Add this line
            this.appDialog = true;
        },
        
        getQuantityDetails(qtyReq) {
            try {
                const qty = typeof qtyReq === 'string' ? JSON.parse(qtyReq) : qtyReq;
                const details = [];
                if (qty.workers > 0) details.push(`${this.$t('Workers')}: ${qty.workers}`);
                if (qty.staff > 0) details.push(`${this.$t('Staff')}: ${qty.staff}`);
                if (qty.staff_lang > 0) details.push(`${this.$t('Staff Language')}: ${qty.staff_lang}`);
                if (qty.engineers > 0) details.push(`${this.$t('Engineers')}: ${qty.engineers}`);
                if (qty.leader > 0) details.push(`${this.$t('Leader')}: ${qty.leader}`);
                if (qty.tranlator > 0) details.push(`${this.$t('Translator')}: ${qty.tranlator}`);
                if (qty.level) details.push(`${this.$t('Level')}: ${qty.level}`);
                return details.join(' | ');
            } catch (e) {
                console.error('Error parsing quantity:', e);
                return '';
            }
        },
        getPositionName(posId) {
            try {
                const position = this.pos.find(p => p.id === parseInt(posId));
                if (!position || !position.type_name) return '';
                const typeNameObj = JSON.parse(position.type_name.trim());
                const locale = this.$i18n.locale || 'en';
                return typeNameObj[locale] || typeNameObj.en || '';
            } catch (error) {
                console.error('Error parsing position name:', error);
                return '';
            }
        },
        async preloadFlowData() {
            const visibleItems = this.filteredData;
            const preloadPromises = visibleItems.map(item => {
                if (!this.flowCache.has(item.id)) {
                    return this.getFlowDataForItem(item).catch(err => {
                        console.error(`Failed to preload flow data for item ${item.id}:`, err);
                    });
                }
                return Promise.resolve();
            });
            
            await Promise.all(preloadPromises);
        },
        filterToDoData(data) {
            // return data.filter(item => {
            //     return [ 'waiting_dept', 'waiting_div', 'waiting_hr_rec_sf','waiting_hr', 'waiting_ad', 'waiting_gm'].includes(item.status);
            // });
            
            return data.filter(item => {
                return this.canApproveItemForTodo(item);
            });
        },
        showRejectionDetails(item) {
            try {
                const approvalData = JSON.parse(item.approval);
                this.rejectionDetails = approvalData
                .filter(level => level.status === 'rejected' && level.reason_denied)
                .map((level, index) => ({
                    index: index + 1,
                    empno: level.approver.empno,
                    name: level.approver.name,
                    dept: level.approver.dept,
                    reason_denied: level.reason_denied,
                    processed_at: this.formatDate(level.processed_at)
                }));
                if (this.rejectionDetails.length > 0) {
                    this.rejectionDialog = true;
                }
            } catch (error) {
                console.error('Error parsing approval data:', error);
            }
        },
        isItemSelected(item) {
            if (item.change_req) return false;
            return this.selectedItem === item;
        },
        async transferCandidates() {
            if (!this.transferMprId || this.selectedCandidates.length === 0) {
                this.$notify({
                    title: this.$t('Warning'),
                    text: this.$t('Please select candidates and enter target Demand ID'),
                    type: 'warning'
                });
                return;
            }
            try {
                const response = await this.$axios.post(this.api + 'transferCandidates', {
                    fromId: this.selectIdMpr.id,
                    toId: parseInt(this.transferMprId),
                    candidates: this.selectedCandidates 
                });
                
                if (response.data.success) {
                    this.$notify({
                        title: this.$t('Success'),
                        text: this.$t('Candidates transferred successfully'),
                        type: 'success'
                    });
                    
                    // Close dialog and reset selection
                    this.candidatesDialog = false;
                    this.selectedCandidates = [];
                    this.transferMprId = '';
                    this.selectAll = false;
                    
                    // Reload data
                    await this.getAllData();
                }
            } catch (error) {
                console.error('Error transferring candidates:', error);
                this.$notify({
                    title: this.$t('Error'),
                    text: error.response?.data?.message || this.$t('Failed to transfer candidates'),
                    type: 'error'
                });
            }
        },
        toggleItemSelection(item) {
            const index = this.selectedCandidates.findIndex(selected => selected.empno === item.empno);
            if (index === -1) {
                this.selectedCandidates.push(item);
            } else {
                this.selectedCandidates.splice(index, 1);
            }
        },
        
        toggleSelectAll() {
            return;
        },
        openChangeRequest() {
            this.selectedItem = null;
            this.isEdit = false;
            this.isChange = true;
            this.appDialog = true;
        },
        async exportExcel() {
            try {
                const headers = this.originalHeaders
                .filter(header => header.value !== 'select' && header.value !== 'action')
                .map(header => ({
                    title: this.$t(header.text),
                    key: header.value
                }));
                
                const data = this.filteredData.map(item => {
                    const row = {};
                    headers.forEach(header => {
                        switch (header.key) {
                            case 'submitter':
                            row[header.title] = item.submiterInfo ? 
                            `${item.submiterInfo.name} (${item.submiterInfo.empno})` : '';
                            break;
                            case 'status':
                            row[header.title] = this.getStatusText(item.status, item);
                            if (item.status_amendent) {
                                row[header.title] += ' / ' + this.getAmendmentStatusText(item.status_amendent);
                            }
                            break;
                            case 'recruited_status':
                            const recruitmentStatus = this.getRecruitmentStatus(item);
                            row[header.title] = recruitmentStatus.show ? 
                            `${recruitmentStatus.text}${recruitmentStatus.detail ? ' ' + recruitmentStatus.detail : ''}` : '';
                            break;
                            case 'pos':
                            row[header.title] = this.getPositionName(item.pos);
                            break;
                            case 'sub_pos':
                            row[header.title] = this.getSubPositionName(item.sub_pos, true);
                            break;
                            case 'qty_req':
                            row[header.title] = this.getDisplayQuantity(item.qty_req, item);
                            break;
                            case 'date_req':
                            case 'key_in_date':
                            row[header.title] = this.formatDate(item[header.key]);
                            break;
                            default:
                            row[header.title] = item[header.key];
                        }
                    });
                    return row;
                });
                
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.json_to_sheet(data);
                const colWidths = headers.map(() => ({ wch: 20 }));
                ws['!cols'] = colWidths;
                XLSX.utils.book_append_sheet(wb, ws, 'Manpower Requests');
                const date = new Date().toISOString().split('T')[0];
                const fileName = `Manpower_Requests_${date}.xlsx`;
                XLSX.writeFile(wb, fileName);
                
                this.$notify({
                    title: this.$t('Success'),
                    text: this.$t('Export successful'),
                    type: 'success'
                });
            } catch (error) {
                console.error('Export error:', error);
                this.$notify({
                    title: this.$t('Error'),
                    text: this.$t('Export failed'),
                    type: 'error'
                });
            }
        },
        updateFilter(key, value) {
            if (value && value.length) {
                this.$set(this.filterValues, key, value);
            } else {
                this.$delete(this.filterValues, key);
            }
        },
        getLanguageLevelText(langCode, levelId) {
            try {
                const langConfig = this.lang.find(config => config.lang_code === langCode);
                if (!langConfig) return 'N/A';
                
                const levels = JSON.parse(langConfig.lang);
                
                const levelIndex = parseInt(levelId) - 1;
                if (levelIndex < 0 || levelIndex >= levels.length) return 'N/A';
                
                const level = levels[levelIndex];
                
                const locale = this.$i18n.locale || 'en';
                return level[locale] || level.en || 'N/A';
                
            } catch (error) {
                console.error('Error getting language level text:', error);
                return 'N/A';
            }
        },
        
        getLanguageRequirements(item) {
            const requirements = [];
            
            if (item.eng_skill && item.eng_skill !== '0') {
                const levelText = this.getLanguageLevelText('en', item.eng_skill);
                requirements.push({
                    type: this.$t('English'),
                    level: levelText,
                    id: item.eng_skill
                });
            }
            
            if (item.chn_skill && item.chn_skill !== '0') {
                const levelText = this.getLanguageLevelText('cn', item.chn_skill);
                requirements.push({
                    type: this.$t('Chinese'), 
                    level: levelText,
                    id: item.chn_skill
                });
            }
            
            if (item.either_skill && item.either_skill !== '0') {
                const levelText = this.getLanguageLevelText('either', item.either_skill);
                requirements.push({
                    type: this.$t('Either'),
                    level: levelText,
                    id: item.either_skill
                });
            }
            return requirements;
        },
        getComputerSkillImages(skills) {
            if (!Array.isArray(skills)) return [];
            
            const skillMap = {
                'Excel': { name: 'Excel', src: './excel.png' },
                'Word': { name: 'Word', src: './word.png' },
                'PowerPoint': { name: 'PowerPoint', src: './ppt.png' },
                'Access': { name: 'Access', src: './acc.png' },
                'Internet': { name: 'Internet', src: './www.png' }
            };
            
            return skills
            .filter(skill => skill !== 'Other')
            .filter(skill => skill in skillMap)
            .map(skill => skillMap[skill]);
        }
    },
    async mounted() {
        try {
            if (this.$session.has('mpr')) {
                this.activeUser = this.$session.get("mpr");
            } else {
                this.$router.push('/');
                return;
            }
            await this.getAllData();
            await this.fetchFlowData();
            await this.fetchData();
            // this.$nextTick(() => {
            //     this.setDefaultTab();
            // });
        }
        catch (error) {
            console.error('Error in mounted hook:', error);
        }
    },
    watch: {
        hasTodoData: {
            handler(newVal, oldVal) {
                if (oldVal !== undefined && newVal !== oldVal) {
                    if (!newVal && this.statusFilter === 1) {
                        this.statusFilter = 0; 
                    }
                }
            },
            immediate: false
        },
        generalFlowData: {
            handler(newVal) {
                if (newVal) {
                    this.$nextTick(() => {
                        this.setDefaultTab();
                    });
                }
            },
            immediate: false
        },
        selectedYears: {
            handler() {
                this.filteredItems = [];
            },
            immediate: true
        },
        
        formattedData: {
            immediate: true,
            handler(newVal) {
                this.filteredItems = [];
            },
            deep: true
        },
        
        dataMpr: {
            handler() {
                this.filteredItems = [];
            },
            immediate: true
        },
        
        // filteredData: {
        //     handler() {
        //         this.$nextTick(() => {
        //             this.preloadFlowData();
        //         });
        //     },
        //     immediate: true,
        //     deep: true
        // },
        
        statusFilter: {
            handler() {
                this.filteredItems = [];
                this.search = '';
            },
            immediate: true
        },
        selectedCandidates(newVal) {
            this.selectAll = newVal.length === this.parsedCandidates.length;
        },
        selectedItem(newVal) {
            this.selectAll = false; 
        },
        appDialog(newVal) {
            if (!newVal) { 
                this.selectedItem = null;  
                this.isEdit = false;
            }
        },
        
    },
}
</script>
<i18n>
    {
        "en": {
            "ID": "ID",
            "Submitted by": "Submitted by",
            "Apply Date": "Apply Date",
            "Unit code": "Unit code",
            "Unit name": "Unit name",
            "Position": "Position",
            "Sub position": "S.position",
            "Conditions": "Conditions",
            "Demand": "Demand",
            "Reason": "Reason",
            "Approval Status": "Approval Status",
            "Recruitment status": "Recruitment status",
            "Recruited": "Recruited",
            "Resign": "Resign",
            "Print": "Print",
            "All": "All",
            "To Do": "To Do",
            "Pending": "Pending",
            "Returned": "Returned",
            "Approved": "Approved",
            "Rejected": "Rejected",
            "Manpower listing": "Manpower request",
            "Reload": "Reload",
            "New request": "New request",
            "Amendment request": "Amendment request",
            "Total": "Total",
            "Pending Unit": "Waiting Unit",
            "Pending Dept": "Waiting department",
            "Pending Div": "Waiting Division",
            "Pending HR Rec SF": "Waiting HR Rec SF",
            "Pending HR": "Waiting HR",
            "Pending AD": "Waiting AD",
            "Pending GM": "Waiting GM",
            "Closed": "Closed",
            "Recruiting": "Recruiting",
            "Recruitment hold": "Recruitment hold",
            "hold": "hold",
            "Resumed": "Resumed",
            "#Change in hiring requirements": "#Change in hiring requirements",
            "Select year": "Select year",
            "Close": "Close",
            "Clear": "Clear",
            "English": "English",
            "Chinese": "Chinese",
            "Either": "Either",
            "Applicant": "Applicant",
            "Search": "Search",
            "Export": "Export",
            "remaining": "remaining",
            "Hold period expired": "Hold period expired",
            "days": "days",
            "Approval History":"Approval History",
            "Current Cycle":"Current Cycle",
            "Recruitment listing details":"Recruitment listing details",
            "Empno": "Empno",
            "Key in": "Key in",
            "Position": "Position",
            "Name":"Name",
            "In date":"In date",
            "Resign date":"Resign date",
            "Resign Listing Details":"Resign Listing Details",
            "Cancelled":"Cancelled"    
        },
        "vi": {
            "ID": "Mã đơn",
            "Submitted by": "Người nộp",
            "Apply Date": "Ngày nộp",
            "Unit code": "Mã đơn vị",
            "Unit name": "Tên đơn vị",
            "Position": "Vị trí",
            "Sub position": "Vị trí phụ",
            "Conditions": "Điều kiện",
            "Demand": "Nhu cầu",
            "Reason": "Lý do",
            "Approval Status": "Trạng thái",
            "Recruitment status": "Trạng thái tuyển dụng",
            "Recruited": "Đã tuyển dụng",
            "Resign": "Đã nghỉ việc",
            "Print": "In",
            "All": "Tất cả",
            "To Do": "Cần làm",
            "Pending": "Chờ duyệt",
            "Returned": "Trả lại người đề nghị",
            "Approved": "Đã duyệt",
            "Rejected": "Từ chối",
            "Manpower listing": "Đơn xin bổ sung công nhân nhân viên",
            "Reload": "Tải lại dữ liệu",
            "New request": "Yêu cầu mới",
            "Amendment request": "Yêu cầu sửa đổi",
            "Total": "Tổng cộng",
            "Pending Unit": "Đang chờ đơn vị phê duyệt",
            "Pending Dept": "Đang chờ bộ phận phê duyệt",
            "Pending Div": "Đang chờ cấp sở phê duyệt",
            "Pending HR Rec SF": "Đang chờ nhân viên HR phê duyệt",
            "Pending HR": "Đang chờ chủ quản HR phê duyệt",
            "Pending AD": "Đang chờ chủ quản AD phê duyệt",
            "Pending GM": "Đang chờ phó tổng phê duyệt",
            "Closed": "Đã đóng",
            "Recruiting": "Đang tuyển dụng",
            "Recruitment hold": "Tạm dừng tuyển dụng",
            "hold": "tạm dừng",
            "Resumed": "Đã tiếp tục",
            "#Change in hiring requirements": "#Thay đổi yêu cầu tuyển dụng",
            "Select year": "Chọn năm",
            "Close": "Đóng",
            "Clear": "Xóa",
            "English": "Tiếng Anh",
            "Chinese": "Tiếng Trung",
            "Either": "NN.Bất kỳ",
            "Applicant": "Người nộp đơn",
            "Search": "Tìm kiếm",
            "Export": "Xuất Excel",
            "remaining": "còn lại",
            "Hold period expired": "Đã hết thời gian tạm dừng",
            "days": "ngày",
            "Approval History":"Lịch sử phê duyệt",
            "Current Cycle":"Chu kỳ hiện tại",
            "Recruitment listing details":"Chi tiết danh sách tuyển dụng",
            "Empno": "Mã số thẻ",
            "Key in": "Ngày nộp hồ sơ",
            "Name":"Họ tên",
            "In date":"Ngày tuyển dụng",     
            "Resign date":"Ngày thôi việc",
            "Resign Listing Details":"Chi tiết danh sách nghỉ việc",
            "Cancelled":"Đã hủy"
        },
        "cn": {
            "ID": "表單編號",
            "Submitted by": "提交者",
            "Apply Date": "申請日期",
            "Unit code": "單位代號",
            "Unit name": "單位名稱",
            "Position": "職位",
            "Sub position": "子職位",
            "Conditions": "招募條件",
            "Demand": "需求",
            "Reason": "需求原因",
            "Approval Status": "核准狀態",
            "Recruitment status": "招募狀態",
            "Recruited": "入職人數",
            "Resign": "離職人數",
            "Print": "列印",
            "All": "所有",
            "To Do": "待處理",
            "Pending": "簽核中",
            "Returned": "退回申請人",
            "Approved": "已核准",
            "Rejected": "否決",
            "Manpower listing": "人員增補申請",
            "Reload": "重新載入資料",
            "New request": "新需求",
            "Amendment request": "需求變更申請",
            "Total": "總計",
            "Pending Unit": "待單位核准",
            "Pending Dept": "等部門主管",
            "Pending Div": "等處級主管",
            "Pending HR Rec SF": "待人力資源核准",
            "Pending HR": "等HR",
            "Pending AD": "等AD",
            "Pending GM": "等GM",
            "Closed": "結案",
            "Recruiting": "招募中",
            "Recruitment hold": "暫停招募",
            "hold": "暫停",
            "Resumed": "恢復",
            "#Change in hiring requirements": "#招聘要求變更",
            "Select year": "選擇年份",
            "Close": "關閉",
            "Clear": "清除",
            "English": "英語",
            "Chinese": "中文",
            "Either": "都可",
            "Applicant": "申請人",
            "Search": "搜索",
            "Export": "導出",
            "remaining": "剩餘天數",
            "Hold period expired": "暂停期已过",
            "days": "天",
            "Approval History":"审批历史",
            "Current Cycle":"当前周期",
            "Recruitment listing details":"招聘列表详情",
            "Empno": "卡号",
            "Key in": "提交日期",
            "Name": "姓名",
            "In date": "入职日期",
            "Resign date": "离职日期",
            "Resign Listing Details": "离职列表详情",
            "Cancelled": "已取消"
        }
    }  
</i18n>     
<style scoped>
.data-table {
    border-radius: 8px;
    overflow: hidden;
}

.v-data-table {
    border: 1px solid #e0e0e0;
}
.v-data-table ::v-deep tbody tr:not(.total-row):hover {
    background-color: #f3fd99 !important;
}

.total-row {
    background-color: #00838F !important;
}

.total-row td {
    color: white !important;
    font-size: 1.1em !important;
    border-top: 2px solid #00838F !important;
    border-bottom: none !important;
}

.text-caption {
    font-size: 0.75rem !important;
}

.v-menu {
    display: inline-block;
}

.tooltip-content {
    white-space: pre-line;
    text-align: left;
}

.skill-icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

.skill-icon:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}
.v-data-table ::v-deep thead th {
    font-weight: 600 !important;
    color: #00838F !important;
    background: white;
    position: sticky;
    top: 0;
    z-index: 1;
    white-space: nowrap; /* Ngăn header xuống dòng */
}
</style>
