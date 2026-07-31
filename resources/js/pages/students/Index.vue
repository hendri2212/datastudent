<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    Plus, 
    Pencil, 
    Trash2, 
    Search, 
    Users, 
    Archive,  
    Loader2,
    Building2,
    Heart,
    Eye,
    GraduationCap,
    FileCheck, 
    AlertTriangle, 
    User,
    UserCheck,
    Copy,
    Check,
    Share2,
    Shield,
    Trophy,
    FileText,
    Unlock,
    Lock,
    ExternalLink,
    Filter,
    RefreshCw,
    Phone,
    Mail,
    MapPin,
    CheckCircle2,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue'; 

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import StudentFormDialog from './StudentFormDialog.vue';

// Import Types Terpusat
import type { 
    Student, 
    School, 
    Major, 
    Classroom, 
    AcademicYear, 
    MasterOption, 
    MasterOptionCode, 
    EducationLevel, 
    SocialPlatform, 
    StudentDocument,
} from './types';

// Interface Paginator Laravel
interface PaginatedData<T> {
    data: T[];
    current_page?: number;
    last_page?: number;
    total?: number;
    per_page?: number;
    from?: number;
    to?: number;
    links?: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    students: Student[] | PaginatedData<Student>;
    schools?: School[];
    majors?: Major[];
    classrooms?: Classroom[];
    academicYears?: AcademicYear[];
    genders?: MasterOptionCode[];
    religions?: MasterOption[];
    studentStatuses?: MasterOption[];
    bloodTypes?: MasterOption[];
    citizenships?: MasterOption[];
    documentTypes?: MasterOption[];
    occupations?: MasterOption[];
    incomeCategories?: MasterOption[];
    relationshipTypes?: MasterOption[];
    educationLevels?: EducationLevel[];
    socialPlatforms?: SocialPlatform[];
    filters?: {
        search?: string;
        classroom_id?: string | number;
        major_id?: string | number;
        academic_year_id?: string | number;
        citizenship_id?: string | number;
        gender_id?: string | number;
        religion_id?: string | number;
        student_status_id?: string | number;
        blood_type_id?: string | number;
        tab?: string;
    };
}>();

// Normalisasi Array Siswa
const studentList = computed<Student[]>(() => {
    if (!props.students) {
        return [];
    }

    if (Array.isArray(props.students)) {
        return props.students.filter((s): s is Student => s !== null && s !== undefined);
    }

    if (Array.isArray(props.students.data)) {
        return props.students.data.filter((s): s is Student => s !== null && s !== undefined);
    }

    return [];
});

const paginationMeta = computed(() => {
    if (props.students && !Array.isArray(props.students)) {
        return props.students;
    }

    return null;
});

// --- STATISTIK KOMPREHENSIF ---
const totalStudentsCount = computed(() => {
    return paginationMeta.value?.total ?? studentList.value.length;
});

const verifiedStudentsCount = computed(() => {
    return studentList.value.filter(s => Boolean(s.verified_at || s.is_locked)).length;
});

const unverifiedStudentsCount = computed(() => {
    return studentList.value.filter(s => !Boolean(s.verified_at || s.is_locked)).length;
});

const genderStats = computed(() => {
    const counts: Record<string, number> = {};

    if (props.genders) {
        props.genders.forEach(g => {
            if (g?.name) {
                counts[g.name] = 0;
            }
        });
    }

    studentList.value.forEach(s => {
        const genderName = s.gender?.name || 'Lainnya';
        counts[genderName] = (counts[genderName] || 0) + 1;
    });

    return counts;
});

const totalAchievementCount = computed(() => {
    return studentList.value.reduce((total, student) => {
        return total + (student.achievements?.length || 0);
    }, 0);
});

const totalViolationPoints = computed(() => {
    return studentList.value.reduce((total, student) => {
        const points = student.violations?.reduce((sum, violation) => sum + (Number(violation.point) || 0), 0) || 0;

        return total + points;
    }, 0);
});

// State Reactive Filter
const searchQuery = ref(props.filters?.search || '');
const selectedClassroom = ref<string | number>(props.filters?.classroom_id || '');
const selectedMajor = ref<string | number>(props.filters?.major_id || '');
const selectedAcademicYear = ref<string | number>(props.filters?.academic_year_id || '');
const selectedCitizenship = ref<string | number>(props.filters?.citizenship_id || '');
const selectedGender = ref<string | number>(props.filters?.gender_id || '');
const selectedReligion = ref<string | number>(props.filters?.religion_id || '');
const selectedStudentStatus = ref<string | number>(props.filters?.student_status_id || '');
const selectedBloodType = ref<string | number>(props.filters?.blood_type_id || '');
const activeTab = ref(props.filters?.tab || 'active');

// Modal States
const isFilterModalOpen = ref(false);
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isDetailModalOpen = ref(false);

const selectedStudent = ref<Student | null>(null);
const isSubmitting = ref(false);
const copiedKey = ref<string | null>(null);

// Menghitung Jumlah Filter Aktif
const activeFilterCount = computed(() => {
    let count = 0;

    if (selectedClassroom.value) {
count++;
}

    if (selectedMajor.value) {
count++;
}

    if (selectedAcademicYear.value) {
count++;
}

    if (selectedCitizenship.value) {
count++;
}

    if (selectedGender.value) {
count++;
}

    if (selectedReligion.value) {
count++;
}

    if (selectedStudentStatus.value) {
count++;
}

    if (selectedBloodType.value) {
count++;
}

    return count;
});

// Helper Status Verifikasi Siswa
const isStudentVerified = (student: Student | null): boolean => {
    if (!student) {
return false;
}

    return Boolean(student.verified_at || student.is_locked);
};

// Handler Filter
let searchTimeout: ReturnType<typeof setTimeout>;
const handleFilterChange = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/students',
            {
                search: searchQuery.value,
                classroom_id: selectedClassroom.value,
                major_id: selectedMajor.value,
                academic_year_id: selectedAcademicYear.value,
                citizenship_id: selectedCitizenship.value,
                gender_id: selectedGender.value,
                religion_id: selectedReligion.value,
                student_status_id: selectedStudentStatus.value,
                blood_type_id: selectedBloodType.value,
                tab: activeTab.value,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 300);
};

