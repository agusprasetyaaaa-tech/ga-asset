<?php

namespace App\Http\Controllers;

use App\Models\AssetCodeSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssetCodeSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin'),
        ];
    }
    public function index()
    {
        $setting = AssetCodeSetting::getSetting();

        return Inertia::render('Settings/AssetCode', [
            'setting' => $setting,
            'preview' => $setting->getPreview($setting->next_number),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Regex diupdate untuk memperbolehkan slash (/) dan titik (.)
            'prefix' => 'required|string|max:20|regex:/^[A-Za-z0-9\-_\/\.]+$/',
            'separator' => 'required|string|max:3',
            'date_format' => 'required|in:none,Y,Ym,Ymd,ym,ymd',
            'date_position' => 'required|in:middle,end',
            // Digit length diturunkan minimum menjadi 1
            'digit_length' => 'required|integer|min:1|max:8',
            'reset_period' => 'required|in:never,yearly,monthly',
        ]);

        $setting = AssetCodeSetting::getSetting();
        $setting->update($validated);

        return redirect()->back()->with('success', 'Template kode asset berhasil diperbarui.');
    }

    public function resetCounter()
    {
        $setting = AssetCodeSetting::getSetting();
        $setting->update([
            'next_number' => 1,
            'last_reset_ref' => $setting->getCurrentResetRef(),
        ]);

        return redirect()->back()->with('success', 'Nomor urut berhasil direset ke 1.');
    }
}
