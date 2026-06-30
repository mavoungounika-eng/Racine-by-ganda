<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrandDemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = Category::query()->value('id');
        $userId = User::query()
            ->where('email', 'admin@racine.com')
            ->value('id') ?? User::query()->value('id');

        if (!$categoryId || !$userId) {
            $this->command?->warn('Impossible de seed les produits demo: category_id ou user_id manquant.');
            return;
        }

        $products = [
            [
                'slug' => 'racine-boubou-heritage',
                'title' => 'Boubou Heritage RACINE',
                'description' => 'Boubou ample en coton premium, coupe ceremonie, finition artisanale.',
                'price' => 89000,
                'stock' => 24,
            ],
            [
                'slug' => 'racine-ensemble-wax-sahara',
                'title' => 'Ensemble Wax Sahara',
                'description' => 'Ensemble 2 pieces en wax africain, style urbain chic.',
                'price' => 62000,
                'stock' => 30,
            ],
            [
                'slug' => 'racine-robe-ndop-lumiere',
                'title' => 'Robe Ndop Lumiere',
                'description' => 'Robe moderne inspiree du textile Ndop, ligne elegante et confortable.',
                'price' => 54000,
                'stock' => 18,
            ],
            [
                'slug' => 'racine-veste-kente-eclipse',
                'title' => 'Veste Kente Eclipse',
                'description' => 'Veste taillee en tissu kente, look premium pour sorties et ceremonies.',
                'price' => 71000,
                'stock' => 14,
            ],
            [
                'slug' => 'racine-pantalon-bogolan-noir',
                'title' => 'Pantalon Bogolan Noir',
                'description' => 'Pantalon coupe droite en bogolan, style minimal et signature africaine.',
                'price' => 39000,
                'stock' => 26,
            ],
            [
                'slug' => 'racine-chemise-afroline-ivoire',
                'title' => 'Chemise Afroline Ivoire',
                'description' => 'Chemise unisexe manches longues, finition propre pour usage quotidien.',
                'price' => 33000,
                'stock' => 40,
            ],
            [
                'slug' => 'racine-jupe-ankara-sunrise',
                'title' => 'Jupe Ankara Sunrise',
                'description' => 'Jupe taille haute en ankara, design vibrant et coupe feminine.',
                'price' => 28000,
                'stock' => 35,
            ],
            [
                'slug' => 'racine-caftan-ganda-signature',
                'title' => 'Caftan Ganda Signature',
                'description' => 'Caftan habille signature RACINE BY GANDA, edition pilote.',
                'price' => 97000,
                'stock' => 12,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'category_id' => $categoryId,
                    'user_id' => $userId,
                    'product_type' => 'brand',
                    'title' => $product['title'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'is_active' => true,
                    'main_image' => null,
                ]
            );
        }

        $this->command?->info('Produits demo RACINE seeded: '.count($products));
    }
}
