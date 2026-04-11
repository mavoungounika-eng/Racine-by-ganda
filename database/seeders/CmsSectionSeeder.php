<?php

namespace Database\Seeders;

use App\Models\CmsSection;
use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsSectionSeeder extends Seeder
{
    public function run(): void
    {
        $heroSections = [
            'home' => [
                'badge' => 'Nouvelle Collection 2025',
                'title' => "L'Élégance Africaine Réinventée",
                'description' => "Découvrez des créations uniques qui célèbrent notre héritage. Des pièces artisanales confectionnées par les meilleurs créateurs africains.",
            ],
            'boutique' => [
                'badge' => 'Notre Collection',
                'title' => 'Notre Boutique',
                'description' => 'Découvrez nos créations uniques inspirées du patrimoine africain.',
            ],
            'a-propos' => [
                'badge' => 'Notre Histoire',
                'title' => 'Célébrer la Beauté Africaine',
                'description' => "RACINE BY GANDA est née d'une passion profonde pour l'artisanat africain.",
            ],
            'showroom' => [
                'badge' => 'Showroom',
                'title' => 'Notre Showroom',
                'description' => 'Visitez notre espace et découvrez nos collections en exclusivité.',
            ],
            'atelier' => [
                'badge' => 'Atelier',
                'title' => 'Notre Atelier',
                'description' => 'Découvrez notre processus de création et notre savoir-faire artisanal.',
            ],
            'createurs' => [
                'badge' => 'Nos Talents',
                'title' => 'Nos Créateurs',
                'description' => "Rencontrez les créateurs talentueux qui façonnent l'identité de RACINE BY GANDA.",
            ],
            'contact' => [
                'badge' => 'Contactez-nous',
                'title' => 'Contact',
                'description' => 'Nous sommes là pour répondre à toutes vos questions.',
            ],
            'evenements' => [
                'badge' => 'Événements',
                'title' => 'Nos Événements',
                'description' => 'Découvrez nos défilés, expositions et rencontres.',
            ],
            'portfolio' => [
                'badge' => 'Portfolio',
                'title' => 'Notre Portfolio',
                'description' => 'Explorez nos créations et réalisations.',
            ],
            'albums' => [
                'badge' => 'Galerie',
                'title' => 'Albums Photos',
                'description' => 'Parcourez nos albums et découvrez nos collections en images.',
            ],
            'amira-ganda' => [
                'badge' => 'Fondatrice',
                'title' => 'Amira Ganda',
                'description' => 'Découvrez le parcours et la vision de notre fondatrice.',
            ],
            'aide' => [
                'badge' => 'Support',
                'title' => 'Aide & Support',
                'description' => "Trouvez des réponses à vos questions et obtenez de l'aide.",
            ],
            'livraison' => [
                'badge' => 'Livraison',
                'title' => 'Informations de Livraison',
                'description' => 'Découvrez nos options de livraison et délais.',
            ],
            'retours-echanges' => [
                'badge' => 'Retours',
                'title' => 'Retours & Échanges',
                'description' => "Politique de retours et d'échanges de RACINE BY GANDA.",
            ],
            'cgv' => [
                'badge' => 'CGV',
                'title' => 'Conditions Générales de Vente',
                'description' => 'Consultez nos conditions générales de vente.',
            ],
            'confidentialite' => [
                'badge' => 'Confidentialité',
                'title' => 'Politique de Confidentialité',
                'description' => 'Protection de vos données personnelles.',
            ],
        ];

        $created = 0;
        foreach ($heroSections as $pageSlug => $data) {
            if (!Page::where('slug', $pageSlug)->exists()) {
                continue;
            }

            CmsSection::updateOrCreate(
                ['page_slug' => $pageSlug, 'key' => 'hero'],
                [
                    'type' => 'banner',
                    'data' => $data,
                    'is_active' => true,
                    'order' => 0,
                ]
            );
            $created++;
        }

        $this->command->info("✅ {$created} sections hero CMS créées !");
    }
}
