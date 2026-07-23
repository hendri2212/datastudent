<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Head, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Award,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Database,
    Download,
    FileText,
    Filter,
    Fingerprint,
    Heart,
    Info,
    Lock,
    MoreHorizontal,
    Pencil,
    Plus,
    Printer,
    RefreshCw,
    School,
    Search,
    ShieldCheck,
    TrendingUp,
    Unlock,
    UserMinus,
    Users
} from '@lucide/vue';
import { computed, ref, } from 'vue';

import StudentFormDialog from './StudentFormDialog.vue';

/**
 * ==========================================
 * CONTROLLER & STATE INITIALIZATION
 * ==========================================
 */
const page = usePage();
const authUser = computed(() => page.props.auth?.user || { id: 2026, name: 'Staf Administrasi TU Kepala' });

// Master Option Arrays Sesuai Skema Database Migrasi Relasional
const religionOptions = [
    { id: 1, name: 'Islam' },
    { id: 2, name: 'Kristen' },
    { id: 3, name: 'Katolik' },
    { id: 4, name: 'Hindu' },
    { id: 5, name: 'Buddha' },
    { id: 6, name: 'Khonghucu' }
];

const genderOptions = [
    { id: 1, name: 'Laki-laki', code: 'L' },
    { id: 2, name: 'Perempuan', code: 'P' }
];

const majors = [
    { id: 1, name: 'Rekayasa Perangkat Lunak', code: 'RPL', head: 'Ir. H. Budi Utomo, M.T.' },
    { id: 2, name: 'Akuntansi dan Keuangan Lembaga', code: 'AKL', head: 'Hj. Siti Aminah, S.E., M.Ak.' },
    { id: 3, name: 'Desain Komunikasi Visual', code: 'DKV', head: 'Rendra Wijaya, S.Sn., M.Ds.' },
    { id: 4, name: 'Teknik Komputer dan Jaringan', code: 'TKJ', head: 'Anwar Sadat, S.Kom., M.Cs.' }
];

const classrooms = [
    { id: 1, name: 'X RPL 1', major_id: 1, grade: 'X' },
    { id: 2, name: 'XI RPL 1', major_id: 1, grade: 'XI' },
    { id: 3, name: 'XII RPL 1', major_id: 1, grade: 'XII' },
    { id: 4, name: 'X AKL 1', major_id: 2, grade: 'X' },
    { id: 5, name: 'XI AKL 1', major_id: 2, grade: 'XI' },
    { id: 6, name: 'XII AKL 1', major_id: 2, grade: 'XII' },
    { id: 7, name: 'XII DKV 1', major_id: 3, grade: 'XII' },
    { id: 8, name: 'XII TKJ 1', major_id: 4, grade: 'XII' }
];

const bloodTypes = [
    { id: 1, name: 'A' },
    { id: 2, name: 'B' },
    { id: 3, name: 'AB' },
    { id: 4, name: 'O' }
];

