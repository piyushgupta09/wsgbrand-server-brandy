<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class MonaalBill extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'monaal_id',
        'uuid',
        'doc_no',
        'doc_date',
        'customer_sid',
        'status',
        'note',
        'amount',
        'payable',
        'balance',
        'tags',
        'completed_by',
        'completed_at',
        'paid_at',
        'details',
        'pos',
    ];

    const STATUS = [
        'draft',
        'issued',
        'partial',
        'completed',
        'cancelled',
    ];

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFilteredBills($query, $status, $search)
    {
        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('doc_no', 'like', '%' . $search . '%')
                    ->orWhere('doc_date', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%')
                    ->orWhere('tags', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function scopePartyBills($query, $queryId)
    {
        return $query->where('customer_sid', $queryId)->orderBy('created_at', 'desc');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }
}