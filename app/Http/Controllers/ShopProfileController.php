<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShopProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShopProfileController extends Controller
{
    /**
     * Show shop profile settings.
     * Guaranteed IDOR-safe: Retrieves strictly the active authenticated tenant.
     */
    public function edit(): View
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            abort(403, 'Unauthorized access to shop profile.');
        }

        return view('shop.profile', compact('tenant'));
    }

    /**
     * Update shop profile settings.
     * Guaranteed IDOR-safe: Updates strictly the authenticated user's tenant.
     */
    public function update(UpdateShopProfileRequest $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            abort(403, 'Unauthorized access to shop profile.');
        }

        $validated = $request->validated();

        // Handle shop logo upload
        if ($request->hasFile('logo')) {
            if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $logoPath = $request->file('logo')->store('logos', 'public');
            $tenant->logo_path = $logoPath;
        }

        $tenant->name = $validated['name'];
        $tenant->owner_name = $validated['owner_name'];
        $tenant->phone = $validated['phone'];
        $tenant->email = $validated['email'] ?? null;
        $tenant->address = $validated['address'] ?? null;
        $tenant->gstin = $validated['gstin'] ?? null;
        $tenant->currency = $validated['currency'] ?? 'INR';
        $tenant->save();

        // Keep owner user details aligned with shop identity
        $user = auth()->user();
        $user->name = $validated['owner_name'];
        $user->phone = $validated['phone'];
        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }
        $user->save();

        return redirect()->route('shop.profile')->with('success', 'Shop profile settings updated successfully.');
    }
}

