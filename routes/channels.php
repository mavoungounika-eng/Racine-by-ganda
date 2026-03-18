<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// ── Présence canal par produit ────────────────────────────────────────────
// Accessible : admins, super_admin, staff, pos_operator
Broadcast::channel('stock.{productId}', function ($user) {
    return in_array($user->role, [
        'admin', 'super_admin', 'staff',
    ]);
});

// ── Canal admin stock ─────────────────────────────────────────────────────
// Alertes stock bas + anomalies (admins uniquement)
Broadcast::channel('admin.stock', function ($user) {
    return in_array($user->role, ['admin', 'super_admin']);
});

// ── Canal multi-caisses (toutes caisses connectées) ───────────────────────
// Reçoit toutes les mises à jour stock en temps réel
Broadcast::channel('pos.broadcast', function ($user) {
    return in_array($user->role, [
        'admin', 'super_admin', 'staff',
    ]);
});

// ── Canal device spécifique ───────────────────────────────────────────────
// Pour cibler une caisse précise (ex: réconciliation)
Broadcast::channel('pos.device.{deviceId}', function ($user, $deviceId) {
    return $user->posDevice?->device_id === $deviceId
        || in_array($user->role, ['admin', 'super_admin']);
});

// ── Canal utilisateur (framework default) ────────────────────────────────
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
