Route::post('vg-recuitingReport/updateDate', 'vgRecruitingReportCtrl@updateDate');
Route::post('vg-recuitingReport/saveManpower', [vgRecruitingReportCtrl::class, 'saveManpower']);

Route::any('vg-mpr/manualSyncResignTracking', 'vgMprCtrl@manualSyncResignTracking');
Route::any('vg-mpr/manualMigrateToDemand', 'vgMprCtrl@manualMigrateToDemand');
Route::any('vg-mpr/checkResignEmpNo', 'vgMprCtrl@checkResignEmpNo');
