<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Purchase;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Brandy\Models\PurchaseDispatch;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseItem extends Model 
{
    use Authx, LogsActivity;

    protected $fillable = [
        'purchase_id',
        'stock_item_id',
        'dispatch_item_id',
        'rate',
        'quantity',
        'amount',
        'status',
        'group_id',
    ];
    
    const STATUS = [
        'received' => 'Received',
        'stocked' => 'Stocked',
        'rejected' => 'Rejected',
    ];
    
    public static $cache_remember; 
    
    public static function getCacheRemember()
    {
        if (!isset(self::$cache_remember)) {
            self::$cache_remember = config('api.cache.remember');
        }

        return self::$cache_remember;
    }

    // Relationship with Purchase
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Relationship with Stock
    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }

    public function group()
    {
        return $this->belongsTo(PurchaseDispatch::class, 'group_id', 'id');
    }

    // Relationship with DispatchItem (assuming DispatchItem model exists)
    public function dispatchItem()
    {
        return $this->belongsTo(DispatchItem::class);
    }

    public function accepter()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }
    
    public static function createPurchaseItems($purchase, $dispatch, $quantities)
    {
        $ledger = $dispatch->ledger;
        $purchaseItems = [];
        foreach ($quantities as $color_size_sid => $qty) {
            [$color_sid, $size_sid] = explode('_', $color_size_sid);
            $stock_sid = $dispatch->ledger->product->slug . '_' . $color_sid . "_" . $size_sid;
            $stockSku = StockItem::where('sku', $stock_sid)->first();

            $group = PurchaseDispatch::firstOrCreate([
                'user_id' => auth()->user()->id,
                'purchase_id' => $purchase->id,
                'dispatch_id' => $dispatch->id,
                'ledger_id' => $ledger->id,
            ]);
            $purchaseItems[] = PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'stock_item_id' => $stockSku->id,
                'status' => 'received',
                'quantity' => $qty,
                'rate' => $stockSku->price,
                'amount' => $qty * $stockSku->price,
                'group_id' => $group->id,
                'dispatch_item_id' => $dispatch->dispatchItems->where('stock_item_id', $stockSku->id)->first()->id,                           
            ]);

            $stockSku->update([
                'quantity' => $stockSku->quantity + $qty,
                'incoming' => $stockSku->incoming - $qty,
            ]);

            $stock = $stockSku->stock;
            $newQuantity = $stock->quantity + $qty;
            $newIncoming = $stock->incoming - $qty;
            $stock->update([
                'quantity' => $newQuantity,
                'incoming' => $newIncoming,
            ]);
            
            // $newQty = $purchase->quantity + $qty;
            $newQty = $qty;

            $purchase->update([
                'quantity' => $purchase->quantity + $newQty,
                'tax' => $purchase->tax + ($newQty * $stockSku->price * 0.18), // 18% GST
                'total' => $purchase->total + ($newQty * $stockSku->price),
            ]);
        }

        $tags = $purchase->doc_id . ', ' . $purchase->party->business . ', ' . $dispatch->ledger->product->tags;
        $purchase->update(['tags' => $tags]);

        $dispatch->billed = true;
        $dispatch->save();

        return $purchaseItems;
    }
   
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('model_log');
    }
}