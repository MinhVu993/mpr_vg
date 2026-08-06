<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\hrmDepartmentMdl as hrmDept;
use App\globalDeptMdl as DeptCode;
use App\vgMprLangMdl as Lang;
use App\vgMprWpsMdl as Wps;
use App\vgMprResignTrackingMdl as ResignTracking;
use App\vgMprMdl as Mpr;
use App\vgMprMdlTemp as MprTemp;
use App\vgMprWorkExpMdl as WorkExp;
use App\vgMprTypeApplyMdl as TypeApply;
use App\vgMprEduReqMdl as EduReq;
use App\vgMprSubPosMdl as SubPos;
use App\vgMprChangeReasonMdl as ChangeReason;
use App\vgMprRecReasonMdl as RecReason;
use App\vgMprOtherReasonMdl as OtherReason;
use App\vgMprRecReasonTempMdl as RecReasonTemp;
use App\vgMprMdlEndYear as MprEndYear;
use App\vgRecruitingReportResignDataMdl as ResignData;
use App\vgRecruitingReportEmpnoInfoMdl as EmpnoInfo;
use App\vgRecruitingReportDeptCodeMdl as nameDeptCode;
use App\vgRecruitingReportDemandMdl as Demand;
use App\vgRecruitingReportDutyHrmMdl as DutyHrm;
use App\vgMprUserAccessLogMdl as UserAccessLog;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class vgMprCtrl extends Controller
{
    private function getResignData($loc)
    {
        return ResignData::select("er_empid")
            ->where("loc", $loc)
            ->orderBy("id", "desc")
            ->get();
    }

    private function getRecruitedData($loc)
    {
        return EmpnoInfo::select("tmp_departid")
            ->where("loc", $loc)
            ->orderBy("id", "desc")
            ->get();
    }
    public function getReqId(Request $req)
    {
        $div = $req->input("div");
        $status = $req->input("status");

        $data = Mpr::select("req_id")
            ->where("status", $status)
            ->where("fulfilled", "no")
            ->whereRaw('JSON_EXTRACT(submitter, "$.high_dept") = ?', [$div])
            ->where(function ($query) use ($status) {
                $query
                    ->whereNull("status_amendent")
                    ->orWhere("status_amendent", "")
                    ->orWhere("status_amendent", $status);
            })
            ->where(function ($query) {
                $query->whereNull("deny_temp")->orWhere("deny_temp", "");
            })
            ->orderBy("id", "desc")
            ->get();

        return $data;
    }
    public function getDataByReqId($req_id)
    {
        try {
            if (!$req_id) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Request ID is required",
                    ],
                    400
                );
            }

            $mpr = Mpr::where("req_id", $req_id)
                ->where("status", "completed")
                ->first();

            if (!$mpr) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "No record found for given Request ID",
                    ],
                    404
                );
            }
            $data = $mpr->toArray();

            return response()->json([
                "success" => true,
                "data" => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error retrieving data: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function getChangeReasonData()
    {
        $ChangeData = ChangeReason::orderBy("id", "asc")->get();
        return [
            "ChangeData" => $ChangeData,
        ];
    }
    public function getAllDataMpr(Request $req)
    {
        $loc = $req->input("loc");
        $dept = $req->input("dept");
        $empno = $req->input("empno");
        $high_dept = $req->input("high_dept");
        $role = $req->input("role");
        $group_empno = $req->input("group_empno");

        $dataMprQuery = Mpr::where("loc", $loc)->orderBy("id", "desc");
        $dataMpr = $dataMprQuery->get();

        $dataResign = $this->getResignData($loc);
        $resignEmpIds = collect($dataResign)
            ->pluck("er_deptid")
            ->toArray();

        $recruited = $this->getRecruitedData($loc);
        $recruitedData = collect($recruited)
            ->pluck("tmp_departid")
            ->toArray();

        if ($role === "admin" || $role === "review") {
            // Return all data for admin/review roles
        } elseif ($empno && $dept) {
            $params = new Request([
                "empno" => $empno,
                "app_code" => $app_code,
                "location" => "VG",
                "custom_data" => json_encode([
                    "custom_dept" => $dept,
                    "custom_division" => $high_dept,
                ]),
            ]);
            $flowResponse = $this->getDataFlowForItem($params)->getData();
            $flowData = json_decode(
                json_encode($flowResponse->flow_data ?? []),
                true
            );

            // Hàm helper trích xuất dept/division mà một empno có quyền truy cập từ flowData
            $extractAccessFromFlow = function ($flowData, $searchEmpno) {
                $accessibleDepts = [];
                $divisions = [];
                foreach ($flowData as $level) {
                    if (!empty($level["managers"])) {
                        foreach ($level["managers"] as $manager) {
                            if (strcasecmp($manager["empno"], $searchEmpno) === 0) {
                                if (!empty($manager["dept_code"])) {
                                    $accessibleDepts = array_merge($accessibleDepts, $manager["dept_code"]);
                                }
                                if (!empty($manager["division_code"])) {
                                    $divisions[] = $manager["division_code"];
                                }
                            }
                            if (!empty($manager["deputies"])) {
                                foreach ($manager["deputies"] as $deputy) {
                                    if (
                                        strcasecmp($deputy["empno"], $searchEmpno) === 0 &&
                                        !empty($deputy["status"]) &&
                                        $deputy["status"] === true
                                    ) {
                                        if (!empty($manager["dept_code"])) {
                                            $accessibleDepts = array_merge($accessibleDepts, $manager["dept_code"]);
                                        }
                                        if (!empty($manager["division_code"])) {
                                            $divisions[] = $manager["division_code"];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return [
                    'depts'     => array_unique($accessibleDepts),
                    'divisions' => array_unique($divisions),
                ];
            };

            if ($empno && $flowData) {
                // Thử tìm với empno trước
                $access = $extractAccessFromFlow($flowData, $empno);
                $userAccessibleDepts = $access['depts'];
                $userDivisions       = $access['divisions'];

                // Nếu không tìm thấy empno trong flow → fallback sang group_empno
                $activeEmpno = $empno;
                if (empty($userAccessibleDepts) && empty($userDivisions) && !empty($group_empno)) {
                    \Log::info("getAllDataMpr: empno [{$empno}] không tìm thấy trong flow, fallback sang group_empno [{$group_empno}]");

                    // Resolve lại flow với group_empno
                    $groupParams = new Request([
                        "empno"       => $group_empno,
                        "app_code"    => "mpr",
                        "location"    => "VG",
                        "custom_data" => json_encode([
                            "custom_dept"     => $dept,
                            "custom_division" => $high_dept,
                        ]),
                    ]);
                    $groupFlowResponse = $this->getDataFlowForItem($groupParams)->getData();
                    $groupFlowData = json_decode(
                        json_encode($groupFlowResponse->flow_data ?? []),
                        true
                    );

                    if (!empty($groupFlowData)) {
                        $flowData    = $groupFlowData;
                        $activeEmpno = $group_empno;
                        $access      = $extractAccessFromFlow($flowData, $group_empno);
                        $userAccessibleDepts = $access['depts'];
                        $userDivisions       = $access['divisions'];
                    }
                }

                $dataMpr = $dataMpr
                    ->filter(function ($item) use (
                        $activeEmpno,
                        $flowData,
                        $high_dept,
                        $dept,
                        $userAccessibleDepts,
                        $userDivisions
                    ) {
                        $submitter = json_decode($item->submitter, true);
                        $itemDept = $submitter["dept"] ?? null;

                        // Case 1: User created the request
                        if (
                            isset($submitter["div"]) &&
                            $submitter["div"] === $high_dept
                        ) {
                            return true;
                        }

                        // Case 2: Check division access
                        $itemDiv = isset($submitter["div"])
                            ? trim($submitter["div"])
                            : "";
                        if (
                            !empty($itemDiv) &&
                            in_array($itemDiv, $userDivisions)
                        ) {
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
                            "waiting_dept"     => 0,
                            "waiting_div"      => 1,
                            "waiting_hr_rec_sf" => 2,
                            "waiting_hr"       => 3,
                            "waiting_ad"       => 4,
                            "waiting_gm"       => 5,
                        ];

                        foreach ($statusesToCheck as $status) {
                            if (isset($statusToLevel[$status])) {
                                $currentLevel = $statusToLevel[$status];
                                $currentFlow  = $flowData[$currentLevel] ?? null;

                                if ($currentFlow && !empty($currentFlow["managers"])) {
                                    foreach ($currentFlow["managers"] as $manager) {
                                        if (
                                            $currentLevel >= 2 &&
                                            strcasecmp($manager["empno"], $activeEmpno) === 0
                                        ) {
                                            return true;
                                        }

                                        // For lower levels, check department access
                                        if ($currentLevel < 2) {
                                            if (
                                                strcasecmp($manager["empno"], $activeEmpno) === 0 &&
                                                !empty($manager["dept_code"]) &&
                                                in_array($itemDept, $manager["dept_code"])
                                            ) {
                                                return true;
                                            }
                                        }

                                        // Check deputies
                                        if (!empty($manager["deputies"])) {
                                            foreach ($manager["deputies"] as $deputy) {
                                                if (
                                                    $currentLevel >= 2 &&
                                                    strcasecmp($deputy["empno"], $activeEmpno) === 0 &&
                                                    !empty($deputy["status"]) &&
                                                    $deputy["status"] === true
                                                ) {
                                                    return true;
                                                }

                                                if (
                                                    $currentLevel < 2 &&
                                                    strcasecmp($deputy["empno"], $activeEmpno) === 0 &&
                                                    !empty($deputy["status"]) &&
                                                    $deputy["status"] === true &&
                                                    !empty($manager["dept_code"]) &&
                                                    in_array($itemDept, $manager["dept_code"])
                                                ) {
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        return false;
                    })
                    ->values();
            }
        } elseif ($dept && $role) {
            $dataMpr = $dataMpr
                ->filter(function ($mpr) use ($dept, $role) {
                    $submitter = json_decode($mpr->submitter, true);
                    return isset($submitter["div"]) &&
                        strtolower(trim($submitter["div"])) ===
                        strtolower(trim($dept));
                })
                ->values();
        }

        return response()->json([
            "dataMpr" => $dataMpr,
        ]);
    }

    public function getDataModel(Request $req)
    {
        $loc = $req->input("loc");
        if (!$loc) {
            return response()->json(["error" => "Location not provided"], 400);
        }
        $dataRecReason = RecReason::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataRecReasonTemp = RecReasonTemp::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataDeptCode = DeptCode::orderBy("id", "desc")->get();
        $dataWorkExp = WorkExp::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataTypeApply = TypeApply::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataSubPos = SubPos::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataEduReq = EduReq::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataLang = Lang::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataWps = Wps::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataDeptHrm = nameDeptCode::orderBy("id", "asc")->get();
        $dataDutyHrm = DutyHrm::orderBy("id", "asc")->get();
        $dataOtherReason = OtherReason::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $dataEndYear = MprEndYear::where("loc", $loc)
            ->orderBy("id", "asc")
            ->get();
        $model = [
            "dataRecReason" => $dataRecReason,
            "dataDeptCode" => $dataDeptCode,
            "dataWorkExp" => $dataWorkExp,
            "dataTypeApply" => $dataTypeApply,
            "dataSubPos" => $dataSubPos,
            "dataEduReq" => $dataEduReq,
            "dataLang" => $dataLang,
            "dataWps" => $dataWps,
            "dataDeptHrm" => $dataDeptHrm,
            "dataDutyHrm" => $dataDutyHrm,
            "dataOtherReason" => $dataOtherReason,
            "dataRecReasonTemp" => $dataRecReasonTemp,
            "dataEndYear" => $dataEndYear,
        ];
        return response()->json($model);
    }
    private function handleChangeHistory($existingMpr, $request)
    {
        $historyFields = [
            "change_req",
            "either_change",
            "english_change",
            "chinese_change",
            "computerSkills_change",
            "remark_change",
            "tranlator_change",
        ];

        $updatedData = [];

        foreach ($historyFields as $field) {
            if ($request->has($field) && $request->get($field)) {
                $currentValue = $request->get($field);
                $existingHistory = [];

                // Get existing history from DB
                if ($existingMpr && $existingMpr->$field) {
                    try {
                        $existingHistory = json_decode(
                            $existingMpr->$field,
                            true
                        );
                        // Ensure it's an array
                        $existingHistory = is_array($existingHistory)
                            ? $existingHistory
                            : [$existingHistory];
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
                    }
                }
                $existingHistory[] = $currentValue;

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
                "req_id" => "required",
                "key_in_date" => "required|date",
                "submitter" => "required",
                "supp_type" => "required",
                "edu" => "required",
                "w_exp" => "required",
                "reason_exp" => "required",
                "date_req" => "required|date",
                "qty_req" => "required",
                "jd" => "required",
                "wps" => "required",
                "pos" => "required",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    ["success" => false, "errors" => $validator->errors()],
                    400
                );
            }

            $existingMpr = Mpr::where("req_id", $request->req_id)->first();
            $isAmendment = $request->input("is_amendment") === "true";

            // Xử lý file
            $att_jd = null;
            $att_dis = null;
            if ($request->hasFile("jobDescFile")) {
                $file = $request->file("jobDescFile");
                $filename = $file->getClientOriginalName();
                $file->storeAs("vg-mpr", $filename);
                $att_jd = $filename;
            }
            if ($request->hasFile("dimissionFile")) {
                $file = $request->file("dimissionFile");
                $filename = $file->getClientOriginalName();
                $file->storeAs("vg-mpr", $filename);
                $att_dis = $filename;
            }

            $mpr = Mpr::where("req_id", $request->req_id)->first();
            $recruited = $mpr ? $mpr->recruited : 0;
            $stocknumber = $request->total_qty - $recruited;
            $historyData = $this->handleChangeHistory($existingMpr, $request);

            $activeRct = $request->input("activeRct");
            // --- XỬ LÝ LƯU LOG NGƯỜI DÙNG ---
            // Giải mã thông tin người đang thao tác (được gửi lên trong request->submitter)
            $currentUserData = json_decode($request->submitter, true);
            $actionType = "create";

            if ($existingMpr) {
                if (
                    isset($activeRct) &&
                    $activeRct !== "" &&
                    $activeRct !== null
                ) {
                    $actionType = "process_new_year"; // Hành động xử lý năm mới
                } elseif ($isAmendment) {
                    $actionType = "amendment"; // Hành động điều chỉnh
                } else {
                    $actionType = "update"; // Hành động cập nhật thường
                }
            }

            UserAccessLog::create([
                "empno" => $currentUserData["empno"] ?? "Unknown", // Người đang thực hiện
                "ip_address" => $request->ip(),
                "user_agent" => $request->header("User-Agent"),
                "action" => $actionType . " - ReqID: " . $request->req_id,
            ]);
            //  -- KẾT THÚC XỬ LÝ LƯU LOG NGƯỜI DÙNG ---
            $submitterToSave = $request->submitter;

            if (
                $existingMpr &&
                isset($activeRct) &&
                $activeRct !== "" &&
                $activeRct !== null
            ) {
                $submitterToSave = $existingMpr->submitter;
            }
            $data = [
                "req_id" => $request->req_id,
                "supp_type" => $request->supp_type,
                "key_in_date" => $request->key_in_date,
                "submitter" => $submitterToSave,
                "edu" => $request->edu,
                "spec" => $request->spec,
                "w_exp" => $request->w_exp,
                "reason_exp" => $request->reason_exp,
                "old_emp" => $request->old_emp,
                "date_req" => $request->date_req,
                "qty_req" => $request->qty_req,
                "stocknumber" => $stocknumber,
                "jd" => $request->jd,
                "att_jd" => $att_jd ?? $request->att_jd,
                "att_dis" => $att_dis ?? $request->att_dis,
                "eng_skill" => $request->eng_skill,
                "chn_skill" => $request->chn_skill,
                "either_skill" => $request->either_skill,
                "comp_skill" => $request->comp_skill,
                "wps" => $request->wps,
                "remark" => $request->remark,
                "pos" => $request->pos,
                "sub_pos" => $request->sub_pos,
                "loc" => $request->loc,
                "isOther_reason" => $request->isOther_reason,
                "tranlator" => $request->tranlator,
                "tranlator_change" => $request->tranlator_change,
                "hold" => $request->hold,
                "hold_date" => $request->hold_date,
                "resumed" => $request->resumed,
                "resumed_date" => $request->resumed_date,
                "req_id_change" => $request->req_id_change,
                "change_req" => $request->change_req,
                "either_change" => $request->either_change,
                "english_change" => $request->english_change,
                "chinese_change" => $request->chinese_change,
                "computerSkills_change" => $request->computerSkills_change,
                "remark_change" => $request->remark_change,
                "activeRct" => $activeRct,
            ];

            if ($request->has("deny_temp") && $request->get("deny_temp")) {
                $data["deny_temp"] = $request->get("deny_temp");
            }
            if (isset($activeRct) && $activeRct !== "" && $activeRct !== null) {
                $data["status_endyear"] = "waiting_dept";
                $endYearApproval = [
                    [
                        "level" => 1,
                        "type" => "dept",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "waiting",
                    ],
                    [
                        "level" => 2,
                        "type" => "div",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "",
                    ],
                ];
                $data["approval"] = json_encode($endYearApproval);
            }
            // 2. Đơn Điều Chỉnh (Amendment)
            elseif ($isAmendment) {
                $data["status_amendent"] =
                    $request->status_amendent ?: "waiting_dept";
                $data["status"] = $existingMpr->status; // Giữ nguyên status gốc

                $amendmentApproval = [
                    [
                        "level" => 1,
                        "type" => "dept",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "waiting",
                    ],
                    [
                        "level" => 2,
                        "type" => "div",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "",
                    ],
                    [
                        "level" => 3,
                        "type" => "hr_rec_sf",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "",
                    ],
                    [
                        "level" => 4,
                        "type" => "hr",
                        "approver" => [
                            "empno" => "",
                            "name" => "",
                            "dept" => "",
                        ],
                        "status" => "",
                    ],
                ];
                $data["approval"] = json_encode($amendmentApproval);
            }
            // 3. Đơn Thường
            else {
                $data["status"] = $request->status;
                $data["approval"] = $request->approval;
                $data["status_amendent"] = null;
            }

            $data = array_merge($data, $historyData);

            // --- XỬ LÝ LƯU LỊCH SỬ (LOGS) ---
            // Quan trọng: Dữ liệu approval cũ sẽ được đẩy vào logs tại đây trước khi bị ghi đè bởi mảng mới
            if ($existingMpr) {
                $currentApproval = $existingMpr->approval;
                if (!empty($currentApproval)) {
                    $logsArray = [];
                    if (!empty($existingMpr->logs)) {
                        $existingLogs = json_decode($existingMpr->logs, true);
                        if (
                            isset($existingLogs[0]) &&
                            is_array($existingLogs[0]) &&
                            isset($existingLogs[0][0])
                        ) {
                            $logsArray = $existingLogs;
                        } else {
                            $logsArray = [$existingLogs];
                        }
                    }
                    $logsArray[] = json_decode($currentApproval, true);
                    $data["logs"] = json_encode($logsArray);
                } else {
                    $data["logs"] = $existingMpr->logs;
                }
            } else {
                $data["logs"] = null;
            }

            $mpr = Mpr::updateOrCreate(["req_id" => $request->req_id], $data);

            if ($request->emailList) {
                $this->sendEmailNotification($mpr, $request, $isAmendment);
            }

            return response()->json([
                "success" => true,
                "message" => "Request saved successfully",
                "data" => [
                    "id" => $mpr->id,
                    "status" => $mpr->status,
                    "status_amendent" => $mpr->status_amendent,
                    "status_endyear" => $mpr->status_endyear,
                    "approval" => $data["approval"], // Trả về approval mới để check
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in saveManpower:", [
                "error" => $e->getMessage(),
            ]);
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500
            );
        }
    }
    private function getAmendmentChangeReason($mpr)
    {
        if (empty($mpr->change_req)) {
            return null;
        }
        try {
            $changeReqData = json_decode($mpr->change_req, true);
            if (!is_array($changeReqData) || empty($changeReqData)) {
                return null;
            }
            $lastChangeReq = end($changeReqData);

            if (
                !isset($lastChangeReq["change_reason"]) ||
                !is_array($lastChangeReq["change_reason"]) ||
                !isset($lastChangeReq["change_reason"]["id"])
            ) {
                return null;
            }
            $changeReasonId = (int) $lastChangeReq["change_reason"]["id"];
            if ($changeReasonId <= 0) {
                return null;
            }
            $changeReason = ChangeReason::find($changeReasonId);
            if (!$changeReason) {
                return null;
            }
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
                "cn" => $reasonRaw["cn"] ?? "",
                "en" => $reasonRaw["en"] ?? "",
                "vi" => $reasonRaw["vi"] ?? "",
            ];
        } catch (\Exception $e) {
            \Log::error(
                "Error getting amendment change reason: " . $e->getMessage()
            );
            return null;
        }
    }
    private function sendEmailNotification($mpr, $request, $isAmendment)
    {
        $submitter = json_decode($mpr->submitter, true);

        // Get position and reason names
        $typeApply = TypeApply::where("id", $mpr->pos)
            ->where("loc", $mpr->loc)
            ->first();
        $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;

        $ReasonName = RecReason::where("id", $mpr->reason_exp)
            ->where("loc", $mpr->loc)
            ->first();
        $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;

        $templateData = [
            "department" => $submitter["dept_sumt"] ?? "",
            "requester" => [
                "name" => $submitter["name"] ?? "",
                "empno" => $submitter["empno"] ?? "",
                "position" => $positionName,
            ],
            "request" => [
                "id" => $mpr->req_id,
                "dept" => $submitter["dept"] ?? "",
                "div" => $submitter["div"] ?? "",
                "unit" => $submitter["unit"] ?? "",
                "date" => $mpr->key_in_date,
                "qty" => json_decode($mpr->qty_req, true),
                "reason" => $typereasonName,
                "status" => $isAmendment ? $mpr->status_amendent : $mpr->status,
            ],
        ];
        if ($isAmendment) {
            $emailSubject =
                "[MPR] 人員增補需求變更申請通知 / MPR Amendment Request Notification / Thông báo điều chỉnh Phiếu yêu cầu nhân sự";
            $templateView = "VgMpr-approval_amd";

            // Add amendment specific data
            if (!empty($mpr->change_req)) {
                $changeReqData = json_decode($mpr->change_req, true);
                if (is_array($changeReqData) && !empty($changeReqData)) {
                    $lastChangeReq = end($changeReqData);
                    if (
                        isset($lastChangeReq["change_reason"]) &&
                        isset($lastChangeReq["change_reason"]["id"])
                    ) {
                        $changeReasonId = $lastChangeReq["change_reason"]["id"];
                        $changeReasonModel = ChangeReason::find(
                            $changeReasonId
                        );
                        if ($changeReasonModel) {
                            $reasonData = json_decode(
                                $changeReasonModel->reason,
                                true
                            );
                            if ($reasonData) {
                                $templateData["request"]["reason_change"] = [
                                    "cn" => $reasonData["cn"] ?? "",
                                    "en" => $reasonData["en"] ?? "",
                                    "vi" => $reasonData["vi"] ?? "",
                                ];
                            }
                        }
                    }
                }
            }
            $templateData["request"]["re_id"] = $mpr->req_id_change;
            $templateData["request"]["remark_change"] = $mpr->remark_change;
        } else {
            $emailSubject =
                "[MPR] 人員增補單簽核通知 / Manpower Request Form Pending Your Approval / Thông báo phê duyệt đơn bổ sung nhân sự";
            $templateView = "VgMpr-approval";
        }

        $emailList = json_decode($request->emailList, true);
        $emailContent = view($templateView, $templateData)->render();

        $mailData = [
            "to" => $emailList,
            "subject" => $emailSubject,
            "msgBody" => $emailContent,
        ];

        $params = [
            "target" => $isAmendment ? "VG-MPR" : "VG-MPR",
            "body" => $emailContent,
            "msg_type" => "m",
            "msg_method" => "both",
            "msg_subject" => $emailSubject,
            "mail_template" => "msgCenterMailTemplate",
            "mail_data" => json_encode($mailData),
        ];

        $this->sendMsg($params);
    }
    public function updateReqId(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => [
                    "required",
                    function ($attribute, $value, $fail) {
                        if (!Mpr::find($value)) {
                            $fail("The selected record does not exist.");
                        }
                    },
                ],
                "req_id" => [
                    "required",
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = Mpr::where("req_id", $value)
                            ->where("id", "!=", $request->id)
                            ->exists();
                        if ($exists) {
                            $fail("This Req ID is already in use.");
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "errors" => $validator->errors(),
                    ],
                    400
                );
            }

            $mpr = Mpr::find($request->id);
            $oldReqId = $mpr->req_id;
            $mpr->req_id = $request->req_id;
            $mpr->save();

            return response()->json([
                "success" => true,
                "message" => "Req ID updated successfully",
                "data" => [
                    "id" => $mpr->id,
                    "oldReqId" => $oldReqId,
                    "newReqId" => $mpr->req_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error updating Req ID: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public static function resolveNewFlow($empno, $location = 'vg', $appCode = 'mpr', $customDept = null, $customDivision = null, $unit = null)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10.0,
                'connect_timeout' => 5.0
            ]);

            $deptScopeCodes = [];
            $divScopeCodes = [];

            if (!empty($unit)) {
                $deptScopeCodes = array_values(array_filter(array_map('trim', explode(',', $unit))));
            } elseif (!empty($customDept)) {
                if (strpos($customDept, ',') === false) {
                    $deptScopeCodes = [trim($customDept)];
                } else {
                    $deptScopeCodes = array_values(array_filter(array_map('trim', explode(',', $customDept))));
                }
            }

            foreach ($deptScopeCodes as $code) {
                $divScopeCodes[] = $code;
            }
            $divScopeCodes = array_values(array_unique(array_filter($divScopeCodes)));

            $payload = [
                'empno' => $empno,
                'location' => strtolower($location ?? 'vg'),
                'app_code' => strtolower($appCode ?? 'mpr'),
                'custom_dept' => $customDept,
                'custom_division' => $customDivision,
                'custom_depts' => [
                    'custom_dept_manager' => $customDept
                ],
                'custom_divisions' => [
                    'custom_division_manager' => $customDivision
                ]
            ];

            if (!empty($deptScopeCodes)) {
                $payload['filter_custom_scope_codes'] = [
                    'custom_division_manager' => $divScopeCodes,
                    'custom_dept_manager' => $deptScopeCodes
                ];
            }

            // Log payload for troubleshooting
            \Log::info("Calling resolve API with payload: " . json_encode($payload));

            $response = $client->post('http://gmo021.cansportsvg.com:10003/api/ifm-tracking/resolve', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $result = $body['result'] ?? $body;

            if (!empty($deptScopeCodes)) {
                $needFallback = false;
                if (empty($result) || !isset($result['flow_data'])) {
                    $needFallback = true;
                } else {
                    foreach ($result['flow_data'] as $flowStep) {
                        $lvlCode = $flowStep['lvl_code'] ?? '';
                        if (strpos($lvlCode, 'custom_') !== false) {
                            // Check if managers list is empty
                            if (empty($flowStep['managers'])) {
                                $needFallback = true;
                                break;
                            }
                            
                            // Check if there are no active deputies in this step
                            $hasActiveDeputy = false;
                            foreach ($flowStep['managers'] as $manager) {
                                if (!empty($manager['deputies'])) {
                                    foreach ($manager['deputies'] as $deputy) {
                                        $statusVal = $deputy['status'] ?? null;
                                        if ($statusVal === true || $statusVal === 1 || $statusVal === '1' || $statusVal === 'true') {
                                            $hasActiveDeputy = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                            if (!$hasActiveDeputy) {
                                $needFallback = true;
                                break;
                            }
                        }
                    }
                }

                if ($needFallback) {
                    \Log::info("Fallback triggered: a custom manager level has empty managers or no active deputies with filter_custom_scope_codes. Retrying without filter_custom_scope_codes.");
                    unset($payload['filter_custom_scope_codes']);
                    $response = $client->post('http://gmo021.cansportsvg.com:10003/api/ifm-tracking/resolve', [
                        'json' => $payload,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ]
                    ]);
                    $body = json_decode($response->getBody()->getContents(), true);
                    $result = $body['result'] ?? $body;
                }
            }

            if (empty($result) || !isset($result['flow_data'])) {
                \Log::error("Resolve API returned empty or invalid flow data: " . json_encode($body));
                return null;
            }
            $body = $result;

            // Map dept_names to dept_code and include active deputies in managers array
            if (is_array($body['flow_data'])) {
                foreach ($body['flow_data'] as &$flowStep) {
                    if (isset($flowStep['managers']) && is_array($flowStep['managers'])) {
                        $newManagers = [];
                        foreach ($flowStep['managers'] as &$manager) {
                            if (isset($manager['dept_names']) && !isset($manager['dept_code'])) {
                                $manager['dept_code'] = $manager['dept_names'];
                            }
                            $newManagers[] = $manager;

                            // If there are active deputies, append them as managers
                            if (isset($manager['deputies']) && is_array($manager['deputies'])) {
                                foreach ($manager['deputies'] as $deputy) {
                                    $statusVal = $deputy['status'] ?? null;
                                    if ($statusVal === true || $statusVal === 1 || $statusVal === '1' || $statusVal === 'true') {
                                        $newManagers[] = [
                                            'empno' => $deputy['empno'],
                                            'name' => $deputy['name'],
                                            'full_name' => $deputy['full_name'] ?? $deputy['name'],
                                            'email' => $deputy['email'] ?? '',
                                            'dept_code' => $manager['dept_code'] ?? [],
                                            'division_code' => $manager['division_code'] ?? '',
                                            'levels' => $manager['levels'] ?? [],
                                            'deputies' => [], // Deputies do not have deputies
                                            'status' => 1,
                                            'is_deputy' => true
                                        ];
                                    }
                                }
                            }
                        }
                        $flowStep['managers'] = $newManagers;
                    }
                }
            }

            return $body;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $resp = $e->getResponse();
            $errorBody = $resp ? $resp->getBody()->getContents() : '';
            \Log::error("Guzzle RequestException: " . $e->getMessage() . " | Body: " . $errorBody);
            throw new \Exception("Resolve API HTTP error: " . $e->getMessage() . " (Body: " . $errorBody . ")");
        } catch (\Exception $e) {
            \Log::error("General Exception in resolveNewFlow: " . $e->getMessage());
            throw $e;
        }
    }
    public function getDataFlowForItem(Request $req)
    {

        // VFA
        // $params = new Request([
        //     "empno" => $req->empno,
        //     "app_code" => "mpr",
        //     "location" => "vg",
        //     "custom_data" => json_encode([
        //         "custom_dept" => $req->dept,
        //         "custom_division" => $req->high_dept,
        //     ]),
        // ]);
        // $vfaController = new vfaCtrl();
        // try {
        //     $flowResponse = $vfaController->getFlowUpdated($params);
        //     if (!$flowResponse) {
        //         return response()->json(
        //             ["error" => "No data received from vfaController"],
        //             500
        //         );
        //     }
        //     return response()->json($flowResponse);
        // } catch (\Exception $e) {
        //     return response()->json(["error" => $e->getMessage()], 500);
        // }
        // IFM


        try {
            $empno = $req->empno;
            $appCode = $req->app_code ?? "mpr";
            $location = $req->location ?? "vg";
            
            // Try all possible department/division field name variants
            $customDept = $req->dept ?? $req->dept_sumt ?? $req->custom_dept;
            $customDivision = $req->high_dept ?? $req->custom_division ?? $req->division;

            if (empty($customDept) || empty($customDivision)) {
                if ($req->has('custom_data')) {
                    $customData = json_decode($req->custom_data, true);
                    if (is_array($customData)) {
                        $customDept = $customDept ?: ($customData['custom_dept'] ?? $customData['dept'] ?? $customData['dept_sumt'] ?? null);
                        $customDivision = $customDivision ?: ($customData['custom_division'] ?? $customData['high_dept'] ?? $customData['division'] ?? null);
                    }
                }
            }
          
            $unit = $req->unit ?? null;
            if (empty($unit) && $req->has('id')) {
                $mpr = Mpr::find($req->id);
                if ($mpr) {
                    $submitter = json_decode($mpr->submitter, true);
                    $unit = $submitter['unit'] ?? null;
                }
            }

            $flowResponse = self::resolveNewFlow($empno, $location, $appCode, $customDept, $customDivision, $unit);
            if (!$flowResponse) {
                return response()->json(
                    ["error" => "No data received from resolve API"],
                    500
                );
            }
            return response()->json($flowResponse);
        } catch (\Exception $e) {
            return response()->json([
                "error" => $e->getMessage(),
                "inputs" => [
                    "empno" => $req->empno ?? null,
                    "location" => $req->location ?? null,
                    "custom_dept" => $customDept ?? null,
                    "custom_division" => $customDivision ?? null
                ]
            ], 500);
        }

    }
    public function getBatchDataFlow(Request $req)
    {
        try {
            $requests = $req->input('requests', []);
            $results = [];
            $uniqueRequests = [];

            foreach ($requests as $itemReq) {
                $empno = $itemReq['empno'] ?? null;
                $appCode = $itemReq['app_code'] ?? "mpr";
                $location = $itemReq['location'] ?? "vg";
                $unit = $itemReq['unit'] ?? null;
                
                $customDept = $itemReq['dept'] ?? $itemReq['dept_sumt'] ?? $itemReq['custom_dept'] ?? null;
                $customDivision = $itemReq['high_dept'] ?? $itemReq['custom_division'] ?? $itemReq['division'] ?? null;

                $cacheKey = "{$empno}_{$customDept}_{$customDivision}_{$unit}";
                
                if (!isset($uniqueRequests[$cacheKey])) {
                    $uniqueRequests[$cacheKey] = [
                        'empno' => $empno,
                        'location' => $location,
                        'app_code' => $appCode,
                        'custom_dept' => $customDept,
                        'custom_division' => $customDivision,
                        'unit' => $unit
                    ];
                }
            }

            foreach ($uniqueRequests as $cacheKey => $r) {
                try {
                    $flowResponse = self::resolveNewFlow($r['empno'], $r['location'], $r['app_code'], $r['custom_dept'], $r['custom_division'], $r['unit']);
                    $results[$cacheKey] = $flowResponse['flow_data'] ?? null;
                } catch (\Exception $e) {
                    \Log::error("Error resolving flow for {$cacheKey}: " . $e->getMessage());
                    $results[$cacheKey] = null;
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => $e->getMessage()
            ], 500);
        }
    }
    //
    public function acceptRequest(Request $req)
    {
        
         try {
        $validator = Validator::make($req->all(), [
            "user_empno" => "required|string",
            "dataReq" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(
                ["success" => false, "errors" => $validator->errors()],
                400
            );
        }

        $dataReq = $req["dataReq"];
        $dataFlow = $dataReq["dataFlow"];
        $status = $dataReq["status"];
        $approval = json_decode($dataReq["approval"], true);
        $id = $dataReq["id"];
        $user_empno = $req->user_empno;

        $mpr = Mpr::find($id);
        if (!$mpr) {
            throw new \Exception("MPR record not found");
        }

        $isEndYearRequest =
            !empty($mpr->status_endyear) &&
            $mpr->status_endyear !== "completed" &&
            $mpr->status_endyear !== "declined";

        $isAmendmentRequest =
            !empty($dataReq["status_amendent"]) && !$isEndYearRequest;
        
        if (!$isAmendmentRequest && !empty($mpr->status_amendent) && 
            $mpr->status_amendent !== "completed" && 
            $mpr->status_amendent !== "declined") {
            $isAmendmentRequest = true;
        }

        $amendmentStatus = $dataReq["status_amendent"] ?? $mpr->status_amendent ?? null;

        // Cập nhật thông tin bổ sung
        if (isset($dataReq["approval_record"])) {
            $approvalRecord = json_decode(
                $dataReq["approval_record"],
                true
            );
            if (!is_array($approvalRecord) || !isset($approvalRecord[0])) {
                $approvalRecord = [$approvalRecord];
            }
            $mpr->approval_record = json_encode($approvalRecord);
            $mpr->save();
        }

        if (
            ($status === "waiting_hr" || $status === "waiting_hr_rec_sf") &&
            isset($dataReq["sub_pos"])
        ) {
            $mpr->sub_pos = $dataReq["sub_pos"];
            $mpr->save();
        }

        // --- CẤU HÌNH LUỒNG DUYỆT (FLOW) ---

        if ($isEndYearRequest) {
            // Flow End Year: Dept -> Div -> Completed (2 cấp)
            $statusFlow = [
                "waiting_dept" => ["next" => "waiting_div", "level" => 1],
                "waiting_div" => ["next" => "completed", "level" => 2],
            ];
            $currentStatus = $mpr->status_endyear;
        } elseif ($isAmendmentRequest) {
            $statusFlow = [
                "waiting_dept" => ["next" => "waiting_div", "level" => 1],
                "waiting_div" => [
                    "next" => "waiting_hr_rec_sf",
                    "level" => 2,
                ],
                "waiting_hr_rec_sf" => [
                    "next" => "waiting_hr",
                    "level" => 3,
                ],
                "waiting_hr" => ["next" => "completed", "level" => 4],
            ];
            $currentStatus = $amendmentStatus;
        } else {
            $statusFlow = [
                "waiting_dept" => ["next" => "waiting_div", "level" => 1],
                "waiting_div" => [
                    "next" => "waiting_hr_rec_sf",
                    "level" => 2,
                ],
                "waiting_hr_rec_sf" => [
                    "next" => "waiting_hr",
                    "level" => 3,
                ],
                "waiting_hr" => ["next" => "waiting_ad", "level" => 4],
                "waiting_ad" => ["next" => "waiting_gm", "level" => 5],
                "waiting_gm" => ["next" => "completed", "level" => 6],
            ];
            if ($mpr->supp_type == 1) {
                $statusFlow = [
                    "waiting_dept" => [
                        "next" => "waiting_div",
                        "level" => 1,
                    ],
                    "waiting_div" => [
                        "next" => "waiting_hr_rec_sf",
                        "level" => 2,
                    ],
                    "waiting_hr_rec_sf" => [
                        "next" => "waiting_hr",
                        "level" => 3,
                    ],
                    "waiting_hr" => ["next" => "completed", "level" => 4],
                ];
            }
            $currentStatus = $status;
        }

        // --- CẬP NHẬT TRẠNG THÁI NGƯỜI DUYỆT HIỆN TẠI ---
        if (!isset($statusFlow[$currentStatus])) {
            return response()->json(
                ["success" => false, "message" => "Invalid status"],
                400
            );
        }

        $currentLevelIndex = $statusFlow[$currentStatus]["level"] - 1;
        if (isset($approval[$currentLevelIndex])) {
            $approval[$currentLevelIndex]["status"] = "approved";
            $approval[$currentLevelIndex]["processed_at"] = now()->toDateTimeString();

            if (!empty($dataFlow[$currentLevelIndex]["managers"])) {
                $currentApprover = $this->findApprover(
                    $dataFlow[$currentLevelIndex]["managers"],
                    $user_empno
                );
                if ($currentApprover) {
                    $approval[$currentLevelIndex]["approver"] = $currentApprover;
                }
            }
        }

        $nextStatus = $statusFlow[$currentStatus]["next"];

        // Reset trạng thái level tiếp theo (nếu chưa hoàn thành)
        if ($nextStatus !== "completed") {
            $nextLevelIndex = $statusFlow[$currentStatus]["level"];
            if (isset($approval[$nextLevelIndex])) {
                $approval[$nextLevelIndex]["status"] = "waiting";
            }
        }

        // --- LƯU TRẠNG THÁI MỚI ---
        if ($isEndYearRequest) {
            $mpr->status_endyear = $nextStatus;
        } elseif ($isAmendmentRequest) {
            $mpr->status_amendent = $nextStatus;
        } else {
            $mpr->status = $nextStatus;
        }

        $mpr->approval = json_encode($approval);
        $mpr->save();

        // --- XỬ LÝ KHI HOÀN THÀNH (COMPLETED) ---
        if ($nextStatus === "completed") {
            // Đồng bộ sang bảng resign-tracking khi đơn hoàn thành (kể cả đơn chỉnh sửa/điều chỉnh)
            $this->syncResignTrackingOnCompleted($mpr);

            // Lấy giá trị activeRct an toàn
            $activeRctVal = $mpr->activeRct;
            $activeRctInt = is_numeric($activeRctVal)
                ? intval($activeRctVal)
                : null;

            // TRƯỜNG HỢP 1: Nhân đôi đơn nếu Tiếp tục tuyển dụng (activeRct == 1)
            if ($activeRctInt === 1) {
                $newMpr = $this->duplicateForNewYear($mpr);
                if ($newMpr) {
                    try {
                        \Log::info("New Year Cloned MPR created", [
                            "original" => $mpr->req_id,
                            "new" => $newMpr->req_id,
                        ]);
                        try {
                            $this->migrateCompletedMprToDemandSingle(
                                $newMpr
                            );
                        } catch (\Exception $e) {
                            \Log::error(
                                "Error migrating duplicated MPR to Demand: " .
                                    $e->getMessage()
                            );
                        }
                        // --- BỔ SUNG: CẬP NHẬT ĐƠN CŨ VỀ 0 ĐỂ ĐÓNG ---
                        $denyTempData = [
                            "fullfilldate" => now()->format("Y-m-d"),
                            "fulfilled" => "yes",
                            "recruited" => intval($mpr->recruited ?? 0),
                            "stocknumber" => 0, // Đưa tồn về 0
                            "reason" =>
                            "Closed - Transferred to New Year Request: " .
                                $newMpr->req_id,
                        ];

                        $mpr->deny_temp = json_encode($denyTempData);
                        $mpr->stocknumber = 0; // Quan trọng: Set stock về 0
                        $mpr->fulfilled = "yes";
                        $mpr->fullfilldate = now()->format("Y-m-d");
                        $mpr->save();
                        // ---------------------------------------------
                    } catch (\Exception $e) {
                        \Log::error(
                            "Error cloning MPR: " . $e->getMessage()
                        );
                    }
                }
            }

            // TRƯỜNG HỢP 2: Đóng đơn nếu Hủy tuyển dụng (activeRct == 0)
            elseif (isset($activeRctInt) && $activeRctInt === 0) {
                $denyTempData = [
                    "fullfilldate" => now()->format("Y-m-d"),
                    "fulfilled" => "yes",
                    "recruited" => intval($mpr->recruited ?? 0),
                    "stocknumber" => 0,
                    "reason" => "Cancelled by End Year Decision",
                ];

                $mpr->deny_temp = json_encode($denyTempData);
                $mpr->stocknumber = 0;
                $mpr->fulfilled = "yes";
                $mpr->fullfilldate = now()->format("Y-m-d");
                $mpr->save();
            }

            try {
                $this->migrateCompletedMprToDemandSingle($mpr);
            } catch (\Exception $e) {
            }
        }

        // Gửi email
        $this->sendAcceptEmailNotification(
            $mpr,
            $nextStatus,
            $dataFlow,
            $statusFlow,
            $currentStatus,
            $isAmendmentRequest
        );

        return response()->json([
            "success" => true,
            "message" => "Request accepted successfully",
            "data" => [
                "id" => $id,
                "status" => $mpr->status,
                "status_amendent" => $mpr->status_amendent,
                "status_endyear" => $mpr->status_endyear,
                "approval" => $approval,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(
            [
                "success" => false,
                "message" => "Error accepting request: " . $e->getMessage(),
            ],
            500
        );
    }
    }
    private function findApprover($managers, $user_empno)
    {
        foreach ($managers as $manager) {
            if (strcasecmp($manager["empno"], $user_empno) === 0) {
                return [
                    "empno" => $manager["empno"],
                    "name" => $manager["full_name"]?? $manager["name"],
                    "dept" => $manager["dept_code"][0] ?? "",
                    "email" => $manager["email"],
                ];
            }

            if (!empty($manager["deputies"])) {
                foreach ($manager["deputies"] as $deputy) {
                    if (
                        strcasecmp($deputy["empno"], $user_empno) === 0 &&
                        !empty($deputy["status"])
                    ) {
                        return [
                            "empno" => $deputy["empno"],
                            "name" => $deputy["full_name"] ?? $deputy["name"],
                            "dept" => $manager["dept_code"][0] ?? "",
                            "email" => $deputy["email"],
                        ];
                    }
                }
            }
        }
        return null;
    }
    private function sendMsg($params, $additionalCc = [])
    {
        // try {
        //     // Validate params
        //     if (empty($params)) {
        //         throw new \Exception("Parameters cannot be empty");
        //     }
        //     $msgCenterCtrl = new msgCenterCtrl();
            
        //     $request = new Request();
        //     foreach ($params as $key => $value) {
        //         $request->merge([$key => $value]);
        //     }
        //     $result = $msgCenterCtrl->sendOutMsg($request);
        //     \Log::info("Message sent successfully via msgCenterCtrl", [
        //         'target' => $params['target'] ?? 'N/A',
        //         'msg_type' => $params['msg_type'] ?? 'N/A',
        //     ]);
        //     return $result ?? ['success' => true];
        // } catch (\Exception $e) {
        //     \Log::error("Error in sendMsg: " . $e->getMessage(), [
        //         'params' => $params,
        //         'trace' => $e->getTraceAsString(),
        //     ]);
        //     throw $e;
        // }
         try {
            $client = new \GuzzleHttp\Client();

            $formData = http_build_query($params);

            $response = $client->post(
                "http://gmo021.cansportsvg.com/api/msg-center/sendOutMsg",
                [
                    "headers" => [
                        "Content-Type" => "application/x-www-form-urlencoded",
                    ],
                    "body" => $formData,
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function denyRequest(Request $req)
    {
        
        try {
        $validator = Validator::make($req->all(), [
            "id" => "required",
            "reason" => "required|string|max:500",
            "user_empno" => "required|string",
            "dataReq" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(
                ["success" => false, "errors" => $validator->errors()],
                400
            );
        }

        $dataReq = $req["dataReq"];
        $dataFlow = $dataReq["dataFlow"];
        $status = $dataReq["status"];
        $approval = json_decode($dataReq["approval"], true);
        $id = $dataReq["id"];
        $user_empno = $req->user_empno;

        $mpr = Mpr::find($id);
        if (!$mpr) {
            return response()->json(
                ["success" => false, "message" => "Record not found"],
                404
            );
        }

        // Check loại yêu cầu
        $isEndYearRequest =
            !empty($mpr->status_endyear) &&
            $mpr->status_endyear !== "completed" &&
            $mpr->status_endyear !== "declined";

        // FIX: Fallback to database if status_amendent not in request
        $isAmendmentRequest =
            !empty($dataReq["status_amendent"]) && !$isEndYearRequest;
        
        // Nếu request không có status_amendent nhưng database có, coi đó là amendment
        if (!$isAmendmentRequest && !empty($mpr->status_amendent) && 
            $mpr->status_amendent !== "completed" && 
            $mpr->status_amendent !== "declined") {
            $isAmendmentRequest = true;
        }

        $amendmentStatus = $dataReq["status_amendent"] ?? $mpr->status_amendent ?? null;

        // Auto decline
        $autoDecline =
            isset($dataReq["auto_decline"]) &&
            $dataReq["auto_decline"] === true;
        if ($autoDecline) {
            if ($isEndYearRequest) {
                $mpr->status_endyear = "declined";
            } elseif ($isAmendmentRequest) {
                $mpr->status_amendent = "declined";
            } else {
                $mpr->status = "declined";
            }

            // Update approval
            if ($approval && is_array($approval)) {
                foreach ($approval as &$level) {
                    if (
                        empty($level["status"]) ||
                        $level["status"] === "waiting"
                    ) {
                        $level["status"] = "rejected";
                        $level["reason_denied"] = $req->reason;
                        $level["processed_at"] = now()->toDateTimeString();
                        $level["approver"] = [
                            "empno" => "SYSTEM",
                            "name" => "Auto System",
                            "dept" => "SYSTEM",
                            "email" => "system@company.com",
                        ];
                        break;
                    }
                }
            }
            $mpr->approval = json_encode($approval);
            $mpr->save();
            return response()->json([
                "success" => true,
                "message" => "Request auto declined",
            ]);
        }

        // Manual Deny
        $statusFlow = [
            "waiting_dept" => ["level" => 1],
            "waiting_div" => ["level" => 2],
            "waiting_hr_rec_sf" => ["level" => 3],
            "waiting_hr" => ["level" => 4],
            "waiting_ad" => ["level" => 5],
            "waiting_gm" => ["level" => 6],
        ];

        // Xác định current status
        if ($isEndYearRequest) {
            $currentStatus = $mpr->status_endyear;
        } else {
            $currentStatus = $isAmendmentRequest
                ? $amendmentStatus
                : $status;
        }

        if (!isset($statusFlow[$currentStatus])) {
            return response()->json(
                ["success" => false, "message" => "Invalid status"],
                400
            );
        }

        $currentLevelIndex = $statusFlow[$currentStatus]["level"] - 1;

        if (isset($approval[$currentLevelIndex])) {
            $approval[$currentLevelIndex]["status"] = "rejected";
            $approval[$currentLevelIndex]["reason_denied"] = $req->reason;
            $approval[$currentLevelIndex]["processed_at"] = now()->toDateTimeString();

            if (!empty($dataFlow[$currentLevelIndex]["managers"])) {
                $currentApprover = $this->findApprover(
                    $dataFlow[$currentLevelIndex]["managers"],
                    $user_empno
                );
                if ($currentApprover) {
                    $approval[$currentLevelIndex]["approver"] = $currentApprover;
                }
            }
        }

        // Cập nhật vào DB
        if ($isEndYearRequest) {
            $mpr->status_endyear = "declined";
        } elseif ($isAmendmentRequest) {
            $mpr->status_amendent = "declined";
        } else {
            $mpr->status = "declined";
        }

        $mpr->approval = json_encode($approval);
        $mpr->save();

        $this->sendDenialEmailNotification(
            $mpr,
            $req->reason,
            $isAmendmentRequest
        );

        return response()->json([
            "success" => true,
            "message" => "Request denied successfully",
            "data" => [
                "id" => $mpr->id,
                "status" => $mpr->status,
                "status_amendent" => $mpr->status_amendent,
                "status_endyear" => $mpr->status_endyear,
                "approval" => $approval,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json(
            [
                "success" => false,
                "message" => "Error denying request: " . $e->getMessage(),
            ],
            500
        );
    }
    }
    public function returnRequest(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                "id" => "required",
                "reason" => "required|string",
                "returnToLevel" => "required|string",
                "user_empno" => "required|string",
                "dataReq" => "required",
                "dataReq.dataFlow" => "required",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    ["success" => false, "errors" => $validator->errors()],
                    400
                );
            }

            $dataReq = $req["dataReq"];
            $dataFlow = $dataReq["dataFlow"];
            $status = $dataReq["status"];
            $approval = json_decode($dataReq["approval"], true);
            $id = $dataReq["id"];
            $returnStatus = $req->returnToLevel;
            $user_empno = $req->user_empno;

            $mpr = Mpr::find($id);
            if (!$mpr) {
                return response()->json(
                    ["success" => false, "message" => "Record not found"],
                    404
                );
            }

            // Check loại yêu cầu
            $isEndYearRequest =
                !empty($mpr->status_endyear) &&
                $mpr->status_endyear !== "completed" &&
                $mpr->status_endyear !== "declined";

            $isAmendmentRequest =
                !empty($dataReq["status_amendent"]) && !$isEndYearRequest;
            $amendmentStatus = $dataReq["status_amendent"] ?? null;

            // Save Log
            $currentApproval = $mpr->approval;
            $logsArray = [];
            if (!empty($currentApproval)) {
                if (!empty($mpr->logs)) {
                    $existingLogs = json_decode($mpr->logs, true);
                    if (
                        isset($existingLogs[0]) &&
                        is_array($existingLogs[0]) &&
                        isset($existingLogs[0][0])
                    ) {
                        $logsArray = $existingLogs;
                    } else {
                        $logsArray = [$existingLogs];
                    }
                }
                $logsArray[] = json_decode($currentApproval, true);
                $mpr->logs = json_encode($logsArray);
            }

            $statusFlow = [
                "waiting_dept" => ["level" => 1],
                "waiting_div" => ["level" => 2],
                "waiting_hr_rec_sf" => ["level" => 3],
                "waiting_hr" => ["level" => 4],
                "waiting_ad" => ["level" => 5],
                "waiting_gm" => ["level" => 6],
            ];

            // Reset các level sau điểm trả về
            foreach ($approval as $index => $level) {
                if ($index >= $statusFlow[$returnStatus]["level"] - 1) {
                    $approval[$index]["status"] = "waiting";
                    $approval[$index]["approver"] = null;
                    $approval[$index]["reason_denied"] = null;
                }
            }

            if ($isEndYearRequest) {
                $currentStatus = $mpr->status_endyear;
            } else {
                $currentStatus = $isAmendmentRequest
                    ? $amendmentStatus
                    : $status;
            }

            $currentLevelIndex = $statusFlow[$currentStatus]["level"] - 1;

            if (isset($approval[$currentLevelIndex])) {
                $approval[$currentLevelIndex]["status"] = "returned";
                $approval[$currentLevelIndex]["return_reason"] = $req->reason;
                $approval[$currentLevelIndex]["processed_at"] = now()->toDateTimeString();
                if (!empty($dataFlow[$currentLevelIndex]["managers"])) {
                    $currentApprover = $this->findApprover(
                        $dataFlow[$currentLevelIndex]["managers"],
                        $user_empno
                    );
                    if ($currentApprover) {
                        $approval[$currentLevelIndex]["approver"] = $currentApprover;
                    }
                }
            }

            // Update DB status
            if ($isEndYearRequest) {
                $mpr->status_endyear = $returnStatus;
            } elseif ($isAmendmentRequest) {
                $mpr->status_amendent = $returnStatus;
            } else {
                $mpr->status = $returnStatus;
            }

            $mpr->approval = json_encode($approval);
            $mpr->save();

            // Xử lý gửi mail (cần logic getRecipientsList - giả sử giữ nguyên như cũ)
            $returnLevelIndex = $statusFlow[$returnStatus]["level"] - 1;
            $recipientsList = [];
            if (isset($dataFlow[$returnLevelIndex])) {
                foreach ($dataFlow[$returnLevelIndex]["managers"] as $manager) {
                    $recipientsList[] = [
                        "email" => $manager["email"],
                        "name" => $manager["name"],
                        "empno" => $manager["empno"],
                        "dept" => $manager["dept_code"][0] ?? "",
                        "type" => "manager",
                    ];
                    if (!empty($manager["deputies"])) {
                        foreach ($manager["deputies"] as $deputy) {
                            if (!empty($deputy["status"])) {
                                $recipientsList[] = [
                                    "email" => $deputy["email"],
                                    "name" => $deputy["name"],
                                    "empno" => $deputy["empno"],
                                    "dept" => $manager["dept_code"][0] ?? "",
                                    "type" => "deputy",
                                ];
                            }
                        }
                    }
                }
            }
            $this->sendReturnEmailNotification(
                $mpr,
                $req->reason,
                $returnStatus,
                $recipientsList,
                $isAmendmentRequest
            );

            return response()->json([
                "success" => true,
                "message" => "Request returned successfully",
                "data" => [
                    "id" => $mpr->id,
                    "status" => $mpr->status,
                    "status_amendent" => $mpr->status_amendent,
                    "status_endyear" => $mpr->status_endyear,
                    "approval" => $approval,
                    "recipients" => $recipientsList,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error returning request: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    private function sendAcceptEmailNotification(
        $mpr,
        $nextStatus,
        $dataFlow,
        $statusFlow,
        $currentStatus,
        $isAmendmentRequest
    ) {
        $submitter = json_decode($mpr->submitter, true);

        // Get position and reason names
        $typeApply = TypeApply::where("id", $mpr->pos)
            ->where("loc", $mpr->loc)
            ->first();
        $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;

        $ReasonName = RecReason::where("id", $mpr->reason_exp)
            ->where("loc", $mpr->loc)
            ->first();
        $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;

        // Get change reason data for amendments
        $changeReasonData = null;
        if ($isAmendmentRequest && !empty($mpr->change_req)) {
            $changeReasonData = $this->getAmendmentChangeReason($mpr);
        }

        $templateData = [
            "department" => $submitter["dept_sumt"] ?? "",
            "requester" => [
                "name" => $submitter["name"] ?? "",
                "empno" => $submitter["empno"] ?? "",
                "position" => $positionName,
            ],
            "request" => [
                "id" => $mpr->req_id,
                "re_id" => $mpr->req_id_change,
                "dept" => $submitter["dept"] ?? "",
                "div" => $submitter["div"] ?? "",
                "unit" => $submitter["unit"] ?? "",
                "date" => $mpr->key_in_date,
                "qty" => json_decode($mpr->qty_req, true),
                "reason" => $typereasonName,
                "status" => $nextStatus,
                "reason_change" => $changeReasonData,
                "remark_change" => $mpr->remark_change,
            ],
        ];

        if (!in_array($nextStatus, ["completed", "completed"])) {
            // Send to next level approvers
            $recipientsList = [];
            $nextLevelIndex = $statusFlow[$currentStatus]["level"];

            if (isset($dataFlow[$nextLevelIndex])) {
                foreach ($dataFlow[$nextLevelIndex]["managers"] as $manager) {
                    $recipientsList[] = [
                        "email" => $manager["email"],
                        "name" => $manager["name"],
                        "empno" => $manager["empno"],
                        "dept" => $manager["dept_code"][0] ?? "",
                        "type" => "manager",
                    ];

                    if (!empty($manager["deputies"])) {
                        foreach ($manager["deputies"] as $deputy) {
                            if (!empty($deputy["status"])) {
                                $recipientsList[] = [
                                    "email" => $deputy["email"],
                                    "name" => $deputy["name"],
                                    "empno" => $deputy["empno"],
                                    "dept" => $manager["dept_code"][0] ?? "",
                                    "type" => "deputy",
                                ];
                            }
                        }
                    }
                }
            }

            $emailList = array_column($recipientsList, "email");
            $templateView = $isAmendmentRequest
                ? "VgMpr-approval_amd"
                : "VgMpr-approval";
            $emailSubject = $isAmendmentRequest
                ? "[MPR] 人員增補需求變更申請通知 / MPR Amendment Request Notification / Thông báo điều chỉnh Phiếu yêu cầu nhân sự"
                : "[MPR] 人員增補單簽核通知 / Manpower Request Form Pending Your Approval / Thông báo phê duyệt đơn bổ sung nhân sự";
        } else {
            // Send completion notification to submitter
            $emailList = [$submitter["email"]];
            $templateView = $isAmendmentRequest
                ? "VgMpr-completed_amd"
                : "VgMpr-completed";
            $emailSubject = $isAmendmentRequest
                ? "[MPR] 人員增補需求變更核准通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã được phê duyệt"
                : "[MPR] 人員增補單核准通知 / Đơn xin bổ sung nhân sự đã được phê duyệt";
        }

        $emailContent = view($templateView, $templateData)->render();

        $mailData = [
            "to" => $emailList,
            "subject" => $emailSubject,
            "msgBody" => $emailContent,
        ];

        $params = [
            "target" => $isAmendmentRequest ? "VG-MPR" : "VG-MPR",
            "body" => $emailContent,
            "msg_type" => "m",
            "msg_method" => "both",
            "msg_subject" => $emailSubject,
            "mail_template" => "msgCenterMailTemplate",
            "mail_data" => json_encode($mailData),
        ];

        $this->sendMsg($params);
    }

    private function sendDenialEmailNotification(
        $mpr,
        $reason,
        $isAmendmentRequest
    ) {
        $submitter = json_decode($mpr->submitter, true);

        $typeApply = TypeApply::where("id", $mpr->pos)
            ->where("loc", $mpr->loc)
            ->first();
        $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;

        $ReasonName = RecReason::where("id", $mpr->reason_exp)
            ->where("loc", $mpr->loc)
            ->first();
        $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;

        $changeReasonData = null;
        if ($isAmendmentRequest && !empty($mpr->change_req)) {
            $changeReasonData = $this->getAmendmentChangeReason($mpr);
        }

        $templateData = [
            "department" => $submitter["dept_sumt"] ?? "",
            "requester" => [
                "name" => $submitter["name"] ?? "",
                "empno" => $submitter["empno"] ?? "",
                "position" => $positionName,
            ],
            "request" => [
                "id" => $mpr->req_id,
                "re_id" => $mpr->req_id_change,
                "dept" => $submitter["dept"] ?? "",
                "div" => $submitter["div"] ?? "",
                "unit" => $submitter["unit"] ?? "",
                "date" => $mpr->key_in_date,
                "qty" => json_decode($mpr->qty_req, true),
                "reason" => $typereasonName,
                "reason_change" => $changeReasonData,
                "status" => $isAmendmentRequest ? "declined" : "declined",
                "reason_denied" => $reason,
            ],
        ];

        $templateView = $isAmendmentRequest
            ? "VgMpr-denial_amd"
            : "VgMpr-denial";
        $emailContent = view($templateView, $templateData)->render();
        $emailSubject = $isAmendmentRequest
            ? "[MPR] 人員增補需求變更否決通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã bị từ chối"
            : "[MPR] 人員增補單否決通知 / Đơn xin bổ sung nhân sự đã bị từ chối";

        $mailData = [
            "to" => [$submitter["email"]],
            "subject" => $emailSubject,
            "msgBody" => $emailContent,
        ];

        $params = [
            "target" => $isAmendmentRequest ? "VG-MPR" : "VG-MPR",
            "body" => $emailContent,
            "msg_type" => "m",
            "msg_method" => "both",
            "msg_subject" => $emailSubject,
            "mail_template" => "msgCenterMailTemplate",
            "mail_data" => json_encode($mailData),
        ];

        $this->sendMsg($params);
    }

    private function sendReturnEmailNotification(
        $mpr,
        $reason,
        $returnStatus,
        $recipientsList,
        $isAmendmentRequest
    ) {
        $submitter = json_decode($mpr->submitter, true);

        $typeApply = TypeApply::where("id", $mpr->pos)
            ->where("loc", $mpr->loc)
            ->first();
        $positionName = $typeApply ? $typeApply->type_name : $mpr->pos;

        $ReasonName = RecReason::where("id", $mpr->reason_exp)
            ->where("loc", $mpr->loc)
            ->first();
        $typereasonName = $ReasonName ? $ReasonName->name : $mpr->reason_exp;

        $changeReasonData = null;
        if ($isAmendmentRequest && !empty($mpr->change_req)) {
            $changeReasonData = $this->getAmendmentChangeReason($mpr);
        }

        $templateData = [
            "department" => $submitter["dept_sumt"] ?? "",
            "requester" => [
                "name" => $submitter["name"] ?? "",
                "empno" => $submitter["empno"] ?? "",
                "position" => $positionName,
            ],
            "request" => [
                "id" => $mpr->req_id,
                "re_id" => $mpr->req_id_change,
                "dept" => $submitter["dept"] ?? "",
                "div" => $submitter["div"] ?? "",
                "unit" => $submitter["unit"] ?? "",
                "date" => $mpr->key_in_date,
                "qty" => json_decode($mpr->qty_req, true),
                "reason" => $typereasonName,
                "status" => $returnStatus,
                "return_reason" => $reason,
                "reason_change" => $changeReasonData,
                "return_to" => $this->getLevelName($returnStatus),
            ],
        ];

        $templateView = $isAmendmentRequest
            ? "VgMpr-return_amd"
            : "VgMpr-return";
        $emailContent = view($templateView, $templateData)->render();

        $emailSubject = $isAmendmentRequest
            ? "[MPR] 人員增補需求變更退回通知 / Đơn điều chỉnh yêu cầu bổ sung nhân sự đã bị trả về"
            : "[MPR] 人員增補單退回通知 / Đơn xin bổ sung nhân sự đã bị trả về";

        // --- CHANGED: gửi ưu tiên về người tạo đơn (submitter). nếu không có email thì fallback về recipientsList ---
        $submitterEmail = $submitter["email"] ?? null;
        $emailList = [];
        if (
            !empty($submitterEmail) &&
            filter_var($submitterEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $emailList = [$submitterEmail];
        } else {
            $emailList = array_values(
                array_filter(array_column($recipientsList, "email"))
            );
        }
        $emailList = array_values(array_unique($emailList));
        // --- end change ---

        $mailData = [
            "to" => $emailList,
            "subject" => $emailSubject,
            "msgBody" => $emailContent,
        ];

        $params = [
            "target" => $isAmendmentRequest ? "VG-MPR" : "VG-MPR",
            "body" => $emailContent,
            "msg_type" => "m",
            "msg_method" => "both",
            "msg_subject" => $emailSubject,
            "mail_template" => "msgCenterMailTemplate",
            "mail_data" => json_encode($mailData),
        ];

        $this->sendMsg($params);
    }
    private function getLevelName($level)
    {
        $levels = [
            "waiting_dept" => "Department",
            "waiting_div" => "Division",
            "waiting_hr_rec_sf" => "HR Staff",
            "waiting_hr" => "HR",
            "waiting_ad" => "AD",
            "waiting_gm" => "GM",
        ];
        return $levels[$level] ?? "Unknown";
    }
    public function getMprById($id = "")
    {
        try {
            $mpr = Mpr::find($id);

            if (!$mpr) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "MPR record not found",
                    ],
                    404
                );
            }

            return response()->json([
                "success" => true,
                "data" => $mpr,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error retrieving MPR record: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    public function getLatestReqId(Request $request)
    {
        try {
            $year = $request->query("year");
            $month = $request->query("month");

            $targetDate = null;
            if ($year && $month) {
                $targetDate = \Carbon\Carbon::createFromFormat(
                    "y-m-d",
                    "$year-$month-01"
                );
            }
            $fullReqId = $this->generateNewReqIdString($targetDate);
            $latestNumber = intval(substr($fullReqId, -4));

            return response()->json([
                "success" => true,
                "latestNumber" => $latestNumber,
                "fullReqId" => $fullReqId,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                ],
                500
            );
        }
    }
    public function getLatestReqIdChange(Request $request)
    {
        try {
            $year = $request->query("year");
            $month = $request->query("month");

            if (!$year || !$month) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Year and month are required",
                    ],
                    400
                );
            }

            $pattern = "HR-MPRA0-{$year}{$month}-%";

            $latestRequest = Mpr::where("req_id_change", "LIKE", $pattern)
                ->orderBy("req_id_change", "desc")
                ->first();

            if (!$latestRequest) {
                return response()->json([
                    "latestNumber" => 0,
                ]);
            }

            $matches = [];
            if (
                preg_match(
                    '/(\d{4})$/',
                    $latestRequest->req_id_change,
                    $matches
                )
            ) {
                $latestNumber = intval($matches[1]);
            } else {
                $latestNumber = 0;
            }

            return response()->json([
                "latestNumber" => $latestNumber,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error getting latest request ID: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function updateCancelDate(Request $req)
    {
        try {
            $demand = Mpr::find($req->id);
            if (!$demand) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Demand not found",
                    ],
                    404
                );
            }

            $demand->cancel_date = $req->cancel_date;
            $demand->save();

            return response()->json([
                "success" => true,
                "message" => "Cancel date updated successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error updating cancel date: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function getDataByMprFromRecruitReport($id)
    {
        try {
            $manPower = Mpr::find($id);

            if (!$manPower) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "ManPower not found",
                    ],
                    404
                );
            }

            return response()->json([
                "success" => true,
                "data" => $manPower,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error retrieving data: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function transferCandidates(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "fromId" => "required|integer",
                "toId" => "required|integer",
                "candidates" => "required|array",
                "candidates.*.empno" => "required|string",
                "candidates.*.name" => "required|string",
                "candidates.*.date" => "required|string",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "errors" => $validator->errors(),
                    ],
                    400
                );
            }

            // Get source and target MPR records
            $sourceMpr = Mpr::find($request->fromId);
            $targetMpr = Mpr::find($request->toId);

            if (!$sourceMpr || !$targetMpr) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Source or target MPR not found",
                    ],
                    404
                );
            }

            $sourceCandidates = array_values(
                json_decode($sourceMpr->candidates ?? "[]", true) ?: []
            );
            $targetCandidates = array_values(
                json_decode($targetMpr->candidates ?? "[]", true) ?: []
            );
            $transferCandidates = array_values($request->candidates);

            $sourceCandidates = array_values(
                array_filter($sourceCandidates, function ($candidate) use (
                    $transferCandidates
                ) {
                    return !in_array(
                        $candidate["empno"],
                        array_column($transferCandidates, "empno")
                    );
                })
            );

            $targetCandidates = array_values(
                array_merge($targetCandidates, $transferCandidates)
            );

            $sourceQtyReq = json_decode($sourceMpr->qty_req, true);
            $totalSourceQty = array_sum(
                array_map("intval", array_filter($sourceQtyReq))
            );
            $sourceMpr->recruited = count($sourceCandidates);
            $sourceMpr->stocknumber = $totalSourceQty - $sourceMpr->recruited;
            $sourceMpr->fulfilled = $sourceMpr->stocknumber <= 0 ? "yes" : "no";
            $sourceMpr->candidates = json_encode(
                array_values($sourceCandidates)
            );

            if ($sourceMpr->stocknumber > 0) {
                $sourceMpr->fullfilldate = null;
                $sourceMpr->fulfilled = "no";
            } elseif (
                $sourceMpr->stocknumber <= 0 &&
                empty($sourceMpr->fullfilldate)
            ) {
                $sourceMpr->fullfilldate = date("Y-m-d");
                $sourceMpr->fulfilled = "yes";
            }

            $targetQtyReq = json_decode($targetMpr->qty_req, true);
            $totalTargetQty = array_sum(
                array_map("intval", array_filter($targetQtyReq))
            );
            $targetMpr->recruited = count($targetCandidates);
            $targetMpr->stocknumber = $totalTargetQty - $targetMpr->recruited;
            $targetMpr->fulfilled = $targetMpr->stocknumber <= 0 ? "yes" : "no";
            $targetMpr->candidates = json_encode(
                array_values($targetCandidates)
            );

            // Update target fullfilldate based on new status
            if ($targetMpr->stocknumber > 0) {
                $targetMpr->fullfilldate = null;
                $targetMpr->fulfilled = "no";
            } elseif (
                $targetMpr->stocknumber <= 0 &&
                empty($targetMpr->fullfilldate)
            ) {
                // Only set fullfilldate if becoming fulfilled for the first time
                $targetMpr->fullfilldate = date("Y-m-d");
                $targetMpr->fulfilled = "yes";
            }

            // Save both records
            $sourceMpr->save();
            $targetMpr->save();

            return response()->json([
                "success" => true,
                "message" => "Candidates transferred successfully",
                "data" => [
                    "source" => $sourceMpr,
                    "target" => $targetMpr,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error transferring candidates: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    public static function getFilteredRequests($userData, $includeDeputy = true)
    {
        try {
            if (!isset($userData["empno"])) {
                return [
                    "success" => false,
                    "message" => "Missing employee number",
                    "data" => collect([]),
                ];
            }

            // Define valid request statuses
            $validStatuses = [
                "waiting_dept",
                "waiting_div",
                "waiting_hr_rec_sf",
                "waiting_hr",
                "waiting_ad",
                "waiting_gm",
            ];

            // Get requests with valid statuses
            $requests = Mpr::whereIn("status", $validStatuses)
                ->orWhereIn("status_amendent", $validStatuses)
                ->orderBy("id", "desc")
                ->get();

            // Map status levels
            $statusLevels = [
                "waiting_dept" => 0,
                "waiting_div" => 1,
                "waiting_hr_rec_sf" => 2,
                "waiting_hr" => 3,
                "waiting_ad" => 4,
                "waiting_gm" => 5,
            ];

            // Map reason_exp with SubPos data
            $requests = $requests->map(function ($request) {
                if ($request->reason_exp) {
                    $subPosData = RecReason::find($request->reason_exp);
                    if ($subPosData) {
                        try {
                            // Check if sub_name is JSON
                            $subName = json_decode($subPosData->name, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $request->reason_exp = $subName;
                            } else {
                                $request->reason_exp = $subPosData->name;
                            }
                        } catch (\Exception $e) {
                            \Log::error(
                                "Error mapping reason_exp: " . $e->getMessage()
                            );
                            $request->reason_exp = $subPosData->name;
                        }
                    }
                }
                return $request;
            });

            $filteredRequests = $requests->filter(function ($request) use ($userData, $statusLevels, $validStatuses, $includeDeputy) {
                $empno = $userData["empno"];
                $submitter = json_decode($request->submitter, true);

                if (!$submitter || empty($submitter["empno"])) {
                    return false;
                }

                $subEmpno = $submitter["empno"];
                $location = $userData["location"] ?? "vg";
                $appCode = "mpr";
                $customDept = $submitter["dept"] ?? $submitter["dept_sumt"] ?? $submitter["custom_dept"] ?? null;
                $customDivision = $submitter["high_dept"] ?? $submitter["custom_division"] ?? $submitter["division"] ?? null;
                $unit = $submitter["unit"] ?? null;

                $flowResponse = self::resolveNewFlow($subEmpno, $location, $appCode, $customDept, $customDivision, $unit);
                $flowData = $flowResponse["flow_data"] ?? [];
                try {
                } catch (\Exception $e) {
                    \Log::error("Error in getFilteredRequests resolveNewFlow: " . $e->getMessage());
                    return false;
                }

                if (empty($flowData)) {
                    return false;
                }

                // Check current status with priority for amendment status
                $statusesToCheck = [];
                if (
                    !empty($request->status_amendent) &&
                    in_array($request->status_amendent, $validStatuses)
                ) {
                    $statusesToCheck[] = $request->status_amendent;
                }
                if (
                    !empty($request->status) &&
                    in_array($request->status, $validStatuses)
                ) {
                    $statusesToCheck[] = $request->status;
                }

                if (empty($statusesToCheck)) {
                    return false;
                }

                foreach ($statusesToCheck as $status) {
                    if (!isset($statusLevels[$status])) {
                        continue;
                    }

                    $currentLevel = $statusLevels[$status];
                    if (
                        !isset($flowData[$currentLevel]) ||
                        empty($flowData[$currentLevel]["managers"])
                    ) {
                        continue;
                    }
                    $currentFlow = $flowData[$currentLevel];

                    foreach ($currentFlow["managers"] as $manager) {
                        // Direct manager check
                        if ($manager["empno"] === $empno) {
                            return true;
                        }
                        if ($includeDeputy && !empty($manager["deputies"])) {
                            foreach ($manager["deputies"] as $deputy) {
                                if (
                                    $deputy["empno"] === $empno &&
                                    !empty($deputy["status"]) &&
                                    $deputy["status"] === true
                                ) {
                                    return true;
                                }
                            }
                        }
                    }
                }

                return false;
            });

            return [
                "success" => true,
                "message" => "Requests filtered successfully",
                "data" => $filteredRequests->values(),
            ];
        } catch (\Exception $e) {
            \Log::error("Error in getFilteredRequests:", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return [
                "success" => false,
                "message" => "Error filtering requests: " . $e->getMessage(),
                "data" => collect([]),
            ];
        }
    }

    public function getProcessedRequestsByEmpno(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                "empno" => "required|string",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "errors" => $validator->errors(),
                    ],
                    400
                );
            }

            $empno = $req->input("empno");

            // Lấy tất cả các request đã hoàn thành hoặc bị từ chối
            $allRequests = Mpr::whereIn("status", ["completed", "declined"])
                ->orderBy("updated_at", "DESC")
                ->get([
                    "id",
                    "req_id",
                    "req_id_change",
                    "key_in_date",
                    "approval",
                    "status",
                    "status_amendent",
                    "submitter",
                    "pos",
                    "reason_exp",
                    "qty_req",
                    "recruited",
                    "stocknumber",
                    "fulfilled",
                    "fullfilldate",
                    "loc",
                    "supp_type",
                    "created_at",
                    "updated_at",
                ]);
            $processedRequests = [];
            $declinedRequests = [];

            foreach ($allRequests as $request) {
                $approval = json_decode($request->approval, true);
                $submitter = json_decode($request->submitter, true);
                $qtyReq = json_decode($request->qty_req, true);

                if (!$approval || !is_array($approval)) {
                    continue;
                }

                // Lấy thông tin position và reason
                $typeApply = TypeApply::where("id", $request->pos)
                    ->where("loc", $request->loc)
                    ->first();
                $positionName = $typeApply
                    ? $typeApply->type_name
                    : $request->pos;

                $reasonName = RecReason::where("id", $request->reason_exp)
                    ->where("loc", $request->loc)
                    ->first();
                $typereasonName = $reasonName
                    ? $reasonName->name
                    : $request->reason_exp;

                // Tính tổng số lượng yêu cầu
                $totalQty = 0;
                if ($qtyReq) {
                    $totalQty = array_sum(
                        array_filter([
                            $qtyReq["workers"] ?? 0,
                            $qtyReq["staff"] ?? 0,
                            $qtyReq["engineers"] ?? 0,
                            $qtyReq["leader"] ?? 0,
                            $qtyReq["tranlator"] ?? 0,
                        ])
                    );
                }

                // Tạo object data để return
                $requestData = [
                    "id" => $request->id,
                    "req_id" => $request->req_id,
                    "req_id_change" => $request->req_id_change,
                    "status" => $request->status,
                    "status_amendent" => $request->status_amendent,
                    "supp_type" => $request->supp_type,
                    "key_in_date" => $request->key_in_date,
                    "submitter" => [
                        "empno" => $submitter["empno"] ?? "",
                        "name" => $submitter["name"] ?? "",
                        "dept_sumt" => $submitter["dept_sumt"] ?? "",
                        "high_dept" => $submitter["high_dept"] ?? "",
                        "email" => $submitter["email"] ?? "",
                        "unit" => $submitter["unit"] ?? "",
                    ],
                    "position" => [
                        "id" => $request->pos,
                        "name" => $positionName,
                    ],
                    "reason_exp" => $typereasonName,
                    "quantity" => [
                        "requested" => $totalQty,
                        "recruited" => $request->recruited ?? 0,
                        "stocknumber" => $request->stocknumber ?? $totalQty,
                        "details" => $qtyReq,
                    ],
                    "fulfilled" => $request->fulfilled,
                    "fullfilldate" => $request->fullfilldate,
                    "created_at" => $request->created_at,
                    "updated_at" => $request->updated_at,
                ];

                $isDenied = false;

                // Kiểm tra xem user có từ chối đơn này không
                foreach ($approval as $level) {
                    if (
                        isset($level["approver"]["empno"]) &&
                        $level["approver"]["empno"] === $empno &&
                        isset($level["status"]) &&
                        strtolower($level["status"]) === "rejected"
                    ) {
                        $requestData["deny_reason"] =
                            $level["reason_denied"] ?? "";
                        $requestData["processed_at"] =
                            $level["processed_at"] ?? "";
                        $declinedRequests[] = $requestData;
                        $isDenied = true;
                        break;
                    }
                }

                if ($isDenied) {
                    continue;
                }

                // Kiểm tra xem user có approve đơn này không
                foreach ($approval as $level) {
                    if (
                        isset($level["approver"]["empno"]) &&
                        $level["approver"]["empno"] === $empno &&
                        isset($level["status"]) &&
                        strtolower($level["status"]) === "approved"
                    ) {
                        $requestData["processed_at"] =
                            $level["processed_at"] ?? "";
                        $processedRequests[] = $requestData;
                        break;
                    }
                }
            }

            return response()->json([
                "success" => true,
                "message" => "Processed requests retrieved successfully",
                "data" => [
                    "approved" => $processedRequests,
                    "declined" => $declinedRequests,
                ],
                "summary" => [
                    "total_approved" => count($processedRequests),
                    "total_declined" => count($declinedRequests),
                    "total_processed" =>
                    count($processedRequests) + count($declinedRequests),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in getProcessedRequestsByEmpno:", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error retrieving processed requests: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    public function countResignedCandidates()
    {
        try {
            // Get all MPRs with candidates but no resignList yet
            $mprs = Mpr::whereNotNull("candidates")
                // ->where("candidates", "!=", "")
                // ->where(function ($query) {
                //     $query->whereNull("resignList")->orWhere("resignList", "");
                // })
                // ->get();
                ->paginate(500);

            if ($mprs->isEmpty()) {
                return response()->json(
                    [
                        "status" => "success",
                        "message" => "No new MPRs to process",
                        "data" => [],
                    ],
                    200
                );
            }

            // Collect all candidate empnos in one array
            $allCandidateEmpnos = [];
            $mprCandidatesMap = [];

            foreach ($mprs as $mpr) {
                $candidates = json_decode($mpr->candidates, true);
                if (!empty($candidates)) {
                    $empnos = array_column($candidates, "empno");
                    $allCandidateEmpnos = array_merge(
                        $allCandidateEmpnos,
                        $empnos
                    );
                    $mprCandidatesMap[$mpr->id] = $candidates;
                }
            }

            // Single query to get all resigned employees
            $resignedEmployees = ResignData::whereIn(
                "er_empid",
                array_unique($allCandidateEmpnos)
            )
                ->get()
                ->keyBy("er_empid");

            $results = [];
            $bulkUpdateData = [];

            foreach ($mprs as $mpr) {
                if (!isset($mprCandidatesMap[$mpr->id])) {
                    continue;
                }

                $candidates = $mprCandidatesMap[$mpr->id];
                $resignList = [];
                $resignedCount = 0;

                foreach ($candidates as $candidate) {
                    if (isset($resignedEmployees[$candidate["empno"]])) {
                        $resignData = $resignedEmployees[$candidate["empno"]];
                        $resignList[] = [
                            "empno" => $candidate["empno"],
                            "date" => $resignData->er_resigndate,
                            "name" => $candidate["name"],
                            "key_in" => $candidate["key_in"],
                            "position" => $mpr->pos,
                        ];
                        $resignedCount++;
                    }
                }

                // Prepare bulk update data
                $bulkUpdateData[] = [
                    "id" => $mpr->id,
                    "resign" => $resignedCount,
                    "resignList" => !empty($resignList)
                        ? json_encode($resignList)
                        : null,
                    "updated_at" => now(),
                ];

                $results[] = [
                    "mpr_id" => $mpr->id,
                    "req_id" => $mpr->req_id,
                    "status" => "success",
                    "resigned_count" => $resignedCount,
                ];
            }

            // Bulk update using chunks
            collect($bulkUpdateData)
                ->chunk(100)
                ->each(function ($chunk) {
                    foreach ($chunk as $data) {
                        Mpr::where("id", $data["id"])->update([
                            "resign" => $data["resign"],
                            "resignList" => $data["resignList"],
                            "updated_at" => $data["updated_at"],
                        ]);
                    }
                });

            return response()->json(
                [
                    "status" => "success",
                    "message" =>
                    "Processed " . count($results) . " MPR records",
                    "data" => $results,
                ],
                200
            );
        } catch (\Exception $e) {
            \Log::error(
                "Error in countResignedCandidates: " . $e->getMessage()
            );
            return response()->json(
                [
                    "status" => "error",
                    "message" => $e->getMessage(),
                ],
                500
            );
        }
    }
    private function processAutoCloseEmail($mpr)
    {
        try {
            $submitter = json_decode($mpr->submitter, true);

            // Get position details from TypeApply
            $typeApply = TypeApply::where("id", $mpr->pos)
                ->where("loc", $mpr->loc)
                ->first();
            $position = $typeApply ? $typeApply->type_name : "";

            // Parse position name for different languages
            $positionNames = [
                "cn" => "",
                "vi" => "",
                "en" => "",
            ];

            if ($position) {
                try {
                    $positionObj = json_decode($position, true);
                    $positionNames["cn"] =
                        $positionObj["cn"] ?? ($positionObj["en"] ?? $position);
                    $positionNames["vi"] =
                        $positionObj["vi"] ?? ($positionObj["en"] ?? $position);
                    $positionNames["en"] = $positionObj["en"] ?? $position;
                } catch (\Exception $e) {
                    // Fallback to original value for all languages
                    $positionNames["cn"] = $position;
                    $positionNames["vi"] = $position;
                    $positionNames["en"] = $position;
                }
            }

            // Calculate quantity details
            $qtyReq = json_decode($mpr->qty_req, true);
            $totalQty = 0;
            if ($qtyReq) {
                $totalQty = array_sum(
                    array_filter([
                        $qtyReq["workers"] ?? 0,
                        $qtyReq["staff"] ?? 0,
                        $qtyReq["engineers"] ?? 0,
                        $qtyReq["leader"] ?? 0,
                        $qtyReq["tranlator"] ?? 0,
                    ])
                );
            }

            $templateData = [
                "requester" => [
                    "name" => $submitter["name"] ?? "",
                    "empno" => $submitter["empno"] ?? "",
                    "email" => $submitter["email"] ?? "",
                ],
                "request" => [
                    "id" => $mpr->req_id,
                    "unit" => $submitter["unit"] ?? "",
                    "position" => $positionNames, // Truyền object chứa tất cả ngôn ngữ
                    "stocknumber" => $mpr->stocknumber ?? $totalQty,
                    "recruited" => $mpr->recruited ?? 0,
                    "hold_date" => $mpr->hold_date,
                ],
            ];

            $emailContent = view("vgMpr-autoClose", [
                "templateData" => $templateData,
            ])->render();

            $emailSubject =
                "[MPR] 人員增補申請自動結案通知 / MPR Auto Close Notification / Thông báo tự động đóng yêu cầu nhân sự";

            $mailData = [
                "to" => [$submitter["email"]],
                "subject" => $emailSubject,
                "msgBody" => $emailContent,
            ];

            $params = [
                "target" => "VG-MPR",
                "body" => $emailContent,
                "msg_type" => "m",
                "msg_method" => "both",
                "msg_subject" => $emailSubject,
                "mail_template" => "msgCenterMailTemplate",
                "mail_data" => json_encode($mailData),
            ];

            $this->sendMsg($params);

            return true;
        } catch (\Exception $e) {
            \Log::error("Error sending auto close email: " . $e->getMessage());
            return false;
        }
    }
    public function sendAutoCloseEmail(Request $request)
    {
        try {
            $mpr = Mpr::find($request->id);
            if (!$mpr) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "MPR record not found",
                    ],
                    404
                );
            }
            $emailSent = $this->processAutoCloseEmail($mpr);
            return response()->json([
                "success" => $emailSent,
                "message" => $emailSent
                    ? "Auto close notification sent successfully"
                    : "Failed to send notification",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error sending auto close notification: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }
    private function processAutoReturnDecline($mpr)
    {
        try {
            if (
                $mpr->status !== "waiting_dept" &&
                $mpr->status_amendent !== "waiting_dept"
            ) {
                return;
            }

            $approval = json_decode($mpr->approval, true);
            $hasReturnedStatus = false;

            if (is_array($approval)) {
                foreach ($approval as $level) {
                    if (
                        isset($level["status"]) &&
                        $level["status"] === "returned"
                    ) {
                        $hasReturnedStatus = true;
                        break;
                    }
                }
            }

            if (!$hasReturnedStatus) {
                return;
            }

            $lastUpdate = \Carbon\Carbon::parse($mpr->updated_at);
            $now = \Carbon\Carbon::now();
            $daysDiff = $now->diffInDays($lastUpdate);

            if ($daysDiff < 30) {
                return;
            }

            if ($mpr->status === "waiting_dept") {
                $mpr->status = "declined";
            }
            if ($mpr->status_amendent === "waiting_dept") {
                $mpr->status_amendent = "declined";
            }

            if ($approval && is_array($approval)) {
                foreach ($approval as &$level) {
                    if (
                        empty($level["status"]) ||
                        $level["status"] === "waiting"
                    ) {
                        $level["status"] = "rejected";
                        $level["reason_denied"] =
                            "Auto declined - No action taken for 30 days after return";
                        $level["processed_at"] = now()->toDateTimeString();
                        $level["approver"] = [
                            "empno" => "SYSTEM",
                            "name" => "Auto System",
                            "dept" => "SYSTEM",
                            "email" => "VG-Reception@spg-sportsgear.com",
                        ];
                    }
                }
            }

            $mpr->approval = json_encode($approval);
            $mpr->save();
            $submitter = json_decode($mpr->submitter, true);
            $typeApply = TypeApply::where("id", $mpr->pos)
                ->where("loc", $mpr->loc)
                ->first();
            $position = $typeApply ? $typeApply->type_name : "";
            $positionNames = [
                "cn" => "",
                "vi" => "",
                "en" => "",
            ];

            if ($position) {
                try {
                    $positionObj = json_decode($position, true);
                    $positionNames["cn"] =
                        $positionObj["cn"] ?? ($positionObj["en"] ?? $position);
                    $positionNames["vi"] =
                        $positionObj["vi"] ?? ($positionObj["en"] ?? $position);
                    $positionNames["en"] = $positionObj["en"] ?? $position;
                } catch (\Exception $e) {
                    // Fallback to original value for all languages
                    $positionNames["cn"] = $position;
                    $positionNames["vi"] = $position;
                    $positionNames["en"] = $position;
                }
            }
            $qtyReq = json_decode($mpr->qty_req, true);
            $totalQty = 0;
            if ($qtyReq) {
                $totalQty = array_sum(
                    array_filter([
                        $qtyReq["workers"] ?? 0,
                        $qtyReq["staff"] ?? 0,
                        $qtyReq["engineers"] ?? 0,
                        $qtyReq["leader"] ?? 0,
                        $qtyReq["tranlator"] ?? 0,
                    ])
                );
            }
            $templateData = [
                "requester" => [
                    "name" => $submitter["name"] ?? "",
                    "empno" => $submitter["empno"] ?? "",
                    "email" => $submitter["email"] ?? "",
                ],
                "request" => [
                    "id" => $mpr->req_id,
                    "unit" => $submitter["unit"] ?? "",
                    "position" => $positionNames,
                    "stocknumber" => $mpr->stocknumber ?? $totalQty,
                    "recruited" => $mpr->recruited ?? 0,
                ],
            ];
            $emailContent = view("vgMpr-autoCloseReturn", [
                "templateData" => $templateData,
            ])->render();

            $emailSubject =
                "[MPR] 人員增補單自動作廢通知/ Đơn xin bổ sung nhân sự đã bị hệ thống tự động hủy";

            $mailData = [
                "to" => [$submitter["email"]],
                "subject" => $emailSubject,
                "msgBody" => $emailContent,
            ];

            $params = [
                "target" => "VG-MPR",
                "body" => $emailContent,
                "msg_type" => "m",
                "msg_method" => "both",
                "msg_subject" => $emailSubject,
                "mail_template" => "msgCenterMailTemplate",
                "mail_data" => json_encode($mailData),
            ];

            $this->sendMsg($params);

            return true;
        } catch (\Exception $e) {
            \Log::error(
                "Error in processAutoReturnDecline: " . $e->getMessage()
            );
            return false;
        }
    }
    public function checkAutoReturnDecline()
    {
        try {
            $mprList = Mpr::where(function ($query) {
                $query
                    ->where("status", "waiting_dept")
                    ->orWhere("status_amendent", "waiting_dept");
            })->get();
            // dd($mprList);
            $processedCount = 0;

            foreach ($mprList as $mpr) {
                if ($this->processAutoReturnDecline($mpr)) {
                    $processedCount++;
                }
            }

            return response()->json([
                "success" => true,
                "message" => "Processed $processedCount MPRs",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    
    public function updateMprCandidates()
    {
        try {
            $candidates = EmpnoInfo::where("reg", "yes")
                ->whereNotNull("emp_demandid")
                ->get();

            $groupedCandidates = $candidates->groupBy("emp_demandid");
            $updatedCount = 0;

            foreach ($groupedCandidates as $demandId => $candidateGroup) {
                   $demand = Demand::where("id_code", $demandId)->first();
                if (!$demand || empty($demand->id_code)) {
                    continue;
                }

                $mpr = Mpr::where("req_id", $demand->id_code)->first();
                if (!$mpr) {
                    continue;
                }

                // Format candidates for this MPR
                $formattedCandidates = $candidateGroup
                    ->map(function ($candidate) use ($mpr) {
                        return [
                            "empno" => $candidate->newEmpno,
                            "date" => $candidate->act_en_date,
                            "name" => $candidate->candidate_name,
                            "key_in" => $candidate->key_in_date,
                            "position" => $mpr->pos ?? 2,
                        ];
                    })
                    ->values()
                    ->toArray();

                // Update MPR fields
                $mpr->candidates = json_encode($formattedCandidates);
                $mpr->recruited = count($formattedCandidates);

                // Calculate total requested quantity from qty_req
                $qtyReq = json_decode($mpr->qty_req, true);
                $totalQty = 0;
                if ($qtyReq) {
                    $totalQty = array_sum(
                        array_filter([
                            $qtyReq["workers"] ?? 0,
                            $qtyReq["staff"] ?? 0,
                            $qtyReq["engineers"] ?? 0,
                            $qtyReq["leader"] ?? 0,
                            $qtyReq["tranlator"] ?? 0,
                        ])
                    );
                }

                if (
                    !empty($mpr->status_amendent) &&
                    $mpr->status_amendent === "completed" &&
                    !empty($mpr->change_req)
                ) {
                    try {
                        $changes = json_decode($mpr->change_req, true);
                        if (is_array($changes) && count($changes) > 0) {
                            $lastChange = end($changes);
                            if (
                                !empty($lastChange["change_qty"]) ||
                                $lastChange["change_qty"] === "0"
                            ) {
                                $totalQty = intval($lastChange["change_qty"]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning(
                            "Error parsing change_req in updateMprCandidates: " .
                            $e->getMessage()
                        );
                    }
                }

                $mpr->stocknumber = $totalQty - $mpr->recruited;
                $mpr->fulfilled = $mpr->stocknumber <= 0 ? "yes" : "no";
                $mpr->fullfilldate = $demand->dem_fullfilldate;
                $mpr->save();
                $updatedCount++;
            }

            return response()->json([
                "success" => true,
                "message" => "MPR candidates updated successfully",
                "updated_count" => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error updating MPR candidates: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function migrateCompletedMprToDemand(Request $request)
    {
        try {
            $mprs = Mpr::where("status", "completed")->get();
            $created = [];

            foreach ($mprs as $mpr) {
                if (empty($mpr->req_id)) {
                    continue;
                }
                if (Demand::where("id_code", $mpr->req_id)->exists()) {
                    continue;
                }

                $submitter = json_decode($mpr->submitter, true) ?: [];
                $dem_dept = $submitter["div"] ?? "";
                $dem_sub_department = $submitter["dept"] ?? "";

                $dem_unit = $submitter["unit"] ?? "";
                $dem_position = $mpr->pos;
                $typeApply = TypeApply::where("id", $mpr->pos)
                    ->where("loc", $mpr->loc)
                    ->first();
                if ($typeApply) {
                    $typeName = $typeApply->type_name;
                    if (is_string($typeName)) {
                        $decoded = json_decode($typeName, true);
                        if (
                            json_last_error() === JSON_ERROR_NONE &&
                            is_array($decoded)
                        ) {
                            $dem_position =
                                $decoded["en"] ??
                                (reset($decoded) ?? $typeName);
                        } else {
                            $dem_position = $typeName;
                        }
                    } else {
                        $dem_position = $typeName;
                    }
                }

                $dem_sub_position = $mpr->sub_pos;
                if (!empty($mpr->sub_pos)) {
                    $sub = SubPos::where("id", $mpr->sub_pos)
                        ->where("loc", $mpr->loc)
                        ->first();
                    if ($sub) {
                        $subName = $sub->sub_name ?? ($sub->name ?? null);
                        if (is_string($subName)) {
                            $decoded = json_decode($subName, true);
                            if (
                                json_last_error() === JSON_ERROR_NONE &&
                                is_array($decoded)
                            ) {
                                $dem_sub_position =
                                    $decoded["en"] ??
                                    (reset($decoded) ?? $subName);
                            } else {
                                $dem_sub_position = $subName;
                            }
                        } else {
                            $dem_sub_position = $subName;
                        }
                    }
                }
                $qtyReq = json_decode($mpr->qty_req, true);
                if (!is_array($qtyReq)) {
                    $qtyReq = [];
                }
                $dem_amount = 0;
                foreach ($qtyReq as $k => $v) {
                    if ($k === "level") {
                        continue;
                    }
                    $dem_amount += intval($v);
                }

                $dem_inputdate =
                    $mpr->date_req ?: $mpr->key_in_date ?: date("Y-m-d");

                $spec = $mpr->spec ?? "";
                $isOtherVi = "";
                if (!empty($mpr->isOther_reason)) {
                    $tmp = json_decode($mpr->isOther_reason, true);
                    if (
                        json_last_error() === JSON_ERROR_NONE &&
                        is_array($tmp)
                    ) {
                        $isOtherVi = $tmp["vi"] ?? "";
                    }
                }
                $dem_note_parts = [];
                if ($spec !== "") {
                    $dem_note_parts[] = trim($spec);
                }
                if ($isOtherVi !== "") {
                    $dem_note_parts[] = trim($isOtherVi);
                }
                if (isset($qtyReq["level"]) && !empty($qtyReq["level"])) {
                    $dutyHrm = DutyHrm::where(
                        "duty_no",
                        $qtyReq["level"]
                    )->first();
                    if ($dutyHrm) {
                        try {
                            $dutyName = json_decode($dutyHrm->duty_name, true);
                            if (
                                json_last_error() === JSON_ERROR_NONE &&
                                isset($dutyName["vi"])
                            ) {
                                $displayName = $dutyName["vi"];
                                if (strpos($displayName, "VG") === 0) {
                                    $firstSpace = strpos($displayName, " ");
                                    if ($firstSpace !== false) {
                                        $secondSpace = strpos(
                                            $displayName,
                                            " ",
                                            $firstSpace + 1
                                        );
                                        if ($secondSpace !== false) {
                                            $displayName = trim(
                                                substr(
                                                    $displayName,
                                                    $secondSpace + 1
                                                )
                                            );
                                        }
                                    }
                                }
                                $dem_note_parts[] = "Level: " . $displayName;
                            } else {
                                $dem_note_parts[] =
                                    "Level: " . trim($qtyReq["level"]);
                            }
                        } catch (\Exception $e) {
                            \Log::error(
                                "Error parsing duty_name: " . $e->getMessage()
                            );
                            $dem_note_parts[] =
                                "Level: " . trim($qtyReq["level"]);
                        }
                    } else {
                        $dem_note_parts[] = "Level: " . trim($qtyReq["level"]);
                    }
                }
                $dem_note = implode("\n", $dem_note_parts);

                $demand = new Demand();
                $demand->id_code = $mpr->req_id;
                $demand->dem_dept = $dem_dept;
                $demand->dem_sub_department = $dem_sub_department;
                $demand->dem_unit = $dem_unit;
                $demand->dem_position = $dem_position;
                $demand->dem_sub_position = $dem_sub_position;
                $demand->dem_amount = $dem_amount;
                $demand->dem_inputdate = $dem_inputdate;
                $demand->dem_recruited = 0;
                $demand->dem_fulfilled = "no";
                $demand->dem_fullfilldate = null;
                $demand->stocknumber = $dem_amount;
                $demand->dem_note = $dem_note !== "" ? $dem_note : null;
                $demand->dem_candidates = null;
                $demand->dem_pause_start_at = null;
                $demand->dem_pause_end_at = null;
                $demand->dem_cancel_date = null;
                $demand->user_input = null;
                $demand->user_update = null;

                $demand->save();

                $created[] = [
                    "mpr_id" => $mpr->id,
                    "demand_id" => $demand->id,
                    "id_code" => $demand->id_code,
                ];
            }

            return response()->json([
                "success" => true,
                "created_count" => count($created),
                "created" => $created,
            ]);
        } catch (\Exception $e) {
            \Log::error(
                "Error in migrateCompletedMprToDemand: " . $e->getMessage(),
                ["trace" => $e->getTraceAsString()]
            );
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                    "Error migrating MPR to Demand: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    private function migrateCompletedMprToDemandSingle($mpr)
    {
        try {
            if (
                !empty($mpr->req_id_change) &&
                $mpr->status_amendent === "completed"
            ) {
                $demand = Demand::where("id_code", $mpr->req_id)->first();
                if (!$demand) {
                    \Log::warning(
                        "Migrate amendment failed: Original Demand record not found for req_id: " .
                            $mpr->req_id
                    );
                    return;
                }
                $changeRequests = json_decode($mpr->change_req, true);
                if (is_array($changeRequests)) {
                    $lastChange = end($changeRequests);
                    if (!empty($lastChange["change_qty"])) {
                        $newAmount = intval($lastChange["change_qty"]);
                        $demand->dem_amount = $newAmount;
                        $demand->stocknumber =
                            $newAmount - ($demand->dem_recruited ?? 0);
                        $demand->save();
                    }
                }
                if (!empty($mpr->deny_temp)) {
                    $denyTemp = json_decode($mpr->deny_temp, true);
                    if ($denyTemp && isset($denyTemp["fullfilldate"])) {
                        $reason = "Amendment Cancelled"; // Lý do chung
                        $changeRequests = json_decode($mpr->change_req, true);
                        $lastChange = end($changeRequests);
                        if (isset($lastChange["change_reason"]["raw"])) {
                            $reasonRaw = $lastChange["change_reason"]["raw"];
                            $reason = implode(
                                " / ",
                                array_filter([
                                    $reasonRaw["en"] ?? "",
                                    $reasonRaw["vi"] ?? "",
                                    $reasonRaw["cn"] ?? "",
                                ])
                            );
                        }

                        $cancelData = [
                            "date" => $denyTemp["fullfilldate"],
                            "reason" => $reason,
                        ];
                        $demand->dem_amount = $denyTemp["recruited"];
                        $demand->dem_recruited = $denyTemp["recruited"];
                        $demand->dem_cancel_date = json_encode($cancelData);
                        $demand->stocknumber = 0; // Đóng đơn
                        $demand->dem_fulfilled = "yes";
                        $demand->dem_fullfilldate = $denyTemp["fullfilldate"];
                        $demand->save();
                    }
                }
                return;
            }
            if (empty($mpr->req_id)) {
                throw new \Exception("Empty req_id for MPR ID: " . $mpr->id);
            }
            $existingDemand = Demand::where("id_code", $mpr->req_id)->first();

            if ($existingDemand) {
                \Log::info(
                    "Demand already exists for req_id: " .
                        $mpr->req_id .
                        ". Checking for updates..."
                );

                if (!empty($mpr->deny_temp)) {
                    $denyTemp = json_decode($mpr->deny_temp, true);

                    if ($denyTemp && isset($denyTemp["fullfilldate"])) {
                        $cancelData = [
                            "date" => $denyTemp["fullfilldate"],
                            "reason" =>
                            $denyTemp["reason"] ?? "Auto-closed by system",
                        ];

                        $existingDemand->dem_cancel_date = json_encode(
                            $cancelData
                        );
                        $existingDemand->dem_amount = $denyTemp["recruited"];
                        $existingDemand->dem_recruited = $denyTemp["recruited"];
                        $existingDemand->stocknumber = 0; // Set stock về 0
                        $existingDemand->dem_fulfilled = "yes";
                        $existingDemand->dem_fullfilldate =
                            $denyTemp["fullfilldate"];
                        $existingDemand->save();

                        \Log::info(
                            "Updated existing Demand (cancellation) for req_id: " .
                                $mpr->req_id
                        );
                    }
                }
                return; // Dừng lại sau khi cập nhật
            }
            \Log::info(
                "Demand not found. Creating new Demand for req_id: " .
                    $mpr->req_id
            );

            $submitter = json_decode($mpr->submitter, true) ?: [];
            $dem_dept = $submitter["div"] ?? "";
            $dem_sub_department = $submitter["dept"] ?? "";
            $dem_unit = $submitter["unit"] ?? "";
            $dem_position = $mpr->pos;
            $typeApply = TypeApply::where("id", $mpr->pos)
                ->where("loc", $mpr->loc)
                ->first();
            if ($typeApply) {
                $typeName = $typeApply->type_name;
                if (is_string($typeName)) {
                    $decoded = json_decode($typeName, true);
                    if (
                        json_last_error() === JSON_ERROR_NONE &&
                        is_array($decoded)
                    ) {
                        $dem_position =
                            $decoded["en"] ?? (reset($decoded) ?? $typeName);
                    } else {
                        $dem_position = $typeName;
                    }
                } else {
                    $dem_position = $typeName;
                }
            }

            $dem_sub_position = $mpr->sub_pos;
            if (!empty($mpr->sub_pos)) {
                $sub = SubPos::where("id", $mpr->sub_pos)
                    ->where("loc", $mpr->loc)
                    ->first();
                if ($sub) {
                    $subName = $sub->sub_name ?? ($sub->name ?? null);
                    if (is_string($subName)) {
                        $decoded = json_decode($subName, true);
                        if (
                            json_last_error() === JSON_ERROR_NONE &&
                            is_array($decoded)
                        ) {
                            $dem_sub_position =
                                $decoded["en"] ?? (reset($decoded) ?? $subName);
                        } else {
                            $dem_sub_position = $subName;
                        }
                    } else {
                        $dem_sub_position = $subName;
                    }
                }
            }

            $qtyReq = json_decode($mpr->qty_req, true);
            if (!is_array($qtyReq)) {
                $qtyReq = [];
            }

            $dem_amount = 0;
            foreach ($qtyReq as $k => $v) {
                if ($k === "level") {
                    continue;
                }
                $dem_amount += intval($v);
            }

            $dem_inputdate =
                $mpr->date_req ?: $mpr->key_in_date ?: date("Y-m-d");
            $spec = $mpr->spec ?? "";
            $isOtherVi = "";
            if (!empty($mpr->isOther_reason)) {
                $tmp = json_decode($mpr->isOther_reason, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                    $isOtherVi = $tmp["vi"] ?? "";
                }
            }

            $dem_note_parts = [];
            if ($spec !== "") {
                $dem_note_parts[] = trim($spec);
            }
            if ($isOtherVi !== "") {
                $dem_note_parts[] = trim($isOtherVi);
            }
            if (isset($qtyReq["level"]) && !empty($qtyReq["level"])) {
                $dutyHrm = DutyHrm::where("duty_no", $qtyReq["level"])->first();
                if ($dutyHrm) {
                    try {
                        $dutyName = json_decode($dutyHrm->duty_name, true);
                        if (
                            json_last_error() === JSON_ERROR_NONE &&
                            isset($dutyName["vi"])
                        ) {
                            $displayName = $dutyName["vi"];
                            if (strpos($displayName, "VG") === 0) {
                                $firstSpace = strpos($displayName, " ");
                                if ($firstSpace !== false) {
                                    $secondSpace = strpos(
                                        $displayName,
                                        " ",
                                        $firstSpace + 1
                                    );
                                    if ($secondSpace !== false) {
                                        $displayName = trim(
                                            substr(
                                                $displayName,
                                                $secondSpace + 1
                                            )
                                        );
                                    }
                                }
                            }
                            $dem_note_parts[] = "Level: " . $displayName;
                        } else {
                            $dem_note_parts[] =
                                "Level: " . trim($qtyReq["level"]);
                        }
                    } catch (\Exception $e) {
                        \Log::error(
                            "Error parsing duty_name: " . $e->getMessage()
                        );
                        $dem_note_parts[] = "Level: " . trim($qtyReq["level"]);
                    }
                } else {
                    // Nếu không tìm thấy duty_no tương ứng
                    $dem_note_parts[] = "Level: " . trim($qtyReq["level"]);
                }
            }
            $dem_note = implode("\n", $dem_note_parts);

            // Create new Demand
            $demand = new Demand();
            $demand->id_code = $mpr->req_id;
             $demand->key_in = $mpr->key_in_date;
            $demand->dem_dept = $dem_dept;
            $demand->dem_sub_department = $dem_sub_department;
            $demand->dem_unit = $dem_unit;
            $demand->dem_position = $dem_position;
            $demand->dem_sub_position = $dem_sub_position;
            $demand->dem_amount = $dem_amount;
            $demand->dem_inputdate = $dem_inputdate;
            $demand->dem_recruited = 0;
            $demand->dem_fulfilled = "no";
            $demand->dem_fullfilldate = null;
            $demand->stocknumber = $dem_amount;
            $demand->dem_note = $dem_note !== "" ? $dem_note : null;
            $demand->dem_candidates = null;
            $demand->dem_pause_start_at = null;
            $demand->dem_pause_end_at = null;
            $demand->dem_cancel_date = null;
            $demand->user_input = null;
            $demand->user_update = null;
            $demand->save();

            \Log::info("Successfully migrated MPR to Demand", [
                "mpr_id" => $mpr->id,
                "demand_id" => $demand->id,
                "req_id" => $mpr->req_id,
            ]);

            return $demand;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    private function duplicateForNewYear($originalMpr)
    {
        try {
            $newMpr = $originalMpr->replicate();
            $nextYearJan = now()
                ->addYear()
                ->startOfYear();
            $newMpr->req_id = $this->generateNewReqIdString($nextYearJan);
            $newMpr->req_id_oldyear = $originalMpr->req_id;
            $newMpr->activeRct = null;
            $newMpr->deny_temp = null;
            $newMpr->candidates = null;
            $newMpr->resignList = null;
            $newMpr->resign = 0;

            $pos = intval($originalMpr->pos);
            $newQtyFromChange = null;
            if (
                !empty($originalMpr->status_amendent) &&
                $originalMpr->status_amendent === "completed" &&
                !empty($originalMpr->change_req)
            ) {
                try {
                    $changes = json_decode($originalMpr->change_req, true);
                    if (is_array($changes) && count($changes) > 0) {
                        $lastChange = end($changes);
                        if (
                            !empty($lastChange["change_qty"]) ||
                            $lastChange["change_qty"] === "0"
                        ) {
                            $newQtyFromChange = intval(
                                $lastChange["change_qty"]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning(
                        "Error parsing change_req in duplicateForNewYear: " .
                            $e->getMessage()
                    );
                    $newQtyFromChange = null;
                }
            }

            if (isset($newQtyFromChange) && $newQtyFromChange >= 0) {
                $qtyReqJson = json_decode($originalMpr->qty_req, true) ?? [];
                switch ($pos) {
                    case 1:
                        $qtyReqJson["workers"] = $newQtyFromChange;
                        break;
                    case 2:
                        $qtyReqJson["staff"] = $newQtyFromChange;
                        break;
                    case 3:
                        $qtyReqJson["leader"] = $newQtyFromChange;
                        break;
                    case 4:
                        $qtyReqJson["engineers"] = $newQtyFromChange;
                        break;
                    case 5:
                        $qtyReqJson["tranlator"] = $newQtyFromChange;
                        break;
                    default:
                        break;
                }

                $newMpr->qty_req = json_encode($qtyReqJson);
                $newMpr->stocknumber = $newQtyFromChange;
            } else {
                $originalRecruited = intval($originalMpr->recruited ?? 0);
                $originalStock = intval($originalMpr->stocknumber ?? 0);

                if ($originalRecruited > 0) {
                    $newQty = $originalStock;
                    $qtyReqJson =
                        json_decode($originalMpr->qty_req, true) ?? [];
                    switch ($pos) {
                        case 1:
                            $qtyReqJson["workers"] = $newQty;
                            break;
                        case 2:
                            $qtyReqJson["staff"] = $newQty;
                            break;
                        case 3:
                            $qtyReqJson["leader"] = $newQty;
                            break;
                        case 4:
                            $qtyReqJson["engineers"] = $newQty;
                            break;
                        case 5:
                            $qtyReqJson["tranlator"] = $newQty;
                            break;
                    }
                    $newMpr->qty_req = json_encode($qtyReqJson);
                    $newMpr->stocknumber = $newQty;
                } else {
                    $newMpr->stocknumber = $originalMpr->stocknumber;
                }
            }

            $newMpr->recruited = 0;
            $newMpr->fulfilled = "no";
            $newMpr->fullfilldate = null;
            $newMpr->status = "completed";
            $newMpr->status_amendent = null;

            // Reset ngày tạo
            $newMpr->created_at = now();
            $newMpr->updated_at = now();
            $currentYear = date("Y");
            $yearSetting = MprEndYear::where("year", $currentYear)->first();
            if (!$yearSetting) {
                $yearSetting = MprEndYear::orderBy("year", "desc")->first();
            }

            if (!$yearSetting) {
                $yearSetting = MprEndYear::orderBy("year", "desc")->first();
            }

            if ($yearSetting) {
                $newMpr->key_in_date =
                    $yearSetting->email_date ?? date("Y-m-d");
                $newMpr->date_req = $yearSetting->ey_date_req ?? date("Y-m-d");
                $newMpr->key_new_year =
                    $yearSetting->key_in_newyear ?? date("Y-m-d");
            } else {
                $newMpr->key_in_date = date("Y-m-d");
                $newMpr->date_req = date("Y-m-d");
            }

            $newMpr->approval = $originalMpr->approval;
            $newMpr->logs = $originalMpr->logs;
            $newMpr->approval_record = $originalMpr->approval_record;

            $newMpr->save();

            return $newMpr;
        } catch (\Exception $e) {
            \Log::error("Error duplicating MPR: " . $e->getMessage());
            return false;
        }
    }
    private function generateNewReqIdString($targetDate = null)
    {
        $date = $targetDate ? \Carbon\Carbon::parse($targetDate) : now();
        $yymm = $date->format("ym");
        $prefix = "HR-MPR00-{$yymm}-";
        $maxNumber = Mpr::where("req_id", "LIKE", $prefix . "%")
            ->selectRaw("MAX(CAST(RIGHT(req_id, 4) AS UNSIGNED)) as max_num")
            ->value("max_num");
        $nextNumber = $maxNumber ? $maxNumber + 1 : 1;
        do {
            $sequence = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
            $newReqId = $prefix . $sequence;

            $exists = Mpr::where("req_id", $newReqId)->exists();

            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $newReqId;
    }
    public function sendYearEndNotice()
    {
        try {
            $relevantMprs = Mpr::where(function ($query) {
                $query
                    ->where("status", "completed")
                    ->orWhere("status_amendent", "completed");
            })
                ->where("stocknumber", ">", 0)
                ->whereNotNull("approval")
                ->get(["approval"]);

            $emailList = [];

            foreach ($relevantMprs as $mpr) {
                $approvalData = json_decode($mpr->approval, true);

                if (is_array($approvalData)) {
                    foreach ($approvalData as $level) {
                        if (
                            isset($level["level"]) &&
                            $level["level"] == 1 &&
                            !empty($level["approver"]["email"])
                        ) {
                            $emailList[] = trim($level["approver"]["email"]);
                            break;
                        }
                    }
                }
            }
            $uniqueEmailList = array_values(
                array_unique(array_filter(array_map("strtolower", $emailList)))
            );

            if (empty($uniqueEmailList)) {
                return response()->json([
                    "success" => false,
                    "message" => "No relevant Level 1 approvers found.",
                ]);
            }

            $currentYear = date("Y");
            $yearSetting = MprEndYear::where("year", $currentYear)->first();
            if (!$yearSetting) {
                $yearSetting = MprEndYear::orderBy("year", "desc")->first();
            }

            $emailDateText = "";

            if ($yearSetting && !empty($yearSetting->email_date)) {
                try {
                    $date = \Carbon\Carbon::parse($yearSetting->email_date);
                    $weekMap = [
                        0 => "日",
                        1 => "一",
                        2 => "二",
                        3 => "三",
                        4 => "四",
                        5 => "五",
                        6 => "六",
                    ];
                    $dayOfWeek = $weekMap[$date->dayOfWeek];

                    $emailDateText =
                        $date->format("m/d") . "（" . $dayOfWeek . "）";
                } catch (\Exception $e) {
                    \Log::error(
                        "Date parsing error in notice: " . $e->getMessage()
                    );
                }
            }
            $emailContent = view("VgMpr-yearEndNotice", [
                "email_date" => $emailDateText,
            ])->render();
            $emailSubject = "年度招募結算時程作業通知";
            $mailData = [
                "to" => $uniqueEmailList,
                "subject" => $emailSubject,
                "msgBody" => $emailContent,
            ];
            $params = [
                "target" => "VG-MPR",
                "body" => $emailContent,
                "msg_type" => "m",
                "msg_method" => "both",
                "msg_subject" => $emailSubject,
                "mail_template" => "msgCenterMailTemplate",
                "mail_data" => json_encode($mailData),
            ];
            $this->sendMsg($params);
            return response()->json([
                "success" => true,
                "message" => "Year-end notice sent successfully.",
                "recipients_count" => count($uniqueEmailList),
            ]);
        } catch (\Exception $e) {
            \Log::error("Error sending year-end notice: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error: " . $e->getMessage(),
                ],
                500
            );
        }
    }
    public function getCompletedMprGroupedByDeptEmail()
    {
        try {
            // 1. Lấy dữ liệu từ DB (thêm trường 'submitter' để lấy unit)
            $mprs = Mpr::select([
                "req_id",
                "recruited",
                "resign",
                "stocknumber",
                "candidates",
                "qty_req",
                "approval",
                "submitter",
            ])
                ->where("stocknumber", ">", 0)
                ->whereNull("deny_temp")
                ->where(function ($q) {
                    $q->where("status", "completed")->orWhere(
                        "status_amendent",
                        "completed"
                    );
                })
                ->whereNull("req_id_oldyear")
                ->orderBy("id", "desc")
                ->get();

            $groupedData = [];

            foreach ($mprs as $item) {
                $deptEmail = null;
                if (!empty($item->approval)) {
                    $approvalData = json_decode($item->approval, true);
                    if (is_array($approvalData)) {
                        foreach ($approvalData as $level) {
                            if (
                                isset($level["level"]) &&
                                $level["level"] == 1 &&
                                isset($level["status"]) &&
                                $level["status"] === "approved" &&
                                !empty($level["approver"]["email"])
                            ) {
                                $deptEmail = strtolower(
                                    trim($level["approver"]["email"])
                                );
                                break;
                            }
                        }
                    }
                }

                if (!$deptEmail) {
                    continue;
                }
                $unit = "";
                if (!empty($item->submitter)) {
                    $submitterData = json_decode($item->submitter, true);
                    if (is_array($submitterData)) {
                        $unit = $submitterData["unit"] ?? "";
                    }
                }
                $candidatesCount = 0;
                if (!empty($item->candidates)) {
                    $cArr = json_decode($item->candidates, true);
                    if (is_array($cArr)) {
                        $candidatesCount = count($cArr);
                    }
                }

                $totalQtyReq = 0;
                if (!empty($item->qty_req)) {
                    $qArr = json_decode($item->qty_req, true);
                    if (is_array($qArr)) {
                        $totalQtyReq = array_sum(
                            array_filter(
                                [
                                    $qArr["workers"] ?? 0,
                                    $qArr["staff"] ?? 0,
                                    $qArr["engineers"] ?? 0,
                                    $qArr["leader"] ?? 0,
                                    $qArr["tranlator"] ?? 0,
                                ],
                                "is_numeric"
                            )
                        );
                    }
                }
                $groupedData[$deptEmail][] = [
                    "req_id" => $item->req_id,
                    "unit" => $unit, // <--- Đã thêm Unit vào kết quả trả về
                    "recruited" => $item->recruited,
                    "resign" => $item->resign,
                    "stocknumber" => $item->stocknumber,
                    "candidates_count" => $candidatesCount,
                    "total_qty_req" => $totalQtyReq,
                ];
            }

            return response()->json([
                "success" => true,
                "total_emails" => count($groupedData),
                "data" => $groupedData,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["success" => false, "message" => "Error: " . $e->getMessage()],
                500
            );
        }
    }
    public function sendYearEndConfirmationEmails()
    {
        $groupedDataResponse = $this->getCompletedMprGroupedByDeptEmail();
        $groupedData = json_decode(
            json_encode($groupedDataResponse->getData()->data),
            true
        );

        if (!is_array($groupedData) || empty($groupedData)) {
            return response()->json([
                "success" => false,
                "message" => "No data found to send.",
            ]);
        }
        $currentYear = date("Y"); // Hoặc lấy theo logic năm của bạn (ví dụ 2025)
        $yearSetting = MprEndYear::where("year", $currentYear)->first();
        if (!$yearSetting) {
            $yearSetting = MprEndYear::orderBy("year", "desc")->first();
        }
        $deadlineText = "";

        if ($yearSetting && !empty($yearSetting->deadline_date)) {
            try {
                $date = \Carbon\Carbon::parse($yearSetting->deadline_date);
                $weekMap = [
                    0 => "日",
                    1 => "一",
                    2 => "二",
                    3 => "三",
                    4 => "四",
                    5 => "五",
                    6 => "六",
                ];
                $dayOfWeek = $weekMap[$date->dayOfWeek];
                $deadlineText = $date->format("m/d");
            } catch (\Exception $e) {
                \Log::error("Date parsing error: " . $e->getMessage());
            }
        }
        $sentCount = 0;
        $errorCount = 0;

        foreach ($groupedData as $email => $requests) {
            if (empty($email) || empty($requests)) {
                continue;
            }

            try {
                \Log::info("Processing year-end confirmation for: $email");
                $emailContent = view("VgMpr-yearEndConfirmationList", [
                    "requests" => $requests,
                    "deadline" => $deadlineText,
                ])->render();
                $emailSubject = "[MPR] 人員增補單年度結算，請於 {$deadlineText} 前完成作業。";
                $mailData = [
                    "to" => [$email],
                    "subject" => $emailSubject,
                    "msgBody" => $emailContent,
                ];
                $params = [
                    "target" => "VG-MPR",
                    "body" => $emailContent,
                    "msg_type" => "m",
                    "msg_method" => "both",
                    "msg_subject" => $emailSubject,
                    "mail_template" => "msgCenterMailTemplate",
                    "mail_data" => json_encode($mailData),
                ];
                $this->sendMsg($params);
                $sentCount++;
            } catch (\Exception $e) {
                $errorCount++;
                \Log::error("Failed to send to $email: " . $e->getMessage());
                continue;
            }
        }
        return response()->json([
            "success" => $sentCount > 0,
            "message" => "Process completed. Sent: $sentCount, Failed: $errorCount.",
            "details" => [
                "total_managers" => count($groupedData),
                "sent_count" => $sentCount,
                "error_count" => $errorCount,
            ],
        ]);
    }
    public function saveYearSetting(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "year" => "required|integer|digits:4",
                "email_date" => "required|date",
                "reply_deadline" => "required|date|after_or_equal:email_date",
                "system_update_date" =>
                "required|date|after_or_equal:reply_deadline",
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Validation failed",
                        "errors" => $validator->errors(),
                    ],
                    400
                );
            }
            $setting = MprEndYear::updateOrCreate(
                ["year" => $request->year],
                [
                    "email_date" => $request->email_date,
                    "deadline_date" => $request->reply_deadline,
                    "update_date" => $request->system_update_date,
                    "ey_date_req" => $request->ey_date_req,
                    "key_in_newyear" => $request->key_in_new_year,
                ]
            );

            return response()->json([
                "success" => true,
                "message" => "Year setting saved successfully",
                "data" => $setting,
            ]);
        } catch (\Exception $e) {
            \Log::error("Error saving year setting: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    private function sendGroupedYearEndAutoCloseEmail($email, $requests)
    {
        try {
            $emailContent = view("VgMpr-yearEndAutoClose", [
                "requests" => $requests,
            ])->render();

            $emailSubject = "[MPR] 增補單自動結案通知";

            $mailData = [
                "to" => [$email],
                "subject" => $emailSubject,
                "msgBody" => $emailContent,
            ];

            $params = [
                "target" => "VG-MPR",
                "body" => $emailContent,
                "msg_type" => "m",
                "msg_method" => "both",
                "msg_subject" => $emailSubject,
                "mail_template" => "msgCenterMailTemplate",
                "mail_data" => json_encode($mailData),
            ];

            $this->sendMsg($params);
        } catch (\Exception $e) {
            \Log::error(
                "Error sending GROUPED year-end auto-close email to $email: " .
                    $e->getMessage()
            );
            throw $e;
        }
    }
    private function createSystemSignature($note)
    {
        $timestamp = now()->format("Y-m-d H:i:s");
        return $note;
    }
    public function runYearEndAutoClose()
    {
        $dbUpdateSuccessCount = 0;
        $dbUpdateFailedCount = 0;
        $groupedForEmail = [];

        $mprsToClose = Mpr::where("stocknumber", ">", 0)
            ->where(function ($q) {
                $q->whereNull("status_endyear")->orWhere(
                    "status_endyear",
                    "!=",
                    "completed"
                );
            })
            ->where(function ($q) {
                $q->whereNull("status_endyear")->orWhere(
                    "status_endyear",
                    "!=",
                    "declined"
                );
            })
            ->where(function ($q) {
                $q->whereNull("status")->orWhere("status", "!=", "declined");
            })
            ->where(function ($q) {
                $q->whereNull("status_amendent")->orWhere(
                    "status_amendent",
                    "!=",
                    "declined"
                );
            })
            ->get();

        if ($mprsToClose->isEmpty()) {
            \Log::info(
                "[SCHEDULE] No MPRs found requiring year-end auto-close."
            );
            return response()->json([
                "success" => true,
                "message" => "No requests found to auto-close.",
            ]);
        }

        foreach ($mprsToClose as $mpr) {
            try {
                $email = null;
                if (!empty($mpr->approval)) {
                    $approvalData = json_decode($mpr->approval, true);
                    if (is_array($approvalData)) {
                        foreach ($approvalData as $level) {
                            if (
                                ($level["level"] ?? null) == 1 &&
                                ($level["status"] ?? "") === "approved" &&
                                !empty($level["approver"]["email"])
                            ) {
                                $email = strtolower(
                                    trim($level["approver"]["email"])
                                );
                                break;
                            }
                        }
                    }
                }

                $submitter = json_decode($mpr->submitter, true) ?: [];
                $totalQtyReq = 0;
                if (!empty($mpr->qty_req)) {
                    $qArr = json_decode($mpr->qty_req, true);
                    if (is_array($qArr)) {
                        $totalQtyReq = array_sum([
                            $qArr["workers"] ?? 0,
                            $qArr["staff"] ?? 0,
                            $qArr["engineers"] ?? 0,
                            $qArr["leader"] ?? 0,
                            $qArr["tranlator"] ?? 0,
                        ]);
                    }
                }

                $requestDataRow = [
                    "req_id" => $mpr->req_id,
                    "status_old" => $mpr->status,
                    "status_amendent_old" => $mpr->status_amendent,
                    "unit" => $submitter["unit"] ?? "",
                    "total_qty_req" => $totalQtyReq,
                    "recruited" => $mpr->recruited ?? 0,
                    "resign" => $mpr->resign ?? 0,
                    "stocknumber" => $mpr->stocknumber,
                ];

                $currentApproval = $mpr->approval;
                if (!empty($currentApproval)) {
                    $logsArray = [];
                    if (!empty($mpr->logs)) {
                        $existingLogs = json_decode($mpr->logs, true);
                        if (is_array($existingLogs)) {
                            $logsArray =
                                array_keys($existingLogs) !==
                                range(0, count($existingLogs) - 1)
                                ? [$existingLogs]
                                : $existingLogs;
                        }
                    }
                    $logsArray[] = json_decode($currentApproval, true);
                    $mpr->logs = json_encode($logsArray);
                }

                $reasonNote =
                    "本單未於期限內完成確認，系統已自動結案；如仍有招募需求，請重新送單申請。";
                $newApprovalCycle = [
                    [
                        "level" => 1,
                        "type" => "hr", // Type hiển thị trên UI
                        "status" => "approved",
                        "processed_at" => now()->toDateTimeString(),
                        "approver" => [
                            "empno" => "SYSTEM",
                            "name" => "SYSTEM (AUTO)",
                            "dept" => "HR",
                            "email" => "",
                        ],
                        "reason_denied" => null,
                        "note" => $reasonNote,
                    ],
                ];
                $mpr->approval = json_encode($newApprovalCycle);

                $systemSignature = $this->createSystemSignature($reasonNote);

                // Lấy danh sách record hiện tại (của Cycle 1, 2...)
                $existingRecords = [];
                if (!empty($mpr->approval_record)) {
                    $decoded = json_decode($mpr->approval_record, true);
                    if (is_array($decoded)) {
                        $existingRecords =
                            array_keys($decoded) !==
                            range(0, count($decoded) - 1)
                            ? [$decoded]
                            : $decoded;
                    }
                }

                $existingRecords[] = [
                    "dept" => "",
                    "div" => "",
                    "hr_rec_sf" => "",
                    "hr" => $systemSignature,
                    "ad" => "",
                    "gm" => "",
                ];

                $mpr->approval_record = json_encode($existingRecords);

                // Cập nhật trạng thái
                $denyTempData = [
                    "fullfilldate" => now()->format("Y-m-d"),
                    "fulfilled" => "yes",
                    "recruited" => intval($mpr->recruited ?? 0),
                    "stocknumber" => 0,
                    "reason" => "Auto-closed by system (Year End)",
                    "status_old" => $mpr->status,
                    "status_amendent_old" => $mpr->status_amendent,
                ];

                $mpr->deny_temp = json_encode($denyTempData);
                $mpr->stocknumber = 0;
                $mpr->fulfilled = "yes";
                $mpr->fullfilldate = now()->format("Y-m-d");
                $mpr->activeRct = 0;
                $mpr->status_endyear = "completed";

                if (!is_null($mpr->status)) {
                    $mpr->status = "completed";
                }
                if (!is_null($mpr->status_amendent)) {
                    $mpr->status_amendent = "completed";
                }

                $mpr->save();
                $dbUpdateSuccessCount++;
                // Đồng bộ sang bảng Demand
                $this->migrateCompletedMprToDemandSingle($mpr);

                if (!empty($email)) {
                    $groupedForEmail[$email][] = $requestDataRow;
                }
            } catch (\Exception $e) {
                $dbUpdateFailedCount++;
                \Log::error(
                    "[SCHEDULE] Failed to auto-close MPR ID " .
                        $mpr->id .
                        ": " .
                        $e->getMessage()
                );
            }
        }

        $emailSentCount = 0;
        $emailFailedCount = 0;
        foreach ($groupedForEmail as $email => $requests) {
            try {
                $this->sendGroupedYearEndAutoCloseEmail($email, $requests);
                $emailSentCount++;
            } catch (\Exception $e) {
                $emailFailedCount++;
            }
        }

        $message = "Year-End Auto-Close finished. DB: {$dbUpdateSuccessCount} success, {$dbUpdateFailedCount} failed. Emails: {$emailSentCount} sent, {$emailFailedCount} failed.";
        \Log::info("[SCHEDULE] {$message}");

        return response()->json([
            "success" => true,
            "message" => $message,
            "summary" => [
                "db_success" => $dbUpdateSuccessCount,
                "db_failed" => $dbUpdateFailedCount,
                "email_sent" => $emailSentCount,
                "email_failed" => $emailFailedCount,
            ],
        ]);
    }
    public function manualMigrateToDemand(Request $request)
    {
        try {
            // Tự động lấy các MPR đã hoàn thành và có req_id từ 18 ký tự trở lên
            $mprs = Mpr::where('status', 'completed')
                ->whereNotNull('req_id')
                ->whereRaw('LENGTH(req_id) >= 18')
                ->get();

            $results = [];
            $errors = [];

            foreach ($mprs as $mpr) {
                try {
                    $demand = Demand::where('id_code', $mpr->req_id)->first();
                    if ($demand) {
                        $demand->key_in = $mpr->key_in_date;
                        $demand->save();
                        
                        $results[] = [
                            'mpr_id' => $mpr->id,
                            'req_id' => $mpr->req_id,
                            'status' => 'updated'
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error updating MPR ID {$mpr->id} (req_id: {$mpr->req_id}): " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => empty($errors),
                'migrated_count' => count($results),
                'results' => $results,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in manualMigrateToDemand: " . $e->getMessage(), ["trace" => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => "Error migrating MPR to Demand: " . $e->getMessage()
            ], 500);
        }
    }

    public function checkResignEmpNo(Request $request)
    {
        try {
            $empno = trim($request->input('empno'));
            $currentReqId = $request->input('req_id');

            if (empty($empno)) {
                return response()->json(['success' => false, 'message' => 'EmpNo is required'], 400);
            }

            $query = ResignTracking::where('empno', $empno)
                ->whereNotIn('status', ['declined', 'cancelled']);

            if (!empty($currentReqId)) {
                $query->where('req_id', '!=', $currentReqId);
            }

            $record = $query->first();

            if ($record) {
                return response()->json([
                    'success' => true,
                    'exists' => true,
                    'req_id' => $record->req_id,
                    'status' => $record->status,
                    'emp_name' => $record->emp_name,
                    'message' => "Mã nhân viên {$empno} (" . ($record->emp_name ?? '') . ") đã được sử dụng ở đơn {$record->req_id} (Trạng thái: {$record->status})!"
                ]);
            }

            return response()->json([
                'success' => true,
                'exists' => false
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function syncResignTrackingOnCompleted($mpr)
    {
        try {
            if (empty($mpr) || empty($mpr->old_emp)) {
                return;
            }

            $oldEmpRaw = $mpr->old_emp;
            $empList = [];
            if (is_array($oldEmpRaw)) {
                $empList = $oldEmpRaw;
            } else {
                $decoded = json_decode($oldEmpRaw, true);
                if (is_array($decoded)) {
                    $empList = $decoded;
                } else {
                    $empList = [['empno' => trim($oldEmpRaw)]];
                }
            }

            foreach ($empList as $emp) {
                $empNoVal = is_array($emp) ? ($emp['empno'] ?? null) : $emp;
                if (!empty($empNoVal)) {
                    ResignTracking::updateOrCreate(
                        [
                            'req_id' => $mpr->req_id,
                            'empno' => trim($empNoVal),
                        ],
                        [
                            'emp_name' => is_array($emp) ? ($emp['name'] ?? $emp['emp_name'] ?? null) : null,
                            'dept_code' => is_array($emp) ? ($emp['dept_code'] ?? $emp['dept'] ?? null) : null,
                            'dept_name' => is_array($emp) ? ($emp['dept_name'] ?? null) : null,
                            'status' => 'completed',
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error syncing ResignTracking on completed: " . $e->getMessage());
        }
    }
}
