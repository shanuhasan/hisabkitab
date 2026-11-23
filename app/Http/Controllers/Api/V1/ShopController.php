<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateShopProfileRequest;
use App\Http\Resources\ShopResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    /**
     * GET /api/v1/shop — the shop's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => new ShopResource($request->user()->tenant)]);
    }

    /**
     * PUT /api/v1/shop — update the shop's profile (shop owner only).
     * Send as multipart/form-data with _method=PUT when uploading a logo.
     */
    public function update(UpdateShopProfileRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->logo_path = $request->file('logo')->store('logos', 'public');
        }

        $tenant->fill([
            'name' => $validated['name'],
            'owner_name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'gstin' => $validated['gstin'] ?? null,
            'currency' => $validated['currency'] ?? 'INR',
        ])->save();

        // Keep owner user details aligned with shop identity (same as the web app)
        $user = $request->user();
        $user->name = $validated['owner_name'];
        $user->phone = $validated['phone'];
        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }
        $user->save();

        return response()->json([
            'message' => 'Shop profile updated.',
            'data' => new ShopResource($tenant->fresh()),
        ]);
    }
}
