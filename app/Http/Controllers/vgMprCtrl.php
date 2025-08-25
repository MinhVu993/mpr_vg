<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\hrmDepartmentMdl as hrmDept;
use App\globalDeptMdl as DeptCode;
use App\vgMprLangMdl as Lang;
use App\vgMprWpsMdl as Wps;
use App\vgMprMdl as Mpr;
use App\vgMprWorkExpMdl as WorkExp;
use App\vgMprTypeApplyMdl as TypeApply;
use App\vgMprEduReqMdl as EduReq;
use App\vgMprSubPosMdl as SubPos;
use App\vgMprChangeReasonMdl as ChangeReason;
use App\vgMprRecReasonMdl as RecReason;
use App\vgRecruitingReportResignDataMdl as ResignData;
use App\vgRecruitingReportEmpnoInfoMdl as EmpnoInfo;
use App\vgRecruitingReportDeptCodeMdl as nameDeptCode;
use Illuminate\Support\Facades\Validator;

class vgMprCtrl extends Controller
{
    private function getResignData($loc) {
        return ResignData::select('er_empid')->where('loc', $loc)
        ->orderBy('id', 'desc')
        ->get();
    }
    
    private function getRecruitedData($loc)
    {
        return EmpnoInfo::select('tmp_departid')->where('loc',$loc)
        ->orderBy('id','desc')
        ->get();
    }
    public function getReqId(Request $req)
    {
        $div = $req->input('div');
        $status = $req->input('status');
        
        $data = Mpr::select('req_id')->where('status', $status)
        ->whereRaw('JSON_EXTRACT(submitter, "$.div") = ?', [$div])
        ->orderBy('id', 'desc')
        ->get();
        return $data;
    }
    public function getDataByReqId($req_id)
    {
        try {
            if (!$req_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request ID is required'
                ], 400);
            }
            
            // Find MPR record by req_id
            $mpr = Mpr::where('req_id', $req_id)
            ->where('status', 'completed')
            ->first();
            
