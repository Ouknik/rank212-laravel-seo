<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Blog') }} — Articles & Guides</title>
    <meta name="description" content="Découvrez nos derniers articles, analyses et guides exclusifs.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <style>
        :root { --font-sans: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; --color-primary: #0284c7; --color-text: #1e293b; --color-bg: #f8fafc; }
        body { font-family: var(--font-sans); color: var(--color-text); background: var(--color-bg); margin: 0; padding: 2rem 1rem; line-height: 1.6; }
        .blog-container { max-width: 800px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
        h1 { font-size: 2rem; margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; }
        .article-card { padding: 1.5rem 0; border-bottom: 1px solid #f1f5f9; }
        .article-card:last-child { border-bottom: none; }
        .article-title { font-size: 1.35rem; margin: 0 0 0.5rem; }
        .article-title a { color: #0f172a; text-decoration: none; }
        .article-title a:hover { color: var(--color-primary); }
        .article-meta { font-size: 0.875rem; color: #64748b; margin-bottom: 0.75rem; }
        .article-excerpt { color: #475569; margin: 0 0 1rem; }
        .read-more { color: var(--color-primary); text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .read-more:hover { text-decoration: underline; }
        .pagination { margin-top: 2rem; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <main class="blog-container">
        <h1>Articles & Guides</h1>

        @if($articles->isEmpty())
            <p style="color: #64748b; padding: 2rem 0;">Aucun article publié pour le moment. Revenez bientôt !</p>
        @else
            <div class="articles-list">
                @foreach($articles as $article)
                    <article class="article-card">
                        <h2 class="article-title">
                            <a href="{{ $article->published_url ?: url('/blog/' . $article->slug) }}">
                                {{ $article->title }}
                            </a>
                        </h2>
                        <div class="article-meta">
                            Publié le <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->format('d/m/Y') }}</time>
                            @if(!empty($article->primary_keyword))
                                • <span>{{ $article->primary_keyword }}</span>
                            @endif
                        </div>
                        <p class="article-excerpt">
                            {{ $article->getSeoMetaDescription() }}
                        </p>
                        <a href="{{ $article->published_url ?: url('/blog/' . $article->slug) }}" class="read-more">
                            Lire l'article &rarr;
                        </a>
                    </article>
                @endforeach
            </div>

            @if(method_exists($articles, 'links'))
                <div class="pagination">
                    {{ $articles->links() }}
                </div>
            @endif
        @endif
    </main>
</body>
</html>
