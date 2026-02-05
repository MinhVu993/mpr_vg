<?php

namespace App\Http\Controllers;

use App\globalUserAppNameMdl as AppMdl;
use App\globalUserMdl as userMdl;
use App\vfaFlowMdl as flowMdl;
use App\vfaLevelMdl as lvlMdl;
use App\vfaManagerMdl as vfaManagerMdl;
use App\vfaChangeReportMdl as vfaChangeReportMdl;
use App\vfaManagerCustomDeputiesMdl as managerCustomDeputiesMdl;
use App\msgCenterMsgDataTbl as msgMdl;
use App\vfaUpdatedManagerMdl;
// FACADE
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\systemLogCtrl;

use Illuminate\Http\Request;

class vfaCtrl extends Controller
{

  public function sendReport(Request $request)
  {

    // Lấy payload
    $payload = $request->all(); // Giả sử payload gửi lên là mảng JSON

    // Nếu payload nằm trong key 'data' thì lấy $request->input('data')
    if (isset($payload['data'])) {
      $payload = $payload['data'];
    }

    // Lấy mapping level
    $levels = lvlMdl::all();
    $levelMap = [];
    foreach ($levels as $lvl) {
      $nameJson = json_decode($lvl->lvl_name, true);
      $levelMap[$lvl->lvl_code] = $nameJson['en'] ?? $lvl->lvl_code;
    }

    $msgBody = $this->generateReportTable($payload, $levelMap);

    msgMdl::insert([
      'msg_type' => 'm',
      'msg_method' => 'both',
      'msg_subject' => "[VFA] Change update report",
      'msg_body' => $msgBody,
      'target' => 'VG-EXPAT-LU-TEST',
      'mail_template' => 'msgCenterMailTemplate',
      'mail_data' => json_encode([
        'to' => ['lu.nguyen@spg-sportsgear.com'],
        'bcc' => ['lu.nguyen@spg-sportsgear.com'],
        'subject' => "[VFA] Change update report",
        'msgBody' => $msgBody,
      ]),
    ]);

    // Cập nhật trạng thái reported
    $reportIds = array_column($payload, 'report_id');
    if (!empty($reportIds)) {
      vfaChangeReportMdl::whereIn('id', $reportIds)->update(['reported' => 1]);
    }

    return response()->json([
      'message' => 'Gửi báo cáo thành công!',
    ]);
  }

  private function generateReportTable($data, $levelMap = [])
  {
    if (empty($data) || !is_array($data)) {
      return 'No data available';
    }

    $html = '<table border="1" style="border-collapse: collapse; width: 100%; border-color: #000;">';
    $html .= '<thead style="background-color: rgba(120, 225, 233, 0.27);">';
    $html .= '<tr>';
    $html .= '<th style="padding: 8px; text-align: left;">Name</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Dept (Before)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Dept (Update)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Division (Before)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Division (Update)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Level (Before)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Level (Update)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Deputies (Before)</th>';
    $html .= '<th style="padding: 8px; text-align: left;">Deputies (Update)</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ($data as $row) {
      $html .= '<tr>';

      // Name
      $html .= '<td style="padding: 8px;">' . ($row['empno'] ?? '') . ' - ' . ($row['name'] ?? '') . '</td>';

      // Dept Old
      $deptOld = is_array($row['dept_old'] ?? null) ? implode(', ', $row['dept_old']) : ($row['dept_old'] ?? '');
      $html .= '<td style="padding: 8px; background-color: #f5f5f5ff;">' . $deptOld . '</td>';

      // Dept New
      $deptNew = is_array($row['dept_new'] ?? null) ? implode(', ', $row['dept_new']) : ($row['dept_new'] ?? '');
      $bgDeptNew = ($deptOld != $deptNew) ? 'background-color: #fdb4b4ff;' : '';
      $html .= '<td style="padding: 8px; ' . $bgDeptNew . '">' . $deptNew . '</td>';

      // Division Old
      $divOld = $row['div_old'] ?? '';
      $html .= '<td style="padding: 8px; background-color: #f5f5f5ff;">' . $divOld . '</td>';

      // Division New
      $divNew = $row['div_new'] ?? '';
      $bgDivNew = ($divOld != $divNew) ? 'background-color: #fdb4b4ff;' : '';
      $html .= '<td style="padding: 8px; ' . $bgDivNew . '">' . $divNew . '</td>';

      // Level Old
      $levelsOldArr = is_array($row['levels_old'] ?? null) ? $row['levels_old'] : [];
      $levelsOldStr = '';
      foreach ($levelsOldArr as $index => $code) {
        $name = $levelMap[$code] ?? $code;
        $levelsOldStr .= ($index + 1) . '. ' . $name . '<br>';
      }
      $html .= '<td style="padding: 8px; background-color: #f5f5f5ff;">' . $levelsOldStr . '</td>';

      // Level New
      $levelsNewArr = is_array($row['levels_new'] ?? null) ? $row['levels_new'] : [];
      $levelsNewStr = '';
      foreach ($levelsNewArr as $index => $code) {
        $name = $levelMap[$code] ?? $code;
        $levelsNewStr .= ($index + 1) . '. ' . $name . '<br>';
      }
      $bgLevelsNew = (trim(strip_tags($levelsOldStr)) != trim(strip_tags($levelsNewStr))) ? 'background-color: #fdb4b4ff;' : '';
      // Simple compare logic might be insufficient because of numbering. Let's compare raw array first for accuracy or formatted string
      // Let's compare array difference. If arrays are identical, then formatted strings are identical (logic-wise).
      // Actually formatted string is what the user sees, so if the string representation is different, highlight it.
      $bgLevelsNew = ($levelsOldStr != $levelsNewStr) ? 'background-color: #fdb4b4ff;' : '';

      $html .= '<td style="padding: 8px; ' . $bgLevelsNew . '">' . $levelsNewStr . '</td>';

      // Deputies Old
      $deputiesOld = '';
      if (!empty($row['deputies_old']) && is_array($row['deputies_old'])) {
        foreach ($row['deputies_old'] as $index => $dep) {
          $deputiesOld .= ($index + 1) . '. ' . ($dep['name'] ?? '') . '<br>';
        }
      }
      $html .= '<td style="padding: 8px; background-color: #f5f5f5ff;">' . $deputiesOld . '</td>';

      // Deputies New
      $deputiesNew = '';
      if (!empty($row['deputies_new']) && is_array($row['deputies_new'])) {
        foreach ($row['deputies_new'] as $index => $dep) {
          $deputiesNew .= ($index + 1) . '. ' . ($dep['name'] ?? '') . '<br>';
        }
      }
      $bgDeputiesNew = ($deputiesOld != $deputiesNew) ? 'background-color: #fdb4b4ff;' : '';
      $html .= '<td style="padding: 8px; ' . $bgDeputiesNew . '">' . $deputiesNew . '</td>';

      $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
  }
  public function getDataPendingReport(Request $request)
  {
    // 1. Lấy change report chưa reported
    $reports = vfaChangeReportMdl::orderBy('id', 'desc')->get();

    // 2. Lấy toàn bộ id_new_data
    $ids = $reports->pluck('id_new_data')->filter()->unique()->values();

    // 3. Lấy dữ liệu từ vfaUpdatedManagerMdl
    $updatedManagers = vfaUpdatedManagerMdl::whereIn('id', $ids)->get()
      ->keyBy('id'); // key theo id để map nhanh

    // 4. Gộp dữ liệu
    $result = $reports->map(function ($item) use ($updatedManagers) {
      return [
        'change_report'   => $item,
        'updated_manager' => $updatedManagers->get($item->id_new_data),
      ];
    });

    return response()->json($result);
  }

