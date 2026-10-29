<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\MessageContact;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact', ['contact' => config('contact')]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // Robot détecté : on fait semblant que tout s'est bien passé, sans rien enregistrer
        if (filled($request->input('site_web'))) {
            return back()->with('succes', 'Merci, votre message a bien été envoyé.');
        }

        MessageContact::create($request->safe()->only(['nom', 'email', 'telephone', 'sujet', 'message']));

        return back()->with('succes', 'Merci, votre message a bien été envoyé. Nous vous répondrons dès que possible.');
    }
}
