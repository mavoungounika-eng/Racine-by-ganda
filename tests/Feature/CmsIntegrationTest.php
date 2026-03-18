<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Banner;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que les données globales (footer, nav) sont injectées.
     */
    public function test_global_cms_data_is_injected()
    {
        // Créer une page de footer
        Page::create([
            'title' => 'Terms of Service',
            'slug' => 'terms',
            'status' => 'published',
            'show_in_footer' => true,
            'published_at' => now()
        ]);

        // Créer une catégorie de navigation
        Category::create([
            'name' => 'Vêtements',
            'slug' => 'vetements',
            'status' => 'active'
        ]);

        $response = $this->get(route('frontend.home'));

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('Vêtements');
    }

    /**
     * Test que la homepage affiche les bannières CMS.
     */
    public function test_homepage_shows_cms_banners()
    {
        Banner::create([
            'title' => 'Special Promo',
            'position' => 'homepage_hero',
            'status' => 'active',
            'image_path' => 'banners/test.jpg'
        ]);

        $response = $this->get(route('frontend.home'));

        $response->assertStatus(200);
        $response->assertSee('Special Promo');
    }

    /**
     * Test que les blocs CMS sont injectés sur la home.
     */
    public function test_homepage_shows_cms_blocks()
    {
        ContentBlock::create([
            'key' => 'home_intro',
            'title' => 'Home Intro',
            'content' => 'Welcome to Racine CMS',
            'is_active' => true,
            'type' => 'text'
        ]);

        $response = $this->get(route('frontend.home'));

        $response->assertStatus(200);
        $response->assertSee('Welcome to Racine CMS');
    }

    /**
     * Test le rendu dynamique des pages CMS.
     */
    public function test_dynamic_cms_page_rendering()
    {
        // Nettoyer le cache pour ce test spécifique
        \Illuminate\Support\Facades\Cache::flush();

        $page = Page::create([
            'title' => 'Dynamic Page Test',
            'slug' => 'dynamic-test-page',
            'content' => 'Description of Racine Dynamic',
            'status' => 'published',
            'published_at' => now()->subMinutes(5)
        ]);

        $response = $this->get(route('frontend.page.show', 'dynamic-test-page'));

        $response->assertStatus(200);
        $response->assertSee('Dynamic Page Test');
        $response->assertSee('Description of Racine Dynamic');
    }

    /**
     * Test les redirections des anciennes pages statiques.
     */
    public function test_legacy_routes_redirect_to_cms()
    {
        $response = $this->get('/a-propos');
        $response->assertRedirect('/pages/a-propos');
    }
}
