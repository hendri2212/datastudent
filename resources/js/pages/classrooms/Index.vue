<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { 
    Plus, 
    School, 
    Pencil, 
    Trash2, 
    ChevronLeft, 
    ChevronRight, 
    Search,
    GraduationCap,
    Users,
    MoreHorizontal,
    Info,
    CheckCircle2,
    RotateCcw
} from '@lucide/vue';
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
    level: 'X' | 'XI' | 'XII' | 'XIII';
    major: Major;
    studentCount: number;
    status: 'Aktif' | 'Nonaktif';
    maleCount: number;
    femaleCount: number;
    religion: {
        islam: number;
        kristen: number;
        katolik: number;
        hindu: number;
        buddha: number;
        konghucu: number;
    };
    isDeleted?: boolean; 
}

const majors: Major[] = [
    { id: 1, name: 'Rekayasa Perangkat Lunak', code: 'RPL' },
    { id: 2, name: 'Teknik Jaringan Komputer dan Telekomunikasi', code: 'TJKT' },
    { id: 3, name: 'Teknik Kendaraan Ringan', code: 'TKR' },
    { id: 4, name: 'Akuntansi dan Keuangan Lembaga', code: 'AKL' },
    { id: 5, name: 'Manajemen Perkantoran dan Layanan Bisnis', code: 'MPLB' }
];

const calculateReligionDistribution = (totalStudents: number, salt: number) => {
    let remaining = totalStudents;

    const islam = Math.max(0, Math.floor(remaining * (0.75 + (salt % 3) * 0.05)));
    remaining -= islam;

    const kristen = Math.max(0, Math.floor(remaining * 0.4));
    remaining -= kristen;

    const katolik = Math.max(0, Math.floor(remaining * 0.3));
    remaining -= katolik;

    const hindu = Math.max(0, Math.floor(remaining * 0.4));
    remaining -= hindu;

    const buddha = Math.max(0, Math.floor(remaining * 0.5));
    remaining -= buddha;

    const konghucu = remaining; 

    return { islam, kristen, katolik, hindu, buddha, konghucu };
};

const getRandomMaleRatio = (salt: number) => {
    const percentage = 30 + ((salt * 17) % 41);
 
    return percentage / 100;
};

const classroomsList = ref<Classroom[]>(
    Array.from({ length: 100 }, (_, i) => {
        const id = i + 1;
        const major = majors[i % majors.length];
        const level = ['X', 'XI', 'XII', 'XIII'][i % 4] as 'X' | 'XI' | 'XII' | 'XIII';
        const groupNumber = Math.floor(i / majors.length) % 3 + 1; 
        const studentCount = 30 + (i % 7); 
        const status = i % 8 === 7 ? 'Nonaktif' : 'Aktif'; 

        const maleRatio = getRandomMaleRatio(id);
        const maleCount = Math.floor(studentCount * maleRatio);
        const femaleCount = studentCount - maleCount;

        const religion = calculateReligionDistribution(studentCount, id);

        return {
            id,
            name: `${level} ${major.code} ${groupNumber}`,
            level,
            major,
            studentCount,
            status,
            maleCount,
            femaleCount,
            religion,
            isDeleted: false 
        };
    })
);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

watch(searchQuery, () => {
    currentPage.value = 1;
});

// Hanya mengambil kelas yang belum di-soft delete
const nonDeletedClassrooms = computed(() => {
    return classroomsList.value.filter(classroom => !classroom.isDeleted);
});

// Menghitung total seluruh siswa di sekolah (Hanya yang kelasnya Aktif & Tidak Terhapus)
const totalAllStudentsInSchool = computed(() => 
    nonDeletedClassrooms.value
        .filter(c => c.status === 'Aktif')
        .reduce((sum, c) => sum + c.studentCount, 0)
);

const totalClassesCount = computed(() => nonDeletedClassrooms.value.length);
const activeClassesCount = computed(() => nonDeletedClassrooms.value.filter(c => c.status === 'Aktif').length);
const inactiveClassesCount = computed(() => nonDeletedClassrooms.value.filter(c => c.status === 'Nonaktif').length);