const studentStatuses = [
    { id: 1, name: 'Aktif', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    { id: 2, name: 'Cuti Resmi', badge: 'bg-amber-50 text-amber-700 border-amber-200' },
    { id: 3, name: 'Mutasi Keluar', badge: 'bg-neutral-100 text-neutral-600 border-neutral-300' },
    { id: 4, name: 'Diberhentikan', badge: 'bg-red-50 text-red-700 border-red-200' }
];

/**
 * GENERATOR GENERATION DUMMY DATA LENGKAP SKEMA MULTI-TABEL
 * Menghasilkan data simulasi tebal berstruktur relasional penuh
 */
const generateDummyStudents = () => {
    const list = [];
    const namesMale = ['Muhammad Rizky', 'Achmad Faizal', 'Dimas Aditya', 'Eko Prasetyo', 'Bagus Wijaya', 'Hendra Kusuma', 'Fajar Ramadhan', 'Aditya Nugraha', 'Rian Hidayat', 'Andika Putra'];
    const namesFemale = ['Siti Nur Rahayu', 'Anisa Fitriani', 'Putri Indah', 'Dewi Lestari', 'Rina Novita', 'Mega Utami', 'Intan Permatasari', 'Lestari Wahyuni', 'Sri Wahyuni', 'Nani Wijaya'];
    const lastNames = ['Saputra', 'Pratama', 'Hidayat', 'Kurniawan', 'Santoso', 'Wibowo', 'Siregar', 'Subagyo', 'Ginting', 'Kusuma'];
    const places = ['Kotabaru', 'Banjarmasin', 'Jakarta', 'Surabaya', 'Bandung', 'Yogyakarta', 'Medan', 'Makassar'];
    
    for (let i = 1; i <= 85; i++) {
        const isLocked = i % 3 === 0;
        const genderObj = i % 2 === 0 ? genderOptions[1] : genderOptions[0];
        const baseName = genderObj.id === 2 ? namesFemale[i % namesFemale.length] : namesMale[i % namesMale.length];
        const fullName = `${baseName} ${lastNames[(i + 3) % lastNames.length]} ${i}`;
        
        const religionObj = religionOptions[(i - 1) % religionOptions.length];
        const classroomObj = classrooms[(i - 1) % classrooms.length];
        const majorObj = majors.find(m => m.id === classroomObj.major_id) || majors[0];
        const statusObj = studentStatuses[(i - 1) % 10 < 9 ? 0 : (i % 3 + 1)]; 
        const bloodObj = bloodTypes[i % bloodTypes.length];

        list.push({
            id: i,
            user_id: 1000 + i,
            school_id: 1,
            major_id: majorObj.id,
            classroom_id: classroomObj.id,
            academic_year_id: 1,
            student_status_id: statusObj.id,
            gender_id: genderObj.id,
            religion_id: religionObj.id,
            
            nis: `222310${String(i).padStart(3, '0')}`,
            nisn: `006471${String(i).padStart(4, '0')}`,
            name: fullName,
            email: `${fullName.toLowerCase().replace(/\s+/g, '')}.${i}@smk-pusat.sch.id`,
            phone: `08123456${String(i).padStart(4, '0')}`,
            birth_place: places[i % places.length],
            birth_date: `2006-0${(i % 9) + 1}-${String((i % 20) + 1).padStart(2, '0')}`,
            address: `Jl. Pahlawan Kemerdekaan No. ${i}, RT ${i % 5 + 1}/RW 02, Distrik Utama`,
            notes: isLocked ? 'Berkas kesiswaan telah dikunci permanen untuk proses akreditasi dan sinkronisasi berkas DAPODIK Kemendikbud.' : '',
            is_locked: isLocked,
            verified_at: isLocked ? '2026-07-20 09:15:22' : null,
            verified_by: isLocked ? authUser.value.id : null,
            
            // Relasi Objek Mock Lengkap
            classroom: classroomObj,
            major: majorObj,
            religion: religionObj,
            gender: genderObj,
            student_status: statusObj,
            verified_by_user: isLocked ? { id: authUser.value.id, name: authUser.value.name } : null,

            // Relasi Tabel Khas `student_family`
            family: {
                id: 500 + i,
                father_name: genderObj.id === 2 ? `Irwan ${lastNames[i % lastNames.length]}` : `Ahmad ${lastNames[i % lastNames.length]}`,
                father_phone: '081999888111',
                father_occupation_id: (i % 4) + 1,
                mother_name: `Siti Aminah ${i}`,
                mother_phone: '081999888222',
                guardian_name: i % 10 === 0 ? `Wali Murid Pengganti ${i}` : '',
                guardian_phone: i % 10 === 0 ? '085244332211' : '',
                emergency_contact_name: `Kontak Darurat Keluarga ${i}`,
                emergency_contact_phone: `0899887766${String(i).padStart(2, '0')}`,
                relationship_type_id: 1
            },

            // Relasi Tabel Khas `student_education_history`
            education_history: {
                id: 600 + i,
                school_name: `SMP Negeri ${i % 5 + 1} Sektoral`,
                npsn: `301020${String(i).padStart(2, '0')}`,
                address: 'Kabupaten Pusat Sektor 3',
                entry_year: 2021,
                graduation_year: 2024,
                final_score: (80.00 + (i % 20) * 0.95).toFixed(2),
                is_graduated: true
            },

            // Relasi Tabel Khas `student_healths`
            health: {
                id: 700 + i,
                height: (genderObj.id === 2 ? 155.00 + (i % 15) : 165.00 + (i % 20)).toFixed(2),
                weight: (genderObj.id === 2 ? 45.00 + (i % 18) : 55.00 + (i % 25)).toFixed(2),
                blood_type_id: bloodObj.id,
                blood_type: bloodObj,
                allergies: i % 7 === 0 ? 'Alergi Obat Antibiotik Penisinil' : 'Tidak Ada',
                medical_history: i % 12 === 0 ? 'Asma Bronkial Ringan' : 'Tidak Ada',
                disabilities: 'Tidak Ada',
                medications: 'Tidak Ada',
                hospital: 'RSUD Daerah Sehat',
                doctor: 'dr. H. Gunawan, Sp.A'
            },

            // Array Relasi Transaksional Tambahan (Achievements & Violations)
            achievements: i % 5 === 0 ? [
                { id: 10 + i, title: 'Juara 1 Lomba Kompetensi Siswa (LKS) Bidang IT', level: 'Provinsi', rank: 1, category: 'Akademik' }
            ] : [],
            violations: i % 7 === 0 ? [
                { id: 20 + i, title: 'Terlambat Masuk Gerbang Sekolah Sesi Pagi', point: 5, violation_date: '2026-07-16' }
            ] : []
        });
    }
    return list;
};

/**
 * REACTIVE STATE MANAGEMENT
 */
const students = ref(generateDummyStudents());
const searchQuery = ref('');
const filterStatus = ref('all');
const filterReligion = ref('all');
const filterGender = ref('all');
const filterMajor = ref('all');
const filterGrade = ref('all');
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal Dialog control states
const isFormOpen = ref(false);
const isDetailOpen = ref(false);
const isUnlockDialogOpen = ref(false);
const isAuditLogOpen = ref(false);
const isPrintViewOpen = ref(false);

// Focus Data Target References
const selectedStudent = ref<any>(null);
const selectedStudentForUnlock = ref<any>(null);

// Security OTP & Anti-Tamper Configuration
const authCodeInput = ref('');
const authError = ref('');
const systemSecureToken = '000000'; 

// Sub-Tab inside detail modal view
const activeDetailTab = ref('pokok');

// Audit System logs memory trace
const auditLogs = ref<Array<{ timestamp: string, user: string, action: string, targetnis: string }>>([
    { timestamp: '2026-07-20 10:00:00', user: 'Staf Administrasi TU Kepala', action: 'Inisialisasi Sistem Enkripsi Audit Kesiswaan', targetnis: 'SYSTEM' },
    { timestamp: '2026-07-20 09:15:22', user: 'Staf Administrasi TU Kepala', action: 'Mengunci Berkas Secara Massal Sistem DAPODIK', targetnis: 'BATCH_PROCESS' }
]);

/**
 * ==========================================
 * ADVANCED METRIC COMPUTED STATISTIK 
 * ==========================================
 */
const totalStudentsCount = computed(() => students.value.length);
const lockedStudentsCount = computed(() => students.value.filter(s => s.is_locked).length);
const unlockedStudentsCount = computed(() => students.value.filter(s => !s.is_locked).length);
const maleStudentsCount = computed(() => students.value.filter(s => s.gender_id === 1).length);
const femaleStudentsCount = computed(() => students.value.filter(s => s.gender_id === 2).length);

// Rasio Presentase Kunci Audit
const lockRatioPercent = computed(() => {
    if (totalStudentsCount.value === 0) return 0;
    return Math.round((lockedStudentsCount.value / totalStudentsCount.value) * 100);
});

// Distribusi Per-Agama Komparatif Ringkas
const religionDistribution = computed(() => {
    return religionOptions.map(r => {
        const count = students.value.filter(s => s.religion_id === r.id).length;
        return { name: r.name, count };
    });
});

// Distribusi Per-Jurusan Komparatif Ringkas
const majorDistribution = computed(() => {
    return majors.map(m => {
        const count = students.value.filter(s => s.major_id === m.id).length;
        return { code: m.code, count };
    });
});

// Akumulasi Total Poin Pelanggaran & Prestasi Terdata
const totalViolationPoints = computed(() => {
    return students.value.reduce((acc, current) => {
        const studentPoints = current.violations?.reduce((vAcc: number, v: any) => vAcc + v.point, 0) || 0;
        return acc + studentPoints;
    }, 0);
});

const totalAchievementCount = computed(() => {
    return students.value.reduce((acc, current) => acc + (current.achievements?.length || 0), 0);
});

/**
 * ==========================================
 * MULTI-DIMENSIONAL SEARCH & FILTER LOGIC
 * ==========================================
 */
const filteredStudents = computed(() => {
    return students.value.filter(student => {
        // Query Text Search
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !query || 
            student.name.toLowerCase().includes(query) || 
            student.nis.includes(query) || 
            student.nisn.includes(query) ||
            student.classroom?.name.toLowerCase().includes(query) ||
            student.email.toLowerCase().includes(query);

        // Status Audit Lock Check
        const matchesStatus = filterStatus.value === 'all' || 
            (filterStatus.value === 'locked' && student.is_locked) ||
            (filterStatus.value === 'unlocked' && !student.is_locked);

        // Agama Master Check
        const matchesReligion = filterReligion.value === 'all' || 
            student.religion_id === Number(filterReligion.value);

        // Gender Master Check
        const matchesGender = filterGender.value === 'all' || 
            student.gender_id === Number(filterGender.value);

        // Major Master Check
        const matchesMajor = filterMajor.value === 'all' || 
            student.major_id === Number(filterMajor.value);

        // Grade Level Check
        const matchesGrade = filterGrade.value === 'all' || 
            student.classroom?.grade === filterGrade.value;

        return matchesSearch && matchesStatus && matchesReligion && matchesGender && matchesMajor && matchesGrade;
    });
});

/**
 * PAGINATION METRICS CALCULATORS
 */
const totalPages = computed(() => Math.ceil(filteredStudents.value.length / itemsPerPage.value));
const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredStudents.value.slice(start, start + itemsPerPage.value);
});

const handleFilterChange = () => {
    currentPage.value = 1;
};

const clearAllFilters = () => {
    searchQuery.value = '';
    filterStatus.value = 'all';
    filterReligion.value = 'all';
    filterGender.value = 'all';
    filterMajor.value = 'all';
    filterGrade.value = 'all';
    currentPage.value = 1;
};

/**
 * ==========================================
 * SECURITY & OPERATIONS CORE CODE
 * ==========================================
 */

const openAddForm = () => {
    selectedStudent.value = null;
    isFormOpen.value = true;
};

const openEditForm = (student: any) => {
    if (student.is_locked) {
        selectedStudentForUnlock.value = student;
        authCodeInput.value = '';
        authError.value = '';
        isUnlockDialogOpen.value = true;
        return;
    }
    selectedStudent.value = JSON.parse(JSON.stringify(student));
    isFormOpen.value = true;
};

// Validasi Token Pengaman Super (Mocking Server Security Key Verification)
const handleUnlockVerification = () => {
    if (authCodeInput.value !== systemSecureToken) {
        authError.value = 'Kode token autentikator tidak cocok atau sudah kedaluwarsa!';
        return;
    }
    if (selectedStudentForUnlock.value) {
        const idx = students.value.findIndex(s => s.id === selectedStudentForUnlock.value.id);
        if (idx !== -1) {
            students.value[idx].is_locked = false;
            
            // Log audit trail history entry
            auditLogs.value.unshift({
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
                user: authUser.value.name,
                action: 'Membuka Proteksi Kunci Keamanan Siswa',
                targetnis: students.value[idx].nis
            });

            const targetData = JSON.parse(JSON.stringify(students.value[idx]));
            isUnlockDialogOpen.value = false;
            setTimeout(() => {
                selectedStudent.value = targetData;
                isFormOpen.value = true;
            }, 200);
        }
    }
};

