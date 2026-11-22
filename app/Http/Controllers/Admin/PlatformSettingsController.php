<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    /**
     * Display platform-wide configuration settings.
     */
    public function index(): View
    {
        $settings = [
            'site_name' => PlatformSetting::get('site_name', 'HisabKitab SaaS'),
            'contact_email' => PlatformSetting::get('contact_email', 'support@hisabkitab.in'),
            'contact_phone' => PlatformSetting::get('contact_phone', '+91 98765 43210'),
            'currency' => PlatformSetting::get('currency', 'INR'),
            'timezone' => PlatformSetting::get('timezone', 'Asia/Kolkata'),
            'trial_days' => PlatformSetting::get('trial_days', '30'),
            'support_hours' => PlatformSetting::get('support_hours', 'Mon - Sat (9:00 AM - 8:00 PM IST)'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update platform settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['required', 'email', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'support_hours' => ['nullable', 'string', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, (string) $value, 'general');
        }

        AdminActivityLog::log(
            action: 'platform_settings_updated',
            description: "Platform global configuration settings updated by Super Admin.",
            targetType: 'PlatformSetting'
        );

        return redirect()->route('admin.settings.index')
            ->with('success', 'Platform settings saved successfully.');
    }
}

