<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ClipboardCheck,
    ArrowRight,
    ArrowLeft,
    GraduationCap,
    UserCheck,
    Instagram,
    Globe,
    School,
    Users,
    ClipboardList,
    Heart,
    FileArchive,
    CheckCircle2,
    AlertCircle,
    RotateCcw,
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';

// --- WIZARD LOGIC ---
const currentStep = ref(1);
const totalSteps = 7;
const errorMessage = ref('');

const steps = [
    { id: 1, name: 'Identitas Diri' },
    { id: 2, name: 'Kelas & Jurusan' },
    { id: 3, name: 'Keluarga & Wali' },
    { id: 4, name: 'Riwayat Sekolah' },
    { id: 5, name: 'Kesehatan' },
    { id: 6, name: 'Unggah Dokumen' },
    { id: 7, name: 'Peninjauan Data' },
];

const currentYear = 2026;

// Daftar Dokumen Wajib & Opsional
const requiredDocTypes = [
    'Kartu Keluarga (KK)',
    'Akta Kelahiran',
    'Ijazah SD',
    'Ijazah SMP',
];

const optionalDocTypes = ['KTP / KIA'];

// Gabungan semua tipe dokumen untuk tampilan form
const allDocTypes = [
    ...requiredDocTypes.map((doc) => ({ name: doc, required: true })),
    ...optionalDocTypes.map((doc) => ({ name: doc, required: false })),
];

// --- MASTER DATA ---
const internalGenders = [
    { id: 1, name: 'Laki-laki' },
    { id: 2, name: 'Perempuan' },
];

const internalReligions = [
    { id: 1, name: 'Islam' },
    { id: 2, name: 'Kristen' },
    { id: 3, name: 'Katolik' },
    { id: 4, name: 'Hindu' },
    { id: 5, name: 'Buddha' },
    { id: 6, name: 'Khonghucu' },
];

const internalMajors = [
    { id: 1, name: 'Rekayasa Perangkat Lunak', code: 'RPL' },
    { id: 2, name: 'Akuntansi & Keuangan Lembaga', code: 'AKL' },
    { id: 3, name: 'Desain Komunikasi Visual', code: 'DKV' },
    { id: 4, name: 'Teknik Komputer & Jaringan', code: 'TKJ' },
];

const internalClassrooms = [
    // RPL (major_id: 1)
    { id: 1, major_id: 1, name: 'X RPL 1' },
    { id: 2, major_id: 1, name: 'X RPL 2' },
    { id: 3, major_id: 1, name: 'XI RPL 1' },
    { id: 4, major_id: 1, name: 'XI RPL 2' },
    { id: 5, major_id: 1, name: 'XII RPL 1' },
    { id: 6, major_id: 1, name: 'XII RPL 2' },

    // AKL (major_id: 2)
    { id: 7, major_id: 2, name: 'X AKL 1' },
    { id: 8, major_id: 2, name: 'XI AKL 1' },
    { id: 9, major_id: 2, name: 'XII AKL 1' },

    // DKV (major_id: 3)
    { id: 10, major_id: 3, name: 'X DKV 1' },
    { id: 11, major_id: 3, name: 'XI DKV 1' },
    { id: 12, major_id: 3, name: 'XII DKV 1' },

    // TKJ (major_id: 4)
    { id: 13, major_id: 4, name: 'X TKJ 1' },
    { id: 14, major_id: 4, name: 'XI TKJ 1' },
    { id: 15, major_id: 4, name: 'XII TKJ 1' },
];

// --- FORM STATE ---
const form = useForm({
    is_locked: false,

    // Step 1: Identitas
    name: '',
    nis: '',
    nisn: '',
    birth_place: '',
    birth_date: '',
    gender_id: 1,
    religion_id: 1,
    email: '',
    phone: '',
    instagram_url: '',
    linkedin_url: '',
    address: '',

    // Step 2: Pemetaan Kelas & Jurusan
    major_id: 1,
    classroom_id: 1,
    student_status_id: 1,

    // Step 3: Keluarga & Wali
    family: {
        father_name: '',
        father_phone: '',
        mother_name: '',
        mother_phone: '',
        guardian_name: '',
        guardian_phone: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
    },

    // Step 4: Riwayat Pendidikan
    education_history: {
        school_name: '',
        npsn: '',
        entry_year: '',
        graduation_year: '',
        final_score: '',
    },

    // Step 5: Kondisi Kesehatan
    health: {
        height: '',
        weight: '',
        blood_type_id: 4,
        allergies: '',
        medical_history: '',
        hospital: '',
        doctor: '',
    },

    // Step 6: Upload Dokumen
    documents: [] as Array<{ docType: string; file: File; fileName: string }>,
});