// Toggle Penguncian / Verifikasi Cepat dari Baris Tabel Utama
const directVerifyToggle = (student: any) => {
    const idx = students.value.findIndex(s => s.id === student.id);
    if (idx !== -1) {
        if (students.value[idx].is_locked) {
            selectedStudentForUnlock.value = student;
            authCodeInput.value = '';
            authError.value = '';
            isUnlockDialogOpen.value = true;
        } else {
            students.value[idx].is_locked = true;
            const nowTime = new Date().toISOString().replace('T', ' ').substring(0, 19);
            students.value[idx].verified_at = nowTime;
            students.value[idx].verified_by = authUser.value.id;
            students.value[idx].verified_by_user = { id: authUser.value.id, name: authUser.value.name };
            
            auditLogs.value.unshift({
                timestamp: nowTime,
                user: authUser.value.name,
                action: 'Mengunci & Memverifikasi Berkas Siswa',
                targetnis: students.value[idx].nis
            });
        }
    }
};

const openDetailModal = (student: any) => {
    selectedStudent.value = student;
    activeDetailTab.value = 'pokok';
    isDetailOpen.value = true;
};

const openPrintView = (student: any) => {
    selectedStudent.value = student;
    isPrintViewOpen.value = true;
};

// Simulasi Aksi Print Document Kertas fisik
const triggerSystemPrint = () => {
    window.print();
};

/**
 * FORM DATA SUBMIT INGESTION PROCESSOR
 * Mengintegrasikan data baru/modifikasi ke memori dengan validasi struktur relasi terikat
 */
const handleFormSubmit = (payload: any) => {
    const idx = students.value.findIndex(s => s.id === payload.id);
    
    // Sinkronisasi Binding Model Terkait Master Array Dropdown
    payload.religion = religionOptions.find(r => r.id === payload.religion_id) || religionOptions[0];
    payload.gender = genderOptions.find(g => g.id === payload.gender_id) || genderOptions[0];
    payload.classroom = classrooms.find(c => c.id === payload.classroom_id) || classrooms[0];
    payload.major = majors.find(m => m.id === payload.major_id) || majors[0];
    payload.student_status = studentStatuses.find(s => s.id === payload.student_status_id) || studentStatuses[0];

    // Fallback safeguard data objek nested kesehatan/keluarga/pendidikan jika kosong
    if (!payload.family) payload.family = { father_name: '', mother_name: '', father_phone: '', mother_phone: '' };
    if (!payload.education_history) payload.education_history = { school_name: '', npsn: '', final_score: '0.00' };
    if (!payload.health) payload.health = { height: '0', weight: '0', allergies: 'Tidak Ada' };

    const timestampStr = new Date().toISOString().replace('T', ' ').substring(0, 19);

    if (idx !== -1) {
        // Logika Perubahan Status Kunci & Inject Timestamp Verifikator
        if (payload.is_locked && !students.value[idx].is_locked) {
            payload.verified_at = timestampStr;
            payload.verified_by = authUser.value.id;
            payload.verified_by_user = { id: authUser.value.id, name: authUser.value.name };
        } else if (!payload.is_locked) {
            payload.verified_at = null;
            payload.verified_by = null;
            payload.verified_by_user = null;
        }

        students.value[idx] = { ...students.value[idx], ...payload };
        
        auditLogs.value.unshift({
            timestamp: timestampStr,
            user: authUser.value.name,
            action: 'Melakukan Modifikasi Entri Skema Siswa',
            targetnis: payload.nis
        });
    } else {
        // Skema Insert Record Data Baru
        const newId = students.value.length > 0 ? Math.max(...students.value.map(s => s.id)) + 1 : 1;
        payload.id = newId;
        
        if (payload.is_locked) {
            payload.verified_at = timestampStr;
            payload.verified_by = authUser.value.id;
            payload.verified_by_user = { id: authUser.value.id, name: authUser.value.name };
        }

        payload.achievements = [];
        payload.violations = [];

        students.value.unshift(payload);
        
        auditLogs.value.unshift({
            timestamp: timestampStr,
            user: authUser.value.name,
            action: 'Melakukan Registrasi Penuh Siswa Baru',
            targetnis: payload.nis
        });
    }
    isFormOpen.value = false;
};

// Fungsi helper untuk mencari dokumen secara aman
const getSelectedStudentDoc = (docType: string) => {
  if (!selectedStudent.value || !selectedStudent.value.documents) return null;
  const docs = selectedStudent.value.documents as any[];
  return docs.find((d: any) => d.type === docType) || null;
};

// Hapus Entri Siswa (Hanya diizinkan jika status tidak terkunci)
const deleteStudentEntry = (id: number) => {
    const target = students.value.find(s => s.id === id);
    if (target && target.is_locked) {
        alert('Gagal Eksekusi: Berkas terdata berstatus terkunci oleh audit pusat! Silakan hubungi administrator.');
        return;
    }
    if (confirm('Apakah Anda yakin ingin menghapus data siswa ini secara permanen dari sistem multi-tabel?')) {
        students.value = students.value.filter(s => s.id !== id);
        if (target) {
            auditLogs.value.unshift({
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
                user: authUser.value.name,
                action: 'Menghapus Permanen Record Siswa (Soft Delete)',
                targetnis: target.nis
            });
        }
    }
};

const exportDataCSV = () => {
    alert('Simulasi Ekspor: Berhasil merangkum ' + filteredStudents.value.length + ' data baris siswa aktif ke berkas spreadsheet .CSV');
};
</script>

