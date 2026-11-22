<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
     * Show form to create a new subscription plan.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store newly created subscription plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['nullable', 'string', 'max:30', 'unique:plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'customer_limit' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $features = [
            'pdf_statements' => (bool) $request->input('features.pdf_statements', false),
            'whatsapp_reminders' => (bool) $request->input('features.whatsapp_reminders', false),
            'csv_exports' => (bool) $request->input('features.csv_exports', false),
            'advanced_reports' => (bool) $request->input('features.advanced_reports', false),
            'priority_support' => (bool) $request->input('features.priority_support', false),
        ];

        $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'customer_limit' => $validated['customer_limit'],
            'description' => $validated['description'] ?? null,
            'features' => $features,
            'is_active' => $request->has('is_active'),
        ]);

        AdminActivityLog::log(
            action: 'plan_created',
            description: "New subscription plan '{$plan->name}' created at ₹{$plan->price}/month.",
            targetType: 'Plan',
            targetId: $plan->id
        );

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' has been created successfully.");
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

        AdminActivityLog::log(
            action: 'plan_updated',
            description: "Subscription plan '{$plan->name}' updated: price ₹{$plan->price}, customer limit {$plan->customer_limit}.",
            targetType: 'Plan',
            targetId: $plan->id
        );

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' has been updated successfully.");
    }

    /**
     * Safe plan deletion (only allowed when no subscriptions exist for the plan).
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot delete plan '{$plan->name}' because shops have active or historical subscriptions associated with it. Please disable the plan instead.");
        }

        $planName = $plan->name;
        $planId = $plan->id;
        $plan->delete();

        AdminActivityLog::log(
            action: 'plan_deleted',
            description: "Subscription plan '{$planName}' (ID: {$planId}) deleted.",
            targetType: 'Plan',
            targetId: $planId
        );

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$planName}' deleted successfully.");
    }
}
