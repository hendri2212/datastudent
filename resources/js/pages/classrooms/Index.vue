<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    Plus, 
    School, 
    Pencil, 
    Trash2, 
    Search,
    GraduationCap,
    Users,
    MoreHorizontal,
    Info,
    RotateCcw,
    Archive,
    Filter,
    X,
    ChevronLeft,
    ChevronRight,
    Loader2
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Major {
    id: number;
    name: string;
    code: string;
}

interface Classroom {
    id: number;
    name: string;
    level: 'X' | 'XI' | 'XII';
    rombel: number;
    major: Major;
    studentCount: number;
    status: 'Aktif' | 'Nonaktif';
    deleted_at?: string | null;
    maleCount: number;
    femaleCount: number;
    religion: {
        islam: number;
        kristen: number;
        katolik: number;
        hindu: number;
        buddha: number;
        khonghucu: number;
    };
}

const props = defineProps<{
    classrooms: Classroom[];
    majors: Major[];
    trashedCount: number;
    filters: { 
        search?: string; 
        tab?: string;
    };
}>();

// Filter States
const activeTab = ref<'active' | 'trashed'>(props.filters?.tab === 'trashed' ? 'trashed' : 'active');
const searchQuery = ref(props.filters?.search || '');
const selectedLevel = ref('');
const selectedMajor = ref('');
const selectedRombel = ref('');

// Pagination States
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Modal Controls & Selected Target
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isForceDeleteModalOpen = ref(false);
const isStatusModalOpen = ref(false);

const selectedClassroom = ref<Classroom | null>(null);

// Specific Loaders
const isDeleting = ref(false);
const isForceDeleting = ref(false);
const restoringId = ref<number | null>(null);

// Form Handlers
const addForm = useForm({
    major_id: '' as number | '',
    level: 'X' as 'X' | 'XI' | 'XII',
});

const editForm = useForm({
    major_id: '' as number | '',
    level: 'X' as 'X' | 'XI' | 'XII',
    rombel: 1,
    status: 'Aktif' as 'Aktif' | 'Nonaktif',
});

// Computed Properties
const totalStudents = computed(() =>
    props.classrooms.reduce((sum, item) => sum + (item.studentCount || 0), 0)
);

const availableRombels = computed(() => {
    const list = props.classrooms.map(c => c.rombel);
    return Array.from(new Set(list)).sort((a, b) => a - b);
});

// Client-side Filter (Dengan pemisahan status Tab & Kriteria Search/Dropdown)
const filteredClassrooms = computed(() => {
    return props.classrooms.filter((item) => {
        // Tab check
        const isTrashed = !!item.deleted_at;
        if (activeTab.value === 'trashed' && !isTrashed) return false;
        if (activeTab.value === 'active' && isTrashed) return false;

        // Search & Dropdown filters
        const q = searchQuery.value.toLowerCase();
        const matchesSearch = !q || 
            item.name.toLowerCase().includes(q) ||
            item.level.toLowerCase().includes(q) ||
            item.major?.name?.toLowerCase().includes(q) ||
            item.major?.code?.toLowerCase().includes(q);

        const matchesLevel = !selectedLevel.value || item.level === selectedLevel.value;
        const matchesMajor = !selectedMajor.value || item.major?.id.toString() === selectedMajor.value;
        const matchesRombel = !selectedRombel.value || item.rombel.toString() === selectedRombel.value;

        return matchesSearch && matchesLevel && matchesMajor && matchesRombel;
    });
});

// Auto reset page on filter change
watch([searchQuery, selectedLevel, selectedMajor, selectedRombel, itemsPerPage], () => {
    currentPage.value = 1;
});

// Pagination Calculations
const totalPages = computed(() => Math.ceil(filteredClassrooms.value.length / itemsPerPage.value) || 1);

const paginatedClassrooms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredClassrooms.value.slice(start, start + itemsPerPage.value);
});

