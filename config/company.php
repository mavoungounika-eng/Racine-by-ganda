<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for RACINE BY GANDA legal information.
    | These values are used across frontend views (terms, privacy, contact).
    | MUST be filled in .env before production deployment.
    |
    */

    'name' => env('COMPANY_NAME', 'RACINE BY GANDA'),

    /*
    | Pays et ville du siège social (juridiction OHADA)
    */
    'country' => env('COMPANY_COUNTRY', 'République du Congo'),
    'city' => env('COMPANY_CITY', 'Pointe-Noire'),

    /*
    | RCCM Number (Registre du Commerce et du Crédit Mobilier — OHADA)
    | Format: RCCM CG-PNR-XX-XXXX-XX-XXXXX (République du Congo)
    | Example: RCCM CG-PNR-01-2026-B12-00001
    */
    'rccm' => env('COMPANY_RCCM', ''),

    /*
    | Legal address (siège social)
    | Full address including street, postal code, city
    */
    'address' => env('COMPANY_ADDRESS', ''),

    /*
    | Support phone number
    | Format: +XXX X XX XX XX XX (international format recommended)
    */
    'phone' => env('COMPANY_PHONE', ''),

    /*
    | Share capital (capital social)
    | Format: numeric value in euros (e.g., 10000 for 10 000 €)
    */
    'capital' => env('COMPANY_CAPITAL', ''),

    /*
    | Contact emails
    */
    'email' => env('COMPANY_EMAIL', 'contact@racinebyganda.com'),
    'support_email' => env('COMPANY_SUPPORT_EMAIL', 'support@racinebyganda.com'),
    'dpo_email' => env('COMPANY_DPO_EMAIL', 'dpo@racinebyganda.com'),

    /*
    | CEO / Legal representative
    */
    'ceo' => env('COMPANY_CEO', 'Amira Ganda'),

    /*
    | VAT number (TVA intracommunautaire) - if applicable
    */
    'vat_number' => env('COMPANY_VAT_NUMBER', ''),

    /*
    | Devises — XAF (FCFA) principale, EUR secondaire (diaspora)
    */
    'currency_main' => 'XAF',
    'currency_secondary' => 'EUR',

    /*
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    |
    | Social media profile URLs for RACINE BY GANDA.
    | Leave empty if not yet created. Links will not display if empty.
    |
    */

    'instagram' => env('SOCIAL_INSTAGRAM', ''),
    'facebook'  => env('SOCIAL_FACEBOOK', ''),
    'twitter'   => env('SOCIAL_TWITTER', ''),
    'tiktok'    => env('SOCIAL_TIKTOK', ''),
    'pinterest' => env('SOCIAL_PINTEREST', ''),
    'youtube'   => env('SOCIAL_YOUTUBE', ''),
    'linkedin'  => env('SOCIAL_LINKEDIN', ''),
];
