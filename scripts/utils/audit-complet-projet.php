<?php

/**
 * AUDIT COMPLET EN PROFONDEUR - RACINE BY GANDA
 * Vérifie : Design, Vues, Layouts, Routes pour tous les modules
 */

echo "🔍 AUDIT COMPLET EN PROFONDEUR - RACINE BY GANDA\n";
echo "==================================================\n\n";

$rapport = [
    'date' => date('Y-m-d H:i:s'),
    'layouts' => [],
    'vues' => [],
    'routes' => [],
    'modules' => [],
    'design' => [],
    'erreurs' => [],
    'avertissements' => []
];

// 1. AUDIT DES LAYOUTS
echo "1️⃣  AUDIT DES LAYOUTS\n";
echo "---------------------\n";

$layouts = glob('resources/views/layouts/*.blade.php');
$layoutsModules = glob('modules/*/Resources/views/layouts/*.blade.php');
$allLayouts = array_merge($layouts, $layoutsModules);

foreach ($allLayouts as $layout) {
    $content = file_get_contents($layout);
    $relPath = str_replace('\\', '/', $layout);
    
    $info = [
        'fichier' => $relPath,
        'bootstrap' => false,
        'tailwind' => false,
        'racine_css' => false,
        'jquery' => false,
        'alpine' => false,
        'tailwind_cdn' => false,
        'bootstrap_cdn' => false,
        'framework' => 'unknown'
    ];
    
    // Détection du framework
    if (strpos($content, 'bootstrap') !== false || strpos($content, 'Bootstrap') !== false) {
        $info['bootstrap'] = true;
        $info['framework'] = 'Bootstrap';
    }
    if (strpos($content, 'tailwind') !== false || strpos($content, 'Tailwind') !== false) {
        $info['tailwind'] = true;
        if ($info['framework'] === 'unknown') {
            $info['framework'] = 'Tailwind';
        } else {
            $info['framework'] = 'Mixte';
        }
    }
    if (strpos($content, 'racine-variables.css') !== false) {
        $info['racine_css'] = true;
    }
    if (strpos($content, 'jquery') !== false) {
        $info['jquery'] = true;
    }
    if (strpos($content, 'alpine') !== false || strpos($content, 'Alpine') !== false) {
        $info['alpine'] = true;
    }
    if (strpos($content, 'cdn.tailwindcss.com') !== false) {
        $info['tailwind_cdn'] = true;
    }
    if (strpos($content, 'getbootstrap.com') !== false || strpos($content, 'cdn.jsdelivr.net/npm/bootstrap') !== false) {
        $info['bootstrap_cdn'] = true;
    }
    
    // Vérifications de cohérence
    if ($info['tailwind'] && !$info['bootstrap'] && !$info['racine_css']) {
        $rapport['avertissements'][] = "⚠️  Layout {$relPath} : Utilise Tailwind sans Bootstrap/RACINE";
    }
    if ($info['bootstrap'] && $info['tailwind']) {
        $rapport['avertissements'][] = "⚠️  Layout {$relPath} : Mixte Bootstrap + Tailwind (incohérence)";
    }
    
    $rapport['layouts'][] = $info;
    echo ($info['framework'] === 'Bootstrap' ? '✅' : '⚠️ ') . " " . basename($layout) . " : {$info['framework']}\n";
}

echo "\n";

// 2. AUDIT DES VUES
echo "2️⃣  AUDIT DES VUES\n";
echo "------------------\n";

function scanViews($dir, $basePath = '') {
    $files = [];
    $items = glob($dir . '/*');
    
    foreach ($items as $item) {
        if (is_dir($item) && basename($item) !== 'obsolete' && basename($item) !== 'node_modules') {
            $files = array_merge($files, scanViews($item, $basePath));
        } elseif (is_file($item) && strpos($item, '.blade.php') !== false) {
            $files[] = $item;
        }
    }
    
    return $files;
}

$vuesPaths = [
    'resources/views/admin',
    'resources/views/frontend',
    'resources/views/profile',
    'resources/views/creator',
    'resources/views/checkout',
    'resources/views/auth',
    'modules/*/Resources/views'
];