const startIndex = computed(() => filteredClassrooms.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1);
const endIndex = computed(() => Math.min(currentPage.value * itemsPerPage.value, filteredClassrooms.value.length));

const isFilterActive = computed(() => !!(searchQuery.value || selectedLevel.value || selectedMajor.value || selectedRombel.value));

const resetFilters = () => {
    searchQuery.value = '';
    selectedLevel.value = '';
    selectedMajor.value = '';
    selectedRombel.value = '';
};

// Switch Tab Handler
const switchTab = (tab: 'active' | 'trashed') => {
    activeTab.value = tab;
    currentPage.value = 1;
    router.get('/classrooms', { tab, search: searchQuery.value }, { preserveState: true });
};

const selectedMajorCode = computed(() => {
    const major = props.majors.find((m) => m.id === addForm.major_id);
    return major ? major.code : 'JURUSAN';
});

// Modal Openers
const openAddModal = () => {
    addForm.reset();
    addForm.clearErrors();
    addForm.major_id = props.majors[0]?.id || '';
    isAddModalOpen.value = true;
};

const openEditModal = (item: Classroom) => {
    selectedClassroom.value = item;
    editForm.clearErrors();
    editForm.major_id = item.major?.id || '';
    editForm.level = item.level;
    editForm.rombel = item.rombel;
    editForm.status = item.status;
    isEditModalOpen.value = true;
};

const openSoftDeleteModal = (item: Classroom) => {
    selectedClassroom.value = item;
    isDeleteModalOpen.value = true;
};

const openForceDeleteModal = (item: Classroom) => {
    selectedClassroom.value = item;
    isForceDeleteModalOpen.value = true;
};

const openStatusModal = (item: Classroom) => {
    selectedClassroom.value = item;
    isStatusModalOpen.value = true;
};

