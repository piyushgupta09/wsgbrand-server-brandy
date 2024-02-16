<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Ledger;
use Illuminate\Database\Eloquent\Model;
use Fpaipl\Brandy\Models\LedgerNotigroup;

class Notigroup extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'ledger_id',
    ];

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
        static::created(function ($model) {
            $ledger = $model->ledger;
            
            $partyUser = $ledger->party->user;
            LedgerNotigroup::create([
                'channel' => $partyUser->uuid,
                'user_id' => $partyUser->id,
                'notigroup_id' => $model->id,
            ]);
            
            $managerUser = $ledger->manager->user;
            LedgerNotigroup::create([
                'channel' => $managerUser->uuid,
                'user_id' => $managerUser->id,
                'notigroup_id' => $model->id,
            ]);
        });
        static::saved(function ($model) {
            $ledger = $model->ledger;
            $party = $model->ledger->party;
            $manager = $ledger->manager;
            $notigroups = $model->ledgerNotigroups;
            foreach ($notigroups as $notigroup) {
                $notigroup->update([
                    'channel' => $party->user->uuid,
                    'user_id' => $party->user->id,
                ]);
            }
            foreach ($notigroups as $notigroup) {
                $notigroup->update([
                    'channel' => $manager->user->uuid,
                    'user_id' => $manager->user->id,
                ]);
            }
        });
    }
    
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function ledgerNotigroups()
    {
        return $this->hasMany(LedgerNotigroup::class);
    }

    public function members()
    {
        return $this->hasMany(LedgerNotigroup::class);
    }
}
