<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BloodType;
use App\Models\Classroom;
use App\Models\Citizenship;
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
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentDocument;
use App\Models\StudentEducationHistory;
use App\Models\StudentFamily;
use App\Models\StudentHealth;
use App\Models\StudentSocial;
use App\Models\StudentStatus;
use App\Models\StudentViolation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) {
            return;
        }

        $academicYearId = AcademicYear::where('name', '2025/2026')->value('id');
        $studentStatusId = StudentStatus::where('name', 'Aktif')->value('id');
        $citizenshipId = Citizenship::where('code', 'WNI')->value('id');

        $genders = Gender::pluck('id', 'code')->all();
        $classrooms = Classroom::pluck('id', 'name')->all();
        $majors = Major::pluck('id', 'code')->all();
        $educationLevels = EducationLevel::pluck('id', 'name')->all();
        $socialPlatforms = SocialPlatform::pluck('id', 'name')->all();
        $relationshipTypes = RelationshipType::pluck('id', 'name')->all();
        $occupations = Occupation::pluck('id', 'name')->all();
        $incomeCategories = IncomeCategory::pluck('id', 'name')->all();
        $documentTypes = DocumentType::pluck('id', 'name')->all();
        $bloodTypes = BloodType::pluck('id', 'name')->all();

        $students = [
            [
                'full_name' => 'Aisyah Putri',
                'nis' => '2025000101',
                'nisn' => '1000000001',
                'gender' => 'P',
                'major' => 'RPL',
                'classroom' => 'XI-RPL-1',
                'birth_place' => 'Bandung',
                'birth_date' => '2008-08-15',
                'phone' => '081234567891',
                'email' => 'aisyah.putri@example.com',
                'notes' => 'Siswa teladan, aktif di ekstrakurikuler robotika.',
                'family' => [
                    'father_name' => 'Iwan Putra',
                    'father_phone' => '081234510001',
                    'father_occupation' => 'PNS',
                    'father_income_category' => '5 - 10 juta',
                    'mother_name' => 'Dewi Astuti',
                    'mother_phone' => '081234510002',
                    'mother_occupation' => 'Guru',
                    'mother_income_category' => '3 - 5 juta',
                    'guardian_name' => null,
                    'guardian_phone' => null,
                    'guardian_occupation' => null,
                    'guardian_income_category' => null,
                    'emergency_contact_name' => 'Dewi Astuti',
                    'emergency_contact_phone' => '081234510002',
                    'relationship_type' => 'Orang Tua',
                    'notes' => 'Ayah bertugas di Dinas Pendidikan.',
                ],
                'health' => [
                    'blood_type' => 'A',
                    'height' => 158.5,
                    'weight' => 49.3,
                    'allergies' => 'Alergi kacang',
                    'medical_history' => 'Asma ringan',
                    'disabilities' => null,
                    'medications' => 'Ventolin saat serangan',
                    'hospital' => 'RS Hasan Sadikin',
                    'doctor' => 'Dr. Taufik',
                    'notes' => 'Perlu inhaler saat olahraga berat.',
                ],
                'education_histories' => [
                    [
                        'education_level' => 'SMP',
                        'school_name' => 'SMP Negeri 2 Bandung',
                        'npsn' => '20304050',
                        'address' => 'Jl. Kebon Jati 10',
                        'entry_year' => 2021,
                        'graduation_year' => 2024,
                        'final_score' => 88.5,
                        'is_graduated' => true,
                        'notes' => 'Lulus dengan prestasi nilai UN terbaik.',
                    ],
                ],
                'socials' => [
                    ['platform' => 'Instagram', 'username' => '@aisyahputri', 'url' => 'https://instagram.com/aisyahputri', 'is_public' => true, 'is_primary' => true],
                    ['platform' => 'TikTok', 'username' => '@aisyahputri', 'url' => 'https://tiktok.com/@aisyahputri', 'is_public' => false, 'is_primary' => false],
                ],
                'achievements' => [
                    ['title' => 'Juara 1 Lomba Coding', 'organizer' => 'OSIS', 'level' => 'Sekolah', 'category' => 'Akademik', 'rank' => 1, 'achievement_date' => '2024-11-20', 'certificate' => 'Sertifikat Coding', 'description' => 'Menang lomba pengembangan aplikasi sekolah.'],
                ],
                'violations' => [
                    ['title' => 'Terlambat 2x', 'point' => 5, 'violation_date' => '2025-01-15', 'description' => 'Datang terlambat ke sekolah dua kali dalam satu bulan.'],
                ],
                'documents' => [
                    ['type' => 'Kartu Keluarga', 'original_name' => 'KK Aisyah Putri', 'mime_type' => 'application/pdf', 'file_size' => 152000, 'extension' => 'pdf', 'notes' => 'Dokumen keluarga lengkap.'],
                    ['type' => 'Ijazah', 'original_name' => 'Ijazah SMP Aisyah', 'mime_type' => 'application/pdf', 'file_size' => 262400, 'extension' => 'pdf', 'notes' => 'Ijazah SMP yang diunggah.'],
                ],
            ],
            [
                'full_name' => 'Budi Prasetya',
                'nis' => '2025000102',
                'nisn' => '1000000002',
                'gender' => 'L',
                'major' => 'TKJ',
                'classroom' => 'X-TKJ-1',
                'birth_place' => 'Jakarta',
                'birth_date' => '2008-05-10',
                'phone' => '081234567892',
                'email' => 'budi.prasetya@example.com',
                'notes' => 'Aktif di ekstrakurikuler Basket.',
                'family' => [
                    'father_name' => 'Saputra',
                    'father_phone' => '081234520001',
                    'father_occupation' => 'Wiraswasta',
                    'father_income_category' => '5 - 10 juta',
                    'mother_name' => 'Ratna Sari',
                    'mother_phone' => '081234520002',
                    'mother_occupation' => 'Tidak Bekerja',
                    'mother_income_category' => '2 - 3 juta',
                    'guardian_name' => null,
                    'guardian_phone' => null,
                    'guardian_occupation' => null,
                    'guardian_income_category' => null,
                    'emergency_contact_name' => 'Saputra',
                    'emergency_contact_phone' => '081234520001',
                    'relationship_type' => 'Orang Tua',
                    'notes' => 'Ayah memiliki usaha catering rumahan.',
                ],
                'health' => [
                    'blood_type' => 'B',
                    'height' => 165.2,
                    'weight' => 55.8,
                    'allergies' => null,
                    'medical_history' => null,
                    'disabilities' => null,
                    'medications' => null,
                    'hospital' => null,
                    'doctor' => null,
                    'notes' => 'Sehat, aktif olahraga.',
                ],
                'education_histories' => [
                    [
                        'education_level' => 'SMP',
                        'school_name' => 'SMP Negeri 8 Jakarta',
                        'npsn' => '40123012',
                        'address' => 'Jl. Merdeka Timur 5',
                        'entry_year' => 2021,
                        'graduation_year' => 2024,
                        'final_score' => 84.2,
                        'is_graduated' => true,
                        'notes' => 'Siswa aktif OSIS.',
                    ],
                ],
                'socials' => [
                    ['platform' => 'Instagram', 'username' => '@budiprasetya', 'url' => 'https://instagram.com/budiprasetya', 'is_public' => true, 'is_primary' => true],
                ],
                'achievements' => [
                    ['title' => 'Juara 2 Kompetisi Robotik', 'organizer' => 'Komunitas Robot', 'level' => 'Kota', 'category' => 'Non Akademik', 'rank' => 2, 'achievement_date' => '2024-10-12', 'certificate' => 'Sertifikat Robotik', 'description' => 'Prestasi di bidang robotik SMK.'],
                ],
                'violations' => [
                    ['title' => 'Tidak pakai seragam olahraga', 'point' => 3, 'violation_date' => '2025-02-18', 'description' => 'Datang tanpa seragam olahraga saat jadwal pelajaran berupa olahraga.'],
                ],
                'documents' => [
                    ['type' => 'Kartu Keluarga', 'original_name' => 'KK Budi Prasetya', 'mime_type' => 'application/pdf', 'file_size' => 148800, 'extension' => 'pdf', 'notes' => 'KK terbaru.'],
                    ['type' => 'Akta Kelahiran', 'original_name' => 'Akta Budi', 'mime_type' => 'application/pdf', 'file_size' => 112000, 'extension' => 'pdf', 'notes' => 'Dokumen identitas.'],
                ],
            ],
            // Add more student blocks here with the same structure for the remaining 8 students...
        ];

        foreach ($students as $studentData) {
            $student = Student::updateOrCreate(
                [
                    'nis' => $studentData['nis'],
                    'nisn' => $studentData['nisn'],
                ],
                [
                    'school_id' => $school->id,
                    'major_id' => $majors[$studentData['major']] ?? null,
                    'classroom_id' => $classrooms[$studentData['classroom']] ?? null,
                    'academic_year_id' => $academicYearId,
                    'student_status_id' => $studentStatusId,
                    'religion_id' => Religion::inRandomOrder()->value('id'),
                    'gender_id' => $genders[$studentData['gender']] ?? $genders['L'],
                    'blood_type_id' => $bloodTypes[array_rand($bloodTypes)] ?? null,
                    'citizenship_id' => $citizenshipId,
                    'full_name' => $studentData['full_name'],
                    'nickname' => explode(' ', $studentData['full_name'])[0],
                    'birth_place' => $studentData['birth_place'],
                    'birth_date' => $studentData['birth_date'],
                    'phone' => $studentData['phone'],
                    'email' => $studentData['email'],
                    'address' => 'Jl. Merdeka No. ' . substr($studentData['nis'], -2),
                    'postal_code' => '12345',
                    'notes' => $studentData['notes'],
                ]
            );

            $family = $studentData['family'];
            StudentFamily::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'father_name' => $family['father_name'],
                    'father_phone' => $family['father_phone'],
                    'father_occupation_id' => $occupations[$family['father_occupation']] ?? null,
                    'father_income_category_id' => $incomeCategories[$family['father_income_category']] ?? null,
                    'mother_name' => $family['mother_name'],
                    'mother_phone' => $family['mother_phone'],
                    'mother_occupation_id' => $occupations[$family['mother_occupation']] ?? null,
                    'mother_income_category_id' => $incomeCategories[$family['mother_income_category']] ?? null,
                    'guardian_name' => $family['guardian_name'],
                    'guardian_phone' => $family['guardian_phone'],

                    // Lines 241 & 242: Direct null checks or array lookup fallback without inner ??
                    'guardian_occupation_id' => null,
                    'guardian_income_category_id' => null,
                    'emergency_contact_name' => $family['emergency_contact_name'],
                    'emergency_contact_phone' => $family['emergency_contact_phone'],
                    'relationship_type_id' => $relationshipTypes[$family['relationship_type']] ?? null,
                    'notes' => $family['notes'],
                ]
            );

            $health = $studentData['health'];
            StudentHealth::updateOrCreate(
                ['student_id' => $student->id],
                [
                    // Line 255: Access the string key directly into the lookup array
                    'blood_type_id' => $bloodTypes[$health['blood_type']] ?? null,

                    'height' => $health['height'],
                    'weight' => $health['weight'],
                    'allergies' => $health['allergies'],
                    'medical_history' => $health['medical_history'],
                    'disabilities' => $health['disabilities'],
                    'medications' => $health['medications'],
                    'hospital' => $health['hospital'],
                    'doctor' => $health['doctor'],
                    'notes' => $health['notes'],
                ]
            );

            foreach ($studentData['education_histories'] as $education) {
                StudentEducationHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'school_name' => $education['school_name'],
                    ],
                    [
                        'education_level_id' => $educationLevels[$education['education_level']] ?? null,
                        'npsn' => $education['npsn'],
                        'address' => $education['address'],
                        'entry_year' => $education['entry_year'],
                        'graduation_year' => $education['graduation_year'],
                        'final_score' => $education['final_score'],
                        'is_graduated' => $education['is_graduated'],
                        'notes' => $education['notes'],
                    ]
                );
            }

            foreach ($studentData['socials'] as $social) {
                StudentSocial::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'social_platform_id' => $socialPlatforms[$social['platform']] ?? null,
                        'username' => $social['username'],
                    ],
                    [
                        'url' => $social['url'],
                        'is_public' => $social['is_public'],
                        'is_primary' => $social['is_primary'],
                    ]
                );
            }

            foreach ($studentData['achievements'] as $achievement) {
                StudentAchievement::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'title' => $achievement['title'],
                    ],
                    [
                        'organizer' => $achievement['organizer'],
                        'level' => $achievement['level'],
                        'category' => $achievement['category'],
                        'rank' => $achievement['rank'],
                        'achievement_date' => $achievement['achievement_date'],
                        'certificate' => $achievement['certificate'],
                        'description' => $achievement['description'],
                    ]
                );
            }

            foreach ($studentData['violations'] as $violation) {
                StudentViolation::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'title' => $violation['title'],
                    ],
                    [
                        'point' => $violation['point'],
                        'violation_date' => $violation['violation_date'],
                        'description' => $violation['description'],
                    ]
                );
            }

            foreach ($studentData['documents'] as $index => $document) {
                $documentTypeId = $documentTypes[$document['type']] ?? null;
                $storedName = Str::slug($student->full_name . '-' . $document['type'] . '-' . ($index + 1));

                StudentDocument::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'original_name' => $document['original_name'],
                    ],
                    [
                        'document_type_id' => $documentTypeId,
                        'stored_name' => $storedName . '.' . $document['extension'],
                        'file_path' => '/storage/documents/' . $storedName . '.' . $document['extension'],
                        'disk' => 'public',
                        'mime_type' => $document['mime_type'],
                        'file_size' => $document['file_size'],
                        'extension' => $document['extension'],
                        'notes' => $document['notes'],
                    ]
                );
            }
        }
        
    }

}