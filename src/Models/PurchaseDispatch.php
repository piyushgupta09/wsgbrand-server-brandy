<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Purchase;
use Fpaipl\Brandy\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;

class PurchaseDispatch extends Model
{
    protected $table = 'purchase_dispatch';

    protected $fillable = [
        'user_id',        // Accepted by
        'purchase_id',    // Foreign key to Purchase
        'dispatch_id',    // Foreign key to Dispatch
        'ledger_id',      // Foreign key to Ledger
    ];

    protected static function boot() {
        parent::boot();
        static::created(function ($model) {
            $ledger = $model->dispatch->ledger;
            $ledger->update([
                'balance_qty' => $ledger->balance_qty - $model->quantity,
            ]);
            $ledger->save();
        });
    }

    public function acceptor()
    {
        return $this->belongsTo(User::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'group_id', 'id');
    }
}
