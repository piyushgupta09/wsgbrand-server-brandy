<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Party;
use Fpaipl\Brandy\Models\PoItem;
use Fpaipl\Prody\Models\Product;
use Fpaipl\Prody\Models\Material;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Po extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'order_id', 'product_id', 'party_id', 'material_id', 'name',
        'm_customer_id', 'm_customer_name', 'm_product_id', 'm_order_id', 'm_catelog_id',
        'status', 'accepted_at', 'order_quantity', 'sid', 'uuid',
        'pre_order', 'rate', 'quantity', 'amount', 'note', 'issued_by', 
        'issued_at', 'completed_by', 'completed_at', 'delivery_mode', 'expiry', 
        'tol_rate', 'tol_quantity', 'tol_expiry', 'payment_terms', 
        'rejection_terms', 'special_terms', 'archived', 'reason',
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

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function poItems()
    {
        return $this->hasMany(PoItem::class);
    }
   
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public static function removeSupplierPrefix($string) {
        $result = strstr($string, "-");
        return ltrim($result, '-');
    }
}