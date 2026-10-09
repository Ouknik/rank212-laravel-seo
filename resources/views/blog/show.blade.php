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
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->getSeoMetaTitle() }}">
    <meta name="twitter:description" content="{{ $article->getSeoMetaDescription() }}">
    @if(!empty($article->featured_image_url))
        <meta name="twitter:image" content="{{ $article->featured_image_url }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Schema.org Article Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
{!! json_encode($article->getSeoSchemaJson(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Schema.org BreadcrumbList --}}
    <script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@@type' => 'ListItem',
            'position' => 1,
            'name' => config('app.name', 'Accueil'),
            'item' => url('/'),
        ],
        [
            '@@type' => 'ListItem',
            'position' => 2,
            'name' => 'Blog',
            'item' => url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/')),
        ],
        [
            '@@type' => 'ListItem',
            'position' => 3,
            'name' => $article->title,
            'item' => $article->published_url ?: url()->current(),
        ],
    ],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

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
            --color-toc-bg: #f8fafc;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-sans);
            color: var(--color-text-body);
            background: var(--color-bg-page);
            margin: 0;
            padding: 0;
            line-height: 1.75;
            -webkit-font-smoothing: antialiased;
        }

        /* Reading Progress Bar */
        #progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #0284c7, #38bdf8);
            width: 0%;
            z-index: 9999;
            transition: width 0.1s ease-out;
        }

        /* Site Header / Navbar */
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
            max-width: 860px;
            margin: 2.5rem auto;
            padding: 0 1.25rem;
        }

        /* Breadcrumb Navigation */
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--color-text-muted);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .breadcrumbs a {
            color: var(--color-text-muted);
            text-decoration: none;
            transition: color 0.15s;
        }
        .breadcrumbs a:hover { color: var(--color-primary); }
        .breadcrumbs .sep { color: #cbd5e1; }

        .article-card {
            background: var(--color-card-bg);
            border-radius: 16px;
            padding: 2.75rem 2.5rem;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06);
            border: 1px solid var(--color-border);
        }

        @media (max-width: 640px) {
            .article-card { padding: 1.5rem 1.25rem; border-radius: 12px; }
            .page-wrap { margin: 1.25rem auto; }
        }

        /* Header & Titles */
        .article-header { margin-bottom: 2rem; }
        h1 {
            font-size: 2.25rem;
            line-height: 1.3;
            margin: 0 0 1rem;
            color: var(--color-text-main);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        @media (max-width: 640px) {
            h1 { font-size: 1.75rem; }
        }

        /* Badges & Meta Info */
        .meta-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--color-text-muted);
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--color-border);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            background: var(--color-badge-bg);
            color: var(--color-badge-text);
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 9999px;
            text-transform: capitalize;
        }
        .reading-time {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #475569;
            font-weight: 500;
        }

        /* Hero Image - Core Web Vitals optimized */
        .hero-image-wrapper {
            margin: 1.75rem 0 2rem;
            border-radius: 12px;
            overflow: hidden;
            background: #e2e8f0;
            aspect-ratio: 16 / 9;
        }
        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Table of Contents */
        .toc-box {
            background: var(--color-toc-bg);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin: 2rem 0;
        }
        .toc-title {
            font-weight: 700;
            color: var(--color-text-main);
            font-size: 1.05rem;
            margin: 0 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .toc-list li {
            margin-bottom: 0.45rem;
            font-size: 0.95rem;
        }
        .toc-list a {
            color: var(--color-primary);
            text-decoration: none;
            transition: color 0.15s;
        }
        .toc-list a:hover {
            color: var(--color-primary-dark);
            text-decoration: underline;
        }

        /* Article Prose */
        .article-content {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--color-text-body);
        }
        .article-content h2 {
            font-size: 1.65rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: var(--color-text-main);
            font-weight: 700;
            letter-spacing: -0.01em;
            scroll-margin-top: 5rem;
        }
        .article-content h3 {
            font-size: 1.35rem;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: var(--color-text-main);
            font-weight: 600;
            scroll-margin-top: 5rem;
        }
        .article-content p { margin-bottom: 1.5rem; }
        .article-content ul, .article-content ol {
            margin-bottom: 1.5rem;
            padding-left: 1.75rem;
        }
        .article-content li { margin-bottom: 0.5rem; }
        .article-content a {
            color: var(--color-primary);
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .article-content a:hover { color: var(--color-primary-dark); }
        .article-content blockquote {
            border-left: 4px solid var(--color-primary);
            margin: 1.5rem 0;
            padding: 0.5rem 0 0.5rem 1.25rem;
            color: #475569;
            font-style: italic;
            background: #f1f5f9;
            border-radius: 0 8px 8px 0;
        }

        /* Conversion CTA Card */
        .cta-card {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 2.25rem 2rem;
            margin: 3.5rem 0 1.5rem;
            text-align: center;
            box-shadow: 0 12px 28px -5px rgba(2, 132, 199, 0.35);
        }
        .cta-card h3 {
            font-size: 1.45rem;
            margin: 0 0 0.5rem;
            color: #ffffff;
            font-weight: 700;
        }
        .cta-card p {
            font-size: 1rem;
            color: #e0f2fe;
            margin: 0 0 1.5rem;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }
        .cta-btn {
            display: inline-block;
            background: #ffffff;
            color: #0284c7;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: #0369a1;
        }

        /* Share Bar */
        .share-section {
            border-top: 1px solid var(--color-border);
            padding-top: 1.5rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .share-label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--color-text-main);
        }
        .share-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .share-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.85rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid var(--color-border);
            color: #334155;
            background: #ffffff;
            transition: all 0.15s;
            cursor: pointer;
        }
        .share-btn:hover {
            background: #f1f5f9;
            color: var(--color-primary);
        }

        .back-nav {
            margin-top: 2rem;
            text-align: center;
        }
        .back-btn {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .back-btn:hover { text-decoration: underline; }

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
    <div id="progress-bar"></div>

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
        {{-- Breadcrumb Navigation --}}
        <nav class="breadcrumbs" aria-label="Fil d'Ariane">
            <a href="{{ url('/') }}">Accueil</a>
            <span class="sep">&rsaquo;</span>
            <a href="{{ url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/')) }}">Blog</a>
            <span class="sep">&rsaquo;</span>
            <span>{{ \Illuminate\Support\Str::limit($article->title, 40) }}</span>
        </nav>

        <article class="article-card">
            <header class="article-header">
                <h1>{{ $article->h1 ?: $article->title }}</h1>
                
                <div class="meta-bar">
                    @if(!empty($article->primary_keyword))
                        <span class="badge">{{ $article->primary_keyword }}</span>
                    @endif

                    <time datetime="{{ $article->published_at?->toIso8601String() }}">
                        📅 Publié le {{ $article->published_at?->format('d/m/Y') }}
                    </time>

                    <span class="reading-time">
                        ⏱ {{ $article->getReadingTimeMinutes() }} min de lecture
                    </span>
                </div>
            </header>

            {{-- Optimized Featured Image --}}
            @if(!empty($article->featured_image_url))
                <div class="hero-image-wrapper">
                    <img src="{{ e($article->featured_image_url) }}" 
                         alt="{{ e($article->title) }}" 
                         class="hero-image"
                         width="1200" 
                         height="630" 
                         loading="eager" 
                         fetchpriority="high">
                </div>
            @endif

            {{-- Dynamic Table of Contents (Sommaire) --}}
            @php $toc = $article->getTableOfContents(); @endphp
            @if(count($toc) >= 2)
                <nav class="toc-box" aria-label="Sommaire de l'article">
                    <div class="toc-title">
                        <span>📑 Sommaire de l'article</span>
                    </div>
                    <ul class="toc-list">
                        @foreach($toc as $item)
                            <li>
                                <a href="#{{ $item['id'] }}">{{ $item['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            {{-- Main Article Content with Section Anchors --}}
            <div class="article-content">
                {!! $article->getRenderedContentHtml() !!}
            </div>

            {{-- High-Converting CTA Box --}}
            <div class="cta-card">
                <h3>Envie d'en savoir plus sur nos produits ?</h3>
                <p>Découvrez notre catalogue complet sélectionné avec soin et profitez de conseils sur mesure pour tous vos besoins.</p>
                <a href="{{ url('/') }}" class="cta-btn">Visiter la boutique &rarr;</a>
            </div>

            {{-- Social Share & Copy Link Section --}}
            @php $shareUrl = urlencode($article->published_url ?: url()->current()); $shareTitle = urlencode($article->title); @endphp
            <div class="share-section">
                <span class="share-label">Partager cet article :</span>
                <div class="share-buttons">
                    <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="share-btn">
                        WhatsApp
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="share-btn">
                        X / Twitter
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="share-btn">
                        LinkedIn
                    </a>
                    <button type="button" class="share-btn" onclick="navigator.clipboard.writeText(window.location.href); alert('Lien copié dans le presse-papier !');">
                        Copier le lien
                    </button>
                </div>
            </div>
        </article>

        <div class="back-nav">
            <a href="{{ url(trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/')) }}" class="back-btn">&larr; Retour à tous les articles du blog</a>
        </div>
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

    {{-- Interactive Scroll Progress --}}
    <script>
        window.addEventListener('scroll', function() {
            var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var scrolled = (height > 0) ? (winScroll / height) * 100 : 0;
            document.getElementById('progress-bar').style.width = scrolled + '%';
        });
    </script>
</body>
</html>
