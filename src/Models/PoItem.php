<?php

namespace Fpaipl\Brandy\Models;

use Fpaipl\Brandy\Models\Po;
use Fpaipl\Panel\Traits\Authx;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Prody\Models\MaterialRange;
use Fpaipl\Prody\Models\MaterialOption;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class PoItem extends Model 
{
    use
        Authx,
        LogsActivity;

    protected $fillable = [
        'po_id', 'material_option_id', 'material_range_id', 'product_option_id', 
        'product_range_id', 'order_quantity', 'fcpu', 'quantity', 'rate', 
        'amount', 'so_id', 'note', 'name',
    ];

    const MODEL_LOG_NAME = 'po-item-model-log';

    public function po()
    {
        return $this->belongsTo(Po::class);
    }

    public function materialOption()
    {
        return $this->belongsTo(MaterialOption::class);
    }

    public function materialRange()
    {
        return $this->belongsTo(MaterialRange::class);
    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->useLogName(self::MODEL_LOG_NAME);
    }
}