const activeClassesPercentage = computed(() => 
    totalClassesCount.value > 0 ? Math.round((activeClassesCount.value / totalClassesCount.value) * 100) : 0
);
const inactiveClassesPercentage = computed(() => 
    totalClassesCount.value > 0 ? 100 - activeClassesPercentage.value : 0
);

// Menghitung statistik kelas dan siswa per jurusan (Siswa nonaktif tidak ikut terhitung)
const classesPerMajor = computed(() => {
    return majors.map(m => {
        const filteredClasses = nonDeletedClassrooms.value.filter(c => c.major.id === m.id);
        const activeClassesOnly = filteredClasses.filter(c => c.status === 'Aktif');
        
        return {
            id: m.id,
            name: m.name,
            code: m.code,
            classCount: filteredClasses.length, // Total kelas terdaftar (Aktif + Nonaktif)
            studentCount: activeClassesOnly.reduce((sum, c) => sum + c.studentCount, 0) // Hanya siswa dari kelas aktif
        };
    });
});

// Fitur pencarian fleksibel untuk mencari Nama, Tingkat, Jurusan, maupun Status (Aktif/Nonaktif/Terhapus)
const filteredClassrooms = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    if (!keyword) {
return classroomsList.value;
}

    return classroomsList.value.filter((classroom) => {
        const textToSearch = [
            classroom.name, 
            classroom.level, 
            classroom.major.name,
            classroom.isDeleted ? 'terhapus' : classroom.status
        ];
        
        return textToSearch.some((value) =>
            value.toLowerCase().includes(keyword)
        );
    });
});

const totalPages = computed(() => Math.ceil(filteredClassrooms.value.length / itemsPerPage));

const paginatedClassrooms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;

    return filteredClassrooms.value.slice(start, end);
});

const prevPage = () => {
 if (currentPage.value > 1) {
currentPage.value--;
} 
};
const nextPage = () => {
 if (currentPage.value < totalPages.value) {
currentPage.value++;
} 
};

// ==========================================
// STATE & FUNGSI TAMBAH/EDIT KELAS
// ==========================================
const isModalOpen = ref(false);
const modalMode = ref<'add' | 'edit'>('add');
const formError = ref('');

const form = ref({
    id: 0,
    level: 'X' as 'X' | 'XI' | 'XII' | 'XIII',
    majorId: majors[0].id,
    groupNumber: '1',
    studentCount: 36,
    status: 'Aktif' as 'Aktif' | 'Nonaktif'
});

const openAddModal = () => {
    modalMode.value = 'add';
    formError.value = '';
    form.value = {
        id: 0,
        level: 'X',
        majorId: majors[0].id,
        groupNumber: '1',
        studentCount: 36,
        status: 'Aktif'
    };
    isModalOpen.value = true;
};

const openEditModal = (classroom: Classroom) => {
    modalMode.value = 'edit';
    formError.value = '';
    
    const nameParts = classroom.name.split(' ');
    const groupNumber = nameParts.length > 2 ? nameParts[nameParts.length - 1] : '1';

    form.value = {
        id: classroom.id,
        level: classroom.level,
        majorId: classroom.major.id,
        groupNumber,
        studentCount: classroom.studentCount,
        status: classroom.status
    };
    isModalOpen.value = true;
};

const saveClassroom = () => {
    const selectedMajor = majors.find(m => m.id === Number(form.value.majorId));

    if (!selectedMajor) {
        formError.value = 'Jurusan tidak valid.';

        return;
    }

    if (!form.value.groupNumber.toString().trim()) {
        formError.value = 'Nomor kelompok rombel harus diisi!';

        return;
    }

    const generatedName = `${form.value.level} ${selectedMajor.code} ${form.value.groupNumber.toString().trim().toUpperCase()}`;
    const total = Number(form.value.studentCount) || 0;

    if (modalMode.value === 'add') {
        const newId = classroomsList.value.length > 0 ? Math.max(...classroomsList.value.map(c => c.id)) + 1 : 1;
        
        const maleRatio = getRandomMaleRatio(newId);
        const mCount = Math.floor(total * maleRatio);
        const fCount = total - mCount;

        const religionDist = calculateReligionDistribution(total, newId);

        classroomsList.value = [
            {
                id: newId,
                name: generatedName,
                level: form.value.level,
                major: selectedMajor,
                studentCount: total,
                status: form.value.status,
                maleCount: mCount,
                femaleCount: fCount,
                religion: religionDist,
                isDeleted: false
            },
            ...classroomsList.value
        ];
    } else {
        const index = classroomsList.value.findIndex(c => c.id === form.value.id);

        if (index !== -1) {
            const current = classroomsList.value[index];
            
            const maleRatio = getRandomMaleRatio(current.id);
            const mCount = Math.floor(total * maleRatio);
            const fCount = total - mCount;

            const religionDist = calculateReligionDistribution(total, current.id);

            classroomsList.value.splice(index, 1, {
                ...current,
                name: generatedName,
                level: form.value.level,
                major: selectedMajor,
                studentCount: total,
                status: form.value.status,
                maleCount: mCount,
                femaleCount: fCount,
                religion: religionDist 
            });
            classroomsList.value = [...classroomsList.value];
        }
    }

    isModalOpen.value = false;
};

