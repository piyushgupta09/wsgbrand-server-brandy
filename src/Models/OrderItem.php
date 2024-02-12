<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Order;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\StockItem;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'stock_item_id',
        'order_id',
        'quantity',
    ];

    const MODEL_LOG_NAME = 'order-item-model-log';
    
    //For Cache remember time
    public static $cache_remember; 
    
    public static function getCacheRemember()
    {
        if (!isset(self::$cache_remember)) {
            self::$cache_remember = config('api.cache.remember');
        }

        return self::$cache_remember;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }

    public static function createOrderItems($order, $ledger, $quantities)
    {
        $orderItems = [];
        foreach($quantities as $color_arr){
            foreach($color_arr as $color_size_sid => $qty){
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock_sid = $ledger->product->slug.'_'.$color_sid."_".$size_sid;
                $stockSku = StockItem::where('sku', $stock_sid)->first();
                $orderItems[] = OrderItem::create([
                    'stock_item_id' => $stockSku->id,
                    'order_id' => $order->id,
                    'quantity' => $qty,
                ]);
            }
        }
        return $orderItems;
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->useLogName(self::MODEL_LOG_NAME);
    }
}