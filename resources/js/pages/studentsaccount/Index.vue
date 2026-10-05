<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Search,
    UserCheck,
    UserX,
    KeyRound,
    Users,
    Loader2,
    Check,
    Copy,
    RefreshCw,
    ShieldCheck,
    Mail,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

interface StudentAccountItem {
    id: number;
    full_name: string;
    nisn: string;
    nis: string;
    email: string | null;
    user_id: number | null;
    classroom?: { name: string };
    major?: { name: string };
    user?: { id: number; email: string; name: string };
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    students: PaginatedData<StudentAccountItem>;
    filters?: { search?: string; status?: string };
}>();

// State Reactive
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Modal States
const isSingleModalOpen = ref(false);
const isBulkModalOpen = ref(false);
const selectedStudent = ref<StudentAccountItem | null>(null);
const defaultPassword = ref('12345678');
const isSubmitting = ref(false);
const copiedKey = ref<string | null>(null);

// Computes Email Utama untuk Siswa
const resolveStudentEmail = (student: StudentAccountItem | null): string => {
    if (!student) {
return '';
}

    if (student.user?.email) {
return student.user.email;
}

    if (student.email && student.email.trim() !== '') {
return student.email.trim();
}

    return `${student.nisn || student.nis || student.id}@siswa.belajar.id`;
};

// Handler Filter & Search Auto-debounce
let searchTimeout: ReturnType<typeof setTimeout>;
const handleFilterChange = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/student-accounts',
            {
                search: searchQuery.value,
                status: statusFilter.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
};

watch([searchQuery, statusFilter], () => {
    handleFilterChange();
});

const handleResetFilter = () => {
    searchQuery.value = '';
    statusFilter.value = '';
};