            if (!$mpr) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No record found for given Request ID'
                ], 404);
            }
            $data = $mpr->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving data: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getChangeReasonData() {
        $ChangeData = ChangeReason::orderBy('id', 'asc')->get();
        return [
            'ChangeData' => $ChangeData,
        ];
    }
    public function getAllDataMpr(Request $req)
    {
        $loc = $req->input('loc');
        $dept = $req->input('dept');
        $empno = $req->input('empno');
        $high_dept = $req->input('high_dept');
        $role = $req->input('role');
        
        $dataMprQuery = Mpr::where('loc', $loc)->orderBy('id', 'desc');
        $dataMpr = $dataMprQuery->get();
        
        $dataResign = $this->getResignData($loc);
        $resignEmpIds = collect($dataResign)->pluck('er_deptid')->toArray();
        
        $recruited = $this->getRecruitedData($loc);
        $recruitedData = collect($recruited)->pluck('tmp_departid')->toArray();
        
        if ($role === "admin" || $role === "review") {
            // Return all data for admin/review roles
        }
        else if ($empno && $dept) {
            $params = new Request([
                'empno' => $empno,
                'app_code' => 'mpr',
                'location' => 'VG',
                'custom_data' => json_encode([
                    'custom_dept' => $dept,
                    'custom_division' => $high_dept,
                ]),
            ]);
            $flowResponse = $this->getDataFlowForItem($params)->getData();
            $flowData = json_decode(json_encode($flowResponse->flow_data ?? []), true);
            
            if ($empno && $flowData) {
                $userAccessibleDepts = [];
                $userDivisions = [];
                
                foreach ($flowData as $level) {
                    if (!empty($level['managers'])) {
                        foreach ($level['managers'] as $manager) {
                            // Direct manager access
                            if ($manager['empno'] === $empno) {
                                if (!empty($manager['dept_code'])) {
                                    $userAccessibleDepts = array_merge($userAccessibleDepts, $manager['dept_code']);
                                }
                                if (!empty($manager['division_code'])) {
                                    $userDivisions[] = $manager['division_code'];
                                }
                            }
                            
                            // Deputy access
                            if (!empty($manager['deputies'])) {
                                foreach ($manager['deputies'] as $deputy) {
                                    if ($deputy['empno'] === $empno && !empty($deputy['status']) && $deputy['status'] === true) {
                                        if (!empty($manager['dept_code'])) {
                                            $userAccessibleDepts = array_merge($userAccessibleDepts, $manager['dept_code']);
                                        }
                                        if (!empty($manager['division_code'])) {
                                            $userDivisions[] = $manager['division_code'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $userAccessibleDepts = array_unique($userAccessibleDepts);
                $userDivisions = array_unique($userDivisions);
                
                $dataMpr = $dataMpr->filter(function($item) use ($empno, $flowData, $high_dept, $dept, $userAccessibleDepts, $userDivisions) {
                    $submitter = json_decode($item->submitter, true);
                    $itemDept = $submitter['dept'] ?? null;
                    
                    // Case 1: User created the request
                    if (isset($submitter['div']) && $submitter['div'] === $high_dept) {
                        return true;
                    }
                    
                    // Case 2: Check division access
                    $itemDiv = isset($submitter['div']) ? trim($submitter['div']) : '';
                    if (!empty($itemDiv) && in_array($itemDiv, $userDivisions)) {
                        return true;
                    }
                    
                    // Case 3: Check department access
                    $itemDept = isset($submitter['dept_sumt']) ? trim($submitter['dept_sumt']) : 
                    (isset($submitter['dept']) ? trim($submitter['dept']) : '');
                    
                    if (!empty($itemDept) && in_array($itemDept, $userAccessibleDepts)) {
                        return true;
                    }
                    
                    // Case 4: Check current approval level access
                    $statusesToCheck = [];
                    if (!empty($item->status)) {
                        $statusesToCheck[] = $item->status;
                    }
                    if (!empty($item->status_amendent)) {
                        $statusesToCheck[] = $item->status_amendent;
                    }
                    
                    $statusToLevel = [
                        'waiting_unit' => 0,
                        'waiting_dept' => 1, 
                        'waiting_div' => 2,
                        'waiting_hr_rec_sf' => 3,
                        'waiting_hr' => 4,
                        'waiting_ad' => 5,
                        'waiting_gm' => 6
                    ];
                    
                    foreach ($statusesToCheck as $status) {
                        if (isset($statusToLevel[$status])) {
                            $currentLevel = $statusToLevel[$status];
                            $currentFlow = $flowData[$currentLevel] ?? null;
                            
                            if ($currentFlow && !empty($currentFlow['managers'])) {
                                foreach ($currentFlow['managers'] as $manager) {
                                    // For HR_REC_SF and above levels, show all departments
                                    if ($currentLevel >= 3 && $manager['empno'] === $empno) {
                                        return true;
                                    }
                                    
                                    // For lower levels, check department access
                                    if ($currentLevel < 3) {
                                        if ($manager['empno'] === $empno && !empty($manager['dept_code'])) {
                                            if (in_array($itemDept, $manager['dept_code'])) {
                                                return true;
                                            }
                                        }
                                    }
                                    
                                    // Check deputies
                                    if (!empty($manager['deputies'])) {
                                        foreach ($manager['deputies'] as $deputy) {
                                            // For HR_REC_SF and above levels
                                            if ($currentLevel >= 3 && 
                                            $deputy['empno'] === $empno && 
                                            !empty($deputy['status']) && 
                                            $deputy['status'] === true) {
                                                return true;
                                            }
                                            
                                            // For lower levels
                                            if ($currentLevel < 3 && 
                                            $deputy['empno'] === $empno && 
                                            !empty($deputy['status']) && 
                                            $deputy['status'] === true &&
                                            !empty($manager['dept_code']) && 
                                            in_array($itemDept, $manager['dept_code'])) {
                                                return true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    return false;
                })->values();
            }
        }
        else if ($dept && $role) {
            $dataMpr = $dataMpr->filter(function($mpr) use ($dept, $role) {
                $submitter = json_decode($mpr->submitter, true);
                return isset($submitter['div']) && 
                strtolower(trim($submitter['div'])) === strtolower(trim($dept));
            })->values();
        }
        
        return response()->json([
            'dataMpr' => $dataMpr,
        ]);
    }
    public function getDataModel(Request $req)
    {
        $loc = $req->input('loc');
        if (!$loc) {
            return response()->json(['error' => 'Location not provided'], 400);
        }
        $dataRecReason = RecReason::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataDeptCode = DeptCode::orderBy('id', 'desc')->get();
        $dataWorkExp = WorkExp::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataTypeApply = TypeApply::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataSubPos = SubPos::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataEduReq = EduReq::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataLang = Lang::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataWps = Wps::where('loc', $loc)->orderBy('id', 'asc')->get();
        $dataDeptHrm = nameDeptCode::orderBy('id', 'asc')->get();
        $model = [
            'dataRecReason' => $dataRecReason,
            'dataDeptCode' => $dataDeptCode,
            'dataWorkExp' => $dataWorkExp,
            'dataTypeApply' => $dataTypeApply,
            'dataSubPos' => $dataSubPos,
            'dataEduReq' => $dataEduReq,
            'dataLang' => $dataLang,
            'dataWps' => $dataWps,
            'dataDeptHrm' => $dataDeptHrm,
        ];
        return response()->json($model); 
    }
    private function handleChangeHistory($existingMpr, $request) {
        $historyFields = [
            'change_req',
            'either_change', 
            'english_change',
            'chinese_change',
            'computerSkills_change',
            'remark_change',
            'tranlator_change'
        ];
        
        $updatedData = [];
        
        foreach ($historyFields as $field) {
            if ($request->has($field) && $request->get($field)) {
                $currentValue = $request->get($field);
                $existingHistory = [];
                
                // Get existing history from DB
                if ($existingMpr && $existingMpr->$field) {
                    try {
                        $existingHistory = json_decode($existingMpr->$field, true);
                        // Ensure it's an array
                        $existingHistory = is_array($existingHistory) ? $existingHistory : [$existingHistory];
                    } catch (\Exception $e) {
                        $existingHistory = [];
                    }
                }
                
                // Parse currentValue if it's a JSON string
                if (is_string($currentValue)) {
                    try {
                        $parsedValue = json_decode($currentValue, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $currentValue = $parsedValue;
                        }
                    } catch (\Exception $e) {
                        // Keep currentValue as is if parsing fails
                    }
                }
                
                // Add new value to history
                $existingHistory[] = $currentValue;
                
                // JSON encode without escaping slashes and unicode
                $updatedData[$field] = json_encode(
                    $existingHistory, 
                    JSON_UNESCAPED_SLASHES | 
                    JSON_UNESCAPED_UNICODE | 
                    JSON_PRESERVE_ZERO_FRACTION
                );
            }
        }
        
        return $updatedData;
    }
    public function saveManpower(Request $request) 
    {
        try {
            $validator = Validator::make($request->all(), [
                'req_id' => 'required',
                'key_in_date' => 'required|date',
                'submitter' => 'required',
                'supp_type' => 'required',
                'edu' => 'required',
                'w_exp' => 'required',
                'reason_exp' => 'required',
                'date_req' => 'required|date',
                'qty_req' => 'required',
                'jd' => 'required',
                'wps' => 'required',
                'pos' => 'required'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }
            
            $existingMpr = Mpr::where('req_id', $request->req_id)->first();
            $isAmendment = $request->input('is_amendment') === 'true';
            
            // Handle file uploads
            $att_jd = null;
            $att_dis = null;
            
            if ($request->hasFile('jobDescFile')) {
                $file = $request->file('jobDescFile');
                $filename = $file->getClientOriginalName();
                $file->storeAs('vg-mpr', $filename);
                $att_jd = $filename;
            }
            
            if ($request->hasFile('dimissionFile')) {
                $file = $request->file('dimissionFile');
                $filename = $file->getClientOriginalName();
                $file->storeAs('vg-mpr', $filename);
                $att_dis = $filename;
            }
            
            // Calculate stock number
            $mpr = Mpr::where('req_id', $request->req_id)->first();
            $recruited = $mpr ? $mpr->recruited : 0;
            $stocknumber = $request->total_qty - $recruited;
            
            // Handle change history
            $historyData = $this->handleChangeHistory($existingMpr, $request);
            
            $data = [
                'req_id' => $request->req_id,
                'supp_type' => $request->supp_type,
                'key_in_date' => $request->key_in_date,
                'submitter' => $request->submitter,
                'edu' => $request->edu,
                'spec' => $request->spec,
                'w_exp' => $request->w_exp,
                'reason_exp' => $request->reason_exp,
                'old_emp' => $request->old_emp,
                'date_req' => $request->date_req,
                'qty_req' => $request->qty_req,
                'stocknumber' => $stocknumber,
                'jd' => $request->jd,
                'att_jd' => $att_jd ?? $request->att_jd,
                'att_dis' => $att_dis ?? $request->att_dis,
                'eng_skill' => $request->eng_skill,
                'chn_skill' => $request->chn_skill,
                'either_skill' => $request->either_skill,
                'comp_skill' => $request->comp_skill,
                'wps' => $request->wps,
                'remark' => $request->remark,
                'pos' => $request->pos,
                'sub_pos' => $request->sub_pos,
                'loc' => $request->loc,
                'isOther_reason' => $request->isOther_reason,
                'tranlator' => $request->tranlator,
                'tranlator_change' => $request->tranlator_change,
                'hold' => $request->hold,
                'hold_date' => $request->hold_date,
                'resumed' => $request->resumed,
                'resumed_date' => $request->resumed_date,
                'req_id_change' => $request->req_id_change,
                
                // Amendment request fields
                'change_req' => $request->change_req,
                'either_change' => $request->either_change,
                'english_change' => $request->english_change,
                'chinese_change' => $request->chinese_change,
                'computerSkills_change' => $request->computerSkills_change,
                'remark_change' => $request->remark_change
            ];
            
            if ($request->has('deny_temp') && $request->get('deny_temp')) {
                $data['deny_temp'] = $request->get('deny_temp');
            }
            
            // Xử lý status dựa trên loại request
            if ($isAmendment) {
                // Amendment request - chỉ set status_amendent
                $data['status_amendent'] = $request->status_amendent ?: 'waiting_unit';
                
                // Giữ nguyên status và approval gốc nếu đã tồn tại
                if ($existingMpr) {
                    $data['status'] = $existingMpr->status;
                    $data['approval'] = $existingMpr->approval;
                } else {
                    $data['status'] = $request->status ?: 'waiting_unit';
                    $data['approval'] = $request->approval;
                }
            } else {
                // Đơn thường
                $data['status'] = $request->status;
                $data['approval'] = $request->approval;
                $data['status_amendent'] = null;
            }
            
            // Merge history data
            $data = array_merge($data, $historyData);
            // Handle logs to preserve approval history
            if ($existingMpr) {
                $currentApproval = $existingMpr->approval;
                
                if (!empty($currentApproval)) {
                    $logsArray = [];
                    if (!empty($existingMpr->logs)) {
                        $existingLogs = json_decode($existingMpr->logs, true);
                        if (isset($existingLogs[0]) && is_array($existingLogs[0]) && isset($existingLogs[0][0])) {
                            $logsArray = $existingLogs;
                        } else {
                            $logsArray = [$existingLogs];
                        }
                    }
                    $logsArray[] = json_decode($currentApproval, true);
                    $data['logs'] = json_encode($logsArray);
                } else {
                    $data['logs'] = $existingMpr->logs;
                }
            } else {
                $data['logs'] = null;
            }
            $mpr = Mpr::updateOrCreate(
                ['req_id' => $request->req_id],
                $data
            );
            
            // Send email notification
            if ($request->emailList) {
                $this->sendEmailNotification($mpr, $request, $isAmendment);
            }
            
            return response()->json([
                'success' => true,
                'message' => $isAmendment ? 'Amendment request saved successfully' : 'Manpower request saved successfully',
                'data' => [
                    'id' => $mpr->id,
                    'req_id' => $mpr->req_id,
                    'status' => $mpr->status,
                    'status_amendent' => $mpr->status_amendent,
                    'is_amendment' => $isAmendment
                    ]
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Error in saveManpower:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error saving manpower request: ' . $e->getMessage()
                ], 500);
            }
        }
        private function getAmendmentChangeReason($mpr) {
            if (empty($mpr->change_req)) {
                return null;
            }
            try {
                $changeReqData = json_decode($mpr->change_req, true);
                if (!is_array($changeReqData) || empty($changeReqData)) {
                    return null;
                }
                $lastChangeReq = end($changeReqData);
                
                // Kiểm tra cấu trúc
                if (
                    !isset($lastChangeReq['change_reason']) ||
                    !is_array($lastChangeReq['change_reason']) ||
                    !isset($lastChangeReq['change_reason']['id'])
                    ) {
                        return null;
                    }
                    
                    // Ép kiểu id về int (trường hợp lưu "03", "7", ...)
                    $changeReasonId = (int)$lastChangeReq['change_reason']['id'];
                    if ($changeReasonId <= 0) {
                        return null;
                    }
                    
                    $changeReason = ChangeReason::find($changeReasonId);
                    if (!$changeReason) {
                        return null;
                    }
                    
                    // Giải mã field reason (thường là JSON)
                    $reasonRaw = $changeReason->reason;
                    if (is_string($reasonRaw)) {
                        $decoded = json_decode($reasonRaw, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $reasonRaw = $decoded;
                        }
                    }
                    
                    if (!is_array($reasonRaw)) {
                        return null;
                    }
                    
                    return [
                        'cn' => $reasonRaw['cn'] ?? '',
                        'en' => $reasonRaw['en'] ?? '',
                        'vi' => $reasonRaw['vi'] ?? '',
                    ];
                    
                } catch (\Exception $e) {
                    \Log::error('Error getting amendment change reason: ' . $e->getMessage());
                    return null;
                }
            }
            private function sendEmailNotification($mpr, $request, $isAmendment)
            {
                $submitter = json_decode($mpr->submitter, true);
                
                // Get position and reason names
                $typeApply = TypeApply::where('id', $mpr->pos)->where('loc', $mpr->loc)->first();
                $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;
                
                $ReasonName = RecReason::where('id', $mpr->reason_exp)->where('loc', $mpr->loc)->first();
                $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;
                
                $templateData = [
                    'department' => $submitter['dept_sumt'] ?? '',
                    'requester' => [
                        'name' => $submitter['name'] ?? '',
                        'empno' => $submitter['empno'] ?? '',
                        'position' => $positionName
                    ],
                    'request' => [
                        'id' => $mpr->req_id,
                        'dept' => $submitter['dept'] ?? '',
                        'div' => $submitter['div'] ?? '',
                        'unit' => $submitter['unit'] ?? '',
                        'date' => $mpr->key_in_date,
                        'qty' => json_decode($mpr->qty_req, true),
                        'reason' => $typereasonName,
                        'status' => $isAmendment ? $mpr->status_amendent : $mpr->status
                        ]
                    ];
                    
                    // Determine email subject and template based on request type
                    if ($isAmendment) {
                        $emailSubject = "[MPR] 人員增補需求變更申請通知 / MPR Amendment Request Notification / Thông báo điều chỉnh Phiếu yêu cầu nhân sự";
                        $templateView = 'VgMpr-approval_amd';
                        
                        // Add amendment specific data
                        if (!empty($mpr->change_req)) {
                            $changeReqData = json_decode($mpr->change_req, true);
                            if (is_array($changeReqData) && !empty($changeReqData)) {
                                $lastChangeReq = end($changeReqData);
                                if (isset($lastChangeReq['change_reason']) && isset($lastChangeReq['change_reason']['id'])) {
                                    $changeReasonId = $lastChangeReq['change_reason']['id'];
                                    $changeReasonModel = ChangeReason::find($changeReasonId);
                                    if ($changeReasonModel) {
                                        $reasonData = json_decode($changeReasonModel->reason, true);
                                        if ($reasonData) {
                                            $templateData['request']['reason_change'] = [
                                                'cn' => $reasonData['cn'] ?? '',
                                                'en' => $reasonData['en'] ?? '',
                                                'vi' => $reasonData['vi'] ?? ''
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $templateData['request']['re_id'] = $mpr->req_id_change;
                        $templateData['request']['remark_change'] = $mpr->remark_change;
                    } else {
                        $emailSubject = "[MPR] 人員增補單簽核通知 / Manpower Request Form Pending Your Approval / Thông báo phê duyệt đơn bổ sung nhân sự";
                        $templateView = 'VgMpr-approval';
                    }
                    
                    $emailList = json_decode($request->emailList, true);
                    $emailContent = view($templateView, $templateData)->render();
                    
                    $mailData = [
                        'to' => $emailList,
                        'subject' => $emailSubject,
                        'msgBody' => $emailContent,
                    ];
                    
                    $params = [
                        'target' => $isAmendment ? 'VG-MPR' : 'VG-MPR',
                        'body' => $emailContent,
                        'msg_type' => "m",
                        'msg_method' => "both",
                        'msg_subject' => $emailSubject,
                        'mail_template' => "msgCenterMailTemplate",
                        'mail_data' => json_encode($mailData),
                    ];
                    
                    $this->sendMsg($params);
                }
                public function updateReqId(Request $request)
                {
                    try {
                        $validator = Validator::make($request->all(), [
                            'id' => ['required', function ($attribute, $value, $fail) {
                                if (!Mpr::find($value)) {
                                    $fail('The selected record does not exist.');
                                }
                            }],
                            'req_id' => ['required', function ($attribute, $value, $fail) use ($request) {
                                $exists = Mpr::where('req_id', $value)
                                ->where('id', '!=', $request->id)
                                ->exists();
                                if ($exists) {
                                    $fail('This Req ID is already in use.');
                                }
                            }]
                        ]);
                        
                        if ($validator->fails()) {
                            return response()->json([
                                'success' => false,
                                'errors' => $validator->errors()
                            ], 400);
                        }
                        
                        $mpr = Mpr::find($request->id);
                        $oldReqId = $mpr->req_id;
                        $mpr->req_id = $request->req_id;
                        $mpr->save();
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Req ID updated successfully',
                            'data' => [
                                'id' => $mpr->id,
                                'oldReqId' => $oldReqId,
                                'newReqId' => $mpr->req_id
                                ]
                            ]);
                            
                        } catch (\Exception $e) {
                            return response()->json([
                                'success' => false, 
                                'message' => 'Error updating Req ID: ' . $e->getMessage()
                            ], 500);
                        }
                    }
                    public function getDataFlowForItem(Request $req)
                    {
                        $params = new Request([
                            'empno' => $req->empno,
                            'app_code' => 'mpr',
                            'location' => 'VG',
                            'custom_data' => json_encode([
                                'custom_dept' => $req->dept,
                                'custom_division' => $req->high_dept,
                            ]), 
                        ]); 
                        $vfaController = new vfaCtrl();
                        try {
                            $flowResponse = $vfaController->getFlowUpdated($params); 
                            if (!$flowResponse) {
                                return response()->json(['error' => 'No data received from vfaController'], 500);
                            }
                            return response()->json($flowResponse);
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 500);
                        }
                    }
                    public function acceptRequest(Request $req)
                    {
                        try {
                            $validator = Validator::make($req->all(), [
                                'user_empno' => 'required|string', 
                                'dataReq' => 'required',
                            ]);
                            
                            if ($validator->fails()) {
                                return response()->json([
                                    'success' => false,
                                    'errors' => $validator->errors()
                                ], 400);
                            }
                            
                            $dataReq = $req['dataReq'];
                            $dataFlow = $dataReq['dataFlow']; 
                            $status = $dataReq['status'];
                            $approval = json_decode($dataReq['approval'], true);
                            $id = $dataReq['id'];
                            $user_empno = $req->user_empno; 
                            
                            // Check if this is an amendment request
                            $isAmendmentRequest = !empty($dataReq['status_amendent']);
                            $amendmentStatus = $dataReq['status_amendent'] ?? null;
                            
                            $mpr = Mpr::find($id);
                            if (!$mpr) {
                                throw new \Exception('MPR record not found');
                            }
                            
                            // Update approval_record if provided
                            if (isset($dataReq['approval_record'])) {
                                $approvalRecord = json_decode($dataReq['approval_record'], true);
                                if (!is_array($approvalRecord) || !isset($approvalRecord[0])) {
                                    $approvalRecord = [$approvalRecord];
                                }
                                $mpr->approval_record = json_encode($approvalRecord);
                                $mpr->save();
                            }
                            
                            if (($status === 'waiting_hr' || $status === 'waiting_hr_rec_sf') && isset($dataReq['sub_pos'])) {
                                $mpr->sub_pos = $dataReq['sub_pos'];
                                $mpr->save();
                            }
                            
                            // Define status flow for amendment vs regular request
                            if ($isAmendmentRequest) {
                                // Amendment status flow - shorter flow
                                $statusFlow = [
                                    'waiting_unit' => ['next' => 'waiting_dept', 'level' => 1],
                                    'waiting_dept' => ['next' => 'waiting_div', 'level' => 2], 
                                    'waiting_div' => ['next' => 'waiting_hr_rec_sf', 'level' => 3],
                                    'waiting_hr_rec_sf' => ['next' => 'waiting_hr', 'level' => 4],
                                    'waiting_hr' => ['next' => 'completed', 'level' => 5], // End at HR for amendments
                                ];
                                $currentStatus = $amendmentStatus;
                            } else {
                                // Regular request status flow
                                if ($mpr->supp_type == 1) {
                                    $statusFlow = [
                                        'waiting_unit' => ['next' => 'waiting_dept', 'level' => 1],
                                        'waiting_dept' => ['next' => 'waiting_div', 'level' => 2], 
                                        'waiting_div' => ['next' => 'waiting_hr_rec_sf', 'level' => 3],
                                        'waiting_hr_rec_sf' => ['next' => 'waiting_hr', 'level' => 4],
                                        'waiting_hr' => ['next' => 'completed', 'level' => 5], 
                                    ];
                                } else {
                                    $statusFlow = [
                                        'waiting_unit' => ['next' => 'waiting_dept', 'level' => 1],
                                        'waiting_dept' => ['next' => 'waiting_div', 'level' => 2], 
                                        'waiting_div' => ['next' => 'waiting_hr_rec_sf', 'level' => 3],
                                        'waiting_hr_rec_sf' => ['next' => 'waiting_hr', 'level' => 4],
                                        'waiting_hr' => ['next' => 'waiting_ad', 'level' => 5],
                                        'waiting_ad' => ['next' => 'waiting_gm', 'level' => 6],
                                        'waiting_gm' => ['next' => 'completed', 'level' => 7],
                                    ];
                                }
                                $currentStatus = $status;
                            }
                            
                            $currentLevelIndex = $statusFlow[$currentStatus]['level'] - 1;
                            if (isset($approval[$currentLevelIndex])) {
                                $approval[$currentLevelIndex]['status'] = 'approved';
                                $approval[$currentLevelIndex]['processed_at'] = now()->toDateTimeString();
                                
                                if (!empty($dataFlow[$currentLevelIndex]['managers'])) {
                                    $currentApprover = $this->findApprover($dataFlow[$currentLevelIndex]['managers'], $user_empno);
                                    if ($currentApprover) {
                                        $approval[$currentLevelIndex]['approver'] = $currentApprover;
                                    }
                                }
                            }
                            
                            $nextStatus = $statusFlow[$currentStatus]['next'];
                            if (!in_array($nextStatus, ['completed', 'completed'])) {
                                $nextLevelIndex = $statusFlow[$currentStatus]['level'];
                                if (isset($approval[$nextLevelIndex])) {
                                    $approval[$nextLevelIndex]['status'] = 'waiting';
                                }
                            }
                            
                            // Update MPR record based on request type
                            if ($isAmendmentRequest) {
                                $mpr->status_amendent = $nextStatus;
                                // Keep original status unchanged for amendments
                            } else {
                                $mpr->status = $nextStatus;
                            }
                            $mpr->approval = json_encode($approval);
                            $mpr->save();
                            
                            // Send email notification
                            $this->sendAcceptEmailNotification($mpr, $nextStatus, $dataFlow, $statusFlow, $currentStatus, $isAmendmentRequest);
                            
                            return response()->json([
                                'success' => true,
                                'message' => $isAmendmentRequest ? 'Amendment request accepted successfully' : 'Request accepted successfully',
                                'data' => [
                                    'id' => $id,
                                    'status' => $mpr->status,
                                    'status_amendent' => $mpr->status_amendent,
                                    'approval' => $approval,
                                    ]
                                ]);
                                
                            } catch (\Exception $e) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Error accepting request: ' . $e->getMessage()
                                ], 500);
                            }
                        }
                        private function findApprover($managers, $user_empno)
                        {
                            foreach ($managers as $manager) {
                                if ($manager['empno'] === $user_empno) {
                                    return [
                                        'empno' => $manager['empno'],
                                        'name' => $manager['name'],
                                        'dept' => $manager['dept_code'][0] ?? '',
                                        'email' => $manager['email']
                                    ];
                                }
                                
                                if (!empty($manager['deputies'])) {
                                    foreach ($manager['deputies'] as $deputy) {
                                        if ($deputy['empno'] === $user_empno && !empty($deputy['status'])) {
                                            return [
                                                'empno' => $deputy['empno'],
                                                'name' => $deputy['name'],
                                                'dept' => $manager['dept_code'][0] ?? '',
                                                'email' => $deputy['email']
                                            ];
                                        }
                                    }
                                }
                            }
                            return null;
                        }
                        private function sendMsg($params) 
                        {
                            try {
                                $client = new \GuzzleHttp\Client();
                                
                                $formData = http_build_query($params);
                                
                                $response = $client->post("http://gmo021.cansportsvg.com/api/msg-center/sendOutMsg", [
                                    'headers' => [
                                        'Content-Type' => 'application/x-www-form-urlencoded'
                                    ],
                                    'body' => $formData 
                                ]);
                                
                                return json_decode($response->getBody()->getContents(), true);
                            } catch (\Exception $e) {
                                throw $e;
                            }
                        }
                        public function denyRequest(Request $req)
                        {
                            try {
                                $validator = Validator::make($req->all(), [
                                    'id' => 'required',
                                    'reason' => 'required|string|max:500',
                                    'user_empno' => 'required|string', 
                                    'dataReq' => 'required',
                                ]);
                                
                                if ($validator->fails()) {
                                    return response()->json([
                                        'success' => false,
                                        'errors' => $validator->errors()
                                    ], 400);
                                }
                                
                                $dataReq = $req['dataReq'];
                                $dataFlow = $dataReq['dataFlow'];
                                $status = $dataReq['status'];
                                $approval = json_decode($dataReq['approval'], true);
                                $id = $dataReq['id'];
                                $user_empno = $req->user_empno;
                                
                                // Check if this is an auto decline
                                $autoDecline = isset($dataReq['auto_decline']) && $dataReq['auto_decline'] === true;
                                
                                // Check if this is an amendment request
                                $isAmendmentRequest = !empty($dataReq['status_amendent']);
                                $amendmentStatus = $dataReq['status_amendent'] ?? null;
                                
                                $mpr = Mpr::find($id);
                                if (!$mpr) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'MPR record not found'
                                    ], 404);
                                }
                                
                                if ($autoDecline) {
                                    // Handle auto decline - simpler logic
                                    if ($isAmendmentRequest) {
                                        $mpr->status_amendent = 'declined';
                                    } else {
                                        $mpr->status = 'declined';
                                    }
                                    
                                    // Update approval with auto decline reason
                                    if ($approval && is_array($approval)) {
                                        foreach ($approval as &$level) {
                                            if (empty($level['status']) || $level['status'] === 'waiting') {
                                                $level['status'] = 'rejected';
                                                $level['reason_denied'] = $req->reason;
                                                $level['processed_at'] = now()->toDateTimeString();
                                                $level['approver'] = [
                                                    'empno' => 'SYSTEM',
                                                    'name' => 'Auto System',
                                                    'dept' => 'SYSTEM',
                                                    'email' => 'system@company.com'
                                                ];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $mpr->approval = json_encode($approval);
                                    $mpr->save();
                                    
                                    return response()->json([
                                        'success' => true,
                                        'message' => 'Request auto declined successfully',
                                        'data' => [
                                            'id' => $mpr->id,
                                            'status' => $mpr->status,
                                            'status_amendent' => $mpr->status_amendent,
                                            'approval' => $approval
                                            ]
                                        ]);
                                    }
                                    
                                    // Original manual deny logic
                                    $statusFlow = [
                                        'waiting_unit' => ['level' => 1],
                                        'waiting_dept' => ['level' => 2],
                                        'waiting_div' => ['level' => 3],
                                        'waiting_hr_rec_sf' => ['level' => 4],
                                        'waiting_hr' => ['level' => 5],
                                        'waiting_ad' => ['level' => 6],
                                        'waiting_gm' => ['level' => 7],
                                    ];
                                    
                                    $currentStatus = $isAmendmentRequest ? $amendmentStatus : $status;
                                    
                                    if (!isset($statusFlow[$currentStatus])) {
                                        return response()->json([
                                            'success' => false,
                                            'message' => 'Invalid current status for denial'
                                        ], 400);
                                    }
                                    
                                    $currentLevelIndex = $statusFlow[$currentStatus]['level'] - 1;
                                    
                                    if (isset($approval[$currentLevelIndex])) {
                                        $approval[$currentLevelIndex]['status'] = 'rejected';
                                        $approval[$currentLevelIndex]['reason_denied'] = $req->reason;
                                        $approval[$currentLevelIndex]['processed_at'] = now()->toDateTimeString();
                                        
                                        if (!empty($dataFlow[$currentLevelIndex]['managers'])) {
                                            $currentApprover = $this->findApprover($dataFlow[$currentLevelIndex]['managers'], $user_empno);
                                            if ($currentApprover) {
                                                $approval[$currentLevelIndex]['approver'] = $currentApprover;
                                            }
                                        }
                                    }
                                    
                                    // Update status based on request type
                                    if ($isAmendmentRequest) {
                                        $mpr->status_amendent = 'declined';
                                    } else {
                                        $mpr->status = 'declined';
                                    }
                                    $mpr->approval = json_encode($approval);
                                    $mpr->save();
                                    
                                    // Send denial notification
                                    $this->sendDenialEmailNotification($mpr, $req->reason, $isAmendmentRequest);
                                    
                                    return response()->json([
                                        'success' => true,
                                        'message' => $isAmendmentRequest ? 'Amendment request denied successfully' : 'Request denied successfully',
                                        'data' => [
                                            'id' => $mpr->id,
                                            'status' => $mpr->status,
                                            'status_amendent' => $mpr->status_amendent,
                                            'approval' => $approval
                                            ]
                                        ]);
                                        
                                    } catch (\Exception $e) {
                                        return response()->json([
                                            'success' => false,
                                            'message' => 'Error denying request: ' . $e->getMessage()
                                        ], 500);
                                    }
                                }
                                public function returnRequest(Request $req)
                                {
                                    try {
                                        $validator = Validator::make($req->all(), [
                                            'id' => 'required',
                                            'reason' => 'required|string|max:500',
                                            'returnToLevel' => 'required|string',  
                                            'user_empno' => 'required|string', 
                                            'dataReq' => 'required',
                                            'dataReq.dataFlow' => 'required'
                                        ]);
                                        
                                        if ($validator->fails()) {
                                            return response()->json([
                                                'success' => false,
                                                'errors' => $validator->errors()
                                            ], 400);
                                        }
                                        
                                        $dataReq = $req['dataReq'];
                                        $dataFlow = $dataReq['dataFlow'];
                                        $status = $dataReq['status'];
                                        $approval = json_decode($dataReq['approval'], true);
                                        $id = $dataReq['id'];
                                        $returnStatus = $req->returnToLevel;
                                        $user_empno = $req->user_empno;
                                        
                                        // Check if this is an amendment request
                                        $isAmendmentRequest = !empty($dataReq['status_amendent']);
                                        $amendmentStatus = $dataReq['status_amendent'] ?? null;
                                        
                                        // Get mpr record
                                        $mpr = Mpr::find($id);
                                        if (!$mpr) {
                                            return response()->json([
                                                'success' => false,
                                                'message' => 'MPR record not found'
                                            ], 404);
                                        }
                                        
                                        // Save current approval to logs before updating
                                        $currentApproval = $mpr->approval;
                                        $logsArray = [];
                                        
                                        if (!empty($currentApproval)) {
                                            if (!empty($mpr->logs)) {
                                                $existingLogs = json_decode($mpr->logs, true);
                                                if (isset($existingLogs[0]) && is_array($existingLogs[0]) && isset($existingLogs[0][0])) {
                                                    $logsArray = $existingLogs;
                                                } else {
                                                    $logsArray = [$existingLogs];
                                                }
                                            }
                                            $logsArray[] = json_decode($currentApproval, true);
                                            $mpr->logs = json_encode($logsArray);
                                        }
                                        
                                        $statusFlow = [
                                            'waiting_unit' => ['level' => 1],
                                            'waiting_dept' => ['level' => 2],
                                            'waiting_div' => ['level' => 3],
                                            'waiting_hr_rec_sf' => ['level' => 4],
                                            'waiting_hr' => ['level' => 5],
                                            'waiting_ad' => ['level' => 6],
                                            'waiting_gm' => ['level' => 7],
                                        ];
                                        
                                        // Update approval array for return
                                        foreach ($approval as $index => $level) {
                                            if ($index >= $statusFlow[$returnStatus]['level'] - 1) {
                                                $approval[$index]['status'] = 'waiting';
                                                $approval[$index]['approver'] = null;
                                                $approval[$index]['reason_denied'] = null;
                                            }
                                        }
                                        
                                        $currentStatus = $isAmendmentRequest ? $amendmentStatus : $status;
                                        $currentLevelIndex = $statusFlow[$currentStatus]['level'] - 1;
                                        
                                        if (isset($approval[$currentLevelIndex])) {
                                            $approval[$currentLevelIndex]['status'] = 'returned';
                                            $approval[$currentLevelIndex]['return_reason'] = $req->reason;
                                            
                                            if (!empty($dataFlow[$currentLevelIndex]['managers'])) {
                                                $currentApprover = $this->findApprover($dataFlow[$currentLevelIndex]['managers'], $user_empno);
                                                if ($currentApprover) {
                                                    $approval[$currentLevelIndex]['approver'] = $currentApprover;
                                                }
                                            }
                                        }
                                        
                                        // Get recipients list
                                        $returnLevelIndex = $statusFlow[$returnStatus]['level'] - 1;
                                        $recipientsList = [];
                                        
                                        if (isset($dataFlow[$returnLevelIndex])) {
                                            foreach ($dataFlow[$returnLevelIndex]['managers'] as $manager) {
                                                $recipientsList[] = [
                                                    'email' => $manager['email'],
                                                    'name' => $manager['name'],
                                                    'empno' => $manager['empno'],
                                                    'dept' => $manager['dept_code'][0] ?? '',
                                                    'type' => 'manager'
                                                ];
                                                
                                                if (!empty($manager['deputies'])) {
                                                    foreach ($manager['deputies'] as $deputy) {
                                                        if (!empty($deputy['status'])) {
                                                            $recipientsList[] = [
                                                                'email' => $deputy['email'],
                                                                'name' => $deputy['name'],
                                                                'empno' => $deputy['empno'],
                                                                'dept' => $manager['dept_code'][0] ?? '',
                                                                'type' => 'deputy'
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Update MPR record based on request type
                                        if ($isAmendmentRequest) {
                                            $mpr->status_amendent = $returnStatus;
                                        } else {
                                            $mpr->status = $returnStatus;
                                        }
                                        $mpr->approval = json_encode($approval);
                                        $mpr->save();
                                        
                                        // Send email notification
                                        $this->sendReturnEmailNotification($mpr, $req->reason, $returnStatus, $recipientsList, $isAmendmentRequest);
                                        
                                        return response()->json([
                                            'success' => true,
                                            'message' => $isAmendmentRequest ? 'Amendment request returned successfully' : 'Request returned successfully',
                                            'data' => [
                                                'id' => $mpr->id,
                                                'status' => $mpr->status,
                                                'status_amendent' => $mpr->status_amendent,
                                                'approval' => $approval,
                                                'recipients' => $recipientsList
                                                ]
                                            ]);
                                            
                                        } catch (\Exception $e) {
                                            return response()->json([
                                                'success' => false,
                                                'message' => 'Error returning request: ' . $e->getMessage()
                                            ], 500);
                                        }
                                    }
                                    private function sendAcceptEmailNotification($mpr, $nextStatus, $dataFlow, $statusFlow, $currentStatus, $isAmendmentRequest)
                                    {
                                        $submitter = json_decode($mpr->submitter, true);
                                        
                                        // Get position and reason names
                                        $typeApply = TypeApply::where('id', $mpr->pos)->where('loc', $mpr->loc)->first();
                                        $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;
                                        
                                        $ReasonName = RecReason::where('id', $mpr->reason_exp)->where('loc', $mpr->loc)->first();
                                        $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;
                                        
                                        // Get change reason data for amendments
                                        $changeReasonData = null;
                                        if ($isAmendmentRequest && !empty($mpr->change_req)) {
                                            $changeReasonData = $this->getAmendmentChangeReason($mpr);
                                        }
                                        
                                        $templateData = [
                                            'department' => $submitter['dept_sumt'] ?? '',
                                            'requester' => [
                                                'name' => $submitter['name'] ?? '',
                                                'empno' => $submitter['empno'] ?? '',
                                                'position' => $positionName
                                            ],
                                            'request' => [
                                                'id' => $mpr->req_id,
                                                're_id' => $mpr->req_id_change,
                                                'dept' => $submitter['dept'] ?? '',
                                                'div' => $submitter['div'] ?? '',
                                                'unit' => $submitter['unit'] ?? '',
                                                'date' => $mpr->key_in_date,
                                                'qty' => json_decode($mpr->qty_req, true),
                                                'reason' => $typereasonName,
                                                'status' => $nextStatus,
                                                'reason_change' => $changeReasonData,
                                                'remark_change' => $mpr->remark_change
                                                ]
                                            ];
                                            
                                            if (!in_array($nextStatus, ['completed', 'completed'])) {
                                                // Send to next level approvers
                                                $recipientsList = [];
                                                $nextLevelIndex = $statusFlow[$currentStatus]['level'];
                                                
                                                if (isset($dataFlow[$nextLevelIndex])) {
                                                    foreach ($dataFlow[$nextLevelIndex]['managers'] as $manager) {
                                                        $recipientsList[] = [
                                                            'email' => $manager['email'],
                                                            'name' => $manager['name'],
                                                            'empno' => $manager['empno'],
                                                            'dept' => $manager['dept_code'][0] ?? '',
                                                            'type' => 'manager'
                                                        ];
                                                        
                                                        if (!empty($manager['deputies'])) {
                                                            foreach ($manager['deputies'] as $deputy) {
                                                                if (!empty($deputy['status'])) {
                                                                    $recipientsList[] = [
                                                                        'email' => $deputy['email'],
                                                                        'name' => $deputy['name'],
                                                                        'empno' => $deputy['empno'],
                                                                        'dept' => $manager['dept_code'][0] ?? '',
                                                                        'type' => 'deputy'
                                                                    ];
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                $emailList = array_column($recipientsList, 'email');
                                                $templateView = $isAmendmentRequest ? 'VgMpr-approval_amd' : 'VgMpr-approval';
                                                $emailSubject = $isAmendmentRequest 
                                                ? "[MPR] 人員增補需求變更申請通知 / MPR Amendment Request Notification / Thông báo điều chỉnh Phiếu yêu cầu nhân sự"
                                                : "[MPR] 人員增補單簽核通知 / Manpower Request Form Pending Your Approval / Thông báo phê duyệt đơn bổ sung nhân sự";
                                            } else {
                                                // Send completion notification to submitter
                                                $emailList = [$submitter['email']];
                                                $templateView = $isAmendmentRequest ? 'VgMpr-completed_amd' : 'VgMpr-completed';
                                                $emailSubject = $isAmendmentRequest 
                                                ? "[MPR] 人員增補需求變更核准通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã được phê duyệt"
                                                : "[MPR] 人員增補單核准通知 / Đơn xin bổ sung nhân sự đã được phê duyệt";
                                            }
                                            
                                            $emailContent = view($templateView, $templateData)->render();
                                            
                                            $mailData = [
                                                'to' => $emailList,
                                                'subject' => $emailSubject,
                                                'msgBody' => $emailContent,
                                            ];
                                            
                                            $params = [
                                                'target' => $isAmendmentRequest ? 'VG-MPR' : 'VG-MPR',
                                                'body' => $emailContent,
                                                'msg_type' => "m",
                                                'msg_method' => "both",
                                                'msg_subject' => $emailSubject,
                                                'mail_template' => "msgCenterMailTemplate",
                                                'mail_data' => json_encode($mailData),
                                            ];
                                            
                                            $this->sendMsg($params);
                                        }
                                        
                                        private function sendDenialEmailNotification($mpr, $reason, $isAmendmentRequest)
                                        {
                                            $submitter = json_decode($mpr->submitter, true);
                                            
                                            $typeApply = TypeApply::where('id', $mpr->pos)->where('loc', $mpr->loc)->first();
                                            $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;
                                            
                                            $ReasonName = RecReason::where('id', $mpr->reason_exp)->where('loc', $mpr->loc)->first();
                                            $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;
                                            
                                            $changeReasonData = null;
                                            if ($isAmendmentRequest && !empty($mpr->change_req)) {
                                                $changeReasonData = $this->getAmendmentChangeReason($mpr);
                                            }
                                            
                                            $templateData = [
                                                'department' => $submitter['dept_sumt'] ?? '',
                                                'requester' => [
                                                    'name' => $submitter['name'] ?? '',
                                                    'empno' => $submitter['empno'] ?? '',
                                                    'position' => $positionName
                                                ],
                                                'request' => [
                                                    'id' => $mpr->req_id,
                                                    're_id' => $mpr->req_id_change,
                                                    'dept' => $submitter['dept'] ?? '',
                                                    'div' => $submitter['div'] ?? '',
                                                    'unit' => $submitter['unit'] ?? '',
                                                    'date' => $mpr->key_in_date,
                                                    'qty' => json_decode($mpr->qty_req, true),
                                                    'reason' => $typereasonName,
                                                    'reason_change' => $changeReasonData,
                                                    'status' => $isAmendmentRequest ? 'declined' : 'declined',
                                                    'reason_denied' => $reason
                                                    ]
                                                ];
                                                
                                                $templateView = $isAmendmentRequest ? 'VgMpr-denial_amd' : 'VgMpr-denial';
                                                $emailContent = view($templateView, $templateData)->render();
                                                $emailSubject = $isAmendmentRequest 
                                                ? "[MPR] 人員增補需求變更否決通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã bị từ chối"
                                                : "[MPR] 人員增補單否決通知 / Đơn xin bổ sung nhân sự đã bị từ chối";
                                                
                                                $mailData = [
                                                    'to' => [$submitter['email']],
                                                    'subject' => $emailSubject,
                                                    'msgBody' => $emailContent,
                                                ];
                                                
                                                $params = [
                                                    'target' => $isAmendmentRequest ? 'VG-MPR' : 'VG-MPR',
                                                    'body' => $emailContent,
                                                    'msg_type' => "m",
                                                    'msg_method' => "both",
                                                    'msg_subject' => $emailSubject,
                                                    'mail_template' => "msgCenterMailTemplate",
                                                    'mail_data' => json_encode($mailData),
                                                ];
                                                
                                                $this->sendMsg($params);
                                            }
                                            
                                            private function sendReturnEmailNotification($mpr, $reason, $returnStatus, $recipientsList, $isAmendmentRequest)
                                            {
                                                $submitter = json_decode($mpr->submitter, true);
                                                
                                                $typeApply = TypeApply::where('id', $mpr->pos)->where('loc', $mpr->loc)->first();
                                                $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;
                                                
                                                $ReasonName = RecReason::where('id', $mpr->reason_exp)->where('loc', $mpr->loc)->first();
                                                $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;
                                                
                                                $changeReasonData = null;
                                                if ($isAmendmentRequest && !empty($mpr->change_req)) {
                                                    $changeReasonData = $this->getAmendmentChangeReason($mpr);
                                                }
                                                
                                                $templateData = [
                                                    'department' => $submitter['dept_sumt'] ?? '',
                                                    'requester' => [
                                                        'name' => $submitter['name'] ?? '',
                                                        'empno' => $submitter['empno'] ?? '',
                                                        'position' => $positionName
                                                    ],
                                                    'request' => [
                                                        'id' => $mpr->req_id,
                                                        're_id' => $mpr->req_id_change,
                                                        'dept' => $submitter['dept'] ?? '',
                                                        'div' => $submitter['div'] ?? '',
                                                        'unit' => $submitter['unit'] ?? '',
                                                        'date' => $mpr->key_in_date,
                                                        'qty' => json_decode($mpr->qty_req, true),
                                                        'reason' => $typereasonName,
                                                        'status' => $returnStatus,
                                                        'return_reason' => $reason,
                                                        'reason_change' => $changeReasonData,
                                                        'return_to' => $this->getLevelName($returnStatus)
                                                        ]
                                                    ];
                                                    
                                                    $templateView = $isAmendmentRequest ? 'VgMpr-return_amd' : 'VgMpr-return';
                                                    $emailContent = view($templateView, $templateData)->render();
                                                    
                                                    $emailSubject = $isAmendmentRequest
                                                    ? "[MPR] 人員增補需求變更退回通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã bị trả về"
                                                    : "[MPR] 人員增補單退回通知 / Đơn xin bổ sung nhân sự đã bị trả về";
                                                    
                                                    $emailList = array_column($recipientsList, 'email');
                                                    
                                                    $mailData = [
                                                        'to' => $emailList,
                                                        'subject' => $emailSubject,
                                                        'msgBody' => $emailContent,
                                                    ];
                                                    
                                                    $params = [
                                                        'target' => $isAmendmentRequest ? 'VG-MPR' : 'VG-MPR',
                                                        'body' => $emailContent,
                                                        'msg_type' => "m",
                                                        'msg_method' => "both",
                                                        'msg_subject' => $emailSubject,
                                                        'mail_template' => "msgCenterMailTemplate",
                                                        'mail_data' => json_encode($mailData),
                                                    ];
                                                    
                                                    $this->sendMsg($params);
                                                }
                                                private function getLevelName($level) {
                                                    $levels = [
                                                        'waiting_unit' => 'Unit',
                                                        'waiting_dept' => 'Department',
                                                        'waiting_div' => 'Division', 
                                                        'waiting_hr_rec_sf' => 'HR Staff',
                                                        'waiting_hr' => 'HR',
                                                        'waiting_ad' => 'AD',
                                                        'waiting_gm' => 'GM'
                                                    ];
                                                    return $levels[$level] ?? 'Unknown';
                                                }
                                                public function getMprById($id='')
                                                {
                                                    try {
                                                        $mpr = Mpr::find($id);
                                                        
                                                        if (!$mpr) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'MPR record not found'
                                                            ], 404);
                                                        }
                                                        
                                                        return response()->json([
                                                            'success' => true,
                                                            'data' => $mpr
                                                        ]);
                                                        
                                                    } catch (\Exception $e) {
                                                        return response()->json([
                                                            'success' => false,
                                                            'message' => 'Error retrieving MPR record: ' . $e->getMessage()
                                                        ], 500);
                                                    }
                                                }
                                                
                                                public function getLatestReqId(Request $request)
                                                {
                                                    try {
                                                        $year = $request->query('year');
                                                        $month = $request->query('month');
                                                        
                                                        if (!$year || !$month) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'Year and month parameters are required'
                                                            ], 400);
                                                        }
                                                        
                                                        $pattern = "HR-MPR00-{$year}{$month}-%";
                                                        
                                                        $latestRequest = Mpr::where('req_id', 'LIKE', $pattern)
                                                        ->orderBy('req_id', 'desc')
                                                        ->first();
                                                        
                                                        if (!$latestRequest) {
                                                            return response()->json([
                                                                'success' => true,
                                                                'latestNumber' => 0
                                                            ]);
                                                        }
                                                        
                                                        $matches = [];
                                                        if (preg_match('/(\d{4})$/', $latestRequest->req_id, $matches)) {
                                                            $latestNumber = intval($matches[1]);
                                                        } else {
                                                            $latestNumber = 0;
                                                        }
                                                        
                                                        return response()->json([
                                                            'success' => true,
                                                            'latestNumber' => $latestNumber
                                                        ]);
                                                        
                                                    } catch (\Exception $e) {
                                                        return response()->json([
                                                            'success' => false,
                                                            'message' => 'Error getting latest request ID: ' . $e->getMessage()
                                                        ], 500);
                                                    }
                                                }
                                                public function getLatestReqIdChange(Request $request)
                                                {
                                                    try {
                                                        $year = $request->query('year');
                                                        $month = $request->query('month');
                                                        
                                                        if (!$year || !$month) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'Year and month are required'
                                                            ], 400);
                                                        }
                                                        
                                                        $pattern = "HR-MPRA0-{$year}{$month}-%";
                                                        
                                                        $latestRequest = Mpr::where('req_id_change', 'LIKE', $pattern)
                                                        ->orderBy('req_id_change', 'desc')
                                                        ->first();
                                                        
                                                        if (!$latestRequest) {
                                                            return response()->json([
                                                                'latestNumber' => 0
                                                            ]);
                                                        }
                                                        
                                                        $matches = [];
                                                        if (preg_match('/(\d{4})$/', $latestRequest->req_id_change, $matches)) {
                                                            $latestNumber = intval($matches[1]);
                                                        } else {
                                                            $latestNumber = 0;
                                                        }
                                                        
                                                        return response()->json([
                                                            'latestNumber' => $latestNumber
                                                        ]);
                                                        
                                                    } catch (\Exception $e) {
                                                        return response()->json([
                                                            'success' => false,
                                                            'message' => 'Error getting latest request ID: ' . $e->getMessage()
                                                        ], 500);
                                                    }
                                                }
                                                public function updateCancelDate(Request $req)
                                                {
                                                    try {
                                                        $demand = Mpr::find($req->id);
                                                        if (!$demand) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'Demand not found'
                                                            ], 404);
                                                        }
                                                        
                                                        $demand->cancel_date = $req->cancel_date;
                                                        $demand->save();
                                                        
                                                        return response()->json([
                                                            'success' => true,
                                                            'message' => 'Cancel date updated successfully'
                                                        ]);
                                                        
                                                    } catch (\Exception $e) {
                                                        return response()->json([
                                                            'success' => false,
                                                            'message' => 'Error updating cancel date: ' . $e->getMessage()
                                                        ], 500);
                                                    }
                                                }
                                                public function getDataByMprFromRecruitReport($id)
                                                {
                                                    try {
                                                        $manPower = Mpr::find($id);
                                                        
                                                        if (!$manPower) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'ManPower not found'
                                                            ], 404);
                                                        }
                                                        
                                                        return response()->json([
                                                            'success' => true,
                                                            'data' => $manPower
                                                        ]);
                                                    }
                                                    catch (\Exception $e) {
                                                        return response()->json([
                                                            'success' => false,
                                                            'message' => 'Error retrieving data: ' . $e->getMessage()
                                                        ], 500);
                                                    }
                                                }
                                                public function transferCandidates(Request $request)
                                                {
                                                    try {
                                                        $validator = Validator::make($request->all(), [
                                                            'fromId' => 'required|integer',
                                                            'toId' => 'required|integer',
                                                            'candidates' => 'required|array',
                                                            'candidates.*.empno' => 'required|string',
                                                            'candidates.*.name' => 'required|string',
                                                            'candidates.*.date' => 'required|string',
                                                        ]);
                                                        
                                                        if ($validator->fails()) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'errors' => $validator->errors()
                                                            ], 400);
                                                        }
                                                        
                                                        // Get source and target MPR records
                                                        $sourceMpr = Mpr::find($request->fromId);
                                                        $targetMpr = Mpr::find($request->toId);
                                                        
                                                        if (!$sourceMpr || !$targetMpr) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'Source or target MPR not found'
                                                            ], 404);
                                                        }
                                                        
                                                        $sourceCandidates = array_values(json_decode($sourceMpr->candidates ?? '[]', true) ?: []);
                                                        $targetCandidates = array_values(json_decode($targetMpr->candidates ?? '[]', true) ?: []);
                                                        $transferCandidates = array_values($request->candidates);
                                                        
                                                        $sourceCandidates = array_values(array_filter($sourceCandidates, function($candidate) use ($transferCandidates) {
                                                            return !in_array($candidate['empno'], array_column($transferCandidates, 'empno'));
                                                        }));
                                                        
                                                        $targetCandidates = array_values(array_merge($targetCandidates, $transferCandidates));
                                                        
                                                        $sourceQtyReq = json_decode($sourceMpr->qty_req, true);
                                                        $totalSourceQty = array_sum(array_map('intval', array_filter($sourceQtyReq)));
                                                        $sourceMpr->recruited = count($sourceCandidates);
                                                        $sourceMpr->stocknumber = $totalSourceQty - $sourceMpr->recruited;
                                                        $sourceMpr->fulfilled = $sourceMpr->stocknumber <= 0 ? 'yes' : 'no';
                                                        $sourceMpr->candidates = json_encode(array_values($sourceCandidates));
                                                        
                                                        if ($sourceMpr->stocknumber > 0) {
                                                            $sourceMpr->fullfilldate = null;
                                                            $sourceMpr->fulfilled = 'no';
                                                        } else if ($sourceMpr->stocknumber <= 0 && empty($sourceMpr->fullfilldate)) {
                                                            $sourceMpr->fullfilldate = date('Y-m-d');
                                                            $sourceMpr->fulfilled = 'yes';
                                                        }
                                                        
                                                        $targetQtyReq = json_decode($targetMpr->qty_req, true);
                                                        $totalTargetQty = array_sum(array_map('intval', array_filter($targetQtyReq)));
                                                        $targetMpr->recruited = count($targetCandidates);
                                                        $targetMpr->stocknumber = $totalTargetQty - $targetMpr->recruited;
                                                        $targetMpr->fulfilled = $targetMpr->stocknumber <= 0 ? 'yes' : 'no';
                                                        $targetMpr->candidates = json_encode(array_values($targetCandidates));
                                                        
                                                        // Update target fullfilldate based on new status
                                                        if ($targetMpr->stocknumber > 0) {
                                                            $targetMpr->fullfilldate = null;
                                                            $targetMpr->fulfilled = 'no';
                                                        } else if ($targetMpr->stocknumber <= 0 && empty($targetMpr->fullfilldate)) {
                                                            // Only set fullfilldate if becoming fulfilled for the first time
                                                            $targetMpr->fullfilldate = date('Y-m-d');
                                                            $targetMpr->fulfilled = 'yes';
                                                        }
                                                        
                                                        // Save both records
                                                        $sourceMpr->save();
                                                        $targetMpr->save();
                                                        
                                                        return response()->json([
                                                            'success' => true,
                                                            'message' => 'Candidates transferred successfully',
                                                            'data' => [
                                                                'source' => $sourceMpr,
                                                                'target' => $targetMpr
                                                                ]
                                                            ]);
                                                            
                                                        } catch (\Exception $e) {
                                                            return response()->json([
                                                                'success' => false,
                                                                'message' => 'Error transferring candidates: ' . $e->getMessage()
                                                            ], 500);
                                                        }
                                                    }
                                                    
                                                    public static function getFilteredRequests($userData, $includeDeputy = true) 
                                                    {
                                                        if (!isset($userData['empno'])) {
                                                            return [
                                                                'success' => false,
                                                                'message' => 'Missing employee number',
                                                                'data' => collect([])
                                                            ];
                                                        }
                                                        
                                                        $params = new Request([
                                                            'empno' => $userData['empno'],
                                                            'app_code' => 'MPR',
                                                            'location' => 'VG',
                                                            'custom_data' => json_encode([
                                                                'custom_dept' => $userData['dept'],
                                                                'custom_division' => $userData['high_dept'],
                                                            ]),
                                                        ]);
                                                        
                                                        $controller = new self();
                                                        $flowResponse = $controller->getDataFlowForItem($params)->getData();
                                                        $flowData = json_decode(json_encode($flowResponse->flow_data ?? []), true);
                                                        
                                                        if (!$flowData) {
                                                            return [
                                                                'success' => false,
                                                                'message' => 'No flow data found',
                                                                'data' => collect([])
                                                            ];
                                                        }
                                                        
                                                        $requests = Mpr::whereNotIn('status', ['completed', 'declined'])
                                                        ->orderBy('id', 'desc')
                                                        ->get();
                                                        // Build a map of departments the user has access to
                                                        $userAccessibleDepts = [];
                                                        foreach ($flowData as $level) {
                                                            if (!empty($level['managers'])) {
                                                                foreach ($level['managers'] as $manager) {
                                                                    // Direct manager access
                                                                    if ($manager['empno'] === $userData['empno'] && !empty($manager['dept_code'])) {
                                                                        $userAccessibleDepts = array_merge($userAccessibleDepts, $manager['dept_code']);
                                                                    }
                                                                    
                                                                    // Deputy access
                                                                    if ($includeDeputy && !empty($manager['deputies'])) {
                                                                        foreach ($manager['deputies'] as $deputy) {
                                                                            if ($deputy['empno'] === $userData['empno'] && 
                                                                            !empty($deputy['status']) && 
                                                                            $deputy['status'] === true) {
                                                                                $userAccessibleDepts = array_merge($userAccessibleDepts, $manager['dept_code']);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        $userAccessibleDepts = array_unique($userAccessibleDepts);
                                                        
                                                        $filteredRequests = $requests->filter(function($request) use ($userData, $userAccessibleDepts) {
                                                            if (!$request->approval || !$request->submitter) {
                                                                return false;
                                                            }
                                                            
                                                            $submitter = json_decode($request->submitter, true);
                                                            $itemDept = isset($submitter['dept_sumt']) ? trim($submitter['dept_sumt']) : '';
                                                            
                                                            // Case 1: User created the request
                                                            if ($submitter['empno'] === $userData['empno']) {
                                                                return true;
                                                            }
                                                            
                                                            // Case 2: User has access to the department (either as manager or deputy)
                                                            return in_array($itemDept, $userAccessibleDepts);
                                                        });
                                                        
                                                        return [
                                                            'success' => true,
                                                            'message' => 'Requests filtered successfully',
                                                            'data' => $filteredRequests->values()
                                                        ];
                                                    }
                                                    
                                                    public function countResignedCandidates()
                                                    {
                                                        try {
                                                            $results = [];
                                                            
                                                            // Get all MPRs with candidates data
                                                            $mprs = Mpr::whereNotNull('candidates')
                                                            ->where('candidates', '!=', '')
                                                            ->get();
                                                            
                                                            foreach ($mprs as $mpr) {
                                                                $candidates = json_decode($mpr->candidates, true);
                                                                
                                                                if (!$candidates || empty($candidates)) {
                                                                    $mpr->resign = 0;
                                                                    $mpr->resignList = null;
                                                                    $mpr->save();
                                                                    $results[] = [
                                                                        'mpr_id' => $mpr->id,
                                                                        'req_id' => $mpr->req_id,
                                                                        'status' => 'success',
                                                                        'message' => 'No candidates found',
                                                                        'resigned_count' => 0
                                                                    ];
                                                                    continue;
                                                                }
                                                                
                                                                $candidateEmpnos = array_column($candidates, 'empno');
                                                                $resignedEmployees = ResignData::whereIn('er_empid', $candidateEmpnos)->get();
                                                                $resignedCount = $resignedEmployees->count();
                                                                
                                                                // Only create resignList if there are resigned employees
                                                                if ($resignedCount > 0) {
                                                                    $resignList = [];
                                                                    foreach ($candidates as $candidate) {
                                                                        $resignData = $resignedEmployees->firstWhere('er_empid', $candidate['empno']);
                                                                        if ($resignData) {
                                                                            $resignList[] = [
                                                                                'empno' => $candidate['empno'],
                                                                                'date' => $resignData->er_resigndate,
                                                                                'name' => $candidate['name'],
                                                                                'key_in' => $candidate['key_in'],
                                                                                'position' => $mpr->pos // Lấy trực tiếp pos từ Mpr
                                                                            ];
                                                                        }
                                                                    }
                                                                    $mpr->resignList = json_encode($resignList);
                                                                } else {
                                                                    $mpr->resignList = null;
                                                                }
                                                                
                                                                $mpr->resign = $resignedCount;
                                                                $mpr->save();
                                                                
                                                                $results[] = [
                                                                    'mpr_id' => $mpr->id,
                                                                    'req_id' => $mpr->req_id,
                                                                    'status' => 'success',
                                                                    'resigned_count' => $resignedCount,
                                                                    'resigned_employees' => $resignedEmployees->pluck('er_empid')->toArray()
                                                                ];
                                                            }
                                                            
                                                            return response()->json([
                                                                'status' => 'success',
                                                                'message' => 'Processed ' . count($results) . ' MPR records',
                                                                'data' => $results
                                                            ], 200);
                                                            
                                                        } catch (\Exception $e) {
                                                            return response()->json([
                                                                'status' => 'error', 
                                                                'message' => $e->getMessage()
                                                            ], 500);
                                                        }
                                                    }
                                                    private function processAutoCloseEmail($mpr)
                                                    {
                                                        try {
                                                            $submitter = json_decode($mpr->submitter, true);
                                                            
                                                            // Get position details from TypeApply
                                                            $typeApply = TypeApply::where('id', $mpr->pos)
                                                            ->where('loc', $mpr->loc)
                                                            ->first();
                                                            $position = $typeApply ? $typeApply->type_name : '';
                                                            
                                                            // Parse position name for different languages
                                                            $positionNames = [
                                                                'cn' => '',
                                                                'vi' => '',
                                                                'en' => ''
                                                            ];
                                                            
                                                            if ($position) {
                                                                try {
                                                                    $positionObj = json_decode($position, true);
                                                                    $positionNames['cn'] = $positionObj['cn'] ?? $positionObj['en'] ?? $position;
                                                                    $positionNames['vi'] = $positionObj['vi'] ?? $positionObj['en'] ?? $position;
                                                                    $positionNames['en'] = $positionObj['en'] ?? $position;
                                                                } catch (\Exception $e) {
                                                                    // Fallback to original value for all languages
                                                                    $positionNames['cn'] = $position;
                                                                    $positionNames['vi'] = $position;
                                                                    $positionNames['en'] = $position;
                                                                }
                                                            }
                                                            
                                                            // Calculate quantity details
                                                            $qtyReq = json_decode($mpr->qty_req, true);
                                                            $totalQty = 0;
                                                            if ($qtyReq) {
                                                                $totalQty = array_sum(array_filter([
                                                                    $qtyReq['workers'] ?? 0,
                                                                    $qtyReq['staff'] ?? 0,
                                                                    $qtyReq['staff_lang'] ?? 0,
                                                                    $qtyReq['engineers'] ?? 0,
                                                                    $qtyReq['leader'] ?? 0,
                                                                    $qtyReq['tranlator'] ?? 0
                                                                ]));
                                                            }
                                                            
                                                            $templateData = [
                                                                'requester' => [
                                                                    'name' => $submitter['name'] ?? '',
                                                                    'empno' => $submitter['empno'] ?? '',
                                                                    'email' => $submitter['email'] ?? ''
                                                                ],
                                                                'request' => [
                                                                    'id' => $mpr->req_id,
                                                                    'unit' => $submitter['unit'] ?? '',
                                                                    'position' => $positionNames, // Truyền object chứa tất cả ngôn ngữ
                                                                    'stocknumber' => $mpr->stocknumber ?? $totalQty,
                                                                    'recruited' => $mpr->recruited ?? 0,
                                                                    'hold_date' => $mpr->hold_date
                                                                    ]
                                                                ];
                                                                
                                                                $emailContent = view('vgMpr-autoClose', ['templateData' => $templateData])->render();
                                                                
                                                                $emailSubject = "[MPR] 人員增補申請自動結案通知 / MPR Auto Close Notification / Thông báo tự động đóng yêu cầu nhân sự";
                                                                
                                                                $mailData = [
                                                                    'to' => [$submitter['email']],
                                                                    'subject' => $emailSubject,
                                                                    'msgBody' => $emailContent,
                                                                ];
                                                                
                                                                $params = [
                                                                    'target' => 'VG-MPR',
                                                                    'body' => $emailContent,
                                                                    'msg_type' => "m",
                                                                    'msg_method' => "both",
                                                                    'msg_subject' => $emailSubject,
                                                                    'mail_template' => "msgCenterMailTemplate",
                                                                    'mail_data' => json_encode($mailData),
                                                                ];
                                                                
                                                                $this->sendMsg($params);
                                                                
                                                                return true;
                                                            } catch (\Exception $e) {
                                                                \Log::error('Error sending auto close email: ' . $e->getMessage());
                                                                return false;
                                                            }
                                                        }
                                                        public function sendAutoCloseEmail(Request $request)
                                                        {
                                                            try {
                                                                $mpr = Mpr::find($request->id);
                                                                if (!$mpr) {
                                                                    return response()->json([
                                                                        'success' => false,
                                                                        'message' => 'MPR record not found'
                                                                    ], 404);
                                                                }
                                                                $emailSent = $this->processAutoCloseEmail($mpr);
                                                                return response()->json([
                                                                    'success' => $emailSent,
                                                                    'message' => $emailSent ? 'Auto close notification sent successfully' : 'Failed to send notification'
                                                                ]);
                                                                
                                                            } catch (\Exception $e) {
                                                                return response()->json([
                                                                    'success' => false,
                                                                    'message' => 'Error sending auto close notification: ' . $e->getMessage()
                                                                ], 500);
                                                            }
                                                        }
                                                        public function updateMprCandidates()
                                                        {
                                                            try {
                                                                // Get all registered candidates
                                                                $candidates = EmpnoInfo::where('reg', 'yes')
                                                                ->whereNotNull('emp_demandid')
                                                                ->get();
                                                                
                                                                // Group candidates by emp_demandid
                                                                $groupedCandidates = $candidates->groupBy('emp_demandid');
                                                                
                                                                foreach ($groupedCandidates as $demandId => $candidateGroup) {
                                                                    // Find corresponding MPR record
                                                                    $mpr = Mpr::find($demandId);
                                                                    
                                                                    if (!$mpr) {
                                                                        continue;
                                                                    }
                                                                    
                                                                    // Transform candidates data into required format
                                                                    $formattedCandidates = $candidateGroup->map(function($candidate) {
                                                                        return [
                                                                            'empno' => $candidate->newEmpno,
                                                                            'date' => $candidate->act_en_date,
                                                                            'name' => $candidate->candidate_name,
                                                                            'key_in' => $candidate->key_in_date,
                                                                            'position' => $candidate->position
                                                                        ];
                                                                    })->values()->toArray();
                                                                    
                                                                    // Update MPR candidates field
                                                                    $mpr->candidates = json_encode($formattedCandidates);
                                                                    
                                                                    // Update recruited count
                                                                    $mpr->recruited = count($formattedCandidates);
                                                                    
                                                                    // Calculate and update stocknumber
                                                                    $qtyReq = json_decode($mpr->qty_req, true);
                                                                    $totalQty = array_sum(array_filter($qtyReq));
                                                                    $mpr->stocknumber = $totalQty - $mpr->recruited;
                                                                    
                                                                    // Update fulfilled status
                                                                    $mpr->fulfilled = $mpr->stocknumber <= 0 ? 'yes' : 'no';
                                                                    
                                                                    // Set fullfilldate if becoming fulfilled
                                                                    if ($mpr->stocknumber <= 0 && empty($mpr->fullfilldate)) {
                                                                        $mpr->fullfilldate = date('Y-m-d');
                                                                    }
                                                                    
                                                                    $mpr->save();
                                                                }
                                                                
                                                                return response()->json([
                                                                    'success' => true,
                                                                    'message' => 'MPR candidates updated successfully',
                                                                    'updated_count' => $groupedCandidates->count()
                                                                ]);
                                                                
                                                            } catch (\Exception $e) {
                                                                return response()->json([
                                                                    'success' => false,
                                                                    'message' => 'Error updating MPR candidates: ' . $e->getMessage()
                                                                ], 500);
                                                            }
                                                        }
                                                        
                                                    }
                                                    