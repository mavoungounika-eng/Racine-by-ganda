<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crée toutes les pages CMS par défaut pour le site RACINE BY GANDA.
     */
    public function run(): void
    {
        $pages = [
            // Pages principales (déjà créées en Phase 1, mais on les inclut pour éviter les doublons)
            [
                'slug' => 'home',
                'title' => 'RACINE BY GANDA - Mode Africaine Contemporaine',
                'type' => 'hybrid',
                'template' => 'home',
                'seo_title' => 'RACINE BY GANDA - Mode Africaine Contemporaine',
                'seo_description' => 'Découvrez des créations uniques qui célèbrent notre héritage africain. Des pièces artisanales confectionnées par les meilleurs créateurs africains.',
                'is_published' => true,
            ],
            [
                'slug' => 'boutique',
                'title' => 'Notre Boutique - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'shop',
                'seo_title' => 'Boutique - RACINE BY GANDA',
                'seo_description' => 'Découvrez nos créations uniques inspirées du patrimoine africain. Mode, accessoires et art de vivre.',
                'is_published' => true,
            ],
            [
                'slug' => 'a-propos',
                'title' => 'À Propos - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'about',
                'seo_title' => 'À Propos - RACINE BY GANDA',
                'seo_description' => 'Découvrez l\'histoire de RACINE BY GANDA, notre mission et nos valeurs.',
                'is_published' => true,
            ],

            // Pages Marque & Présentation
            [
                'slug' => 'showroom',
                'title' => 'Showroom - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'showroom',
                'seo_title' => 'Showroom - RACINE BY GANDA',
                'seo_description' => 'Visitez notre showroom et découvrez nos collections en exclusivité.',
                'is_published' => true,
            ],
            [
                'slug' => 'atelier',
                'title' => 'Atelier - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'atelier',
                'seo_title' => 'Atelier - RACINE BY GANDA',
                'seo_description' => 'Découvrez notre atelier de création et notre processus artisanal.',
                'is_published' => true,
            ],
            [
                'slug' => 'createurs',
                'title' => 'Nos Créateurs - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'creators',
                'seo_title' => 'Nos Créateurs - RACINE BY GANDA',
                'seo_description' => 'Rencontrez les créateurs talentueux qui façonnent l\'identité de RACINE BY GANDA.',
                'is_published' => true,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'contact',
                'seo_title' => 'Contact - RACINE BY GANDA',
                'seo_description' => 'Contactez-nous pour toute question ou demande d\'information.',
                'is_published' => true,
            ],

            // Pages Contenu Riches
            [
                'slug' => 'evenements',
                'title' => 'Événements - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'events',
                'seo_title' => 'Événements - RACINE BY GANDA',
                'seo_description' => 'Découvrez nos événements, défilés et rencontres.',
                'is_published' => true,
            ],
            [
                'slug' => 'portfolio',
                'title' => 'Portfolio - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'portfolio',
                'seo_title' => 'Portfolio - RACINE BY GANDA',
                'seo_description' => 'Explorez notre portfolio de créations et réalisations.',
                'is_published' => true,
            ],
            [
                'slug' => 'albums',
                'title' => 'Albums Photos - RACINE BY GANDA',
                'type' => 'hybrid',
                'template' => 'albums',
                'seo_title' => 'Albums Photos - RACINE BY GANDA',
                'seo_description' => 'Parcourez nos albums photos et découvrez nos collections en images.',
                'is_published' => true,
            ],
            [
                'slug' => 'amira-ganda',
                'title' => 'Amira Ganda - Fondatrice & CEO',
                'type' => 'content',
                'template' => 'ceo',
                'seo_title' => 'Amira Ganda - Fondatrice & CEO - RACINE BY GANDA',
                'seo_description' => 'Découvrez le parcours et la vision d\'Amira Ganda, fondatrice de RACINE BY GANDA.',
                'is_published' => true,
            ],
            [
                'slug' => 'charte-graphique',
                'title' => 'Charte Graphique - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'brand-guidelines',
                'seo_title' => 'Charte Graphique - RACINE BY GANDA',
                'seo_description' => 'Découvrez notre charte graphique et nos guidelines de marque.',
                'is_published' => true,
            ],

            // Pages Informatives
            [
                'slug' => 'aide',
                'title' => 'Aide & Support - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'help',
                'seo_title' => 'Aide & Support - RACINE BY GANDA',
                'seo_description' => 'Trouvez des réponses à vos questions et obtenez de l\'aide.',
                'is_published' => true,
            ],
            [
                'slug' => 'livraison',
                'title' => 'Livraison - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'shipping',
                'seo_title' => 'Livraison - RACINE BY GANDA',
                'seo_description' => 'Informations sur nos options de livraison et délais.',
                'is_published' => true,
            ],
            [
                'slug' => 'retours-echanges',
                'title' => 'Retours & Échanges - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'returns',
                'seo_title' => 'Retours & Échanges - RACINE BY GANDA',
                'seo_description' => 'Politique de retours et d\'échanges de RACINE BY GANDA.',
                'is_published' => true,
            ],
            [
                'slug' => 'cgv',
                'title' => 'Conditions Générales de Vente - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'terms',
                'seo_title' => 'CGV - RACINE BY GANDA',
                'seo_description' => 'Conditions générales de vente de RACINE BY GANDA.',
                'is_published' => true,
            ],
            [
                'slug' => 'confidentialite',
                'title' => 'Confidentialité - RACINE BY GANDA',
                'type' => 'content',
                'template' => 'privacy',
                'seo_title' => 'Politique de Confidentialité - RACINE BY GANDA',
                'seo_description' => 'Politique de confidentialité et protection des données personnelles.',
                'is_published' => true,
            ],
        ];

        foreach ($pages as $pageData) {
            CmsPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        $this->command->info('✅ Pages CMS créées/mises à jour avec succès !');
    }
}
