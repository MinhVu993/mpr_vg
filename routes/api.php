Route::post('vg-recuitingReport/updateDate', 'vgRecruitingReportCtrl@updateDate');
Route::post('vg-recuitingReport/saveManpower', [vgRecruitingReportCtrl::class, 'saveManpower']);

Route::any('vg-mpr/manualMigrateToDemand', 'vgMprCtrl@manualMigrateToDemand');
