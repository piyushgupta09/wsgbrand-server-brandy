<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Database\Eloquent\Model;
use Fpaipl\Panel\Traits\Authx;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Chatable extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'chat_id',
        'chatable_type',
        'chatable_id',
    ];

    //For Cache remember time
    public static $cache_remember; 
    
    public static function getCacheRemember()
    {
        if (!isset(self::$cache_remember)) {
            self::$cache_remember = config('api.cache.remember');
        }

        return self::$cache_remember;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                    'id', 
                    'chat_id',
                    'chatable_type',
                    'chatable_id',
                    'created_at', 
                    'updated_at', 
                    'deleted_at'
            ])->useLogName('model_log');
    }
}