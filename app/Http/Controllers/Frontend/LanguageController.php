<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (! in_array($locale, ['en', 'id'])) {
            $locale = 'id';
        }

        Session::put('locale', $locale);
        app()->setLocale($locale);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'locale' => $locale]);
        }

        return back();
    }
}
