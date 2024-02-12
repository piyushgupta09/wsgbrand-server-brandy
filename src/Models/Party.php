<?php

namespace Fpaipl\Brandy\Models;

use Illuminate\Support\Str;
use Fpaipl\Brandy\Models\Po;
use Fpaipl\Panel\Traits\HasActive;
use Fpaipl\Authy\Traits\HasAddresses;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\{LogOptions, Traits\LogsActivity};
use Fpaipl\Panel\Traits\{Authx, BelongsToUser, ManageMedia};
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia, MediaCollections\Models\Media};

/**
 * The Party model represents a party entity in the application.
 * It is used to manage information about various types of parties (like staff, fabricator, etc.).
 * 
 * @property int $user_id User identifier related to the party
 * @property string $business Name of the business
 * @property string $gst GST number
 * @property string $pan PAN number
 * @property string $sid Unique identifier for the party
 * @property string $type Type of the party (e.g., staff, fabricator)
 * @property int $monaal_id Fabricator's SID as per monaal.in
 * @property string $info Additional information
 * @property string $tags Searchable tags
 * @property bool $active Status of the party (active/inactive)
 */
class Party extends Model implements HasMedia
{
    use Authx, BelongsToUser, HasActive, SoftDeletes, LogsActivity, InteractsWithMedia, HasRoles, HasAddresses;

    const MODEL_LOG_NAME = 'party-model-log';
    const MEDIA_COLLECTION_NAME = 'party-image';
    const MEDIA_CONVERSION_THUMB = 'party-image-thumb';
    const MEDIA_CONVERSION_PREVIEW = 'party-image-preview';
    
    // Attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'sid',
        'type',
        'monaal_id',
        'info',
        'tags',
        'active',
        'name',
        'business',
        'gstin',
        'pan',
        'print',
        'line1',
        'line2',
        'state',
        'country',
        'pincode',
        'mobile',
        'contact',
    ];
    
    // Attributes used for searching
    protected $searchable = [
        'business', 'gst', 'pan', 'sid', 'type', 'monaal_id', 'info',
    ];

    // Predefined types of parties
    public const TYPE = [
        'staff', 'manager', 'fabricator', 'customer', 'supplier',
    ];

    const PRODUCT_VENDOR = 'product-vendor';
    const PRODUCT_FATORY = 'product-factory';

    const TYPES = [
        'product-vendor' => 'Product Vendor',
        'product-factory' => 'Product Factory',
    ];

    /**
     * The "booting" method of the model.
     * 
     * @return void
     */
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->sid = self::generateSid();
            $model->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName() {
        return 'sid';
    }

    // public function address() {
    //     $addressString = $this->line1 . ($this->line1 ? ', ' : '') . $this->line2 . ($this->line2 ? ', ' : '') . $this->state . ($this->state ? ', ' : '') . ', ' . $this->country . ', ' . $this->pincode;
    //     $addressIsEmpty = Str::of(Str::replace(',', '', $addressString))->trim()->isEmpty();
    //     if ($addressIsEmpty) {
    //         return 'Address not available';
    //     }
    //     return $addressString;
    // }

    /**
     * Generate a unique SID for the party.
     *
     * @return string
     */
    public static function generateSid() { 
        $count = self::count();
        $lastCount = $count ? $count : 0;
        $serial = str_pad($lastCount, 2, '0', STR_PAD_LEFT);
        $seprator = '-';
        $brandPrefix = 'DG';
        $modelPrefix = 'PY';
        return $brandPrefix . $seprator . $modelPrefix . $seprator . $serial;
    }

    public function getTableData($key)
    {
        switch($key){
            case 'type':
                return Str::title(Str::replace('-', ' ', $this->type));
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

    public function pos() {
        return $this->hasMany(Po::class);
    }

    //-------- Scopes --------

    /**
     * Scope a query to filter parties by role and status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $role The role to filter by.
     * @param bool|string $status The status to filter by, accepts 'active', 'inactive' or a boolean.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGetParty($query, $role = null, $status = true) {
        if (!is_null($role)) {
            $query->where('type', $role);
        }

        if (is_bool($status)) {
            $query->where('active', $status);
        } elseif ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        return $query;
    }

    public static function scopeRole($query, $role = null) {
        if (!is_null($role)) {
            $query->where('type', $role);
        }
        return $query;
    }

    //-------------- Media --------------

    public function getImage($conversion = self::MEDIA_CONVERSION_THUMB) {
        return $this->getFirstMediaUrl($this->getMediaCollectionName(), $conversion);
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
            ->useFallbackUrl(config('panel.uia') . $this->business)
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
