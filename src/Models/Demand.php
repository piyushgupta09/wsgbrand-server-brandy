<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Ledger;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\DemandItem;
use Fpaipl\Panel\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fpaipl\Panel\Notifications\WebPushNotification;

class Demand extends Model 
{
    use
        Authx,
        SoftDeletes,
        BelongsToUser,
        LogsActivity;

    public const NEW_DEMAND_EVENT = 'new_demand';

    protected $fillable = [
        'sid',
        'ledger_id',
        'quantity',
        'expected_at',
        'user_id',
        'tolerance',
    ];
    
    public function getRouteKeyName()
    {
        return 'sid';
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
                'demandable_qty' => $ledger->demandable_qty - $model->quantity,
                'total_demand' => $ledger->total_demand + $model->quantity,
                'dispatchable_qty' => $ledger->dispatchable_qty + $model->quantity,
            ]);
            $ledger->saveQuietly();
            
            // Send Notification
            $title = 'New Demand';
            $message = 'You have received a new demand from ' . $model->user->name;
            $action = 'ledgers/' . $ledger->sid;
            $model->ledger->party->user->notify(new WebPushNotification($title, $message, $action));
        });
    }

    public static function generateSid() { 
        $count = self::all()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 3, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'DM';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }  

    // // Helper Functions

    // public function scopeStaffDemands($query, $userId, $ledgerSid = null)
    // {

    //     $query->where('user_id', $userId);
    //     if(!empty($ledgerSid)){
    //         $query->where('ledger_sid', $ledgerSid);
    //     }
        
    //     return $query->orderBy('created_at', 'desc');

    // }

    // public function scopeFabricatorDemands($query, $userId, $ledgerSid = null)
    // {
    //     if(!empty($ledgerSid)){
    //         $query->where('ledger_sid', $ledgerSid);
    //     }

    //     return $query->whereHas('ledger', function ($query){
    //         $query->where('party_id', auth()->user()->party->id);
    //     })->orderBy('created_at', 'desc');
    // }

    // public function scopeManagerDemands($query, $ledgerSid = null)
    // {
    //     if(!empty($ledgerSid)){
    //         $query->where('ledger_sid', $ledgerSid);
    //     }
        
    //     return $query->orderBy('created_at', 'desc');
    // }
    
    // Relationships
    
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function demandItems()
    {
        return $this->hasMany(DemandItem::class);
    }

    public function items()
    {
        return $this->demandItems;
    }

    public function chats()
    {
        return $this->morphToMany(Chat::class, 'chatable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model_log');
    }
}