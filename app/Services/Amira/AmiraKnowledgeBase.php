<?php

namespace App\Services\Amira;

class AmiraKnowledgeBase
{
    /**
     * Base de connaissances statique pour réponses instantanées
     */
    private array $knowledgeBase = [];

    public function __construct()
    {
        $this->loadKnowledge();
    }

    /**
     * Rechercher une réponse dans la base de connaissances
     */
    public function search(string $question): ?array
    {
        $question = strtolower(trim($question));
        
        foreach ($this->knowledgeBase as $category => $items) {
            foreach ($items as $item) {
                foreach ($item['keywords'] as $keyword) {
                    if (str_contains($question, strtolower($keyword))) {
                        return [
                            'answer' => $item['answer'],
                            'category' => $category,
                            'source' => 'knowledge_base',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Charger la base de connaissances
     */
    private function loadKnowledge(): void
    {
        $this->knowledgeBase = [
            'shipping' => [
                [
                    'keywords' => ['livraison', 'délai', 'combien de temps', 'quand', 'recevoir'],
                    'answer' => 'Les délais de livraison sont de 3 à 7 jours ouvrés pour Douala et Yaoundé, et de 5 à 10 jours pour les autres villes du Cameroun.',
                ],
                [
                    'keywords' => ['frais de livraison', 'coût livraison', 'prix livraison'],
                    'answer' => 'Les frais de livraison sont de 2000 FCFA pour Douala et Yaoundé, et de 3000 FCFA pour les autres villes.',
                ],
                [
                    'keywords' => ['suivi', 'tracking', 'où est ma commande'],
                    'answer' => 'Vous pouvez suivre votre commande depuis votre compte client, section "Mes commandes". Vous recevrez également un email avec le lien de suivi.',
                ],
            ],
            'orders' => [
                [
                    'keywords' => ['commander', 'acheter', 'passer commande'],
                    'answer' => 'Pour passer commande, ajoutez les produits à votre panier, puis cliquez sur "Commander". Vous pourrez choisir votre mode de paiement (carte bancaire ou Mobile Money).',
                ],
                [
                    'keywords' => ['paiement', 'payer', 'carte', 'mobile money'],
                    'answer' => 'Nous acceptons les paiements par carte bancaire (Visa, Mastercard) et Mobile Money (MTN, Orange). Le paiement est sécurisé.',
                ],
                [
                    'keywords' => ['annuler', 'modifier commande'],
                    'answer' => 'Vous pouvez annuler votre commande dans les 2 heures suivant la validation. Connectez-vous à votre compte et accédez à "Mes commandes".',
                ],
            ],
            'returns' => [
                [
                    'keywords' => ['retour', 'rembours', 'échanger', 'remplacer'],
                    'answer' => 'Vous disposez de 14 jours pour retourner un produit. Le produit doit être dans son état d\'origine avec l\'étiquette. Contactez le support pour initier un retour.',
                ],
                [
                    'keywords' => ['garantie', 'défectueux', 'abîmé'],
                    'answer' => 'Si votre produit est défectueux ou abîmé, contactez-nous immédiatement à support@racinebyganda.com avec des photos. Nous traiterons votre demande sous 48h.',
                ],
            ],
            'products' => [
                [
                    'keywords' => ['taille', 'pointure', 'guide des tailles'],
                    'answer' => 'Consultez le guide des tailles disponible sur chaque fiche produit. En cas de doute, contactez le créateur via la messagerie.',
                ],
                [
                    'keywords' => ['stock', 'disponible', 'rupture'],
                    'answer' => 'La disponibilité est indiquée sur chaque fiche produit. Si un produit est en rupture, vous pouvez activer les notifications pour être alerté du retour en stock.',
                ],
                [
                    'keywords' => ['matière', 'composition', 'tissu'],
                    'answer' => 'La composition et les matières sont détaillées dans la description de chaque produit. Pour plus d\'informations, contactez directement le créateur.',
                ],
            ],
            'account' => [
                [
                    'keywords' => ['compte', 'inscription', 'créer compte'],
                    'answer' => 'Créez votre compte gratuitement en cliquant sur "S\'inscrire". Vous aurez accès à votre historique de commandes et pourrez suivre vos livraisons.',
                ],
                [
                    'keywords' => ['mot de passe', 'oublié', 'réinitialiser'],
                    'answer' => 'Cliquez sur "Mot de passe oublié" sur la page de connexion. Vous recevrez un email pour réinitialiser votre mot de passe.',
                ],
            ],
        ];
    }
}