$allVues = [];
foreach ($vuesPaths as $path) {
    if (strpos($path, '*') !== false) {
        $dirs = glob($path);
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $allVues = array_merge($allVues, scanViews($dir));
            }
        }
    } elseif (is_dir($path)) {
        $allVues = array_merge($allVues, scanViews($path));
    }
}

$layoutsUtilises = [];
$vuesSansLayout = [];
$vuesIncoherentes = [];

foreach ($allVues as $vue) {
    $content = file_get_contents($vue);
    $relPath = str_replace('\\', '/', $vue);
    
    // Détection du layout utilisé
    if (preg_match("/@extends\(['\"](.+?)['\"]\)/", $content, $matches)) {
        $layoutUsed = $matches[1];
        
        if (!isset($layoutsUtilises[$layoutUsed])) {
            $layoutsUtilises[$layoutUsed] = [];
        }
        $layoutsUtilises[$layoutUsed][] = $relPath;
        
        // Vérifier si le layout existe
        $layoutFile = str_replace('.', '/', $layoutUsed) . '.blade.php';
        $possiblePaths = [
            'resources/views/' . $layoutFile,
            'modules/*/Resources/views/' . $layoutFile
        ];
        
        $layoutExists = false;
        foreach ($possiblePaths as $layoutPath) {
            if (strpos($layoutPath, '*') !== false) {
                $found = glob($layoutPath);
                if (!empty($found)) {
                    $layoutExists = true;
                    break;
                }
            } elseif (file_exists($layoutPath)) {
                $layoutExists = true;
                break;
            }
        }
        
        if (!$layoutExists) {
            $rapport['erreurs'][] = "❌ Vue {$relPath} : Layout '{$layoutUsed}' introuvable";
            $vuesIncoherentes[] = $relPath;
        }
    } else {
        $vuesSansLayout[] = $relPath;
    }
    
    // Détection de CSS inline
    if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $content)) {
        $rapport['avertissements'][] = "⚠️  Vue {$relPath} : Contient du CSS inline";
    }
    
    // Détection de JS inline
    if (preg_match('/<script[^>]*>(?!.*src=)(.*?)<\/script>/is', $content)) {
        if (strpos($content, 'src=') === false || preg_match('/<script[^>]*>(?=.*src=).*?<\/script>/is', $content)) {
            $rapport['avertissements'][] = "⚠️  Vue {$relPath} : Contient du JavaScript inline";
        }
    }
}

echo "Total vues scannées : " . count($allVues) . "\n";
echo "Layouts utilisés : " . count($layoutsUtilises) . "\n";
if (!empty($vuesSansLayout)) {
    echo "⚠️  Vues sans layout : " . count($vuesSansLayout) . "\n";
}
if (!empty($vuesIncoherentes)) {
    echo "❌ Vues avec layout introuvable : " . count($vuesIncoherentes) . "\n";
}

$rapport['vues'] = [
    'total' => count($allVues),
    'layouts_utilises' => $layoutsUtilises,
    'sans_layout' => $vuesSansLayout,
    'incoherentes' => $vuesIncoherentes
];

echo "\n";

// 3. AUDIT DES ROUTES
echo "3️⃣  AUDIT DES ROUTES\n";
echo "--------------------\n";

$routeFiles = [
    'routes/web.php',
    'routes/api.php'
];

$moduleRouteFiles = glob('modules/*/routes/*.php');

foreach (array_merge($routeFiles, $moduleRouteFiles) as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $relPath = str_replace('\\', '/', $file);
    
    // Détection des routes
    preg_match_all("/Route::(get|post|put|patch|delete|resource|any)\s*\([^)]+\)/i", $content, $matches);
    $routeCount = count($matches[0]);
    
    // Détection des middleware
    $hasAuth = strpos($content, 'auth') !== false;
    $hasAdmin = strpos($content, 'admin') !== false;
    $hasMiddleware = preg_match("/->middleware\([^)]+\)/", $content) > 0;
    
    $rapport['routes'][] = [
        'fichier' => $relPath,
        'nombre_routes' => $routeCount,
        'middleware_auth' => $hasAuth,
        'middleware_admin' => $hasAdmin,
        'has_middleware' => $hasMiddleware
    ];
    
    echo "✅ {$relPath} : {$routeCount} routes\n";
}

