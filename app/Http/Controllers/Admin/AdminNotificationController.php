<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(): View
    {
        // Pour l'instant, retourner une page simple
        // À développer plus tard avec un vrai système de notifications
        $notifications = [];

        return view('admin.notifications.index', compact('notifications'));
    }
}
