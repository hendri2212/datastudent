<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
import {
    destroy as destroyStudent,
    detail as studentDetail,
    forceDelete as forceDeleteStudent,
    index as studentsIndex,
    restore as restoreStudent,
    unverify as unverifyStudent,
    verify as verifyStudent,
} from '@/routes/students';
import {
    download as downloadDocument,
    preview as previewDocument,
} from '@/routes/students/documents';
import type { Auth } from '@/types/auth';
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
    PaginatedData,
    StudentFilters,
    StudentStatistics,
} from './types';

const props = defineProps<{
    students: PaginatedData<Student>;
    statistics: StudentStatistics;
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
    filters?: StudentFilters;
}>();

const studentList = computed(() => props.students.data);
const paginationMeta = computed(() => props.students);
const page = usePage<{ auth: Auth }>();
const permissions = computed(() => page.props.auth.permissions);

// State Reactive Filter
const searchQuery = ref(props.filters?.search || '');
const selectedClassroom = ref<string | number>(
    props.filters?.classroom_id || '',
);
const selectedMajor = ref<string | number>(props.filters?.major_id || '');
const selectedAcademicYear = ref<string | number>(
    props.filters?.academic_year_id || '',
);
const selectedCitizenship = ref<string | number>(
    props.filters?.citizenship_id || '',
);
const selectedGender = ref<string | number>(props.filters?.gender_id || '');
const selectedReligion = ref<string | number>(props.filters?.religion_id || '');
const selectedStudentStatus = ref<string | number>(
    props.filters?.student_status_id || '',
);
const selectedBloodType = ref<string | number>(
    props.filters?.blood_type_id || '',
);
const activeTab = ref(props.filters?.tab || 'active');

// Modal States
const isFilterModalOpen = ref(false);
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isDetailModalOpen = ref(false);

const selectedStudent = ref<Student | null>(null);
const isSubmitting = ref(false);
const isLoadingStudentDetail = ref(false);
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
            studentsIndex.url(),
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
                preserveScroll: true,
                replace: true,
                only: ['students', 'statistics', 'filters'],
            },
        );
    }, 300);
};

watch(
    [
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
    ],
    () => {
        handleFilterChange();
    },
);

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

