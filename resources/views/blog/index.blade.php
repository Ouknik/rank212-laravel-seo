<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Blog') }} — Articles & Guides</title>
    <meta name="description" content="Découvrez nos derniers articles, analyses et guides d'experts pour développer votre boutique.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --color-primary: #0284c7;
            --color-primary-dark: #0369a1;
            --color-text-main: #0f172a;
            --color-text-muted: #64748b;
            --color-text-body: #334155;
            --color-bg-page: #f8fafc;
            --color-card-bg: #ffffff;
            --color-border: #e2e8f0;
            --color-badge-bg: #e0f2fe;
            --color-badge-text: #0369a1;
        }

        body {
            font-family: var(--font-sans);
            color: var(--color-text-body);
            background: var(--color-bg-page);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Bar */
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid var(--color-border);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
        .site-header-inner {
            max-width: 1040px;
            margin: 0 auto;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .site-brand {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--color-text-main);
            text-decoration: none;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .site-nav {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .site-nav a {
            color: var(--color-text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.925rem;
            transition: color 0.15s;
        }
        .site-nav a:hover, .site-nav a.active {
            color: var(--color-primary);
        }

        .page-wrap {
            max-width: 1040px;
            margin: 2.5rem auto;
            padding: 0 1.25rem 4rem;
        }

        /* Page Hero */
        .blog-hero {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .blog-hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--color-text-main);
            margin: 0 0 0.75rem;
            letter-spacing: -0.02em;
        }
        .blog-hero p {
            font-size: 1.15rem;
            color: var(--color-text-muted);
            max-width: 620px;
            margin: 0 auto;
        }

        @media (max-width: 640px) {
            .blog-hero h1 { font-size: 2rem; }
            .blog-hero p { font-size: 1rem; }
        }

        /* Grid */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        /* Card */
        .article-card {
            background: var(--color-card-bg);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
        }
        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px -4px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }

        .card-image-wrap {
            aspect-ratio: 16 / 9;
            background: #e2e8f0;
            overflow: hidden;
            position: relative;
        }
        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .article-card:hover .card-image {
            transform: scale(1.03);
        }

        .card-body {
            padding: 1.75rem 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.8rem;
            color: var(--color-text-muted);
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            background: var(--color-badge-bg);
            color: var(--color-badge-text);
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 9999px;
            text-transform: capitalize;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.35;
            margin: 0 0 0.75rem;
            color: var(--color-text-main);
        }
        .card-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.15s;
        }
        .card-title a:hover {
            color: var(--color-primary);
        }

        .card-excerpt {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.55;
            margin: 0 0 1.25rem;
            flex-grow: 1;
        }

        .card-footer {
            margin-top: auto;
            border-top: 1px solid #f1f5f9;
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .read-more {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: gap 0.15s;
        }
        .read-more:hover {
            gap: 0.55rem;
            color: var(--color-primary-dark);
        }

        .empty-box {
            text-align: center;
            padding: 4rem 1.5rem;
            background: var(--color-card-bg);
            border-radius: 16px;
            border: 1px dashed var(--color-border);
        }
        .empty-box h2 {
            font-size: 1.35rem;
            color: var(--color-text-main);
            margin: 0 0 0.5rem;
        }
        .empty-box p {
            color: var(--color-text-muted);
            margin: 0;
        }

        .pagination-box {
            margin-top: 3.5rem;
            display: flex;
            justify-content: center;
        }

        /* Site Footer */
        .site-footer {
            border-top: 1px solid var(--color-border);
            background: #ffffff;
            margin-top: 4rem;
            padding: 2rem 1.25rem;
        }
        .site-footer-inner {
            max-width: 1040px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }
        .footer-links {
            display: flex;
            gap: 1.25rem;
        }
        .footer-links a {
            color: var(--color-text-muted);
            text-decoration: none;
        }
        .footer-links a:hover {
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    {{-- Store Header --}}
    <header class="site-header">
        <div class="site-header-inner">
            <a href="{{ url('/') }}" class="site-brand">
                🛍️ {{ config('app.name', 'Boutique') }}
            </a>
            <nav class="site-nav">
                <a href="{{ url('/') }}">Boutique</a>
                <a href="{{ url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/')) }}" class="active">Blog</a>
            </nav>
        </div>
    </header>

    <div class="page-wrap">
        <section class="blog-hero">
            <h1>Articles & Guides d'Experts</h1>
            <p>Retrouvez nos analyses détaillées, guides d'achat et conseils pratiques pour faire les meilleurs choix.</p>
        </section>

        @if($articles->isEmpty())
            <div class="empty-box">
                <h2>Aucun article publié pour le moment</h2>
                <p>Nos rédacteurs préparent actuellement du nouveau contenu de haute qualité. Revenez très bientôt !</p>
            </div>
        @else
            <main class="articles-grid">
                @foreach($articles as $article)
                    <article class="article-card">
                        @if(!empty($article->featured_image_url))
                            <a href="{{ $article->published_url ?: url('/blog/' . $article->slug) }}" class="card-image-wrap">
                                <img src="{{ e($article->featured_image_url) }}" 
                                     alt="{{ e($article->title) }}" 
                                     class="card-image"
                                     loading="lazy">
                            </a>
                        @endif

                        <div class="card-body">
                            <div class="card-meta">
                                @if(!empty($article->primary_keyword))
                                    <span class="badge">{{ $article->primary_keyword }}</span>
                                @endif

                                <span>
                                    📅 {{ $article->published_at?->format('d/m/Y') }}
                                </span>

                                @if(method_exists($article, 'getReadingTimeMinutes'))
                                    <span>
                                        ⏱ {{ $article->getReadingTimeMinutes() }} min
                                    </span>
                                @endif
                            </div>

                            <h2 class="card-title">
                                <a href="{{ $article->published_url ?: url('/blog/' . $article->slug) }}">
                                    {{ $article->title }}
                                </a>
                            </h2>

                            <p class="card-excerpt">
                                {{ $article->getSeoMetaDescription() }}
                            </p>

                            <div class="card-footer">
                                <a href="{{ $article->published_url ?: url('/blog/' . $article->slug) }}" class="read-more">
                                    Lire l'article <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </main>

            @if(method_exists($articles, 'links'))
                <nav class="pagination-box" aria-label="Pagination des articles">
                    {{ $articles->links() }}
                </nav>
            @endif
        @endif
    </div>

    {{-- Store Footer --}}
    <footer class="site-footer">
        <div class="site-footer-inner">
            <div>
                &copy; {{ date('Y') }} {{ config('app.name', 'Boutique') }}. Tous droits réservés.
            </div>
            <div class="footer-links">
                <a href="{{ url('/') }}">Boutique</a>
                <a href="{{ url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/')) }}">Articles & Guides</a>
                <a href="{{ url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/') . '/sitemap.xml') }}" target="_blank">Sitemap XML</a>
            </div>
        </div>
    </footer>
</body>
</html>
