<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Po;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\OrderItem;
use Fpaipl\Panel\Traits\SearchTags;
use Illuminate\Support\Facades\Log;
use Fpaipl\Panel\Traits\BelongsToUser;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Panel\Events\PushNotification;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fpaipl\Panel\Notifications\AppNotification;
use Fpaipl\Panel\Notifications\WebPushNotification;

class Order extends Model 
{
    use
        Authx,
        SoftDeletes,
        LogsActivity,
        BelongsToUser,
        SearchTags,
        HasRoles;

    const NEW_ORDER_EVENT = 'new_order';
    const UPDATE_ORDER_EVENT = 'update_order';

    protected $fillable = [
        'sid',
        'ledger_id',
        'party_id',
        'quantity',
        'expected_at',
        'log_status_time',
        'status',
        'user_id',
        'reject',
    ];

    protected $searchables = [
        'sid',
        'ledger_id',
        'party_id',
    ];

    public const STATUS = ['issued','accepted','cancelled','rejected','completed','deleted'];
    
    public static function setLog($key, $order = null)
    {
        if($key == self::STATUS[0]){
            $log = [['status' => $key, 'time' => date('Y-m-d H:i:s')]];
        } else {
            $log = json_decode($order->log_status_time, true);
            array_push($log, ['status' => $key, 'time' => date('Y-m-d H:i:s')]);
        }
        return json_encode($log);
    }
    
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
            $title = 'New Order';
            $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
            $partyUser = $model->party->user;
            $action = 'new-orders?search=' . $model->sid;
            // Log::info([
            //     'title' => $title,
            //     'message' => $message,
            //     'action' => $action,
            // ]);
            $partyUser->notify(new WebPushNotification($title, $message, $action));
        });
        static::saved(function ($model) {
            $model->queued = 0;
            $model->log_status_time = self::setLog($model->status, $model);
            $priorityString = $model->party->name . ', ' . $model->ledger->product->code . ', ' . $model->ledger->product->name;
            $model = $model->updateMyTags($priorityString);
            $model->saveQuietly();           
        });
        static::updated(function ($model) {

            if ($model->status == self::STATUS[0]) {
                // If order is issued and have created_at not same as updated_at then send notification, then its a re-issued order
                if ($model->created_at != $model->updated_at) {
                    $title = 'Order Re-issued';
                    $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
                    $partyUser = $model->party->user;
                    $action = 'new-orders?search=' . $model->sid;
                    $partyUser->notify(new WebPushNotification($title, $message, $action));
                }
            } elseif ($model->status == self::STATUS[5]) {
                $title = 'Order Deleted';
                $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
                $partyUser = $model->party->user;
                $action = 'mydashboard';
                $partyUser->notify(new WebPushNotification($title, $message, $action));
            } else {

                $ledger = $model->ledger;
                $title = Str::title('Order ' . $model->status);
                $message = '#' . $model->sid . ' is ' . $model->status . ' by ' . $model->party->name;
    
                // if order is accepted then update (add) the ledger balance_qty
                if ($model->status == self::STATUS[1]) {
                    $stock = $ledger->product->stock;
                    $stock->update([
                        'incoming' => $stock->quantity + $model->quantity,
                    ]);
                }
    
                $brandUser = $model->user;
                $action = 'purchases/pos?status=' . $model->status . '&search=' . $model->sid;
                $brandUser->notify(new WebPushNotification($title, $message, $action));
            }

        });
    }

    public static function generateSid() { 
        $lastRecord = self::orderBy('id', 'desc')->withTrashed()->first(); 
        // If there's no record yet, start with ID 1
        $nextId = $lastRecord ? $lastRecord->id + 1 : 1; 
        $serial = str_pad($nextId, 4, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'OR';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }

    // Helper Functions

    public function scopeFilteredOrders($query, $role, $status, $search, $partyId = null)
    {

        if($partyId){
            $query->where('party_id', $partyId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tags', 'like', '%' . $search . '%')
                    ->orWhereHas('ledger', function ($q) use ($search) {
                        $q->where('tags', 'like', '%' . $search . '%');
                    });
            });
        }
    }

    public function scopePartyOrders($query, $queryId)
    {
        return $query->where('party_id', $queryId);
    }

    public function scopeBrandOrders($query, $queryId=null)
    {
        if ($queryId) {
            // Who create the order, will see the order
            return $query->where('user_id', $queryId);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to filter orders based on user role and optionally by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param string|null $userId The ID of the user, required for staff and fabricator roles.
     * @param string|null $status Optional. The status to filter orders.
     * @param string|null $role The role of the user (staff, fabricator, manager).
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder.
     */
    // public function scopeFilteredOrders($query, $queryId, $status, $search,  $role)
    // {
    //     // switch ($role) {
    //     //     case 'brand':
    //     //         $query->where('user_id', $queryId);
    //     //         break;
    //     //     case 'factory':
    //     //     case 'vendor':
    //     //         $query->whereHas('ledger', function ($q) use ($queryId) {
    //     //             $q->where('party_id', $queryId);
    //     //         });
    //     //         break;
    //     //     default: break;
    //     // }

    //     if ($status) {
    //         $query->where('status', $status);
    //     } else {
    //         $query->whereIn('status', [self::STATUS[0], self::STATUS[1]]);
    //     }

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('sid', 'like', '%' . $search . '%')
    //                 ->orWhereHas('ledger', function ($q) use ($search) {
    //                     $q->where('name', 'like', '%' . $search . '%');
    //                 });
    //         });
    //     }

    //     return $query->orderBy('created_at', 'desc');
    // }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS[1]);
    }

    public function scopeNonRejected($query)
    {
        return $query->where('status', '!=', self::STATUS[3]);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->orderItems;
    }

    public function chats()
    {
        return $this->morphToMany(Chat::class, 'chatable');
    }

    public function pos()
    {
        return $this->hasMany(Po::class, 'order_id');
    }

    public function party()
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                    'id', 
                    'sid',
                    'ledger_id',
                    'quantity',
                    'expected_at',
                    'log_status_time',
                    'status',
                    'user_id',
                    'reject',
                    'created_at', 
                    'updated_at', 
                    'deleted_at'
            ])->useLogName('model_log');
    }

    
}