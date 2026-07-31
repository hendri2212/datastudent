<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { 
    Loader2, 
    User, 
    Users, 
    History, 
    HeartPulse, 
    Trophy, 
    FileText,
    Share2,
    Plus,
    Trash2,
    Shield,
    XCircle,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
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
import { Label } from '@/components/ui/label';

// Import Types
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
    DocumentType,
} from './types';

const props = defineProps<{
    show: boolean;
    student: Student | null;
    schools?: School[];
    majors?: Major[];
    classrooms?: Classroom[];
    academicYears?: AcademicYear[];
    genders?: MasterOptionCode[];
    religions?: MasterOption[];
    studentStatuses?: MasterOption[];
    bloodTypes?: MasterOption[];
    occupations?: MasterOption[];
    incomeCategories?: MasterOption[];
    citizenships?: MasterOption[];
    educationLevels?: EducationLevel[];
    socialPlatforms?: SocialPlatform[];
    relationshipTypes?: MasterOption[];
    documentTypes?: DocumentType[];
}>();

const emit = defineEmits(['close']);

const activeTab = ref('biodata');

// Form State
const form = useForm({
    school_id: null as number | null,
    major_id: null as number | null,
    classroom_id: null as number | null,
    academic_year_id: null as number | null,
    gender_id: null as number | null,
    religion_id: null as number | null,
    student_status_id: null as number | null,
    citizenship_id: null as number | null,
    nisn: '',
    nis: '',
    full_name: '',
    nickname: '',
    birth_place: '',
    birth_date: '' as string,
    phone: '',
    email: '',
    address: '',
    postal_code: '',
    document_type_id: null as number | null,
    
    family: {
        father_name: '',
        father_occupation_id: null as number | null,
        father_income_category_id: null as number | null,
        father_phone: '',
        mother_name: '',
        mother_occupation_id: null as number | null,
        mother_income_category_id: null as number | null,
        mother_phone: '',
        guardian_name: '',
        guardian_occupation_id: null as number | null,
        guardian_income_category_id: null as number | null,
        guardian_phone: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        relationship_type_id: null as number | null,
        notes: '',
    },

    health: {
        blood_type_id: null as number | null,
        height: '' as string | number,
        weight: '' as string | number,
        allergies: '',
        medical_history: '',
        disabilities: '',
        medications: '',
        hospital: '',
        doctor: '',
        notes: '',
    },

    // Dynamic Lists
    education_histories: [] as any[],
    socials: [] as any[],
    achievements: [] as any[],
    violations: [] as any[],

    // File Document
    new_document_name: '',
    new_document_file: null as File | null,
});

// Helper Sanitasi ID / Angka
const parseNullableId = (val: any) => (val === '' || val === undefined || val === null ? null : Number(val));

// Helper Angka Kosong / Zero Safe
const parseNullableNumber = (val: any): number | null => {
    if (val === '' || val === undefined || val === null) {
return null;
}

    const num = Number(val);

    return isNaN(num) ? null : num;
};

// Helper Format Angka ke String untuk Form Input Binding
const formatNumberToString = (val: any): string => {
    if (val === null || val === undefined || val === '') {
return '';
}

    return String(val);
};

// Helper Format Tanggal khusus untuk Input Type="date" (YYYY-MM-DD)
const formatDateForInput = (dateStr: any): string => {
    if (!dateStr) {
return '';
}

    return String(dateStr).split('T')[0].split(' ')[0];
};

// Reset State Input Dokumen
const resetDocumentFields = () => {
    form.new_document_file = null;
    form.new_document_name = '';
    form.document_type_id = null;
};

// Warning & Duplication Check: Cek apakah jenis dokumen yang dipilih sudah pernah diunggah
const isDocumentTypeExists = computed(() => {
    if (!form.document_type_id || !props.student?.documents) {
return false;
}

    return props.student.documents.some(
        (doc: any) => parseNullableId(doc.document_type_id) === parseNullableId(form.document_type_id)
    );
});

