<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- Core SEO Tags --}}
    <title>{{ $article->getSeoMetaTitle() }}</title>
    <meta name="description" content="{{ $article->getSeoMetaDescription() }}">
    @if(!empty($article->getSeoFocusKeywords()))
        <meta name="keywords" content="{{ implode(', ', $article->getSeoFocusKeywords()) }}">
    @endif
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $article->published_url ?: url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->getSeoMetaTitle() }}">
    <meta property="og:description" content="{{ $article->getSeoMetaDescription() }}">
    <meta property="og:url" content="{{ $article->published_url ?: url()->current() }}">
    @if(!empty($article->featured_image_url))
        <meta property="og:image" content="{{ $article->featured_image_url }}">
    @endif
    <meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->getSeoMetaTitle() }}">
    <meta name="twitter:description" content="{{ $article->getSeoMetaDescription() }}">
    @if(!empty($article->featured_image_url))
        <meta name="twitter:image" content="{{ $article->featured_image_url }}">
    @endif

    {{-- Schema.org Article Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
{!! json_encode($article->getSeoSchemaJson(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <style>
        :root { --font-sans: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; --color-primary: #0284c7; --color-text: #1e293b; --color-bg: #f8fafc; }
        body { font-family: var(--font-sans); color: var(--color-text); background: var(--color-bg); margin: 0; padding: 2rem 1rem; line-height: 1.7; }
        .article-container { max-width: 820px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
        .back-link { display: inline-block; margin-bottom: 1.5rem; color: var(--color-primary); text-decoration: none; font-size: 0.95rem; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        h1 { font-size: 2.25rem; line-height: 1.25; margin: 0 0 1rem; color: #0f172a; }
        .article-meta { font-size: 0.9rem; color: #64748b; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .featured-image { width: 100%; max-height: 440px; object-fit: cover; border-radius: 8px; margin-bottom: 2rem; }
        .article-body { font-size: 1.1rem; color: #334155; }
        .article-body h2 { font-size: 1.6rem; margin-top: 2rem; color: #0f172a; }
        .article-body h3 { font-size: 1.3rem; margin-top: 1.5rem; color: #1e293b; }
        .article-body p { margin-bottom: 1.25rem; }
        .article-body ul, .article-body ol { margin-bottom: 1.5rem; padding-left: 1.5rem; }
        .article-body li { margin-bottom: 0.5rem; }
        .article-body a { color: var(--color-primary); text-decoration: underline; }
        .article-body a:hover { color: #0369a1; }
    </style>
</head>
<body>
    <article class="article-container">
        <a href="{{ url(trim(config('seo-saas.publishing.blog_prefix', 'blog'), '/')) }}" class="back-link">&larr; Retour au blog</a>

        <header>
            <h1>{{ $article->h1 ?: $article->title }}</h1>
            <div class="article-meta">
                Publié le <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->format('d/m/Y') }}</time>
                @if(!empty($article->primary_keyword))
                    • <span>Catégorie : {{ $article->primary_keyword }}</span>
                @endif
            </div>
        </header>

        @if(!empty($article->featured_image_url))
            <img src="{{ e($article->featured_image_url) }}" alt="{{ e($article->title) }}" class="featured-image">
        @endif

        <div class="article-body">
            {!! $article->content_html !!}
        </div>
    </article>
</body>
</html>
