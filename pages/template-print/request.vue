<template>
    <div>
        <div ref="printContent">
            <table class="table table-bordered no-bottom-border" style="margin-bottom: 0">
                <tr>
                    <td rowspan="2" class="align-middle text-header" style="width: 12%">
                        <img width="75px;" :src="`./logo.png`" class="img-fluid mx-auto mt-1" alt="" ref="logoImage" />
                    </td>
                    <td rowspan="3" class="text-center align-middle text-header_title">
                        ĐƠN XIN BỔ SUNG CÔNG NHÂN - NHÂN VIÊN
                        <br />
                        人員增補申請單
                    </td>
                    <td>
                        Ngày thiết lập (sửa đổi) <br />
                        (修)訂日期
                    </td>
                    <td>25.02.2025</td>
                </tr>
                <tr>
                    <td>
                        Mã số tài liệu <br />
                        文件編號
                    </td>
                    <td>
                        ADHR02-SP001-01<br />
                    </td>
                </tr>
                <tr>
                    <td class="colum3 align-middle">
                        Bộ phận soạn thảo <br />
                        編訂部門 <br />
                        Tài Nguyên Nhân Lực <br />
                        人力資源部 <br />
                    </td>
                    <td class="colum3 align-middle">
                        Phiên bản 版本<br />
                        8
                    </td>
                    <td class="colum3 align-middle">
                        Trang 頁次 <br />
                        1/1
                    </td>
                </tr>
            </table>
            <table class="table" style="margin-top: 20px;">
                <tr>
                    <td style="text-align: left; width: 33.33%; border: none;"><strong>ĐƠN VỊ YÊU CẦU</br>申請單位:</strong>
                        <span style="margin-left: 10px">{{ formData?.submitter?.dept || '' }}</span></td>
                        <td style="text-align: left; width: 33.33%; border: none;"><strong>NGÀY THÁNG YÊU
                            CẦU</br>申請日期:</strong> <span style="margin-left: 10px">{{ formData?.key_in_date || ''
                            }}</span></td>
                            <td style="text-align: left; width: 33.33%; border: none;"><strong>MÃ SỐ ĐƠN</br>申請單流水號:</strong>
                                <span style="margin-left: 10px">{{ formData?.req_id || '' }}</span></td>
                            </tr>
                        </table>
                        <table class="table table-bordered" style="margin-top: 10px;">
                            <tr>
                                <td colspan="4" style="text-align: left;"><strong>I- YÊU CẦU CHUNG (增補內容)</strong></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: left;"><strong>1/ Điều kiện cụ thể (增補條件)</strong></td>
                            </tr>
                            <tr>
                                <td style="text-align: left; vertical-align: top; width: 33.33%;"><strong>Trình độ học vấn (教育程度):</strong>
                                    <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                                        <label><input type="checkbox" readonly @click.prevent ref="eduTHCS" style="margin: 5px;">Dưới 12/12 (高中以下)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="eduPTTH" style="margin: 5px;">12/12 (高中)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="eduTC" style="margin: 5px;"> Trung cấp (中專)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="eduCD" style="margin: 5px;">Cao đẳng (大專)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="eduDH" style="margin: 5px;">Đại học (大學)</label>
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: top;  width: 33.33%;"><strong>Chuyên môn (專業背景):</strong>
                                    <div style="text-align: left; margin-top: 10px; margin-left: 10px;">
                                        {{ formData?.spec || '' }}
                                    </div>
                                </td>
                                <td colspan="2" style="text-align: left;  width: 33.33%;"><strong>Kinh nghiệm làm việc (經驗):</strong>
                                    <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px; ">
                                        <label><input type="checkbox" readonly @click.prevent ref="expNo" style="margin: 5px;">Không cần (不需經驗)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="expbl1" style="margin: 5px;"> Dưới 1 năm (一年以下)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="exp12" style="margin: 5px;">Từ 1 đến dưới 2 năm (一年至二年以下)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="exp35" style="margin: 5px;">Từ 2 đến dưới 3 năm (二年至 三年以下)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="exp5" style="margin: 5px;">Trên 3 năm (三年以上)</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: left;"><strong>2/ Nguyên nhân tuyển dụng (增補資訊)</strong></td>
                            </tr>
                            <tr>
                                <td style="text-align: left; width: 25%;"><strong>Nguyên nhân (原因)</strong>
                                    <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                                        <label><input type="checkbox" readonly @click.prevent ref="reasonExpand" style="margin: 5px;">Mở rộng biên chế (擴大編制)</label>
                                        <label><input type="checkbox" readonly @click.prevent ref="reasonReplace" style="margin: 5px;">Bổ sung thôi việc (離職補充)</label>
                                        <label>MST(工號):&nbsp;<input type="text" ref="oldEmpNo"
                                            style="width: 300px; margin: 5px; border-bottom: 1px solid black;"></label>
                                            <label>
                                                <input 
                                                type="checkbox" 
                                                readonly 
                                                @click.prevent 
                                                ref="reasonOther" 
                                                style="margin: 5px;"
                                                >Nguyên nhân khác (其他原因):_____ 
                                                {{ getLocalizedOtherReason(formData?.isOther_reason) }} 
                                                _____
                                            </label>
                                        </div>
                                    </td>
                                    <td style="text-align: center; vertical-align: top; width: 25%;"><strong>Ngày yêu cầu công nhân – nhân viên đến </br> (希望報到日期)</strong>
                                        <div style="margin-top: 10px;">
                                            {{ formData?.date_req || '' }}
                                        </div>
                                    </td>
                                    <td colspan="2" style="text-align: left; width: 25%;"><strong>Số lượng cần bổ sung (增補總數)</strong>
                                        <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                                            <label><input type="text" ref="workers" style="width: 100px; text-align: center; border-bottom: 1px solid black;">名 Công nhân
                                                (工人) </label>
                                                <label><input type="text" ref="staff" style="width: 100px;  text-align: center; border-bottom: 1px solid black;">名 Nhân viên
                                                    (課員) </label>
                                                    <label><input type="text" ref="leader" style="width: 100px;  text-align: center; border-bottom: 1px solid black;">名 Cán bộ
                                                        (幹部) bậc 職級:<input type="text" ref="level"
                                                        style="width: 250px; text-align: center; border-bottom: 1px solid black;"></label>
                                                        <label><input type="text" ref="engineers" style="width: 100px;  text-align: center; border-bottom: 1px solid black;">名 Kỹ sư (工程師)</label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" style="text-align: left;"><strong>II- MÔ TẢ CÔNG VIỆC(職務說明書):</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align: left;"><strong>Mô tả công việc cơ bản (基本職務說明)</strong>
                                                    <div style="margin-top: 10px; margin-left: 10px;">
                                                        {{ formData?.jd || '' }}
                                                    </div>
                                                </td>
                                                <td colspan="2" style="text-align: left;"><strong>Đính kèm bảng mô tả công việc</br>附職務說明書</strong>
                                                    <div style="display: flex; justify-content: space-evenly; gap: 5px; margin-top: 5px;">
                                                        <label><input type="checkbox" readonly @click.prevent ref="hasAttachment"> Có (有)</label>
                                                        <label><input type="checkbox" readonly @click.prevent ref="noAttachment"> Không (無)</label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" style="text-align: left;"><strong>III- YÊU CẦU VỀ KỸ NĂNG (技能要求)</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" style="text-align: left;"><strong>1/ Trình độ ngoại ngữ (語言能力):</strong>
                                                    <div style="display: flex; margin-top: 5px; text-align: left; margin-left: 20px; gap: 15px;">
                                                        <label> + Tiếng Anh (英文):</label>
                                                        <label style="margin-left: 10px;">
                                                            <input type="checkbox" ref="engHigh" readonly @click.prevent 
                                                            style="margin-right: 5px;"> Cao 高</label>
                                                            <label>
                                                                <input type="checkbox" readonly @click.prevent ref="engMed"
                                                                style="margin-right: 5px;"> Trung 中</label>
                                                                <label>
                                                                    <input type="checkbox" readonly @click.prevent ref="engLow"
                                                                    style="margin-right: 5px;"> Thấp 低</label>
                                                                    <label>
                                                                        <input type="checkbox" readonly @click.prevent ref="engNo"
                                                                        style="margin-right: 5px;"> Không 不需要</label>
                                                                    </div>
                                                                    <div style="display: flex; margin-top: 5px; text-align: left; margin-left: 20px; gap: 15px;">
                                                                        <label> + Tiếng Hoa (中文):</label>
                                                                        <label style="margin-left: 10px;">
                                                                            <input type="checkbox" ref="chnHigh" readonly @click.prevent
                                                                            style="margin-right: 5px;"> Cao 高</label>
                                                                            <label>
                                                                                <input type="checkbox" readonly @click.prevent ref="chnMed"
                                                                                style="margin-right: 5px;"> Trung 中</label>
                                                                                <label>
                                                                                    <input type="checkbox" readonly @click.prevent ref="chnLow"
                                                                                    style="margin-right: 5px;"> Thấp 低</label>
                                                                                    <label>
                                                                                        <input type="checkbox" readonly @click.prevent ref="chnNo"
                                                                                        style="margin-right: 5px;"> Không 不需要</label>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td colspan="4" style="text-align: left;"><strong>2/ Trình độ vi tính (電腦技能):</strong>
                                                                                    <div
                                                                                    style="display: flex; justify-content: space-evenly; gap: 5px; margin-top: 10px; margin-left: 10px;">
                                                                                    <label><input type="checkbox" readonly @click.prevent ref="comp_Word"> Word</label>
                                                                                    <label><input type="checkbox" readonly @click.prevent ref="comp_Access"> Access</label>
                                                                                    <label><input type="checkbox" readonly @click.prevent ref="comp_Internet"> Internet</label>
                                                                                    <label><input type="checkbox" readonly @click.prevent ref="comp_Excel"> Excel</label>
                                                                                    <label><input type="checkbox" readonly @click.prevent ref="comp_PowerPoint"> Power Point</label>
                                                                                    <label> Khác (其他):<input type="text" ref="compOther" style="width: 100px;"></label>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="4" style="text-align: left;">
                                                                                <strong>3/ An toàn lao động 職場安全衛生知識:</strong>
                                                                                <div
                                                                                style="display: flex; justify-content: space-evenly; gap: 5px; margin-top: 10px; margin-left: 10px;">
                                                                                <label><input type="checkbox" readonly @click.prevent ref="wpsBasic"> Cơ bản 可</label>
                                                                                <label><input type="checkbox" readonly @click.prevent ref="wpsIntermediate"> Khá 好</label>
                                                                                <label><input type="checkbox" readonly @click.prevent ref="wpsAdvanced"> Tốt 優</label>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    
                                                                </table>
                                                                <table class=" table no-top-border">
                                                                    <tr>
                                                                        <td style="text-align: center; width: 25%;"><strong>Chủ Quản Cấp Sở <br> 處級主管</strong></td>
                                                                        <td style="text-align: center; width: 25%;"><strong>Chủ Quản Bộ Phận <br> 部級主管</strong></td>
                                                                        <td style="text-align: center; width: 25%;"><strong>Chủ Quản Đơn Vị <br> 單位主管</strong></td>
                                                                        <td colspan="1" style="text-align: center; width: 25%;"><strong>Người Điền Biểu <br> 填表人</strong>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="text-align: center; height: 100px;">
                                                                            <template v-if="getApprover(2)">
                                                                                {{ getApprover(2).empno }} - {{ getApprover(2).name }}
                                                                                <div v-if="getApprovalDate(2)">{{ getApprovalDate(2) }}</div>
                                                                            </template>
                                                                        </td>
                                                                        <td style="text-align: center; height: 100px;">
                                                                            <template v-if="getApprover(1)">
                                                                                {{ getApprover(1).empno }} - {{ getApprover(1).name }}
                                                                                <div v-if="getApprovalDate(1)">{{ getApprovalDate(1) }}</div>
                                                                            </template>
                                                                        </td>
                                                                        <td style="text-align: center; height: 100px;">
                                                                            <!-- <template v-if="getApprover(1)">
                                                                                {{ getApprover(1).empno }} - {{ getApprover(1).name }}
                                                                                <div v-if="getApprovalDate(1)">{{ getApprovalDate(1) }}</div>
                                                                            </template> -->
                                                                        </td>
                                                                        <td style="text-align: center; height: 100px;">
                                                                            <template v-if="formData?.submitter">
                                                                                {{ formData.submitter.empno }} - {{ formData.submitter.name }}
                                                                                <div v-if="formData?.key_in_date">{{ formData.key_in_date }}</div>
                                                                            </template>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                                <table class=" table no-top-border">
                                                                    <tr>
                                                                        <td style="text-align: center; width: 33.33%;"><strong>Văn Phòng TGĐ <br> 總經理室</strong></td>
                                                                        <td style="text-align: center; width: 33.33%;"><strong>Khu Quản Lý <br> 管理處</strong></td>
                                                                        <td style="text-align: center; width: 33.33%;"><strong>BP. Tài Nguyên Nhân Lực <br>
                                                                            人力資源部審核與建議</strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="text-align: center; height: 100px;">
                                                                                <template v-if="getApprover(6)">
                                                                                    <span v-if="getApprover(6).empno && getApprover(6).name">
                                                                                        {{ getApprover(6).empno }} - {{ getApprover(6).name }}
                                                                                    </span>
                                                                                    <span v-else>
                                                                                        {{ getApprover(6).empno || getApprover(6).name || '' }}
                                                                                    </span>
                                                                                    <div v-if="getApprovalDate(6)">{{ getApprovalDate(6) }}</div>
                                                                                </template>
                                                                            </td>
                                                                            <td style="text-align: center; height: 100px;">
                                                                                <template v-if="getApprover(5)">
                                                                                    <span v-if="getApprover(5).empno && getApprover(5).name">
                                                                                        {{ getApprover(5).empno }} - {{ getApprover(5).name }}
                                                                                    </span>
                                                                                    <span v-else>
                                                                                        {{ getApprover(5).empno || getApprover(5).name || '' }}
                                                                                    </span>
                                                                                    <div v-if="getApprovalDate(5)">{{ getApprovalDate(5) }}</div>
                                                                                </template>
                                                                            </td>
                                                                            <td style="text-align: center; height: 100px;">
                                                                                <template v-if="getApprover(4)">
                                                                                    <span v-if="getApprover(4).empno && getApprover(4).name">
                                                                                        {{ getApprover(4).empno }} - {{ getApprover(4).name }}
                                                                                    </span>
                                                                                    <span v-else>
                                                                                        {{ getApprover(4).empno || getApprover(4).name || '' }}
                                                                                    </span>
                                                                                    <div v-if="getApprovalDate(4)">{{ getApprovalDate(4) }}</div>
                                                                                </template>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <table class="signature-table">
                                                                        <tr>
                                                                            <td colspan="2" class="signature-note" style="font-weight: bold;">
                                                                                Lưu trình trình ký/呈簽流程:<br>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="2" class="signature-note">
                                                                                - Người Điền Biểu ➜ Chủ Quản Đơn Vị ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR  (Áp dụng đối với CNV, cán bộ Việt Nam cấp Trưởng ngành trở xuống)<br>
                                                                                填表人 ➜ 單位主管 ➜ 部級主管 ➜ 處級主管 ➜ 人力資源部 (適用於越籍課長級(含)以下幹部/職員/工人)
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="2" class="signature-note">
                                                                                - Người Điền Biểu ➜ Chủ Quản Đơn Vị ➜ Chủ quản bộ phận ➜ Chủ Quản Cấp Sở ➜ Bộ phận ADHR ➜ Khu quản lý ➜ Văn Phòng TGĐ (Áp dụng đối với cán bộ Việt Nam cấp Trưởng ngành thâm niên trở lên)<br>
                                                                                填表人 ➜ 單位主管 ➜ 部級主管 ➜ 處級主管 ➜ 人力資源部 ➜ 管理處 ➜ 總經理室 (適用於越籍資深課長級(含)以上幹部)
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="2" class="signature-note">
                                                                                - Sau khi hoàn thành trình ký, nộp cho bộ phận ADHR lưu giữ<br>
                                                                                簽核完成後，由人力資源部留存
                                                                                
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        
                                                        <script>
                                                        export default {
                                                            layout: "print",
                                                            data() {
                                                                return {
                                                                    api: 'http://gmo021.cansportsvg.com/api/vg-mpr-temp/',
                                                                    formData: null,
                                                                    duty:[]
                                                                };
                                                            },
                                                            async created() {
                                                                await this.getDataModel();
                                                                const id = this.$route.query.id;
                                                                
                                                                if (id) {
                                                                    try {
                                                                        const response = await this.$axios.get(`${this.api}getMprById/${id}`);
                                                                        if (response.data && response.data.success) {
                                                                            const data = response.data.data;
                                                                            let qtyReqData;
                                                                            try {
                                                                                qtyReqData = data.qty_req ? JSON.parse(data.qty_req) : {};
                                                                            } catch (e) {
                                                                                console.error("Error parsing qty_req:", e);
                                                                                qtyReqData = {};
                                                                            }
                                                                            
                                                                            // Parse deny_temp for recruited value
                                                                            let denyTempData = null;
                                                                            try {
                                                                                denyTempData = data.deny_temp ? JSON.parse(data.deny_temp) : null;
                                                                            } catch (e) {
                                                                                console.error("Error parsing deny_temp:", e);
                                                                                denyTempData = null;
                                                                            }
                                                                            
                                                                            if (data.req_id_change) {
                                                                                let compSkill = data.comp_skill ? JSON.parse(data.comp_skill || '{}') : {};
                                                                                if (data.computerSkills_change) {
                                                                                    try {
                                                                                        let computerChanges = JSON.parse(data.computerSkills_change);
                                                                                        if (Array.isArray(computerChanges) && computerChanges.length > 0) {
                                                                                            // Get the last change
                                                                                            compSkill = computerChanges[computerChanges.length - 1];
                                                                                        }
                                                                                    } catch (e) {
                                                                                        console.error("Error parsing computerSkills_change:", e);
                                                                                    }
                                                                                }
                                                                                
                                                                                // For language skills
                                                                                let eitherSkill = data.either_skill;
                                                                                if (data.either_change) {
                                                                                    try {
                                                                                        // Parse either_change properly
                                                                                        if (data.either_change.includes('[')) {
                                                                                            // It's a JSON string array
                                                                                            eitherSkill = JSON.parse(data.either_change);
                                                                                        } else {
                                                                                            // It's a single value
                                                                                            eitherSkill = data.either_change;
                                                                                        }
                                                                                    } catch (e) {
                                                                                        console.error("Error parsing either_change:", e);
                                                                                    }
                                                                                }
                                                                                
                                                                                let engSkill = data.eng_skill;
                                                                                if (data.english_change) {
                                                                                    try {
                                                                                        // Parse english_change properly
                                                                                        if (data.english_change.includes('[')) {
                                                                                            // It's a JSON string array
                                                                                            engSkill = JSON.parse(data.english_change);
                                                                                        } else {
                                                                                            // It's a single value
                                                                                            engSkill = data.english_change;
                                                                                        }
                                                                                    } catch (e) {
                                                                                        console.error("Error parsing english_change:", e);
                                                                                    }
                                                                                }
                                                                                
                                                                                let chnSkill = data.chn_skill;
                                                                                if (data.chinese_change) {
                                                                                    try {
                                                                                        // Parse chinese_change properly
                                                                                        if (data.chinese_change.includes('[')) {
                                                                                            // It's a JSON string array
                                                                                            chnSkill = JSON.parse(data.chinese_change);
                                                                                        } else {
                                                                                            // It's a single value
                                                                                            chnSkill = data.chinese_change;
                                                                                        }
                                                                                    } catch (e) {
                                                                                        console.error("Error parsing chinese_change:", e);
                                                                                    }
                                                                                }
                                                                                
                                                                                // Process quantity changes
                                                                                if (data.change_req) {
                                                                                    try {
                                                                                        const changeReq = JSON.parse(data.change_req);
                                                                                        if (Array.isArray(changeReq) && changeReq.length > 0) {
                                                                                            // Get the last quantity change
                                                                                            const lastChange = changeReq[changeReq.length - 1];
                                                                                            if (lastChange.change_qty) {
                                                                                                // Find which key in qtyReqData has a non-zero value
                                                                                                Object.keys(qtyReqData).forEach(key => {
                                                                                                    if (qtyReqData[key] > 0) {
                                                                                                        qtyReqData[key] = parseInt(lastChange.change_qty);
                                                                                                    }
                                                                                                });
                                                                                            }
                                                                                        }
                                                                                    } catch (e) {
                                                                                        console.error("Error parsing change_req:", e);
                                                                                    }
                                                                                }
                                                                                
                                                                                // Format data with possibly changed values
                                                                                this.formData = {
                                                                                    submitter: JSON.parse(data.submitter || '{}'),
                                                                                    education: JSON.parse(data.edu || '{}'),
                                                                                    qtyReq: qtyReqData,
                                                                                    engSkill: engSkill ? JSON.parse(engSkill || '[]') : null,
                                                                                    chnSkill: chnSkill ? JSON.parse(chnSkill || '[]') : null,
                                                                                    either_skill: eitherSkill,
                                                                                    workExp: JSON.parse(data.w_exp || '{}'),
                                                                                    reasonExp: data.reason_exp,
                                                                                    compSkill: compSkill,
                                                                                    positionInfo: JSON.parse(data.pos || '{}'),
                                                                                    approval: JSON.parse(data.approval || '[]'),
                                                                                    req_id: data.req_id,
                                                                                    key_in_date: data.key_in_date,
                                                                                    date_req: data.date_req,
                                                                                    spec: data.spec,
                                                                                    jd: data.jd,
                                                                                    att_jd: data.att_jd,
                                                                                    att_dis: data.att_dis,
                                                                                    wps: data.wps,
                                                                                    old_emp: data.old_emp,
                                                                                    raw_qty_req: data.qty_req,
                                                                                    isOther_reason: data.isOther_reason,
                                                                                    isChangeRequest: !!data.req_id_change,
                                                                                    status_amendent: data.status_amendent,
                                                                                    denyTempData: denyTempData,
                                                                                    recruited: data.recruited
                                                                                };
                                                                            } else {
                                                                                // No change request, use original data
                                                                                this.formData = {
                                                                                    submitter: JSON.parse(data.submitter || '{}'),
                                                                                    education: JSON.parse(data.edu || '{}'),
                                                                                    qtyReq: qtyReqData,
                                                                                    engSkill: JSON.parse(data.eng_skill || '[]'),
                                                                                    chnSkill: JSON.parse(data.chn_skill || '[]'),
                                                                                    either_skill: data.either_skill,
                                                                                    workExp: JSON.parse(data.w_exp || '{}'),
                                                                                    reasonExp: data.reason_exp,
                                                                                    compSkill: JSON.parse(data.comp_skill || '{}'),
                                                                                    positionInfo: JSON.parse(data.pos || '{}'),
                                                                                    approval: JSON.parse(data.approval || '[]'),
                                                                                    req_id: data.req_id,
                                                                                    key_in_date: data.key_in_date,
                                                                                    date_req: data.date_req,
                                                                                    spec: data.spec,
                                                                                    jd: data.jd,
                                                                                    att_jd: data.att_jd,
                                                                                    att_dis: data.att_dis,
                                                                                    wps: data.wps,
                                                                                    old_emp: data.old_emp,
                                                                                    raw_qty_req: data.qty_req,
                                                                                    isOther_reason: data.isOther_reason,
                                                                                    isChangeRequest: false,
                                                                                    status_amendent: data.status_amendent,
                                                                                    denyTempData: denyTempData,
                                                                                    recruited: data.recruited
                                                                                };
                                                                            }
                                                                        }
                                                                    } catch (error) {
                                                                        console.error('Error fetching print data:', error);
                                                                    }
                                                                }
                                                            },
                                                            watch: {
                                                                formData: {
                                                                    handler(newVal) {
                                                                        if (newVal) {
                                                                            this.$nextTick(() => {
                                                                                this.updateFormFields();
                                                                            });
                                                                        }
                                                                    },
                                                                    immediate: true
                                                                }
                                                            },
                                                            
                                                            mounted() {
                                                                this.$nextTick(() => {
                                                                    if (this.formData) {
                                                                        this.updateFormFields();
                                                                    }
                                                                });
                                                            },
                                                            methods: {
                                                                async getDataModel() {
                                                                    let params = {
                                                                        loc: 'vg'
                                                                    };
                                                                    try {
                                                                        const response = await this.$axios.post(this.api + "getDataModel", params);
                                                                        if (response.data) {
                                                                            const dutyData = response.data.dataDutyHrm || [];
                                                                            this.duty = dutyData;
                                                                            console.log('Fetched duty data:', this.duty);
                                                                            return dutyData;
                                                                        } else {
                                                                            console.error('API response missing data');
                                                                            this.duty = [];
                                                                            return [];
                                                                        }
                                                                    } catch (error) {
                                                                        console.error('Error fetching duty data:', error);
                                                                        if (error.response) {
                                                                            console.error('API error details:', error.response.data);
                                                                        }
                                                                        this.duty = [];
                                                                        return [];
                                                                    }
                                                                },
                                                                getLocalizedOtherReason(reason) {
                                                                    try {
                                                                        if (!reason) return '';
                                                                        
                                                                        // Nếu reason là string JSON
                                                                        if (typeof reason === 'string' && reason.startsWith('{')) {
                                                                            const parsedReason = JSON.parse(reason);
                                                                            return `${parsedReason.vi}/${parsedReason.cn}`; // Hiển thị cả tiếng Việt và tiếng Trung
                                                                        }
                                                                        
                                                                        // Nếu reason đã là object
                                                                        if (typeof reason === 'object' && reason !== null) {
                                                                            return `${reason.vi}/${reason.cn}`; // Hiển thị cả tiếng Việt và tiếng Trung
                                                                        }
                                                                        
                                                                        // Nếu là string thường
                                                                        return reason;
                                                                    } catch (error) {
                                                                        console.error('Error parsing other reason:', error);
                                                                        return reason || '';
                                                                    }
                                                                },
                                                                getLevelName(dutyNo) {
                                                                    if (!dutyNo || !this.duty) return '';
                                                                    
                                                                    const dutyItem = this.duty.find(d => d.duty_no === dutyNo);
                                                                    if (!dutyItem) return dutyNo;
                                                                    
                                                                    try {
                                                                        const dutyName = typeof dutyItem.duty_name === 'string' 
                                                                        ? JSON.parse(dutyItem.duty_name) 
                                                                        : dutyItem.duty_name;
                                                                        
                                                                        // Get vi and cn values
                                                                        let viName = dutyName.vi || '';
                                                                        let cnName = dutyName.cn || '';
                                                                        
                                                                        // Handle VG prefix for Vietnamese name
                                                                        if (viName.startsWith('VG')) {
                                                                            const parts = viName.split(' ');
                                                                            if (parts.length > 2) {
                                                                                viName = parts.slice(2).join(' '); 
                                                                            }
                                                                        }
                                                                        
                                                                        // Handle VG prefix for Chinese name
                                                                        if (cnName.startsWith('VG')) {
                                                                            const parts = cnName.split(' ');
                                                                            if (parts.length > 2) {
                                                                                cnName = parts.slice(2).join(' ');
                                                                            }
                                                                        }
                                                                        
                                                                        // Return formatted string
                                                                        return `${viName}/${cnName}`; 
                                                                        
                                                                    } catch (e) {
                                                                        console.error('Error parsing duty name:', e);
                                                                        return dutyNo;
                                                                    }
                                                                },
                                                                updateFormFields() {
                                                                    if (!this.formData) return;
                                                                    
                                                                    // Education level
                                                                    if (this.formData.education) {
                                                                        const eduLevel = parseInt(this.formData.education);
                                                                        if (eduLevel === 1)  this.$refs.eduTHCS.checked = true;
                                                                        else if (eduLevel === 2) this.$refs.eduPTTH.checked = true;
                                                                        else if (eduLevel === 3) this.$refs.eduTC.checked = true;
                                                                        else if (eduLevel === 4) this.$refs.eduCD.checked = true;
                                                                        else if (eduLevel === 5) this.$refs.eduDH.checked = true;
                                                                    }
                                                                    
                                                                    // Work experience
                                                                    if (this.formData.workExp) {
                                                                        const expLevel = parseInt(this.formData.workExp);
                                                                        if (expLevel === 1) this.$refs.expNo.checked = true;
                                                                        else if (expLevel === 2) this.$refs.expbl1.checked = true;
                                                                        else if (expLevel === 3) this.$refs.exp12.checked = true;
                                                                        else if (expLevel === 4) this.$refs.exp35.checked = true;
                                                                        else if (expLevel === 5) this.$refs.exp5.checked = true;
                                                                    }
                                                                    
                                                                    // Reason for expansion
                                                                    if (this.formData.reasonExp) {
                                                                        const reasonId = parseInt(this.formData.reasonExp);
                                                                        if (reasonId === 1) this.$refs.reasonExpand.checked = true;
                                                                        if (reasonId === 2) this.$refs.reasonReplace.checked = true;
                                                                        if (reasonId === 3) this.$refs.reasonOther.checked = true;
                                                                        
                                                                        // Old employee number
                                                                        if (this.formData.old_emp) {
                                                                            this.$refs.oldEmpNo.value = this.formData.old_emp;
                                                                        }
                                                                    }
                                                                    
                                                                    // Quantity required - handle recruited value when status_amendent is completed
                                                                    const shouldUseRecruited = this.formData.status_amendent === 'completed' && 
                                                                    this.formData.denyTempData && 
                                                                    this.formData.denyTempData.recruited !== undefined;
                                                                    
                                                                    if (shouldUseRecruited) {
                                                                        // Use recruited value for all quantity fields
                                                                        const recruitedValue = this.formData.recruited || this.formData.denyTempData.recruited;
                                                                        
                                                                        // Find which type of position was originally requested and set that field
                                                                        if (this.formData.qtyReq) {
                                                                            if (this.formData.qtyReq.workers && this.formData.qtyReq.workers > 0) {
                                                                                this.$refs.workers.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq.staff && this.formData.qtyReq.staff > 0) {
                                                                                this.$refs.staff.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq.staff_lang && this.formData.qtyReq.staff_lang > 0) {
                                                                                this.$refs.staff.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq.tranlator && this.formData.qtyReq.tranlator > 0) {
                                                                                this.$refs.staff.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq.leader && this.formData.qtyReq.leader > 0) {
                                                                                this.$refs.leader.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq.engineers && this.formData.qtyReq.engineers > 0) {
                                                                                this.$refs.engineers.value = recruitedValue;
                                                                            }
                                                                            if (this.formData.qtyReq && this.formData.qtyReq.level) {
                                                                                const levelDutyNo = this.formData.qtyReq.level;
                                                                                const levelName = this.getLevelName(levelDutyNo);
                                                                                this.$refs.level.value = levelName;
                                                                            }
                                                                        }
                                                                    } else {
                                                                        // Use original logic for quantity fields
                                                                        if (this.formData.qtyReq) {
                                                                            if (this.formData.qtyReq.workers && this.formData.qtyReq.workers > 0) {
                                                                                this.$refs.workers.value = this.formData.qtyReq.workers;
                                                                            }
                                                                            if (this.formData.qtyReq.staff && this.formData.qtyReq.staff > 0) {
                                                                                this.$refs.staff.value = this.formData.qtyReq.staff;
                                                                            }
                                                                            if (this.formData.qtyReq.staff_lang && this.formData.qtyReq.staff_lang > 0) {
                                                                                this.$refs.staff.value = this.formData.qtyReq.staff_lang;
                                                                            }
                                                                            if (this.formData.qtyReq.tranlator && this.formData.qtyReq.tranlator > 0) {
                                                                                this.$refs.staff.value = this.formData.qtyReq.tranlator;
                                                                            }
                                                                            if (this.formData.qtyReq.leader && this.formData.qtyReq.leader > 0) {
                                                                                this.$refs.leader.value = this.formData.qtyReq.leader;
                                                                            }
                                                                            if (this.formData.qtyReq.engineers && this.formData.qtyReq.engineers > 0) {
                                                                                this.$refs.engineers.value = this.formData.qtyReq.engineers;
                                                                            }
                                                                            if (this.formData.qtyReq && this.formData.qtyReq.level) {
                                                                                const levelDutyNo = this.formData.qtyReq.level;
                                                                                const levelName = this.getLevelName(levelDutyNo);
                                                                                this.$refs.level.value = levelName;
                                                                            }
                                                                        }
                                                                    }
                                                                    
                                                                    // Language skills handling - updated for either_skill changes
                                                                    if (this.formData.either_skill) {
                                                                        try {
                                                                            const eitherSkill = this.formData.either_skill;
                                                                            // Check either_skill as number value
                                                                            if (typeof eitherSkill === 'number' || typeof eitherSkill === 'string') {
                                                                                const skillLevel = parseInt(eitherSkill);
                                                                                if (skillLevel === 1) {
                                                                                    this.$refs.engHigh.checked = true;
                                                                                    this.$refs.chnHigh.checked = true;
                                                                                } else if (skillLevel === 2) {
                                                                                    this.$refs.engMed.checked = true; 
                                                                                    this.$refs.chnMed.checked = true;
                                                                                } else if (skillLevel === 3) {
                                                                                    this.$refs.engLow.checked = true;
                                                                                    this.$refs.chnLow.checked = true;
                                                                                } else if (skillLevel === 4) {
                                                                                    this.$refs.engNo.checked = true;
                                                                                    this.$refs.chnNo.checked = true;
                                                                                }
                                                                            }
                                                                            // Handle either_skill as array
                                                                            else if (Array.isArray(eitherSkill)) {
                                                                                // Find the last valid value
                                                                                const validValues = eitherSkill
                                                                                .map(val => parseInt(val))
                                                                                .filter(val => !isNaN(val) && [1, 2, 3, 4].includes(val));
                                                                                
                                                                                if (validValues.length > 0) {
                                                                                    const lastValue = validValues[validValues.length - 1];
                                                                                    
                                                                                    // Clear all checkboxes first
                                                                                    this.$refs.engHigh.checked = false;
                                                                                    this.$refs.engMed.checked = false;
                                                                                    this.$refs.engLow.checked = false;
                                                                                    this.$refs.engNo.checked = false;
                                                                                    this.$refs.chnHigh.checked = false;
                                                                                    this.$refs.chnMed.checked = false;
                                                                                    this.$refs.chnLow.checked = false;
                                                                                    this.$refs.chnNo.checked = false;
                                                                                    
                                                                                    // Set the correct checkboxes based on the last value
                                                                                    switch (lastValue) {
                                                                                        case 1:
                                                                                        this.$refs.engHigh.checked = true;
                                                                                        this.$refs.chnHigh.checked = true;
                                                                                        break;
                                                                                        case 2:
                                                                                        this.$refs.engMed.checked = true;
                                                                                        this.$refs.chnMed.checked = true;
                                                                                        break;
                                                                                        case 3:
                                                                                        this.$refs.engLow.checked = true;
                                                                                        this.$refs.chnLow.checked = true;
                                                                                        break;
                                                                                        case 4:
                                                                                        this.$refs.engNo.checked = true;
                                                                                        this.$refs.chnNo.checked = true;
                                                                                        break;
                                                                                    }
                                                                                }
                                                                            }
                                                                        } catch (e) {
                                                                            console.error('Error parsing either_skill:', e);
                                                                            this.handleIndividualLanguageSkills();
                                                                        }
                                                                    } else {
                                                                        this.handleIndividualLanguageSkills();
                                                                    }
                                                                    
                                                                    // Computer skills - handle both original and changed data
                                                                    if (this.formData.compSkill && this.formData.compSkill.skills) {
                                                                        const skills = this.formData.compSkill.skills;
                                                                        if (skills.includes('Word')) this.$refs.comp_Word.checked = true;
                                                                        if (skills.includes('Excel')) this.$refs.comp_Excel.checked = true;
                                                                        if (skills.includes('PowerPoint')) this.$refs.comp_PowerPoint.checked = true;
                                                                        if (skills.includes('Access')) this.$refs.comp_Access.checked = true;
                                                                        if (skills.includes('Internet')) this.$refs.comp_Internet.checked = true;
                                                                        
                                                                        // Other computer skills
                                                                        if (this.formData.compSkill.others) {
                                                                            this.$refs.compOther.value = this.formData.compSkill.others;
                                                                        }
                                                                    }
                                                                    
                                                                    // Workplace safety
                                                                    if (this.formData.wps) {
                                                                        const wpsLevel = parseInt(this.formData.wps);
                                                                        if (wpsLevel === 1) this.$refs.wpsBasic.checked = true;
                                                                        else if (wpsLevel === 2) this.$refs.wpsIntermediate.checked = true;
                                                                        else if (wpsLevel === 3) this.$refs.wpsAdvanced.checked = true;
                                                                    }
                                                                    
                                                                    // Attachment checkbox
                                                                    if (this.formData.att_jd) {
                                                                        this.$refs.hasAttachment.checked = true;
                                                                    } else {
                                                                        this.$refs.noAttachment.checked = true;
                                                                    }
                                                                    
                                                                },
                                                                
                                                                handleIndividualLanguageSkills() {
                                                                    if (this.formData.engSkill) {
                                                                        let engLevel;
                                                                        if (Array.isArray(this.formData.engSkill)) {
                                                                            // Take the last value in the array
                                                                            const validValues = this.formData.engSkill.filter(val => [1, 2, 3, 4].includes(parseInt(val)));
                                                                            if (validValues.length > 0) {
                                                                                engLevel = parseInt(validValues[validValues.length - 1]);
                                                                            }
                                                                        } else {
                                                                            // If it's a single value
                                                                            engLevel = parseInt(this.formData.engSkill);
                                                                        }
                                                                        
                                                                        // Clear all checkboxes first
                                                                        this.$refs.engHigh.checked = false;
                                                                        this.$refs.engMed.checked = false;
                                                                        this.$refs.engLow.checked = false;
                                                                        this.$refs.engNo.checked = false;
                                                                        
                                                                        // Set the correct checkbox
                                                                        switch(engLevel) {
                                                                            case 1:
                                                                            this.$refs.engHigh.checked = true;
                                                                            break;
                                                                            case 2:
                                                                            this.$refs.engMed.checked = true;
                                                                            break;
                                                                            case 3:
                                                                            this.$refs.engLow.checked = true;
                                                                            break;
                                                                            case 4:
                                                                            this.$refs.engNo.checked = true;
                                                                            break;
                                                                        }
                                                                    }
                                                                    
                                                                    // Handle Chinese skill
                                                                    if (this.formData.chnSkill) {
                                                                        let chnLevel;
                                                                        if (Array.isArray(this.formData.chnSkill)) {
                                                                            // Take the last value in the array
                                                                            const validValues = this.formData.chnSkill.filter(val => [1, 2, 3, 4].includes(parseInt(val)));
                                                                            if (validValues.length > 0) {
                                                                                chnLevel = parseInt(validValues[validValues.length - 1]);
                                                                            }
                                                                        } else {
                                                                            // If it's a single value
                                                                            chnLevel = parseInt(this.formData.chnSkill);
                                                                        }
                                                                        
                                                                        // Clear all checkboxes first
                                                                        this.$refs.chnHigh.checked = false;
                                                                        this.$refs.chnMed.checked = false;
                                                                        this.$refs.chnLow.checked = false;
                                                                        this.$refs.chnNo.checked = false;
                                                                        
                                                                        // Set the correct checkbox
                                                                        switch(chnLevel) {
                                                                            case 1:
                                                                            this.$refs.chnHigh.checked = true;
                                                                            break;
                                                                            case 2:
                                                                            this.$refs.chnMed.checked = true;
                                                                            break;
                                                                            case 3:
                                                                            this.$refs.chnLow.checked = true;
                                                                            break;
                                                                            case 4:
                                                                            this.$refs.chnNo.checked = true;
                                                                            break;
                                                                        }
                                                                    }
                                                                    
                                                                },
                                                                getApprover(level) {
                                                                    if (!this.formData || !this.formData.approval) return null;
                                                                    try {
                                                                        const approvalItem = this.formData.approval.find(item => item.level === level);
                                                                        return approvalItem?.approver || null;
                                                                    } catch (e) {
                                                                        console.error('Error getting approver data:', e);
                                                                        return null;
                                                                    }
                                                                },
                                                                getApprovalDate(level) {
                                                                    if (!this.formData || !this.formData.approval) return null;
                                                                    try {
                                                                        const approvalItem = this.formData.approval.find(item => item.level === level);
                                                                        if (approvalItem?.processed_at) {
                                                                            return approvalItem.processed_at.split(' ')[0]; // Returns only YYYY-MM-DD part
                                                                        }
                                                                        return null;
                                                                    } catch (e) {
                                                                        console.error('Error getting approval date:', e);
                                                                        return null;
                                                                    }
                                                                }
                                                            }
                                                        };
                                                    </script>
                                                    
                                                    <style>
                                                    body {
                                                        font-family: "Times New Roman", Times, serif !important;
                                                    }
                                                    
                                                    .chinese {
                                                        font-family: Kaiti, "宋体", serif !important;
                                                    }
                                                    
                                                    .text-header_title,
                                                    .text-header,
                                                    .colum3,
                                                    .table th b,
                                                    .signature-cell {
                                                        font-family: "Times New Roman", SimSun, "宋体", Times, serif !important;
                                                    }
                                                    
                                                    .print-layout th {
                                                        font-family: "Times New Roman", Times, serif !important;
                                                    }
                                                    
                                                    .v-application {
                                                        line-height: 1.2;
                                                    }
                                                    
                                                    .text-header_title {
                                                        font-size: 31px !important;
                                                        font-family: "Times New Roman", Times, serif !important;
                                                        width: 31%;
                                                        padding: 15px !important;
                                                        font-weight: bold;
                                                    }
                                                    
                                                    .text-header {
                                                        font-size: 16px !important;
                                                        padding: 8px !important;
                                                        line-height: 1.4;
                                                    }
                                                    
                                                    .colum3 {
                                                        width: 12%;
                                                        padding: 8px !important;
                                                        font-size: 15px !important;
                                                    }
                                                    
                                                    .table {
                                                        width: 100%;
                                                        max-width: 100%;
                                                        margin-bottom: 1rem;
                                                        background-color: transparent;
                                                        border-collapse: collapse !important;
                                                    }
                                                    
                                                    .table td,
                                                    .table th {
                                                        text-align: center;
                                                        padding: 5px;
                                                        vertical-align: middle;
                                                        border: 1px solid #333;
                                                        background-color: transparent;
                                                    }
                                                    
                                                    .table thead th {
                                                        vertical-align: bottom;
                                                        border-bottom: 2px solid #333;
                                                    }
                                                    
                                                    .table tbody+tbody {
                                                        border-top: 2px solid #333;
                                                    }
                                                    
                                                    .table-bordered {
                                                        border: 1px solid #333;
                                                    }
                                                    
                                                    .table-bordered td,
                                                    .table-bordered th {
                                                        border: 1px solid #333;
                                                    }
                                                    
                                                    .table-bordered2 td,
                                                    .table-bordered2 th {
                                                        border-top: none;
                                                        border-left: 1px solid #333;
                                                        border-right: 1px solid #333;
                                                        border-bottom: 1px solid #333;
                                                    }
                                                    
                                                    .table th b {
                                                        text-transform: uppercase;
                                                        font-size: 15px;
                                                    }
                                                    
                                                    .align-middle {
                                                        vertical-align: middle !important;
                                                    }
                                                    
                                                    tr.textMid td {
                                                        font-size: 12px !important;
                                                    }
                                                    
                                                    img.img-fluid {
                                                        padding: 3px;
                                                        max-width: 100px;
                                                        margin: 0 auto;
                                                        display: block;
                                                    }
                                                    
                                                    .signature-cell {
                                                        padding: 10px 50px;
                                                        vertical-align: middle !important;
                                                        text-align: center !important;
                                                    }
                                                    
                                                    .signature-title {
                                                        font-size: 14px;
                                                        font-weight: bold;
                                                        line-height: 1.5;
                                                        display: inline-block;
                                                    }
                                                    
                                                    /* New styles */
                                                    .no-bottom-border {
                                                        border-bottom: 1px solid #333 !important;
                                                    }
                                                    
                                                    .no-top-border {
                                                        border-top: none !important;
                                                    }
                                                    
                                                    /* Ensure no margin between tables */
                                                    .table {
                                                        margin-bottom: 0 !important;
                                                    }
                                                    
                                                    /* Adjust border for table cells */
                                                    .no-bottom-border tr:last-child td {
                                                        border-bottom: 1px solid #333 !important;
                                                    }
                                                    
                                                    .no-top-border tr:first-child td,
                                                    .no-top-border tr:first-child th {
                                                        border-top: none !important;
                                                    }
                                                    
                                                    /* Signature table styles */
                                                    .signature-table {
                                                        width: 100%;
                                                        margin-top: 20px;
                                                        border-collapse: collapse;
                                                    }
                                                    
                                                    .no-top-border {
                                                        border-top: none !important;
                                                    }
                                                    
                                                    .signature-space {
                                                        height: 150px;
                                                        border: none !important;
                                                    }
                                                    
                                                    .signature-note {
                                                        text-align: left;
                                                        padding: 10px;
                                                        white-space: nowrap;
                                                    }
                                                    
                                                    /* Ensure all borders are 1px thick */
                                                    .table-bordered {
                                                        border: 1px solid #333;
                                                    }
                                                    
                                                    .table td,
                                                    .table th {
                                                        border: 1px solid #333;
                                                    }
                                                    
                                                    .table thead th {
                                                        border-bottom: 1px solid #333;
                                                    }
                                                    
                                                    .table tbody+tbody {
                                                        border-top: 1px solid #333;
                                                    }
                                                    
                                                    /* Add loading state styles */
                                                    .processing {
                                                        opacity: 0.7;
                                                        pointer-events: none;
                                                    }
                                                    
                                                    /* Button style */
                                                    .mb-3 {
                                                        margin-bottom: 1rem !重要;
                                                    }
                                                    
                                                    /* Print media query to hide elements */
                                                    @media print {
                                                        .no-print {
                                                            display: none !important;
                                                        }
                                                        
                                                        /* Adjust margins for print layout */
                                                        .table {
                                                            margin: 0 !important;
                                                        }
                                                        
                                                        .no-bottom-border {
                                                            margin-bottom: 10px !important;
                                                        }
                                                        
                                                        /* Set print margins to 0.1 inches */
                                                        @page {
                                                            margin: 0.2in;
                                                            size: A4 portrait;
                                                        }
                                                        
                                                        /* Scale content to 86% */
                                                        [ref="printContent"] {
                                                            transform-origin: top left;
                                                        }
                                                    }
                                                </style>