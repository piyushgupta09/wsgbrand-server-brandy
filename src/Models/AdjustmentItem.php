<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\StockItem;
use Fpaipl\Brandy\Models\Adjustment;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class AdjustmentItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'stock_item_id',
        'adjustment_id',
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
    
    // Relationships

    public function adjustment()
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }


    public static function createAdjustmentItems($adjustment, $ledger, $quantities)
    {
        $adjustmentItems = [];
        foreach($quantities as $color_arr){
            foreach($color_arr as $color_size_sid => $qty){
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock_sid = $ledger->product->slug.'_'.$color_sid."_".$size_sid;
                $stockSku = StockItem::where('sku', $stock_sid)->first();
                $adjustmentItems[] = AdjustmentItem::create([
                    'stock_item_id' => $stockSku->id,
                    'adjustment_id' => $adjustment->id,
                    'quantity' => $qty,
                ]);
            }
        }
        return $adjustmentItems;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                    'id', 
                    'stock_item_id',
                    'order_id',
                    'quantity',
                    'created_at', 
                    'updated_at', 
            ])->useLogName('model_log');
    }
}