// Watcher sync data
watch(
    () => props.student,
    (newStudent) => {
        if (newStudent) {
            form.school_id = parseNullableId(newStudent.school_id);
            form.major_id = parseNullableId(newStudent.major_id);
            form.classroom_id = parseNullableId(newStudent.classroom_id);
            form.academic_year_id = parseNullableId(newStudent.academic_year_id);
            form.gender_id = parseNullableId(newStudent.gender_id);
            form.religion_id = parseNullableId(newStudent.religion_id);
            form.student_status_id = parseNullableId(newStudent.student_status_id);
            form.citizenship_id = parseNullableId(newStudent.citizenship_id);

            form.nisn = newStudent.nisn || '';
            form.nis = newStudent.nis || '';
            form.full_name = newStudent.full_name || '';
            form.nickname = newStudent.nickname || '';
            form.birth_place = newStudent.birth_place || '';
            form.birth_date = formatDateForInput(newStudent.birth_date);
            form.phone = newStudent.phone || '';
            form.email = newStudent.email || '';
            form.address = newStudent.address || '';
            form.postal_code = newStudent.postal_code || '';

            resetDocumentFields();

            // Fill Family
            if (newStudent.family) {
                form.family.father_name = newStudent.family.father_name || '';
                form.family.father_occupation_id = parseNullableId(newStudent.family.father_occupation_id);
                form.family.father_income_category_id = parseNullableId(newStudent.family.father_income_category_id);
                form.family.father_phone = newStudent.family.father_phone || '';
                form.family.mother_name = newStudent.family.mother_name || '';
                form.family.mother_occupation_id = parseNullableId(newStudent.family.mother_occupation_id);
                form.family.mother_income_category_id = parseNullableId(newStudent.family.mother_income_category_id);
                form.family.mother_phone = newStudent.family.mother_phone || '';
                form.family.guardian_name = newStudent.family.guardian_name || '';
                form.family.guardian_occupation_id = parseNullableId(newStudent.family.guardian_occupation_id);
                form.family.guardian_income_category_id = parseNullableId(newStudent.family.guardian_income_category_id);
                form.family.guardian_phone = newStudent.family.guardian_phone || '';
                form.family.emergency_contact_name = newStudent.family.emergency_contact_name || '';
                form.family.emergency_contact_phone = newStudent.family.emergency_contact_phone || '';
                form.family.relationship_type_id = parseNullableId(newStudent.family.relationship_type_id);
                form.family.notes = newStudent.family.notes || '';
            }

            // Fill Health
            if (newStudent.health) {
                form.health.blood_type_id = parseNullableId(newStudent.health.blood_type_id);
                form.health.height = formatNumberToString(newStudent.health.height);
                form.health.weight = formatNumberToString(newStudent.health.weight);
                form.health.allergies = newStudent.health.allergies || '';
                form.health.medical_history = newStudent.health.medical_history || '';
                form.health.disabilities = newStudent.health.disabilities || '';
                form.health.medications = newStudent.health.medications || '';
                form.health.hospital = newStudent.health.hospital || '';
                form.health.doctor = newStudent.health.doctor || '';
                form.health.notes = newStudent.health.notes || '';
            }

            // Fill Arrays
            form.education_histories = newStudent.education_histories 
                ? newStudent.education_histories.map(edu => ({
                    ...edu,
                    education_level_id: parseNullableId(edu.education_level_id),
                    entry_year: formatNumberToString(edu.entry_year),
                    graduation_year: formatNumberToString(edu.graduation_year),
                    is_graduated: edu.is_graduated ?? true
                })) 
                : [];

            form.socials = newStudent.socials 
                ? newStudent.socials.map(s => ({ 
                    ...s, 
                    social_platform_id: parseNullableId(s.social_platform_id) 
                })) 
                : [];

            form.achievements = newStudent.achievements 
                ? newStudent.achievements.map(ach => ({
                    ...ach,
                    rank: formatNumberToString(ach.rank),
                    achievement_date: formatDateForInput(ach.achievement_date)
                })) 
                : [];

            form.violations = newStudent.violations 
                ? newStudent.violations.map(vio => ({
                    ...vio,
                    point: formatNumberToString(vio.point),
                    violation_date: formatDateForInput(vio.violation_date)
                })) 
                : [];
        } else {
            form.reset();
            resetDocumentFields();
        }
    },
    { immediate: true }
);

// Dynamic Helpers
const addEducation = () => {
    form.education_histories.push({
        education_level_id: null,
        school_name: '',
        npsn: '',
        address: '',
        entry_year: '',
        graduation_year: '',
        final_score: '',
        is_graduated: true,
        notes: ''
    });
};

const removeEducation = (index: number) => {
    form.education_histories.splice(index, 1);
};

