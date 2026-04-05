<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\ContentBlock;
use Illuminate\Database\Seeder;

class ContactInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Content Block: contact_info
        ContentBlock::updateOrCreate(
            ['key' => 'contact_info'],
            [
                'title'   => 'Coordonnées',
                'type'    => 'json',
                'content' => json_encode([
                    'name'     => 'AMIRA GANDA',
                    'title'    => 'CEO & Fondatrice',
                    'email'    => 'contact@racinebyganda.com',
                    'phone'    => '+242 065166110',
                    'company'  => 'RACINE BY GANDA',
                ]),
                'is_active' => true,
                'description' => 'Coordonnées officielles RGB',
            ]
        );

        // 2. Update home blocks
        $homeIntro = ContentBlock::where('key', 'home_intro')->first();
        if ($homeIntro) {
            $homeIntro->update([
                'content' => '<h1>Bienvenue chez RACINE BY GANDA</h1><p>Fondé par <strong>AMIRA GANDA</strong>, nous célébrons l\'héritage africain à travers des créations authentiques.</p>'
            ]);
        }

        $homeCta = ContentBlock::updateOrCreate(
            ['key' => 'home_cta'],
            [
                'title' => 'Home CTA',
                'type' => 'html',
                'content' => '<div class="cta-banner"><h2>Rejoignez l\'univers RACINE</h2><p>Contactez-nous au <a href="tel:+242065166110">+242 065166110</a> ou via <a href="mailto:contact@racinebyganda.com">contact@racinebyganda.com</a></p></div>',
                'is_active' => true,
            ]
        );

        // 3. Page Contact
        Page::updateOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contactez-nous',
                'template' => 'default',
                'status' => 'published',
                'show_in_footer' => true,
                'show_in_header' => false,
                'content' => '
<div class="contact-info">
  <h2>RACINE BY GANDA</h2>
  <p><strong>AMIRA GANDA</strong> — CEO & Fondatrice</p>
  <p>
    <a href="mailto:contact@racinebyganda.com">
      contact@racinebyganda.com
    </a>
  </p>
  <p>
    <a href="tel:+242065166110">+242 065166110</a>
  </p>
</div>',
                'published_at' => now(),
            ]
        );
    }
}
