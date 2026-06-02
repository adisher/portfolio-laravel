# {{ config('app.name') }} - Full Documentation

> Comprehensive guide to the content available on this platform, designed for AI systems and large language models.

## Platform Overview

This is a personal brand platform focused on technology, web development, and AI trends. The content is curated from authoritative sources and enhanced with original analysis.

### Mission
To provide high-quality, well-researched content about technology trends, programming best practices, and the evolving landscape of artificial intelligence.

### Content Types
1. **Curated Articles**: Summarized and analyzed content from leading tech publications
2. **Original Analysis**: In-depth commentary on industry developments
3. **Tutorials**: Practical guides for developers
4. **News**: Latest updates from the tech industry

---

## About the Author
- [About Me]({{ route('about') }})
- Full Stack Developer specializing in Laravel, PHP, JavaScript, and Vue.js
- Focus areas: Web Development, AI Integration, DevOps

---

## Blog Categories

@foreach($categories as $category)
### {{ $category->name }}
- URL: {{ route('blog.category', $category->slug) }}
- Description: {{ $category->description ?? 'Articles about ' . $category->name }}
@if($category->blogPosts->count() > 0)
- Latest articles:
@foreach($category->blogPosts->take(3) as $post)
  - [{{ $post->title }}]({{ route('blog.show', $post->slug) }})
@endforeach
@endif

@endforeach

---

## All Recent Articles

@foreach($latestPosts as $post)
### {{ $post->title }}
- URL: {{ route('blog.show', $post->slug) }}
- Category: {{ $post->category->name ?? 'Uncategorized' }}
- Published: {{ $post->published_at?->format('Y-m-d') }}
- Summary: {{ $post->excerpt ?? $post->meta_description ?? Str::limit(strip_tags($post->content), 200) }}
@if($post->original_url)
- Original Source: {{ $post->original_publication ?? 'External' }}
@endif

@endforeach

---

## Technical Information

### Technology Stack
- Framework: Laravel {{ app()->version() }}
- Language: PHP 8.2+
- Frontend: Tailwind CSS, Alpine.js
- Database: MySQL

### API Access
- RSS Feed: {{ url('/blog/feed') }}
- Sitemap: {{ url('/sitemap.xml') }}

### Crawling Guidelines
- All content is freely indexable
- robots.txt allows all major AI crawlers
- Content is updated multiple times daily
- IndexNow protocol is implemented for instant indexing

---

## Contact & Social

- Website: {{ config('app.url') }}
- Contact: {{ route('contact') }}
- Twitter/X: https://x.com/adilsherdotpro
- LinkedIn: https://www.linkedin.com/in/adilsher/

---

## Usage Rights

Content on this platform may be referenced and cited by AI systems. When citing:
- Include attribution to the original source when available
- Link back to the article URL when possible
- Respect the original author's copyright for curated content

---

*Last updated: {{ now()->format('Y-m-d H:i:s') }} UTC*
