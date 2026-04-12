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

        // Données CMS injectées par HomeComposer
        return view('frontend.home', compact(
            'featuredProducts', 'latestCreators'
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

    /**
     * Methods for specific frontend pages
     */
    public function showroom() { return view('frontend.showroom'); }
    public function atelier() { return view('frontend.atelier'); }
    public function contact() { return view('frontend.contact'); }

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
        $creators = \App\Models\CreatorProfile::active()
            ->with('user')
            ->paginate(12);

        $totalProducts = \App\Models\Product::where('product_type', 'marketplace')
            ->where('is_active', true)
            ->count();

        $cmsPage = app(\App\Services\Cms\PageService::class)->getPublishedPage('createurs');

        return view('frontend.creators', compact('creators', 'totalProducts', 'cmsPage'));
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
        $creator = \App\Models\CreatorProfile::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = \App\Models\Product::where('is_active', true)
            ->where('user_id', $creator->user_id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(24)
            ->withQueryString();

        return view('frontend.creator-shop', compact('creator', 'products'));
    }
    public function events() { return view('frontend.events'); }
    public function portfolio() { return view('frontend.portfolio'); }
    public function albums() { return view('frontend.albums'); }
    public function ceo() { return view('frontend.ceo'); }
    public function help() { return view('frontend.help'); }
    public function accountClientCreator() { return view('frontend.account-client-creator'); }
    public function shipping() { return view('frontend.shipping'); }
    public function returns() { return view('frontend.returns'); }
    public function terms() { return view('frontend.terms'); }
    public function privacy() { return view('frontend.privacy'); }
    public function cookies() { return redirect()->route('frontend.page.show', 'cookies'); }
    public function about() { return redirect()->route('frontend.page.show', 'a-propos'); }
    public function becomeCreator()
    {
        $plans = \App\Models\CreatorPlan::where('is_active', true)->orderBy('price')->get();
        return view('frontend.become-creator', compact('plans'));
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
