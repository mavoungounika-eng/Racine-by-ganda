<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Idempotent : skip si les catégories existent déjà
        if (Category::count() > 0) {
            $this->command->info('Catégories déjà présentes (' . Category::count() . '), skip.');
            return;
        }

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
                    'Ensembles 2 pieces casual',
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
                    'Gilets habilles',
                ]
            ],
            [
                'name' => 'Femme - Tenues de ceremonie',
                'children' => [
                    'Robe invitee mariage',
                    'Tenue tradi-chic',
                    'Tenue gala/soiree',
                    'Tenue officielle',
                    'Boubou',
                ]
            ],
            [
                'name' => 'Femme - Loungewear & Maison',
                'children' => [
                    'Pyjamas',
                    "Tenues d'interieur confort",
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
                    'Echarpes',
                    'Bandeaux cheveux',
                    'Mitaines',
                ]
            ],
        ];

        $now = now();
        $displayOrder = 1;
        foreach ($femmeCategories as $categoryData) {
            $parentSlug = Str::slug($categoryData['name']);
            DB::table('categories')->insertOrIgnore([
                'name'          => $categoryData['name'],
                'slug'          => $parentSlug,
                'gender'        => 'femme',
                'display_order' => $displayOrder,
                'is_active'     => true,
                'level'         => 0,
                'path'          => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $parent = Category::where('slug', $parentSlug)->first();
            DB::table('categories')->where('id', $parent->id)->update(['path' => (string)$parent->id]);
            $displayOrder++;

            $childOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                $childSlug = Str::slug($parentSlug . '-' . $childName);
                DB::table('categories')->insertOrIgnore([
                    'name'          => $childName,
                    'slug'          => $childSlug,
                    'gender'        => 'femme',
                    'parent_id'     => $parent->id,
                    'display_order' => $childOrder,
                    'is_active'     => true,
                    'level'         => 1,
                    'path'          => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $child = Category::where('slug', $childSlug)->first();
                if ($child) {
                    DB::table('categories')->where('id', $child->id)->update(['path' => $parent->id . '/' . $child->id]);
                }
                $childOrder++;
            }
        }

        // ========================================
        // CATEGORIES HOMME
        // ========================================

        $hommeCategories = [
            [
                'name' => 'Homme - Hauts',
                'children' => [
                    'T-shirts',
                    'T-shirts pagne',
                    'Chemises pagne',
                    'Chemises habillees',
                    'Polos',
                    'Sweatshirts',
                    'Hoodies',
                ]
            ],
            [
                'name' => 'Homme - Bas',
                'children' => [
                    'Pantalons habilles',
                    'Pantalons pagne',
                    'Jeans',
                    'Chinos',
                    'Shorts',
                ]
            ],
            [
                'name' => 'Homme - Ensembles & Costumes',
                'children' => [
                    'Costumes 2 pieces',
                    'Costumes 3 pieces',
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
                    'Vestes legeres',
                    'Manteaux',
                ]
            ],
            [
                'name' => 'Homme - Tenues tradi & ceremonie',
                'children' => [
                    'Boubou moderne',
                    'Tuniques pagne',
                    'Tenues tradi-chic',
                    'Tenues ceremonie',
                    'Abacost',
                ]
            ],
            [
                'name' => 'Homme - Loungewear & Maison',
                'children' => [
                    'Pyjamas',
                    "Tenues d'interieur",
                    'Ensembles relax',
                ]
            ],
            [
                'name' => 'Homme - Sport & Streetwear',
                'children' => [
                    'Survetements',
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
                    'Noeuds papillon',
                    'Foulards',
                    'Ceintures textile',
                    'Echarpes',
                ]
            ],
        ];

        foreach ($hommeCategories as $categoryData) {
            $parentSlug = Str::slug($categoryData['name']);
            DB::table('categories')->insertOrIgnore([
                'name'          => $categoryData['name'],
                'slug'          => $parentSlug,
                'gender'        => 'homme',
                'display_order' => $displayOrder,
                'is_active'     => true,
                'level'         => 0,
                'path'          => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $parent = Category::where('slug', $parentSlug)->first();
            DB::table('categories')->where('id', $parent->id)->update(['path' => (string)$parent->id]);
            $displayOrder++;

            $childOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                $childSlug = Str::slug($parentSlug . '-' . $childName);
                DB::table('categories')->insertOrIgnore([
                    'name'          => $childName,
                    'slug'          => $childSlug,
                    'gender'        => 'homme',
                    'parent_id'     => $parent->id,
                    'display_order' => $childOrder,
                    'is_active'     => true,
                    'level'         => 1,
                    'path'          => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $child = Category::where('slug', $childSlug)->first();
                if ($child) {
                    DB::table('categories')->where('id', $child->id)->update(['path' => $parent->id . '/' . $child->id]);
                }
                $childOrder++;
            }
        }

        // ========================================
        // CATEGORIES ENFANT
        // ========================================

        $enfantCategories = [
            [
                'name' => 'Enfant - Tenues scolaires',
                'children' => [
                    'Robe scolaire',
                    'Ensemble scolaire',
                ]
            ],
        ];

        foreach ($enfantCategories as $categoryData) {
            $parentSlug = Str::slug($categoryData['name']);
            DB::table('categories')->insertOrIgnore([
                'name'          => $categoryData['name'],
                'slug'          => $parentSlug,
                'gender'        => 'enfant',
                'display_order' => $displayOrder,
                'is_active'     => true,
                'level'         => 0,
                'path'          => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $parent = Category::where('slug', $parentSlug)->first();
            DB::table('categories')->where('id', $parent->id)->update(['path' => (string)$parent->id]);
            $displayOrder++;

            $childOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                $childSlug = Str::slug($parentSlug . '-' . $childName);
                DB::table('categories')->insertOrIgnore([
                    'name'          => $childName,
                    'slug'          => $childSlug,
                    'gender'        => 'enfant',
                    'parent_id'     => $parent->id,
                    'display_order' => $childOrder,
                    'is_active'     => true,
                    'level'         => 1,
                    'path'          => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $child = Category::where('slug', $childSlug)->first();
                if ($child) {
                    DB::table('categories')->where('id', $child->id)->update(['path' => $parent->id . '/' . $child->id]);
                }
                $childOrder++;
            }
        }

        $this->command->info(Category::count() . ' categories creees avec succes !');
        $this->command->info('   - Categories parentes : ' . Category::whereNull('parent_id')->count());
        $this->command->info('   - Sous-categories : ' . Category::whereNotNull('parent_id')->count());
        $this->command->info('   - Femme : ' . Category::where('gender', 'femme')->count());
        $this->command->info('   - Homme : ' . Category::where('gender', 'homme')->count());
        $this->command->info('   - Enfant : ' . Category::where('gender', 'enfant')->count());
    }
}
