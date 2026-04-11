<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Nouvelle Collection 2025',
                'subtitle' => 'Découvrez des créations uniques qui célèbrent notre héritage africain',
                'image_path' => 'banners/hero-collection.jpg',
                'link_url' => '/boutique',
                'link_text' => 'Découvrir',
                'position' => 'homepage_hero',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'L\'Élégance Africaine Réinventée',
                'subtitle' => 'Des pièces artisanales confectionnées par les meilleurs créateurs',
                'image_path' => 'banners/hero-elegance.jpg',
                'link_url' => '/createurs',
                'link_text' => 'Nos Créateurs',
                'position' => 'homepage_hero',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => 'Livraison offerte dès 50 000 XAF',
                'subtitle' => 'Sur toute la collection en ligne',
                'image_path' => 'banners/promo-livraison.jpg',
                'link_url' => '/boutique',
                'link_text' => 'En profiter',
                'position' => 'homepage_promo',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Devenez Créateur Partenaire',
                'subtitle' => 'Rejoignez la marketplace RACINE BY GANDA',
                'image_path' => 'banners/promo-createur.jpg',
                'link_url' => '/devenir-createur',
                'link_text' => 'Rejoindre',
                'position' => 'homepage_promo',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => 'Meilleures Ventes',
                'subtitle' => 'Les pièces les plus populaires',
                'image_path' => 'banners/sidebar-bestsellers.jpg',
                'link_url' => '/boutique?sort=popular',
                'link_text' => 'Voir',
                'position' => 'sidebar',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Soldes de Saison',
                'subtitle' => 'Jusqu\'à -30% sur une sélection',
                'image_path' => 'banners/shop-top-soldes.jpg',
                'link_url' => '/boutique?promo=1',
                'link_text' => 'Découvrir',
                'position' => 'category_top',
                'status' => 'active',
                'sort_order' => 1,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title'], 'position' => $banner['position']],
                $banner
            );
        }

        $this->command->info('✅ ' . Banner::count() . ' bannières créées avec succès !');
    }
}
