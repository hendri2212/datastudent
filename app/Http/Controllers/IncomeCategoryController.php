<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $incomeCategories = IncomeCategory::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();

        return Inertia::render('master/income-categories/Index', [
            'incomeCategories' => $incomeCategories,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:income_categories,name'],
            'minimum' => ['nullable', 'numeric', 'min:0'],
            'maximum' => ['nullable', 'numeric', 'min:0'],
        ]);

        IncomeCategory::create($validated);

        return back()->with('success', 'Kategori penghasilan berhasil ditambahkan.');
    }

    public function update(Request $request, IncomeCategory $incomeCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:income_categories,name,' . $incomeCategory->id],
            'minimum' => ['nullable', 'numeric', 'min:0'],
            'maximum' => ['nullable', 'numeric', 'min:0'],
        ]);

        $incomeCategory->update($validated);

        return back()->with('success', 'Kategori penghasilan berhasil diperbarui.');
    }

    public function destroy(IncomeCategory $incomeCategory): RedirectResponse
    {
        $incomeCategory->delete();

        return back()->with('success', 'Kategori penghasilan berhasil dihapus.');
    }
}