<?php
/**
 * Configuration des frais de livraison
 * Tous les montants sont en XAF (devise de référence).
 * Le helper format_price() convertira automatiquement.
 *
 * Zones basées sur le champ `country` de l'adresse (code ISO 3166-1 alpha-2)
 * ou sur la devise de session comme fallback.
 */
return [

    /**
     * Tarif livraison à domicile par zone
     */
    'zones' => [
        'local' => [
            'label'        => 'Pointe-Noire / Brazzaville',
            'countries'    => ['CG'],          // Congo-Brazzaville
            'cost'         => 2000,            // FCFA
            'free_above'   => 50000,           // Livraison gratuite dès 50 000 FCFA
            'delay'        => '24 – 48h',
        ],
        'cemac' => [
            'label'        => 'Zone CEMAC',
            'countries'    => ['CM', 'GA', 'TD', 'CF', 'GQ'], // Cameroun, Gabon, Tchad, RCA, Guinée Éq.
            'cost'         => 8000,
            'free_above'   => 150000,
            'delay'        => '3 – 7 jours',
        ],
        'afrique' => [
            'label'        => 'Afrique (hors CEMAC)',
            'countries'    => ['SN', 'CI', 'ML', 'BF', 'GN', 'TG', 'BJ', 'NE', 'CD', 'AO', 'ZA', 'NG', 'GH', 'MA', 'TN', 'DZ', 'EG'],
            'cost'         => 15000,
            'free_above'   => 250000,
            'delay'        => '5 – 14 jours',
        ],
        'europe' => [
            'label'        => 'Europe',
            'countries'    => ['FR', 'BE', 'CH', 'DE', 'ES', 'IT', 'NL', 'PT', 'LU', 'AT'],
            'cost'         => 25000,
            'free_above'   => 400000,
            'delay'        => '7 – 14 jours',
        ],
        'international' => [
            'label'        => 'International',
            'countries'    => [],              // Fallback : tout le reste
            'cost'         => 35000,
            'free_above'   => 600000,
            'delay'        => '10 – 21 jours',
        ],
    ],

    /**
     * Retrait en showroom : toujours gratuit
     */
    'pickup_cost' => 0,

    /**
     * Zone par défaut si pays inconnu
     */
    'default_zone' => 'local',

    /**
     * Mapping devise → zone (fallback quand pas d'adresse)
     */
    'currency_zone_fallback' => [
        'XAF' => 'local',
        'XOF' => 'afrique',
        'EUR' => 'europe',
    ],
];
