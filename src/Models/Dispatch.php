<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\Purchase;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Panel\Traits\SearchTags;
use Fpaipl\Brandy\Models\DispatchItem;
use Fpaipl\Panel\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Brandy\Models\PurchaseDispatch;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fpaipl\Panel\Notifications\WebPushNotification;

class Dispatch extends Model 
{
    use
        Authx,
        SearchTags,
        SoftDeletes,
        BelongsToUser,
        LogsActivity;
    
    const NEW_DISPATCH_EVENT = 'new_dispatch';

    protected $fillable = [
        'sid',
        'ledger_id',
        'quantity',
        'user_id',
        'billed',
        'party_id',
        'tags',
    ];

    protected $searchables = [
        'sid',
        'ledger_id',
        'party_id',
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
        static::saved(function ($model) {
            $priorityString = $model->party->name . ', ' . $model->ledger->product->code . ', ' . $model->ledger->product->name;
            $model = $model->updateMyTags($priorityString);
            $model->saveQuietly();           
        });
        static::created(function ($model) {
            $ledger = $model->ledger;
            $ledger->update([
                'last_activity' => Str::afterLast(get_class($model), '\\'),
                'dispatchable_qty' => $ledger->dispatchable_qty - $model->quantity,
                'total_dispatch' => $ledger->total_dispatch + $model->quantity,
                'balance_qty' => $ledger->balance_qty - $model->quantity,
            ]);
            $ledger->saveQuietly();

            // Send Notification
            $title = 'New Dispatch';
            $message = '#' . $ledger->product_sid . ', has been dispatched with ' . $model->quantity . ' pcs.';
            // fetch all users that has role of brand manager
            $brandManagers = User::whereHas('roles', function ($query) {
                $query->where('name', 'manager-brand');
            })->get();
            // send notification to all brand managers
            foreach ($brandManagers as $brandManager) {
                $action = 'purchases/incomings?search=' . $ledger->sid;
                $brandManager->notify(new WebPushNotification($title, $message, $action));
            }
        });
    }

    public static function generateSid() { 
        $count = self::all()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 3, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'DS';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }     

    // Helper Functions
    public function scopeFilteredDispatches($query, $role, $status, $search, $sortBy, $sortOrder, $partyId = null)
    {

        if($partyId){
            $query->where('party_id', $partyId);
        }

        if ($status !== null) {
            if ($status == 'pending') {
                $query->where('billed', false);
            } else if ($status == 'received') {
                $query->where('billed', true);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tags', 'like', '%' . $search . '%')
                    ->orWhereHas('ledger', function ($q) use ($search) {
                        $q->where('tags', 'like', '%' . $search . '%');
                    });
            });
        }
        
        if ($sortBy && $sortOrder) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    // userId is the party->user->id
    public function scopePartyDispatches($query, $userId)
    {
        return $query->where('party_id', $userId);
    }

    public function scopeBrandDispatches($query)
    {
        return $query;
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }
  
    // Relationships

    public function purchaseDispatches()
    {
        return $this->hasMany(PurchaseDispatch::class);
    }

    public function purchaseDispatch()
    {
        return $this->hasOne(PurchaseDispatch::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function items()
    {
        return $this->dispatchItems;
    }

    public function chats()
    {
        return $this->morphToMany(Chat::class, 'chatable');
    }

    public function purchases()
    {
        return $this->belongsToMany(Purchase::class, 'purchase_dispatch');
    }

    // Relationship with DispatchItems (if DispatchItem model exists)
    public function dispatchItems()
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model_log');
    }
}