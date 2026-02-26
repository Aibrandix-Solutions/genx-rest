<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class PurchaseAttachment extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['url', 'is_image'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->exists($this->file_path)
            ? asset('storage/' . $this->file_path)
            : '';
    }

    public function getIsImageAttribute(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * Determine file_type from mime type.
     */
    public static function resolveFileType(string $mimeType): string
    {
        return str_starts_with($mimeType, 'image/') ? 'image' : 'document';
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (PurchaseAttachment $attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}
