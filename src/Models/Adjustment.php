<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Ledger;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Jobs\NotifyGroup;
use Fpaipl\Panel\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Brandy\Models\AdjustmentItem;
use Spatie\Activitylog\Traits\LogsActivity;
use Fpaipl\Panel\Notifications\WebPushNotification;

class Adjustment extends Model 
{
    use
        Authx,
        BelongsToUser,
        LogsActivity;

    protected $fillable = [
        'sid',
        'ledger_id',
        'quantity',
        'user_id',
        'type',
    ];

    const NEW_ADJUSTMENT_EVENT = 'new_adjustment';
    
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->sid = self::generateSid();
        });
        static::created(function ($model) {
            $title = 'New Adjustment';
            $message = '#' . $model->ledger->product_sid . ', is adjusted with ' . $model->quantity . ' pcs.';
            if ($model->type == 'ready') {
                $action = 'products/ledger/' . $model->ledger->sid;
                NotifyGroup::dispatch(
                    title: $title,
                    action: $action,
                    message: $message,
                    event: 'brand-event',
                    ledgerId: $model->ledger->id,
                    skipId: request()->user()->uuid,
                );
            } elseif ($model->type == 'order' || $model->type == 'demand') {
                $action = 'ledgers/' . $model->ledger->sid;
                NotifyGroup::dispatch(
                    title: $title,
                    action: $action,
                    message: $message,
                    event: 'party-event',
                    ledgerId: $model->ledger->id,
                    skipId: request()->user()->uuid,
                );
            }
        });
    }

    public static function generateSid() { 
        $count = self::all()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 2, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'AJ';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }     

    // Helper Functions

    // public function scopeStaffAdjustments($query, $userId)
    // {
    //     $query->where('user_id', $userId);
    //     return $query->orderBy('created_at', 'desc');
    // }

    // public function scopeFabricatorAdjustments($query, $userId)
    // {
    //     return $query->whereHas('ledger', function ($query) {
    //         $query->where('party_id', auth()->user()->party->id);
    //     })->orderBy('created_at', 'desc');
    // }

    // public function scopeManagerAdjustments($query)
    // {
    //     return $query->orderBy('created_at', 'desc');
    // }
    
    // Relationships

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function adjustmentItems()
    {
        return $this->hasMany(AdjustmentItem::class);
    }

    public function items()
    {
        return $this->adjustmentItems;
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
                    'user_id',
                    'ledger_id',
                    'quantity',
                    'type',
                    'created_at', 
                    'updated_at', 
            ])->useLogName('model_log');
    }
}