<template>
    <div class="min-h-screen bg-neutral-50 text-xs text-neutral-800 antialiased font-sans p-4 md:p-6 print:bg-white print:p-0">
        <Head title="Pusat Manajemen Database Kesiswaan Multi-Relasi" />

        <div class="mx-auto max-w-7xl space-y-6 print:max-w-full print:space-y-0">
            
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-5 rounded-2xl border border-neutral-200/80 shadow-sm print:hidden">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl border border-indigo-100 hidden sm:block">
                        <School class="size-6" />
                    </div>
                    <div>
                        <h1 class="text-base font-black text-neutral-900 tracking-tight flex items-center gap-2">
                            Data Kesiswaan Multi-Relasi 
                            <span class="px-2 py-0.5 rounded-full text-[9px] bg-indigo-100 text-indigo-700 border border-indigo-200 font-mono font-bold">V2.6-Stable</span>
                        </h1>
                        <p class="text-neutral-400 text-[11px] mt-0.5 font-medium">
                            Konsol Utama Pemetaan Tabel Relasional 
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" @click="isAuditLogOpen = true" class="h-9 text-xs font-semibold rounded-xl gap-1.5 px-3 border-neutral-200 hover:bg-neutral-50 text-neutral-600">
                        <ClipboardList class="size-3.5" /> Log Jejak Audit
                    </Button>
                    <Button @click="openAddForm" class="!bg-indigo-600 hover:!bg-indigo-700 text-white font-bold rounded-xl h-9 text-xs px-4 border-none shadow-sm transition-all duration-200">
                        <Plus class="size-4 mr-1 stroke-[3]" /> Registrasi Siswa Baru
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 print:hidden">
                
                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm flex flex-col justify-between hover:border-neutral-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Total Relasi</span>
                        <div class="size-6 bg-neutral-100 rounded-lg flex items-center justify-center text-neutral-500"><Users class="size-3.5" /></div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xl font-black text-neutral-900 font-mono">{{ totalStudentsCount }}</span>
                        <span class="block text-[10px] text-neutral-400 font-medium mt-0.5">Siswa Terdaftar</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm border-l-red-500 border-l-4 flex flex-col justify-between hover:border-neutral-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Kunci DAPODIK</span>
                        <div class="size-6 bg-red-50 rounded-lg flex items-center justify-center text-red-500"><Lock class="size-3" /></div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xl font-black text-red-600 font-mono">{{ lockedStudentsCount }}</span>
                        <span class="block text-[10px] text-neutral-400 font-medium mt-0.5">Rasio: {{ lockRatioPercent }}% Berkas</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm border-l-emerald-500 border-l-4 flex flex-col justify-between hover:border-neutral-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Terbuka (Edit)</span>
                        <div class="size-6 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-500"><Unlock class="size-3" /></div>
                    </div>
                    <div class="mt-2">
                        <span class="text-xl font-black text-emerald-600 font-mono">{{ unlockedStudentsCount }}</span>
                        <span class="block text-[10px] text-neutral-400 font-medium mt-0.5">Dapat Dimodifikasi</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm flex flex-col justify-between hover:border-neutral-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Laki-Laki (L)</span>
                        <span class="text-[9px] font-mono font-bold bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded border border-blue-100">GND_1</span>
                    </div>
                    <div class="mt-2">
                        <span class="text-xl font-black text-neutral-800 font-mono">{{ maleStudentsCount }}</span>
                        <span class="block text-[10px] text-neutral-400 font-medium mt-0.5">Total Siswa L</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm flex flex-col justify-between hover:border-neutral-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Perempuan (P)</span>
                        <span class="text-[9px] font-mono font-bold bg-pink-50 text-pink-600 px-1.5 py-0.5 rounded border border-pink-100">GND_2</span>
                    </div>
                    <div class="mt-2">
                        <span class="text-xl font-black text-neutral-800 font-mono">{{ femaleStudentsCount }}</span>
                        <span class="block text-[10px] text-neutral-400 font-medium mt-0.5">Total Siswi P</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-neutral-200/80 shadow-sm flex flex-col justify-between hover:border-neutral-300 transition-all bg-gradient-to-br from-neutral-50 to-white">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-400 font-bold text-[10px] uppercase tracking-wider">Poin & Prestasi</span>
                        <div class="size-6 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500"><Award class="size-3.5" /></div>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-1 divide-x border-t pt-1.5 border-neutral-100">
                        <div>
                            <span class="block font-bold text-red-600 font-mono">{{ totalViolationPoints }} Pts</span>
                            <span class="text-[8px] text-neutral-400 block">Pelanggaran</span>
                        </div>
                        <div class="pl-2">
                            <span class="block font-bold text-indigo-600 font-mono">{{ totalAchievementCount }} Tks</span>
                            <span class="text-[8px] text-neutral-400 block">Penghargaan</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 print:hidden">
                <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-sm col-span-1 md:col-span-2">
                    <h3 class="font-bold text-neutral-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5 mb-2.5">
                        <TrendingUp class="size-3.5 text-indigo-500" /> Distribusi Proporsi Agama Terdata
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        <div v-for="rel in religionDistribution" :key="rel.name" class="p-2 bg-neutral-50 rounded-lg border border-neutral-100">
                            <span class="block text-neutral-400 font-medium text-[9px] truncate">{{ rel.name }}</span>
                            <span class="text-xs font-black text-neutral-800 font-mono">{{ rel.count }} <span class="text-[9px] font-normal text-neutral-400">jiwa</span></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-neutral-200/80 shadow-sm col-span-1 md:col-span-2">
                    <h3 class="font-bold text-neutral-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5 mb-2.5">
                        <Database class="size-3.5 text-indigo-500" /> Beban Siswa Per-Jurusan Kompetensi
                    </h3>
                    <div class="grid grid-cols-4 gap-2">
                        <div v-for="maj in majorDistribution" :key="maj.code" class="p-2 bg-neutral-50 rounded-lg border border-neutral-100 text-center">
                            <span class="block font-bold text-indigo-600 font-mono text-[10px]">{{ maj.code }}</span>
                            <span class="text-sm font-black text-neutral-800 font-mono block mt-0.5">{{ maj.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-neutral-200/80 shadow-sm space-y-3 print:hidden">
                <div class="flex items-center justify-between border-b pb-2 border-neutral-100">
                    <span class="font-black text-neutral-900 flex items-center gap-1.5">
                        <Filter class="size-3.5 text-indigo-600" /> Filter Multi-Dimensi Database Kesiswaan
                    </span>
                    <button @click="clearAllFilters" class="text-indigo-600 hover:text-indigo-700 text-[10px] font-bold flex items-center gap-1 bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100 transition-colors">
                        <RefreshCw class="size-2.5" /> Reset Seluruh Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5">
                    <div class="col-span-1 sm:col-span-2 relative">
                        <label class="block font-bold text-neutral-400 text-[9px] uppercase mb-1">Cari Textual</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-2.5 size-3.5 text-neutral-400" />
                            <Input v-model="searchQuery" @input="handleFilterChange" placeholder="Ketik nama, NIS, NISN, kelas..." class="pl-9 h-9 text-xs rounded-xl" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-400 text-[9px] uppercase mb-1">Audit Kunci</label>
                        <select v-model="filterStatus" @change="handleFilterChange" class="w-full h-9 text-xs rounded-xl border border-neutral-200 px-3 bg-neutral-50 font-medium text-neutral-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                            <option value="all">Semua Audit Kunci</option>
                            <option value="locked">Terkunci (`is_locked` = 1)</option>
                            <option value="unlocked">Terbuka (`is_locked` = 0)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-400 text-[9px] uppercase mb-1">Agama</label>
                        <select v-model="filterReligion" @change="handleFilterChange" class="w-full h-9 text-xs rounded-xl border border-neutral-200 px-3 bg-neutral-50 font-medium text-neutral-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                            <option value="all">Semua Agama</option>
                            <option v-for="rel in religionOptions" :key="rel.id" :value="rel.id">{{ rel.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-400 text-[9px] uppercase mb-1">Jenis Kelamin</label>
                        <select v-model="filterGender" @change="handleFilterChange" class="w-full h-9 text-xs rounded-xl border border-neutral-200 px-3 bg-neutral-50 font-medium text-neutral-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                            <option value="all">Semua J.K</option>
                            <option v-for="gnd in genderOptions" :key="gnd.id" :value="gnd.id">{{ gnd.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-neutral-400 text-[9px] uppercase mb-1">Jurusan / Major</label>
                        <select v-model="filterMajor" @change="handleFilterChange" class="w-full h-9 text-xs rounded-xl border border-neutral-200 px-3 bg-neutral-50 font-medium text-neutral-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                            <option value="all">Semua Jurusan</option>
                            <option v-for="maj in majors" :key="maj.id" :value="maj.id">{{ maj.code }}</option>
                        </select>
                    </div>
                </div>

                <div class="text-[10px] text-neutral-400 font-medium flex items-center justify-between pt-1">
                    <div>Menemukan <span class="text-neutral-900 font-bold font-mono">{{ filteredStudents.length }}</span> baris siswa dari filter di atas.</div>
                    <Button type="button" variant="link" @click="exportDataCSV" class="h-auto p-0 text-[10px] font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                        <Download class="size-3" /> Unduh Dokumen Hasil Filter (.CSV)
                    </Button>
                </div>
            </div>

            <div class="overflow-hidden bg-white rounded-2xl border border-neutral-200/80 shadow-sm print:border-none print:shadow-none">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 font-bold text-neutral-500 uppercase text-[9px] tracking-wider font-mono">
                                <th class="p-4 w-6">ID</th>
                                <th class="p-4">Informasi Inti & Kontak Siswa</th>
                                <th class="p-4">Identitas Resmi NIS / NISN</th>
                                <th class="p-4">Filter Relasi: Agama & Gender</th>
                                <th class="p-4">Kondisi Kesehatan (`Healths`)</th>
                                <th class="p-4">Status & Penguncian Audit</th>
                                <th class="p-4 w-20 text-center print:hidden">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="student in paginatedStudents" :key="student.id" class="hover:bg-neutral-50/40 transition-colors group">
                                <td class="p-4 text-neutral-400 font-mono font-medium">{{ student.id }}</td>
                                
                                <td class="p-4">
                                    <div class="font-bold text-neutral-900 group-hover:text-indigo-600 transition-colors text-xs">{{ student.name }}</div>
                                    <div class="text-[10px] text-neutral-400 font-mono mt-0.5 flex items-center gap-1">
                                        <span>{{ student.email }}</span>
                                        <span class="text-neutral-300">|</span>
                                        <span class="font-bold text-neutral-600 uppercase">{{ student.classroom?.name }}</span>
                                    </div>
                                </td>
                                
                                <td class="p-4 font-mono text-[11px] text-neutral-700">
                                    <div class="flex items-center gap-1">
                                        <span class="text-neutral-400 text-[10px] font-sans font-medium">NIS:</span> {{ student.nis }}
                                    </div>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-neutral-400 text-[10px] font-sans font-medium">NISN:</span> {{ student.nisn }}
                                    </div>
                                </td>
                                
                                <td class="p-4 font-medium text-neutral-800">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] bg-neutral-100 font-bold border border-neutral-200">{{ student.religion?.name || 'Null' }}</span>
                                    </div>
                                    <div class="text-[10px] text-neutral-400 font-mono mt-1 flex items-center gap-1">
                                        <span>Gender:</span>
                                        <span :class="['px-1 rounded font-bold text-[9px]', student.gender_id === 1 ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700']">
                                            {{ student.gender?.name || '-' }} ({{ student.gender?.code }})
                                        </span>
                                    </div>
                                </td>
                                
                                <td class="p-4">
                                    <div class="text-neutral-700 font-medium flex items-center gap-1" v-if="student.health">
                                        <Activity class="size-3 text-emerald-500 shrink-0" />
                                        <span>TB: {{ student.health.height }} cm</span>
                                        <span class="text-neutral-300">/</span>
                                        <span>BB: {{ student.health.weight }} kg</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-neutral-400 mt-0.5 truncate max-w-[160px]" :title="student.health?.allergies">
                                        Alergi: {{ student.health?.allergies }}
                                    </div>
                                </td>
                                
                                <td class="p-4">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['px-2 py-0.5 rounded-lg text-[10px] font-bold border inline-flex items-center gap-1 shadow-sm transition-all', student.is_locked ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200']">
                                                <Lock v-if="student.is_locked" class="size-2.5 text-red-600" />
                                                <Unlock v-else class="size-2.5 text-emerald-600" />
                                                {{ student.is_locked ? 'Locked' : 'Terbuka' }}
                                            </span>
                                            
                                            <button type="button" @click="directVerifyToggle(student)" class="px-2 py-0.5 rounded-lg text-[9px] font-bold border border-neutral-200 bg-white hover:bg-neutral-50 shadow-xs transition-colors text-neutral-500">
                                                Ubah Audit
                                            </button>
                                        </div>
                                        <div v-if="student.verified_at" class="text-[9px] text-neutral-400 font-mono mt-0.5">
                                            PIC: {{ student.verified_by_user?.name }} ({{ student.verified_at.substring(0, 10) }})
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-center print:hidden">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" class="size-7 p-0 rounded-lg hover:bg-neutral-100"><MoreHorizontal class="size-3.5 text-neutral-500" /></Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="text-xs rounded-xl border border-neutral-200 bg-white shadow-xl p-1 w-44">
                                            <DropdownMenuItem @click="openDetailModal(student)" class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-neutral-50 font-medium">
                                                <Info class="size-3.5 text-indigo-600" /> Lihat Skema Relasi
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openEditForm(student)" class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-neutral-50 font-medium">
                                                <Pencil class="size-3.5 text-amber-600" /> Edit Form Input
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openPrintView(student)" class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-neutral-50 font-medium">
                                                <Printer class="size-3.5 text-emerald-600" /> Cetak Lembar Profil
                                            </DropdownMenuItem>
                                            <div class="h-px bg-neutral-100 my-1"></div>
                                            <DropdownMenuItem @click="deleteStudentEntry(student.id)" class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:bg-red-50 text-red-600 font-medium" :disabled="student.is_locked">
                                                <UserMinus class="size-3.5 text-red-600" /> Hapus Data Siswa
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                            
                            <tr v-if="filteredStudents.length === 0">
                                <td colspan="7" class="p-12 text-center text-neutral-400 font-medium">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <AlertTriangle class="size-8 text-neutral-300" />
                                        <p class="text-neutral-500 text-xs font-bold">Tidak Ditemukan Baris Rekaman Transaksional</p>
                                        <p class="text-[10px] text-neutral-400">Silakan ubah kata kunci pencarian atau bersihkan parameter filter Anda.</p>
                                        <Button size="sm" variant="outline" @click="clearAllFilters" class="mt-2 text-[10px] h-7 rounded-lg">Bersihkan Filter</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between border-t border-neutral-100 p-4 bg-neutral-50/50 text-neutral-500 text-[11px] font-medium gap-3 print:hidden">
                    <div class="flex items-center gap-2">
                        <span>Menampilkan baris data</span>
                        <select v-model="itemsPerPage" @change="currentPage = 1" class="h-6 text-[10px] rounded border border-neutral-200 bg-white font-bold text-neutral-700 focus:outline-none">
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>dari {{ filteredStudents.length }} data hasil pencarian.</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <Button variant="outline" class="size-7 p-0 rounded-lg border-neutral-200" :disabled="currentPage === 1" @click="currentPage--">
                            <ChevronLeft class="size-3.5" />
                        </Button>
                        <div class="flex items-center font-bold px-2 text-neutral-800">
                            Halaman {{ currentPage }} dari {{ totalPages || 1 }}
                        </div>
                        <Button variant="outline" class="size-7 p-0 rounded-lg border-neutral-200" :disabled="currentPage === totalPages || totalPages === 0" @click="currentPage++">
                            <ChevronRight class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isUnlockDialogOpen">
            <DialogContent class="sm:max-w-[360px] p-6 bg-white dark:bg-neutral-900 rounded-2xl border shadow-2xl">
                <DialogHeader>
                    <DialogTitle class="text-sm font-black flex items-center gap-2 text-red-600">
                        <Fingerprint class="size-5 text-red-500 stroke-[2.5]" /> Otentikasi Token Lapisan Keamanan
                    </DialogTitle>
                    <DialogDescription class="text-[11px] font-medium text-neutral-400 leading-relaxed mt-1">
                        Sistem mendeteksi kolom `<span class="font-mono text-neutral-700">is_locked</span>` berstatus aktif. Masukkan Kode OTP Supervisor untuk merombak proteksi data internal.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-3 text-center">
                    <Input v-model="authCodeInput" type="password" placeholder="••••••" class="text-center font-mono tracking-[0.6em] text-lg h-10 rounded-xl bg-neutral-50" maxlength="6" />
                    <div class="p-2 bg-indigo-50/50 rounded-xl border border-indigo-100 text-left flex items-start gap-1.5">
                        <Info class="size-3 text-indigo-600 shrink-0 mt-0.5" />
                        <p class="text-[9px] text-neutral-500 leading-normal">
                            Token Bypass Pengaman untuk Keperluan Uji Coba Administrasi Sekolah: <code class="bg-indigo-100 px-1 font-mono font-bold text-indigo-700 rounded">{{ systemSecureToken }}</code>
                        </p>
                    </div>
                    <p v-if="authError" class="text-[11px] text-red-500 font-bold bg-red-50 p-2 rounded-lg border border-red-100">{{ authError }}</p>
                </div>
                <DialogFooter class="gap-2 border-t pt-3 flex justify-end">
                    <Button variant="outline" @click="isUnlockDialogOpen = false" class="h-8 rounded-lg text-[11px]">Batal</Button>
                    <Button class="!bg-indigo-600 text-white hover:!bg-indigo-700 font-bold border-none shadow-sm h-8 rounded-lg text-[11px]" @click="handleUnlockVerification">Verifikasi Kunci</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDetailOpen"> 
            <DialogContent class="sm:max-w-[640px] p-6 bg-white rounded-2xl max-h-[85vh] overflow-y-auto border shadow-2xl"> 
                <DialogHeader class="border-b pb-3 border-neutral-100"> 
                    <DialogTitle class="text-sm font-black text-indigo-600 flex items-center gap-1.5"> 
                        <Database class="size-4" /> Inspeksi Skema Relasional Tabel Siswa 
                    </DialogTitle> 
                    <DialogDescription class="text-[10px] text-neutral-400"> 
                        Memetakan sambungan data antara tabel utama dengan sub-tabel child database. 
                    </DialogDescription> 
                </DialogHeader> 
                
                <div v-if="selectedStudent" class="py-3 space-y-4 text-[11px]"> 
                    <div class="flex border-b border-neutral-200 gap-1 text-[10px] font-bold overflow-x-auto whitespace-nowrap hidden-scrollbar"> 
                        <button @click="activeDetailTab = 'pokok'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'pokok' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Data Pokok</button> 
                        <button @click="activeDetailTab = 'keluarga'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'keluarga' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Keluarga Murid</button> 
                        <button @click="activeDetailTab = 'pendidikan'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'pendidikan' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Riwayat SMP</button> 
                        <button @click="activeDetailTab = 'medis'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'medis' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Kondisi Medis</button> 
                        <button @click="activeDetailTab = 'dokumen'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'dokumen' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Dokumen Resmi</button>
                        <button @click="activeDetailTab = 'rekam'" :class="['px-3 py-1.5 border-b-2 transition-all', activeDetailTab === 'rekam' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-400 hover:text-neutral-700']">Prestasi & Konseling</button> 
                    </div> 

                    <div v-if="activeDetailTab === 'pokok'" class="space-y-3 animation-fade">
                        <div class="bg-neutral-50 p-4 rounded-xl border border-neutral-100 relative">
                            <span class="absolute top-3 right-3 text-[9px] font-mono bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-bold border border-indigo-200 uppercase">{{ selectedStudent.student_status?.name }}</span>
                            <h4 class="font-black text-neutral-900 text-xs">{{ selectedStudent.name }}</h4>
                            <p class="text-neutral-400 font-mono mt-0.5">ID User Login Relasi: {{ selectedStudent.user_id || 'Belum Terikat' }}</p>
                                
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3 border-t pt-3 border-neutral-200/60 text-neutral-600">
                                    <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Nomor Induk Siswa (NIS)</span> <span class="text-neutral-800 font-mono font-medium">{{ selectedStudent.nis || '-' }}</span></div>
                                    <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">NISN (10 Digit)</span> <span class="text-neutral-800 font-mono font-medium">{{ selectedStudent.nisn || '-' }}</span></div>
                                    <div>
                                    <span class="font-bold text-neutral-400 text-[9px] block uppercase">Status Keaktifan</span> 
                                    <span class="text-neutral-800 font-medium flex items-center gap-1">
                                        <span class="size-1.5 rounded-full" :class="{
                                            'bg-emerald-500': selectedStudent.student_status_id === 1,
                                            'bg-amber-500': selectedStudent.student_status_id === 2,
                                            'bg-blue-500': selectedStudent.student_status_id === 3,
                                            'bg-rose-500': selectedStudent.student_status_id === 4
                                        }">
                                        </span>
                                        {{ selectedStudent.student_status?.name || '-' }}
                                    </span>
                                </div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Penempatan Kelas</span> <span class="text-neutral-800 font-medium">{{ selectedStudent.classroom?.name || '-' }}</span></div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Kompetensi Keahlian</span> <span class="text-neutral-800 font-medium">[{{ selectedStudent.major?.code }}] {{ selectedStudent.major?.name || '-' }}</span></div>
                                <div>
                                    <span class="font-bold text-neutral-400 text-[9px] block uppercase">Ketua Kompetensi Keahlian (Kajur)</span>
                                    <span class="text-neutral-800 font-medium block mt-0.5">
                                        {{ selectedStudent.major?.head || '-' }}
                                    </span>
                                </div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Agama (Religion)</span> <span class="text-neutral-800 font-medium">{{ selectedStudent.religion?.name || '-' }}</span></div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Jenis Kelamin (Gender)</span> <span class="text-neutral-800 font-medium">{{ selectedStudent.gender?.name || '-' }}</span></div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Tempat, Tanggal Lahir</span> <span class="text-neutral-800 font-medium">{{ selectedStudent.birth_place }}, {{ selectedStudent.birth_date }}</span></div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Kontak Telepon Aktif</span> <span class="text-neutral-800 font-mono font-medium">{{ selectedStudent.phone || '-' }}</span></div>
                                <div><span class="font-bold text-neutral-400 text-[9px] block uppercase">Email Akun Murid</span> <span class="text-neutral-800 font-medium truncate block">{{ selectedStudent.email || '-' }}</span></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 border-t pt-2 border-neutral-200/40 text-neutral-600" v-if="selectedStudent.instagram_url || selectedStudent.linkedin_url">
                                <div v-if="selectedStudent.instagram_url"><span class="font-bold text-neutral-400 text-[9px] block uppercase">Instagram Profil</span> <a :href="selectedStudent.instagram_url" target="_blank" class="text-indigo-600 font-medium hover:underline truncate block">{{ selectedStudent.instagram_url }}</a></div>
                                <div v-if="selectedStudent.linkedin_url"><span class="font-bold text-neutral-400 text-[9px] block uppercase">LinkedIn / Medsos</span> <a :href="selectedStudent.linkedin_url" target="_blank" class="text-indigo-600 font-medium hover:underline truncate block">{{ selectedStudent.linkedin_url }}</a></div>
                            </div>
                        </div>
                        <div class="p-3 bg-neutral-50/50 rounded-xl border border-neutral-100">
                            <span class="font-bold text-neutral-400 text-[9px] block uppercase mb-1">Alamat Lengkap Rumah Tinggal Domisili</span>
                            <p class="text-neutral-800 leading-relaxed font-medium">{{ selectedStudent.address }}</p>
                        </div>
                        <div class="p-3 bg-neutral-50/50 rounded-xl border border-neutral-100" v-if="selectedStudent.notes">
                            <span class="font-bold text-neutral-400 text-[9px] block uppercase mb-1">Catatan Internal / Rekam Jejak Administrasi</span>
                            <p class="text-neutral-800 leading-relaxed font-medium">{{ selectedStudent.notes }}</p>
                        </div>
                    </div>

                    <div v-if="activeDetailTab === 'keluarga'" class="space-y-3 animation-fade">
                        <div class="p-3 bg-indigo-50/40 rounded-xl border border-indigo-100 text-indigo-800 font-bold text-[10px] uppercase font-mono">
                            Skema Relasi: `student_family` ➔ One-To-One Unique Constraint Mapping
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100">
                                <span class="font-bold text-neutral-400 text-[9px] block uppercase">Nama Ayah Kandung</span>
                                <span class="text-neutral-800 font-bold block text-xs mt-0.5">{{ selectedStudent.family?.father_name || '-' }}</span>
                                <span class="text-[10px] text-neutral-400 font-mono block mt-1">Telp: {{ selectedStudent.family?.father_phone || '-' }}</span>
                            </div>
                            <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100">
                                <span class="font-bold text-neutral-400 text-[9px] block uppercase">Nama Ibu Kandung</span>
                                <span class="text-neutral-800 font-bold block text-xs mt-0.5">{{ selectedStudent.family?.mother_name || '-' }}</span>
                                <span class="text-[10px] text-neutral-400 font-mono block mt-1">Telp: {{ selectedStudent.family?.mother_phone || '-' }}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100" v-if="selectedStudent.family?.guardian_name">
                            <span class="font-bold text-neutral-400 text-[9px] block uppercase">Nama Wali Murid Pengganti</span>
                            <span class="text-neutral-800 font-bold block text-xs mt-0.5">{{ selectedStudent.family.guardian_name }}</span>
                            <span class="text-[10px] text-neutral-400 font-mono block mt-1">Hubungan Kontak: {{ selectedStudent.family.guardian_phone }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 p-3 bg-neutral-50/60 rounded-xl border border-neutral-100 text-neutral-600">
                            <div>Kontak Darurat: <span class="font-bold text-neutral-800">{{ selectedStudent.family?.emergency_contact_name || '-' }}</span></div>
                            <div>No. HP Darurat: <span class="font-mono text-neutral-800 font-bold">{{ selectedStudent.family?.emergency_contact_phone || '-' }}</span></div>
                        </div>
                    </div>

                    <div v-if="activeDetailTab === 'pendidikan'" class="space-y-3 animation-fade">
                        <div class="p-3 bg-amber-50/40 rounded-xl border border-amber-200 text-amber-800 font-bold text-[10px] uppercase font-mono">
                            Skema Relasi: `student_education_history` ➔ Unique Level Index
                        </div>
                        <div class="p-4 bg-neutral-50 rounded-xl border border-neutral-100 space-y-3">
                            <div>
                                <span class="font-bold text-neutral-400 text-[9px] block uppercase">Nama Sekolah Asal (SMP / MTs)</span>
                                <span class="text-neutral-900 font-black text-xs block mt-0.5">{{ selectedStudent.education_history?.school_name || '-' }}</span>
                                <span class="text-[10px] text-neutral-400 font-mono block mt-0.5">Nomor Pokok Sekolah Nasional (NPSN): {{ selectedStudent.education_history?.npsn || '-' }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 border-t pt-3 border-neutral-200 font-mono text-center">
                                <div class="bg-white p-2 rounded-lg border">
                                    <span class="text-[9px] text-neutral-400 block font-sans uppercase font-bold">Tahun Masuk</span>
                                    <span class="text-neutral-800 font-black">{{ selectedStudent.education_history?.entry_year || '-' }}</span>
                                </div>
                                <div class="bg-white p-2 rounded-lg border">
                                    <span class="text-[9px] text-neutral-400 block font-sans uppercase font-bold">Tahun Lulus</span>
                                    <span class="text-neutral-800 font-black">{{ selectedStudent.education_history?.graduation_year || '-' }}</span>
                                </div>
                                <div class="bg-white p-2 rounded-lg border bg-indigo-50/30 border-indigo-100">
                                    <span class="text-[9px] text-indigo-600 block font-sans uppercase font-bold">Nilai Kelulusan</span>
                                    <span class="text-indigo-700 font-black">{{ selectedStudent.education_history?.final_score || '0.00' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeDetailTab === 'medis'" class="space-y-3 animation-fade">
                        <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100 space-y-2.5">
                            <h5 class="font-bold text-neutral-900 text-xs flex items-center gap-1"><Heart class="size-3.5 text-red-500" /> Profil Antropometri Fisik</h5>
                            <div class="grid grid-cols-3 gap-2 text-center font-mono">
                                <div class="bg-white p-2 rounded-lg border">
                                    <span class="text-[9px] text-neutral-400 block font-sans font-medium">Tinggi Badan</span>
                                    <span class="font-bold text-neutral-800">{{ selectedStudent.health?.height || '-' }} cm</span>
                                </div>
                                <div class="bg-white p-2 rounded-lg border">
                                    <span class="text-[9px] text-neutral-400 block font-sans font-medium">Berat Badan</span>
                                    <span class="font-bold text-neutral-800">{{ selectedStudent.health?.weight || '-' }} kg</span>
                                </div>
                                <div class="bg-white p-2 rounded-lg border bg-red-50/30 border-red-100">
                                    <span class="text-[9px] text-red-600 block font-sans font-bold">Golongan Darah</span>
                                    <span class="font-black text-red-600">{{ selectedStudent.health?.blood_type?.name || 'O' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100">
                                <span class="font-bold text-red-600 text-[9px] block uppercase font-mono">Daftar Kontraindikasi Alergi Medis</span>
                                <p class="text-neutral-800 font-medium mt-1">{{ selectedStudent.health?.allergies || 'Tidak Terdeteksi' }}</p>
                            </div>
                            <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100">
                                <span class="font-bold text-neutral-400 text-[9px] block uppercase font-mono">Riwayat Penyakit Bawaan Kronis</span>
                                <p class="text-neutral-800 font-medium mt-1">{{ selectedStudent.health?.medical_history || 'Tidak Terdeteksi' }}</p>
                            </div>
                        </div>

                        <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100 text-neutral-500 font-medium" v-if="selectedStudent.health?.hospital">
                            Fasilitas Kesehatan Rujukan Utama: <span class="text-neutral-800 font-bold">{{ selectedStudent.health?.hospital }}</span>, PIC Penanggung Jawab: <span class="text-neutral-800 font-bold">{{ selectedStudent.health?.doctor }}</span>
                        </div>
                    </div>

                    <div v-if="activeDetailTab === 'dokumen'" class="space-y-3 animation-fade">
                        <div class="p-3 bg-indigo-50/40 rounded-xl border border-indigo-100 text-indigo-800 font-bold text-[10px] uppercase font-mono">
                            Skema Relasi: `student_documents` ➔ Berkas Lampiran Digital Resmi Siswa
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="docType in ['Kartu Keluarga (KK)', 'Akta Kelahiran', 'Ijazah SD', 'Ijazah SMP', 'KTP / KIA']" :key="docType" class="p-3 bg-neutral-50 rounded-xl border border-neutral-200/60 flex flex-col justify-between gap-3">
                                <div>
                                    <span class="font-bold text-neutral-400 text-[9px] block uppercase tracking-wide">{{ docType }}</span>
                                    <span class="text-neutral-800 font-medium block text-xs mt-1 truncate" :title="getSelectedStudentDoc(docType)?.original_name">
                                        {{ getSelectedStudentDoc(docType)?.original_name || 'Berkas belum diunggah' }}
                                    </span>
                                </div>
                                <div v-if="getSelectedStudentDoc(docType)" class="flex items-center">
                                    <a :href="getSelectedStudentDoc(docType)?.file_path" target="_blank" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 transition-all underline decoration-indigo-200 decoration-2">
                                        <FileText class="size-3" /> Buka / Unduh Berkas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeDetailTab === 'rekam'" class="space-y-3 animation-fade"> 
                        <div>
                            <h6 class="font-bold text-neutral-800 text-[10px] uppercase tracking-wider text-indigo-600 mb-1">🏆 Penghargaan & Prestasi Terdata (`student_achievements`)</h6>
                            <div v-if="selectedStudent.achievements?.length > 0" class="space-y-1.5">
                                <div v-for="ach in selectedStudent.achievements" :key="ach.id" class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-100 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-emerald-900 text-[11px]">{{ ach.title }}</div>
                                        <div class="text-[9px] text-emerald-600 font-medium">Tingkat Skala: {{ ach.level }} | Peringkat: {{ ach.rank }}</div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[9px] bg-emerald-100 text-emerald-800 font-bold rounded-sm uppercase font-mono">{{ ach.category }}</span>
                                </div>
                            </div>
                            <div v-else class="text-neutral-400 p-3 text-center border rounded-xl bg-neutral-50/50">Belum ada torehan rekam prestasi yang diinput pada semester berjalan.</div>
                        </div>

                        <div class="pt-1">
                            <h6 class="font-bold text-neutral-800 text-[10px] uppercase tracking-wider text-red-600 mb-1">⚠️ Catatan Pelanggaran Tata Tertib Murid (`student_violations`)</h6>
                            <div v-if="selectedStudent.violations?.length > 0" class="space-y-1.5">
                                <div v-for="violation in selectedStudent.violations" :key="violation.id" class="p-2.5 bg-red-50 rounded-lg border border-red-100 flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-red-900 text-[11px]">{{ violation.title }}</div>
                                        <div class="text-[9px] text-red-600 font-mono">Tanggal Kejadian: {{ violation.violation_date }}</div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] bg-red-100 text-red-700 font-black rounded-sm font-mono">+{{ violation.point }} Poin</span>
                                </div>
                            </div>
                            <div v-else class="text-neutral-400 p-3 text-center border rounded-xl bg-neutral-50/50">Siswa bersih. Bebas dari catatan poin akumulasi pelanggaran kedisiplinan.</div>
                        </div>
                    </div>
                </div> 
                
                <DialogFooter class="border-t pt-3 border-neutral-100 flex justify-between items-center">
                    <div class="text-[10px] text-neutral-400 font-mono" v-if="selectedStudent">UID Ref: STU-{{ String(selectedStudent.id).padStart(4, '0') }}</div>
                    <Button variant="outline" @click="isDetailOpen = false" class="h-8 rounded-lg text-[11px] font-bold">Tutup Dialog Rincian</Button>
                </DialogFooter>
            </DialogContent> 
        </Dialog>

        <Dialog v-model:open="isAuditLogOpen">
            <DialogContent class="sm:max-w-[480px] p-5 bg-white rounded-2xl border shadow-2xl">
                <DialogHeader class="border-b pb-2">
                    <DialogTitle class="text-sm font-black text-neutral-900 flex items-center gap-1.5">
                        <ShieldCheck class="size-4.5 text-indigo-600" /> Repositori Jejak Rekam Komparasi Audit Sistem
                    </DialogTitle>
                    <DialogDescription class="text-[10px]">Riwayat modifikasi dan verifikasi langsung berkas kesiswaan.</DialogDescription>
                </DialogHeader>
                <div class="py-2 space-y-2 max-h-[300px] overflow-y-auto font-mono text-[10px]">
                    <div v-for="(log, lIdx) in auditLogs" :key="lIdx" class="p-2.5 bg-neutral-50 border rounded-lg space-y-1">
                        <div class="flex items-center justify-between text-neutral-400 font-bold text-[9px]">
                            <span>{{ log.timestamp }}</span>
                            <span class="text-indigo-600">REF_NIS: {{ log.targetnis }}</span>
                        </div>
                        <div class="text-neutral-800 font-medium"><span class="font-bold text-neutral-500">Aksi:</span> {{ log.action }}</div>
                        <div class="text-neutral-400 text-[9px]">Operator Eksekusi: {{ log.user }}</div>
                    </div>
                </div>
                <DialogFooter class="border-t pt-2">
                    <Button variant="outline" @click="isAuditLogOpen = false" class="h-8 text-xs font-bold rounded-lg">Tutup Log</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isPrintViewOpen">
            <DialogContent class="sm:max-w-[700px] p-6 bg-white rounded-none max-h-[90vh] overflow-y-auto border shadow-2xl">
                <div class="mb-4 p-3 bg-neutral-100 rounded-xl border flex items-center justify-between print:hidden">
                    <span class="text-[11px] text-neutral-500 font-medium">Dokumen siap dicetak ke printer fisik / simpan ke berkas digital PDF.</span>
                    <Button @click="triggerSystemPrint" class="!bg-emerald-600 hover:!bg-emerald-700 text-white font-bold h-8 text-xs rounded-lg border-none">
                        <Printer class="size-3.5 mr-1" /> Cetak Dokumen Sekarang
                    </Button>
                </div>

                <div v-if="selectedStudent" class="p-4 border-2 border-neutral-800 text-neutral-900 space-y-4 bg-white text-xs">
                    <div class="text-center border-b-4 border-double border-neutral-900 pb-2">
                        <h2 class="text-sm font-black uppercase tracking-wide">Pemerintah Provinsi Sektor Utama Daerah</h2>
                        <h1 class="text-base font-black uppercase tracking-wider text-indigo-900 mt-0.5">SMK PUSAT KEUNGGULAN AKADEMIK</h1>
                        <p class="text-[10px] font-medium text-neutral-500 italic">Alamat Kampus Terpadu: Jalan Raya Kompleks Pendidikan No. 01 Telp (021) 55443322</p>
                    </div>

                    <div class="text-center">
                        <h3 class="font-black text-xs uppercase underline tracking-widest">LEMBAR PROFIL RELASI DOKUMEN INDUK SISWA</h3>
                        <p class="font-mono text-[10px] text-neutral-500 mt-0.5">Nomor Arsip Master: SMK/REG/{{ selectedStudent.nis }}/2026</p>
                    </div>

                    <table class="w-full text-left border-collapse my-4">
                        <tbody>
                            <tr class="border-b"><th class="py-1.5 w-40 font-bold uppercase text-[10px]">Nama Lengkap Siswa</th><td>: {{ selectedStudent.name }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Nomor Induk Siswa (NIS)</th><td class="font-mono">: {{ selectedStudent.nis }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">NISN Nasional</th><td class="font-mono">: {{ selectedStudent.nisn }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Kompetensi Keahlian</th><td>: {{ selectedStudent.major?.name }} ({{ selectedStudent.classroom?.name }})</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Filter Konten Agama</th><td>: {{ selectedStudent.religion?.name }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Filter Konten Gender</th><td>: {{ selectedStudent.gender?.name }} ({{ selectedStudent.gender?.code }})</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Tempat, Tanggal Lahir</th><td>: {{ selectedStudent.birth_place }}, {{ selectedStudent.birth_date }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Alamat Rumah Domisili</th><td>: {{ selectedStudent.address }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Nama Ayah & Ibu Kandung</th><td>: {{ selectedStudent.family?.father_name }} & {{ selectedStudent.family?.mother_name }}</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Asal SMP Kelulusan</th><td>: {{ selectedStudent.education_history?.school_name }} (Nilai: {{ selectedStudent.education_history?.final_score }})</td></tr>
                            <tr class="border-b"><th class="py-1.5 font-bold uppercase text-[10px]">Rekam Fisik Terakhir</th><td>: Tinggi {{ selectedStudent.health?.height }} cm / Berat {{ selectedStudent.health?.weight }} kg / Gol.Darah: {{ selectedStudent.health?.blood_type?.name || 'O' }}</td></tr>
                        </tbody>
                    </table>

                    <div class="pt-8 flex justify-between items-center text-center">
                        <div class="w-48">
                            <p>Mengetahui,</p>
                            <p class="font-bold mt-1">Orang Tua/Wali Murid</p>
                            <div class="h-16"></div>
                            <p class="border-b border-neutral-950 font-bold italic">{{ selectedStudent.family?.father_name || '............................' }}</p>
                        </div>
                        <div class="w-56">
                            <p>Kotabaru, {{ new Date().toISOString().substring(0,10) }}</p>
                            <p class="font-bold mt-1">Kepala Urusan Administrasi TU</p>
                            <div class="h-16"></div>
                            <p class="border-b border-neutral-950 font-bold italic">{{ authUser.name }}</p>
                            <p class="text-[9px] text-neutral-500 font-mono">NIP. 19850314 201012 1 002</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t pt-2 print:hidden">
                    <Button variant="outline" @click="isPrintViewOpen = false" class="h-8 text-xs font-bold rounded-lg">Tutup Lembar Cetak</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <StudentFormDialog 
            v-model:open="isFormOpen" 
            :student="selectedStudent" 
            @submit="handleFormSubmit" 
        />
    </div>
</template>

<style scoped>
.animation-fade {
    animation: fadeIn 0.25s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(2px); }
    to { opacity: 1; transform: translateY(0); }
}
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
}
</style>