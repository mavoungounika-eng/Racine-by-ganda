<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\ContentBlock;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pages légales
        $pages = [
            [
                'title' => 'À Propos',
                'slug' => 'a-propos',
                'content' => '<h2>L\'histoire de RACINE BY GANDA</h2><p>Une marketplace dédiée à l\'artisanat et à la création...</p>',
                'template' => 'sidebar',
                'status' => 'published',
                'show_in_footer' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Conditions Générales de Vente',
                'slug' => 'cgv',
                'content' => '<h2>CGV</h2><p>Voici nos conditions générales de vente détaillées...</p>',
                'template' => 'default',
                'status' => 'published',
                'show_in_footer' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Politique de Confidentialité',
                'slug' => 'confidentialite',
                'content' => '<h2>Confidentialité</h2><p>Nous protégeons vos données personnelles...</p>',
                'template' => 'default',
                'status' => 'published',
                'show_in_footer' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }

        // 2. Blocs de contenu
        $blocks = [
            [
                'key' => 'home_intro',
                'title' => 'Introduction Home',
                'type' => 'html',
                'content' => '<h1>Bienvenue sur RACINE</h1><p>Le meilleur de l\'artisanat local.</p>',
                'is_active' => true,
            ],
            [
                'key' => 'home_features',
                'title' => 'Caractéristiques Home',
                'type' => 'json',
                'content' => json_encode([
                    ['icon' => 'truck', 'title' => 'Livraison Rapide', 'text' => 'Sous 48h partout en France'],
                    ['icon' => 'shield', 'title' => 'Paiement Sécurisé', 'text' => 'Transactions protégées par SSL'],
                    ['icon' => 'heart', 'title' => 'Fait Main', 'text' => 'Produits artisanaux authentiques'],
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($blocks as $block) {
            ContentBlock::updateOrCreate(['key' => $block['key']], $block);
        }

        // 3. Catégories par défaut (si vides)
        if (Category::count() === 0) {
            $categories = [
                ['name' => 'Mode', 'slug' => 'mode', 'icon' => 'shirt', 'status' => 'active'],
                ['name' => 'Décoration', 'slug' => 'decoration', 'icon' => 'home', 'status' => 'active'],
                ['name' => 'Cosmétique', 'slug' => 'cosmetique', 'icon' => 'sparkles', 'status' => 'active'],
            ];

            foreach ($categories as $cat) {
                Category::create($cat);
            }
        }
    }
}