// --- PERSISTENCE / LOCAL STORAGE AUTO-SAVE LOGIC ---
const STORAGE_KEY = 'ppdb_registration_draft_v1';

const saveDraftToLocalStorage = () => {
    try {
        const dataToSave = {
            currentStep: currentStep.value,
            formData: {
                ...form.data(),
                documents: [], // Objek File tidak disimpan ke JSON, tersimpan secara terpisah
            },
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
    } catch (e) {
        console.error('Gagal menyimpan draft:', e);
    }
};

const loadDraftFromLocalStorage = () => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);

        if (saved) {
            const parsed = JSON.parse(saved);

            if (parsed.currentStep) {
                currentStep.value = parsed.currentStep;
            }

            if (parsed.formData) {
                Object.assign(form, parsed.formData);
            }
        }
    } catch (e) {
        console.error('Gagal memuat draft:', e);
    }
};

const clearDraft = () => {
    if (
        confirm('Apakah Anda yakin ingin mengosongkan seluruh isian formulir?')
    ) {
        localStorage.removeItem(STORAGE_KEY);
        form.reset();
        currentStep.value = 1;
        errorMessage.value = '';
    }
};

// Simpan draft secara otomatis tiap kali data form atau langkah berubah
watch(form, () => saveDraftToLocalStorage(), { deep: true });
watch(currentStep, () => saveDraftToLocalStorage());

onMounted(() => {
    loadDraftFromLocalStorage();
});

// --- FILTERED CLASSROOMS COMPUTED & WATCHER ---
const filteredClassrooms = computed(() => {
    return internalClassrooms.filter((c) => c.major_id === form.major_id);
});

watch(
    () => form.major_id,
    (newMajorId) => {
        const validClassrooms = internalClassrooms.filter(
            (c) => c.major_id === newMajorId,
        );

        if (validClassrooms.length > 0) {
            // Hanya set ulang jika kelas saat ini tidak berada di jurusan baru
            const currentClassValid = validClassrooms.some(
                (c) => c.id === form.classroom_id,
            );

            if (!currentClassValid) {
                form.classroom_id = validClassrooms[0].id;
            }
        }
    },
);

// Computed untuk mengecek apakah seluruh dokumen wajib telah terunggah
const isDocumentsComplete = computed(() => {
    const uploadedTypes = form.documents.map((d) => d.docType);

    return requiredDocTypes.every((type) => uploadedTypes.includes(type));
});

// Helper untuk mengecek status per dokumen
const isDocUploaded = (docType: string) => {
    return form.documents.some((d) => d.docType === docType);
};

// --- VALIDATION RULES PER STEP ---
const isStepValid = computed(() => {
    switch (currentStep.value) {
        case 1: // Identitas Diri
            return (
                form.name.trim() !== '' &&
                form.nis.trim() !== '' &&
                form.nisn.trim().length === 10 &&
                form.birth_place.trim() !== '' &&
                form.birth_date !== '' &&
                form.phone.trim() !== '' &&
                form.address.trim() !== ''
            );
        case 2: // Kelas & Jurusan
            return form.major_id > 0 && form.classroom_id > 0;
        case 3: // Keluarga & Wali (Minimal Ibu/Ayah & Kontak Darurat)
            return (
                (form.family.father_name.trim() !== '' ||
                    form.family.mother_name.trim() !== '') &&
                form.family.emergency_contact_name.trim() !== '' &&
                form.family.emergency_contact_phone.trim() !== ''
            );
        case 4: // Riwayat Sekolah
            return (
                form.education_history.school_name.trim() !== '' &&
                form.education_history.graduation_year.trim() !== '' &&
                form.education_history.final_score.trim() !== ''
            );
        case 5: // Kesehatan
            return (
                form.health.height.trim() !== '' &&
                form.health.weight.trim() !== ''
            );
        case 6: // Dokumen Wajib
            return isDocumentsComplete.value;
        case 7: // Peninjauan Data
            return true;
        default:
            return false;
    }
});