// Actions
const submitAdd = () => {
    addForm.post('/classrooms', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
};

const submitEdit = () => {
    if (!selectedClassroom.value) return;
    editForm.put(`/classrooms/${selectedClassroom.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const submitSoftDelete = () => {
    if (!selectedClassroom.value) return;
    router.delete(`/classrooms/${selectedClassroom.value.id}`, {
        onStart: () => { isDeleting.value = true; },
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedClassroom.value = null;
        },
        onFinish: () => { isDeleting.value = false; },
    });
};

const submitRestore = (item: Classroom) => {
    router.post(`/classrooms/${item.id}/restore`, {}, {
        onStart: () => { restoringId.value = item.id; },
        onFinish: () => { restoringId.value = null; },
    });
};

const submitForceDelete = () => {
    if (!selectedClassroom.value) return;
    router.delete(`/classrooms/${selectedClassroom.value.id}/force-delete`, {
        onStart: () => { isForceDeleting.value = true; },
        onSuccess: () => {
            isForceDeleteModalOpen.value = false;
            selectedClassroom.value = null;
        },
        onFinish: () => { isForceDeleting.value = false; },
    });
};
</script>

<template>
    <Head title="Data Kelas" />

    <div class="min-h-screen bg-neutral-50/50 p-6 dark:bg-neutral-900/50">
        <div class="mx-auto max-w-7xl space-y-6">
            
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        Manajemen Kelas
                    </h1>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        Kelola rombel kelas dengan penomoran otomatis dan fitur tempat sampah.
                    </p>
                </div>
                <Button class="gap-2" @click="openAddModal">
                    <Plus class="size-4" />
                    Tambah Kelas Baru
                </Button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-neutral-500">Total Rombel Kelas</span>
                        <div class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                            <School class="size-5" />
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-bold">{{ classrooms.length }} Kelas</div>
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-neutral-500">Total Siswa Terdaftar</span>
                        <div class="rounded-lg bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <Users class="size-5" />
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-bold">{{ totalStudents }} Siswa</div>
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1 dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-neutral-500">Total Jurusan</span>
                        <div class="rounded-lg bg-violet-50 p-2 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400">
                            <GraduationCap class="size-5" />
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-bold">{{ majors.length }} Jurusan</div>
                </div>
            </div>

            <div class="flex items-center gap-2 border-b border-neutral-200 pb-2 dark:border-neutral-800">
                <button
                    @click="switchTab('active')"
                    :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        activeTab === 'active' 
                            ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900' 
                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    ]"
                >
                    Kelas Aktif
                </button>
                <button
                    @click="switchTab('trashed')"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        activeTab === 'trashed' 
                            ? 'bg-rose-600 text-white' 
                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    ]"
                >
                    <Archive class="size-4" />
                    Tempat Sampah
                    <Badge v-if="trashedCount > 0" variant="secondary" class="ml-1">
                        {{ trashedCount }}
                    </Badge>
                </button>
            </div>

            <div class="rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                
                <div class="flex flex-col gap-3 border-b p-4 dark:border-neutral-800 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:w-72">
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama kelas / jurusan..."
                            class="pl-9"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-neutral-500 font-medium mr-1">
                            <Filter class="size-3.5" /> Filter:
                        </div>

                        <select
                            v-model="selectedLevel"
                            class="h-9 rounded-md border border-neutral-200 bg-white px-3 py-1 text-xs dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <option value="">Semua Tingkat</option>
                            <option value="X">Tingkat X</option>
                            <option value="XI">Tingkat XI</option>
                            <option value="XII">Tingkat XII</option>
                        </select>

                        <select
                            v-model="selectedMajor"
                            class="h-9 rounded-md border border-neutral-200 bg-white px-3 py-1 text-xs dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <option value="">Semua Jurusan</option>
                            <option v-for="m in majors" :key="m.id" :value="m.id.toString()">
                                {{ m.code }} - {{ m.name }}
                            </option>
                        </select>

                        <select
                            v-model="selectedRombel"
                            class="h-9 rounded-md border border-neutral-200 bg-white px-3 py-1 text-xs dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <option value="">Semua Rombel</option>
                            <option v-for="r in availableRombels" :key="r" :value="r.toString()">
                                Rombel {{ r }}
                            </option>
                        </select>

                        <Button
                            v-if="isFilterActive"
                            variant="ghost"
                            size="sm"
                            class="h-9 gap-1 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400"
                            @click="resetFilters"
                        >
                            <X class="size-3.5" />
                            Reset
                        </Button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 text-neutral-500 dark:bg-neutral-800/50 dark:text-neutral-400">
                            <tr>
                                <th class="px-6 py-3 font-medium">Nama Kelas</th>
                                <th class="px-6 py-3 font-medium">Tingkat</th>
                                <th class="px-6 py-3 font-medium">Rombel</th>
                                <th class="px-6 py-3 font-medium">Jurusan</th>
                                <th class="px-6 py-3 font-medium">Jumlah Siswa</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-neutral-800">
                            <tr
                                v-for="item in paginatedClassrooms"
                                :key="item.id"
                                class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/50"
                            >
                                <td class="px-6 py-4 font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ item.name }}
                                </td>
                                <td class="px-6 py-4">
                                    <Badge variant="outline">{{ item.level }}</Badge>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    Rombel {{ item.rombel }}
                                </td>
                                <td class="px-6 py-4 text-neutral-600 dark:text-neutral-300">
                                    {{ item.major?.name || '-' }} ({{ item.major?.code || '-' }})
                                </td>
                                <td class="px-6 py-4 font-medium tabular-nums">
                                    {{ item.studentCount }} Siswa
                                </td>
                                <td class="px-6 py-4">
                                    <Badge :variant="item.status === 'Aktif' ? 'default' : 'secondary'">
                                        {{ item.status }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template v-if="activeTab === 'active'">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreHorizontal class="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem @click="openStatusModal(item)">
                                                    <Info class="mr-2 size-4" /> Detail Statistik
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openEditModal(item)">
                                                    <Pencil class="mr-2 size-4" /> Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    class="text-rose-600 dark:text-rose-400"
                                                    @click="openSoftDeleteModal(item)"
                                                >
                                                    <Trash2 class="mr-2 size-4" /> Pindahkan ke Sampah
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </template>

                                    <template v-else>
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="gap-1.5 text-emerald-600 hover:text-emerald-700"
                                                :disabled="restoringId === item.id"
                                                @click="submitRestore(item)"
                                            >
                                                <Loader2 v-if="restoringId === item.id" class="size-3.5 animate-spin" />
                                                <RotateCcw v-else class="size-3.5" />
                                                {{ restoringId === item.id ? 'Memulihkan...' : 'Pulihkan' }}
                                            </Button>

                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                class="gap-1.5"
                                                @click="openForceDeleteModal(item)"
                                            >
                                                <Trash2 class="size-3.5" />
                                                Hapus Permanen
                                            </Button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="filteredClassrooms.length === 0">
                                <td colspan="7" class="p-8 text-center text-neutral-500">
                                    Data kelas tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t p-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 text-xs text-neutral-500">
                        <span>
                            Menampilkan <strong>{{ startIndex }}</strong> - <strong>{{ endIndex }}</strong> dari <strong>{{ filteredClassrooms.length }}</strong> data
                        </span>
                        
                        <div class="flex items-center gap-1.5">
                            <span>Per Halaman:</span>
                            <select
                                v-model="itemsPerPage"
                                class="h-7 rounded border border-neutral-200 bg-white px-2 py-0 text-xs dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            class="size-8"
                            :disabled="currentPage === 1"
                            @click="currentPage--"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>

                        <template v-for="page in totalPages" :key="page">
                            <Button
                                v-if="page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1"
                                :variant="currentPage === page ? 'default' : 'outline'"
                                size="sm"
                                class="h-8 min-w-8 px-2 text-xs"
                                @click="currentPage = page"
                            >
                                {{ page }}
                            </Button>
                            <span v-else-if="page === 2 && currentPage > 3" class="px-1 text-xs text-neutral-400">...</span>
                            <span v-else-if="page === totalPages - 1 && currentPage < totalPages - 2" class="px-1 text-xs text-neutral-400">...</span>
                        </template>

                        <Button
                            variant="outline"
                            size="icon"
                            class="size-8"
                            :disabled="currentPage === totalPages || totalPages === 0"
                            @click="currentPage++"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <Dialog v-model:open="isAddModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tambah Kelas Baru</DialogTitle>
                <DialogDescription>
                    Pilih tingkat dan jurusan. Nomor rombel akan dibuat secara otomatis.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitAdd" class="space-y-4">
                <div class="space-y-2">
                    <Label for="add-level">Tingkat Kelas</Label>
                    <select
                        id="add-level"
                        v-model="addForm.level"
                        class="w-full rounded-md border border-neutral-200 bg-white p-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="X">Tingkat X</option>
                        <option value="XI">Tingkat XI</option>
                        <option value="XII">Tingkat XII</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="add-major">Jurusan</Label>
                    <select
                        id="add-major"
                        v-model="addForm.major_id"
                        class="w-full rounded-md border border-neutral-200 bg-white p-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="" disabled>Pilih Jurusan</option>
                        <option v-for="m in majors" :key="m.id" :value="m.id">
                            {{ m.code }} - {{ m.name }}
                        </option>
                    </select>
                </div>

                <div v-if="addForm.major_id" class="rounded-lg bg-blue-50 p-3 text-xs text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                    Sistem akan otomatis menentukan rombel berikutnya untuk <strong>{{ addForm.level }} {{ selectedMajorCode }}</strong>.
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isAddModalOpen = false">Batal</Button>
                    <Button type="submit" :disabled="addForm.processing">
                        <Loader2 v-if="addForm.processing" class="mr-2 size-4 animate-spin" />
                        Simpan Kelas
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isEditModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Kelas</DialogTitle>
                <DialogDescription>
                    Ubah informasi kelas {{ selectedClassroom?.name }}.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="edit-level">Tingkat Kelas</Label>
                    <select
                        id="edit-level"
                        v-model="editForm.level"
                        class="w-full rounded-md border border-neutral-200 bg-white p-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="X">Tingkat X</option>
                        <option value="XI">Tingkat XI</option>
                        <option value="XII">Tingkat XII</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="edit-major">Jurusan</Label>
                    <select
                        id="edit-major"
                        v-model="editForm.major_id"
                        class="w-full rounded-md border border-neutral-200 bg-white p-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option v-for="m in majors" :key="m.id" :value="m.id">
                            {{ m.code }} - {{ m.name }}
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="edit-rombel">Nomor Rombel</Label>
                    <Input
                        id="edit-rombel"
                        type="number"
                        v-model="editForm.rombel"
                        min="1"
                        max="20"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="edit-status">Status</Label>
                    <select
                        id="edit-status"
                        v-model="editForm.status"
                        class="w-full rounded-md border border-neutral-200 bg-white p-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isEditModalOpen = false">Batal</Button>
                    <Button type="submit" :disabled="editForm.processing">
                        <Loader2 v-if="editForm.processing" class="mr-2 size-4 animate-spin" />
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isDeleteModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Pindahkan ke Tempat Sampah?</DialogTitle>
                <DialogDescription>
                    Kelas <strong>{{ selectedClassroom?.name }}</strong> akan dipindahkan ke tempat sampah.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" :disabled="isDeleting" @click="isDeleteModalOpen = false">Batal</Button>
                <Button variant="destructive" :disabled="isDeleting" @click="submitSoftDelete">
                    <Loader2 v-if="isDeleting" class="mr-2 size-4 animate-spin" />
                    {{ isDeleting ? 'Memindahkan...' : 'Ya, Pindahkan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isForceDeleteModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="text-rose-600">Hapus Secara Permanen?</DialogTitle>
                <DialogDescription>
                    Apakah Anda yakin ingin menghapus kelas <strong>{{ selectedClassroom?.name }}</strong> secara permanen? Data tidak dapat dikembalikan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" :disabled="isForceDeleting" @click="isForceDeleteModalOpen = false">Batal</Button>
                <Button variant="destructive" :disabled="isForceDeleting" @click="submitForceDelete">
                    <Loader2 v-if="isForceDeleting" class="mr-2 size-4 animate-spin" />
                    {{ isForceDeleting ? 'Menghapus...' : 'Hapus Permanen' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isStatusModalOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Statistik {{ selectedClassroom?.name }}</DialogTitle>
                <DialogDescription>
                    Rincian data komposisi siswa berdasarkan jenis kelamin dan agama.
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedClassroom" class="space-y-4 py-2">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Jenis Kelamin</span>
                    <div class="mt-2 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-blue-50 p-3 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                            <div class="text-xs font-medium">Laki-laki</div>
                            <div class="mt-1 text-xl font-bold">{{ selectedClassroom.maleCount }}</div>
                        </div>
                        <div class="rounded-lg bg-pink-50 p-3 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300">
                            <div class="text-xs font-medium">Perempuan</div>
                            <div class="mt-1 text-xl font-bold">{{ selectedClassroom.femaleCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-3 dark:border-neutral-800">
                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Distribusi Agama</span>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Islam:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.islam || 0 }}</strong>
                        </div>
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Kristen:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.kristen || 0 }}</strong>
                        </div>
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Katolik:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.katolik || 0 }}</strong>
                        </div>
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Hindu:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.hindu || 0 }}</strong>
                        </div>
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Buddha:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.buddha || 0 }}</strong>
                        </div>
                        <div class="flex justify-between rounded bg-neutral-100 p-2 dark:bg-neutral-800">
                            <span>Khonghucu:</span>
                            <strong class="font-bold">{{ selectedClassroom.religion?.khonghucu || 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" class="w-full" @click="isStatusModalOpen = false">Tutup Statistik</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>