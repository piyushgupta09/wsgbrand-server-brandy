<?php

namespace Fpaipl\Brandy\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Fpaipl\Panel\Traits\Authx;
use Fpaipl\Brandy\Models\Order;
use Fpaipl\Brandy\Models\Ready;
use Fpaipl\Brandy\Models\Demand;
use Fpaipl\Brandy\Models\Ledger;
use Spatie\MediaLibrary\HasMedia;
use Fpaipl\Brandy\Models\Chatable;
use Fpaipl\Brandy\Models\Dispatch;
use Fpaipl\Brandy\Models\Purchase;
use Spatie\Activitylog\LogOptions;
use Fpaipl\Brandy\Models\Adjustment;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Chat extends Model implements HasMedia
{
    use InteractsWithMedia, Authx, LogsActivity;

    protected $fillable = [
        'uuid', // 'sid',
        'content',
        'type',
        'type_model_id',
        'ledger_id',
        'sender_id',
        'delivered_at',
        'read_at',
    ];

    const TYPES = [
        'text' => 'Text',
        'image' => 'Image',
        'audio' => 'Audio',
        'video' => 'Video',
        'file' => 'File',
    ];

    const MCV_THUMB = 'thumb';
    const MCV_PREVIEW = 'preview';
    const MCN_AUDIO_CHAT = 'audio';
    const MCN_IMAGE_CHAT = 'image';
    const MCN_VIDEO_CHAT = 'video';
    const MCN_FILE_CHAT = 'file';

    // pusher
    const NEW_CHAT_EVENT = 'new_chat';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chat) {
            $chat->uuid = Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function orders()
    {
        return $this->morphedByMany(Order::class, 'chatable');
    }

    public function readies()
    {
        return $this->morphedByMany(Ready::class, 'chatable');
    }

    public function demands()
    {
        return $this->morphedByMany(Demand::class, 'chatable');
    }

    public function adjustments()
    {
        return $this->morphedByMany(Adjustment::class, 'chatable');
    }

    public function ledgers()
    {
        return $this->morphedByMany(Ledger::class, 'chatable');
    }

    public function dispatches()
    {
        return $this->morphedByMany(Dispatch::class, 'chatable');
    }

    public function purchases()
    {
        return $this->morphedByMany(Purchase::class, 'chatable');
    }

    public function chatable(){
        return $this->hasOne(Chatable::class);
    }

    public static function createChatIfNecessary($request, $model, $shouldReturnChat = false)
    {

        switch (get_class($model)) {
            case 'Fpaipl\Brandy\Models\Purchase':
                $ledgerId = $model->dispatches->first()->ledger->id;
                break;
            
            case 'Fpaipl\Brandy\Models\Order':
            case 'Fpaipl\Brandy\Models\Ready':
            case 'Fpaipl\Brandy\Models\Demand':
            case 'Fpaipl\Brandy\Models\Dispatch':
            case 'Fpaipl\Brandy\Models\Adjustment':
                $ledgerId = $model->ledger->id;
                break;

            case 'Fpaipl\Brandy\Models\Ledger':
                $ledgerId = $model->id;
                break;

            default: break;
        }
        
        if ($ledgerId && $request->filled('content')) {

            $content = $request->content;
            $type = 'text';

            $chat = self::create([
                'type' => $type,
                'content' => $content,
                'ledger_id' => $ledgerId,
                'sender_id' => auth()->user()->id,
                'delivered_at' => now(),
            ]);

            if ($request->hasFile('audio')) {
                // $request->validate([
                //     'audio' => 'required|file|mimes:mpga,wav,mp3',
                // ]);
                $chat->addMediaFromRequest('audio')
                     ->usingFileName($chat->id . '.mp3')
                     ->toMediaCollection(self::MCN_AUDIO_CHAT);
                $type = self::MCN_AUDIO_CHAT;
                $content = $chat->audio_url;
            }
    
            $chat->update([
                'type' => $type,
                'content' => $content
            ]);
    
            $chat->chatable()->save(new Chatable([
                'chatable_id' => $model->id,
                'chatable_type' => get_class($model),
            ]));

            if ($shouldReturnChat) {
                return $chat;
            }
        }
    }

    public function getAudioUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::MCN_AUDIO_CHAT);
    }

    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::MCN_IMAGE_CHAT);
    }

    public function getVideoUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::MCN_VIDEO_CHAT);
    }

    public function getFileUrlAttribute()
    {
        return $this->getFirstMediaUrl(self::MCN_FILE_CHAT);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MCN_AUDIO_CHAT)
            ->singleFile();

        $this->addMediaCollection(self::MCN_IMAGE_CHAT)
            ->singleFile();

        $this->addMediaCollection(self::MCN_VIDEO_CHAT)
            ->singleFile();

        $this->addMediaCollection(self::MCN_FILE_CHAT)
            ->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion(self::MCV_THUMB)
            ->width(100)
            ->height(100)
            ->performOnCollections(self::MCN_IMAGE_CHAT);

        $this->addMediaConversion(self::MCV_PREVIEW)
            ->width(400)
            ->height(400)
            ->performOnCollections(self::MCN_IMAGE_CHAT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }
}












// if ($request->hasFile('image')) {
//     $request->validate([
//         'image' => 'required|file|mimes:jpg,jpeg,png',
//     ]);
//     $chat->addMediaFromRequest('image')
//         ->usingFileName($chat->id . '.jpg')
//         ->toMediaCollection('image-chat');
//     $type = self::MCN_IMAGE_CHAT;
//     $content = $chat->image_url;
// }

// if ($request->hasFile('video')) {
//     $request->validate([
//         'video' => 'required|file|mimes:mp4',
//     ]);
//     $chat->addMediaFromRequest('video')
//         ->usingFileName($chat->id . '.mp4')
//         ->toMediaCollection('video-chat');
//     $type = self::MCN_VIDEO_CHAT;
//     $content = $chat->video_url;
// }

// if ($request->hasFile('file')) {
//     $request->validate([
//         'file' => 'required|file|mimes:pdf',
//     ]);
//     $chat->addMediaFromRequest('file')
//         ->usingFileName($chat->id . '.pdf')
    //     ->toMediaCollection('file-chat');
    // $type = self::MCN_FILE_CHAT;
    // $content = $chat->file_url;
// }