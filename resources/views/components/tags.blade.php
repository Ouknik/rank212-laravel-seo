@props(['model' => null])

@php
    $title = $model ? (method_exists($model, 'getSeoMetaTitle') ? $model->getSeoMetaTitle() : ($model->title ?? $model->name ?? '')) : config('app.name');
    $description = $model ? (method_exists($model, 'getSeoMetaDescription') ? $model->getSeoMetaDescription() : ($model->description ?? '')) : '';
    $keywords = $model && method_exists($model, 'getSeoFocusKeywords') ? $model->getSeoFocusKeywords() : [];
    $keywordsString = !empty($keywords) ? implode(', ', $keywords) : null;
    $og = $model && method_exists($model, 'getSeoOgTags') ? $model->getSeoOgTags() : [];
    $schema = $model && method_exists($model, 'getSeoSchemaJson') ? $model->getSeoSchemaJson() : null;
    $ogTitle = $og['title'] ?? $title;
    $ogDesc = $og['description'] ?? $description;
    $ogImage = $og['image_url'] ?? ($model && method_exists($model, 'getSeoImageUrl') ? $model->getSeoImageUrl() : null);
    $ogType = $og['type'] ?? 'product';
@endphp

{{-- Standard SEO Meta Tags --}}
<title>{{ $title }}</title>
@if(!empty($description))
<meta name="description" content="{{ e($description) }}">
@endif
@if(!empty($keywordsString))
<meta name="keywords" content="{{ e($keywordsString) }}">
@endif

{{-- OpenGraph Tags --}}
<meta property="og:title" content="{{ e($ogTitle) }}">
@if(!empty($ogDesc))
<meta property="og:description" content="{{ e($ogDesc) }}">
@endif
@if(!empty($ogImage))
<meta property="og:image" content="{{ e($ogImage) }}">
@endif
<meta property="og:type" content="{{ e($ogType) }}">
<meta property="og:url" content="{{ request()->fullUrl() }}">

{{-- Twitter Card Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ e($ogTitle) }}">
@if(!empty($ogDesc))
<meta name="twitter:description" content="{{ e($ogDesc) }}">
@endif
@if(!empty($ogImage))
<meta name="twitter:image" content="{{ e($ogImage) }}">
@endif

{{-- Schema.org Structured Data (JSON-LD) --}}
@if(!empty($schema))
<script type="application/ld+json">
{!! json_encode($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endif
