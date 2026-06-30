<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Cms\BannerService;
use App\Services\Cms\CategoryService;
use App\Services\Cms\ContentBlockService;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    protected $bannerService;
    protected $categoryService;
    protected $blockService;

    public function __construct(
        BannerService $bannerService,
        CategoryService $categoryService,
        ContentBlockService $blockService
    ) {
        $this->bannerService = $bannerService;
        $this->categoryService = $categoryService;
        $this->blockService = $blockService;
    }

    /**
     * Display the homepage.
     */
    public function home(): \Illuminate\View\View
    {
        $featuredProducts = \App\Models\Product::where('is_active', true)
            ->with('creator', 'category')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $latestCreators = \App\Models\CreatorProfile::active()
            ->with('user')
            ->latest()
            ->limit(6)
            ->get();

        // Stats homepage (cache 5min pour performance)
        $stats = cache()->remember('homepage.stats', 300, function () {
            return [
                'creators_count' => \App\Models\User::whereHas('creatorProfile', function ($q) {
                    $q->where('status', 'active');
                })->count(),
                'countries_count' => \App\Models\User::distinct('country')->count('country'),
                'clients_count' => \App\Models\User::whereHas('role', function ($q) {
                    $q->where('slug', 'client');
                })->count(),
                'products_count' => \App\Models\Product::where('is_active', true)->count(),
            ];
        });

        // Données CMS injectées par HomeComposer
        return view('frontend.home', compact(
            'featuredProducts', 'latestCreators', 'stats'
        ));
    }

    /**
     * Display the shop/catalog.
     */
    public function shop(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $category = null;
        if ($request->filled('category')) {
            $category = \App\Models\Category::where('slug', $request->category)
                ->where('status', 'active')
                ->first();
        }

        $products = \App\Models\Product::where('is_active', true)
            ->when($category, fn($q) => $q->where('category_id', $category->id))
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('min_price'), fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->filled('max_price'), fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->stock_filter === 'in_stock', fn($q) => $q->where('stock', '>', 0))
            ->when($request->stock_filter === 'low_stock', fn($q) => $q->where('stock', '>', 0)->where('stock', '<=', 10))
            ->with('creator', 'category');

        $products = match ($request->sort) {
            'price_asc' => $products->orderBy('price', 'asc'),
            'price_desc' => $products->orderBy('price', 'desc'),
            'name' => $products->orderBy('title', 'asc'),
            'stock' => $products->orderBy('stock', 'desc'),
            default => $products->orderBy('created_at', 'desc'),
        };

        $products = $products->paginate(24)->withQueryString();

        $categories = \App\Models\Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount(['products' => fn($q) => $q->where('is_active', true)])
            ->orderBy('display_order')
            ->get();

        $breadcrumb = $category
            ? app(\App\Services\Cms\CategoryService::class)->getBreadcrumb($category)
            : [];

        return view('frontend.shop', compact('products', 'category', 'categories', 'breadcrumb'));
    }

    /**
     * Display a dynamic CMS page.
     */
    public function page(string $slug): \Illuminate\View\View
    {
        $page = app(\App\Services\Cms\PageService::class)->getPublishedPage($slug);

        abort_if(!$page, 404, 'Page introuvable : ' . $slug);

        $template = match ($page->template) {
            'full_width' => 'cms.pages.full-width',
            'sidebar'    => 'cms.pages.sidebar',
            default      => 'cms.pages.default',
        };

        return view($template, compact('page'));
    }
    protected function renderStaticPageWithCms(string $slug, string $view, array $data = []): \Illuminate\View\View
    {
        $cmsPage = app(\App\Services\Cms\PageService::class)->getPublishedPage($slug);

        return view($view, array_merge($data, ['cmsPage' => $cmsPage]));
    }
    /**
     * Methods for specific frontend pages
     */
    public function showroom() { return $this->renderStaticPageWithCms('showroom', 'frontend.showroom'); }
    public function atelier() { return $this->renderStaticPageWithCms('atelier', 'frontend.atelier'); }
    public function contact() { return $this->renderStaticPageWithCms('contact', 'frontend.contact'); }

    public function contactSubmit(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        \App\Models\ContactMessage::create($validated);

        return redirect()->route('frontend.contact')
            ->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les 24 heures.');
    }
    public function creators()
    {
        // Chercher un créateur marqué comme featured, sinon prendre le premier actif
        $featuredCreator = \App\Models\CreatorProfile::active()
            ->with('user')
            ->where('is_featured', true)
            ->first();
        
        if (!$featuredCreator) {
            // Fallback : prendre le créateur le plus ancien actif
            $featuredCreator = \App\Models\CreatorProfile::active()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->first();
        }

        $creators = \App\Models\CreatorProfile::active()
            ->with('user')
            ->paginate(12);

        $totalProducts = \App\Models\Product::where('product_type', 'marketplace')
            ->where('is_active', true)
            ->count();

        $cmsPage = app(\App\Services\Cms\PageService::class)->getPublishedPage('createurs');

        return view('frontend.creators', compact('creators', 'featuredCreator', 'totalProducts', 'cmsPage'));
    }
    public function marketplace(Request $request)
    {
        $products = \App\Models\Product::where('is_active', true)
            ->where('product_type', 'marketplace')
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('category'), function ($q) use ($request) {
                $cat = \App\Models\Category::where('slug', $request->category)->first();
                return $cat ? $q->where('category_id', $cat->id) : $q;
            })
            ->when($request->filled('creator'), fn($q) => $q->where('user_id', $request->creator))
            ->with('creator.creatorProfile', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(24)
            ->withQueryString();

        $creators = \App\Models\User::whereHas('creatorProfile', fn($q) => $q->where('is_active', true))->get();
        $creatorsCount = $creators->count();

        $categories = \App\Models\Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount(['products' => fn($q) => $q->where('is_active', true)
                ->where('product_type', 'marketplace')])
            ->orderBy('display_order')
            ->get();

        return view('frontend.marketplace', compact('products', 'creators', 'creatorsCount', 'categories'));
    }

    public function creatorShop(string $slug)
    {
        $creatorProfile = \App\Models\CreatorProfile::where('slug', $slug)
            ->where('is_active', true)
            ->with('user')
            ->firstOrFail();

        $products = \App\Models\Product::where('is_active', true)
            ->where('user_id', $creatorProfile->user_id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(24)
            ->withQueryString();

        return view('frontend.creator-shop', compact('creatorProfile', 'products'));
    }
    public function events() { return $this->renderStaticPageWithCms('evenements', 'frontend.events'); }
    public function portfolio() { return $this->renderStaticPageWithCms('portfolio', 'frontend.portfolio'); }
    public function albums() { return $this->renderStaticPageWithCms('albums', 'frontend.albums'); }
    public function ceo() { return $this->renderStaticPageWithCms('amira-ganda', 'frontend.ceo'); }
    public function help() { return $this->renderStaticPageWithCms('aide', 'frontend.help'); }
    public function accountClientCreator() { return $this->renderStaticPageWithCms('aide-compte-client-createur', 'frontend.account-client-creator'); }
    public function shipping() { return $this->renderStaticPageWithCms('livraison', 'frontend.shipping'); }
    public function returns() { return $this->renderStaticPageWithCms('retours-echanges', 'frontend.returns'); }
    public function terms() { return $this->renderStaticPageWithCms('cgv', 'frontend.terms'); }
    public function privacy() { return $this->renderStaticPageWithCms('confidentialite', 'frontend.privacy'); }
    public function cookies() { return redirect()->route('frontend.page.show', 'cookies'); }
    public function about() { return $this->renderStaticPageWithCms('a-propos', 'frontend.about'); }
    public function legal() { return $this->renderStaticPageWithCms('mentions-legales', 'frontend.legal'); }
    public function becomeCreator()
    {
        $plans = \App\Models\CreatorPlan::where('is_active', true)->orderBy('price')->get();
        return $this->renderStaticPageWithCms('devenir-createur', 'frontend.become-creator', compact('plans'));
    }

    public function product($id)
    {
        $product = \App\Models\Product::with(['creator', 'category', 'images'])
            ->where('is_active', true)
            ->findOrFail($id);
        
        $relatedProducts = \App\Models\Product::where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('frontend.product', compact('product', 'relatedProducts'));
    }

    /**
     * Legacy index method
     */
    public function index()
    {
        return $this->home();
    }
}


