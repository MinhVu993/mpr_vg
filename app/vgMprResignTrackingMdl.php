<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class vgMprResignTrackingMdl extends Model
{
    protected $table = 'resign-tracking';

    protected $fillable = [
        'req_id',
        'empno',
        'emp_name',
        'dept_code',
        'dept_name',
        'status',
    ];
}
