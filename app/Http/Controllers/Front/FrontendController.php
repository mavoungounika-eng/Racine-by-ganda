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

        $latestCreators = \App\Models\User::where('role', 'createur')
            ->where('is_active', true)
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
            ->with('creator', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(24)
            ->withQueryString();

        $breadcrumb = $category
            ? app(\App\Services\Cms\CategoryService::class)->getBreadcrumb($category)
            : [];

        return view('frontend.shop', compact('products', 'category', 'breadcrumb'));
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
    public function creators() 
    { 
        $creators = \App\Models\User::where('role', 'createur')
            ->where('is_active', true)
            ->paginate(12);
        return view('frontend.creators', compact('creators')); 
    }
    public function marketplace() { return view('frontend.marketplace'); }
    public function creatorShop(string $slug)
    {
        $creator = \App\Models\CreatorProfile::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        return view('frontend.creator-shop', compact('creator'));
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
    public function about() { return redirect()->route('frontend.page.show', 'a-propos'); }
    public function becomeCreator() { return view('frontend.become-creator'); }

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