watch([
    searchQuery,
    selectedClassroom,
    selectedMajor,
    selectedAcademicYear,
    selectedCitizenship,
    selectedGender,
    selectedReligion,
    selectedStudentStatus,
    selectedBloodType,
    activeTab,
], () => {
    handleFilterChange();
});

const handleResetFilters = () => {
    searchQuery.value = '';
    selectedClassroom.value = '';
    selectedMajor.value = '';
    selectedAcademicYear.value = '';
    selectedCitizenship.value = '';
    selectedGender.value = '';
    selectedReligion.value = '';
    selectedStudentStatus.value = '';
    selectedBloodType.value = '';
    activeTab.value = 'active';
    isFilterModalOpen.value = false;
};

// Modal Triggers
const handleOpenCreateModal = () => {
    selectedStudent.value = null;
    isFormModalOpen.value = true;
};

const handleOpenDetailModal = (student: Student) => {
    selectedStudent.value = student;
    isDetailModalOpen.value = true;
};

const handleOpenEditModal = (student: Student) => {
    selectedStudent.value = student;
    isFormModalOpen.value = true;
};

const handleOpenDeleteModal = (student: Student) => {
    selectedStudent.value = student;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (!selectedStudent.value?.id) {
return;
}
    
    isSubmitting.value = true;
    router.delete(`/students/${selectedStudent.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedStudent.value = null;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const handleVerifyStudent = (student: Student) => {
    if (!student?.id) {
return;
}

    router.post(`/students/${student.id}/verify`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleUnverifyStudent = (student: Student) => {
    if (!student?.id) {
return;
}

    router.post(`/students/${student.id}/unverify`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleRestore = (student: Student) => {
    if (!student?.id) {
return;
}

    router.post(`/students/${student.id}/restore`, {}, {
        preserveScroll: true,
    });
};

const handleForceDelete = (student: Student) => {
    if (!student?.id) {
return;
}

    if (!confirm('Yakin ingin menghapus siswa ini secara permanen? Tindakan ini tidak bisa dikembalikan.')) {
        return;
    }

    router.delete(`/students/${student.id}/force-delete`, {
        preserveScroll: true,
    });
};

// Clipboard Helpers
const copyToClipboard = (text: string | undefined | null, key: string) => {
    if (!text) {
return;
}

    navigator.clipboard.writeText(text).then(() => {
        copiedKey.value = key;
        setTimeout(() => {
            copiedKey.value = null;
        }, 2000);
    });
};

const copySummaryText = (student: Student | null) => {
    if (!student) {
return;
}

    const summary = `
=== DETAIL DATA SISWA ===
Nama Lengkap    : ${student.full_name || '-'} ${student.nickname ? `(${student.nickname})` : ''}
NISN            : ${student.nisn || '-'}
NIS             : ${student.nis || '-'}
Kelas           : ${student.classroom?.name || '-'}
Jurusan         : ${student.major?.name || '-'}
Tahun Ajaran    : ${student.academic_year?.name || '-'}
Jenis Kelamin   : ${student.gender?.name || '-'}
Agama           : ${student.religion?.name || '-'}
Kewarganegaraan : ${student.citizenship?.name || student.citizenship_id || '-'}
No. Telepon     : ${student.phone || '-'}
Email           : ${student.email || '-'}
Alamat          : ${student.address || '-'}
Kode Pos        : ${student.postal_code || '-'}
Status Siswa    : ${student.student_status?.name || '-'}
Status Verifikasi: ${isStudentVerified(student) ? 'Sudah Terverifikasi' : 'Belum Terverifikasi'}
    `.trim();

    copyToClipboard(summary, 'summary_' + student.id);
};

// Helpers Media Sosial URL
const formatSocialUrl = (url?: string, username?: string, platform?: string) => {
    if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
        return url;
    }

    if (url) {
        return `https://${url}`;
    }

    if (username && platform) {
        const plat = platform.toLowerCase();

        if (plat.includes('instagram')) {
return `https://instagram.com/${username.replace('@', '')}`;
}

        if (plat.includes('facebook')) {
return `https://facebook.com/${username}`;
}

        if (plat.includes('twitter') || plat.includes('x')) {
return `https://x.com/${username.replace('@', '')}`;
}

        if (plat.includes('tiktok')) {
return `https://tiktok.com/@${username.replace('@', '')}`;
}

        if (plat.includes('linkedin')) {
return `https://linkedin.com/in/${username}`;
}
    }

    return '#';
};

const getDocumentPreviewUrl = (doc: StudentDocument) => {
    if (!selectedStudent.value?.id || !doc.id) {
return '#';
}

    return `/students/${selectedStudent.value.id}/documents/${doc.id}/preview`;
};

const getDocumentDownloadUrl = (doc: StudentDocument) => {
    if (!selectedStudent.value?.id || !doc.id) {
return '#';
}

    return `/students/${selectedStudent.value.id}/documents/${doc.id}/download`;
};

const shareDocument = async (doc: StudentDocument) => {
    const url = getDocumentPreviewUrl(doc);

    if (!url) {
return;
}

    const shareData = {
        title: doc.original_name || 'Dokumen Siswa',
        text: `Lihat berkas/dokumen resmi siswa: ${doc.original_name || 'Dokumen'}`,
        url: window.location.origin + url,
    };

    if (navigator.share) {
        try {
            await navigator.share(shareData);
       } catch (err) {
            console.error('Terjadi kesalahan:', err);
        }
    } else {
        copyToClipboard(shareData.url, `doc_share_${doc.id}`);
        alert('Tautan dokumen telah disalin. Anda dapat menempelkannya (paste) ke media sosial.');
    }
};
</script>