// --- HELPER FUNCTIONS ---
const filterNumbers = (
    event: Event,
    obj: any,
    key: string,
    maxVal?: number,
) => {
    const target = event.target as HTMLInputElement;
    let val = target.value.replace(/\D/g, '');

    if (maxVal && Number(val) > maxVal) {
        val = String(maxVal);
    }

    obj[key] = val;
    target.value = val;
};

const formatScore = (event: Event, obj: any, key: string) => {
    const target = event.target as HTMLInputElement;
    let digits = target.value.replace(/\D/g, '');

    if (digits.length > 4) {
        digits = digits.slice(0, 4);
    }

    if (!digits) {
        obj[key] = '';
        target.value = '';

        return;
    }

    if (digits.length < 3) {
        obj[key] = digits;
        target.value = digits;
    } else {
        const integerPart = digits.slice(0, -2);
        const decimalPart = digits.slice(-2);
        let scoreVal = `${Number(integerPart)}.${decimalPart}`;

        if (Number(scoreVal) > 100) {
            scoreVal = '100.00';
        }

        obj[key] = scoreVal;
        target.value = scoreVal;
    }
};

const forceUppercase = (event: Event, obj: any, key: string) => {
    const target = event.target as HTMLInputElement;
    const uppercaseVal = target.value.toUpperCase();
    obj[key] = uppercaseVal;
    target.value = uppercaseVal;
};

const handleSpecificFileChange = (e: Event, docType: string) => {
    const input = e.target as HTMLInputElement;

    if (input.files && input.files[0]) {
        const selectedFile = input.files[0];
        const existingIndex = form.documents.findIndex(
            (d) => d.docType === docType,
        );

        if (existingIndex !== -1) {
            form.documents.splice(existingIndex, 1);
        }

        form.documents.push({
            docType,
            file: selectedFile,
            fileName: selectedFile.name,
        });
    }
};

const getDocumentName = (docType: string) => {
    const found = form.documents.find((d) => d.docType === docType);

    return found ? found.fileName : '';
};

// Review Helpers
const getMajorName = (id: number) =>
    internalMajors.find((m) => m.id === id)?.name || '-';
const getClassroomName = (id: number) =>
    internalClassrooms.find((c) => c.id === id)?.name || '-';
const getBloodTypeName = (id: number) => ['A', 'B', 'AB', 'O'][id - 1] || '-';

