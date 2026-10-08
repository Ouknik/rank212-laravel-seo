<?php

namespace SeoSaas\LaravelSeo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use SeoSaas\LaravelSeo\Models\SeoPublishedArticle;

class BlogController extends Controller
{
    /**
     * Display a listing of published blog articles.
     */
    public function index(Request $request): View
    {
        $query = SeoPublishedArticle::published();

        if ($storeId = config('seo-saas.publishing.store_id')) {
            $query->forStore($storeId);
        }

        $articles = $query->latest('published_at')->paginate(10);

        return view('seo::blog.index', compact('articles'));
    }

    /**
     * Display a single published blog article.
     * Unpublished, received, or draft articles return 404.
     */
    public function show(string $slug): View
    {
        $query = SeoPublishedArticle::published()->where('slug', $slug);

        if ($storeId = config('seo-saas.publishing.store_id')) {
            $query->forStore($storeId);
        }

        $article = $query->firstOrFail();

        return view('seo::blog.show', compact('article'));
    }

    /**
     * Generate an XML sitemap of all published blog articles.
     */
    public function sitemap(): Response
    {
        $query = SeoPublishedArticle::published();

        if ($storeId = config('seo-saas.publishing.store_id')) {
            $query->forStore($storeId);
        }

        $articles = $query->latest('published_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Blog index URL
        $blogIndexUrl = url(trim(config('seo-saas.publishing.blog_prefix', 'blog'), '/'));
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($blogIndexUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
        $xml .= "    <lastmod>" . now()->toAtomString() . "</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>0.9</priority>\n";
        $xml .= "  </url>\n";

        // Published article URLs
        foreach ($articles as $article) {
            $articleUrl = $article->published_url ?: url('/blog/' . $article->slug);
            $lastmod = ($article->updated_at ?? $article->published_at ?? now())->toAtomString();

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($articleUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";

            if (!empty($article->featured_image_url)) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . htmlspecialchars($article->featured_image_url, ENT_XML1, 'UTF-8') . "</image:loc>\n";
                $xml .= "      <image:title>" . htmlspecialchars($article->title, ENT_XML1, 'UTF-8') . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
