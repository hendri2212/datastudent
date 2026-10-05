<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StudentAccountController extends Controller
{
    /**
     * Menampilkan daftar akun siswa
     */
    public function index(Request $request): Response
    {
        $query = Student::with([
            'user',
            'currentEnrollment.classroom.major', // Relasi yang benar sesuai model Student Anda
        ])
        ->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($request->status === 'has_account', function ($q) {
            $q->whereNotNull('user_id');
        })
        ->when($request->status === 'no_account', function ($q) {
            $q->whereNull('user_id');
        });

        $students = $query->paginate(15)->withQueryString();

        return Inertia::render('studentsaccount/Index', [
            'students' => $students,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Membuat akun individual untuk 1 siswa
     */
    public function createAccount(Request $request, Student $student): RedirectResponse
    {
        if ($student->user_id) {
            return redirect()->back()->with('error', 'Siswa ini sudah memiliki akun login.');
        }

        $email = !empty(trim($student->email))
            ? trim($student->email)
            : ($request->input('email') 
                ?: ($student->nisn ?: $student->nis ?: $student->id) . '@siswa.belajar.id');

        $password = $request->input('password', '12345678');

       $request->validate([
            'email' => 'nullable|email|unique:users,email',
        ]);

        DB::transaction(function () use ($student, $email, $password) {
            $user = User::create([
                'name'     => $student->full_name,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => 'student',
            ]);

            $student->update([
                'user_id' => $user->id,
                'email'   => $email,
            ]);
        });

        return redirect()->back()->with('success', "Akun berhasil dibuat dengan email: {$email} dan password: {$password}");
    }

    /**
     * Membuat akun massal untuk semua siswa yang belum punya akun
     */
    public function bulkCreateAccounts(Request $request): RedirectResponse
    {
        $password = $request->input('password', '12345678');
        $studentsWithoutAccount = Student::whereNull('user_id')->get();

        if ($studentsWithoutAccount->isEmpty()) {
            return redirect()->back()->with('info', 'Semua siswa sudah memiliki akun.');
        }

        $createdCount = 0;

        DB::transaction(function () use ($studentsWithoutAccount, $password, &$createdCount) {
            foreach ($studentsWithoutAccount as $student) {
                // PERBAIKAN LOGIKA EMAIL: Utamakan email asli siswa
                $email = !empty(trim($student->email))
                    ? trim($student->email)
                    : ($student->nisn ?: $student->nis ?: $student->id) . '@siswa.belajar.id';

                // Jika email siswa sudah terpakai di akun user lain, tambahkan fallback unik
                if (User::where('email', $email)->exists()) {
                    $email = "siswa_{$student->id}_" . time() . "@siswa.belajar.id";
                }

                $user = User::create([
                    'name'     => $student->full_name,
                    'email'    => $email,
                    'password' => Hash::make($password),
                    'role'     => 'student',
                ]);

                $student->update([
                    'user_id' => $user->id,
                    'email'   => $email,
                ]);

                $createdCount++;
            }
        });

        return redirect()->back()->with('success', "Berhasil membuat {$createdCount} akun siswa.");
    }
}
