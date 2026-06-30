<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class FrontendContactController extends Controller
{
    public function show(): View
    {
        $cmsPage = null;

        return view('frontend.contact', compact('cmsPage'));
    }

    public function submit(ContactFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            Mail::to(config('app.company.email', 'contact@racine-ganda.com'))
                ->send(new ContactFormMail($validated));
            \Log::info('Email contact envoyé avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur email contact: ' . $e->getMessage());
        }

        return redirect()->route('frontend.contact')
            ->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les 24 heures.');
    }
}