  public function SaveChangedPendingReporting(Request $request)
  {
    // map id -> id_new_data
    $idNewData = $request->input('id');

    // CHỈ LẤY CÁC FIELD HỢP LỆ
    $data = $request->only([
      'global_user_id',
      'empno',
      'syno_username',
      'location',
      'name',
      'status',
      'dept_code_hrm',
      'dept_code',
      'division_code',
      'division_code_',
      'levels',
      'deputies',
    ]);

    $data['id_new_data'] = $idNewData;

    // update theo id_new_data
    $model = vfaChangeReportMdl::where('id_new_data', $idNewData)->first();

    if ($model) {
      $model->update($data);
    } else {
      $model = vfaChangeReportMdl::create($data);
    }

    return response()->json([
      'message' => 'Cập nhật thành công!',
      'data' => $model,
    ]);
  }

  public function CheckDeptNotInVfa()
  {
    $vfaCodes = vfaUpdatedManagerMdl::pluck('dept_code')->toArray();

    // Lấy toàn bộ người dùng
    $users = userMdl::all();

    // Danh sách các user->dept KHÔNG khớp
    $notFoundDepts = [];

    foreach ($users as $user) {
      $dept = $user->dept;

      // Bỏ qua nếu $dept null hoặc rỗng
      if (empty($dept)) {
        continue;
      }

      // Kiểm tra xem dept_code nào là chuỗi con của user->dept
      $found = false;
      foreach ($vfaCodes as $code) {
        if (stripos($dept, $code) !== false || stripos($code, $dept) !== false) {
          $found = true;
          break;
        }
      }

      // Nếu không khớp, thêm vào danh sách
      if (!$found) {
        $notFoundDepts[] = $dept;
      }
    }

    return response()->json([
      'not_matched_dept' => array_values(array_unique($notFoundDepts)),
    ]);
  }
  public function checkDivisionNotInVfa()
  {
    $vfaCodes = vfaUpdatedManagerMdl::pluck('division_code')->toArray();

    // Lấy toàn bộ người dùng từ globalUserMdl
    $users = userMdl::all();

    // Mảng chứa các high_dept không khớp
    $notFoundDepts = [];

    foreach ($users as $user) {
      $dept = $user->high_dept;

      // Bỏ qua nếu $dept null hoặc rỗng
      if (empty($dept)) {
        continue;
      }

      // Kiểm tra xem division_code nào là chuỗi con của high_dept hoặc ngược lại
      $found = false;
      foreach ($vfaCodes as $code) {
        if (stripos($dept, $code) !== false || stripos($code, $dept) !== false) {
          $found = true;
          break;
        }
      }

      // Nếu không tìm thấy thì thêm vào danh sách
      if (!$found) {
        $notFoundDepts[] = $dept;
      }
    }

    // Trả về JSON các high_dept không tìm thấy trong division_code
    return response()->json([
      'not_matched_high_dept' => array_values(array_unique($notFoundDepts)),
    ]);
  }