echo "\n";

// 4. AUDIT DES MODULES
echo "4️⃣  AUDIT DES MODULES\n";
echo "---------------------\n";

$modules = glob('modules/*', GLOB_ONLYDIR);

foreach ($modules as $module) {
    $moduleName = basename($module);
    $relPath = str_replace('\\', '/', $module);
    
    $moduleInfo = [
        'nom' => $moduleName,
        'chemin' => $relPath,
        'routes' => [],
        'controllers' => [],
        'models' => [],
        'views' => [],
        'layouts' => []
    ];
    
    // Routes
    $routes = glob($module . '/routes/*.php');
    $moduleInfo['routes'] = array_map(function($r) { return basename($r); }, $routes);
    
    // Controllers
    $controllers = glob($module . '/Http/Controllers/*.php');
    $moduleInfo['controllers'] = array_map(function($c) { return basename($c); }, $controllers);
    
    // Models
    $models = glob($module . '/Models/*.php');
    $moduleInfo['models'] = array_map(function($m) { return basename($m); }, $models);
    
    // Views
    $views = scanViews($module . '/Resources/views', $module);
    $moduleInfo['views'] = count($views);
    
    // Layouts
    $layouts = glob($module . '/Resources/views/layouts/*.blade.php');
    $moduleInfo['layouts'] = array_map(function($l) { return basename($l); }, $layouts);
    
    $rapport['modules'][] = $moduleInfo;
    
    echo "✅ {$moduleName} : " . count($controllers) . " controllers, " . count($models) . " models, " . $moduleInfo['views'] . " vues\n";
}

echo "\n";

// 5. AUDIT DU DESIGN SYSTEM
echo "5️⃣  AUDIT DU DESIGN SYSTEM\n";
echo "--------------------------\n";

$racineCss = 'public/css/racine-variables.css';
if (file_exists($racineCss)) {
    echo "✅ racine-variables.css existe\n";
    
    $cssContent = file_get_contents($racineCss);
    
    // Vérifier les variables principales
    $variables = ['--racine-primary', '--racine-secondary', '--racine-accent', '--racine-dark', '--racine-light'];
    $found = 0;
    foreach ($variables as $var) {
        if (strpos($cssContent, $var) !== false) {
            $found++;
        }
    }
    
    echo "Variables RACINE trouvées : {$found}/" . count($variables) . "\n";
    
    $rapport['design']['racine_css'] = [
        'existe' => true,
        'variables' => $found . '/' . count($variables)
    ];
} else {
    echo "❌ racine-variables.css introuvable\n";
    $rapport['erreurs'][] = "❌ Fichier racine-variables.css introuvable";
    $rapport['design']['racine_css'] = ['existe' => false];
}

// Vérifier l'utilisation de racine-variables.css dans les layouts
$usageRacineCss = 0;
foreach ($rapport['layouts'] as $layout) {
    if ($layout['racine_css']) {
        $usageRacineCss++;
    }
}

echo "Layouts utilisant racine-variables.css : {$usageRacineCss}/" . count($rapport['layouts']) . "\n";

$rapport['design']['usage_racine_css'] = $usageRacineCss;

echo "\n";

// RÉSUMÉ FINAL
echo "==================================================\n";
echo "📊 RÉSUMÉ FINAL\n";
echo "==================================================\n";
echo "Layouts analysés : " . count($rapport['layouts']) . "\n";
echo "Vues analysées : " . $rapport['vues']['total'] . "\n";
echo "Fichiers de routes analysés : " . count($rapport['routes']) . "\n";
echo "Modules analysés : " . count($rapport['modules']) . "\n";
echo "Erreurs trouvées : " . count($rapport['erreurs']) . "\n";
echo "Avertissements : " . count($rapport['avertissements']) . "\n";
echo "\n";

// Sauvegarder le rapport
file_put_contents('audit-complet-resultat.json', json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "✅ Rapport sauvegardé dans audit-complet-resultat.json\n";

