<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BloodType;
use App\Models\Citizenship;
use App\Models\Classroom;
use App\Models\DocumentType;
use App\Models\EducationLevel;
use App\Models\Gender;
use App\Models\IncomeCategory;
use App\Models\Major;
use App\Models\Occupation;
use App\Models\RelationshipType;
use App\Models\Religion;
use App\Models\School;
use App\Models\SocialPlatform;
use App\Models\StudentStatus;
use App\Models\User;
use Database\Seeders\StudentSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat data Sekolah pertama (ID 1)
        $school = School::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'SMK Negeri 1',
            'npsn' => '10800001',
        ]);

        // 2. Buat atau perbarui user admin (Mencegah error duplicate entry)
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Data Default Jenis Kelamin (Genders)
        $genders = [
            ['code' => 'L', 'name' => 'Laki-laki'],
            ['code' => 'P', 'name' => 'Perempuan'],
        ];

        foreach ($genders as $gender) {
            Gender::firstOrCreate(
                ['code' => $gender['code']],
                ['name' => $gender['name']]
            );
        }

        // 4. Data Default Agama (Religions)
        $religions = [
            'Islam',
            'Kristen Protestan',
            'Katolik',
            'Hindu',
            'Buddha',
            'Khonghucu',
        ];

        foreach ($religions as $religion) {
            Religion::firstOrCreate(['name' => $religion]);
        }

        // 5. Data Default Golongan Darah (BloodTypes)
        $bloodTypes = ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        foreach ($bloodTypes as $bloodType) {
            BloodType::firstOrCreate(['name' => $bloodType]);
        }

        // 6. Data Default Tahun Akademik (AcademicYears)
        AcademicYear::firstOrCreate([
            'name' => '2025/2026',
        ], [
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        AcademicYear::firstOrCreate([
            'name' => '2026/2027',
        ], [
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => false,
        ]);

        // 7. Data Default Jenis Dokumen (DocumentTypes)
        $documentTypes = [
            'Kartu Keluarga',
            'Akta Kelahiran',
            'Ijazah',
            'Raport',
            'Surat Keterangan Sehat',
            'KTP Orang Tua',
            'SKHUN',
        ];
        foreach ($documentTypes as $type) {
            DocumentType::firstOrCreate(['name' => $type]);
        }

        // 8. Data Default Status Siswa (StudentStatus)
        $studentStatuses = [
            'Aktif',
            'Tidak Aktif',
            'Lulus',
            'Mutasi',
            'DO',
        ];
        foreach ($studentStatuses as $status) {
            StudentStatus::firstOrCreate(['name' => $status]);
        }

        // 9. Data Default Kewarganegaraan
        $citizenships = [
            'WNI',
            'WNA',
        ];
        foreach ($citizenships as $citizenship) {
            Citizenship::firstOrCreate(
                ['code' => $citizenship],
                ['name' => $citizenship]
            );
        }

        // 10. Data Default Pekerjaan (Occupation)
        $occupations = [
            'PNS',
            'Pegawai Swasta',
            'Wiraswasta',
            'Petani',
            'Nelayan',
            'Tidak Bekerja',
            'Guru',
            'Dokter',
            'TNI/POLRI',
            'Lainnya',
        ];
        foreach ($occupations as $occupation) {
            Occupation::firstOrCreate(['name' => $occupation]);
        }

        // 11. Data Default Kategori Penghasilan (IncomeCategory)
        $incomeCategories = [
            'Kurang dari 1 juta',
            '1 - 2 juta',
            '2 - 3 juta',
            '3 - 5 juta',
            '5 - 10 juta',
            'Lebih dari 10 juta',
        ];
        foreach ($incomeCategories as $incomeCategory) {
            IncomeCategory::firstOrCreate(['name' => $incomeCategory]);
        }

        // 12. Data Default Hubungan Keluarga (RelationshipType)
        $relationshipTypes = [
            'Orang Tua',
            'Wali',
            'Saudara',
            'Kakak',
            'Adik',
            'Paman',
            'Bibi',
        ];
        foreach ($relationshipTypes as $relationshipType) {
            RelationshipType::firstOrCreate(['name' => $relationshipType]);
        }

        // 13. Data Default Jenjang Pendidikan (EducationLevel)
        $educationLevels = [
            ['name' => 'SD', 'sort_order' => 10],
            ['name' => 'SMP', 'sort_order' => 20],
            ['name' => 'SMA/SMK', 'sort_order' => 30],
            ['name' => 'D1/D2/D3', 'sort_order' => 40],
            ['name' => 'S1', 'sort_order' => 50],
            ['name' => 'S2', 'sort_order' => 60],
            ['name' => 'S3', 'sort_order' => 70],
        ];
        foreach ($educationLevels as $level) {
            EducationLevel::firstOrCreate(
                ['name' => $level['name']],
                ['sort_order' => $level['sort_order']]
            );
        }

        // 14. Data Default Platform Media Sosial (SocialPlatform)
        $socialPlatforms = [
            ['name' => 'Instagram', 'base_url' => 'https://instagram.com'],
            ['name' => 'Facebook', 'base_url' => 'https://facebook.com'],
            ['name' => 'Twitter', 'base_url' => 'https://twitter.com'],
            ['name' => 'TikTok', 'base_url' => 'https://tiktok.com'],
            ['name' => 'YouTube', 'base_url' => 'https://youtube.com'],
        ];
        foreach ($socialPlatforms as $platform) {
            SocialPlatform::firstOrCreate(
                ['name' => $platform['name']],
                ['base_url' => $platform['base_url']]
            );
        }

        // 15. Data Default Jurusan (Major)
        $majors = [
            ['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan'],
            ['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak'],
            ['code' => 'MM', 'name' => 'Multimedia'],
            ['code' => 'AKL', 'name' => 'Akuntansi dan Keuangan Lembaga'],
            ['code' => 'OTKP', 'name' => 'Otomatisasi dan Tata Kelola Perkantoran'],
        ];

        $majorMap = [];
        foreach ($majors as $major) {
            $majorModel = Major::firstOrCreate(
                ['code' => $major['code'], 'school_id' => $school->id],
                ['name' => $major['name']]
            );

            $majorMap[$major['code']] = $majorModel;
        }

        // 16. Data Default Kelas (Classroom)
        $classrooms = [
            ['name' => 'X-TKJ-1', 'major_code' => 'TKJ', 'level' => 'X', 'rombel' => 1],
            ['name' => 'X-TKJ-2', 'major_code' => 'TKJ', 'level' => 'X', 'rombel' => 2],
            ['name' => 'XI-RPL-1', 'major_code' => 'RPL', 'level' => 'XI', 'rombel' => 1],
            ['name' => 'XI-MM-1', 'major_code' => 'MM', 'level' => 'XI', 'rombel' => 1],
            ['name' => 'XII-AKL-1', 'major_code' => 'AKL', 'level' => 'XII', 'rombel' => 1],
        ];
        foreach ($classrooms as $classroom) {
            $major = $majorMap[$classroom['major_code']] ?? null;
            if (! $major) {
                continue;
            }

            Classroom::firstOrCreate(
                ['major_id' => $major->id, 'name' => $classroom['name']],
                ['level' => $classroom['level'], 'rombel' => $classroom['rombel']]
            );
        }

        // 17. Data Dummy Siswa
        $this->call(StudentSeeder::class);
    }
}