const addSocial = () => {
    form.socials.push({
        social_platform_id: null,
        username: '',
        url: '',
    });
};

const removeSocial = (index: number) => {
    form.socials.splice(index, 1);
};

const addAchievement = () => {
    form.achievements.push({
        title: '',
        organizer: '',
        level: '',
        category: '',
        rank: '',
        achievement_date: '',
        description: '',
    });
};

const removeAchievement = (index: number) => {
    form.achievements.splice(index, 1);
};

const normalizeScoreInput = (edu: any) => {
    if (edu.final_score === null || edu.final_score === undefined || edu.final_score === '') {
        edu.final_score = '';

        return;
    }

    const numericOnly = String(edu.final_score).replace(/[^0-9]/g, '');

    if (!numericOnly) {
        edu.final_score = '';

        return;
    }

    if (numericOnly.length <= 2) {
        edu.final_score = Number(numericOnly).toString();

        return;
    }

    const integerPart = numericOnly.slice(0, numericOnly.length - 2);
    const decimalPart = numericOnly.slice(-2);
    const normalized = Number(`${integerPart}.${decimalPart}`);
    edu.final_score = normalized.toFixed(2);
};

const addViolation = () => {
    form.violations.push({
        title: '',
        point: '',
        violation_date: '',
        description: '',
    });
};

const removeViolation = (index: number) => {
    form.violations.splice(index, 1);
};

