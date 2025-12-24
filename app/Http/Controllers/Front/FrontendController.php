<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\CreatorProfile;
use App\Models\CreatorPlan;
use App\Services\CmsContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FrontendController extends Controller
{
    protected CmsContentService $cmsService;

    public function __construct(CmsContentService $cmsService)
    {
        $this->cmsService = $cmsService;
    }

    /**
     * Display the homepage
     */
    public function home(): View
    {
        // Charger les catégories actives avec compteur de produits
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('display_order')
            ->limit(6)
            ->get();

        // Charger les produits mis en avant (featured)
        $featuredProducts = Product::where('is_active', true)
            ->with('category')
            ->latest()
            ->limit(8)
            ->get();

        // Charger le contenu CMS pour la page d'accueil
        $cmsPage = $this->cmsService->getPage('home');

        return view('frontend.home', compact('featuredProducts', 'categories', 'cmsPage'));
    }

    /**
     * Afficher la page boutique avec produits et filtres
     * 
     * P10 : Cache léger sur le catalogue produit (TTL: 1h)
     * 
     * @param Request $request Requête avec paramètres de recherche/filtres
     * @return View Vue de la boutique avec produits paginés
     */
    public function shop(Request $request): View
    {
        // Charger les catégories hiérarchiques avec cache (optimisation)
        $categories = Cache::remember('shop_categories_hierarchical', 3600, function () {
            return Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->withCount(['products' => function ($q) {
                            $q->where('is_active', true);
                        }])
                        ->orderBy('display_order');
                }])
                ->orderBy('display_order')
                ->get();
        });

        // P10 : Cache des produits avec clé basée sur les filtres et la pagination
        // La clé inclut tous les paramètres de filtrage et de pagination pour éviter les collisions
        $cacheKey = $this->buildShopCacheKey($request);
        
        // TTL : 1 heure (3600 secondes)
        // Le cache inclut la pagination pour optimiser les requêtes répétées sur les mêmes pages
        $products = Cache::remember($cacheKey, 3600, function () use ($request) {
            $perPage = min($request->get('per_page', 12), 48);
            return $this->buildProductsQuery($request)->paginate($perPage)->withQueryString();
        });


        // Charger le contenu CMS pour la page boutique avec toutes les sections
        $cmsPage = $this->cmsService->getPage('boutique');

        // Récupérer les sections CMS spécifiques
        $heroSection = $cmsPage?->section('hero');
        $introSection = $cmsPage?->section('intro');
        $filtersSection = $cmsPage?->section('filters');
        $footerSection = $cmsPage?->section('footer');

        return view('frontend.shop', compact(
            'products',
            'categories',
            'cmsPage',
            'heroSection',
            'introSection',
            'filtersSection',
            'footerSection'
        ));
    }

    /**
     * Display the showroom page
     */
    public function showroom(): View
    {
        // Charger le contenu CMS pour la page showroom
        $cmsPage = $this->cmsService->getPage('showroom');

        return view('frontend.showroom', compact('cmsPage'));
    }

    /**
     * Display the atelier page
     */
    public function atelier(): View
    {
        // Charger le contenu CMS pour la page atelier
        $cmsPage = $this->cmsService->getPage('atelier');

        return view('frontend.atelier', compact('cmsPage'));
    }

    /**
     * Display the contact page
     */
    public function contact(): View
    {
        // Charger le contenu CMS pour la page contact
        $cmsPage = $this->cmsService->getPage('contact');

        return view('frontend.contact', compact('cmsPage'));
    }

    /**
     * Display the about page
     */
    public function about(): View
    {
        // Charger le contenu CMS pour la page À propos
        $cmsPage = $this->cmsService->getPage('a-propos');

        return view('frontend.about', compact('cmsPage'));
    }

    /**
     * Display the account client/creator FAQ page
     * 
     * Page d'aide expliquant le système de compte unique
     */
    public function accountClientCreator(): View
    {
        return view('frontend.account-client-creator');
    }

    /**
     * Display the help page
     */
    public function help(): View
    {
        // Charger le contenu CMS pour la page aide
        $cmsPage = $this->cmsService->getPage('aide');

        return view('frontend.help', compact('cmsPage'));
    }

    /**
     * Display the shipping page
     */
    public function shipping(): View
    {
        // Charger le contenu CMS pour la page livraison
        $cmsPage = $this->cmsService->getPage('livraison');

        return view('frontend.shipping', compact('cmsPage'));
    }

    /**
     * Display the returns page
     */
    public function returns(): View
    {
        // Charger le contenu CMS pour la page retours
        $cmsPage = $this->cmsService->getPage('retours-echanges');

        return view('frontend.returns', compact('cmsPage'));
    }

    /**
     * Display the terms page
     */
    public function terms(): View
    {
        // Charger le contenu CMS pour la page CGV
        $cmsPage = $this->cmsService->getPage('cgv');

        return view('frontend.terms', compact('cmsPage'));
    }

    /**
     * Display the privacy page
     */
    public function privacy(): View
    {
        // Charger le contenu CMS pour la page confidentialité
        $cmsPage = $this->cmsService->getPage('confidentialite');

        return view('frontend.privacy', compact('cmsPage'));
    }

    /**
     * Afficher le détail d'un produit
     * 
     * @param int $id ID du produit
     * @return View Vue du détail produit avec produits similaires
     */
    public function product($id): View
    {
        $product = Product::where('is_active', true)
            ->with(['category:id,name,slug', 'creator:id,name'])
            ->findOrFail($id);

        // Get related products from same category (avec eager loading)
        $relatedProducts = Product::where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('category:id,name,slug')
            ->select('id', 'category_id', 'title', 'slug', 'price', 'main_image')
            ->limit(4)
            ->get();

        return view('frontend.product', compact('product', 'relatedProducts'));
    }

    /**
     * Display the creators list page - Présentation stylistes
     */
    public function creators(Request $request): View
    {
        // Charger les données ERP (créateurs)
        $query = CreatorProfile::where('is_active', true)
            ->where('is_verified', true)
            ->with('user')
            ->withCount('products');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('brand_name', 'like', "%{$search}%");
        }

        $creators = $query->latest()->paginate(12);

        // Total produits marketplace
        $totalProducts = Product::whereHas('creator')->where('is_active', true)->count();

        // Charger le contenu CMS pour la page créateurs
        $cmsPage = $this->cmsService->getPage('createurs');

        return view('frontend.creators', compact('creators', 'totalProducts', 'cmsPage'));
    }

    /**
     * Display the marketplace page - All creators' products
     * 
     * Grille de TOUS les produits des créateurs avec filtres
     */
    public function marketplace(Request $request): View
    {
        // Charger TOUS les produits créateurs avec filtres
        $query = Product::where('is_active', true)
            ->whereHas('creator') // Uniquement produits avec créateur
            ->with(['category', 'creator.creatorProfile', 'images', 'mainImage']);

        // Filtre par créateur
        if ($request->filled('creator')) {
            $query->where('user_id', $request->creator);
        }

        // Filtre catégorie
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtre prix
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(24);

        // Charger créateurs pour filtre
        $creators = \App\Models\User::query()
            ->whereHas('roleRelation', function ($q) {
                $q->whereIn('slug', ['creator', 'createur']);
            })
            ->whereHas('creatorProfile', function ($q) {
                $q->where('is_active', true)->where('is_verified', true);
            })
            ->with('creatorProfile')
            ->has('products')
            ->get();

        // Charger catégories pour filtre
        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->whereHas('creator');
            }])
            ->having('products_count', '>', 0)
            ->get();

        $creatorsCount = $creators->count();
        $totalProducts = Product::whereHas('creator')->where('is_active', true)->count();

        // Charger le contenu CMS pour la page marketplace
        $cmsPage = $this->cmsService->getPage('marketplace');

        return view('frontend.marketplace', compact(
            'products',
            'creators',
            'categories',
            'creatorsCount',
            'totalProducts',
            'cmsPage'
        ));
    }

    /**
     * Display individual creator shop page
     */
    public function creatorShop(string $slug): View
    {
        // Récupérer le profil créateur par slug
        $creatorProfile = CreatorProfile::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->with('user')
            ->firstOrFail();

        // Charger les produits du créateur
        $products = Product::where('user_id', $creatorProfile->user_id)
            ->where('product_type', 'marketplace')
            ->where('is_active', true)
            ->with('category')
            ->latest()
            ->paginate(24);

        return view('frontend.creator-shop', compact('creatorProfile', 'products'));
    }

    /**
     * Display the events page
     */
    public function events(): View
    {
        // Charger le contenu CMS pour la page événements
        $cmsPage = $this->cmsService->getPage('evenements');

        return view('frontend.events', compact('cmsPage'));
    }

    /**
     * Display the portfolio page
     */
    public function portfolio(): View
    {
        // Charger le contenu CMS pour la page portfolio
        $cmsPage = $this->cmsService->getPage('portfolio');

        return view('frontend.portfolio', compact('cmsPage'));
    }

    /**
     * Display the albums page
     */
    public function albums(): View
    {
        // Charger le contenu CMS pour la page albums
        $cmsPage = $this->cmsService->getPage('albums');

        return view('frontend.albums', compact('cmsPage'));
    }

    /**
     * Display the CEO page (Amira Ganda)
     */
    public function ceo(): View
    {
        // Charger le contenu CMS pour la page Amira Ganda
        $cmsPage = $this->cmsService->getPage('amira-ganda');

        return view('frontend.ceo', compact('cmsPage'));
    }


    /**
     * Construire la requête de produits avec tous les filtres
     * 
     * Méthode extraite pour faciliter le cache et la réutilisation
     * 
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildProductsQuery(Request $request)
    {
        // Construire la requête produits avec eager loading optimisé
        $query = Product::where('is_active', true)
            ->with(['category:id,name,slug,gender,parent_id', 'category.parent'])
            ->select('id', 'category_id', 'user_id', 'title', 'slug', 'price', 'stock', 'main_image', 'created_at');

        // Filtre par genre (nouveau)
        if ($request->filled('gender')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Filtre par catégorie parente (nouveau)
        if ($request->filled('parent_category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('parent_id', $request->parent_category)
                  ->orWhere('id', $request->parent_category);
            });
        }

        // Filtre par type de produit (brand vs marketplace)
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        // Filtre par catégorie (multi-sélection)
        if ($request->filled('category')) {
            $categoryIds = is_array($request->category) ? $request->category : [$request->category];
            $query->whereIn('category_id', $categoryIds);
        }

        // Recherche améliorée (multi-champs)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtre par prix
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Filtre par stock
        if ($request->filled('stock_filter')) {
            switch ($request->stock_filter) {
                case 'in_stock':
                    $query->where('stock', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('stock', '>', 0)->where('stock', '<=', 10);
                    break;
            }
        }

        // Filtre par créateur
        if ($request->filled('creator')) {
            $query->where('user_id', $request->creator);
        }

        // Tri
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('title', 'asc');
                break;
            case 'stock':
                $query->orderBy('stock', 'desc');
                break;
            default:
                $query->latest();
        }

        return $query;
    }

    /**
     * Construire la clé de cache pour la page boutique
     * 
     * La clé inclut tous les paramètres de filtrage et de pagination
     * pour éviter les collisions de cache.
     * 
     * @param Request $request
     * @return string
     */
    protected function buildShopCacheKey(Request $request): string
    {
        $filters = [
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 12),
            'sort' => $request->get('sort', 'latest'),
            'gender' => $request->get('gender'),
            'parent_category' => $request->get('parent_category'),
            'product_type' => $request->get('product_type'),
            'category' => $request->get('category'),
            'search' => $request->get('search'),
            'price_min' => $request->get('price_min'),
            'price_max' => $request->get('price_max'),
            'stock_filter' => $request->get('stock_filter'),
            'creator' => $request->get('creator'),
        ];

        // Normaliser les tableaux pour la clé de cache
        if (is_array($filters['category'])) {
            sort($filters['category']);
        }

        return 'shop.products.' . md5(json_encode($filters));
    }

    /**
     * Display the "Devenir Créateur" page with subscription plans
     * 
     * UX & Copywriting page for creator subscription
     */
    public function becomeCreator(): View
    {
        $plans = CreatorPlan::active()
            ->orderBy('price')
            ->with('capabilities')
            ->get();

        return view('frontend.become-creator', compact('plans'));
    }
}
