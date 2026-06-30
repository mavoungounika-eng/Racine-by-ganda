<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CreatorProfile;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate sitemap.xml
     */
    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= $this->url(route('frontend.home'), '1.0', 'daily');

        // Static pages
        $staticPages = [
            route('frontend.shop') => ['priority' => '0.9', 'changefreq' => 'daily'],
            route('frontend.about') => ['priority' => '0.8', 'changefreq' => 'monthly'],
            route('frontend.contact') => ['priority' => '0.7', 'changefreq' => 'monthly'],
            route('frontend.creators') => ['priority' => '0.8', 'changefreq' => 'weekly'],
            route('frontend.showroom') => ['priority' => '0.7', 'changefreq' => 'monthly'],
            route('frontend.atelier') => ['priority' => '0.7', 'changefreq' => 'monthly'],
            route('frontend.help') => ['priority' => '0.6', 'changefreq' => 'monthly'],
            route('frontend.shipping') => ['priority' => '0.6', 'changefreq' => 'monthly'],
            route('frontend.returns') => ['priority' => '0.6', 'changefreq' => 'monthly'],
            route('frontend.terms') => ['priority' => '0.5', 'changefreq' => 'yearly'],
            route('frontend.privacy') => ['priority' => '0.5', 'changefreq' => 'yearly'],
            route('frontend.legal') => ['priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $url => $params) {
            $xml .= $this->url($url, $params['priority'], $params['changefreq']);
        }

        // Products (active only)
        Product::where('is_active', true)
            ->select('id', 'slug', 'updated_at')
            ->chunk(100, function ($products) use (&$xml) {
                foreach ($products as $product) {
                    $url = route('frontend.product', $product->id);
                    $lastmod = $product->updated_at->toAtomString();
                    $xml .= $this->url($url, '0.8', 'weekly', $lastmod);
                }
            });

        // Creator profiles (active only)
        CreatorProfile::where('status', 'active')
            ->whereHas('user')
            ->select('id', 'slug', 'updated_at')
            ->chunk(50, function ($creators) use (&$xml) {
                foreach ($creators as $creator) {
                    if ($creator->slug) {
                        $url = route('frontend.creatorShop', $creator->slug);
                        $lastmod = $creator->updated_at->toAtomString();
                        $xml .= $this->url($url, '0.7', 'weekly', $lastmod);
                    }
                }
            });

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate URL entry for sitemap
     */
    private function url(string $loc, string $priority, string $changefreq, ?string $lastmod = null): string
    {
        $xml = '<url>';
        $xml .= '<loc>' . htmlspecialchars($loc) . '</loc>';
        if ($lastmod) {
            $xml .= '<lastmod>' . $lastmod . '</lastmod>';
        }
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';

        return $xml;
    }
}
