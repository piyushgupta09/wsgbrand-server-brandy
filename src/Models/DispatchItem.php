<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Dispatch;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DispatchItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'stock_item_id',
        'dispatch_id',
        'quantity',
    ];
    
    //For Cache remember time
    public static $cache_remember; 
    
    public static function getCacheRemember()
    {
        if (!isset(self::$cache_remember)) {
            self::$cache_remember = config('api.cache.remember');
        }

        return self::$cache_remember;
    }

    public static function createDispatchItems($dispatch, $ledger, $quantities)
    {
        $dispatchItems = [];
        foreach($quantities as $color_arr){
            foreach($color_arr as $color_size_sid => $qty){
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock_sid = $ledger->product->slug.'_'.$color_sid."_".$size_sid;
                $stockSku = StockItem::where('sku', $stock_sid)->first();
                $dispatchItems[] = DispatchItem::create([
                    'stock_item_id' => $stockSku->id,
                    'dispatch_id' => $dispatch->id,
                    'quantity' => $qty,
                ]);
            }
        }
        return $dispatchItems;
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }
    
    // Relationship with Dispatch
    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }

    // Relationship with PurchaseItems
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model_log');
    }
}