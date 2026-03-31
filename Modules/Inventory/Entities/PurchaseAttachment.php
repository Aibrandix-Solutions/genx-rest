<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use App\Models\User;

class PurchaseAttachment extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['url', 'is_image'];

    // Folder inside public/user-uploads where attachments are stored
    const UPLOAD_DIR = 'purchase-attachments';

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Returns the public URL for the attachment.
     * Files are stored at public/user-uploads/purchase-attachments/{file_path}
     */
    public function getUrlAttribute(): string
    {
        $fullPath = public_path('user-uploads/' . self::UPLOAD_DIR . '/' . $this->file_path);

        return File::exists($fullPath)
            ? asset('user-uploads/' . self::UPLOAD_DIR . '/' . $this->file_path)
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
     * Delete the physical file when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (PurchaseAttachment $attachment) {
            $fullPath = public_path('user-uploads/' . self::UPLOAD_DIR . '/' . $attachment->file_path);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        });
    }
}
