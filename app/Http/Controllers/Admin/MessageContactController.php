<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageContactController extends Controller
{
    public function index(Request $request): Response
    {
        $filtre = $request->string('filtre')->toString() === 'tous' ? 'tous' : 'non_lus';

        $messages = MessageContact::query()
            ->when($filtre === 'non_lus', fn ($q) => $q->whereNull('lu_le'))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (MessageContact $m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'email' => $m->email,
                'telephone' => $m->telephone,
                'sujet' => $m->sujet,
                'message' => $m->message,
                'lu' => $m->lu_le !== null,
                'cree_le' => $m->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'filtre' => $filtre,
            'non_lus' => MessageContact::whereNull('lu_le')->count(),
        ]);
    }

    public function basculerLu(MessageContact $messageContact): RedirectResponse
    {
        $messageContact->forceFill(['lu_le' => $messageContact->lu_le ? null : now()])->save();

        return back();
    }

    public function destroy(MessageContact $messageContact): RedirectResponse
    {
        $messageContact->delete();

        return back();
    }
}
