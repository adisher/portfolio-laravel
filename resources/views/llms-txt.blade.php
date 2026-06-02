# {{ config('app.name') }}

> Personal brand platform covering AI trends, web development, and tech news. Built with Laravel.

## About
- [About Me]({{ route('about') }}): Background, skills, and expertise in full-stack development
- [Portfolio]({{ route('portfolio.index') }}): Featured projects and work samples
- [Contact]({{ route('contact') }}): Get in touch for collaboration

## Blog Categories
@foreach($categories as $category)
- [{{ $category->name }}]({{ route('blog.category', $category->slug) }}): {{ $category->description ?? 'Articles about ' . $category->name }}
@endforeach

## Latest Articles
@foreach($latestPosts as $post)
- [{{ $post->title }}]({{ route('blog.show', $post->slug) }}): {{ Str::limit($post->excerpt ?? $post->meta_description, 100) }}
@endforeach

## Topics Covered
- Artificial Intelligence and Machine Learning
- Web Development (Laravel, PHP, JavaScript, Vue.js)
- Tech Industry News and Analysis
- Programming Best Practices
- DevOps and Cloud Infrastructure
- Career Development for Developers

## Feeds
- [RSS Feed]({{ url('/blog/feed') }}): Subscribe for updates
- [Sitemap]({{ url('/sitemap.xml') }}): All pages and articles

## Contact
- Website: {{ config('app.url') }}
- [Contact Form]({{ route('contact') }})
