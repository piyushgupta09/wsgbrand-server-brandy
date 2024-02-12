<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Ready;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class ReadyItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'stock_item_id',
        'ready_id',
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

    public static function createReadyItems($ready, $ledger, $quantities)
    {
        $readyItems = [];
        foreach($quantities as $color_arr){
            foreach($color_arr as $color_size_sid => $qty){
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock_sid = $ledger->product->slug.'_'.$color_sid."_".$size_sid;
                $stockSku = StockItem::where('sku', $stock_sid)->first();
                $readyItems[] = ReadyItem::create([
                    'stock_item_id' => $stockSku->id,
                    'ready_id' => $ready->id,
                    'quantity' => $qty,
                ]);
            }
        }
        return $readyItems;
    }

    public function ready()
    {
        return $this->belongsTo(Ready::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                    'id', 
                    'stock_item_id',
                    'ready_id',
                    'quantity',
                    'created_at', 
                    'updated_at', 
            ])->useLogName('model_log');
    }
}