// 1. SOFT DELETE (Mengubah flag status data)
const deleteClassroom = (id: number) => {
    const classroom = classroomsList.value.find(c => c.id === id);

    if (classroom) {
        classroom.isDeleted = true;
        classroomsList.value = [...classroomsList.value];
    }
};

// RESTORE DATA DARI SOFT DELETE
const restoreClassroom = (id: number) => {
    const classroom = classroomsList.value.find(c => c.id === id);

    if (classroom) {
        classroom.isDeleted = false;
        classroomsList.value = [...classroomsList.value];
    }
};

// 2. HARD DELETE (Permanen hapus objek data)
const hardDeleteClassroom = (id: number) => {
    const confirmDelete = confirm("Apakah Anda yakin ingin menghapus kelas ini secara permanen? Tindakan ini tidak dapat dibatalkan.");

    if (confirmDelete) {
        classroomsList.value = classroomsList.value.filter(c => c.id !== id);
    }
};

// ==========================================
// STATE & DETAIL DEMOGRAFI KELAS
// ==========================================
const isStatusModalOpen = ref(false);
const detailType = ref<'class' | 'major'>('class');

const selectedClassroom = ref<Classroom | null>(null);
const selectedMajorData = ref<{ id: number; name: string; code: string; studentCount: number } | null>(null);

const openStatusDetail = (classroom: Classroom) => {
    detailType.value = 'class';
    selectedClassroom.value = classroom;
    isStatusModalOpen.value = true;
};

const openMajorDetail = (majorItem: { id: number; name: string; code: string; studentCount: number }) => {
    detailType.value = 'major';
    selectedMajorData.value = majorItem;
    isStatusModalOpen.value = true;
};

const selectedMajorStudentsByLevel = computed(() => {
    if (!selectedMajorData.value) {
return { X: 0, XI: 0, XII: 0, XIII: 0 };
}
    
    const result = { X: 0, XI: 0, XII: 0, XIII: 0 };
    // Hanya hitung siswa dari kelas yang aktif di jurusan ini
    const activeMajorClassrooms = nonDeletedClassrooms.value.filter(
        c => c.major.id === selectedMajorData.value?.id && c.status === 'Aktif'
    );
    
    activeMajorClassrooms.forEach(c => {
        if (c.level in result) {
            result[c.level] += c.studentCount;
        }
    });
    
    return result;
});

const levelHighlights = computed(() => {
    const data = selectedMajorStudentsByLevel.value;
    const values = Object.values(data);
    
    const maxVal = Math.max(...values);
    const minVal = Math.min(...values);

    return {
        max: maxVal,
        min: minVal
    };
});

const malePercentage = computed(() => {
    if (!selectedClassroom.value || selectedClassroom.value.studentCount === 0) {
return 0;
}

    return Math.round((selectedClassroom.value.maleCount / selectedClassroom.value.studentCount) * 100);
});

const femalePercentage = computed(() => {
    if (!selectedClassroom.value || selectedClassroom.value.studentCount === 0) {
return 0;
}

    return 100 - malePercentage.value; 
});

const majorPercentage = computed(() => {
    if (!selectedMajorData.value || totalAllStudentsInSchool.value === 0) {
return 0;
}

    return Math.round((selectedMajorData.value.studentCount / totalAllStudentsInSchool.value) * 100);
});

