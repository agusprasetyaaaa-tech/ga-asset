<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'profile_photo_url' => $request->user()->profile_photo_url,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'importErrors' => $request->session()->get('importErrors'),
            ],
            'notifications' => $request->user() ? [
                'damaged_count' => \App\Models\Asset::where('status', 'damaged')->count(),
                'maintenance_count' => \App\Models\Asset::where('status', 'maintenance')->count(),
                'expiring_warranty' => \App\Models\Asset::where(function($query) {
                        $query->whereNotNull('warranty_date')
                            ->where('warranty_date', '>', now())
                            ->where('warranty_date', '<=', now()->addDays(30));
                    })->orWhere(function($query) {
                        $query->whereNotNull('license_expiration_date')
                            ->where('license_expiration_date', '>', now())
                            ->where('license_expiration_date', '<=', now()->addDays(30));
                    })->count(),
            ] : null,
        ];
    }
}
