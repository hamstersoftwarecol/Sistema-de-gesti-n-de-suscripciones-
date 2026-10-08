<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $name = setting('company_name', config('app.name'));

        return response()->json([
            'name' => $name,
            'short_name' => mb_substr($name, 0, 12),
            'description' => __('Subscription management ERP'),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#111827',
            'theme_color' => '#4f46e5',
            'lang' => app()->getLocale(),
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => asset('icons/icon-512-maskable.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => __('Dashboard'), 'url' => '/dashboard'],
                ['name' => __('Subscriptions'), 'url' => '/subscriptions'],
                ['name' => __('Invoices'), 'url' => '/invoices'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function offline(): View
    {
        return view('offline');
    }
}
