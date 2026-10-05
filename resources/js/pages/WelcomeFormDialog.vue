<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'; // Sesuaikan lokasi komponen Dialog UI kamu
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
} from '@/pages/students/types';
import Welcome from '@/pages/WelcomeForm.vue';

// Props yang diterima dari halaman utama / parent component
const props = defineProps<{
    open: boolean;
    student?: Student | null;
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

// Emit untuk mengontrol status open/close dialog ke parent
const emit = defineEmits(['update:open', 'saved']);

const handleClose = () => {
    emit('update:open', false);
};

const handleSaved = () => {
    emit('saved');
    handleClose();
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="flex w-[96vw] max-w-none flex-col overflow-hidden p-0 sm:max-w-6xl sm:rounded-2xl h-[92vh] min-h-[92vh] max-h-[92vh]">
            <!-- Header dialog tersembunyi secara visual jika ingin tampilan kustom dari Welcome.vue -->
            <DialogHeader class="sr-only">
                <DialogTitle>
                    {{ student ? 'Edit Profil & Data Siswa' : 'Pendaftaran Siswa Baru' }}
                </DialogTitle>
                <DialogDescription>
                    Formulir pengisian detail profil dan dokumen siswa.
                </DialogDescription>
            </DialogHeader>

            <!-- Formulir profil siswa -->
            <Welcome
                :student="props.student"
                :schools="props.schools"
                :majors="props.majors"
                :classrooms="props.classrooms"
                :academic-years="props.academicYears"
                :genders="props.genders"
                :religions="props.religions"
                :student-statuses="props.studentStatuses"
                :blood-types="props.bloodTypes"
                :occupations="props.occupations"
                :income-categories="props.incomeCategories"
                :citizenships="props.citizenships"
                :education-levels="props.educationLevels"
                :social-platforms="props.socialPlatforms"
                :relationship-types="props.relationshipTypes"
                :document-types="props.documentTypes"
                @close="handleClose"
                @saved="handleSaved"
            />
        </DialogContent>
    </Dialog>
</template>
