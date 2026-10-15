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

        $email = $this->resolveStudentEmail($student, $request->input('email'));

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
        // Every account in this bulk operation gets the same initial password,
        // so compute its bcrypt hash once instead of hashing once per student.
        $hashedPassword = Hash::make($password);

        DB::transaction(function () use ($studentsWithoutAccount, $hashedPassword, &$createdCount) {
            foreach ($studentsWithoutAccount as $student) {
                $email = $this->resolveStudentEmail($student);

                // Keep generated emails tied to the student's NISN when a collision occurs.
                if (User::where('email', $email)->exists()) {
                    [$local, $domain] = explode('@', $email, 2);
                    $email = "{$local}+{$student->id}@{$domain}";
                }

                $user = User::create([
                    'name'     => $student->full_name,
                    'email'    => $email,
                    'password' => $hashedPassword,
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

    /**
     * Use an existing valid email when present; imported placeholders such as "-"
     * mean no email, so generate the login email from the student's NISN.
     */
    private function resolveStudentEmail(Student $student, ?string $requestedEmail = null): string
    {
        $email = trim((string) ($requestedEmail ?: $student->email));

        if ($email !== '' && !in_array(mb_strtolower($email), ['-', '—', 'n/a', 'null'], true)
            && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $identifier = trim((string) ($student->nisn ?: $student->nis ?: $student->id));

        return $identifier . '@erapor.id';
    }
}