<template>
    <Head title="Manajemen Data Siswa" />

    <div class="p-3 sm:p-6 space-y-4 sm:space-y-6 max-w-7xl mx-auto w-full overflow-x-hidden">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-neutral-200 dark:border-neutral-800 pb-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                    <GraduationCap class="h-6 w-6 sm:h-7 sm:w-7 text-blue-600 shrink-0" />
                    <span>Manajemen Data Siswa</span>
                </h1>
                <p class="text-xs sm:text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                    Pusat pengelolaan biodata, riwayat akademis, dokumen resmi, dan statistik distribusi siswa.
                </p>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <Button @click="handleOpenCreateModal" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-sm font-semibold text-xs sm:text-sm h-9 sm:h-10">
                    <Plus class="h-4 w-4" />
                    <span>Tambah Siswa Baru</span>
                </Button>
            </div>
        </div>

        <!-- Statistik Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3">
            <div class="bg-white dark:bg-neutral-900 p-3 sm:p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-neutral-500">Total Siswa</p>
                    <h3 class="text-lg sm:text-2xl font-extrabold text-neutral-900 dark:text-neutral-100">{{ totalStudentsCount }}</h3>
                    <p class="text-[10px] sm:text-[11px] text-emerald-600 font-medium mt-0.5 flex items-center gap-1">
                        <CheckCircle2 class="h-3 w-3 shrink-0" /> <span class="truncate">{{ verifiedStudentsCount }} Terverifikasi</span>
                    </p>
                </div>
                <div class="p-2.5 sm:p-3 bg-blue-50 dark:bg-blue-950/50 rounded-xl text-blue-600 shrink-0"><Users class="h-5 w-5 sm:h-6 sm:w-6" /></div>
            </div>

            <div class="bg-white dark:bg-neutral-900 p-3 sm:p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-neutral-500">Belum Verifikasi</p>
                    <h3 class="text-lg sm:text-2xl font-extrabold text-amber-600">{{ unverifiedStudentsCount }}</h3>
                    <p class="text-[10px] sm:text-[11px] text-neutral-400 mt-0.5 truncate">Membutuhkan Tinjauan</p>
                </div>
                <div class="p-2.5 sm:p-3 bg-amber-50 dark:bg-amber-950/50 rounded-xl text-amber-600 shrink-0"><Lock class="h-5 w-5 sm:h-6 sm:w-6" /></div>
            </div>

            <div class="bg-white dark:bg-neutral-900 p-3 sm:p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-neutral-500">Total Prestasi</p>
                    <h3 class="text-lg sm:text-2xl font-extrabold text-indigo-600">{{ totalAchievementCount }}</h3>
                    <p class="text-[10px] sm:text-[11px] text-neutral-400 mt-0.5 truncate">Penghargaan Siswa</p>
                </div>
                <div class="p-2.5 sm:p-3 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl text-indigo-600 shrink-0"><Trophy class="h-5 w-5 sm:h-6 sm:w-6" /></div>
            </div>

            <div class="bg-white dark:bg-neutral-900 p-3 sm:p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-neutral-500">Poin Pelanggaran</p>
                    <h3 class="text-lg sm:text-2xl font-extrabold text-rose-600">{{ totalViolationPoints }}</h3>
                    <p class="text-[10px] sm:text-[11px] text-neutral-400 mt-0.5 truncate">Akumulasi Poin</p>
                </div>
                <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/50 rounded-xl text-rose-600 shrink-0"><Shield class="h-5 w-5 sm:h-6 sm:w-6" /></div>
            </div>

            <div class="bg-white dark:bg-neutral-900 p-3 sm:p-4 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm flex flex-col justify-between col-span-2 lg:col-span-1">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-neutral-500 mb-1">Distribusi Gender</p>
                    <div class="flex flex-wrap gap-1.5 text-[11px] sm:text-xs font-semibold">
                        <span v-for="(count, gender) in genderStats" :key="gender" class="bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-md text-neutral-700 dark:text-neutral-300">
                            {{ gender }}: <strong class="text-blue-600">{{ count }}</strong>
                        </span>
                    </div>
                </div>
                <div v-if="activeFilterCount > 0" class="mt-2">
                    <Badge variant="secondary" class="text-[9px] sm:text-[10px] bg-blue-50 text-blue-700 border border-blue-200">
                        {{ activeFilterCount }} Filter Aktif
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-neutral-200 dark:border-neutral-800 pb-2">
            <div class="flex items-center gap-4 text-xs sm:text-sm font-medium border-b border-neutral-100 dark:border-neutral-800 md:border-none pb-2 md:pb-0">
                <button
                    type="button"
                    @click="activeTab = 'active'"
                    :class="['pb-2 md:pb-0 border-b-2 transition-all flex items-center gap-1.5', activeTab === 'active' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300']"
                >
                    <Users class="h-4 w-4" /> Siswa Aktif
                </button>
                <button
                    type="button"
                    @click="activeTab = 'trashed'"
                    :class="['pb-2 md:pb-0 border-b-2 transition-all flex items-center gap-1.5', activeTab === 'trashed' ? 'border-red-600 text-red-600 font-bold' : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300']"
                >
                    <Archive class="h-4 w-4" /> Tempat Sampah
                </button>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="relative w-full sm:w-64 md:w-72">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
                    <Input v-model="searchQuery" placeholder="Cari Nama, NISN, NIS, Email..." class="pl-9 h-9 text-xs" />
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="isFilterModalOpen = true" class="flex-1 sm:flex-none h-9 text-xs border-neutral-300 flex items-center justify-center gap-1.5">
                        <Filter class="h-3.5 w-3.5 text-neutral-500" /> 
                        <span>Filter</span>
                        <Badge v-if="activeFilterCount > 0" class="ml-0.5 h-4 px-1 text-[9px] bg-blue-600 text-white rounded-full">{{ activeFilterCount }}</Badge>
                    </Button>
                    <Button v-if="activeFilterCount > 0 || searchQuery" variant="ghost" size="sm" @click="handleResetFilters" class="h-9 text-xs text-rose-600 hover:bg-rose-50 px-2 sm:px-3">
                        <RefreshCw class="h-3.5 w-3.5 sm:mr-1" /> <span class="hidden sm:inline">Reset</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                <table class="w-full text-left text-xs sm:text-sm whitespace-nowrap min-w-[640px]">
                    <thead class="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-600 dark:text-neutral-400 font-semibold border-b border-neutral-200 dark:border-neutral-800">
                        <tr>
                            <th class="p-3 pl-4 text-[11px] tracking-wide uppercase">Siswa & Biodata</th>
                            <th class="p-3 text-[11px] tracking-wide uppercase">Akademik</th>
                            <th class="p-3 text-[11px] tracking-wide uppercase">Kontak & Alamat</th>
                            <th class="p-3 text-[11px] tracking-wide uppercase">Status & Verifikasi</th>
                            <th class="p-3 pr-4 text-[11px] tracking-wide uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr v-if="studentList.length === 0">
                            <td colspan="5" class="p-8 sm:p-12 text-center text-neutral-500 text-xs sm:text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Users class="h-8 w-8 sm:h-10 sm:w-10 text-neutral-300" />
                                    <p class="font-semibold text-neutral-700 dark:text-neutral-300">Data siswa tidak ditemukan.</p>
                                    <p class="text-xs text-neutral-400">Coba ubah filter atau kata kunci pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="student in studentList" :key="student.id" class="hover:bg-blue-50/40 dark:hover:bg-neutral-800/40 transition-colors">
                            <td class="p-3 pl-4">
                                <div class="flex items-center gap-2.5 sm:gap-3">
                                    <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-xs border border-blue-200 shrink-0">
                                        {{ student.full_name?.charAt(0)?.toUpperCase() || 'S' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-neutral-900 dark:text-neutral-100 text-xs sm:text-[13px] flex items-center gap-1.5 flex-wrap">
                                            <span>{{ student.full_name }}</span>
                                            <span v-if="student.nickname" class="text-neutral-400 font-normal italic text-[11px]">({{ student.nickname }})</span>
                                        </div>
                                        <div class="text-[10px] sm:text-[11px] text-neutral-500 flex items-center gap-1.5 mt-0.5">
                                            <span>{{ student.gender?.name || 'Gender (-)' }}</span>
                                            <span>•</span>
                                            <span>{{ student.religion?.name || 'Agama (-)' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                    <Badge variant="outline" class="text-[10px] font-bold bg-blue-50 text-blue-700 border-blue-200 px-1.5 py-0.5 rounded-md">
                                        {{ student.classroom?.name || 'Tanpa Kelas' }}
                                    </Badge>
                                    <span class="text-[10px] text-neutral-600 bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 rounded-md dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300">
                                        {{ student.major?.name || 'Umum' }}
                                    </span>
                                </div>
                                <div class="text-[10px] sm:text-[11px] text-neutral-500 font-mono">
                                    NISN: <span class="text-neutral-800 dark:text-neutral-200 font-semibold">{{ student.nisn || '-' }}</span> | NIS: <span class="text-neutral-800 dark:text-neutral-200 font-semibold">{{ student.nis || '-' }}</span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="text-[11px] sm:text-[12px] font-medium text-neutral-800 dark:text-neutral-200 flex items-center gap-1">
                                    <Phone class="h-3 w-3 text-neutral-400 shrink-0" />
                                    <span>{{ student.phone || '-' }}</span>
                                </div>
                                <div class="text-[10px] sm:text-[11px] text-neutral-500 truncate max-w-[140px] sm:max-w-[160px] flex items-center gap-1 mt-0.5" :title="student.email || '-'">
                                    <Mail class="h-3 w-3 text-neutral-400 shrink-0" />
                                    <span class="truncate">{{ student.email || '-' }}</span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <div :class="['w-2 h-2 rounded-full shrink-0', isStudentVerified(student) ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500']"></div>
                                    <span :class="['text-[10px] sm:text-[11px] font-semibold', isStudentVerified(student) ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">
                                        {{ isStudentVerified(student) ? 'Terverifikasi' : 'Belum Diverifikasi' }}
                                    </span>
                                </div>
                                <div class="text-[10px] sm:text-[11px] text-neutral-500">{{ student.student_status?.name || 'Status (-)' }}</div>
                            </td>
                            <td class="p-3 pr-4 text-center">
                                <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                                    <Button variant="ghost" size="icon" @click="handleOpenDetailModal(student)" title="Lihat Detail Lengkap" class="h-7 w-7 sm:h-8 sm:w-8 text-blue-600 hover:bg-blue-50 rounded-lg">
                                        <Eye class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    </Button>

                                    <template v-if="activeTab === 'active'">
                                        <Button v-if="!isStudentVerified(student)" variant="ghost" size="icon" @click="handleVerifyStudent(student)" title="Verifikasi Data Siswa" class="h-7 w-7 sm:h-8 sm:w-8 text-emerald-600 hover:bg-emerald-50 rounded-lg">
                                            <Check class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>
                                        <Button v-else variant="ghost" size="icon" @click="handleUnverifyStudent(student)" title="Batalkan Verifikasi" class="h-7 w-7 sm:h-8 sm:w-8 text-slate-500 hover:bg-slate-100 rounded-lg">
                                            <Unlock class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>

                                        <Button variant="ghost" size="icon" @click="handleOpenEditModal(student)" title="Edit Data" class="h-7 w-7 sm:h-8 sm:w-8 text-amber-600 hover:bg-amber-50 rounded-lg">
                                            <Pencil class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="handleOpenDeleteModal(student)" title="Hapus Siswa" class="h-7 w-7 sm:h-8 sm:w-8 text-rose-600 hover:bg-rose-50 rounded-lg">
                                            <Trash2 class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>
                                    </template>
                                    <template v-else>
                                        <Button variant="ghost" size="icon" @click="handleRestore(student)" title="Pulihkan Data" class="h-7 w-7 sm:h-8 sm:w-8 text-emerald-600 hover:bg-emerald-50 rounded-lg">
                                            <UserCheck class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="handleForceDelete(student)" title="Hapus Permanen" class="h-7 w-7 sm:h-8 sm:w-8 text-rose-600 hover:bg-rose-50 rounded-lg">
                                            <Trash2 class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </Button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationMeta && paginationMeta.links" class="p-3 sm:p-4 border-t border-neutral-200 dark:border-neutral-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-500">
                <div class="text-center sm:text-left">
                    Menampilkan <strong>{{ paginationMeta.from || 0 }}</strong> - <strong>{{ paginationMeta.to || 0 }}</strong> dari <strong>{{ paginationMeta.total || 0 }}</strong> siswa
                </div>
                <div class="flex flex-wrap justify-center items-center gap-1">
                    <template v-for="(link, idx) in paginationMeta.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-md border transition-colors text-[11px] sm:text-xs',
                                link.active 
                                    ? 'bg-blue-600 text-white border-blue-600 font-bold' 
                                    : 'bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50'
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-2.5 py-1 sm:px-3 sm:py-1.5 text-neutral-300 dark:text-neutral-600 border border-transparent text-[11px] sm:text-xs"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <Dialog :open="isFilterModalOpen" @update:open="isFilterModalOpen = $event">
        <DialogContent class="w-[95vw] sm:max-w-lg rounded-xl p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-base sm:text-lg font-bold">
                    <Filter class="h-4 w-4 sm:h-5 sm:w-5 text-blue-600 shrink-0" />
                    <span>Filter Lanjutan Data Siswa</span>
                </DialogTitle>
                <DialogDescription class="text-xs sm:text-sm">
                    Pilih kriteria spesifik di bawah ini untuk menyaring data siswa secara tepat.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 py-3">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Kelas</label>
                    <select v-model="selectedClassroom" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Kelas</option><option v-for="c in props.classrooms" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Jurusan</label>
                    <select v-model="selectedMajor" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Jurusan</option><option v-for="m in props.majors" :key="m.id" :value="m.id">{{ m.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Tahun Ajaran</label>
                    <select v-model="selectedAcademicYear" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Tahun Ajaran</option><option v-for="ay in props.academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Status Siswa</label>
                    <select v-model="selectedStudentStatus" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Status</option><option v-for="st in props.studentStatuses" :key="st.id" :value="st.id">{{ st.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Jenis Kelamin</label>
                    <select v-model="selectedGender" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Jenis Kelamin</option><option v-for="g in props.genders" :key="g.id" :value="g.id">{{ g.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Agama</label>
                    <select v-model="selectedReligion" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Agama</option><option v-for="r in props.religions" :key="r.id" :value="r.id">{{ r.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Kewarganegaraan</label>
                    <select v-model="selectedCitizenship" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Kewarganegaraan</option><option v-for="c in props.citizenships" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">Golongan Darah</label>
                    <select v-model="selectedBloodType" class="w-full border rounded-lg p-2 text-xs bg-background border-input focus:ring-2 focus:ring-blue-500"><option value="">Semua Golongan Darah</option><option v-for="bt in props.bloodTypes" :key="bt.id" :value="bt.id">{{ bt.name }}</option></select>
                </div>
            </div>

            <DialogFooter class="flex flex-row justify-between items-center gap-2 pt-3 border-t dark:border-neutral-800">
                <Button variant="ghost" size="sm" @click="handleResetFilters" class="text-xs">Reset Filter</Button>
                <Button size="sm" @click="isFilterModalOpen = false" class="bg-blue-600 text-white text-xs">Terapkan Filter</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Student Form Dialog -->
    <StudentFormDialog
        :show="isFormModalOpen"
        :student="selectedStudent"
        :schools="props.schools"
        :majors="props.majors"
        :classrooms="props.classrooms"
        :academic-years="props.academicYears"
        :genders="props.genders"
        :religions="props.religions"
        :student-statuses="props.studentStatuses"
        :blood-types="props.bloodTypes"
        :citizenships="props.citizenships"
        :occupations="props.occupations"
        :income-categories="props.incomeCategories"
        :relationship-types="props.relationshipTypes"
        :education-levels="props.educationLevels"
        :social-platforms="props.socialPlatforms"
        :document-types="props.documentTypes"
        @close="isFormModalOpen = false"
    />

    <!-- Detail Modal -->
    <Dialog :open="isDetailModalOpen" @update:open="isDetailModalOpen = $event">
        <DialogContent class="w-[95vw] sm:max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl p-4 sm:p-6">
            <DialogHeader class="border-b pb-3 dark:border-neutral-800">
                <DialogTitle class="text-lg sm:text-xl flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <User class="h-5 w-5 text-blue-600 shrink-0" /> Profil & Data Lengkap Siswa
                    </span>
                </DialogTitle>
                <DialogDescription class="text-xs sm:text-sm">
                    Rincian komprehensif seluruh data siswa beserta dokumen dan riwayat aktivitas.
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedStudent" class="space-y-5 sm:space-y-6 pt-2">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-neutral-800 dark:to-neutral-900 p-3.5 sm:p-4 rounded-xl border border-blue-100 dark:border-neutral-700 flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                            {{ selectedStudent.full_name }}
                        </h3>
                        <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-1">
                            Panggilan: <span class="font-semibold">{{ selectedStudent.nickname || '-' }}</span> | 
                            Gender: <span class="font-semibold">{{ selectedStudent.gender?.name || '-' }}</span>
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-2">
                            <Badge class="bg-blue-600 text-white text-[10px] sm:text-xs">{{ selectedStudent.classroom?.name || 'Tanpa Kelas' }}</Badge>
                            <Badge variant="secondary" class="text-[10px] sm:text-xs">{{ selectedStudent.major?.name || 'Umum' }}</Badge>
                            <Badge variant="outline" class="text-[10px] sm:text-xs">{{ selectedStudent.school?.name || 'Sekolah (-)' }}</Badge>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" @click="copySummaryText(selectedStudent)" class="bg-white dark:bg-neutral-800 flex items-center gap-1.5 self-start md:self-auto shadow-sm text-xs h-8">
                        <Check v-if="copiedKey === 'summary_' + selectedStudent.id" class="h-3.5 w-3.5 text-emerald-600" />
                        <Copy v-else class="h-3.5 w-3.5" />
                        <span>{{ copiedKey === 'summary_' + selectedStudent.id ? 'Tersalin!' : 'Salin Profil' }}</span>
                    </Button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                    <div class="border p-3 sm:p-3.5 rounded-xl flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900 dark:border-neutral-800">
                        <div>
                            <span class="text-[9px] sm:text-[10px] text-neutral-500 uppercase font-bold tracking-wider">NISN</span>
                            <p class="font-mono text-sm sm:text-base font-bold text-blue-600 dark:text-blue-400 mt-0.5">{{ selectedStudent.nisn || '-' }}</p>
                        </div>
                        <Button variant="ghost" size="sm" @click="copyToClipboard(selectedStudent.nisn, 'nisn')" :disabled="!selectedStudent.nisn" class="h-8 w-8 p-0">
                            <Check v-if="copiedKey === 'nisn'" class="h-4 w-4 text-emerald-600" />
                            <Copy v-else class="h-4 w-4 text-neutral-500" />
                        </Button>
                    </div>
                    <div class="border p-3 sm:p-3.5 rounded-xl flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900 dark:border-neutral-800">
                        <div>
                            <span class="text-[9px] sm:text-[10px] text-neutral-500 uppercase font-bold tracking-wider">NIS</span>
                            <p class="font-mono text-sm sm:text-base font-bold text-neutral-800 dark:text-neutral-200 mt-0.5">{{ selectedStudent.nis || '-' }}</p>
                        </div>
                        <Button variant="ghost" size="sm" @click="copyToClipboard(selectedStudent.nis, 'nis')" :disabled="!selectedStudent.nis" class="h-8 w-8 p-0">
                            <Check v-if="copiedKey === 'nis'" class="h-4 w-4 text-emerald-600" />
                            <Copy v-else class="h-4 w-4 text-neutral-500" />
                        </Button>
                    </div>
                </div>

                <!-- Identitas Pribadi -->
                <div class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <User class="h-3.5 w-3.5 text-blue-500" /> Identitas & Biodata Pribadi
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3 text-xs">
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Tempat, Tanggal Lahir</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                                {{ selectedStudent.birth_place || '-' }}{{ selectedStudent.birth_date ? `, ${selectedStudent.birth_date}` : '' }}
                            </span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Jenis Kelamin</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.gender?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Agama</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.religion?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Kewarganegaraan</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.citizenship?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Status Siswa</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.student_status?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Tahun Ajaran</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.academic_year?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Kontak & Alamat -->
                <div class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <MapPin class="h-3.5 w-3.5 text-emerald-500" /> Kontak & Alamat Tempat Tinggal
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-xs">
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 flex justify-between items-center">
                            <div>
                                <span class="text-neutral-400 block mb-0.5 flex items-center gap-1"><Phone class="h-3 w-3" /> No. Telepon / WhatsApp</span>
                                <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.phone || '-' }}</span>
                            </div>
                            <Button v-if="selectedStudent.phone" variant="ghost" size="icon" class="h-6 w-6" @click="copyToClipboard(selectedStudent.phone, 'phone')">
                                <Check v-if="copiedKey === 'phone'" class="h-3.5 w-3.5 text-emerald-600" /><Copy v-else class="h-3.5 w-3.5 text-neutral-400" />
                            </Button>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 flex justify-between items-center">
                            <div>
                                <span class="text-neutral-400 block mb-0.5 flex items-center gap-1"><Mail class="h-3 w-3" /> Email Siswa</span>
                                <span class="font-semibold text-neutral-800 dark:text-neutral-200 truncate block max-w-[180px] sm:max-w-none">{{ selectedStudent.email || '-' }}</span>
                            </div>
                            <Button v-if="selectedStudent.email" variant="ghost" size="icon" class="h-6 w-6" @click="copyToClipboard(selectedStudent.email, 'email')">
                                <Check v-if="copiedKey === 'email'" class="h-3.5 w-3.5 text-emerald-600" /><Copy v-else class="h-3.5 w-3.5 text-neutral-400" />
                            </Button>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 sm:col-span-2">
                            <span class="text-neutral-400 block mb-0.5">Alamat Tempat Tinggal Lengkap</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.address || '-' }}</span>
                            <span v-if="selectedStudent.postal_code" class="text-neutral-500 ml-2">(Kode Pos: {{ selectedStudent.postal_code }})</span>
                        </div>
                    </div>
                </div>

                <!-- Lembaga Akademik -->
                <div class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Building2 class="h-3.5 w-3.5 text-indigo-500" /> Lembaga Pendidikan & Akademik
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 text-xs">
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Sekolah</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.school?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Jurusan</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.major?.name || '-' }}</span>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800">
                            <span class="text-neutral-400 block mb-0.5">Kelas</span>
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.classroom?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Orang Tua & Wali -->
                <div v-if="selectedStudent.family" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Users class="h-3.5 w-3.5 text-purple-500" /> Data Orang Tua & Wali
                    </h4>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5 sm:gap-3 text-xs">
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-neutral-50/30 dark:bg-neutral-900/40 space-y-1">
                            <span class="text-blue-600 font-bold block border-b pb-1">DATA AYAH</span>
                            <p><span class="text-neutral-400">Nama:</span> <span class="font-semibold">{{ selectedStudent.family.father_name || '-' }}</span></p>
                            <p><span class="text-neutral-400">No. HP:</span> {{ selectedStudent.family.father_phone || '-' }}</p>
                            <p><span class="text-neutral-400">Pekerjaan:</span> {{ selectedStudent.family.father_occupation?.name || '-' }}</p>
                            <p><span class="text-neutral-400">Penghasilan:</span> {{ selectedStudent.family.father_income_category?.name || '-' }}</p>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-neutral-50/30 dark:bg-neutral-900/40 space-y-1">
                            <span class="text-purple-600 font-bold block border-b pb-1">DATA IBU</span>
                            <p><span class="text-neutral-400">Nama:</span> <span class="font-semibold">{{ selectedStudent.family.mother_name || '-' }}</span></p>
                            <p><span class="text-neutral-400">No. HP:</span> {{ selectedStudent.family.mother_phone || '-' }}</p>
                            <p><span class="text-neutral-400">Pekerjaan:</span> {{ selectedStudent.family.mother_occupation?.name || '-' }}</p>
                            <p><span class="text-neutral-400">Penghasilan:</span> {{ selectedStudent.family.mother_income_category?.name || '-' }}</p>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-neutral-50/30 dark:bg-neutral-900/40 space-y-1">
                            <span class="text-amber-600 font-bold block border-b pb-1">DATA WALI</span>
                            <p><span class="text-neutral-400">Nama:</span> <span class="font-semibold">{{ selectedStudent.family.guardian_name || '-' }}</span></p>
                            <p><span class="text-neutral-400">No. HP:</span> {{ selectedStudent.family.guardian_phone || '-' }}</p>
                            <p><span class="text-neutral-400">Pekerjaan:</span> {{ selectedStudent.family.guardian_occupation?.name || '-' }}</p>
                            <p><span class="text-neutral-400">Penghasilan:</span> {{ selectedStudent.family.guardian_income_category?.name || '-' }}</p>
                        </div>
                        <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 lg:col-span-3 space-y-1">
                            <span class="text-rose-600 font-bold block border-b pb-1">KONTAK DARURAT & CATATAN</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                <p><span class="text-neutral-400">Kontak Darurat:</span> {{ selectedStudent.family.emergency_contact_name || '-' }}</p>
                                <p><span class="text-neutral-400">No. HP Darurat:</span> {{ selectedStudent.family.emergency_contact_phone || '-' }}</p>
                                <p><span class="text-neutral-400">Hubungan:</span> {{ selectedStudent.family.relationship_type?.name || '-' }}</p>
                            </div>
                            <p v-if="selectedStudent.family.notes" class="pt-1 text-neutral-500"><span class="text-neutral-400">Catatan:</span> {{ selectedStudent.family.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Kesehatan -->
                <div v-if="selectedStudent.health" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Heart class="h-3.5 w-3.5 text-red-500" /> Rekam Kesehatan Siswa
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 text-xs">
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800"><span class="text-neutral-400 block">Gol. Darah</span><span class="font-bold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.blood_type?.name || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800"><span class="text-neutral-400 block">Tinggi Badan</span><span class="font-bold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.height || '-' }} cm</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800"><span class="text-neutral-400 block">Berat Badan</span><span class="font-bold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.weight || '-' }} kg</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800"><span class="text-neutral-400 block">RS Rujukan</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.hospital || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800 col-span-2"><span class="text-neutral-400 block">Dokter Penanggung Jawab</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.doctor || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800 col-span-2"><span class="text-neutral-400 block">Riwayat Penyakit / Alergi</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.medical_history || selectedStudent.health.allergies || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800 col-span-2"><span class="text-neutral-400 block">Kebutuhan Khusus</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.disabilities || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800 col-span-2"><span class="text-neutral-400 block">Konsumsi Obat Rutin</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.medications || '-' }}</span></div>
                        <div class="p-2.5 border rounded-lg dark:border-neutral-800 col-span-2 sm:col-span-4"><span class="text-neutral-400 block">Catatan Kesehatan</span><span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ selectedStudent.health.notes || '-' }}</span></div>
                    </div>
                </div>

                <!-- Riwayat Pendidikan -->
                <div v-if="selectedStudent.education_histories?.length" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <GraduationCap class="h-3.5 w-3.5 text-amber-500" /> Riwayat Pendidikan Sebelumnya
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div v-for="(edu, index) in selectedStudent.education_histories" :key="edu.id || index" class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-neutral-50/30 dark:bg-neutral-900/30">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-bold text-xs sm:text-sm text-neutral-900 dark:text-neutral-100">{{ edu.school_name || '-' }}</p>
                                    <p class="text-neutral-500 text-[10px] sm:text-[11px]">{{ edu.education_level?.name || 'Jenjang tidak ditentukan' }}</p>
                                </div>
                                <Badge :variant="edu.is_graduated ? 'default' : 'secondary'" class="text-[10px]">{{ edu.is_graduated ? 'Lulus' : 'Belum Lulus' }}</Badge>
                            </div>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2 text-[10px] sm:text-[11px] text-neutral-600 dark:text-neutral-400">
                                <span>Tahun Masuk: <strong>{{ edu.entry_year || '-' }}</strong></span>
                                <span>Tahun Lulus: <strong>{{ edu.graduation_year || '-' }}</strong></span>
                                <span>NPSN: <strong>{{ edu.npsn || '-' }}</strong></span>
                                <span>Nilai Akhir: <strong>{{ edu.final_score || '-' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Sosial -->
                <div v-if="selectedStudent.socials?.length" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Share2 class="h-3.5 w-3.5 text-cyan-500" /> Media Sosial
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div v-for="(soc, index) in selectedStudent.socials" :key="soc.id || index" class="p-2.5 border rounded-lg dark:border-neutral-800 flex items-center justify-between gap-2">
                            <div class="overflow-hidden">
                                <p class="font-bold text-neutral-800 dark:text-neutral-200">{{ soc.social_platform?.name || 'Platform' }}</p>
                                <p class="text-neutral-500 text-[10px] font-mono truncate">{{ soc.username || soc.url || '-' }}</p>
                                <a :href="formatSocialUrl(soc.url, soc.username, soc.social_platform?.name)" target="_blank" rel="noreferrer noopener" class="text-blue-600 hover:underline text-[10px] sm:text-[11px] font-medium flex items-center gap-1 mt-1 truncate"><span class="truncate">{{ soc.url || 'Buka Profil' }}</span><ExternalLink class="h-3 w-3 shrink-0" /></a>
                            </div>
                            <Button v-if="soc.url" variant="ghost" size="icon" class="h-6 w-6 shrink-0" @click="copyToClipboard(soc.url, 'soc_' + index)">
                                <Check v-if="copiedKey === 'soc_' + index" class="h-3 w-3 text-emerald-600" />
                                <Copy v-else class="h-3 w-3 text-neutral-400" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Prestasi -->
                <div v-if="selectedStudent.achievements?.length" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Trophy class="h-3.5 w-3.5 text-amber-500" /> Prestasi Siswa
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div v-for="(ach, index) in selectedStudent.achievements" :key="ach.id || index" class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-amber-50/20 dark:bg-amber-950/10">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-bold text-neutral-900 dark:text-neutral-100">{{ ach.title }}</p>
                                <Badge variant="outline" class="bg-amber-100 text-amber-800 border-amber-300 text-[10px] shrink-0">Rank: {{ ach.rank || '-' }}</Badge>
                            </div>
                            <p class="text-neutral-500 text-[10px] sm:text-[11px] mt-1">
                                Penyelenggara: {{ ach.organizer || '-' }} | Tingkat: {{ ach.level || '-' }} | Tanggal: {{ ach.achievement_date || '-' }}
                            </p>
                            <p v-if="ach.description" class="text-neutral-600 dark:text-neutral-300 text-[10px] sm:text-[11px] mt-1">{{ ach.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pelanggaran -->
                <div v-if="selectedStudent.violations?.length" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <Shield class="h-3.5 w-3.5 text-rose-500" /> Catatan Pelanggaran Kedisiplinan
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div v-for="(vio, index) in selectedStudent.violations" :key="vio.id || index" class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-rose-50/20 dark:bg-rose-950/10">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-bold text-neutral-900 dark:text-neutral-100">{{ vio.title }}</p>
                                <Badge variant="destructive" class="text-[10px] shrink-0">Poin: {{ vio.point ?? '-' }}</Badge>
                            </div>
                            <p class="text-neutral-500 text-[10px] sm:text-[11px] mt-1">Tanggal: {{ vio.violation_date || '-' }}</p>
                            <p v-if="vio.description" class="text-neutral-600 dark:text-neutral-300 text-[10px] sm:text-[11px] mt-1">{{ vio.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Berkas & Dokumen -->
                <div v-if="selectedStudent.documents?.length" class="space-y-2.5 sm:space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-1.5 border-b pb-1">
                        <FileText class="h-3.5 w-3.5 text-blue-500" /> Berkas & Dokumen Resmi
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div v-for="doc in selectedStudent.documents" :key="doc.id" class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                            <div>
                                <p class="font-bold text-neutral-900 dark:text-neutral-100 text-xs">
        {{ doc.notes || doc.notes || doc.original_name || 'Dokumen' }}
    </p>

    <!-- Original File Name (Pudar/Muted) -->
    <p v-if="doc.notes || doc.notes" class="text-[10px] text-neutral-400 dark:text-neutral-500 truncate max-w-[200px] sm:max-w-[300px]">
        File: {{ doc.original_name }}
    </p>
                                <p class="text-neutral-500 text-[10px] sm:text-[11px]">{{ doc.document_type?.name || 'Tipe Berkas' }} • {{ doc.mime_type || '-' }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 self-end sm:self-auto">
                                <a :href="getDocumentPreviewUrl(doc)" target="_blank" class="px-2.5 py-1 border rounded-md text-[11px] font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800">Pratinjau</a>
                                <a :href="getDocumentDownloadUrl(doc)" target="_blank" class="px-2.5 py-1 bg-blue-600 text-white rounded-md text-[11px] font-medium hover:bg-blue-700">Unduh</a>
                                <Button type="button" variant="outline" size="sm" @click="shareDocument(doc)" class="h-7 px-2 text-[11px] flex items-center gap-1 text-neutral-700 dark:text-neutral-300 border-neutral-300 dark:border-neutral-700" title="Bagikan ke Sosmed">
                                    <Share2 class="h-3 w-3 text-blue-600" />
                                    <span>Bagikan</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Verifikasi -->
                <div class="p-2.5 sm:p-3 border rounded-lg dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 text-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-1.5">
                    <div class="flex items-center gap-2">
                        <FileCheck class="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>Verifikator: <strong>{{ selectedStudent.verifier?.name || 'Belum Diverifikasi' }}</strong></span>
                    </div>
                    <div>
                        <span>Waktu Verifikasi: <strong>{{ selectedStudent.verified_at || '-' }}</strong></span>
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-4 border-t pt-3 dark:border-neutral-800">
                <Button variant="outline" size="sm" @click="isDetailModalOpen = false" class="text-xs">Tutup Modal</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Modal -->
    <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
        <DialogContent class="w-[95vw] sm:max-w-md rounded-xl p-4 sm:p-6">
            <DialogHeader>
                <DialogTitle class="text-rose-600 font-bold flex items-center gap-2 text-base sm:text-lg">
                    <AlertTriangle class="h-5 w-5 shrink-0" />
                    <span>Konfirmasi Hapus Siswa</span>
                </DialogTitle>
                <DialogDescription class="text-xs sm:text-sm">
                    Apakah Anda yakin ingin memindahkan data siswa <strong>{{ selectedStudent?.full_name }}</strong> ke tempat sampah?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4 flex flex-row gap-2 justify-end">
                <Button variant="outline" size="sm" @click="isDeleteModalOpen = false" class="text-xs">Batal</Button>
                <Button variant="destructive" size="sm" :disabled="isSubmitting" @click="handleDelete" class="bg-rose-600 hover:bg-rose-700 text-white text-xs">
                    <Loader2 v-if="isSubmitting" class="mr-2 h-3.5 w-3.5 animate-spin" />
                    <span>Ya, Hapus Data</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>