<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Ledger;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\ReadyItem;
use Fpaipl\Panel\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fpaipl\Panel\Notifications\WebPushNotification;

class Ready extends Model 
{
    use
        Authx,
        SoftDeletes,
        BelongsToUser,
        LogsActivity;

    const NEW_READY_EVENT = 'new_ready';

    protected $fillable = [
        'sid',
        'ledger_id',
        'quantity',
        'user_id',
    ];

    public function getRouteKeyName()
    {
        return 'sid';
    }
    
    //For Cache remember time
    public static $cache_remember; 
    
    public static function getCacheRemember()
    {
        if (!isset(self::$cache_remember)) {
            self::$cache_remember = config('api.cache.remember');
        }

        return self::$cache_remember;
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->sid = self::generateSid();
        });
        static::created(function ($model) {
            $ledger = $model->ledger;
            $ledger->update([
                'last_activity' => Str::afterLast(get_class($model), '\\'),
                'readyable_qty' => $ledger->readyable_qty - $model->quantity,
                'total_ready' => $ledger->total_ready + $model->quantity,
                'demandable_qty' => $ledger->demandable_qty + $model->quantity,
            ]);
            $ledger->save();
            // Send Notification
            $title = Str::title('New Ready by ' . $ledger->party->user->name);
            $message = '#' . $ledger->product_sid . ', has been ready for ' . $model->quantity . ' pcs.';
            // fetch all users that has role of brand manager
            $brandManagers = User::whereHas('roles', function ($query) {
                $query->where('name', 'manager-brand');
            })->get();
            // send notification to all brand managers
            foreach ($brandManagers as $brandManager) {
                $action = 'products/ledger/' . $ledger->sid;
                $brandManager->notify(new WebPushNotification($title, $message, $action));
            }
        });
    }

    public static function generateSid() { 
        $count = self::all()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 4, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'RD';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }  

    // Helper Functions

    public function scopeStaffRedies($query, $userId, $ledgerSid = null)
    {
        if(!empty($ledgerSid)){
            $query->where('ledger_sid', $ledgerSid);
        }

        return $query->whereHas('ledger', function ($query){
        })->whereHas('orders', function($query) use($userId){
            $query->where('user_id', $userId);
        })->orderBy('created_at', 'desc');

    }

    public function scopeFabricatorRedies($query, $userId, $ledgerSid = null)
    {
        $query->where('user_id', $userId);
        if(!empty($ledgerSid)){
            $query->where('ledger_sid', $ledgerSid);
        }
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeManagerRedies($query, $ledgerSid = null)
    {
        if(!empty($ledgerSid)){
            $query->where('ledger_sid', $ledgerSid);
        }
        
        return $query->orderBy('created_at', 'desc');
    }
  
    // Relationships

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function readyItems()
    {
        return $this->hasMany(ReadyItem::class);
    }

    public function items()
    {
        return $this->readyItems;
    }

    public function chats()
    {
        return $this->morphToMany(Chat::class, 'chatable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                    'id', 
                    'sid',
                    'ledger_id',
                    'quantity',
                    'user_id',
                    'created_at', 
                    'updated_at', 
                    'deleted_at'
            ])->useLogName('model_log');
    }
}