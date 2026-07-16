<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    MoreHorizontal
} from '@lucide/vue';
import { ref, computed, watch } from 'vue'; 
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
}

const majors: Major[] = [
    { id: 1, name: 'Rekayasa Perangkat Lunak', code: 'RPL' },
    { id: 2, name: 'Teknik Jaringan Komputer dan Telekomunikasi', code: 'TJKT' },
    { id: 3, name: 'Teknik Kendaraan Ringan', code: 'TKR' },
    { id: 4, name: 'Akuntansi dan Keuangan Lembaga', code: 'AKL' },
    { id: 5, name: 'Manajemen Perkantoran dan Layanan Bisnis', code: 'MPLB' }
];

const classrooms: Classroom[] = Array.from({ length: 100 }, (_, i) => {
    const id = i + 1;
    const major = majors[i % majors.length];
    const level = ['X', 'XI', 'XII', 'XIII'][i % 4] as 'X' | 'XI' | 'XII' | 'XIII';
    const groupNumber = Math.floor(i / majors.length) % 3 + 1; 
    const studentCount = 30 + (i % 7); 
    const status = i % 6 === 5 ? 'Nonaktif' : 'Aktif';

    return {
        id: id,
        name: `${level} ${major.code} ${groupNumber}`,
        level: level,
        major: major,
        studentCount: studentCount,
        status: status
    };
});

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

watch(searchQuery, () => {
    currentPage.value = 1;
});

const totalStudents = computed(() => classrooms.reduce((sum, c) => sum + c.studentCount, 0));

const classesPerMajor = computed(() => {
    return majors.map(m => {
        const filtered = classrooms.filter(c => c.major.id === m.id);
        return {
            code: m.code,
            classCount: filtered.length,
            studentCount: filtered.reduce((sum, c) => sum + c.studentCount, 0)
        };
    });
});

const filteredClassrooms = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    if (!keyword) return classrooms;

    return classrooms.filter((classroom) =>
        [classroom.name, classroom.level, classroom.major.name].some((value) =>
            value.toLowerCase().includes(keyword)
        )
    );
});

const totalPages = computed(() => Math.ceil(filteredClassrooms.value.length / itemsPerPage));

const paginatedClassrooms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredClassrooms.value.slice(start, end);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

const editClassroom = (classroom: Classroom) => {
    console.log('Mengedit kelas:', classroom.name);
};

const deleteClassroom = (id: number) => {
    if (confirm('Apakah kamu yakin ingin menghapus data kelas ini?')) {
        console.log('Menghapus kelas dengan ID:', id);
    }
};
</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                    <School class="h-6 w-6 text-neutral-500" />
                    Daftar Kelas
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola tingkatan rombel dan distribusi total siswa per kompetensi keahlian.
                </p>
            </div>
            
            <Link 
                href="#" 
                class="inline-flex items-center gap-2 bg-neutral-900 dark:bg-neutral-50 hover:bg-neutral-800 dark:hover:bg-neutral-200 text-white dark:text-neutral-900 text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm"
            >
                <Plus class="h-4 w-4" />
                Tambah Kelas
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div 
                v-for="item in classesPerMajor" 
                :key="item.code" 
                class="p-4 bg-white dark:bg-neutral-900 border rounded-xl shadow-sm space-y-2"
            >
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    <span>{{ item.code }}</span>
                    <GraduationCap class="h-4 w-4 text-neutral-400" />
                </div>
                <div>
                    <div class="text-xl font-bold">{{ item.classCount }} <span class="text-xs font-normal text-muted-foreground">Kelas</span></div>
                    <div class="text-xs text-neutral-500 flex items-center gap-1 mt-0.5">
                        <Users class="h-3 w-3" /> {{ item.studentCount }} Siswa
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-neutral-200 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">Rombongan Belajar</h3>
                    <p class="text-xs text-muted-foreground">Menampilkan total seluruh siswa aktif (Total: {{ totalStudents }} Siswa).</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        class="pl-9" 
                        placeholder="Cari kelas, tingkat, atau jurusan..." 
                    />
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse table-fixed min-w-[800px]">
                    <thead>
                        <tr class="bg-neutral-50 dark:bg-neutral-800/50 border-b border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 text-xs font-semibold uppercase tracking-wider">
                            <th class="px-6 py-4 w-16 text-center">No</th>
                            <th class="px-6 py-4 w-32">Tingkat</th> 
                            <th class="px-6 py-4 w-40">Nama Kelas</th>
                            <th class="px-6 py-4">Jurusan</th>
                            <th class="px-6 py-4 w-36">Status</th> 
                            <th class="px-6 py-4 w-32 text-right">Total Siswa</th>
                            <th class="px-6 py-4 w-24 text-right">Aksi</th>
                        </tr>
                    </thead>
                                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800 text-sm text-neutral-700 dark:text-neutral-300">
                    <template v-if="filteredClassrooms.length > 0">
                        <tr 
                            v-for="(classroom, index) in paginatedClassrooms" 
                            :key="classroom.id"
                            class="h-[61px] border-b transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-6 py-4 text-center font-medium text-muted-foreground truncate w-16">
                                {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                            </td>
                            <td class="px-6 py-4 truncate w-28">
                                <Badge variant="secondary">
                                    Kelas {{ classroom.level }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 font-semibold truncate w-40">
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
                                <Badge :variant="classroom.status === 'Aktif' ? 'default' : 'secondary'">
                                    {{ classroom.status }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-right tabular-nums truncate w-32">
                                {{ classroom.studentCount.toLocaleString('id-ID') }}
                            </td>
                            <td class="px-6 py-4 text-right w-24">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="size-4" />
                                            <span class="sr-only">Aksi {{ classroom.name }}</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-32">
                                        <DropdownMenuItem @click="editClassroom(classroom)" class="cursor-pointer">
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
                            Rombel atau jurusan yang dicari tidak ditemukan.
                        </td>
                    </tr>

                    <tr 
                        v-for="emptyRow in (itemsPerPage - (paginatedClassrooms.length || 1))" 
                        :key="'blank-' + emptyRow"
                        class="h-[61px] border-b last:border-0 bg-transparent"
                    >
                        <td class="w-16"></td>
                        <td class="w-28"></td>
                        <td class="w-40"></td>
                        <td></td> 
                        <td class="w-28"></td>
                        <td class="w-32"></td>
                        <td class="w-24"></td>
                    </tr>
                </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/20 flex items-center justify-between">
                <span class="text-xs text-muted-foreground">
                    Menampilkan <b>{{ (currentPage - 1) * itemsPerPage + 1 }}</b> - <b>{{ Math.min(currentPage * itemsPerPage, filteredClassrooms.length) }}</b> dari <b>{{ filteredClassrooms.length }}</b> data hasil pencarian.
                </span>

                <div class="flex items-center gap-2">
                    <button 
                        @click="prevPage" 
                        :disabled="currentPage === 1"
                        class="p-2 border rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    
                    <span class="text-sm font-medium px-2 text-neutral-700 dark:text-neutral-300">
                        {{ currentPage }} / {{ totalPages || 1 }}
                    </span>

                    <button 
                        @click="nextPage" 
                        :disabled="currentPage === totalPages || totalPages === 0"
                        class="p-2 border rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>