const radius = 40;
const circumference = 2 * Math.PI * radius;

const strokeDashoffset = computed(() => {
    if (detailType.value === 'class') {
        return circumference - (malePercentage.value / 100) * circumference;
    } else {
        return circumference - (majorPercentage.value / 100) * circumference;
    }
});
</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                    <School class="h-6 w-6 text-neutral-500" />
                    Daftar Kelas & Demografi
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola data rombel aktif dan monitor status keaktifan kelas dengan mudah.
                </p>
            </div>
            
            <Button 
                @click="openAddModal"
                class="inline-flex items-center gap-2 bg-neutral-900 dark:bg-neutral-50 hover:bg-neutral-800 dark:hover:bg-neutral-200 text-white dark:text-neutral-900 text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm cursor-pointer"
            >
                <Plus class="h-4 w-4" />
                Tambah Kelas
            </Button>
        </div>

        <div class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-400">Total Kelas & Siswa Aktif Per Jurusan</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div 
                    v-for="item in classesPerMajor" 
                    :key="item.code" 
                    @click="openMajorDetail(item)"
                    class="p-4 bg-white dark:bg-neutral-900 border rounded-xl shadow-sm space-y-2 cursor-pointer hover:shadow-md hover:border-neutral-400 dark:hover:border-neutral-700 transition-all active:scale-[0.98] select-none"
                >
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        <span>{{ item.code }}</span>
                        <GraduationCap class="h-4 w-4 text-neutral-400" />
                    </div>
                    <div>
                        <div class="text-xl font-bold">{{ item.classCount }} <span class="text-xs font-normal text-muted-foreground">Kelas</span></div>
                        <div class="text-xs text-neutral-500 flex items-center gap-1 mt-0.5">
                            <Users class="h-3 w-3" /> {{ item.studentCount.toLocaleString('id-ID') }} Siswa Aktif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-1">
            <div class="bg-white dark:bg-neutral-900 border rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b pb-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500 flex items-center gap-2">
                        <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                        Status Keaktifan Kelas
                    </h3>
                    <span class="text-xs font-semibold text-neutral-400">{{ totalClassesCount }} Rombel</span>
                </div>
                
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs font-medium">
                            <span>Aktif (Siswa Terhitung)</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ activeClassesCount }} Kelas ({{ activeClassesPercentage }}%)</span>
                        </div>
                        <div class="h-3 w-full bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-emerald-500 rounded-full transition-all duration-500" 
                                :style="{ width: activeClassesPercentage + '%' }"
                            ></div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs font-medium">
                            <span>Nonaktif (Siswa Diabaikan)</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400">{{ inactiveClassesCount }} Kelas ({{ inactiveClassesPercentage }}%)</span>
                        </div>
                        <div class="h-3 w-full bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-rose-500 rounded-full transition-all duration-500" 
                                :style="{ width: inactiveClassesPercentage + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-neutral-200 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">Rombongan Belajar</h3>
                    <p class="text-xs text-muted-foreground">Kelola data rombel. Kolom pencarian mendukung pencarian status "Aktif", "Nonaktif", atau "Terhapus".</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        class="pl-9" 
                        placeholder="Cari kelas, jurusan, atau status (aktif/nonaktif)..." 
                    />
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse table-fixed min-w-[800px]">
                    <thead>
                        <tr class="bg-neutral-50 dark:bg-neutral-800/50 border-b border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 text-xs font-semibold uppercase tracking-wider">
                            <th class="px-2 py-4 w-16 text-center">No</th>
                            <th class="px-6 py-4 w-32">Tingkat</th> 
                            <th class="px-6 py-4 w-45">Nama Kelas</th>
                            <th class="px-6 py-4">Jurusan</th>
                            <th class="px-6 py-4 w-36">Status</th> 
                            <th class="px-6 py-4 w-36 text-right">Total Siswa</th>
                            <th class="px-6 py-4 w-32 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800 text-sm text-neutral-700 dark:text-neutral-300">
                        <template v-if="filteredClassrooms.length > 0">
                            <tr 
                                v-for="(classroom, index) in paginatedClassrooms" 
                                :key="classroom.id"
                                class="h-[61px] border-b transition-all duration-300 last:border-0 hover:bg-muted/30"
                                :class="{ 'opacity-40 bg-neutral-50/50 dark:bg-neutral-950/20 select-none': classroom.isDeleted }"
                            >
                                <td class="px-2 py-4 text-center font-semibold text-neutral-500 w-16">
                                    {{ ((currentPage - 1) * itemsPerPage) + index + 1 }}
                                </td>
                                <td class="px-6 py-4 truncate w-28">
                                    <Badge :variant="classroom.isDeleted ? 'outline' : 'secondary'">
                                        Kelas {{ classroom.level }}
                                    </Badge>
                                </td>
                                <td 
                                    class="px-6 py-4 font-bold truncate w-45 text-neutral-900 dark:text-neutral-100"
                                    :class="{ 'line-through text-neutral-400': classroom.isDeleted }"
                                >
                                    {{ classroom.name }}
                                </td>
                                <td class="px-6 py-4 text-muted-foreground min-w-0">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <GraduationCap class="size-4" />
                                        </div>
                                        <span class="font-medium text-foreground truncate block">{{ classroom.major.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 truncate w-28">
                                    <Badge 
                                        :variant="classroom.isDeleted ? 'outline' : (classroom.status === 'Aktif' ? 'default' : 'secondary')"
                                    >
                                        {{ classroom.isDeleted ? 'Terhapus' : classroom.status }}
                                    </Badge>
                                </td>
                                <td 
                                    class="px-6 py-4 text-right tabular-nums truncate w-36 font-semibold"
                                    :class="[classroom.status === 'Nonaktif' || classroom.isDeleted ? 'text-neutral-400 italic font-normal text-xs' : 'text-neutral-900 dark:text-neutral-100']"
                                >
                                    {{ classroom.studentCount.toLocaleString('id-ID') }} Siswa
                                    <span v-if="classroom.status === 'Nonaktif' && !classroom.isDeleted" class="block text-[10px] text-rose-500 font-medium">(Tak Terhitung)</span>
                                </td>
                                <td class="px-6 py-4 text-right w-32">
                                    <div v-if="classroom.isDeleted" class="flex justify-end gap-1">
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="cursor-pointer text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20"
                                            title="Pulihkan Kelas"
                                            @click="restoreClassroom(classroom.id)"
                                        >
                                            <RotateCcw class="size-4" />
                                        </Button>
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="cursor-pointer text-destructive hover:bg-destructive/10"
                                            title="Hapus Permanen"
                                            @click="hardDeleteClassroom(classroom.id)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                    <DropdownMenu v-else>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="cursor-pointer">
                                                <MoreHorizontal class="size-4" />
                                                <span class="sr-only">Aksi {{ classroom.name }}</span>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-40">
                                            <DropdownMenuItem @click="openStatusDetail(classroom)" class="cursor-pointer">
                                                <Info class="mr-2 size-3.5 text-indigo-500" />
                                                Lihat Demografi
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openEditModal(classroom)" class="cursor-pointer">
                                                <Pencil class="mr-2 size-3.5" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteClassroom(classroom.id)" class="text-destructive focus:text-destructive cursor-pointer">
                                                <Trash2 class="mr-2 size-3.5" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </template>

                        <tr v-else class="h-[61px]">
                            <td colspan="7" class="px-6 text-center text-muted-foreground italic">
                                Rombel, jurusan, atau status yang dicari tidak ditemukan.
                            </td>
                        </tr>

                        <tr 
                            v-for="emptyRow in (itemsPerPage - (paginatedClassrooms.length || 1))" 
                            :key="'blank-' + emptyRow"
                            class="h-[61px] border-b last:border-0 bg-transparent"
                        >
                            <td class="w-16 px-2"></td>
                            <td class="w-28"></td>
                            <td class="w-45"></td>
                            <td></td> 
                            <td class="w-28"></td>
                            <td class="w-36"></td>
                            <td class="w-32"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/20 flex items-center justify-between">
                <span class="text-xs text-muted-foreground">
                    Menampilkan <b>{{ filteredClassrooms.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }}</b> - <b>{{ Math.min(currentPage * itemsPerPage, filteredClassrooms.length) }}</b> dari <b>{{ filteredClassrooms.length }}</b> data hasil pencarian.
                </span>

                <div class="flex items-center gap-2">
                    <button 
                        @click="prevPage" 
                        :disabled="currentPage === 1"
                        class="p-2 border rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors cursor-pointer"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    
                    <span class="text-sm font-medium px-2 text-neutral-700 dark:text-neutral-300">
                        {{ currentPage }} / {{ totalPages || 1 }}
                    </span>

                    <button 
                        @click="nextPage" 
                        :disabled="currentPage === totalPages || totalPages === 0"
                        class="p-2 border rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors cursor-pointer"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ modalMode === 'add' ? 'Tambah Kelas' : 'Edit Kelas' }}</DialogTitle>
                <DialogDescription>
                    Silakan isi detail kelas di bawah ini. Kelas akan terbentuk otomatis berdasarkan Tingkat, Kode Jurusan, dan Kelompok.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <p v-if="formError" class="text-sm font-medium text-destructive bg-destructive/10 p-2 rounded-lg text-center">
                    {{ formError }}
                </p>

                <div class="grid gap-2">
                    <Label for="level" class="text-left font-medium">Tingkat Kelas <span class="text-destructive">*</span></Label>
                    <select
                        id="level"
                        v-model="form.level"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="X">Kelas X</option>
                        <option value="XI">Kelas XI</option>
                        <option value="XII">Kelas XII</option>
                        <option value="XIII">Kelas XIII</option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label for="major" class="text-left font-medium">Kompetensi Keahlian (Jurusan) <span class="text-destructive">*</span></Label>
                    <select
                        id="major"
                        v-model="form.majorId"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option v-for="major in majors" :key="major.id" :value="major.id">
                            {{ major.code }} - {{ major.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label for="groupNumber" class="text-left font-medium">Kelompok / Rombel <span class="text-destructive">*</span></Label>
                    <Input
                        id="groupNumber"
                        v-model="form.groupNumber"
                        placeholder="Contoh: 1, 2, atau A"
                        required
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="studentCount" class="text-left font-medium">Jumlah Siswa</Label>
                    <Input
                        id="studentCount"
                        type="number"
                        v-model="form.studentCount"
                        placeholder="Masukkan total siswa"
                        min="0"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="status" class="text-left font-medium">Status Kelas</Label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="isModalOpen = false">
                    Batal
                </Button>
                <Button type="button" @click="saveClassroom">
                    Simpan Perubahan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isStatusModalOpen">
        <DialogContent class="sm:max-w-[450px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                    <Info class="h-5 w-5 text-indigo-500" />
                    <span v-if="detailType === 'class'">Demografi Kelas: {{ selectedClassroom?.name }}</span>
                    <span v-else>Statistik Jurusan: {{ selectedMajorData?.code }}</span>
                </DialogTitle>
                <DialogDescription>
                    <span v-if="detailType === 'class'">Rasio perbandingan gender siswa serta rincian pemeluk agama di dalam kelas pilihan.</span>
                    <span v-else>Rincian sebaran siswa dan kontribusi total dari kompetensi keahlian terkait.</span>
                </DialogDescription>
            </DialogHeader>

            <div v-if="detailType === 'class' && selectedClassroom" class="space-y-6 py-4 flex flex-col items-center">
                <div class="relative flex items-center justify-center">
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle
                            cx="64"
                            cy="64"
                            :r="radius"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="transparent"
                            class="text-pink-400 dark:text-pink-500/40"
                        />
                        <circle
                            cx="64"
                            cy="64"
                            :r="radius"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="transparent"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="strokeDashoffset"
                            stroke-linecap="round"
                            class="text-blue-500 dark:text-blue-400 transition-all duration-700 ease-in-out"
                        />
                    </svg>
                    <div class="absolute flex flex-col items-center bg-white dark:bg-neutral-900 px-2 py-1 rounded-md">
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                            L: {{ malePercentage }}%
                        </span>
                        <div class="w-8 h-[1px] bg-neutral-200 dark:bg-neutral-700 my-0.5"></div>
                        <span class="text-xs font-bold text-pink-600 dark:text-pink-400">
                            P: {{ femalePercentage }}%
                        </span>
                    </div>
                </div>

                <div class="w-full grid grid-cols-2 gap-4 text-center px-4">
                    <div class="p-2.5 rounded-lg border bg-blue-50/50 dark:bg-blue-950/10 border-blue-100 dark:border-blue-900/40">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium block">Laki-Laki (L)</span>
                        <span class="text-lg font-bold text-blue-700 dark:text-blue-300">
                            {{ selectedClassroom.maleCount }} <span class="text-xs font-normal">Siswa</span>
                        </span>
                        <span class="text-xs font-bold block text-blue-500 mt-0.5">({{ malePercentage }}%)</span>
                    </div>
                    <div class="p-2.5 rounded-lg border bg-pink-50/50 dark:bg-pink-950/10 border-pink-100 dark:border-pink-900/40">
                        <span class="text-xs text-pink-600 dark:text-pink-400 font-medium block">Perempuan (P)</span>
                        <span class="text-lg font-bold text-pink-700 dark:text-pink-300">
                            {{ selectedClassroom.femaleCount }} <span class="text-xs font-normal">Siswi</span>
                        </span>
                        <span class="text-xs font-bold block text-pink-500 mt-0.5">({{ femalePercentage }}%)</span>
                    </div>
                </div>

                <hr class="w-full border-neutral-200 dark:border-neutral-800" />

                <div class="w-full space-y-3">
                    <h4 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">
                        Distribusi Agama Kelas
                    </h4>
                    
                    <div class="space-y-2">
                        <div v-for="(count, key) in selectedClassroom.religion" :key="key" class="flex justify-between items-center text-sm border-b pb-1.5 border-neutral-100 dark:border-neutral-800/80">
                            <span class="font-medium text-neutral-600 dark:text-neutral-400 capitalize">{{ key }}</span>
                            <span class="font-bold tabular-nums text-neutral-800 dark:text-neutral-200">
                                {{ count }} Siswa
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="detailType === 'major' && selectedMajorData" class="space-y-6 py-4 flex flex-col items-center">
                <div class="relative flex items-center justify-center">
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle
                            cx="64"
                            cy="64"
                            :r="radius"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="transparent"
                            class="text-neutral-100 dark:text-neutral-800"
                        />
                        <circle
                            cx="64"
                            cy="64"
                            :r="radius"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="transparent"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="strokeDashoffset"
                            stroke-linecap="round"
                            class="text-indigo-600 dark:text-indigo-400 transition-all duration-700 ease-in-out"
                        />
                    </svg>
                    <div class="absolute flex flex-col items-center">
                        <span class="text-2xl font-black tracking-tight text-neutral-900 dark:text-neutral-100">
                            {{ majorPercentage }}%
                        </span>
                        <span class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">
                            Rasio Siswa Aktif
                        </span>
                    </div>
                </div>

                <div class="w-full text-center px-4 space-y-4">
                    <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                        Jurusan <b>{{ selectedMajorData.name }} ({{ selectedMajorData.code }})</b> memiliki kontribusi total sebanyak <b>{{ selectedMajorData.studentCount.toLocaleString('id-ID') }} siswa aktif</b> terhitung, setara dengan <b>{{ majorPercentage }}%</b> dari total seluruh siswa aktif di sekolah ini.
                    </p>

                    <div class="border rounded-xl p-4 bg-neutral-50/50 dark:bg-neutral-800/30 text-left space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-400">Jumlah Siswa Per Kelas (Hanya Kelas Aktif)</h4>
                        
                        <div class="space-y-2">
                            <div 
                                v-for="(count, level) in selectedMajorStudentsByLevel" 
                                :key="level"
                                class="flex justify-between items-center text-sm border-b pb-1 last:border-0 last:pb-0"
                            >
                                <span class="font-medium text-neutral-600 dark:text-neutral-400">Kelas {{ level }}</span>
                                <span 
                                    class="font-bold tabular-nums transition-colors"
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400': count === levelHighlights.max && levelHighlights.max !== levelHighlights.min,
                                        'text-rose-600 dark:text-rose-400': count === levelHighlights.min && levelHighlights.max !== levelHighlights.min,
                                        'text-neutral-800 dark:text-neutral-200': count !== levelHighlights.max && count !== levelHighlights.min
                                    }"
                                >
                                    {{ count }} Siswa
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" class="w-full" @click="isStatusModalOpen = false">
                    Tutup Statistik
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>