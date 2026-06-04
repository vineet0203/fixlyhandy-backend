<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class BaseModel extends Model
{
    public $timestamps = true;

    protected static function booted()
    {
        static::creating(function ($model) {
            // created_by
            if (empty($model->created_by)) {
                $model->created_by = Auth::id()
                    ?? config('system.user_id');
            }

            // updated_by
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id()
                    ?? config('system.user_id');
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id()
                ?? config('system.user_id');
        });
    }
}
