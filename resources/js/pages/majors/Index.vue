<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    GraduationCap,
    MoreHorizontal,
    Plus,
    Search,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { index as majorsIndex } from '@/routes/majors';

type Major = {
    id: number;
    code: string;
    name: string;
    studentCount: number;
    status: 'Aktif' | 'Nonaktif';
};

const majors: Major[] = [
    {
        id: 1,
        code: 'TJKT',
        name: 'Teknik Jaringan Komputer dan Telekomunikasi',
        studentCount: 216,
        status: 'Aktif',
    },
    {
        id: 2,
        code: 'PPLG',
        name: 'Pengembangan Perangkat Lunak dan Gim',
        studentCount: 198,
        status: 'Aktif',
    },
    {
        id: 3,
        code: 'TKR',
        name: 'Teknik Kendaraan Ringan',
        studentCount: 184,
        status: 'Aktif',
    },
    {
        id: 4,
        code: 'AKL',
        name: 'Akuntansi dan Keuangan Lembaga',
        studentCount: 172,
        status: 'Aktif',
    },
    {
        id: 5,
        code: 'MPLB',
        name: 'Manajemen Perkantoran dan Layanan Bisnis',
        studentCount: 165,
        status: 'Aktif',
    },
    {
        id: 6,
        code: 'PM',
        name: 'Pemasaran',
        studentCount: 0,
        status: 'Nonaktif',
    },
];

const search = ref('');

const filteredMajors = computed(() => {
    const keyword = search.value.trim().toLocaleLowerCase('id');

    if (!keyword) {
        return majors;
    }

    return majors.filter((major) =>
        [major.code, major.name, major.status].some((value) =>
            value.toLocaleLowerCase('id').includes(keyword),
        ),
    );
});

const activeMajors = computed(
    () => majors.filter((major) => major.status === 'Aktif').length,
);
const totalStudents = computed(() =>
    majors.reduce((total, major) => total + major.studentCount, 0),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Jurusan',
                href: majorsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Jurusan" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Jurusan</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola data kompetensi keahlian yang tersedia di sekolah.
                </p>
            </div>
            <Button class="w-full sm:w-auto">
                <Plus class="size-4" />
                Tambah Jurusan
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Jurusan</CardTitle
                    >
                    <GraduationCap class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ majors.length }}</div>
                    <p class="text-xs text-muted-foreground">
                        Seluruh kompetensi keahlian
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Jurusan Aktif</CardTitle
                    >
                    <BookOpenCheck class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ activeMajors }}</div>
                    <p class="text-xs text-muted-foreground">Menerima siswa</p>
                </CardContent>
            </Card>
            <Card class="sm:col-span-2 lg:col-span-1">
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Siswa</CardTitle
                    >
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ totalStudents.toLocaleString('id-ID') }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Dari semua jurusan
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader
                class="gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Daftar Jurusan</CardTitle>
                    <CardDescription>
                        Data berikut masih menggunakan data dummy.
                    </CardDescription>
                </div>
                <div class="relative w-full sm:w-72">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-9"
                        placeholder="Cari jurusan..."
                    />
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-y bg-muted/40 text-left">
                            <tr>
                                <th class="h-11 px-6 font-medium">Kode</th>
                                <th class="h-11 px-6 font-medium">
                                    Nama Jurusan
                                </th>
                                <th class="h-11 px-6 text-right font-medium">
                                    Siswa
                                </th>
                                <th class="h-11 px-6 font-medium">Status</th>
                                <th class="h-11 w-16 px-6"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="major in filteredMajors"
                                :key="major.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-6 py-4 font-mono font-medium">
                                    {{ major.code }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                        >
                                            <GraduationCap class="size-4" />
                                        </div>
                                        <span class="font-medium">{{
                                            major.name
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums">
                                    {{
                                        major.studentCount.toLocaleString(
                                            'id-ID',
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    <Badge
                                        :variant="
                                            major.status === 'Aktif'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{ major.status }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="size-4" />
                                        <span class="sr-only"
                                            >Aksi {{ major.name }}</span
                                        >
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="filteredMajors.length === 0">
                                <td
                                    colspan="5"
                                    class="h-32 px-6 text-center text-muted-foreground"
                                >
                                    Jurusan yang dicari tidak ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
