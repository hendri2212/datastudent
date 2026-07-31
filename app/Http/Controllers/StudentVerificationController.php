<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentVerificationController extends Controller
{
    /**
     * Memverifikasi data siswa oleh user yang sedang login
     */
    public function verify(Request $request, Student $student): RedirectResponse
    {
        if ($student->verified_at !== null) {
            // Mengirim pesan error via Inertia/Flash Session jika sudah diverifikasi
            return redirect()->back()->withErrors([
                'message' => 'Siswa ini sudah diverifikasi sebelumnya.'
            ]);
        }

        $student->update([
            'verified_at' => now(),
            'verified_by' => Auth::id(), // Mengisi kolom verified_by dengan ID User login
        ]);

        // Mengembalikan response redirect back khas Inertia
        return redirect()->back()->with('success', 'Data siswa berhasil diverifikasi.');
    }

    /**
     * Membatalkan verifikasi siswa
     */
    public function unverify(Student $student): RedirectResponse
    {
        $student->update([
            'verified_at' => null,
            'verified_by' => null,
        ]);

        // Mengembalikan response redirect back khas Inertia
        return redirect()->back()->with('success', 'Status verifikasi siswa telah dibatalkan.');
    }
}