  public function getSynoFromEmpno()
  {
    try {
      // Lấy tất cả dữ liệu từ bảng vfaUpdatedManagerMdl
      $records = vfaUpdatedManagerMdl::all();
      $updatedCount = 0;

      foreach ($records as $record) {
        // Kiểm tra nếu có empno
        if ($record->empno) {
          // Tìm user trong bảng globalUserMdl theo empno
          $user = userMdl::where('empno', $record->empno)->first();

          // Nếu tìm thấy user và có syno_username
          if ($user && $user->syno_username) {
            // Cập nhật lại syno_username trong bảng vfaUpdatedManagerMdl
            $record->syno_username = $user->syno_username;
            $record->save();
            $updatedCount++;
          }
        }
      }

      // Log the successful batch update
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Batch Update: getSynoFromEmpno completed. $updatedCount records updated.",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => 'Cập nhật thành công!', 'updated_count' => $updatedCount]);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error in batch update getSynoFromEmpno: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to update syno_username', 'error' => $e->getMessage()], 500);
    }
  }
  public function getIDFromUserMdlToVfa()
  {
    try {
      // Lấy tất cả dữ liệu từ bảng vfaUpdatedManagerMdl
      $records = vfaUpdatedManagerMdl::all();
      $updatedCount = 0;

      foreach ($records as $record) {
        // Kiểm tra nếu có empno
        if ($record->empno) {
          // Tìm user trong bảng userMdl theo empno
          $user = userMdl::where('empno', $record->empno)->first();

          // Nếu tìm thấy user và có id
          if ($user && $user->id) {
            // Cập nhật lại global_user_id trong bảng vfaUpdatedManagerMdl
            $record->global_user_id = $user->id;
            $record->save();
            $updatedCount++;
          }
        }
      }

      // Log the successful batch update
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Batch Update: getIDFromUserMdlToVfa completed. $updatedCount records updated.",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => 'Cập nhật thành công!', 'updated_count' => $updatedCount]);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error in batch update getIDFromUserMdlToVfa: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to update global_user_id', 'error' => $e->getMessage()], 500);
    }
  }

