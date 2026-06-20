<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Hauts' => [
                'Haut' => 'femme',
                'Kimono' => 'femme',
                'Chemise (pagne/tissu)' => 'unisex',
            ],
            'Bas' => [
                'Culotte / Jupe' => 'femme',
                'Pantalon en pagne' => 'unisex',
                'Pantalon en tissu' => 'unisex',
                'Ensemble jupe' => 'femme',
            ],
            'Robes' => [
                'Robe en pagne' => 'femme',
                'Robe en tissu' => 'femme',
                'Robe soirée' => 'femme',
                'Robe de mariage' => 'femme',
                'Tenue scolaire robe' => 'enfant',
            ],
            'Ensembles & Combinaisons' => [
                'Combinaison tissus' => 'femme',
                'Combinaison en pagne' => 'femme',
                'Combi-short' => 'femme',
                'Ensemble chemise/pantalon en pagne' => 'homme',
                'Ensemble chemise/pantalon en tissu' => 'homme',
                'Tenue scolaire ensemble' => 'enfant',
            ],
            'Vestes & Costumes' => [
                'Veste coupe simple' => 'homme',
                'Veste coupe croisée' => 'homme',
                'Ensemble veste coupe simple' => 'homme',
                'Ensemble veste coupe croisée' => 'homme',
            ],
            'Tenues traditionnelles' => [
                'Boubou' => 'unisex',
                'Abacost' => 'homme',
            ],
        ];

        $order = 0;

        foreach ($tree as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'gender' => 'unisex',
                'level' => 0,
                'parent_id' => null,
                'display_order' => $order,
                'sort_order' => $order,
                'is_active' => true,
                'status' => 'active',
            ]);

            $parent->update(['path' => "{$parent->id}/"]);

            $childOrder = 0;
            foreach ($children as $childName => $gender) {
                $child = Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'gender' => $gender,
                    'level' => 1,
                    'parent_id' => $parent->id,
                    'display_order' => $childOrder,
                    'sort_order' => $childOrder,
                    'is_active' => true,
                    'status' => 'active',
                ]);

                $child->update(['path' => "{$parent->id}/{$child->id}/"]);
                $childOrder++;
            }

            $order++;
        }
    }
}
