<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'admin@racine.cm';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User {$email} not found.\n";
    exit(1);
}

echo "User found: {$user->name} (ID: {$user->id})\n";
echo "Role ID: {$user->role_id}\n";
echo "Role Attribute: " . ($user->role ?? 'null') . "\n";

$user->load('roleRelation');
if ($user->roleRelation) {
    echo "Role Relation: {$user->roleRelation->name} (Slug: {$user->roleRelation->slug})\n";
} else {
    echo "Role Relation: NULL\n";
}

echo "getRoleSlug(): " . $user->getRoleSlug() . "\n";
echo "isAdmin(): " . ($user->isAdmin() ? 'YES' : 'NO') . "\n";

echo "Checking Gate 'access-erp'...\n";
try {
    $allowed = Gate::forUser($user)->allows('access-erp');
    echo "Gate 'access-erp': " . ($allowed ? 'ALLOWED' : 'DENIED') . "\n";
} catch (\Exception $e) {
    echo "Error checking gate: " . $e->getMessage() . "\n";
}

echo "Checking Gate 'access-admin'...\n";
try {
    $allowed = Gate::forUser($user)->allows('access-admin');
    echo "Gate 'access-admin': " . ($allowed ? 'ALLOWED' : 'DENIED') . "\n";
} catch (\Exception $e) {
    echo "Error checking gate: " . $e->getMessage() . "\n";
}