  public function addDeptData()
  {
    try {
      // Lấy tất cả các bản ghi từ vfaUpdatedManagerMdl
      $managers = vfaUpdatedManagerMdl::all();
      $updatedCount = 0;

      foreach ($managers as $manager) {
        // Tìm user có empno tương ứng trong userMdl
        $user = userMdl::where('empno', $manager->empno)->first();

        if ($user && $user->dept_hrm) { // Nếu tìm thấy và dept_hrm không rỗng
          // Giải mã dept_code (JSON) thành mảng
          $deptCodeArray = json_decode($manager->dept_code, true);

          // Nếu dept_code chưa phải là mảng, khởi tạo mảng mới
          if (!is_array($deptCodeArray)) {
            $deptCodeArray = [];
          }

          // Thêm dept_hrm từ userMdl vào mảng deptCodeArray nếu chưa tồn tại
          if (!in_array($user->dept_hrm, $deptCodeArray)) {
            $deptCodeArray[] = $user->dept_hrm;
          }

          // Cập nhật dept_code_hrm với mảng mới (chuyển thành JSON)
          $manager->dept_code_hrm = json_encode($deptCodeArray);

          // Lưu bản ghi đã được cập nhật
          $manager->save();
          $updatedCount++;
        }
      }

      // Log the successful batch update
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Batch Update: addDeptData completed. $updatedCount records updated.",
      //   'local',
      //   'success'
      // );

      return response()->json(['status' => 'success', 'message' => 'Dept data updated successfully.', 'updated_count' => $updatedCount]);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error in batch update addDeptData: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['status' => 'error', 'message' => 'Failed to update dept data', 'error' => $e->getMessage()], 500);
    }
  }



  public function getListIdForEmpno(Request $req)
  {
    return managerCustomDeputiesMdl::where('empno', $req->empno)->get();
  }
  public function saveDeputies(Request $req)
  {
    try {
      if ($req->typeDeputies == 'all') {
        $deputies = array_map(function ($app) use ($req) {
          return [
            'app_name' => $app['app_name'],
            'deputies' => $req->empnoDeputy,
            'status' => 'true'
          ];
        }, $req->listAppsVfa);

        $deputiesJson = json_encode($deputies);
        $dayOffJson = json_encode($req->day_off);

        ManagerCustomDeputiesMdl::whereIn('id', $req->id)->update(['deputies' => $deputiesJson, 'day_off' => $dayOffJson]);

        // Log the successful action
        // systemLogCtrl::logAppEvent(
        //   'VFA',
        //   'Deputies saved for manager(s) ' . json_encode($req->id) . ', type: ' . $req->typeDeputies . ', deputy: ' . $req->empnoDeputy,
        //   'local',
        //   'success'
        // );

        return response()->json(['message' => 'Deputies saved successfully'], 200);
      } else if ($req->typeDeputies == 'each') {
        $deputies = array_map(function ($app) use ($req) {
          if (isset($app['checked']) && $app['checked'] == true) {
            return [
              'app_name' => $app['app_name'],
              'deputies' => $req->empnoDeputy,
              'status' => 'true'
            ];
          }
        }, $req->listAppsVfa);

        $deputies = array_filter($deputies);
        $deputiesJson = json_encode($deputies);
        $dayOffJson = json_encode($req->day_off);

        ManagerCustomDeputiesMdl::whereIn('id', $req->id)->update(['deputies' => $deputiesJson, 'day_off' => $dayOffJson]);

        // Log the successful action
        // systemLogCtrl::logAppEvent(
        //   'VFA',
        //   'Deputies saved for manager(s) ' . json_encode($req->id) . ', type: ' . $req->typeDeputies . ', deputy: ' . $req->empnoDeputy,
        //   'local',
        //   'success'
        // );

        return response()->json(['message' => 'Deputies saved successfully'], 200);
      }
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error saving deputies: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to save deputies', 'error' => $e->getMessage()], 500);
    }
  }

  public function getAppsVfa()
  {
    return AppMdl::where('vfa', 'true')->get();
  }
  public function deleteData(Request $req)
  {
    try {
      $cate = lvlMdl::find($req->id);
      $cate->delete();

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Approval Level deleted: ID ' . $req->id,
      //   'local',
      //   'success'
      // );

      return "Deleted";
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error deleting approval level: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to delete approval level', 'error' => $e->getMessage()], 500);
    }
  }

  public function getAllFlowData()
  {
    return flowMdl::orderBy('id', 'asc')->get();
  }

  public function getUserByEmpno(Request $req)
  {
    return userMdl::select(['name', 'empno', 'dept', 'high_dept'])
      ->where('empno', $req->empno)->first();
  }

  public function getLevelData()
  {
    return lvlMdl::get();
  }
  // approval-config
  public function getAppData()
  {
    return AppMdl::get();
  }

  public function saveFlowData(Request $req)
  {
    try {
      $flow = flowMdl::updateOrCreate(
        ['id' => $req->id],
        [
          'app_code' => $req->app_code,
          'location' => $req->location_code,
          'flow_data' => $req->flow_data,
        ]
      );

      // Determine if it was created or updated
      $action = $flow->wasRecentlyCreated ? 'Created' : 'Updated';

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Approval Flow $action: ID $flow->id, App Code $req->app_code, Location $req->location_code",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => "Approval Flow $action successfully"], 200);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error saving approval flow: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to save approval flow', 'error' => $e->getMessage()], 500);
    }
  }

  public function deleteConfigData(Request $req)
  {
    try {
      $cate = flowMdl::find($req->id);
      $cate->delete();

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Approval Flow deleted: ID ' . $req->id,
      //   'local',
      //   'success'
      // );

      return "Deleted";
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error deleting approval flow: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to delete approval flow', 'error' => $e->getMessage()], 500);
    }
  }

  // approval-level
  public function saveLevelToDB(Request $req)
  {
    try {
      $level = lvlMdl::updateOrCreate(
        ['id' => $req->id],
        [
          'lvl_type' => $req->lvl_type,
          'lvl_name' => $req->lvl_name,
          'lvl_code' => $req->lvl_code,
        ]
      );

      // Determine if it was created or updated
      $action = $level->wasRecentlyCreated ? 'Created' : 'Updated';

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Approval Level $action: ID $level->id, Code $req->lvl_code, Name $req->lvl_name",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => "Approval Level $action successfully"], 200);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error saving approval level: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to save approval level', 'error' => $e->getMessage()], 500);
    }
  }

  // approval-manager
  public function getDepts()
  {
    return userMdl::distinct()->pluck('dept');
  }
  public function getDivs()
  {
    return userMdl::distinct()->pluck('high_dept');
  }
  public function getManagers()
  {
    return userMdl::select(['name', 'empno'])->get();
  }
  public function getApprovalManagers()
  {
    return vfaManagerMdl::all();
  }
  public function saveManagerData(Request $req)
  {
    try {
      $manager = vfaManagerMdl::updateOrCreate(
        ['id' => $req->id],
        [
          'empno' => $req->empno,
          'location' => $req->location,
          'name' => $req->name,
          'status' => $req->status,
          'dept_code' => $req->dept_code,
          'division_code' => $req->division_code,
          'levels' => $req->levels,
          'deputies' => $req->deputies,
        ]
      );

      // Determine if it was created or updated
      $action = $manager->wasRecentlyCreated ? 'Created' : 'Updated';

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Approval Manager (Old) $action: ID $manager->id, Empno $req->empno",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => "Approval Manager (Old) $action successfully"], 200);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error saving approval manager (Old): ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to save approval manager (Old)', 'error' => $e->getMessage()], 500);
    }
  }
  public function saveManagerToDB2(Request $req)
  {
    try {
      $manager = vfaUpdatedManagerMdl::updateOrCreate(
        ['id' => $req->id],
        [
          'empno' => $req->empno,
          'location' => $req->location,
          'name' => $req->name,
          'status' => $req->status,
          'dept_code' => json_encode($req->dept_code),
          'division_code' => $req->division_code,
          'levels' => $req->levels,
          'deputies' => $req->deputies,
        ]
      );

      // Determine if it was created or updated
      $action = $manager->wasRecentlyCreated ? 'Created' : 'Updated';

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   "Approval Manager $action: ID $manager->id, Empno $req->empno",
      //   'local',
      //   'success'
      // );

      return response()->json(['message' => "Approval Manager $action successfully"], 200);
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error saving approval manager: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to save approval manager', 'error' => $e->getMessage()], 500);
    }
  }
  public function deleteManager(Request $req)
  {
    try {
      $row = vfaManagerMdl::find($req->id);
      $row->delete();

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Approval Manager (Old) deleted: ID ' . $req->id,
      //   'local',
      //   'success'
      // );

      return 'Deleted';
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error deleting approval manager (Old): ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to delete approval manager (Old)', 'error' => $e->getMessage()], 500);
    }
  }
  public function deleteManager2(Request $req)
  {
    try {
      $row = vfaUpdatedManagerMdl::find($req->id);
      $row->delete();

      // Log the successful action
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Approval Manager deleted: ID ' . $req->id,
      //   'local',
      //   'success'
      // );

      return 'Deleted';
    } catch (\Exception $e) {
      // Log the error
      // systemLogCtrl::logAppEvent(
      //   'VFA',
      //   'Error deleting approval manager: ' . $e->getMessage(),
      //   'local',
      //   'error'
      // );

      // Return error response
      return response()->json(['message' => 'Failed to delete approval manager', 'error' => $e->getMessage()], 500);
    }
  }
  public function getDataForApprovalManager()
  {
    return [
      'managerData' => vfaManagerMdl::all(),
      'levelData' => lvlMdl::all(),
      'globalDept' => userMdl::whereNotNull('dept')->pluck('dept')->unique()->values()->all(),
      'globalDivision' => userMdl::whereNotNull('high_dept')->pluck('high_dept')->unique()->values()->all(),
      'globalManager' => userMdl::select(['name', 'empno', 'location', 'dept', 'high_dept'])->get(),
    ];
  }
  // public function setAgain(){
  //     $rows = vfaUpdatedManagerMdl::all();
  //     foreach($rows as $r){
  //         $r->dept_code = json_encode([$r->dept_code]);
  //         $r->save();
  //     }
  // }
  public function getDataForApprovalManager2()
  {
    $managerArr = vfaUpdatedManagerMdl::all();
    $arr = $managerArr->pluck('empno')->toArray();
    $managerWithGroupEmpnoArr = userMdl::whereIn('empno', $arr)->get(['group_empno', 'empno']);
    foreach ($managerArr as $m) {
      $m['group_empno'] = null;
      foreach ($managerWithGroupEmpnoArr as $mg) {
        if ($m['empno'] == $mg['empno']) {
          $m['group_empno'] = $mg['group_empno'];
        }
      }
    }
    return [
      'managerData' => $managerArr,
      'levelData' => lvlMdl::all(),
      'globalDept' => userMdl::whereNotNull('dept')->pluck('dept')->unique()->values()->all(),
      'globalDeptHrm' => userMdl::whereNotNull('dept_hrm')->pluck('dept_hrm')->unique()->values()->all(),
      'globalDivision' => userMdl::whereNotNull('high_dept')->pluck('high_dept')->unique()->values()->all(),
      'globalManager' => userMdl::select(['name', 'empno', 'location', 'dept', 'dept_hrm', 'high_dept'])->get(),
    ];
  }

  public function getDataByEmpnoForApprovalManager($empno)
  {
    // chỉ lấy những manager có empno giống hoặc chứa $empnoSearch
    $managerArr = vfaUpdatedManagerMdl::where('empno', 'like', '%' . $empno . '%')->get();

    $arr = $managerArr->pluck('empno')->toArray();

    $managerWithGroupEmpnoArr = userMdl::whereIn('empno', $arr)->get(['group_empno', 'empno']);

    foreach ($managerArr as $m) {
      $m['group_empno'] = null;
      foreach ($managerWithGroupEmpnoArr as $mg) {
        if ($m['empno'] == $mg['empno']) {
          $m['group_empno'] = $mg['group_empno'];
          break; // tránh lặp thừa
        }
      }
    }

    return [
      'managerData' => $managerArr,
      // 'levelData' => lvlMdl::all(),
      // 'globalDept' => userMdl::whereNotNull('dept')->pluck('dept')->unique()->values()->all(),
      // 'globalDeptHrm' => userMdl::whereNotNull('dept_hrm')->pluck('dept_hrm')->unique()->values()->all(),
      // 'globalDivision' => userMdl::whereNotNull('high_dept')->pluck('high_dept')->unique()->values()->all(),
      // 'globalManager' => userMdl::select(['name', 'empno', 'location', 'dept', 'dept_hrm', 'high_dept'])->get(),
    ];
  }
  // end approval-manager

  // test-flow
  public function getManagersByLevelCode(Request $req)
  {
    return vfaManagerMdl::where('levels', 'like', '%' . $req->lvl_code . '%')
      ->orderBy('id', 'asc')
      ->get();
  }

  public function getAllManagerData()
  {
    return vfaManagerMdl::get();
  }
  public function getAllManagerData2()
  {
    return vfaUpdatedManagerMdl::get();
  }

  public function getManagerArrayData(Request $req)
  {
    return userMdl::select(['empno', 'name', 'high_dept'])->whereIn('empno', $req->empno_array)->get()->all();
  }

  public function testFlow(Request $req)
  {
    // $req (app_code, location, empno, custom_data)
    $custom_data = collect(json_decode($req->custom_data));

    $app_flow_data = flowMdl::where('app_code', $req->app_code)->where('location', $req->location)->first();
    $app_flow_data->flow_data = json_decode($app_flow_data->flow_data, true);
    $flowArray = collect($app_flow_data->flow_data);

    $level_data = collect(lvlMdl::get());
    $flowArray = $flowArray->map(function ($lvl_code) use ($level_data) {
      $lvl = $level_data->where('lvl_code', $lvl_code)->first();
      return [
        'lvl_code' => $lvl_code,
        'lvl_type' => $lvl['lvl_type'],
        'lvl_name' => json_decode($lvl['lvl_name']),
      ];
    });

    $approver_data = collect(vfaManagerMdl::where('location', $req->location)->get());
    $approver_data->each(function (&$approver) {
      $approver->levels = json_decode($approver->levels);
    });

    $emp_data = userMdl::select(['empno', 'dept', 'high_dept'])->where('empno', $req->empno)->first();

    $temp = collect();
    $flowArray->each(function ($flow) use ($emp_data, $approver_data, &$temp, $custom_data) {
      $temp1 = collect();
      $managers = $approver_data->filter(function ($approver) use ($emp_data, $flow, $custom_data) {
        if ($flow['lvl_type'] == 'normal') {
          if ($flow['lvl_code'] == 'dept_manager') {
            return $approver['dept_code'] == $emp_data['dept'] && in_array('dept_manager', $approver['levels']);
          } elseif ($flow['lvl_code'] == 'division_manager') {
            return $approver['division_code'] == $emp_data['high_dept'] && in_array('division_manager', $approver['levels']);
          }
        } elseif ($flow['lvl_type'] == 'defined') {
          return in_array($flow['lvl_code'], $approver['levels']);
        } elseif ($flow['lvl_type'] == 'custom') {
          if ($flow['lvl_code'] == 'custom_dept_manager') {
            return $custom_data['custom_dept'] == $approver['dept_code'] && in_array('custom_dept_manager', $approver['levels']);
          } elseif ($flow['lvl_code'] == 'custom_division_manager') {
            return $custom_data['custom_division'] == $approver['division_code'] && in_array('custom_division_manager', $approver['levels']);
          }
        }
        return false;
      });
      $managers->each(function ($approver) use (&$temp1) {
        if (isActive($approver->empno)) {
          $approver['email'] = $this->getEmailByUserName($approver['empno']);
          $temp1->push($approver);
        }
      });
      $temp->push([
        'lvl_code' => $flow['lvl_code'],
        'lvl_type' => $flow['lvl_type'],
        'lvl_name' => $flow['lvl_name'],
        'managers' => $temp1->all(),
      ]);
    });

    $app_flow_data['flow_data'] = $temp;
    return collect($app_flow_data);
  }

  public function rwTestFlow(Request $req)
  {
    // $req (app_code, location, empno, custom_data)
    $custom_data = collect(json_decode($req->custom_data));

    $app_flow_data = flowMdl::where('app_code', $req->app_code)->where('location', $req->location)->first();
    $app_flow_data->flow_data = json_decode($app_flow_data->flow_data, true);
    $flowArray = collect($app_flow_data->flow_data);

    $level_data = collect(lvlMdl::get());
    $flowArray = $flowArray->map(function ($lvl_code) use ($level_data) {
      $lvl = $level_data->where('lvl_code', $lvl_code)->first();
      return [
        'lvl_code' => $lvl_code,
        'lvl_type' => $lvl['lvl_type'],
        'lvl_name' => json_decode($lvl['lvl_name']),
      ];
    });

    $approver_data = collect(vfaManagerMdl::where('location', $req->location)->get());
    $approver_data->each(function (&$approver) {
      $approver->levels = json_decode($approver->levels);
    });

    $emp_data = userMdl::select(['empno', 'dept', 'high_dept'])->where('empno', $req->empno)->first();
    $temp = collect();
    $flowArray->each(function ($flow) use ($emp_data, $approver_data, &$temp, $custom_data) {
      $temp1 = collect();
      $managers = $approver_data->filter(function ($approver) use ($emp_data, $flow, $custom_data) {
        if ($flow['lvl_type'] == 'normal') {
          if ($flow['lvl_code'] == 'dept_manager') {
            return $approver['dept_code'] == $emp_data['dept'] && in_array('dept_manager', $approver['levels']);
          } elseif ($flow['lvl_code'] == 'division_manager') {
            return $approver['division_code'] == $emp_data['high_dept'] && in_array('division_manager', $approver['levels']);
          }
        } elseif ($flow['lvl_type'] == 'defined') {
          return in_array($flow['lvl_code'], $approver['levels']);
        } elseif ($flow['lvl_type'] == 'custom') {
          if ($flow['lvl_code'] == 'custom_dept_manager') {
            return $custom_data['custom_dept'] == $approver['dept_code'] && in_array('dept_manager', $approver['levels']);
          } elseif ($flow['lvl_code'] == 'custom_division_manager') {
            return $custom_data['custom_division'] == $approver['division_code'] && in_array('division_manager', $approver['levels']);
          }
        }
        return false;
      });
      $managers->each(function ($approver) use (&$temp1) {
        if (isActive($approver->empno)) {
          $approver['email'] = $this->getEmailByUserName($approver['empno']);
          $temp1->push($approver);
        }
      });
      $temp->push([
        'lvl_code' => $flow['lvl_code'],
        'lvl_type' => $flow['lvl_type'],
        'lvl_name' => $flow['lvl_name'],
        'managers' => $temp1->all(),
      ]);
    });

    $app_flow_data['flow_data'] = $temp;
    return collect($app_flow_data);
  }

  // end test-flow
  public function getEmailByUserName($empno)
  {
    // return $empno;
    $user = userMdl::where('empno', $empno)->first();
    // "Trying to get property 'email' of non-object", so check if $user is null
    // if email is null then return 'vg-hr027@spg-sportsgear.com'
    if ($user == null) {
      return 'lu.nguyen@spg-sportsgear.com';
    } else {
      return $user->email;
    }
  }

  public function ssrUserPaginate($perPage = 10)
  {
    return userMdl::paginate($perPage);
  }
  public function ssrUserGetAll()
  {
    return userMdl::get();
  }
  // update vfa
  public function getFlowUpdated(Request $req)
  {
    Log::debug('getFlowUpdated called with request: ' . json_encode($req->all()));

    $custom_data = json_decode($req->custom_data, true);

    $emp = userMdl::select(['empno', 'dept', 'high_dept'])->where('empno', $req->empno)->first();
    if (!$emp) {
      $emp = userMdl::select(['empno', 'dept', 'high_dept'])->where('group_empno', $req->empno)->first();
    }
    Log::debug('Employee data asdfasdf: ' . json_encode($emp));

    $appFlow = flowMdl::select(['app_code', 'location', 'flow_data'])->where(['app_code' => $req->app_code, 'location' => $req->location])->first();
    Log::debug('App flow data: ' . json_encode($appFlow));

    $flowData = json_decode($appFlow->flow_data, true);
    Log::debug('Flow data: ' . json_encode($flowData));

    $lvlData = lvlMdl::select(['lvl_type', 'lvl_name', 'lvl_code'])->whereIn('lvl_code', $flowData)->get();
    Log::debug('Level data: ' . json_encode($lvlData));

    $approver = vfaUpdatedManagerMdl::select(['empno', 'name', 'dept_code', 'division_code', 'levels', 'deputies', 'status'])->where('location', $req->location)
      ->get()->map(function ($a) {
        $a['levels'] = json_decode($a['levels'], true);
        $a['dept_code'] = json_decode($a['dept_code'], true);
        return $a;
      });

    $arr = $approver->pluck('empno')->toArray();
    $managerWithGroupEmpnoArr = userMdl::whereIn('empno', $arr)->get(['group_empno', 'empno']);
    foreach ($approver as &$m) {
      $m['group_empno'] = null;
      foreach ($managerWithGroupEmpnoArr as $mg) {
        if ($m['empno'] == $mg['empno']) {
          $m['group_empno'] = $mg['group_empno'];
        }
      }
    }
    $res = [];
    foreach ($flowData as $lvl_code) {
      $d = $lvlData->where('lvl_code', $lvl_code)->first();
      // Log::debtaug('Processing level code: ' . $lvl_code);

      $d['managers'] = $approver->filter(function ($a) use ($lvl_code, $emp, $d, $custom_data) {
        if ($d['lvl_type'] === 'normal') {
          $c1 = in_array($lvl_code, $a['levels']); // dept_manager
          $c2 = in_array($emp['dept'], $a['dept_code']);
          if ($lvl_code === 'division_manager') {
            Log::debug('Division manager check: dfasdfasdf' . json_encode($a));
            $c2 = $c2 && ($a['division_code'] == $emp['high_dept']);
          }
          return $c1 && $c2;
        }
        if ($d['lvl_type'] === 'defined') {
          return in_array($lvl_code, $a['levels']);
        }
        if ($d['lvl_type'] === 'custom') {
          $c1 = in_array($lvl_code, $a['levels']);
          $c2 = in_array($custom_data['custom_dept'], $a['dept_code']);
          if ($lvl_code === 'custom_division') $c2 = $c2 && $a['division_code'] == $a['division_code'];
          return $c1 && $c2;
        }
        return false;
      })->unique('empno')->values();
      $d['managers']->each(function (&$m) {
        $m['email'] = $this->getEmailByUserName($m['empno']);

        if (isset($m['deputies']) && is_string($m['deputies'])) {
          $m['deputies'] = json_decode($m['deputies'], true);
          $arr = $m['deputies'];
          foreach ($arr as &$deputy) {
            $deputy['email'] = $this->getEmailByUserName($deputy['empno']);
          }
          $m['deputies'] = $arr;
        }
      });
      $d['lvl_name'] = json_decode($d['lvl_name']);
      $res[] = $d;
    }
    $appFlow->flow_data = $res;

    Log::debug('getFlowUpdated completed, returning app flow: ' . json_encode($appFlow));

    return $appFlow;
  }

  public function getFlowUpdatedWithAD(Request $req)
  {
    // Log::info('getFlowUpdated called with request: ' . json_encode($req->all()));

    $custom_data = json_decode($req->custom_data, true);
    // Log::debug('Custom data: ' . json_encode($custom_data));

    $emp = userMdl::select(['syno_username', 'empno', 'dept', 'high_dept'])->where('syno_username', $req->syno_username)->first();
    // Log::debug('Employee data: ' . json_encode($emp));

    $appFlow = flowMdl::select(['app_code', 'location', 'flow_data'])->where(['app_code' => $req->app_code, 'location' => $req->location])->first();
    // Log::debug('App flow data: ' . json_encode($appFlow));

    $flowData = json_decode($appFlow->flow_data, true);
    // Log::debug('Flow data: ' . json_encode($flowData));

    $lvlData = lvlMdl::select(['lvl_type', 'lvl_name', 'lvl_code'])->whereIn('lvl_code', $flowData)->get();
    // Log::debug('Level data: ' . json_encode($lvlData));

    $approver = vfaUpdatedManagerMdl::select(['syno_username', 'empno', 'name', 'dept_code', 'division_code', 'levels', 'deputies', 'status'])->where('location', $req->location)
      ->get()->map(function ($a) {
        $a['levels'] = json_decode($a['levels'], true);
        $a['dept_code'] = json_decode($a['dept_code'], true);
        return $a;
      });
    // foreach ($approver as $a) {bta
    //   // log it
    //   Log::debug('Approver data: ' . json_encode($a));
    // }

    $arr = $approver->pluck('syno_username')->toArray();
    $managerWithGroupEmpnoArr = userMdl::whereIn('syno_username', $arr)->get(['group_empno', 'syno_username']);
    foreach ($approver as &$m) {
      $m['group_empno'] = null;
      foreach ($managerWithGroupEmpnoArr as $mg) {
        if ($m['syno_username'] == $mg['syno_username']) {
          $m['group_empno'] = $mg['group_empno'];
        }
      }
    }
    $res = [];
    foreach ($flowData as $lvl_code) {
      $d = $lvlData->where('lvl_code', $lvl_code)->first();
      // Log::debtaug('Processing level code: ' . $lvl_code);

      $d['managers'] = $approver->filter(function ($a) use ($lvl_code, $emp, $d, $custom_data) {
        if ($d['lvl_type'] === 'normal') {
          $c1 = in_array($lvl_code, $a['levels']); // dept_manager
          $c2 = in_array($emp['dept'], $a['dept_code']);
          if ($lvl_code === 'division_manager') $c2 = $c2 && ($a['division_code'] == $emp['high_dept']);
          return $c1 && $c2;
        }
        if ($d['lvl_type'] === 'defined') {
          return in_array($lvl_code, $a['levels']);
        }
        if ($d['lvl_type'] === 'custom') {
          $c1 = in_array($lvl_code, $a['levels']);
          $c2 = in_array($custom_data['custom_dept'], $a['dept_code']);
          if ($lvl_code === 'custom_division') $c2 = $c2 && $a['division_code'] == $a['division_code'];
          return $c1 && $c2;
        }
        return false;
      })->values();
      $d['managers']->each(function (&$m) {
        $m['email'] = $this->getEmailByUserName($m['empno']);

        if (isset($m['deputies']) && is_string($m['deputies'])) {
          $m['deputies'] = json_decode($m['deputies'], true);
          $arr = $m['deputies'];
          foreach ($arr as &$deputy) {
            $deputy['email'] = $this->getEmailByUserName($deputy['empno']);
          }
          $m['deputies'] = $arr;
        }
      });
      $d['lvl_name'] = json_decode($d['lvl_name']);
      $res[] = $d;
    }
    $appFlow->flow_data = $res;

    // Log::info('getFlowUpdated completed, returning app flow: ' . json_encode($appFlow));

    return $appFlow;
  }

  // deputy
  public function getManager()
  {
    return vfaUpdatedManagerMdl::all(['id', 'empno', 'name']);
  }
}
// utils
function isActive($empno)
{
  return userMdl::where('empno', $empno)->first()->active;
}
