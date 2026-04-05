<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="RACINE BY GANDA API",
 *     description="API REST pour la marketplace e-commerce RACINE BY GANDA. Plateforme SaaS permettant aux créateurs de vendre leurs produits avec gestion POS, abonnements, et analytics.",
 *     @OA\Contact(
 *         email="tech@racine-ganda.com",
 *         name="RACINE Tech Team"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://racine-ganda.com/terms"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="session",
 *     type="apiKey",
 *     in="cookie",
 *     name="laravel_session",
 *     description="Session-based authentication"
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Gestion des produits RACINE"
 * )
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="Gestion des commandes"
 * )
 *
 * @OA\Tag(
 *     name="POS",
 *     description="Point de vente (POS) - Sessions et ventes"
 * )
 *
 * @OA\Tag(
 *     name="Creators",
 *     description="Gestion des créateurs et abonnements"
 * )
 *
 * @OA\Tag(
 *     name="Analytics",
 *     description="Analytics et rapports"
 * )
 *
 * @OA\Tag(
 *     name="Monitoring",
 *     description="Monitoring système et métriques"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
