<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Panel\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\{LogOptions, Traits\LogsActivity};
use Fpaipl\Panel\Traits\{Authx, BelongsToUser, ManageMedia};
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia, MediaCollections\Models\Media};

class Employee extends Model implements HasMedia
{
    use Authx, BelongsToUser, HasActive, SoftDeletes, LogsActivity, InteractsWithMedia;

    const MODEL_LOG_NAME = 'employee-model-log';
    const MEDIA_COLLECTION_NAME = 'employee-image';
    const MEDIA_CONVERSION_THUMB = 'employee-image-thumb';
    const MEDIA_CONVERSION_PREVIEW = 'employee-image-preview';
    
    // Attributes that are mass assignable
    protected $fillable = [
        'uuid',
        'sid',
        'user_id',
        'name',
        'mobile',
        'info',
        'tags',
        'active',
    ];
    
    // Attributes used for searching
    protected $searchable = [
        'name', 'sid', 'info',
    ];

    /**
     * The "booting" method of the model.
     * 
     * @return void
     */
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->sid = self::generateSid();
        });
    }

    public function getRouteKeyName() {
        return 'sid';
    }

    public static function generateSid() { 
        $count = self::withTrashed()->count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 1, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'EM';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }

    public function getTableData($key)
    {
        switch($key){
            default:
                return $this->$key;
        }
    }

    //-------- Relationships --------

    /**
     * Get the messages associated with the party.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages() {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get the ledgers associated with the party.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ledgers() {
        return $this->hasMany(Ledger::class);
    }

    //-------------- Media --------------

    public function getImage() {
        return $this->getFirstMediaUrl($this->getMediaCollectionName(), self::MEDIA_CONVERSION_THUMB);
    }

    /**
     * Get the name of the media collection.
     *
     * @return string
     */
    public function getMediaCollectionName(): string {
        return self::MEDIA_COLLECTION_NAME;
    }

    /**
     * Register media collections for the model.
     *
     * @return void
     */
    public function registerMediaCollections(): void {
        $this
            ->addMediaCollection($this->getMediaCollectionName())
            ->useFallbackUrl(config('panel.uia') . $this->name)
            ->singleFile();
    }

    /**
     * Register media conversions for the model.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion(self::MEDIA_CONVERSION_THUMB)
            ->format('webp')
            ->width(100)
            ->height(100)
            ->sharpen(10)
            ->queued();

        $this->addMediaConversion(self::MEDIA_CONVERSION_PREVIEW)
            ->format('webp')
            ->width(400)
            ->height(400)
            ->sharpen(10)
            ->queued();
    }

    //------------ Activity Log ------------

    /**
     * Get the options for the activity log.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->useLogName(self::MODEL_LOG_NAME);
    }
}
