{!! '<'.'?'.'xml version="1.0" encoding="UTF-8" ?>' !!}
    <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:atom="http://www.w3.org/2005/Atom">
        <channel>
            <title>
                <![CDATA[{{ $meta['title'] }}]]>
            </title>
            <link>
            <![CDATA[{{ url('/') }}]]>
            </link>
            <description>
                <![CDATA[{{ $meta['description'] }}]]>
            </description>
            <language>{{ $meta['language'] }}</language>
            <pubDate>{{ $items->first()?->updated_at?->toRssString() ?? now()->toRssString() }}</pubDate>
            <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
            <atom:link href="{{ url()->current() }}" rel="self" type="application/rss+xml" />
            <generator>Laravel Portfolio</generator>
            <webMaster>{{ config('mail.from.address') }} ({{ config('app.name') }})</webMaster>
            <managingEditor>{{ config('mail.from.address') }} ({{ config('app.name') }})</managingEditor>
            <docs>https://www.rssboard.org/rss-specification</docs>
            <ttl>60</ttl>

            @foreach($items as $item)
            <item>
                <title>
                    <![CDATA[{{ $item->title }}]]>
                </title>
                <link>
                <![CDATA[{{ route('blog.show', $item->slug) }}]]>
                </link>
                <description>
                    <![CDATA[{{ $item->excerpt }}]]>
                </description>
                <content:encoded>
                    <![CDATA[
                @if($item->featured_image)
                <img src="{{ Storage::url($item->featured_image) }}" alt="{{ $item->title }}" style="max-width: 100%; height: auto; margin-bottom: 20px;">
                @endif
                {!! nl2br(e($item->content)) !!}
                
                @if($item->tags->count())
                <hr style="margin: 20px 0;">
                <p><strong>Tags:</strong> 
                @foreach($item->tags as $tag)
                    <span style="background: {{ $tag->color }}20; color: {{ $tag->color }}; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-right: 5px;">{{ $tag->name }}</span>
                @endforeach
                </p>
                @endif
            ]]>
                </content:encoded>
                <author>
                    <![CDATA[{{ config('mail.from.address') }} ({{ $item->user->name }})]]>
                </author>
                <category>
                    <![CDATA[{{ $item->category->name }}]]>
                </category>
                <guid isPermaLink="true">{{ route('blog.show', $item->slug) }}</guid>
                <pubDate>{{ $item->published_at->toRssString() }}</pubDate>

                @if($item->featured_image)
                <enclosure url="{{ Storage::url($item->featured_image) }}" type="image/jpeg" />
                @endif
            </item>
            @endforeach
        </channel>
    </rss>