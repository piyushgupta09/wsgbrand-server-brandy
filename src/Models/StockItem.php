<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Stock;
use Fpaipl\Brandy\Models\Ledger;
use Fpaipl\Prody\Models\Product;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\OrderItem;
use Fpaipl\Brandy\Models\ReadyItem;
use Fpaipl\Brandy\Models\DemandItem;
use Fpaipl\Prody\Models\ProductRange;
use Fpaipl\Prody\Models\ProductOption;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockItem extends Model 
{
    use
        Authx,
        SoftDeletes,
        LogsActivity;

    const UPDATE_EVENT = 'update_stock_item';

    protected $fillable = [
        'stock_id',
        'name',
        'sid',
        'sku', 

        'quantity',
        'product_id',
        'product_name',
        'product_sid',
        'product_code',
        'mrp',
        'price',

        'product_option_id',
        'product_option_sid',
        'product_option_name',
        'product_range_id',
        'product_range_sid',
        'product_range_name',

        'note',
        'active',
        'tags',
    ];

    public function scopeForwsg($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->whereHas('productDecisions', function ($q) {
                $q->where('inbulk', true);
            });
        });
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function getRouteKeyName()
    {
        return 'product_sid';
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }

    public function productRange()
    {
        return $this->belongsTo(ProductRange::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function readyItems()
    {
        return $this->hasMany(ReadyItem::class);
    }

    public function demandItems()
    {
        return $this->hasMany(DemandItem::class);
    }

    public function skus() {
        return $this->hasMany(self::class, 'product_sid', 'product_sid');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'product_id', 'product_id')->get();
    }

    public function partiesLedger($partyId = null)
    {
        if ($partyId) {
            // Ledger of given party for given product
            return Ledger::where('party_id', $partyId)->where('product_sid', $this->product_sid)->with('party')->get();
        } else {
            // All ledger for this product
            return Ledger::where('product_sid', $this->product_sid)->get();
        }
        
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty();
    }
}