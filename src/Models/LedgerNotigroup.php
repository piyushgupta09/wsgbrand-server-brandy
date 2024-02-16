<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Database\Eloquent\Model;

class LedgerNotigroup extends Model
{
    protected $fillable = [
        'channel', // user uuid
        'user_id',
        'ledger_id',
    ];

    protected $table = 'ledger_notigroups';

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->channel = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
}
