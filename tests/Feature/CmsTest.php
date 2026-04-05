<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles nécessaires
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'role' => 'admin',
            'is_admin' => true,
            'status' => 'active',
            'auth_version' => 1,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function a_published_page_is_accessible_by_slug()
    {
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Some content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('frontend.page.show', $page->slug));

        $response->assertStatus(200);
        $response->assertSee('Test Page');
        $response->assertSee('Some content');
    }

    /** @test */
    public function a_draft_page_is_not_accessible()
    {
        $page = Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => 'Draft content',
            'status' => 'draft',
        ]);

        $response = $this->get(route('frontend.page.show', $page->slug));

        $response->assertStatus(404);
    }

    /** @test */
    public function page_caching_works()
    {
        Cache::tags(['cms', 'pages'])->flush();

        $page = Page::create([
            'title' => 'Cache Page',
            'slug' => 'cache-page',
            'content' => 'Cache content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // First call - should cache
        $this->get(route('frontend.page.show', $page->slug));
        $this->assertTrue(Cache::tags(['cms', 'pages'])->has("cms_page:{$page->slug}"));

        // Update page - should clear cache (via Observer)
        $page->update(['title' => 'Updated Cache Page']);
        $this->assertFalse(Cache::tags(['cms', 'pages'])->has("cms_page:{$page->slug}"));
    }

    /** @test */
    public function admin_can_create_a_page()
    {
        // On force l'utilisation de Sanctum pour cette requête
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.admin.cms.pages.store'), [
                'title' => 'Admin Page',
                'slug' => 'admin-page',
                'status' => 'published',
                'template' => 'default',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', ['slug' => 'admin-page']);
    }

    /** @test */
    public function category_hierarchy_is_calculated_automatically()
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $parent->refresh();
        $this->assertEquals(0, $parent->level);
        $this->assertEquals((string)$parent->id, $parent->path);

        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);
        $child->refresh();
        $this->assertEquals(1, $child->level);
        $this->assertEquals($parent->id . '/' . $child->id, $child->path);

        $grandchild = Category::create(['name' => 'GrandChild', 'slug' => 'grand-child', 'parent_id' => $child->id]);
        $grandchild->refresh();
        $this->assertEquals(2, $grandchild->level);
        $this->assertEquals($parent->id . '/' . $child->id . '/' . $grandchild->id, $grandchild->path);
    }

    /** @test */
    public function banner_impression_is_tracked()
    {
        $banner = \App\Models\Banner::create([
            'title' => 'Promo',
            'image_path' => 'test.jpg',
            'status' => 'active',
        ]);

        $job = new \App\Jobs\TrackBannerImpression($banner);
        $job->handle();

        $this->assertEquals(1, $banner->fresh()->impressions_count);
    }
}