// Clipboard Helper
const copyToClipboard = (text: string, key: string) => {
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

// Open Modals
const openSingleModal = (student: StudentAccountItem) => {
    selectedStudent.value = student;
    defaultPassword.value = '12345678';
    isSingleModalOpen.value = true;
};

const openBulkModal = () => {
    defaultPassword.value = '12345678';
    isBulkModalOpen.value = true;
};

// Submit Handlers
const handleCreateSingleAccount = () => {
    if (!selectedStudent.value) {
return;
}

    isSubmitting.value = true;
    const targetEmail = resolveStudentEmail(selectedStudent.value);

    router.post(
        `/students/${selectedStudent.value.id}/create-account`,
        {
            email: targetEmail,
            password: defaultPassword.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSingleModalOpen.value = false;
                selectedStudent.value = null;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const handleBulkCreateAccounts = () => {
    isSubmitting.value = true;
    router.post(
        '/student-accounts/bulk-create',
        {
            password: defaultPassword.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isBulkModalOpen.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Manajemen Akun Siswa" />

    <div class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
        <!-- Header Halaman -->
        <div class="flex flex-col gap-4 border-b border-neutral-200 pb-5 md:flex-row md:items-center md:justify-between dark:border-neutral-800">
            <div>
                <h1 class="flex items-center gap-2 text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                    <ShieldCheck class="h-6 w-6 text-neutral-700 dark:text-neutral-300" />
                    <span>Manajemen Akun Akses Siswa</span>
                </h1>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    Kelola dan buatkan akun login siswa menggunakan email resmi atau generator otomatis NISN/NIS.
                </p>
            </div>
            <div>
                <Button
                    @click="openBulkModal"
                    class="flex items-center gap-2 bg-neutral-900 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-200"
                >
                    <KeyRound class="h-3.5 w-3.5" />
                    <span>Buat Akun Massal</span>
                </Button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <Search class="absolute top-2.5 left-3 h-4 w-4 text-neutral-400" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Cari Nama, NISN, NIS, atau Email..."
                        class="h-9 pl-9 text-xs"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="h-9 rounded-md border border-neutral-200 bg-white px-3 py-1 text-xs text-neutral-700 focus:outline-none dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300"
                >
                    <option value="">Semua Status Akun</option>
                    <option value="has_account">Sudah Memiliki Akun</option>
                    <option value="no_account">Belum Memiliki Akun</option>
                </select>
                <Button
                    v-if="searchQuery || statusFilter"
                    variant="ghost"
                    size="sm"
                    @click="handleResetFilter"
                    class="h-9 text-xs text-neutral-500 hover:text-neutral-800 dark:hover:text-neutral-200"
                >
                    <RefreshCw class="mr-1 h-3.5 w-3.5" /> Reset
                </Button>
            </div>
        </div>

        <!-- Tabel Akun Siswa -->
        <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="border-b border-neutral-200 bg-neutral-50/80 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/40 dark:text-neutral-400">
                        <tr>
                            <th class="py-3 px-4 uppercase tracking-wider text-[11px]">Nama Siswa</th>
                            <th class="py-3 px-4 uppercase tracking-wider text-[11px]">Kelas & NISN</th>
                            <th class="py-3 px-4 uppercase tracking-wider text-[11px]">Email Akses</th>
                            <th class="py-3 px-4 uppercase tracking-wider text-[11px]">Status Akun</th>
                            <th class="py-3 px-4 text-center uppercase tracking-wider text-[11px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr v-if="props.students.data.length === 0">
                            <td colspan="5" class="p-10 text-center text-xs text-neutral-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Users class="h-8 w-8 text-neutral-300 dark:text-neutral-700" />
                                    <p class="font-medium text-neutral-600 dark:text-neutral-400">Data siswa tidak ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="student in props.students.data"
                            :key="student.id"
                            class="transition-colors hover:bg-neutral-50/60 dark:hover:bg-neutral-800/30"
                        >
                            <td class="py-3 px-4 font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ student.full_name }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                        {{ student.classroom?.name || 'Tanpa Kelas' }}
                                    </span>
                                    <span class="text-[11px] font-mono text-neutral-400">
                                        NISN: {{ student.nisn || '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-neutral-600 dark:text-neutral-300">
                                <div class="flex items-center gap-1.5">
                                    <Mail class="h-3.5 w-3.5 text-neutral-400 shrink-0" />
                                    <span>{{ resolveStudentEmail(student) }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    v-if="student.user_id"
                                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                                >
                                    <UserCheck class="h-3 w-3" /> Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-md bg-neutral-100 px-2 py-1 text-[11px] font-medium text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400"
                                >
                                    <UserX class="h-3 w-3" /> Belum Dibuat
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <Button
                                    v-if="!student.user_id"
                                    size="sm"
                                    variant="outline"
                                    @click="openSingleModal(student)"
                                    class="h-7 border-neutral-300 px-2.5 text-[11px] font-medium hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800"
                                >
                                    <KeyRound class="mr-1 h-3 w-3 text-neutral-500" /> Buat Akun
                                </Button>
                                <div v-else class="flex items-center justify-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                                        title="Salin Email Akses"
                                        @click="copyToClipboard(resolveStudentEmail(student), `email_${student.id}`)"
                                    >
                                        <Check v-if="copiedKey === `email_${student.id}`" class="h-3.5 w-3.5 text-emerald-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div v-if="props.students.links" class="flex flex-col items-center justify-between gap-3 border-t border-neutral-200 p-3.5 text-xs text-neutral-500 sm:flex-row dark:border-neutral-800">
                <div>
                    Menampilkan <strong>{{ props.students.from || 0 }}</strong> - <strong>{{ props.students.to || 0 }}</strong> dari <strong>{{ props.students.total || 0 }}</strong> siswa
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, idx) in props.students.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'rounded px-2.5 py-1 text-xs transition-colors',
                                link.active
                                    ? 'bg-neutral-900 font-semibold text-white dark:bg-neutral-100 dark:text-neutral-900'
                                    : 'border border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            v-html="link.label"
                            class="px-2 py-1 text-neutral-300 dark:text-neutral-700"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Buat Akun Individual -->
    <Dialog :open="isSingleModalOpen" @update:open="isSingleModalOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-base font-bold text-neutral-900 dark:text-neutral-100">
                    <KeyRound class="h-4 w-4 text-neutral-600" />
                    <span>Buat Akun Akses Siswa</span>
                </DialogTitle>
                <DialogDescription class="text-xs">
                    Konfirmasi pembuatan akun login untuk <strong>{{ selectedStudent?.full_name }}</strong>.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-2 text-xs" v-if="selectedStudent">
                <div class="space-y-1">
                    <label class="font-medium text-neutral-700 dark:text-neutral-300">Email Akses Login</label>
                    <Input
                        :value="resolveStudentEmail(selectedStudent)"
                        readonly
                        class="bg-neutral-50 font-mono text-xs dark:bg-neutral-800/50"
                    />
                    <p class="text-[11px] text-neutral-400">Diambil otomatis dari email data siswa atau generator NISN.</p>
                </div>
                <div class="space-y-1">
                    <label class="font-medium text-neutral-700 dark:text-neutral-300">Password Default</label>
                    <Input v-model="defaultPassword" type="text" class="font-mono text-xs" />
                    <p class="text-[11px] text-neutral-400">Password standar awal diset ke <code>12345678</code>.</p>
                </div>
            </div>

            <DialogFooter class="flex justify-end gap-2 border-t pt-3 dark:border-neutral-800">
                <Button variant="outline" size="sm" @click="isSingleModalOpen = false" class="text-xs">Batal</Button>
                <Button
                    size="sm"
                    :disabled="isSubmitting"
                    @click="handleCreateSingleAccount"
                    class="bg-neutral-900 text-xs text-white hover:bg-neutral-800 dark:bg-neutral-100 dark:text-neutral-900"
                >
                    <Loader2 v-if="isSubmitting" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    <span>Proses Buat Akun</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal Buat Akun Massal -->
    <Dialog :open="isBulkModalOpen" @update:open="isBulkModalOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-base font-bold text-neutral-900 dark:text-neutral-100">
                    <ShieldCheck class="h-4 w-4 text-neutral-600" />
                    <span>Buat Akun Otomatis Seluruh Siswa</span>
                </DialogTitle>
                <DialogDescription class="text-xs">
                    Membuatkan akun login secara otomatis untuk seluruh siswa yang belum memiliki akun.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-2 text-xs">
                <div class="space-y-1">
                    <label class="font-medium text-neutral-700 dark:text-neutral-300">Password Bawaan Massal</label>
                    <Input v-model="defaultPassword" type="text" class="font-mono text-xs" />
                </div>
                <p class="text-[11px] text-neutral-500">
                    Sistem akan mengutamakan email siswa yang terdaftar. Jika kosong, email akan otomatis dibuat dengan format <code>[nisn]@siswa.belajar.id</code>.
                </p>
            </div>

            <DialogFooter class="flex justify-end gap-2 border-t pt-3 dark:border-neutral-800">
                <Button variant="outline" size="sm" @click="isBulkModalOpen = false" class="text-xs">Batal</Button>
                <Button
                    size="sm"
                    :disabled="isSubmitting"
                    @click="handleBulkCreateAccounts"
                    class="bg-neutral-900 text-xs text-white hover:bg-neutral-800 dark:bg-neutral-100 dark:text-neutral-900"
                >
                    <Loader2 v-if="isSubmitting" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    <span>Jalankan Pembuatan Akun Massal</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
