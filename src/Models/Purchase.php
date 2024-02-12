<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Party;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Purchase extends Model 
{
    use Authx, LogsActivity;

    protected $fillable = [
        'doc_date',
        'doc_id',
        'quantity',
        'total',
        'tax',
        'gross',
        'status',
        'party_id', // Make sure this is the correct field name for the foreign key
        'tags',
    ];

    const STATUS = [
        'received' => 'Received',
        'stocked' => 'Stocked',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    const NEW_PURCHASE_EVENT = 'new_purchase';

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function chats()
    {
        return $this->morphToMany(Chat::class, 'chatable');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function items()
    {
        return $this->purchaseItems;
    }

    public function scopeFilteredPurchases($query, $role, $status, $search, $sortBy, $sortOrder, $partyId = null)
    {

        if($partyId){
            $query->where('party_id', $partyId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tags', 'like', '%' . $search . '%');
            });
        }
        
        if ($sortBy && $sortOrder) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    // Relationship with Dispatch through PurchaseDispatch
    public function dispatches()
    {
        return $this->belongsToMany(Dispatch::class, 'purchase_dispatch');
    }

    public function scopePartyPurchases($query, $userId)
    {
        return $query->where('party_id', $userId);
    }

    public function scopeBrandPurchases($query)
    {
        return $query;
    }

   
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model_log');
    }
}