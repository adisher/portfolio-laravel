<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'extension',
        'size',
        'disk',
        'path',
        'folder',
        'metadata',
        'variants',
        'alt_text',
        'description',
        'user_id'
    ];

    protected $casts = [
        'metadata' => 'array',
        'variants' => 'array',
    ];

    protected $appends = ['url', 'formatted_size', 'is_image'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getUrlAttribute()
    {
        // Make sure the storage disk URL is correct
        if ($this->disk === 'public') {
            return asset('storage/' . $this->path);
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getFormattedSizeAttribute()
    {
        return $this->formatBytes($this->size);
    }

    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    // Get variant URL
    public function getVariantUrl($variant = 'thumbnail')
    {
        if (!$this->variants || !isset($this->variants[$variant])) {
            return $this->url;
        }

        if ($this->disk === 'public') {
            return asset('storage/' . $this->variants[$variant]);
        }

        return Storage::disk($this->disk)->url($this->variants[$variant]);
    }

    // Static Methods
    public static function uploadFile($file, $folder = '/', $disk = 'public')
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate unique filename
        $fileName = time() . '_' . uniqid() . '.' . $extension;
        
        // Clean folder path
        $folder = trim($folder, '/');
        $folder = $folder ? '/' . $folder : '/';
        
        // Store file
        $path = $file->storeAs(
            'media' . $folder,
            $fileName,
            $disk
        );

        // Create media record
        $media = static::create([
            'name' => $originalName,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'disk' => $disk,
            'path' => $path,
            'folder' => $folder,
            'user_id' => auth()->id(),
        ]);

        // Process image if it's an image file
        if ($media->is_image) {
            $media->processImage();
        }

        return $media;
    }

    // Process image variants
    public function processImage()
    {
        if (!$this->is_image) {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read(Storage::disk($this->disk)->get($this->path));

            // Get original dimensions
            $width = $image->width();
            $height = $image->height();

            // Update metadata
            $this->update([
                'metadata' => [
                    'width' => $width,
                    'height' => $height,
                ]
            ]);

            $variants = [];

            // Create thumbnail (300x300)
            $thumbnail = $image->cover(300, 300);
            $thumbnailPath = $this->getVariantPath('thumbnail');
            Storage::disk($this->disk)->put($thumbnailPath, (string) $thumbnail->toJpeg(85));
            $variants['thumbnail'] = $thumbnailPath;

            // Create medium size (800px max width)
            if ($width > 800) {
                $medium = $image->scaleDown(width: 800);
                $mediumPath = $this->getVariantPath('medium');
                Storage::disk($this->disk)->put($mediumPath, (string) $medium->toJpeg(90));
                $variants['medium'] = $mediumPath;
            }

            // Create optimized version (1920px max width)
            if ($width > 1920) {
                $optimized = $image->scaleDown(width: 1920);
                $optimizedPath = $this->getVariantPath('optimized');
                Storage::disk($this->disk)->put($optimizedPath, (string) $optimized->toJpeg(90));
                $variants['optimized'] = $optimizedPath;
            }

            // Update variants
            $this->update(['variants' => $variants]);

        } catch (\Exception $e) {
            \Log::error('Image processing failed: ' . $e->getMessage());
        }
    }

    // Generate variant path
    private function getVariantPath($variant)
    {
        $directory = dirname($this->path);
        $filename = pathinfo($this->file_name, PATHINFO_FILENAME);
        return $directory . '/' . $filename . '_' . $variant . '.jpg';
    }

    // Delete media and all variants
    public function deleteFile()
    {
        // Delete original file
        Storage::disk($this->disk)->delete($this->path);

        // Delete variants
        if ($this->variants) {
            foreach ($this->variants as $variantPath) {
                Storage::disk($this->disk)->delete($variantPath);
            }
        }

        // Delete record
        $this->delete();
    }

    // Utility method to format file size
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    // Scopes
    public function scopeInFolder($query, $folder = '/')
    {
        return $query->where('folder', $folder);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('mime_type', 'like', $type . '%');
    }
}