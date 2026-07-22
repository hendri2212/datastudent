<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Major, MajorFormData, MajorStatus } from './types';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    major?: Major | null;
    errors?: Record<string, string>;
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [value: MajorFormData];
}>();

const form = reactive<MajorFormData>({
    code: '',
    name: '',
    status: 'Aktif',
    school_id: undefined,
});

const title = computed(() =>
    props.mode === 'create' ? 'Tambah Jurusan' : 'Edit Jurusan',
);

const description = computed(() =>
    props.mode === 'create'
        ? 'Lengkapi informasi jurusan baru.'
        : 'Perbarui informasi jurusan yang dipilih.',
);

const resetForm = () => {
    form.code = props.major?.code ?? '';
    form.name = props.major?.name ?? '';
    form.status = props.major?.status ?? 'Aktif';
    form.school_id = props.major?.school_id ?? undefined;
};

watch(
    () => [props.open, props.major] as const,
    ([open]) => {
        if (open) {
            resetForm();
        }
    },
);

const submit = () => {
    emit('submit', {
        code: form.code.trim().toLocaleUpperCase('id'),
        name: form.name.trim(),
        status: form.status,
        school_id: form.school_id,
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>{{ description }}</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="major-code">Kode Jurusan</Label>
                        <Input
                            id="major-code"
                            v-model="form.code"
                            maxlength="20"
                            placeholder="Contoh: PPLG"
                            required
                        />
                        <p v-if="errors?.code" class="text-xs text-destructive">
                            {{ errors.code }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            Gunakan singkatan unik maksimal 20 karakter.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="major-name">Nama Jurusan</Label>
                        <Input
                            id="major-name"
                            v-model="form.name"
                            maxlength="100"
                            placeholder="Contoh: Pengembangan Perangkat Lunak dan Gim"
                            required
                        />
                        <p v-if="errors?.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="major-status">Status</Label>
                        <Select
                            :model-value="form.status"
                            @update:model-value="
                                form.status = $event as MajorStatus
                            "
                        >
                            <SelectTrigger id="major-status" class="w-full">
                                <SelectValue placeholder="Pilih status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Aktif">Aktif</SelectItem>
                                <SelectItem value="Nonaktif"
                                    >Nonaktif</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <p
                            v-if="errors?.status"
                            class="text-xs text-destructive"
                        >
                            {{ errors.status }}
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Batal</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : mode === 'create'
                                  ? 'Tambah Jurusan'
                                  : 'Simpan Perubahan'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>