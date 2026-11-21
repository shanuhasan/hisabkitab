<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    /**
     * Display listing of subscription plans for Super Admin.
     */
    public function index(): View
    {
        $plans = Plan::withCount('subscriptions')->orderBy('price', 'asc')->get();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update plan prices, limits, and features.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'customer_limit' => ['required', 'integer'], // -1 = Unlimited
            'description' => ['nullable', 'string', 'max:500'],
            'features.pdf_statements' => ['nullable', 'boolean'],
            'features.whatsapp_reminders' => ['nullable', 'boolean'],
            'features.csv_exports' => ['nullable', 'boolean'],
            'features.advanced_reports' => ['nullable', 'boolean'],
            'features.priority_support' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $features = [
            'pdf_statements' => (bool) $request->input('features.pdf_statements', false),
            'whatsapp_reminders' => (bool) $request->input('features.whatsapp_reminders', false),
            'csv_exports' => (bool) $request->input('features.csv_exports', false),
            'advanced_reports' => (bool) $request->input('features.advanced_reports', false),
            'priority_support' => (bool) $request->input('features.priority_support', false),
        ];

        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'customer_limit' => $validated['customer_limit'],
            'description' => $validated['description'] ?? null,
            'features' => $features,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' has been updated successfully.");
    }
}

