<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, LogOut, Pencil, UserRound, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import type {
    Student, School, Major, Classroom, AcademicYear, MasterOption,
    MasterOptionCode, EducationLevel, SocialPlatform, DocumentType,
} from '@/pages/students/types';
import WelcomeFormDialog from '@/pages/WelcomeFormDialog.vue';
import { logout } from '@/routes';
import { preview as previewDocument } from '@/routes/students/documents';

const props = defineProps<{
    student: Student | null;
    hasRegistered: boolean;
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

const page = usePage();
const dialogOpen = ref(false);
const previewItem = ref<{ url: string; title: string; pdf: boolean } | null>(null);
const authUser = computed(() => (page.props as any).auth?.user);
const isStudentUser = computed(() => ['student', 'siswa'].includes(authUser.value?.role));
const canUpdate = computed(() => !isStudentUser.value || !props.student?.verified_at);
const student = computed(() => props.student);

const value = (value?: string | number | null) => value === null || value === undefined || value === '' ? '—' : value;
const date = (value?: string | null) => value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';
const openPreview = (url: string, title: string, mime = '') => {
    previewItem.value = { url, title, pdf: mime.toLowerCase().includes('pdf') || /\.pdf(?:$|\?)/i.test(url) };
};

const sections = computed(() => {
    const s = student.value;

    if (!s) {
return [];
}

    return [
        { title: 'Biodata', items: [['Nama lengkap', s.full_name], ['Nama panggilan', s.nickname], ['NISN', s.nisn], ['NIS', s.nis], ['Jenis kelamin', s.gender?.name], ['Tempat lahir', s.birth_place], ['Tanggal lahir', date(s.birth_date)], ['Agama', s.religion?.name], ['Kewarganegaraan', s.citizenship?.name], ['Telepon', s.phone], ['Email', s.email], ['Alamat', s.address], ['Kode pos', s.postal_code]] },
        { title: 'Data sekolah', items: [['Sekolah', s.school?.name], ['Jurusan', s.major?.name], ['Kelas', s.current_enrollment?.classroom?.name || s.classroom?.name], ['Tahun ajaran', s.current_enrollment?.academic_year?.name || s.academic_year?.name], ['Status siswa', s.current_enrollment?.status?.name || s.student_status?.name]] },
        { title: 'Keluarga', items: [['Nama ayah', s.family?.father_name], ['Pekerjaan ayah', s.family?.father_occupation?.name], ['Penghasilan ayah', s.family?.father_income_category?.name], ['Telepon ayah', s.family?.father_phone], ['Nama ibu', s.family?.mother_name], ['Pekerjaan ibu', s.family?.mother_occupation?.name], ['Penghasilan ibu', s.family?.mother_income_category?.name], ['Telepon ibu', s.family?.mother_phone], ['Nama wali', s.family?.guardian_name], ['Pekerjaan wali', s.family?.guardian_occupation?.name], ['Penghasilan wali', s.family?.guardian_income_category?.name], ['Telepon wali', s.family?.guardian_phone], ['Kontak darurat', s.family?.emergency_contact_name], ['Telepon darurat', s.family?.emergency_contact_phone], ['Hubungan kontak darurat', s.family?.relationship_type?.name], ['Catatan keluarga', s.family?.notes]] },
        { title: 'Kesehatan', items: [['Golongan darah', s.health?.blood_type?.name], ['Tinggi badan', s.health?.height ? `${s.health.height} cm` : null], ['Berat badan', s.health?.weight ? `${s.health.weight} kg` : null], ['Alergi', s.health?.allergies], ['Riwayat kesehatan', s.health?.medical_history], ['Disabilitas', s.health?.disabilities], ['Obat rutin', s.health?.medications], ['Dokter', s.health?.doctor], ['Rumah sakit', s.health?.hospital], ['Catatan kesehatan', s.health?.notes]] },
    ];
});
</script>

<template>
    <Head title="Profil Siswa" />
    <main class="min-h-screen bg-neutral-50 px-4 py-8 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100 sm:px-6">
        <div class="mx-auto max-w-5xl space-y-6">
            <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center gap-4">
                    <button v-if="student?.photo_url" type="button" class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-neutral-100 ring-offset-2 hover:ring-2 hover:ring-neutral-400 dark:bg-neutral-800" aria-label="Pratinjau foto siswa" @click="openPreview(student.photo_url, 'Foto siswa', 'image/*')">
                        <img v-if="student?.photo_url" :src="student.photo_url" :alt="student.full_name" class="h-full w-full object-cover" />
                    </button>
                    <div v-else class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800"><UserRound class="h-7 w-7 text-neutral-400" /></div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Profil Siswa</p>
                        <h1 class="mt-1 text-xl font-bold">{{ student?.full_name || 'Data siswa belum tersedia' }}</h1>
                        <p v-if="student" class="mt-1 text-sm text-neutral-500">{{ student.email || 'Email belum diisi' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="student && canUpdate" @click="dialogOpen = true"><Pencil class="mr-2 h-4 w-4" /> Update data</Button>
                    <Link :href="logout()" as="button" @click="router.flushAll()" class="inline-flex h-9 items-center justify-center rounded-md border border-neutral-200 bg-white px-3 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800">
                        <LogOut class="mr-2 h-4 w-4" /> Keluar akun
                    </Link>
                </div>
                <p v-if="student && !canUpdate" class="text-sm text-neutral-500">Data sudah diverifikasi dan terkunci.</p>
            </header>

            <div v-if="student" class="grid gap-5 md:grid-cols-2">
                <section v-for="section in sections" :key="section.title" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 border-b border-neutral-100 pb-3 text-sm font-bold uppercase tracking-wide dark:border-neutral-800">{{ section.title }}</h2>
                    <dl class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div v-for="item in section.items" :key="String(item[0])" class="min-w-0">
                            <dt class="text-xs text-neutral-500">{{ item[0] }}</dt>
                            <dd class="mt-1 break-words text-sm font-medium">{{ value(item[1] as string | number | null) }}</dd>
                        </div>
                    </dl>
                </section>
                <section v-if="student.education_histories?.length" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Riwayat Pendidikan</h2>
                    <div v-for="history in student.education_histories" :key="history.id" class="space-y-3 border-t border-neutral-100 py-4 first:border-0 dark:border-neutral-800">
                        <div class="flex items-center justify-between gap-3">
                            <div><p class="text-sm font-semibold">{{ history.school_name }}</p><p class="text-xs text-neutral-500">{{ history.education_level?.name || 'Jenjang tidak diisi' }} · {{ history.is_graduated ? 'Lulus' : 'Belum lulus' }}</p></div>
                            <Button v-if="history.certificate && typeof history.id === 'number'" type="button" variant="outline" size="sm" class="shrink-0" @click="openPreview(`/student-education-histories/${history.id}/certificate`, 'Ijazah / dokumen pendidikan', history.certificate.endsWith('.pdf') ? 'application/pdf' : 'image/*')"><Eye class="mr-1 h-3.5 w-3.5" /> Preview</Button>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-xs text-neutral-500">Tahun masuk</dt><dd class="mt-1 font-medium">{{ value(history.entry_year) }}</dd></div>
                            <div><dt class="text-xs text-neutral-500">Tahun lulus</dt><dd class="mt-1 font-medium">{{ value(history.graduation_year) }}</dd></div>
                            <div><dt class="text-xs text-neutral-500">NPSN</dt><dd class="mt-1 font-medium">{{ value(history.npsn) }}</dd></div>
                            <div><dt class="text-xs text-neutral-500">Nilai akhir</dt><dd class="mt-1 font-medium">{{ value(history.final_score) }}</dd></div>
                            <div class="col-span-2 sm:col-span-3"><dt class="text-xs text-neutral-500">Alamat sekolah</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ value(history.address) }}</dd></div>
                            <div v-if="history.notes" class="col-span-2 sm:col-span-3"><dt class="text-xs text-neutral-500">Catatan</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ history.notes }}</dd></div>
                        </dl>
                    </div>
                </section>
                <section v-if="student.documents?.length" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Dokumen</h2>
                    <div v-for="doc in student.documents" :key="doc.id" class="flex items-center justify-between gap-3 border-t border-neutral-100 py-3 dark:border-neutral-800">
                        <div class="min-w-0"><p class="truncate text-sm font-medium">{{ doc.notes || doc.original_name || doc.file_name || 'Dokumen' }}</p><p class="mt-0.5 truncate text-xs text-neutral-500">{{ doc.document_type?.name || 'Dokumen' }}<span v-if="doc.notes && (doc.original_name || doc.file_name)"> · {{ doc.original_name || doc.file_name }}</span></p></div>
                        <Button type="button" variant="outline" size="sm" class="shrink-0" @click="openPreview(previewDocument.url({ student: student.id, document: doc.id }), doc.notes || doc.original_name || doc.file_name || 'Dokumen siswa', doc.mime_type || '')"><Eye class="mr-1 h-3.5 w-3.5" /> Preview</Button>
                    </div>
                </section>
                <section v-if="student.socials?.length" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Media Sosial</h2>
                    <div v-for="social in student.socials" :key="social.id" class="border-t border-neutral-100 py-3 first:border-0 dark:border-neutral-800"><p class="text-sm font-medium">{{ social.social_platform?.name || 'Platform' }}</p><p class="mt-1 break-all text-xs text-neutral-500">{{ social.username || '—' }}<span v-if="social.url"> · {{ social.url }}</span></p><p class="mt-1 text-[11px] text-neutral-500">{{ social.is_public ? 'Publik' : 'Privat' }}<span v-if="social.is_primary"> · Akun utama</span></p></div>
                </section>
                <section v-if="student.achievements?.length" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Prestasi</h2>
                    <div v-for="achievement in student.achievements" :key="achievement.id" class="border-t border-neutral-100 py-3 dark:border-neutral-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0"><p class="text-sm font-semibold">{{ achievement.title }}</p><p class="mt-1 text-xs text-neutral-500">{{ achievement.organizer || '—' }} · {{ achievement.category || '—' }} · {{ achievement.level || '—' }}</p><p class="mt-1 text-xs text-neutral-500">Peringkat {{ value(achievement.rank) }} · {{ date(achievement.achievement_date) }}</p><p v-if="achievement.description" class="mt-2 whitespace-pre-line text-sm text-neutral-700 dark:text-neutral-300">{{ achievement.description }}</p></div>
                            <Button v-if="typeof achievement.certificate === 'string' && achievement.certificate" type="button" variant="outline" size="sm" class="shrink-0" @click="openPreview(`/storage/${achievement.certificate}`, 'Sertifikat prestasi')"><Eye class="mr-1 h-3.5 w-3.5" /> Preview</Button>
                        </div>
                    </div>
                </section>
                <section v-if="student.violations?.length" class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Kedisiplinan</h2>
                    <div v-for="violation in student.violations" :key="violation.id" class="border-t border-neutral-100 py-3 dark:border-neutral-800">
                        <p class="text-sm font-semibold">{{ violation.title }}</p><p class="text-xs text-neutral-500">{{ date(violation.violation_date) }} · {{ violation.point ?? 0 }} poin</p><p v-if="violation.description" class="mt-1 whitespace-pre-line text-sm text-neutral-700 dark:text-neutral-300">{{ violation.description }}</p>
                    </div>
                </section>
            </div>
            <section v-else class="rounded-2xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-neutral-900">
                <p class="text-sm text-neutral-600 dark:text-neutral-300">Belum ada data siswa yang terhubung dengan akun {{ authUser?.email }}.</p>
                <Button class="mt-4" @click="dialogOpen = true">Lengkapi data siswa</Button>
            </section>
        </div>
    </main>

    <WelcomeFormDialog
        v-model:open="dialogOpen"
        :student="student"
        :schools="schools" :majors="majors" :classrooms="classrooms" :academic-years="academicYears"
        :genders="genders" :religions="religions" :student-statuses="studentStatuses" :blood-types="bloodTypes"
        :occupations="occupations" :income-categories="incomeCategories" :citizenships="citizenships"
        :education-levels="educationLevels" :social-platforms="socialPlatforms" :relationship-types="relationshipTypes"
        :document-types="documentTypes"
    />
    <div v-if="previewItem" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" @click.self="previewItem = null" @keydown.esc="previewItem = null">
        <section class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900" role="dialog" aria-modal="true" :aria-label="previewItem.title">
            <header class="flex items-center justify-between gap-4 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <h2 class="truncate text-sm font-semibold">{{ previewItem.title }}</h2>
                <button type="button" class="rounded-lg p-2 text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800" aria-label="Tutup pratinjau" @click="previewItem = null"><X class="h-4 w-4" /></button>
            </header>
            <div class="flex min-h-0 flex-1 items-center justify-center overflow-auto bg-neutral-100 p-3 dark:bg-neutral-950">
                <iframe v-if="previewItem.pdf" :src="previewItem.url" class="h-[78vh] w-full rounded-lg bg-white" :title="previewItem.title" />
                <img v-else :src="previewItem.url" :alt="previewItem.title" class="max-h-[78vh] max-w-full rounded-lg object-contain" />
            </div>
        </section>
    </div>
</template>
