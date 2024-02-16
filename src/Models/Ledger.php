<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Brandy\Models\Chat;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\Ready;
use Fpaipl\Brandy\Models\Stock;
use Fpaipl\Brandy\Models\Demand;
use Fpaipl\Prody\Models\Product;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Employee;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\Notigroup;
use Fpaipl\Brandy\Models\Adjustment;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Brandy\Models\LedgerNotigroup;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use
        Authx,
        SoftDeletes,
        LogsActivity;

    protected $fillable = [
        'sid',
        'name',
        'product_sid',
        'product_id',
        'stock_id',
        'party_id',
        'employee_id',
        'note',
        'last_activity',
        'min_qty',
        'max_qty',
        'fee_rate',
        'order_cap',
        'fab_rate',
        'balance_qty',
        'readyable_qty',
        'demandable_qty',
        'dispatchable_qty',
        'order_adj',
        'ready_adj',
        'demand_adj',
        'total_order',
        'total_ready',
        'total_demand',
        'total_dispatch',
        'tags',
    ];

    const NEW_LEDGER = 'new_ledger';

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
            $searchtags[] = $model->sid;
            $searchtags[] = $model->name;
            $searchtags[] = $model->product_sid;
            $searchtags[] = $model->party->name;
            $searchtags[] = $model->party->mobile;
            $searchtags = array_unique($searchtags);
            $model->tags = implode(', ', $searchtags);
            $model->saveQuietly();

            // Add party user to ledger notigroup
            if ($model->party) {
                LedgerNotigroup::create([
                    'user_id' => $model->party->user->id,
                    'ledger_id' => $model->id,
                ]);
            }

            // Add employee user to ledger notigroup
            if ($model->manager) {
                LedgerNotigroup::create([
                    'user_id' => $model->manager->user->id,
                    'ledger_id' => $model->id,
                ]);
            }

        });
    }

    public static function generateSid() { 
        $count = self::withTrashed()->get()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 3, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'LR';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }

    /**
     * Scope a query to filter ledgers based on product and party.
     *
     * This generic scope filters the ledgers by product and optionally by party.
     * It first finds a stock based on the provided product SID and then filters ledgers by this product.
     * If a party SID is provided, it also filters by this party.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param string|null $productSid Optional. The SID of the product to filter ledgers by.
     * @param string|null $partySid Optional. The SID of the party to further filter ledgers.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder.
     */
    public function scopeFilteredLedgers($query, $productSid = null, $partySid = null)
    {
        if (!empty($productSid)) {
            $stock = Stock::where('product_sid', $productSid)->first();
            if ($stock) {
                $query->where('product_id', $stock->product_id);
            }
        }

        if (!empty($partySid)) {
            $party = Party::where('sid', $partySid)->first();
            if ($party) {
                $query->where('party_id', $party->id);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }


    public function getLatestOrder()
    {
        return $this->orders()->latest()->first();
    }

    public function latestorder()
    {
        return $this->hasOne(Order::class)->latest();
    }

    public function getDispatchableQty()
    {
        $ledgerDemandQty = $this->demands->sum('quantity');
        $ledgerDispatchQty = $this->dispatches->sum('quantity');
        return $ledgerDemandQty - $ledgerDispatchQty;
    }

    public function getUnbilledDispatchedQty()
    {
        $alreadyPurchased = 0;
        $ledgerDispatchQty = $this->dispatches->sum('quantity');
        return $ledgerDispatchQty - $alreadyPurchased;
    }

    public function getNetReadyableQty()
    {
        return $this->readyable_qty - $this->order_adj + $this->ready_adj;
    }

    public function getNetDemandableQty()
    {
        return $this->demandable_qty - $this->ready_adj + $this->demand_adj;
    }

    public function getNetDispatchableQty()
    {
        $vendorDispatchableQty = $this->dispatchable_qty - $this->order_adj;
        if ($this->party->user->isVendor()) {
            return $vendorDispatchableQty;
        }
        return $this->dispatchable_qty - $this->demand_adj;
    }

    // 
    public function getNetStockableQty()
    {
        $totalDispatchesQuantity = $this->dispatches()->with('dispatchItems')->get()->sum(function($dispatch) {
            return $dispatch->dispatchItems->sum('quantity');
        });

        // $totalPurchasesQuantity = 0;
        // $this->dispatches->each(function($dispatch) use (&$totalPurchasesQuantity) {
        //     $dispatch->purchaseDispatches->each(function($purchaseDispatch) use (&$totalPurchasesQuantity) {
        //         $purchaseDispatch->purchase->purchaseItems->each(function($purchaseItem) use (&$totalPurchasesQuantity) {
        //             $totalPurchasesQuantity += $purchaseItem->quantity;
        //         });
        //     });
        // });
        return $totalDispatchesQuantity;
        // return [
        //     'totalDispatchesQuantity' => $totalDispatchesQuantity,
        //     'totalPurchasesQuantity' => $totalPurchasesQuantity,
        // ];
    }

    public function getLedgerBalanceQty()
    {
        return $this->balance_qty - $this->order_adj; // + $this->ready_adj - $this->demand_adj;
    }

    public function getUnacceptedOrders()
    {
        return $this->orders()->where('status', Order::STATUS[0])->sum('quantity');
    }

    public function getImage($conversion)
    {
        return $this->product?->getImage($conversion);
    }

    // Relationships

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->with('chats');
    }

    public function readies()
    {
        return $this->hasMany(Ready::class)->with('chats');
    }

    public function dispatches()
    {
        return $this->hasMany(Dispatch::class)->with('chats');
    }

    public function demands()
    {
        return $this->hasMany(Demand::class)->with('chats');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function adjustments()
    {
        return $this->hasMany(Adjustment::class)->with('chats');
    }

    public function party()
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function notigroups()
    {
        return $this->hasMany(LedgerNotigroup::class, 'ledger_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty();
    }


    public static function findAndUpdateLedgerLastActivity($stock, $party, $activity)
    {
        $ledger = self::where('product_id', $stock->product_id)
                        ->where('party_id', $party->id)
                        ->first();

        if ($ledger && $ledger->last_activity != $activity) {
            $ledger->update(['last_activity' => $activity]);
        }

        return $ledger;
    }

}
