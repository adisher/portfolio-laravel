<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Serializes a published BlogPost for external automation clients (n8n).
 *
 * Everything a downstream posting flow needs is precomputed here: an absolute
 * canonical URL, an absolute featured-image URL, the body in both HTML and
 * Markdown, category, tags (with plain names ready for hashtags) and the
 * publish timestamp in ISO-8601.
 */
class BlogArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'url'              => route('blog.show', $this->slug),
            'excerpt'          => $this->excerpt,
            'content_html'     => $this->rendered_content,
            'content_markdown' => $this->content,
            'featured_image'   => $this->absoluteImageUrl(),
            'image_alt'        => $this->featured_image_alt,
            'reading_time'     => $this->reading_time,
            'category'         => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags'             => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'tag_names'        => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->values()),
            'author'           => $this->whenLoaded('user', fn () => optional($this->user)->name),
            'meta' => [
                'title'       => $this->meta_title ?: $this->title,
                'description' => $this->meta_description ?: $this->excerpt,
                'keywords'    => $this->meta_keywords,
            ],
            'published_at'     => optional($this->published_at)->toIso8601String(),
            'updated_at'       => optional($this->updated_at)->toIso8601String(),
            'posted'           => $this->posted_at !== null,
            'posted_at'        => optional($this->posted_at)->toIso8601String(),
            'posted_via'       => $this->posted_via,
        ];
    }

    /**
     * Resolve the featured image to an absolute URL, or null when unset.
     * Storage::url() yields a root-relative path on the local disk, so wrap
     * it in url() to produce a fully-qualified link n8n can hand to a
     * social platform. An already-absolute URL (e.g. S3) is passed through.
     */
    protected function absoluteImageUrl(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }

        $path = Storage::url($this->featured_image);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
