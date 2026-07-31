<?php

namespace App\Http\Controllers;

use App\Models\SocialPlatform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialPlatformController extends Controller
{
    /**
     * Menampilkan daftar platform media sosial
     */
    public function index(): Response
    {
        return Inertia::render('master/social-platforms/Index', [
            'socialPlatforms' => SocialPlatform::all(),
        ]);
    }

    /**
     * Menyimpan platform media sosial baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255', 'unique:social_platforms,name'],
            'icon'     => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'string', 'max:255'],
        ]);

        SocialPlatform::create($validated);

        return back()->with('success', 'Platform media sosial berhasil ditambahkan.');
    }

    /**
     * Memperbarui platform media sosial
     */
    public function update(Request $request, SocialPlatform $socialPlatform): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255', 'unique:social_platforms,name,' . $socialPlatform->id],
            'icon'     => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'string', 'max:255'],
        ]);

        $socialPlatform->update($validated);

        return back()->with('success', 'Platform media sosial berhasil diperbarui.');
    }

    /**
     * Menghapus platform media sosial
     */
    public function destroy(SocialPlatform $socialPlatform): RedirectResponse
    {
        $socialPlatform->delete();

        return back()->with('success', 'Platform media sosial berhasil dihapus.');
    }
}