const loadStudentDetail = async (student: Student): Promise<Student> => {
    isLoadingStudentDetail.value = true;

    try {
        const response = await fetch(studentDetail.url(student.id), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Gagal memuat detail siswa (${response.status}).`);
        }

        return ((await response.json()) as { student: Student }).student;
    } finally {
        isLoadingStudentDetail.value = false;
    }
};

const handleOpenDetailModal = async (student: Student) => {
    selectedStudent.value = student;
    isDetailModalOpen.value = true;
    selectedStudent.value = await loadStudentDetail(student);
};

const handleOpenEditModal = async (student: Student) => {
    selectedStudent.value = student;
    selectedStudent.value = await loadStudentDetail(student);
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
    router.delete(destroyStudent.url(selectedStudent.value.id), {
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

    router.post(
        verifyStudent.url(student.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const handleUnverifyStudent = (student: Student) => {
    if (!student?.id) {
        return;
    }

    router.post(
        unverifyStudent.url(student.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const handleRestore = (student: Student) => {
    if (!student?.id) {
        return;
    }

    router.post(
        restoreStudent.url(student.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleForceDelete = (student: Student) => {
    if (!student?.id) {
        return;
    }

    if (
        !confirm(
            'Yakin ingin menghapus siswa ini secara permanen? Tindakan ini tidak bisa dikembalikan.',
        )
    ) {
        return;
    }

    router.delete(forceDeleteStudent.url(student.id), {
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
const formatSocialUrl = (
    url?: string,
    username?: string,
    platform?: string,
) => {
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

    return previewDocument.url({
        student: selectedStudent.value.id,
        document: doc.id,
    });
};

const getDocumentDownloadUrl = (doc: StudentDocument) => {
    if (!selectedStudent.value?.id || !doc.id) {
        return '#';
    }

    return downloadDocument.url({
        student: selectedStudent.value.id,
        document: doc.id,
    });
};

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const shareDocument = async (doc: StudentDocument) => {
    const url = getDocumentPreviewUrl(doc);

    if (!url) {
        return;
    }

    const sharePayload = {
        title:
            doc.original_name ||
            doc.stored_name ||
            doc.file_name ||
            'Dokumen Siswa',
        text: `Dokumen Resmi Siswa: ${doc.original_name || doc.stored_name || doc.file_name || 'Dokumen'}`,
        url,
    };

    if (navigator.share) {
        try {
            await navigator.share(sharePayload);
        } catch {
            // Cancelled by user — safely ignored
        }

        return;
    }

    copyToClipboard(url, `doc_share_${doc.id}`);
    alert('Link pratinjau dokumen berhasil disalin ke clipboard.');
};
</script>

<template>
    <Head title="Manajemen Data Siswa" />

    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <div
            class="flex flex-col gap-4 border-b border-neutral-200 pb-4 md:flex-row md:items-center md:justify-between dark:border-neutral-800"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-2xl font-black tracking-tight text-neutral-900 dark:text-neutral-100"
                >
                    <GraduationCap class="h-7 w-7 text-blue-600" />
                    <span>Manajemen Data Siswa</span>
                </h1>
                <p
                    class="mt-1 text-xs text-neutral-500 sm:text-sm dark:text-neutral-400"
                >
                    Pusat pengelolaan biodata, riwayat akademis, dokumen resmi,
                    dan statistik distribusi siswa.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    @click="handleOpenCreateModal"
                    class="flex items-center gap-2 bg-blue-600 font-semibold text-white shadow-sm hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tambah Siswa Baru</span>
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div
                class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Total Siswa
                    </p>
                    <h3
                        class="text-2xl font-extrabold text-neutral-900 dark:text-neutral-100"
                    >
                        {{ props.statistics.total }}
                    </h3>
                    <p
                        class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-emerald-600"
                    >
                        {{ props.statistics.verified }} Terverifikasi
                    </p>
                </div>
                <div
                    class="rounded-xl bg-blue-50 p-3 text-blue-600 dark:bg-blue-950/50"
                >
                    <Users class="h-6 w-6" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Belum Verifikasi
                    </p>
                    <h3 class="text-2xl font-extrabold text-amber-600">
                        {{ props.statistics.unverified }}
                    </h3>
                    <p class="mt-0.5 text-[11px] text-neutral-400">
                        Membutuhkan Tinjauan
                    </p>
                </div>
                <div
                    class="rounded-xl bg-amber-50 p-3 text-amber-600 dark:bg-amber-950/50"
                >
                    <Lock class="h-6 w-6" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Total Prestasi
                    </p>
                    <h3 class="text-2xl font-extrabold text-indigo-600">
                        {{ props.statistics.achievements }}
                    </h3>
                    <p class="mt-0.5 text-[11px] text-neutral-400">
                        Penghargaan Siswa
                    </p>
                </div>
                <div
                    class="rounded-xl bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-950/50"
                >
                    <Trophy class="h-6 w-6" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Poin Pelanggaran
                    </p>
                    <h3 class="text-2xl font-extrabold text-rose-600">
                        {{ props.statistics.violation_points }}
                    </h3>
                    <p class="mt-0.5 text-[11px] text-neutral-400">
                        Akumulasi Poin
                    </p>
                </div>
                <div
                    class="rounded-xl bg-rose-50 p-3 text-rose-600 dark:bg-rose-950/50"
                >
                    <Shield class="h-6 w-6" />
                </div>
            </div>

            <div
                class="col-span-2 flex flex-col justify-between rounded-xl border border-neutral-200 bg-white p-4 shadow-sm lg:col-span-1 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <p
                        class="mb-1 text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Distribusi Gender
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span
                            v-for="(count, gender) in props.statistics.genders"
                            :key="gender"
                            class="rounded-md bg-neutral-100 px-2 py-1 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                        >
                            {{ gender }}:
                            <strong class="text-blue-600">{{ count }}</strong>
                        </span>
                    </div>
                </div>
                <div v-if="activeFilterCount > 0" class="mt-2">
                    <Badge
                        variant="secondary"
                        class="border border-blue-200 bg-blue-50 text-[10px] text-blue-700"
                    >
                        {{ activeFilterCount }} Filter Aktif
                    </Badge>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col justify-between gap-4 border-b border-neutral-200 pb-2 md:flex-row md:items-center dark:border-neutral-800"
        >
            <div class="flex items-center gap-4 text-sm font-medium">
                <button
                    type="button"
                    @click="activeTab = 'active'"
                    :class="[
                        'flex items-center gap-2 border-b-2 pb-2 transition-all',
                        activeTab === 'active'
                            ? 'border-blue-600 font-bold text-blue-600'
                            : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                    ]"
                >
                    <Users class="h-4 w-4" /> Siswa Aktif
                </button>
                <button
                    type="button"
                    @click="activeTab = 'trashed'"
                    :class="[
                        'flex items-center gap-2 border-b-2 pb-2 transition-all',
                        activeTab === 'trashed'
                            ? 'border-red-600 font-bold text-red-600'
                            : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                    ]"
                >
                    <Archive class="h-4 w-4" /> Tempat Sampah
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-full md:w-72">
                    <Search
                        class="absolute top-2.5 left-3 h-4 w-4 text-neutral-400"
                    />
                    <Input
                        v-model="searchQuery"
                        placeholder="Cari Nama, NISN, NIS, Email..."
                        class="h-9 pl-9 text-xs"
                    />
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    @click="isFilterModalOpen = true"
                    class="flex h-9 items-center gap-1.5 border-neutral-300 text-xs"
                >
                    <Filter class="h-3.5 w-3.5 text-neutral-500" />
                    <span>Filter</span>
                    <Badge
                        v-if="activeFilterCount > 0"
                        class="ml-1 h-4 rounded-full bg-blue-600 px-1 text-[9px] text-white"
                        >{{ activeFilterCount }}</Badge
                    >
                </Button>
                <Button
                    v-if="activeFilterCount > 0 || searchQuery"
                    variant="ghost"
                    size="sm"
                    @click="handleResetFilters"
                    class="h-9 text-xs text-rose-600 hover:bg-rose-50"
                >
                    <RefreshCw class="mr-1 h-3.5 w-3.5" /> Reset
                </Button>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-neutral-200 bg-neutral-50 font-semibold text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400"
                    >
                        <tr>
                            <th
                                class="p-3 pl-4 text-xs tracking-wide uppercase"
                            >
                                Siswa & Biodata
                            </th>
                            <th class="p-3 text-xs tracking-wide uppercase">
                                Akademik
                            </th>
                            <th class="p-3 text-xs tracking-wide uppercase">
                                Kontak & Alamat
                            </th>
                            <th class="p-3 text-xs tracking-wide uppercase">
                                Status & Verifikasi
                            </th>
                            <th
                                class="p-3 pr-4 text-center text-xs tracking-wide uppercase"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 dark:divide-neutral-800"
                    >
                        <tr v-if="studentList.length === 0">
                            <td
                                colspan="5"
                                class="p-12 text-center text-sm text-neutral-500"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2"
                                >
                                    <Users class="h-10 w-10 text-neutral-300" />
                                    <p
                                        class="font-semibold text-neutral-700 dark:text-neutral-300"
                                    >
                                        Data siswa tidak ditemukan.
                                    </p>
                                    <p class="text-xs text-neutral-400">
                                        Coba ubah filter atau kata kunci
                                        pencarian Anda.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="student in studentList"
                            :key="student.id"
                            class="transition-colors hover:bg-blue-50/40 dark:hover:bg-neutral-800/40"
                        >
                            <td class="p-3 pl-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full border border-blue-200 bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"
                                    >
                                        {{
                                            student.full_name
                                                ?.charAt(0)
                                                ?.toUpperCase() || 'S'
                                        }}
                                    </div>
                                    <div>
                                        <div
                                            class="flex items-center gap-1.5 text-[13px] font-bold text-neutral-900 dark:text-neutral-100"
                                        >
                                            <span>{{ student.full_name }}</span>
                                            <span
                                                v-if="student.nickname"
                                                class="text-xs font-normal text-neutral-400 italic"
                                                >({{ student.nickname }})</span
                                            >
                                        </div>
                                        <div
                                            class="mt-0.5 flex items-center gap-1.5 text-[11px] text-neutral-500"
                                        >
                                            <span>{{
                                                student.gender?.name ||
                                                'Gender (-)'
                                            }}</span>
                                            <span>•</span>
                                            <span>{{
                                                student.religion?.name ||
                                                'Agama (-)'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="mb-1 flex items-center gap-1.5">
                                    <Badge
                                        variant="outline"
                                        class="rounded-md border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700"
                                    >
                                        {{
                                            student.classroom?.name ||
                                            'Tanpa Kelas'
                                        }}
                                    </Badge>
                                    <span
                                        class="rounded-md border border-neutral-200 bg-neutral-100 px-1.5 py-0.5 text-[10px] text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                    >
                                        {{ student.major?.name || 'Umum' }}
                                    </span>
                                </div>
                                <div
                                    class="font-mono text-[11px] text-neutral-500"
                                >
                                    NISN:
                                    <span
                                        class="font-semibold text-neutral-800 dark:text-neutral-200"
                                        >{{ student.nisn || '-' }}</span
                                    >
                                    | NIS:
                                    <span
                                        class="font-semibold text-neutral-800 dark:text-neutral-200"
                                        >{{ student.nis || '-' }}</span
                                    >
                                </div>
                            </td>
                            <td class="p-3">
                                <div
                                    class="flex items-center gap-1 text-[12px] font-medium text-neutral-800 dark:text-neutral-200"
                                >
                                    <Phone class="h-3 w-3 text-neutral-400" />
                                    <span>{{ student.phone || '-' }}</span>
                                </div>
                                <div
                                    class="mt-0.5 flex max-w-[160px] items-center gap-1 truncate text-[11px] text-neutral-500"
                                    :title="student.email || '-'"
                                >
                                    <Mail class="h-3 w-3 text-neutral-400" />
                                    <span>{{ student.email || '-' }}</span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="mb-1 flex items-center gap-1.5">
                                    <div
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            isStudentVerified(student)
                                                ? 'animate-pulse bg-emerald-500'
                                                : 'bg-amber-500',
                                        ]"
                                    ></div>
                                    <span
                                        :class="[
                                            'text-[11px] font-semibold',
                                            isStudentVerified(student)
                                                ? 'text-emerald-700 dark:text-emerald-400'
                                                : 'text-amber-700 dark:text-amber-400',
                                        ]"
                                    >
                                        {{
                                            isStudentVerified(student)
                                                ? 'Terverifikasi'
                                                : 'Belum Diverifikasi'
                                        }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-neutral-500">
                                    {{
                                        student.student_status?.name ||
                                        'Status (-)'
                                    }}
                                </div>
                            </td>
                            <td class="p-3 pr-4 text-center">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="handleOpenDetailModal(student)"
                                        title="Lihat Detail Lengkap"
                                        class="h-8 w-8 rounded-lg text-blue-600 hover:bg-blue-50"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>

                                    <template v-if="activeTab === 'active'">
                                        <Button
                                            v-if="
                                                permissions.verifyStudents &&
                                                !isStudentVerified(student)
                                            "
                                            variant="ghost"
                                            size="icon"
                                            @click="
                                                handleVerifyStudent(student)
                                            "
                                            title="Verifikasi Data Siswa"
                                            aria-label="Verifikasi data siswa"
                                            class="h-8 w-8 rounded-lg text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <Check class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            v-else-if="
                                                permissions.verifyStudents
                                            "
                                            variant="ghost"
                                            size="icon"
                                            @click="
                                                handleUnverifyStudent(student)
                                            "
                                            title="Batalkan Verifikasi"
                                            aria-label="Batalkan verifikasi siswa"
                                            class="h-8 w-8 rounded-lg text-slate-500 hover:bg-slate-100"
                                        >
                                            <Unlock class="h-4 w-4" />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="
                                                handleOpenEditModal(student)
                                            "
                                            title="Edit Data"
                                            class="h-8 w-8 rounded-lg text-amber-600 hover:bg-amber-50"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="
                                                handleOpenDeleteModal(student)
                                            "
                                            title="Hapus Siswa"
                                            class="h-8 w-8 rounded-lg text-rose-600 hover:bg-rose-50"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </template>
                                    <template v-else>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="handleRestore(student)"
                                            title="Pulihkan Data"
                                            class="h-8 w-8 rounded-lg text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <UserCheck class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            v-if="permissions.forceDelete"
                                            variant="ghost"
                                            size="icon"
                                            @click="handleForceDelete(student)"
                                            title="Hapus Permanen"
                                            aria-label="Hapus siswa permanen"
                                            class="h-8 w-8 rounded-lg text-rose-600 hover:bg-rose-50"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="paginationMeta && paginationMeta.links"
                class="flex flex-col items-center justify-between gap-3 border-t border-neutral-200 p-4 text-xs text-neutral-500 sm:flex-row dark:border-neutral-800"
            >
                <div>
                    Menampilkan
                    <strong>{{ paginationMeta.from || 0 }}</strong> -
                    <strong>{{ paginationMeta.to || 0 }}</strong> dari
                    <strong>{{ paginationMeta.total || 0 }}</strong> siswa
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, idx) in paginationMeta.links"
                        :key="idx"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'rounded-md border px-3 py-1.5 transition-colors',
                                link.active
                                    ? 'border-blue-600 bg-blue-600 font-bold text-white'
                                    : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            v-html="link.label"
                            class="border border-transparent px-3 py-1.5 text-neutral-300 dark:text-neutral-600"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="isFilterModalOpen" @update:open="isFilterModalOpen = $event">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                    <Filter class="h-5 w-5 text-blue-600" />
                    <span>Filter Lanjutan Data Siswa</span>
                </DialogTitle>
                <DialogDescription>
                    Pilih kriteria spesifik di bawah ini untuk menyaring data
                    siswa secara tepat.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-3 py-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Kelas</label
                    >
                    <select
                        v-model="selectedClassroom"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Kelas</option>
                        <option
                            v-for="c in props.classrooms"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Jurusan</label
                    >
                    <select
                        v-model="selectedMajor"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Jurusan</option>
                        <option
                            v-for="m in props.majors"
                            :key="m.id"
                            :value="m.id"
                        >
                            {{ m.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Tahun Ajaran</label
                    >
                    <select
                        v-model="selectedAcademicYear"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Tahun Ajaran</option>
                        <option
                            v-for="ay in props.academicYears"
                            :key="ay.id"
                            :value="ay.id"
                        >
                            {{ ay.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Status Siswa</label
                    >
                    <select
                        v-model="selectedStudentStatus"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Status</option>
                        <option
                            v-for="st in props.studentStatuses"
                            :key="st.id"
                            :value="st.id"
                        >
                            {{ st.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Jenis Kelamin</label
                    >
                    <select
                        v-model="selectedGender"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Jenis Kelamin</option>
                        <option
                            v-for="g in props.genders"
                            :key="g.id"
                            :value="g.id"
                        >
                            {{ g.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Agama</label
                    >
                    <select
                        v-model="selectedReligion"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Agama</option>
                        <option
                            v-for="r in props.religions"
                            :key="r.id"
                            :value="r.id"
                        >
                            {{ r.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Kewarganegaraan</label
                    >
                    <select
                        v-model="selectedCitizenship"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Kewarganegaraan</option>
                        <option
                            v-for="c in props.citizenships"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label
                        class="text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >Golongan Darah</label
                    >
                    <select
                        v-model="selectedBloodType"
                        class="w-full rounded-lg border border-input bg-background p-2 text-xs focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Semua Golongan Darah</option>
                        <option
                            v-for="bt in props.bloodTypes"
                            :key="bt.id"
                            :value="bt.id"
                        >
                            {{ bt.name }}
                        </option>
                    </select>
                </div>
            </div>

            <DialogFooter
                class="flex items-center justify-between gap-2 border-t pt-3 dark:border-neutral-800"
            >
                <Button variant="ghost" size="sm" @click="handleResetFilters"
                    >Reset Filter</Button
                >
                <Button
                    size="sm"
                    @click="isFilterModalOpen = false"
                    class="bg-blue-600 text-white"
                    >Terapkan Filter</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

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

    <Dialog :open="isDetailModalOpen" @update:open="isDetailModalOpen = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader class="border-b pb-3 dark:border-neutral-800">
                <DialogTitle class="flex items-center justify-between text-xl">
                    <span class="flex items-center gap-2">
                        <User class="h-5 w-5 text-blue-600" /> Profil & Data
                        Lengkap Siswa
                    </span>
                </DialogTitle>
                <DialogDescription>
                    Rincian komprehensif seluruh data siswa beserta dokumen dan
                    riwayat aktivitas.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="isLoadingStudentDetail"
                class="flex items-center justify-center gap-2 py-16 text-sm text-neutral-500"
            >
                <Loader2 class="h-5 w-5 animate-spin" /> Memuat detail siswa…
            </div>

            <div v-else-if="selectedStudent" class="space-y-6 pt-2">
                <div
                    class="flex flex-col justify-between gap-4 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 md:flex-row md:items-center dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-xl font-bold text-neutral-900 dark:text-neutral-100"
                        >
                            {{ selectedStudent.full_name }}
                        </h3>
                        <p
                            class="mt-1 text-xs text-neutral-600 dark:text-neutral-400"
                        >
                            Panggilan:
                            <span class="font-semibold">{{
                                selectedStudent.nickname || '-'
                            }}</span>
                            | Gender:
                            <span class="font-semibold">{{
                                selectedStudent.gender?.name || '-'
                            }}</span>
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <Badge class="bg-blue-600 text-white">{{
                                selectedStudent.classroom?.name || 'Tanpa Kelas'
                            }}</Badge>
                            <Badge variant="secondary">{{
                                selectedStudent.major?.name || 'Umum'
                            }}</Badge>
                            <Badge variant="outline">{{
                                selectedStudent.school?.name || 'Sekolah (-)'
                            }}</Badge>
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="copySummaryText(selectedStudent)"
                        class="flex items-center gap-1.5 self-start bg-white shadow-sm md:self-auto dark:bg-neutral-800"
                    >
                        <Check
                            v-if="copiedKey === 'summary_' + selectedStudent.id"
                            class="h-4 w-4 text-emerald-600"
                        />
                        <Copy v-else class="h-4 w-4" />
                        <span>{{
                            copiedKey === 'summary_' + selectedStudent.id
                                ? 'Tersalin!'
                                : 'Salin Seluruh Profil'
                        }}</span>
                    </Button>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        class="flex items-center justify-between rounded-xl border bg-neutral-50/50 p-3.5 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div>
                            <span
                                class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                                >Nomor Induk Siswa Nasional (NISN)</span
                            >
                            <p
                                class="mt-0.5 font-mono text-base font-bold text-blue-600 dark:text-blue-400"
                            >
                                {{ selectedStudent.nisn || '-' }}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="
                                copyToClipboard(selectedStudent.nisn, 'nisn')
                            "
                            :disabled="!selectedStudent.nisn"
                        >
                            <Check
                                v-if="copiedKey === 'nisn'"
                                class="h-4 w-4 text-emerald-600"
                            />
                            <Copy v-else class="h-4 w-4 text-neutral-500" />
                        </Button>
                    </div>
                    <div
                        class="flex items-center justify-between rounded-xl border bg-neutral-50/50 p-3.5 dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div>
                            <span
                                class="text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                                >Nomor Induk Siswa (NIS)</span
                            >
                            <p
                                class="mt-0.5 font-mono text-base font-bold text-neutral-800 dark:text-neutral-200"
                            >
                                {{ selectedStudent.nis || '-' }}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="copyToClipboard(selectedStudent.nis, 'nis')"
                            :disabled="!selectedStudent.nis"
                        >
                            <Check
                                v-if="copiedKey === 'nis'"
                                class="h-4 w-4 text-emerald-600"
                            />
                            <Copy v-else class="h-4 w-4 text-neutral-500" />
                        </Button>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <User class="h-4 w-4 text-blue-500" /> Identitas &
                        Biodata Pribadi
                    </h4>
                    <div
                        class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Tempat, Tanggal Lahir</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                            >
                                {{ selectedStudent.birth_place || '-'
                                }}{{
                                    selectedStudent.birth_date
                                        ? `, ${selectedStudent.birth_date}`
                                        : ''
                                }}
                            </span>
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Jenis Kelamin</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{ selectedStudent.gender?.name || '-' }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Agama</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.religion?.name || '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Kewarganegaraan</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.citizenship?.name || '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Status Siswa</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.student_status?.name || '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Tahun Ajaran</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.academic_year?.name || '-'
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <MapPin class="h-4 w-4 text-emerald-500" /> Kontak &
                        Alamat Tempat Tinggal
                    </h4>
                    <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                        <div
                            class="flex items-center justify-between rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <div>
                                <span
                                    class="mb-0.5 block flex items-center gap-1 text-neutral-400"
                                    ><Phone class="h-3 w-3" /> No. Telepon /
                                    WhatsApp</span
                                >
                                <span
                                    class="font-semibold text-neutral-800 dark:text-neutral-200"
                                    >{{ selectedStudent.phone || '-' }}</span
                                >
                            </div>
                            <Button
                                v-if="selectedStudent.phone"
                                variant="ghost"
                                size="icon"
                                class="h-6 w-6"
                                @click="
                                    copyToClipboard(
                                        selectedStudent.phone,
                                        'phone',
                                    )
                                "
                            >
                                <Check
                                    v-if="copiedKey === 'phone'"
                                    class="h-3.5 w-3.5 text-emerald-600"
                                /><Copy
                                    v-else
                                    class="h-3.5 w-3.5 text-neutral-400"
                                />
                            </Button>
                        </div>
                        <div
                            class="flex items-center justify-between rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <div>
                                <span
                                    class="mb-0.5 block flex items-center gap-1 text-neutral-400"
                                    ><Mail class="h-3 w-3" /> Email Siswa</span
                                >
                                <span
                                    class="font-semibold text-neutral-800 dark:text-neutral-200"
                                    >{{ selectedStudent.email || '-' }}</span
                                >
                            </div>
                            <Button
                                v-if="selectedStudent.email"
                                variant="ghost"
                                size="icon"
                                class="h-6 w-6"
                                @click="
                                    copyToClipboard(
                                        selectedStudent.email,
                                        'email',
                                    )
                                "
                            >
                                <Check
                                    v-if="copiedKey === 'email'"
                                    class="h-3.5 w-3.5 text-emerald-600"
                                /><Copy
                                    v-else
                                    class="h-3.5 w-3.5 text-neutral-400"
                                />
                            </Button>
                        </div>
                        <div
                            class="rounded-lg border p-3 sm:col-span-2 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Alamat Tempat Tinggal Lengkap</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{ selectedStudent.address || '-' }}</span
                            >
                            <span
                                v-if="selectedStudent.postal_code"
                                class="ml-2 text-neutral-500"
                                >(Kode Pos:
                                {{ selectedStudent.postal_code }})</span
                            >
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Building2 class="h-4 w-4 text-indigo-500" /> Lembaga
                        Pendidikan & Akademik
                    </h4>
                    <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-3">
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Sekolah</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{ selectedStudent.school?.name || '-' }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Jurusan</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{ selectedStudent.major?.name || '-' }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-3 dark:border-neutral-800"
                        >
                            <span class="mb-0.5 block text-neutral-400"
                                >Kelas</span
                            >
                            <span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.classroom?.name || '-'
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <div v-if="selectedStudent.family" class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Users class="h-4 w-4 text-purple-500" /> Data Orang Tua
                        & Wali
                    </h4>
                    <div class="grid grid-cols-1 gap-3 text-xs lg:grid-cols-3">
                        <div
                            class="space-y-1 rounded-lg border bg-neutral-50/30 p-3 dark:border-neutral-800 dark:bg-neutral-900/40"
                        >
                            <span
                                class="block border-b pb-1 font-bold text-blue-600"
                                >DATA AYAH</span
                            >
                            <p>
                                <span class="text-neutral-400">Nama:</span>
                                <span class="font-semibold">{{
                                    selectedStudent.family.father_name || '-'
                                }}</span>
                            </p>
                            <p>
                                <span class="text-neutral-400">No. HP:</span>
                                {{ selectedStudent.family.father_phone || '-' }}
                            </p>
                            <p>
                                <span class="text-neutral-400">Pekerjaan:</span>
                                {{
                                    selectedStudent.family.father_occupation
                                        ?.name || '-'
                                }}
                            </p>
                            <p>
                                <span class="text-neutral-400"
                                    >Penghasilan:</span
                                >
                                {{
                                    selectedStudent.family
                                        .father_income_category?.name || '-'
                                }}
                            </p>
                        </div>
                        <div
                            class="space-y-1 rounded-lg border bg-neutral-50/30 p-3 dark:border-neutral-800 dark:bg-neutral-900/40"
                        >
                            <span
                                class="block border-b pb-1 font-bold text-purple-600"
                                >DATA IBU</span
                            >
                            <p>
                                <span class="text-neutral-400">Nama:</span>
                                <span class="font-semibold">{{
                                    selectedStudent.family.mother_name || '-'
                                }}</span>
                            </p>
                            <p>
                                <span class="text-neutral-400">No. HP:</span>
                                {{ selectedStudent.family.mother_phone || '-' }}
                            </p>
                            <p>
                                <span class="text-neutral-400">Pekerjaan:</span>
                                {{
                                    selectedStudent.family.mother_occupation
                                        ?.name || '-'
                                }}
                            </p>
                            <p>
                                <span class="text-neutral-400"
                                    >Penghasilan:</span
                                >
                                {{
                                    selectedStudent.family
                                        .mother_income_category?.name || '-'
                                }}
                            </p>
                        </div>
                        <div
                            class="space-y-1 rounded-lg border bg-neutral-50/30 p-3 dark:border-neutral-800 dark:bg-neutral-900/40"
                        >
                            <span
                                class="block border-b pb-1 font-bold text-amber-600"
                                >DATA WALI</span
                            >
                            <p>
                                <span class="text-neutral-400">Nama:</span>
                                <span class="font-semibold">{{
                                    selectedStudent.family.guardian_name || '-'
                                }}</span>
                            </p>
                            <p>
                                <span class="text-neutral-400">No. HP:</span>
                                {{
                                    selectedStudent.family.guardian_phone || '-'
                                }}
                            </p>
                            <p>
                                <span class="text-neutral-400">Pekerjaan:</span>
                                {{
                                    selectedStudent.family.guardian_occupation
                                        ?.name || '-'
                                }}
                            </p>
                            <p>
                                <span class="text-neutral-400"
                                    >Penghasilan:</span
                                >
                                {{
                                    selectedStudent.family
                                        .guardian_income_category?.name || '-'
                                }}
                            </p>
                        </div>
                        <div
                            class="space-y-1 rounded-lg border p-3 lg:col-span-3 dark:border-neutral-800"
                        >
                            <span
                                class="block border-b pb-1 font-bold text-rose-600"
                                >KONTAK DARURAT & CATATAN</span
                            >
                            <div
                                class="grid grid-cols-1 gap-2 pt-1 sm:grid-cols-3"
                            >
                                <p>
                                    <span class="text-neutral-400"
                                        >Nama Kontak Darurat:</span
                                    >
                                    {{
                                        selectedStudent.family
                                            .emergency_contact_name || '-'
                                    }}
                                </p>
                                <p>
                                    <span class="text-neutral-400"
                                        >No. HP Darurat:</span
                                    >
                                    {{
                                        selectedStudent.family
                                            .emergency_contact_phone || '-'
                                    }}
                                </p>
                                <p>
                                    <span class="text-neutral-400"
                                        >Hubungan Keluarga:</span
                                    >
                                    {{
                                        selectedStudent.family.relationship_type
                                            ?.name || '-'
                                    }}
                                </p>
                            </div>
                            <p
                                v-if="selectedStudent.family.notes"
                                class="pt-1 text-neutral-500"
                            >
                                <span class="text-neutral-400">Catatan:</span>
                                {{ selectedStudent.family.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="selectedStudent.health" class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Heart class="h-4 w-4 text-red-500" /> Rekam Kesehatan
                        Siswa
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                        <div
                            class="rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >Gol. Darah</span
                            ><span
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.blood_type?.name ||
                                    '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >Tinggi Badan</span
                            ><span
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.height || '-'
                                }}
                                cm</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >Berat Badan</span
                            ><span
                                class="font-bold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.weight || '-'
                                }}
                                kg</span
                            >
                        </div>
                        <div
                            class="rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >RS Rujukan</span
                            ><span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.hospital || '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="col-span-2 rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >Dokter Penanggung Jawab</span
                            ><span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.doctor || '-'
                                }}</span
                            >
                        </div>
                        <div
                            class="col-span-2 rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <span class="block text-neutral-400"
                                >Riwayat Penyakit / Alergi</span
                            ><span
                                class="font-semibold text-neutral-800 dark:text-neutral-200"
                                >{{
                                    selectedStudent.health.medical_history ||
                                    selectedStudent.health.allergies ||
                                    '-'
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedStudent.education_histories?.length"
                    class="space-y-3"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <GraduationCap class="h-4 w-4 text-amber-500" /> Riwayat
                        Pendidikan Sebelumnya
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div
                            v-for="(
                                edu, index
                            ) in selectedStudent.education_histories"
                            :key="edu.id || index"
                            class="rounded-lg border bg-neutral-50/30 p-3 dark:border-neutral-800 dark:bg-neutral-900/30"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div>
                                    <p
                                        class="text-sm font-bold text-neutral-900 dark:text-neutral-100"
                                    >
                                        {{ edu.school_name || '-' }}
                                    </p>
                                    <p class="text-[11px] text-neutral-500">
                                        {{
                                            edu.education_level?.name ||
                                            'Jenjang tidak ditentukan'
                                        }}
                                    </p>
                                </div>
                                <Badge
                                    :variant="
                                        edu.is_graduated
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    >{{
                                        edu.is_graduated
                                            ? 'Lulus'
                                            : 'Belum Lulus'
                                    }}</Badge
                                >
                            </div>
                            <div
                                class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-neutral-600 md:grid-cols-4 dark:text-neutral-400"
                            >
                                <span
                                    >Tahun Masuk:
                                    <strong>{{
                                        edu.entry_year || '-'
                                    }}</strong></span
                                >
                                <span
                                    >Tahun Lulus:
                                    <strong>{{
                                        edu.graduation_year || '-'
                                    }}</strong></span
                                >
                                <span
                                    >NPSN:
                                    <strong>{{ edu.npsn || '-' }}</strong></span
                                >
                                <span
                                    >Nilai Akhir:
                                    <strong>{{
                                        edu.final_score || '-'
                                    }}</strong></span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedStudent.socials?.length" class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Share2 class="h-4 w-4 text-cyan-500" /> Media Sosial
                    </h4>
                    <div class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                        <div
                            v-for="(soc, index) in selectedStudent.socials"
                            :key="soc.id || index"
                            class="flex items-center justify-between gap-2 rounded-lg border p-2.5 dark:border-neutral-800"
                        >
                            <div>
                                <p
                                    class="font-bold text-neutral-800 dark:text-neutral-200"
                                >
                                    {{
                                        soc.social_platform?.name || 'Platform'
                                    }}
                                </p>
                                <p
                                    class="font-mono text-[11px] text-neutral-500"
                                >
                                    {{ soc.username || soc.url || '-' }}
                                </p>
                                <a
                                    :href="
                                        formatSocialUrl(
                                            soc.url,
                                            soc.username,
                                            soc.social_platform?.name,
                                        )
                                    "
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    class="mt-1 flex items-center gap-1 truncate text-[11px] font-medium text-blue-600 hover:underline"
                                >
                                    <span>{{
                                        soc.url || 'Buka Profil Media Sosial'
                                    }}</span>
                                    <ExternalLink class="h-3 w-3 shrink-0" />
                                </a>
                            </div>
                            <Button
                                v-if="soc.url"
                                variant="ghost"
                                size="icon"
                                class="h-6 w-6"
                                @click="
                                    copyToClipboard(soc.url, 'soc_' + index)
                                "
                            >
                                <Check
                                    v-if="copiedKey === 'soc_' + index"
                                    class="h-3 w-3 text-emerald-600"
                                />
                                <Copy v-else class="h-3 w-3 text-neutral-400" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedStudent.achievements?.length"
                    class="space-y-3"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Trophy class="h-4 w-4 text-amber-500" /> Prestasi Siswa
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div
                            v-for="(ach, index) in selectedStudent.achievements"
                            :key="ach.id || index"
                            class="rounded-lg border bg-amber-50/20 p-3 dark:border-neutral-800 dark:bg-amber-950/10"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="font-bold text-neutral-900 dark:text-neutral-100"
                                >
                                    {{ ach.title }}
                                </p>
                                <Badge
                                    variant="outline"
                                    class="border-amber-300 bg-amber-100 text-amber-800"
                                    >Peringkat: {{ ach.rank || '-' }}</Badge
                                >
                            </div>
                            <p class="mt-1 text-[11px] text-neutral-500">
                                Penyelenggara: {{ ach.organizer || '-' }} |
                                Tingkat: {{ ach.level || '-' }} | Tanggal:
                                {{ ach.achievement_date || '-' }}
                            </p>
                            <p
                                v-if="ach.description"
                                class="mt-1 text-[11px] text-neutral-600 dark:text-neutral-300"
                            >
                                {{ ach.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedStudent.violations?.length"
                    class="space-y-3"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <Shield class="h-4 w-4 text-rose-500" /> Catatan
                        Pelanggaran Kedisiplinan
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div
                            v-for="(vio, index) in selectedStudent.violations"
                            :key="vio.id || index"
                            class="rounded-lg border bg-rose-50/20 p-3 dark:border-neutral-800 dark:bg-rose-950/10"
                        >
                            <div class="flex items-center justify-between">
                                <p
                                    class="font-bold text-neutral-900 dark:text-neutral-100"
                                >
                                    {{ vio.title }}
                                </p>
                                <Badge variant="destructive"
                                    >Poin: {{ vio.point ?? '-' }}</Badge
                                >
                            </div>
                            <p class="mt-1 text-[11px] text-neutral-500">
                                Tanggal: {{ vio.violation_date || '-' }}
                            </p>
                            <p
                                v-if="vio.description"
                                class="mt-1 text-[11px] text-neutral-600 dark:text-neutral-300"
                            >
                                {{ vio.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="selectedStudent.documents?.length" class="space-y-3">
                    <h4
                        class="flex items-center gap-1.5 border-b pb-1 text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        <FileText class="h-4 w-4 text-blue-500" /> Berkas &
                        Dokumen Resmi
                    </h4>
                    <div class="space-y-2 text-xs">
                        <div
                            v-for="doc in selectedStudent.documents"
                            :key="doc.id"
                            class="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800"
                        >
                            <div>
                                <p
                                    class="font-bold text-neutral-900 dark:text-neutral-100"
                                >
                                    {{
                                        doc.original_name ||
                                        doc.stored_name ||
                                        'Dokumen'
                                    }}
                                </p>
                                <p class="text-[11px] text-neutral-500">
                                    {{
                                        doc.document_type?.name || 'Tipe Berkas'
                                    }}
                                    • {{ doc.mime_type || '-' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a
                                    :href="getDocumentPreviewUrl(doc)"
                                    target="_blank"
                                    class="rounded-md border px-3 py-1 text-xs font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                    >Pratinjau</a
                                >
                                <a
                                    :href="getDocumentDownloadUrl(doc)"
                                    target="_blank"
                                    class="rounded-md bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                                    >Unduh</a
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border bg-neutral-50 p-3 text-xs dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center gap-2">
                        <FileCheck class="h-4 w-4 text-emerald-600" />
                        <span
                            >Verifikator:
                            <strong>{{
                                selectedStudent.verifier?.name ||
                                'Belum Diverifikasi'
                            }}</strong></span
                        >
                    </div>
                    <div>
                        <span
                            >Waktu Verifikasi:
                            <strong>{{
                                selectedStudent.verified_at || '-'
                            }}</strong></span
                        >
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-4 border-t pt-3 dark:border-neutral-800">
                <Button variant="outline" @click="isDetailModalOpen = false"
                    >Tutup Modal</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle
                    class="flex items-center gap-2 font-bold text-rose-600"
                >
                    <AlertTriangle class="h-5 w-5" />
                    <span>Konfirmasi Hapus Siswa</span>
                </DialogTitle>
                <DialogDescription>
                    Apakah Anda yakin ingin memindahkan data siswa
                    <strong>{{ selectedStudent?.full_name }}</strong> ke tempat
                    sampah?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4 flex justify-end gap-2">
                <Button variant="outline" @click="isDeleteModalOpen = false"
                    >Batal</Button
                >
                <Button
                    variant="destructive"
                    :disabled="isSubmitting"
                    @click="handleDelete"
                    class="bg-rose-600 text-white hover:bg-rose-700"
                >
                    <Loader2
                        v-if="isSubmitting"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <span>Ya, Hapus Data</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
