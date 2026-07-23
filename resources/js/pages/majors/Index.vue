<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    GraduationCap,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-vue-next';
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
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import MajorFormDialog from './MajorFormDialog.vue';
import type { Major, MajorFormData } from './types';

const props = defineProps<{
    majors: Major[];
    filters: { search?: string };
}>();

const search = ref(props.filters?.search || '');
const formOpen = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const selectedMajor = ref<Major | null>(null);
const deleteDialogOpen = ref(false);

const filteredMajors = computed(() => {
    if (!search.value) return props.majors;
    const query = search.value.toLowerCase();
    return props.majors.filter(
        (m) =>
            m.code.toLowerCase().includes(query) ||
            m.name.toLowerCase().includes(query) ||
            m.status.toLowerCase().includes(query),
    );
});

const activeMajors = computed(
    () => props.majors.filter((m) => m.status === 'Aktif').length,
);

const totalStudents = computed(() =>
    props.majors.reduce((sum, m) => sum + (m.studentCount || 0), 0),
);

const form = useForm<MajorFormData>({
    code: '',
    name: '',
    status: 'Aktif',
    school_id: undefined,
});

const openCreateForm = () => {
    formMode.value = 'create';
    selectedMajor.value = null;
    form.reset();
    form.clearErrors();
    formOpen.value = true;
};

const openEditForm = (major: Major) => {
    formMode.value = 'edit';
    selectedMajor.value = major;
    form.code = major.code;
    form.name = major.name;
    form.status = major.status;
    form.school_id = major.school_id;
    form.clearErrors();
    formOpen.value = true;
};

const openDeleteDialog = (major: Major) => {
    selectedMajor.value = major;
    deleteDialogOpen.value = true;
};

const saveMajor = (data: MajorFormData) => {
    form.code = data.code;
    form.name = data.name;
    form.status = data.status;
    form.school_id = data.school_id;

    if (formMode.value === 'create') {
        form.post('/majors', {
            onSuccess: () => {
                formOpen.value = false;
                form.reset();
            },
        });
    } else if (selectedMajor.value) {
        form.put(`/majors/${selectedMajor.value.id}`, {
            onSuccess: () => {
                formOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteMajor = () => {
    if (!selectedMajor.value) return;

    router.delete(`/majors/${selectedMajor.value.id}`, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            selectedMajor.value = null;
        },
    });
};
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
            <Button class="w-full sm:w-auto" @click="openCreateForm">
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
                        Daftar kompetensi keahlian dan jumlah siswa terdaftar.
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
                                <th class="h-11 px-6 text-right font-medium">
                                    Aksi
                                </th>
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
                                        (major.studentCount || 0).toLocaleString(
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
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Edit jurusan"
                                            @click="openEditForm(major)"
                                        >
                                            <Pencil class="size-4" />
                                            <span class="sr-only">
                                                Edit {{ major.name }}
                                            </span>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:text-destructive"
                                            title="Hapus jurusan"
                                            @click="openDeleteDialog(major)"
                                        >
                                            <Trash2 class="size-4" />
                                            <span class="sr-only">
                                                Hapus {{ major.name }}
                                            </span>
                                        </Button>
                                    </div>
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

        <MajorFormDialog
            v-model:open="formOpen"
            :mode="formMode"
            :major="selectedMajor"
            :errors="form.errors"
            :processing="form.processing"
            @submit="saveMajor"
        />

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus Jurusan?</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus jurusan
                        <strong class="font-medium text-foreground">
                            {{ selectedMajor?.name }}
                        </strong>
                        ? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteMajor">
                        Hapus Jurusan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>