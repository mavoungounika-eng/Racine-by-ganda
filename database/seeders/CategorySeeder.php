<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing categories
        Category::query()->delete();

        // ========================================
        // CATÉGORIES FEMME
        // ========================================

        $femmeCategories = [
            [
                'name' => 'Femme - Hauts',
                'children' => [
                    'T-shirts & tops',
                    'Tops pagne',
                    'Chemisiers & blouses',
                    'Crop tops',
                    'Bodys',
                    'Gilets & cardigans',
                ]
            ],
            [
                'name' => 'Femme - Bas',
                'children' => [
                    'Pantalons pagne',
                    'Pantalons taille haute',
                    'Jeans',
                    'Jupes courtes',
                    'Jupes midi/longues',
                    'Shorts',
                ]
            ],
            [
                'name' => 'Femme - Robes & Combinaisons',
                'children' => [
                    'Robes pagne',
                    'Robes droites',
                    'Robes cintrées',
                    'Robes longues',
                    'Robes de soirée',
                    'Combinaisons pantalon',
                    'Combishorts',
                ]
            ],
            [
                'name' => 'Femme - Ensembles & Tailleur',
                'children' => [
                    'Ensemble veste + pantalon',
                    'Ensemble jupe',
                    'Tailleur pagne',
                    'Ensembles 2 pièces casual',
                    'Ensemble crop + jupe',
                ]
            ],
            [
                'name' => 'Femme - Vestes & Manteaux',
                'children' => [
                    'Blazers',
                    'Blazers pagne',
                    'Bombers',
                    'Kimonos pagne',
                    'Manteaux',
                    'Gilets habillés',
                ]
            ],
            [
                'name' => 'Femme - Tenues de cérémonie',
                'children' => [
                    'Robe invitée mariage',
                    'Tenue tradi-chic',
                    'Tenue gala/soirée',
                    'Tenue officielle',
                ]
            ],
            [
                'name' => 'Femme - Loungewear & Maison',
                'children' => [
                    'Pyjamas',
                    'Tenues d\'intérieur confort',
                    'Ensembles cocooning',
                    'Peignoirs',
                ]
            ],
            [
                'name' => 'Femme - Sport & Streetwear',
                'children' => [
                    'Jogging',
                    'Leggings',
                    'T-shirts sport',
                    'Sweatshirts',
                    'Hoodies',
                ]
            ],
            [
                'name' => 'Femme - Accessoires textile',
                'children' => [
                    'Foulards & turbans',
                    'Ceintures pagne',
                    'Écharpes',
                    'Bandeaux cheveux',
                    'Mitaines',
                ]
            ],
        ];

        $displayOrder = 1;
        foreach ($femmeCategories as $categoryData) {
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'gender' => 'femme',
                'display_order' => $displayOrder++,
                'is_active' => true,
            ]);

            $childOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($parent->slug . '-' . $childName), // Prefix with parent slug
                    'gender' => 'femme',
                    'parent_id' => $parent->id,
                    'display_order' => $childOrder++,
                    'is_active' => true,
                ]);
            }
        }

        // ========================================
        // CATÉGORIES HOMME
        // ========================================

        $hommeCategories = [
            [
                'name' => 'Homme - Hauts',
                'children' => [
                    'T-shirts',
                    'T-shirts pagne',
                    'Chemises pagne',
                    'Chemises habillées',
                    'Polos',
                    'Sweatshirts',
                    'Hoodies',
                ]
            ],
            [
                'name' => 'Homme - Bas',
                'children' => [
                    'Pantalons habillés',
                    'Pantalons pagne',
                    'Jeans',
                    'Chinos',
                    'Shorts',
                ]
            ],
            [
                'name' => 'Homme - Ensembles & Costumes',
                'children' => [
                    'Costumes 2 pièces',
                    'Costumes 3 pièces',
                    'Ensembles pagne (veste + pantalon)',
                    'Ensembles tunique + pantalon',
                ]
            ],
            [
                'name' => 'Homme - Vestes & Manteaux',
                'children' => [
                    'Blazers',
                    'Blazers pagne',
                    'Bombers',
                    'Vestes légères',
                    'Manteaux',
                ]
            ],
            [
                'name' => 'Homme - Tenues tradi & cérémonie',
                'children' => [
                    'Boubou moderne',
                    'Tuniques pagne',
                    'Tenues tradi-chic',
                    'Tenues cérémonie',
                ]
            ],
            [
                'name' => 'Homme - Loungewear & Maison',
                'children' => [
                    'Pyjamas',
                    'Tenues d\'intérieur',
                    'Ensembles relax',
                ]
            ],
            [
                'name' => 'Homme - Sport & Streetwear',
                'children' => [
                    'Survêtements',
                    'Jogging',
                    'T-shirts street',
                    'Shorts sport',
                    'Hoodies streetwear',
                ]
            ],
            [
                'name' => 'Homme - Accessoires textile',
                'children' => [
                    'Cravates',
                    'Nœuds papillon',
                    'Foulards',
                    'Ceintures textile',
                    'Écharpes',
                ]
            ],
        ];

        foreach ($hommeCategories as $categoryData) {
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'gender' => 'homme',
                'display_order' => $displayOrder++,
                'is_active' => true,
            ]);

            $childOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($parent->slug . '-' . $childName), // Prefix with parent slug
                    'gender' => 'homme',
                    'parent_id' => $parent->id,
                    'display_order' => $childOrder++,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ ' . Category::count() . ' catégories créées avec succès !');
        $this->command->info('   - Catégories parentes : ' . Category::whereNull('parent_id')->count());
        $this->command->info('   - Sous-catégories : ' . Category::whereNotNull('parent_id')->count());
        $this->command->info('   - Femme : ' . Category::where('gender', 'femme')->count());
        $this->command->info('   - Homme : ' . Category::where('gender', 'homme')->count());
    }
}
