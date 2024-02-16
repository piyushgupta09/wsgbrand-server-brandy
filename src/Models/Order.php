<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Po;
use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Jobs\NotifyUser;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Jobs\NotifyGroup;
use Fpaipl\Brandy\Models\OrderItem;
use Fpaipl\Panel\Traits\SearchTags;
use Fpaipl\Panel\Traits\BelongsToUser;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            
            // Update tags
            $priorityString = $model->party->name . ', ' . $model->ledger->product->code . ', ' . $model->ledger->product->name;
            $model = $model->updateMyTags($priorityString);
            $model->saveQuietly();

            // Notify the party
            $title = 'New Order';
            $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
            $action = 'new-orders?search=' . $model->sid;
            self::sendNotification('party-event', $title, $message, $action, $model);
        });
        static::updated(function ($model) {

            if ($model->status == self::STATUS[0]) {
                // If order is issued and have created_at not same as updated_at then send notification, then its a re-issued order
                if ($model->created_at != $model->updated_at) {
                    $title = 'Order Re-issued';
                    $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
                    $partyUser = $model->party->user;
                    $action = 'new-orders?search=' . $model->sid;
                    self::sendNotification('party-event', $title, $message, $action, $model);
                }
            } elseif ($model->status == self::STATUS[5]) {
                $title = 'Order Deleted';
                $message = 'For ' . $model->quantity . ' pcs of #' . $model->ledger->product->code . ' from ' . $model->user->name;
                $partyUser = $model->party->user;
                $action = 'mydashboard';
                self::sendNotification('party-event', $title, $message, $action, $model);
            } else {

                $ledger = $model->ledger;
                $title = Str::title('Order ' . $model->status);
                $message = '#' . $model->sid . ' is ' . $model->status . ' by ' . $model->party->name;
    
                // if order is accepted then update (add) the ledger balance_qty
                if ($model->status == self::STATUS[1]) {
                    $stock = $ledger->product->stock;
                    $newIncoming = $stock->incoming + $model->quantity;
                    $stock->update([
                        'incoming' => $newIncoming,
                        // also perfom auto correction of incoming due to adjustments and other reasons
                    ]);
                }
    
                $action = 'purchases/pos?status=' . $model->status . '&search=' . $model->sid;
                self::sendNotification('brand-event', $title, $message, $action, $model);
            }
            static::saved(function ($model) {
                $model->queued = 0;
                $model->log_status_time = self::setLog($model->status, $model);
                $model->saveQuietly();           
            });
        });
    }

    public static function sendNotification($event, $title, $message, $action, $model)
    {
        NotifyGroup::dispatch(
            title: $title,
            action: $action,
            message: $message,
            event: $event,
            ledgerId: $model->ledger->id,
            skipId: request()->user()->uuid,
        );
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

    // Scopes

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

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS[1]);
    }

    public function scopeNonRejected($query)
    {
        return $query->where('status', '!=', self::STATUS[3]);
    }

    // Relationships

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

    // Activity Log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

}