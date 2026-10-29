<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function apropos(): Response
    {
        return Inertia::render('Apropos', ['contact' => config('contact')]);
    }
}
