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
    | RCS Number (Registre du Commerce et des Sociétés)
    | Format: RCS [Ville] XXX XXX XXX
    | Example: RCS Paris 123 456 789
    */
    'rcs' => env('COMPANY_RCS', ''),

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
    |--------------------------------------------------------------------------
    | Social Media
    |--------------------------------------------------------------------------
    |
    | Social media profile URLs for RACINE BY GANDA.
    | Leave empty if not yet created. Links will not display if empty.
    |
    */

    'instagram' => env('SOCIAL_INSTAGRAM', ''),
    'facebook' => env('SOCIAL_FACEBOOK', ''),
    'twitter' => env('SOCIAL_TWITTER', ''),
    'tiktok' => env('SOCIAL_TIKTOK', ''),
    'pinterest' => env('SOCIAL_PINTEREST', ''),
    'linkedin' => env('SOCIAL_LINKEDIN', ''),

];