// Navigation
const nextStep = () => {
    if (!isStepValid.value) {
        errorMessage.value =
            'Harap lengkapi seluruh bidang wajib (*) pada langkah ini sebelum melanjutkan.';

        return;
    }

    errorMessage.value = '';

    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    errorMessage.value = '';

    if (currentStep.value > 1) {
        currentStep.value--;
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitRegistration = () => {
    if (!isDocumentsComplete.value) {
        errorMessage.value = 'Semua berkas dokumen wajib harus diunggah!';

        return;
    }

    form.post('/public-register', {
        onSuccess: () => {
            localStorage.removeItem(STORAGE_KEY);
        },
    });
};
</script>

<template>
    <Head title="Pendaftaran Siswa Baru" />

    <div class="min-h-screen bg-white font-sans text-black antialiased">
        <header
            class="sticky top-0 z-50 flex items-center justify-between border-b border-black/10 bg-white/90 px-8 py-6 backdrop-blur-md"
        >
            <div class="flex items-center gap-3">
                <div class="rounded-md bg-black p-1.5 text-white">
                    <GraduationCap class="size-5" />
                </div>
                <span class="text-xl font-black tracking-tight uppercase"
                    >Sistem <span class="font-light">Pendaftaran</span></span
                >
            </div>

            <button
                @click="clearDraft"
                type="button"
                title="Kosongkan seluruh isian form"
                class="flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-500 transition-colors hover:border-red-300 hover:text-red-600"
            >
                <RotateCcw class="size-3.5" />
                <span>Reset Form</span>
            </button>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-12">
            <div class="mb-6">
                <div class="mb-3 flex items-center justify-between">
                    <span
                        class="text-[10px] font-bold tracking-widest text-neutral-500 uppercase"
                        >Langkah 0{{ currentStep }} / 0{{ totalSteps }}</span
                    >
                    <span
                        class="font-mono text-[10px] font-bold tracking-widest text-black uppercase"
                        >{{ steps[currentStep - 1].name }}</span
                    >
                </div>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200"
                >
                    <div
                        class="h-full bg-black transition-all duration-500 ease-in-out"
                        :style="{
                            width: `${(currentStep / totalSteps) * 100}%`,
                        }"
                    ></div>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="mb-6 flex animate-in items-center gap-2 rounded-xl border border-red-300 bg-red-50 p-3 text-xs text-red-700"
            >
                <AlertCircle class="size-4 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>

            <div class="min-h-[420px]">
                <div
                    v-if="currentStep === 1"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <UserCheck class="size-3.5 text-black" /> I.
                            Informasi Dasar & Identitas Pokok Murid
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Nama Lengkap
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Masukkan nama sesuai ijazah"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                    :disabled="form.is_locked"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >NIS
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.nis"
                                    @input="
                                        (e) => filterNumbers(e, form, 'nis')
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    placeholder="Contoh: 22001"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                    :disabled="form.is_locked"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >NISN (10 Digit)
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.nisn"
                                    @input="
                                        (e) => filterNumbers(e, form, 'nisn')
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="0071234567"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                    :disabled="form.is_locked"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Tempat Lahir
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="form.birth_place"
                                    type="text"
                                    placeholder="Kota Lahir"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Tanggal Lahir
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="form.birth_date"
                                    type="date"
                                    maxlength="8"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Jenis Kelamin
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.gender_id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs text-black focus:border-black focus:outline-none"
                                >
                                    <option
                                        v-for="g in internalGenders"
                                        :key="g.id"
                                        :value="g.id"
                                    >
                                        {{ g.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Agama
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.religion_id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs text-black focus:border-black focus:outline-none"
                                >
                                    <option
                                        v-for="r in internalReligions"
                                        :key="r.id"
                                        :value="r.id"
                                    >
                                        {{ r.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Email Akun Murid</label
                                >
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="nama@student.sch.id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Nomor HP/WA
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.phone"
                                    @input="
                                        (e) => filterNumbers(e, form, 'phone')
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="12"
                                    placeholder="08XXXXXXXXXX"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label
                                    class="flex items-center gap-1 text-xs font-bold text-neutral-700"
                                >
                                    <Instagram class="size-3" /> URL Instagram
                                </label>
                                <input
                                    v-model="form.instagram_url"
                                    type="url"
                                    placeholder="https://instagram.com/username"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="flex items-center gap-1 text-xs font-bold text-neutral-700"
                                >
                                    <Globe class="size-3" /> URL Media Sosial
                                    Lainnya
                                </label>
                                <input
                                    v-model="form.linkedin_url"
                                    type="url"
                                    placeholder="https://linkedin.com/in/username"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="space-y-1 pt-1">
                            <label class="text-xs font-bold text-neutral-700"
                                >Alamat Rumah Sesuai KK
                                <span class="text-red-500">*</span></label
                            >
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Tuliskan jalan, RT/RW, Kelurahan, Kecamatan, dan Kota/Kabupaten"
                                class="w-full resize-none rounded-lg border border-neutral-300 bg-neutral-50 p-2 text-xs text-black focus:border-black focus:outline-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 2"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3 pt-2">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <School class="size-3.5 text-black" /> II. Pemetaan
                            Relasi Kelas & Kompetensi Keahlian
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Kompetensi Keahlian / Jurusan
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.major_id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs text-black focus:border-black focus:outline-none"
                                    :disabled="form.is_locked"
                                >
                                    <option
                                        v-for="m in internalMajors"
                                        :key="m.id"
                                        :value="m.id"
                                    >
                                        [{{ m.code }}] {{ m.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Penempatan Kelas
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.classroom_id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs text-black focus:border-black focus:outline-none"
                                    :disabled="form.is_locked"
                                >
                                    <option
                                        v-for="c in filteredClassrooms"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 3"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3 pt-2">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <Users class="size-3.5 text-black" /> III. Entitas
                            Hubungan Keluarga & Orang Tua
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div
                                class="space-y-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3"
                            >
                                <span
                                    class="block text-[10px] font-bold tracking-wide text-neutral-500 uppercase"
                                    >Ayah Kandung</span
                                >
                                <div class="space-y-1">
                                    <label
                                        class="text-xs font-medium text-neutral-700"
                                        >Nama Lengkap Ayah</label
                                    >
                                    <input
                                        v-model="form.family.father_name"
                                        type="text"
                                        placeholder="Nama ayah kandung"
                                        class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-xs focus:border-black focus:outline-none"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label
                                        class="text-xs font-medium text-neutral-700"
                                        >No. Telp Ayah</label
                                    >
                                    <input
                                        :value="form.family.father_phone"
                                        @input="
                                            (e) =>
                                                filterNumbers(
                                                    e,
                                                    form.family,
                                                    'father_phone',
                                                )
                                        "
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="12"
                                        placeholder="08XXXXXXXXXX"
                                        class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div
                                class="space-y-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3"
                            >
                                <span
                                    class="block text-[10px] font-bold tracking-wide text-neutral-500 uppercase"
                                    >Ibu Kandung</span
                                >
                                <div class="space-y-1">
                                    <label
                                        class="text-xs font-medium text-neutral-700"
                                        >Nama Lengkap Ibu</label
                                    >
                                    <input
                                        v-model="form.family.mother_name"
                                        type="text"
                                        placeholder="Nama ibu kandung"
                                        class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-xs focus:border-black focus:outline-none"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label
                                        class="text-xs font-medium text-neutral-700"
                                        >No. Telp Ibu</label
                                    >
                                    <input
                                        :value="form.family.mother_phone"
                                        @input="
                                            (e) =>
                                                filterNumbers(
                                                    e,
                                                    form.family,
                                                    'mother_phone',
                                                )
                                        "
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="12"
                                        placeholder="08XXXXXXXXXX"
                                        class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Nama Wali Murid (Opsional)</label
                                >
                                <input
                                    v-model="form.family.guardian_name"
                                    type="text"
                                    placeholder="Nama wali"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >No. Telp Wali Murid</label
                                >
                                <input
                                    :value="form.family.guardian_phone"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.family,
                                                'guardian_phone',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="12"
                                    placeholder="08XXXXXXXXXX"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-3 grid grid-cols-1 gap-3 rounded-xl border border-neutral-300 bg-neutral-100 p-3 md:grid-cols-2"
                        >
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-black"
                                    >Kontak Darurat
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="form.family.emergency_contact_name"
                                    type="text"
                                    placeholder="Contoh: Paman / Kakak Kandung"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-black"
                                    >No. HP Darurat
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.family.emergency_contact_phone"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.family,
                                                'emergency_contact_phone',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="12"
                                    placeholder="08XXXXXXXXXX"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 4"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3 pt-2">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <ClipboardList class="size-3.5 text-black" /> IV.
                            Riwayat Pendidikan Sebelumnya
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1 md:col-span-2">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Nama Sekolah Asal
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.education_history.school_name"
                                    @input="
                                        (e) =>
                                            forceUppercase(
                                                e,
                                                form.education_history,
                                                'school_name',
                                            )
                                    "
                                    type="text"
                                    placeholder="SMPN 1 KOTABARU"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs uppercase focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >NPSN Asal Sekolah</label
                                >
                                <input
                                    :value="form.education_history.npsn"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.education_history,
                                                'npsn',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="8"
                                    placeholder="30102040"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Tahun Masuk</label
                                >
                                <input
                                    :value="form.education_history.entry_year"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.education_history,
                                                'entry_year',
                                                currentYear,
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="4"
                                    :placeholder="`Maks ${currentYear}`"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Tahun Lulus
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="
                                        form.education_history.graduation_year
                                    "
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.education_history,
                                                'graduation_year',
                                                currentYear,
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="4"
                                    :placeholder="`Maks ${currentYear}`"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Nilai Ujian Akhir
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.education_history.final_score"
                                    @input="
                                        (e) =>
                                            formatScore(
                                                e,
                                                form.education_history,
                                                'final_score',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="Ketik 8520 -> 85.20"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 5"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3 pt-2">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <Heart class="size-3.5 text-black" /> V. Informasi
                            Kondisi Kesehatan Fisik & Medis
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Tinggi Badan (cm)
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.health.height"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.health,
                                                'height',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="3"
                                    placeholder="165"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Berat Badan (kg)
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    :value="form.health.weight"
                                    @input="
                                        (e) =>
                                            filterNumbers(
                                                e,
                                                form.health,
                                                'weight',
                                            )
                                    "
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="3"
                                    placeholder="55"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 font-mono text-xs focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Golongan Darah
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.health.blood_type_id"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2 text-xs text-black focus:border-black focus:outline-none"
                                >
                                    <option :value="1">A</option>
                                    <option :value="2">B</option>
                                    <option :value="3">AB</option>
                                    <option :value="4">O</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Daftar Kontraindikasi Alergi</label
                                >
                                <input
                                    :value="form.health.allergies"
                                    @input="
                                        (e) =>
                                            forceUppercase(
                                                e,
                                                form.health,
                                                'allergies',
                                            )
                                    "
                                    type="text"
                                    placeholder="TIDAK ADA"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs uppercase focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Riwayat Penyakit Bawaan</label
                                >
                                <input
                                    :value="form.health.medical_history"
                                    @input="
                                        (e) =>
                                            forceUppercase(
                                                e,
                                                form.health,
                                                'medical_history',
                                            )
                                    "
                                    type="text"
                                    placeholder="TIDAK ADA"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-neutral-50 px-2.5 text-xs uppercase focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3 rounded-xl border border-neutral-300 bg-neutral-50 p-3 md:grid-cols-2"
                        >
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Fasilitas Kesehatan / RSUD Rujukan</label
                                >
                                <input
                                    :value="form.health.hospital"
                                    @input="
                                        (e) =>
                                            forceUppercase(
                                                e,
                                                form.health,
                                                'hospital',
                                            )
                                    "
                                    type="text"
                                    placeholder="RSUD KOTA SEHAT"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-xs uppercase focus:border-black focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-neutral-700"
                                    >Dokter Penanggung Jawab</label
                                >
                                <input
                                    :value="form.health.doctor"
                                    @input="
                                        (e) =>
                                            forceUppercase(
                                                e,
                                                form.health,
                                                'doctor',
                                            )
                                    "
                                    type="text"
                                    placeholder="DR. BUDI SANTOSO"
                                    class="h-8 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-xs uppercase focus:border-black focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 6"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div class="space-y-3 pt-2">
                        <h3
                            class="flex items-center gap-1 border-b border-black/10 pb-1 font-bold text-black"
                        >
                            <FileArchive class="size-3.5 text-black" /> VI.
                            Unggah Dokumen Pendukung Resmi
                        </h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div
                                v-for="doc in allDocTypes"
                                :key="doc.name"
                                class="flex flex-col justify-center gap-2 rounded-xl border border-dashed border-neutral-400 bg-neutral-50 p-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[10px] font-bold text-black"
                                    >
                                        {{ doc.name }}
                                        <span
                                            v-if="doc.required"
                                            class="text-red-500"
                                            >*</span
                                        >
                                        <span
                                            v-else
                                            class="font-normal text-neutral-400"
                                        >
                                            (Opsional)</span
                                        >
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label
                                        :class="[
                                            'cursor-pointer rounded-lg bg-black px-2.5 py-1.5 text-[9px] font-semibold whitespace-nowrap text-white shadow-sm transition hover:bg-neutral-800',
                                            form.is_locked
                                                ? 'cursor-not-allowed opacity-50'
                                                : '',
                                        ]"
                                    >
                                        Pilih Berkas
                                        <input
                                            type="file"
                                            class="hidden"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            @change="
                                                (e) =>
                                                    handleSpecificFileChange(
                                                        e,
                                                        doc.name,
                                                    )
                                            "
                                            :disabled="form.is_locked"
                                        />
                                    </label>
                                    <span
                                        class="max-w-[150px] truncate text-[9px] text-neutral-600"
                                        :title="getDocumentName(doc.name)"
                                    >
                                        {{
                                            getDocumentName(doc.name) ||
                                            'Belum ada berkas'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="currentStep === 7"
                    class="animate-in space-y-6 duration-500 fade-in"
                >
                    <div
                        class="space-y-4 rounded-2xl border border-neutral-300 bg-neutral-50 p-5"
                    >
                        <h4
                            class="flex items-center gap-1.5 border-b border-black/10 pb-2 text-sm font-bold text-black"
                        >
                            <CheckCircle2 class="size-4 text-black" />
                            Peninjauan Ringkasan Data Penuh
                        </h4>

                        <div
                            class="grid grid-cols-1 gap-4 text-xs md:grid-cols-2"
                        >
                            <div class="space-y-1">
                                <span
                                    class="text-[10px] font-bold text-neutral-500 uppercase"
                                    >I. Identitas</span
                                >
                                <p>
                                    <strong>Nama:</strong>
                                    {{ form.name || '-' }}
                                </p>
                                <p>
                                    <strong>NIS / NISN:</strong>
                                    {{ form.nis || '-' }} /
                                    {{ form.nisn || '-' }}
                                </p>
                                <p>
                                    <strong>TTL:</strong>
                                    {{ form.birth_place || '-' }},
                                    {{ form.birth_date || '-' }}
                                </p>
                                <p>
                                    <strong>Kontak:</strong>
                                    {{ form.phone || '-' }} |
                                    {{ form.email || '-' }}
                                </p>
                                <p class="truncate">
                                    <strong>Alamat:</strong>
                                    {{ form.address || '-' }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <span
                                    class="text-[10px] font-bold text-neutral-500 uppercase"
                                    >II. Pemetaan Kelas</span
                                >
                                <p>
                                    <strong>Jurusan:</strong>
                                    {{ getMajorName(form.major_id) }}
                                </p>
                                <p>
                                    <strong>Kelas:</strong>
                                    {{ getClassroomName(form.classroom_id) }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <span
                                    class="text-[10px] font-bold text-neutral-500 uppercase"
                                    >III. Keluarga</span
                                >
                                <p>
                                    <strong>Ayah:</strong>
                                    {{ form.family.father_name || '-' }} ({{
                                        form.family.father_phone || '-'
                                    }})
                                </p>
                                <p>
                                    <strong>Ibu:</strong>
                                    {{ form.family.mother_name || '-' }} ({{
                                        form.family.mother_phone || '-'
                                    }})
                                </p>
                                <p>
                                    <strong>Darurat:</strong>
                                    {{
                                        form.family.emergency_contact_name ||
                                        '-'
                                    }}
                                    ({{
                                        form.family.emergency_contact_phone ||
                                        '-'
                                    }})
                                </p>
                            </div>

                            <div class="space-y-1">
                                <span
                                    class="text-[10px] font-bold text-neutral-500 uppercase"
                                    >IV & V. Pendidikan & Kesehatan</span
                                >
                                <p>
                                    <strong>Asal Sekolah:</strong>
                                    {{
                                        form.education_history.school_name ||
                                        '-'
                                    }}
                                    (Tahun:
                                    {{
                                        form.education_history.entry_year || '-'
                                    }}
                                    -
                                    {{
                                        form.education_history
                                            .graduation_year || '-'
                                    }})
                                </p>
                                <p>
                                    <strong>Nilai Akhir:</strong>
                                    {{
                                        form.education_history.final_score ||
                                        '-'
                                    }}
                                </p>
                                <p>
                                    <strong>Kesehatan:</strong> RS/Faskes:
                                    {{ form.health.hospital || '-' }} | Dokter:
                                    {{ form.health.doctor || '-' }}
                                </p>
                                <p>
                                    <strong>Penyakit / Alergi:</strong>
                                    {{ form.health.medical_history || '-' }} /
                                    {{ form.health.allergies || '-' }}
                                </p>
                                <p>
                                    <strong>Postur / Gol. Darah:</strong>
                                    {{ form.health.height || '-' }} cm /
                                    {{ form.health.weight || '-' }} kg ({{
                                        getBloodTypeName(
                                            form.health.blood_type_id,
                                        )
                                    }})
                                </p>
                            </div>

                            <div
                                class="col-span-1 space-y-2 border-t border-neutral-200 pt-2 md:col-span-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[10px] font-bold text-neutral-500 uppercase"
                                        >VI. Status Kelengkapan Dokumen
                                        Wajib</span
                                    >
                                    <span
                                        :class="[
                                            'rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase',
                                            isDocumentsComplete
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                                : 'border-amber-300 bg-amber-50 text-amber-700',
                                        ]"
                                    >
                                        {{
                                            isDocumentsComplete
                                                ? 'Lengkap (' +
                                                  requiredDocTypes.filter((d) =>
                                                      isDocUploaded(d),
                                                  ).length +
                                                  '/' +
                                                  requiredDocTypes.length +
                                                  ')'
                                                : 'Belum Lengkap'
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-2 pt-1 sm:grid-cols-2 md:grid-cols-3"
                                >
                                    <div
                                        v-for="doc in allDocTypes"
                                        :key="doc.name"
                                        class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-2 text-[11px]"
                                    >
                                        <span
                                            class="mr-2 truncate font-medium text-neutral-700"
                                        >
                                            {{ doc.name }}
                                            <span
                                                v-if="!doc.required"
                                                class="text-[9px] font-normal text-neutral-400"
                                                >(Opsional)</span
                                            >
                                        </span>
                                        <span
                                            :class="[
                                                'rounded px-1.5 py-0.5 text-[9px] font-bold whitespace-nowrap uppercase',
                                                isDocUploaded(doc.name)
                                                    ? 'bg-black text-white'
                                                    : 'bg-neutral-200 text-neutral-600',
                                            ]"
                                        >
                                            {{
                                                isDocUploaded(doc.name)
                                                    ? 'Sudah'
                                                    : 'Belum'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 text-center">
                        <div
                            class="inline-flex rounded-full bg-black p-3 text-white"
                        >
                            <ClipboardCheck class="size-7" />
                        </div>
                        <h2 class="text-lg font-bold">
                            Siap Mengirimkan Pendaftaran?
                        </h2>
                        <p
                            class="mx-auto max-w-md text-xs leading-relaxed text-neutral-600"
                        >
                            Pastikan ringkasan data di atas sudah benar dan
                            valid. Klik tombol di bawah ini untuk mengirimkan
                            pendaftaran.
                        </p>
                        <button
                            @click="submitRegistration"
                            :disabled="form.processing || !isDocumentsComplete"
                            type="button"
                            class="mt-2 rounded-xl bg-black px-8 py-3 text-xs font-bold tracking-widest text-white uppercase shadow-md transition-all hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Memproses...'
                                    : 'Kirim Pendaftaran Sekarang'
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 flex items-center justify-between border-t border-black/10 pt-6"
            >
                <button
                    v-if="currentStep > 1"
                    @click="prevStep"
                    type="button"
                    class="flex items-center gap-2 text-xs font-bold tracking-wider text-black uppercase transition-opacity hover:opacity-60"
                >
                    <ArrowLeft class="size-4" /> Kembali
                </button>
                <div v-else></div>

                <button
                    v-if="currentStep < totalSteps"
                    @click="nextStep"
                    :disabled="!isStepValid"
                    type="button"
                    :class="[
                        'flex items-center gap-2 rounded-lg px-5 py-2.5 text-xs font-bold tracking-wider uppercase transition-colors',
                        isStepValid
                            ? 'cursor-pointer bg-black text-white hover:bg-neutral-800'
                            : 'cursor-not-allowed bg-neutral-300 text-neutral-500',
                    ]"
                >
                    Langkah Selanjutnya <ArrowRight class="size-4" />
                </button>
            </div>
        </main>

        <footer class="border-t border-black/10 py-8 text-center">
            <p
                class="text-[10px] font-bold tracking-widest text-neutral-500 uppercase"
            >
                SISTEM INFORMASI AKADEMIK &bull; PENDAFTARAN SISWA
            </p>
        </footer>
    </div>
</template>

<style scoped>
.animate-in {
    animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