// Action Hapus Dokumen Tersimpan
const deleteDocument = (documentId: number) => {
    if (!props.student || !confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
return;
}

    router.delete(`/students/${props.student.id}/documents/${documentId}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Berhasil dihapus
        }
    });
};

const handleClose = () => {
    if (form.processing) {
return;
}

    form.clearErrors();
    activeTab.value = 'biodata';
    resetDocumentFields();
    emit('close');
};

const handleSubmit = () => {
    // PREVENT SUBMIT JIKA DOKUMEN SUDAH ADA
    if (isDocumentTypeExists.value && form.new_document_file) {
        form.setError('document_type_id', 'Jenis dokumen ini sudah diunggah sebelumnya. Silakan hapus dokumen lama terlebih dahulu!');
        activeTab.value = 'documents'; // Beralih otomatis ke tab dokumen

        return;
    }

    form.transform((data) => ({
        ...data,
        school_id: parseNullableId(data.school_id),
        major_id: parseNullableId(data.major_id),
        classroom_id: parseNullableId(data.classroom_id),
        academic_year_id: parseNullableId(data.academic_year_id),
        gender_id: parseNullableId(data.gender_id),
        religion_id: parseNullableId(data.religion_id),
        student_status_id: parseNullableId(data.student_status_id),
        citizenship_id: parseNullableId(data.citizenship_id),
        document_type_id: parseNullableId(data.document_type_id),
        birth_date: data.birth_date || null,
        
        family: {
            ...data.family,
            father_occupation_id: parseNullableId(data.family.father_occupation_id),
            father_income_category_id: parseNullableId(data.family.father_income_category_id),
            mother_occupation_id: parseNullableId(data.family.mother_occupation_id),
            mother_income_category_id: parseNullableId(data.family.mother_income_category_id),
            guardian_occupation_id: parseNullableId(data.family.guardian_occupation_id),
            guardian_income_category_id: parseNullableId(data.family.guardian_income_category_id),
            relationship_type_id: parseNullableId(data.family.relationship_type_id),
        },

        health: {
            ...data.health,
            blood_type_id: parseNullableId(data.health.blood_type_id),
            height: parseNullableNumber(data.health.height),
            weight: parseNullableNumber(data.health.weight),
        },

        education_histories: data.education_histories.map(edu => ({
            ...edu,
            education_level_id: parseNullableId(edu.education_level_id),
            entry_year: parseNullableNumber(edu.entry_year),
            graduation_year: parseNullableNumber(edu.graduation_year),
            final_score: edu.final_score !== '' ? edu.final_score : null,
        })),

        socials: data.socials.map(soc => ({
            ...soc,
            social_platform_id: parseNullableId(soc.social_platform_id),
        })),

        achievements: data.achievements.map(ach => ({
            ...ach,
            rank: parseNullableNumber(ach.rank),
            achievement_date: ach.achievement_date || null,
        })),

        violations: data.violations.map(vio => ({
            ...vio,
            point: parseNullableNumber(vio.point),
            violation_date: vio.violation_date || null,
        })),
    }));

    if (props.student && props.student.id) {
        if (form.new_document_file) {
            form.post(`/students/${props.student.id}`, {
                headers: { 'X-HTTP-Method-Override': 'PUT' },
                onSuccess: () => {
                    resetDocumentFields();
                    handleClose();
                },
            });
        } else {
            form.put(`/students/${props.student.id}`, {
                onSuccess: () => {
                    resetDocumentFields();
                    handleClose();
                },
            });
        }
    } else {
        form.post('/students', {
            onSuccess: () => {
                resetDocumentFields();
                handleClose();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="props.show" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-4xl max-h-[90vh] flex flex-col overflow-hidden p-0">
            <DialogHeader class="p-6 pb-2 border-b border-neutral-200 dark:border-neutral-800">
                <DialogTitle>
                    {{ props.student ? 'Edit Data Siswa' : 'Tambah Siswa Baru' }}
                </DialogTitle>
                <DialogDescription>
                    Isi dan kelola informasi lengkap data siswa di bawah ini.
                </DialogDescription>
                
                <div class="flex items-center gap-1 mt-4 overflow-x-auto pb-1 text-xs border-b border-neutral-100 dark:border-neutral-800">
                    <button type="button" @click="activeTab = 'biodata'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'biodata' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <User class="h-3.5 w-3.5" />
                        <span>Biodata Utama</span>
                    </button>
                    <button type="button" @click="activeTab = 'family'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'family' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <Users class="h-3.5 w-3.5" />
                        <span>Keluarga</span>
                    </button>
                    <button type="button" @click="activeTab = 'health'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'health' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <HeartPulse class="h-3.5 w-3.5" />
                        <span>Kesehatan</span>
                    </button>
                    <button type="button" @click="activeTab = 'education'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'education' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <History class="h-3.5 w-3.5" />
                        <span>Riwayat Sekolah</span>
                    </button>
                    <button type="button" @click="activeTab = 'socials'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'socials' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <Share2 class="h-3.5 w-3.5" />
                        <span>Medsos</span>
                    </button>
                    <button type="button" @click="activeTab = 'achievements'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'achievements' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <Trophy class="h-3.5 w-3.5" />
                        <span>Prestasi</span>
                    </button>
                    <button type="button" @click="activeTab = 'violations'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'violations' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <Shield class="h-3.5 w-3.5" />
                        <span>Pelanggaran</span>
                    </button>
                    <button type="button" @click="activeTab = 'documents'" :class="['px-3 py-1.5 rounded-md flex items-center gap-1.5 whitespace-nowrap transition-colors', activeTab === 'documents' ? 'bg-blue-50 text-blue-600 font-semibold dark:bg-blue-950 dark:text-blue-400' : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800']">
                        <FileText class="h-3.5 w-3.5" />
                        <span>Dokumen</span>
                    </button>
                </div>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Tab Biodata Utama -->
                <div v-show="activeTab === 'biodata'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="full_name">Nama Lengkap <span class="text-red-500">*</span></Label>
                            <Input id="full_name" v-model="form.full_name" placeholder="Nama Lengkap Siswa" required />
                            <span v-if="form.errors.full_name" class="text-xs text-red-500">{{ form.errors.full_name }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="nickname">Nama Panggilan</Label>
                            <Input id="nickname" v-model="form.nickname" placeholder="Panggilan" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="nisn">NISN <span class="text-red-500">*</span></Label>
                            <Input id="nisn" type="text" inputmode="numeric" pattern="[0-9]*" v-model="form.nisn" placeholder="Nomor Induk Siswa Nasional" required />
                            <span v-if="form.errors.nisn" class="text-xs text-red-500">{{ form.errors.nisn }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="nis">NIS</Label>
                            <Input id="nis" type="text" inputmode="numeric" pattern="[0-9]*" v-model="form.nis" placeholder="Nomor Induk Sekolah" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="gender_id">
                                Jenis Kelamin <span class="text-red-500">*</span>
                            </Label>
                            <select id="gender_id" v-model="form.gender_id" class="w-full border rounded-md p-2 text-sm bg-background border-input" required>
                                <option :value="null">Pilih Jenis Kelamin</option>
                                <option v-for="g in props.genders" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="citizenship_id">
                                Kewarganegaraan <span v-if="form.citizenship_id === null" class="text-red-500">*</span>
                            </Label>
                            <select id="citizenship_id" v-model="form.citizenship_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Kewarganegaraan</option>
                                <option v-for="c in props.citizenships" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="religion_id">
                                Agama <span v-if="form.religion_id === null" class="text-red-500">*</span>
                            </Label>
                            <select id="religion_id" v-model="form.religion_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Agama</option>
                                <option v-for="r in props.religions" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="birth_place">Tempat Lahir</Label>
                            <Input id="birth_place" v-model="form.birth_place" placeholder="Kota Lahir" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="birth_date">
                                Tanggal Lahir <span v-if="!form.birth_date" class="text-red-500">*</span>
                            </Label>
                            <Input id="birth_date" type="date" v-model="form.birth_date" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="classroom_id">
                                Kelas <span v-if="form.classroom_id === null" class="text-red-500">*</span>
                            </Label>
                            <select id="classroom_id" v-model="form.classroom_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Kelas</option>
                                <option v-for="c in props.classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="major_id">
                                Jurusan <span v-if="form.major_id === null" class="text-red-500">*</span>
                            </Label>
                            <select id="major_id" v-model="form.major_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Jurusan</option>
                                <option v-for="m in props.majors" :key="m.id" :value="m.id">{{ m.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="academic_year_id">
                                Tahun Ajaran <span class="text-red-500">*</span>
                            </Label>
                            <select id="academic_year_id" v-model="form.academic_year_id" class="w-full border rounded-md p-2 text-sm bg-background border-input" required>
                                <option :value="null">Pilih Tahun Ajaran</option>
                                <option v-for="ay in props.academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="student_status_id">
                                Status Siswa <span v-if="form.student_status_id === null" class="text-red-500">*</span>
                            </Label>
                            <select id="student_status_id" v-model="form.student_status_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Status</option>
                                <option v-for="st in props.studentStatuses" :key="st.id" :value="st.id">{{ st.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="phone">No. Telepon / WA</Label>
                            <Input id="phone" type="text" inputmode="tel" pattern="[0-9+]*" v-model="form.phone" placeholder="08xxxxxxxxxx" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="email">Email</Label>
                            <Input id="email" type="email" v-model="form.email" placeholder="siswa@sekolah.sch.id" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="address">Alamat</Label>
                            <Input id="address" v-model="form.address" placeholder="Alamat lengkap siswa" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="school_id">
                                Sekolah <span class="text-red-500">*</span>
                            </Label>
                            <select id="school_id" v-model="form.school_id" class="w-full border rounded-md p-2 text-sm bg-background border-input" required>
                                <option :value="null">Pilih Sekolah</option>
                                <option v-for="s in props.schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <span v-if="form.errors.school_id" class="text-xs text-red-500">{{ form.errors.school_id }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="postal_code">Kode Pos</Label>
                            <Input id="postal_code" v-model="form.postal_code" placeholder="Kode Pos" />
                        </div>
                    </div>
                </div>

                <!-- Tab Keluarga -->
                <div v-show="activeTab === 'family'" class="space-y-6">
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 border-b pb-1">Data Ayah</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">Nama Ayah</Label>
                                <Input v-model="form.family.father_name" placeholder="Nama Ayah Kandung" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">No. Telepon Ayah</Label>
                                <Input v-model="form.family.father_phone" placeholder="08xxx" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">
                                    Pekerjaan Ayah <span v-if="form.family.father_occupation_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="form.family.father_occupation_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                    <option :value="null">Pilih Pekerjaan</option>
                                    <option v-for="o in props.occupations" :key="o.id" :value="o.id">{{ o.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">
                                    Penghasilan Ayah <span v-if="form.family.father_income_category_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="form.family.father_income_category_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                    <option :value="null">Pilih Penghasilan</option>
                                    <option v-for="inc in props.incomeCategories" :key="inc.id" :value="inc.id">{{ inc.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 border-b pb-1">Data Ibu</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">Nama Ibu</Label>
                                <Input v-model="form.family.mother_name" placeholder="Nama Ibu Kandung" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">No. Telepon Ibu</Label>
                                <Input v-model="form.family.mother_phone" placeholder="08xxx" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">
                                    Pekerjaan Ibu <span v-if="form.family.mother_occupation_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="form.family.mother_occupation_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                    <option :value="null">Pilih Pekerjaan</option>
                                    <option v-for="o in props.occupations" :key="o.id" :value="o.id">{{ o.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">
                                    Penghasilan Ibu <span v-if="form.family.mother_income_category_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="form.family.mother_income_category_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                    <option :value="null">Pilih Penghasilan</option>
                                    <option v-for="inc in props.incomeCategories" :key="inc.id" :value="inc.id">{{ inc.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 border-b pb-1">Data Wali & Kontak Darurat</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">Nama Wali</Label>
                                <Input v-model="form.family.guardian_name" placeholder="Nama Wali (Opsional)" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">No. Telepon Wali</Label>
                                <Input v-model="form.family.guardian_phone" placeholder="08xxx" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Kontak Darurat (Nama)</Label>
                                <Input v-model="form.family.emergency_contact_name" placeholder="Nama Kontak Darurat" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Kontak Darurat (No Telp)</Label>
                                <Input v-model="form.family.emergency_contact_phone" placeholder="08xxx" class="text-sm" />
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <Label class="text-xs">
                                    Hubungan Kontak Darurat <span v-if="form.family.relationship_type_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="form.family.relationship_type_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                    <option :value="null">Pilih Hubungan</option>
                                    <option v-for="rel in props.relationshipTypes" :key="rel.id" :value="rel.id">{{ rel.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Kesehatan -->
                <div v-show="activeTab === 'health'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <Label class="text-xs">
                                Golongan Darah <span v-if="form.health.blood_type_id === null" class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.health.blood_type_id" class="w-full border rounded-md p-2 text-sm bg-background border-input">
                                <option :value="null">Pilih Gol. Darah</option>
                                <option v-for="bt in props.bloodTypes" :key="bt.id" :value="bt.id">{{ bt.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs">
                                Tinggi Badan (cm) <span v-if="!form.health.height" class="text-red-500">*</span>
                            </Label>
                            <Input type="number" step="0.01" v-model.number="form.health.height" placeholder="165" class="text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs">
                                Berat Badan (kg) <span v-if="!form.health.weight" class="text-red-500">*</span>
                            </Label>
                            <Input type="number" step="0.01" v-model.number="form.health.weight" placeholder="55" class="text-sm" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label class="text-xs">Alergi</Label>
                            <Input v-model="form.health.allergies" placeholder="Alergi Makanan / Obat" class="text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs">Kebutuhan Khusus / Disabilitas</Label>
                            <Input v-model="form.health.disabilities" placeholder="Disabilitas jika ada" class="text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label class="text-xs">Riwayat Penyakit</Label>
                            <Input v-model="form.health.medical_history" placeholder="Asma, Diabetes, dll" class="text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs">Konsumsi Obat Rutin</Label>
                            <Input v-model="form.health.medications" placeholder="Nama obat rujukan" class="text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label class="text-xs">Rumah Sakit Rujukan Darurat</Label>
                            <Input v-model="form.health.hospital" placeholder="RS Rujukan" class="text-sm" />
                        </div>
                        <div class="space-y-1">
                            <Label class="text-xs">Dokter Rujukan</Label>
                            <Input v-model="form.health.doctor" placeholder="Dokter Penanggung Jawab" class="text-sm" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <Label class="text-xs">Catatan Kesehatan Tambahan</Label>
                        <Input v-model="form.health.notes" placeholder="Catatan kesehatan lainnya" class="text-sm" />
                    </div>
                </div>

                <!-- Tab Riwayat Sekolah -->
                <div v-show="activeTab === 'education'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Riwayat Sekolah Sebelumnya</h4>
                        <Button type="button" size="sm" variant="outline" @click="addEducation" class="flex items-center gap-1 text-xs">
                            <Plus class="h-3.5 w-3.5" />
                            <span>Tambah Sekolah</span>
                        </Button>
                    </div>

                    <div v-for="(edu, index) in form.education_histories" :key="index" class="p-4 border rounded-lg space-y-3 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                        <div class="flex justify-between items-center border-b pb-2 dark:border-neutral-800">
                            <span class="text-xs font-semibold">Sekolah #{{ index + 1 }}</span>
                            <Button type="button" size="icon" variant="ghost" @click="removeEducation(index)">
                                <Trash2 class="h-4 w-4 text-red-500" />
                            </Button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">
                                    Jenjang Pendidikan <span v-if="edu.education_level_id === null" class="text-red-500">*</span>
                                </Label>
                                <select v-model="edu.education_level_id" class="w-full border rounded-md p-2 text-sm bg-background border-input" required>
                                    <option :value="null">Pilih Jenjang</option>
                                    <option v-for="el in props.educationLevels" :key="el.id" :value="el.id">{{ el.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Nama Sekolah <span class="text-red-500">*</span></Label>
                                <Input v-model="edu.school_name" placeholder="SMPN 1 Jakarta" class="text-sm" required />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">NPSN</Label>
                                <Input v-model="edu.npsn" placeholder="Nomor NPSN" class="text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">Tahun Masuk</Label>
                                <Input type="number" min="1900" max="2099" inputmode="numeric" v-model.number="edu.entry_year" placeholder="2020" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Tahun Lulus</Label>
                                <Input type="number" min="1900" max="2099" inputmode="numeric" v-model.number="edu.graduation_year" placeholder="2023" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Nilai Akhir / IPK</Label>
                                <Input
                                    type="text"
                                    v-model="edu.final_score"
                                    placeholder="85.50"
                                    @blur="normalizeScoreInput(edu)"
                                    class="text-sm"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                            <div class="space-y-1">
                                <Label class="text-xs">Alamat Sekolah</Label>
                                <Input v-model="edu.address" placeholder="Jl. Raya Utama No. 12" class="text-sm" />
                            </div>
                            <div class="flex items-center gap-2 pt-4">
                                <input type="checkbox" :id="'graduated_' + index" v-model="edu.is_graduated" class="rounded border-input" />
                                <Label :for="'graduated_' + index" class="text-xs font-normal cursor-pointer">Lulus dari Sekolah Ini</Label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Medsos -->
                <div v-show="activeTab === 'socials'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Akun Media Sosial</h4>
                        <Button type="button" size="sm" variant="outline" @click="addSocial" class="flex items-center gap-1 text-xs">
                            <Plus class="h-3.5 w-3.5" />
                            <span>Tambah Medsos</span>
                        </Button>
                    </div>

                    <div v-for="(soc, index) in form.socials" :key="index" class="p-3 border rounded-lg flex flex-col md:flex-row md:items-center gap-3 dark:border-neutral-800">
                        <select v-model="soc.social_platform_id" class="border rounded-md p-2 text-sm bg-background border-input w-full md:w-1/4">
                            <option :value="null">Platform</option>
                            <option v-for="sp in props.socialPlatforms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
                        </select>
                        <Input v-model="soc.username" placeholder="@username" class="text-sm flex-1" />
                        <Input type="url" v-model="soc.url" placeholder="https://" class="text-sm flex-1" />
                        <Button type="button" size="icon" variant="ghost" @click="removeSocial(index)">
                            <Trash2 class="h-4 w-4 text-red-500" />
                        </Button>
                    </div>
                </div>

                <!-- Tab Prestasi -->
                <div v-show="activeTab === 'achievements'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Daftar Prestasi</h4>
                        <Button type="button" size="sm" variant="outline" @click="addAchievement" class="flex items-center gap-1 text-xs">
                            <Plus class="h-3.5 w-3.5" />
                            <span>Tambah Prestasi</span>
                        </Button>
                    </div>

                    <div v-for="(ach, index) in form.achievements" :key="index" class="p-3 border rounded-lg space-y-2 dark:border-neutral-800">
                        <div class="flex justify-between items-center">
                            <Input v-model="ach.title" placeholder="Judul Prestasi / Lomba" class="text-sm font-medium" />
                            <Button type="button" size="icon" variant="ghost" @click="removeAchievement(index)">
                                <Trash2 class="h-4 w-4 text-red-500" />
                            </Button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div class="space-y-1">
                                <Label class="text-xs">Penyelenggara</Label>
                                <Input v-model="ach.organizer" placeholder="Penyelenggara" class="text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Tingkat / Jenis Lomba</Label>
                                <Input v-model="ach.level" placeholder="Sekolah / Kota / Nasional" class="text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Kategori</Label>
                                <select v-model="ach.category" class="w-full border rounded-md p-2 text-sm bg-background border-input text-xs">
                                    <option value="">Pilih Kategori</option>
                                    <option value="Akademik">Akademik</option>
                                    <option value="Non Akademik">Non Akademik</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div class="space-y-1">
                                <Label class="text-xs">Peringkat / Juara</Label>
                                <Input type="number" v-model.number="ach.rank" placeholder="1" class="text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Tanggal Prestasi</Label>
                                <Input type="date" v-model="ach.achievement_date" class="text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Catatan</Label>
                                <Input v-model="ach.description" placeholder="Deskripsi singkat" class="text-xs" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Pelanggaran -->
                <div v-show="activeTab === 'violations'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Daftar Pelanggaran</h4>
                        <Button type="button" size="sm" variant="outline" @click="addViolation" class="flex items-center gap-1 text-xs">
                            <Plus class="h-3.5 w-3.5" />
                            <span>Tambah Pelanggaran</span>
                        </Button>
                    </div>

                    <div v-for="(vio, index) in form.violations" :key="index" class="p-3 border rounded-lg space-y-3 dark:border-neutral-800">
                        <div class="flex justify-between items-center">
                            <Input v-model="vio.title" placeholder="Judul Pelanggaran" class="text-sm font-medium" />
                            <Button type="button" size="icon" variant="ghost" @click="removeViolation(index)">
                                <Trash2 class="h-4 w-4 text-red-500" />
                            </Button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <Label class="text-xs">Poin</Label>
                                <Input type="number" v-model.number="vio.point" placeholder="0" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Tanggal Pelanggaran</Label>
                                <Input type="date" v-model="vio.violation_date" class="text-sm" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Keterangan</Label>
                                <Input v-model="vio.description" placeholder="Deskripsi pelanggaran" class="text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Dokumen -->
                <div v-show="activeTab === 'documents'" class="space-y-4">
                    <div class="space-y-3 p-4 border rounded-lg dark:border-neutral-800">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Upload Dokumen Baru</h4>
                        
                        <!-- DANGER / PREVENT ALERT: Mencegah upload jika jenis dokumen sudah ada -->
                        <div v-if="isDocumentTypeExists" class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-xs flex items-start gap-2 dark:bg-red-950/40 dark:border-red-800/50 dark:text-red-300">
                            <XCircle class="h-4 w-4 text-red-600 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-bold">Gagal/Dihentikan:</p>
                                <p>Siswa ini sudah memiliki berkas untuk jenis dokumen tersebut. Harap **hapus dokumen tersimpan** di bawah terlebih dahulu sebelum mengunggah file baru.</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-xs">
                                Jenis Dokumen <span v-if="form.document_type_id === null" class="text-red-500">*</span>
                            </Label>
                            <select 
                                v-model="form.document_type_id" 
                                class="w-full border rounded-md p-2 text-sm bg-background border-input"
                                @change="form.clearErrors('document_type_id')"
                            >
                                <option :value="null">Pilih Jenis Dokumen</option>
                                <option v-for="dt in props.documentTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
                            </select>
                            <span v-if="form.errors.document_type_id" class="text-xs text-red-500 font-medium block">
                                {{ form.errors.document_type_id }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-xs">Keterangan / Nama Dokumen</Label>
                            <Input v-model="form.new_document_name" placeholder="Misal: Ijazah SMP, KK, Akta" class="text-sm" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-xs">Pilih File (PDF / Gambar)</Label>
                            <Input 
                                type="file" 
                                @change="(e: any) => form.new_document_file = e.target.files[0]"
                                class="mt-1"
                            />
                        </div>
                    </div>

                    <div v-if="props.student?.documents && props.student.documents.length > 0" class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Dokumen Tersimpan</h4>
                        <div v-for="doc in props.student.documents" :key="doc.id" class="flex items-center justify-between border p-3 rounded-lg text-xs dark:border-neutral-800">
                            <span class="font-medium">{{ doc.original_name || doc.stored_name || doc.file_name || 'Dokumen Siswa' }}</span>
                            <div class="flex items-center gap-3">
                                <a :href="props.student ? `/students/${props.student.id}/documents/${doc.id}/preview` : '#'" target="_blank" class="text-blue-600 underline">Lihat / Unduh</a>
                                <Button type="button" variant="ghost" size="icon" class="h-7 w-7 text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteDocument(doc.id)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="pt-4 border-t border-neutral-200 dark:border-neutral-800 flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="handleClose">Batal</Button>
                    <!-- Button di-disable jika user nekat mencoba submit dokumen duplikat -->
                    <Button type="submit" :disabled="form.processing || (isDocumentTypeExists && !!form.new_document_file)">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span>Simpan Data</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>