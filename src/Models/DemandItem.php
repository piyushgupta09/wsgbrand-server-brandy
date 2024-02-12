<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Demand;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class DemandItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'stock_item_id',
        'demand_id',
        'quantity',
    ];

    public static function createDemandItems($demand, $ledger, $quantities)
    {
        $demandItems = [];
        foreach($quantities as $color_arr){
            foreach($color_arr as $color_size_sid => $qty){
                [$color_sid, $size_sid] = explode('_', $color_size_sid);
                $stock_sid = $ledger->product->slug.'_'.$color_sid."_".$size_sid;
                $stockSku = StockItem::where('sku', $stock_sid)->first();
                $demandItems[] = DemandItem::create([
                    'stock_item_id' => $stockSku->id,
                    'demand_id' => $demand->id,
                    'quantity' => $qty,
                ]);
            }
        }
        return $demandItems;
    }

    public function demand()
    {
        return $this->belongsTo(Demand::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity'])
            ->useLogName('demand_item')
            ->logFillable();
    }
}