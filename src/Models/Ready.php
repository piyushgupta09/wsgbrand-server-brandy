<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Jobs\NotifyUser;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Jobs\NotifyGroup;
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

    // Events

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->sid = self::generateSid();
        });
        static::created(function ($model) {

            // Upadte Ledger
            $ledger = $model->ledger;
            $ledger->update([
                'last_activity' => Str::afterLast(get_class($model), '\\'),
                'total_ready' => $ledger->total_ready + $model->quantity,
                'readyable_qty' => $ledger->readyable_qty - $model->quantity,
                'demandable_qty' => $ledger->demandable_qty + $model->quantity,
            ]);
            
            // Prepare Notification Data
            $title = Str::title('New Ready');
            $message = 'Batch of ' . $model->quantity . 'pcs of #' . $ledger->product_sid . ', is ready by ' . $ledger->party->user->name;
            $action = 'products/ledger/' . $ledger->sid;
            NotifyGroup::dispatch(
                title: $title,
                action: $action,
                message: $message,
                event: 'brand-event',
                ledgerId: $ledger->id,
                skipId: request()->user()->uuid,
            );
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

    // Scopes

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