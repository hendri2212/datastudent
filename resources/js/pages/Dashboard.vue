<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';

type GenderCount = { name: string; total: number };
type Demographics = { genders: GenderCount[]; religions: GenderCount[] };
type ClassroomCount = { id: number; name: string; level: string; major: string | null; total: number; demographics: Demographics };
type NamedCount = { id?: number; name: string; total: number; code?: string; demographics?: Demographics };
type AgeGroup = { label: string; total: number };
type Birthday = { id: number; name: string; date: string; age: number; daysUntil: number };
type Activity = { id: number; name: string; action: string; date: string | null; type: 'created' | 'updated' | 'verified' | 'unverified' | 'deleted' | 'restored'; actorName: string; actorRole: string };

const props = defineProps<{
    summary: { total: number; male: number; female: number; classes: number; verified: number; pending: number; averageAge: number };
    genderCounts: GenderCount[];
    religionCounts: NamedCount[];
    majorCounts: NamedCount[];
    classrooms: ClassroomCount[];
    ageGroups: AgeGroup[];
    upcomingBirthdays: Birthday[];
    recentActivity: Activity[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const numberFormat = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });
type SortOrder = 'desc' | 'asc';
const classSort = ref<SortOrder>('desc');
const majorSort = ref<SortOrder>('desc');
const religionSort = ref<SortOrder>('desc');
const selectedClassMajor = ref('Semua jurusan');
const activeGender = ref<string | null>(null);
const genderTotal = computed(() => props.genderCounts.reduce((sum, item) => sum + item.total, 0));
const religionTotal = computed(() => props.religionCounts.reduce((sum, item) => sum + item.total, 0));
const genderPercentages = computed(() => props.genderCounts.map((item) => ({
    ...item,
    percent: genderTotal.value ? Math.round((item.total / genderTotal.value) * 100) : 0,
})));
const majorityGender = computed(() => genderPercentages.value.reduce<(typeof genderPercentages.value)[number] | null>((top, item) => !top || item.total > top.total ? item : top, null));
const centerGender = computed(() => genderPercentages.value.find((item) => item.name === activeGender.value) ?? majorityGender.value);
const chartBackground = computed(() => {
    const colors = ['#2563eb', '#ec4899', '#14b8a6', '#f59e0b', '#8b5cf6'];
    let cursor = 0;
    const segments = props.genderCounts.map((item, index) => {
        const start = cursor;
        cursor += genderTotal.value ? (item.total / genderTotal.value) * 100 : 0;
        const color = activeGender.value && activeGender.value !== item.name ? '#cbd5e1' : colors[index % colors.length];

        return `${color} ${start}% ${cursor}%`;
    });

    return segments.length ? `conic-gradient(${segments.join(', ')})` : '#e2e8f0';
});
const maxClassCount = computed(() => Math.max(1, ...props.classrooms.map((item) => item.total)));
const maxMajorCount = computed(() => Math.max(1, ...props.majorCounts.map((item) => item.total)));
const maxReligionCount = computed(() => Math.max(1, ...props.religionCounts.map((item) => item.total)));
const maxAgeGroupCount = computed(() => Math.max(1, ...props.ageGroups.map((item) => item.total)));
const classMajorOptions = computed(() => ['Semua jurusan', ...new Set(props.classrooms.map((item) => item.major ?? 'Lainnya'))]);
const sortedClassrooms = computed(() => props.classrooms
    .filter((item) => selectedClassMajor.value === 'Semua jurusan' || (item.major ?? 'Lainnya') === selectedClassMajor.value)
    .slice()
    .sort((a, b) => (a.total - b.total) * (classSort.value === 'desc' ? -1 : 1)));
const sortedMajors = computed(() => [...props.majorCounts].sort((a, b) => (a.total - b.total) * (majorSort.value === 'desc' ? -1 : 1)));
const sortedReligions = computed(() => [...props.religionCounts].sort((a, b) => (a.total - b.total) * (religionSort.value === 'desc' ? -1 : 1)));
const genderColors = ['bg-blue-600', 'bg-pink-500', 'bg-teal-500', 'bg-amber-500', 'bg-violet-500'];
const birthdayLabel = (days: number) => days === 0 ? 'Hari ini' : `${days} hari lagi`;
const formatDate = (date: string | null) => date ? dateFormat.format(new Date(date)) : 'Tanggal tidak tersedia';
</script>

<template>
    <Head title="Dashboard Admin" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-8">
        <header>
            <p class="text-sm font-medium text-blue-600">Ringkasan sekolah</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard Admin</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pantau data siswa dan sebaran kelas secara ringkas.</p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan siswa">
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 0">
                <p class="text-sm text-slate-500 dark:text-slate-400">Total siswa aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.total) }}</p>
                <p class="mt-1 text-xs text-slate-500">Siswa dengan kelas aktif</p>
            </article>
            <article class="dashboard-card rounded-2xl border border-blue-100 bg-blue-50/70 p-5 shadow-sm dark:border-blue-900 dark:bg-blue-950/40" style="--card-order: 1">
                <p class="text-sm text-blue-700 dark:text-blue-300">Siswa laki-laki</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.male) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ summary.total ? Math.round((summary.male / summary.total) * 100) : 0 }}% dari total siswa</p>
            </article>
            <article class="dashboard-card rounded-2xl border border-pink-100 bg-pink-50/70 p-5 shadow-sm dark:border-pink-900 dark:bg-pink-950/40" style="--card-order: 2">
                <p class="text-sm text-pink-700 dark:text-pink-300">Siswa perempuan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.female) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ summary.total ? Math.round((summary.female / summary.total) * 100) : 0 }}% dari total siswa</p>
            </article>
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 3">
                <p class="text-sm text-slate-500 dark:text-slate-400">Kelas aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.classes) }}</p>
                <p class="mt-1 text-xs text-slate-500">Rombel dengan status aktif</p>
            </article>
            <article class="dashboard-card rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/40" style="--card-order: 4">
                <p class="text-sm text-emerald-700 dark:text-emerald-300">Data terverifikasi</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.verified) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ summary.total ? Math.round((summary.verified / summary.total) * 100) : 0 }}% dari siswa aktif</p>
            </article>
            <article class="dashboard-card rounded-2xl border border-amber-100 bg-amber-50/70 p-5 shadow-sm dark:border-amber-900 dark:bg-amber-950/40" style="--card-order: 5">
                <p class="text-sm text-amber-700 dark:text-amber-300">Menunggu verifikasi</p>
                <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ numberFormat.format(summary.pending) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data siswa yang belum diverifikasi</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 6">
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">Komposisi jenis kelamin</h2>
                    <p class="mt-1 text-sm text-slate-500">Perbandingan siswa dengan kelas aktif</p>
                </div>
                <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:justify-center">
                    <div class="donut-wrap relative grid size-44 shrink-0 place-items-center rounded-full outline-none focus-visible:ring-2 focus-visible:ring-blue-500" role="img" :aria-label="`Grafik lingkaran komposisi ${genderTotal} siswa.`">
                        <div class="donut-chart absolute inset-0 rounded-full" :style="{ background: chartBackground }"></div>
                        <Transition name="gender-value" mode="out-in">
                            <div :key="activeGender ?? centerGender?.name ?? 'empty'" class="donut-center relative z-10 grid size-28 place-content-center rounded-full bg-white text-center dark:bg-slate-900" :class="activeGender ? 'scale-105' : ''">
                                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ centerGender?.percent ?? 0 }}%</span>
                                <span class="max-w-24 truncate text-xs font-medium text-slate-600 dark:text-slate-300">{{ activeGender ?? centerGender?.name ?? 'Belum ada data' }}</span>
                                <span class="mt-0.5 text-[10px] text-slate-400">{{ numberFormat.format(centerGender?.total ?? genderTotal) }} siswa</span>
                            </div>
                        </Transition>
                    </div>
                    <ul class="w-full space-y-3 sm:w-auto">
                        <li v-for="(item, index) in genderPercentages" :key="item.name" class="gender-legend-row flex min-w-36 cursor-pointer items-center justify-between gap-5 rounded-lg px-2 py-1.5 text-sm outline-none" :class="activeGender === item.name ? 'gender-legend-active' : ''" tabindex="0" @mouseenter="activeGender = item.name" @mouseleave="activeGender = null" @focus="activeGender = item.name" @blur="activeGender = null">
                            <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300"><span class="size-2.5 rounded-full" :class="genderColors[index % genderColors.length]"></span>{{ item.name }}</span>
                            <span class="flex items-center gap-2"><span class="text-xs font-medium text-slate-500">{{ item.percent }}%</span><span class="font-semibold text-slate-900 dark:text-white">{{ numberFormat.format(item.total) }}</span></span>
                        </li>
                        <li v-if="genderCounts.length === 0" class="text-sm text-slate-500">Belum ada data siswa.</li>
                    </ul>
                </div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 7">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Siswa per kelas</h2>
                        <p class="mt-1 text-sm text-slate-500">Jumlah siswa berdasarkan rombongan belajar</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ sortedClassrooms.length }} kelas</span>
                        <select v-model="selectedClassMajor" class="max-w-44 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" aria-label="Filter kelas berdasarkan jurusan">
                            <option v-for="major in classMajorOptions" :key="major" :value="major">{{ major }}</option>
                        </select>
                        <div class="flex gap-1" role="group" aria-label="Urutkan kelas">
                            <button type="button" class="sort-button" :class="classSort === 'desc' ? 'sort-button-active' : ''" :aria-pressed="classSort === 'desc'" @click="classSort = 'desc'">Terbanyak</button>
                            <button type="button" class="sort-button" :class="classSort === 'asc' ? 'sort-button-active' : ''" :aria-pressed="classSort === 'asc'" @click="classSort = 'asc'">Tersedikit</button>
                        </div>
                    </div>
                </div>
                <div v-if="sortedClassrooms.length" class="mt-6 max-h-80 space-y-4 overflow-y-auto pr-1">
                    <div v-for="classroom in sortedClassrooms" :key="classroom.id" class="group rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-blue-500" tabindex="0" :aria-label="`${classroom.name}, ${classroom.total} siswa. Arahkan atau fokus untuk melihat rincian gender dan agama.`">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ classroom.name }}</span>
                                <span v-if="classroom.major" class="ml-2 text-xs text-slate-500">{{ classroom.major }}</span>
                            </div>
                            <span class="shrink-0 font-semibold text-slate-700 dark:text-slate-300">{{ numberFormat.format(classroom.total) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="chart-bar h-full rounded-full bg-blue-600" :style="{ width: `${(classroom.total / maxClassCount) * 100}%` }"></div>
                        </div>
                        <div class="demographic-reveal grid grid-cols-2 gap-3 rounded-lg bg-blue-50/80 px-3 text-xs dark:bg-blue-950/40">
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Jenis kelamin</p><p v-for="gender in classroom.demographics.genders" :key="gender.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ gender.name }}</span><span class="font-medium">{{ numberFormat.format(gender.total) }}</span></p><p v-if="!classroom.demographics.genders.length" class="text-slate-500">Belum ada data</p></div>
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Agama</p><p v-for="religion in classroom.demographics.religions" :key="religion.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ religion.name }}</span><span class="font-medium">{{ numberFormat.format(religion.total) }}</span></p><p v-if="!classroom.demographics.religions.length" class="text-slate-500">Belum ada data</p></div>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Tidak ada kelas aktif pada jurusan ini.</div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Statistik agama dan jurusan">
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 8">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-slate-900 dark:text-white">Siswa berdasarkan agama</h2><p class="mt-1 text-sm text-slate-500">Termasuk kategori tanpa siswa</p></div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950 dark:text-violet-300">{{ numberFormat.format(religionTotal) }} siswa</span>
                        <div class="flex gap-1" role="group" aria-label="Urutkan agama">
                            <button type="button" class="sort-button" :class="religionSort === 'desc' ? 'sort-button-active' : ''" :aria-pressed="religionSort === 'desc'" @click="religionSort = 'desc'">Terbanyak</button>
                            <button type="button" class="sort-button" :class="religionSort === 'asc' ? 'sort-button-active' : ''" :aria-pressed="religionSort === 'asc'" @click="religionSort = 'asc'">Tersedikit</button>
                        </div>
                    </div>
                </div>
                <div v-if="religionCounts.length" class="mt-6 max-h-72 space-y-4 overflow-y-auto pr-1">
                    <div v-for="religion in sortedReligions" :key="religion.name">
                        <div class="mb-1.5 flex justify-between gap-3 text-sm"><span class="font-medium text-slate-800 dark:text-slate-200">{{ religion.name }}</span><span class="font-semibold text-slate-700 dark:text-slate-300">{{ numberFormat.format(religion.total) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="chart-bar h-full rounded-full bg-violet-500" :style="{ width: `${(religion.total / maxReligionCount) * 100}%` }"></div></div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Data agama belum tersedia.</div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 9">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-slate-900 dark:text-white">Siswa berdasarkan jurusan</h2><p class="mt-1 text-sm text-slate-500">Sebaran siswa pada jurusan aktif</p></div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 dark:bg-teal-950 dark:text-teal-300">{{ majorCounts.length }} jurusan</span>
                        <div class="flex gap-1" role="group" aria-label="Urutkan jurusan">
                            <button type="button" class="sort-button" :class="majorSort === 'desc' ? 'sort-button-active' : ''" :aria-pressed="majorSort === 'desc'" @click="majorSort = 'desc'">Terbanyak</button>
                            <button type="button" class="sort-button" :class="majorSort === 'asc' ? 'sort-button-active' : ''" :aria-pressed="majorSort === 'asc'" @click="majorSort = 'asc'">Tersedikit</button>
                        </div>
                    </div>
                </div>
                <div v-if="majorCounts.length" class="mt-6 max-h-72 space-y-4 overflow-y-auto pr-1">
                    <div v-for="major in sortedMajors" :key="major.code ?? major.name" class="group rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-teal-500" tabindex="0" :aria-label="`${major.name}, ${major.total} siswa. Arahkan atau fokus untuk melihat rincian gender dan agama.`">
                        <div class="mb-1.5 flex justify-between gap-3 text-sm"><span class="min-w-0"><span v-if="major.code" class="mr-2 text-xs text-slate-400">{{ major.code }}</span><span class="font-medium text-slate-800 dark:text-slate-200">{{ major.name }}</span></span><span class="shrink-0 font-semibold text-slate-700 dark:text-slate-300">{{ numberFormat.format(major.total) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="chart-bar h-full rounded-full bg-teal-500" :style="{ width: `${(major.total / maxMajorCount) * 100}%` }"></div></div>
                        <div class="demographic-reveal grid grid-cols-2 gap-3 rounded-lg bg-teal-50/80 px-3 text-xs dark:bg-teal-950/40">
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Jenis kelamin</p><p v-for="gender in major.demographics?.genders ?? []" :key="gender.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ gender.name }}</span><span class="font-medium">{{ numberFormat.format(gender.total) }}</span></p><p v-if="!major.demographics?.genders.length" class="text-slate-500">Belum ada data</p></div>
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Agama</p><p v-for="religion in major.demographics?.religions ?? []" :key="religion.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ religion.name }}</span><span class="font-medium">{{ numberFormat.format(religion.total) }}</span></p><p v-if="!major.demographics?.religions.length" class="text-slate-500">Belum ada data</p></div>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Jurusan aktif belum tersedia.</div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-3" aria-label="Usia, ulang tahun, dan aktivitas siswa">
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 10">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-slate-900 dark:text-white">Statistik usia</h2><p class="mt-1 text-sm text-slate-500">Usia siswa dengan kelas aktif</p></div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Rata-rata {{ summary.averageAge }} th</span>
                </div>
                <div v-if="ageGroups.length" class="mt-6 space-y-4">
                    <div v-for="group in ageGroups" :key="group.label">
                        <div class="mb-1.5 flex justify-between gap-3 text-sm"><span class="font-medium text-slate-800 dark:text-slate-200">{{ group.label }}</span><span class="font-semibold text-slate-700 dark:text-slate-300">{{ numberFormat.format(group.total) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="chart-bar h-full rounded-full bg-indigo-500" :style="{ width: `${(group.total / maxAgeGroupCount) * 100}%` }"></div></div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Data tanggal lahir belum tersedia.</div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 11">
                <div><h2 class="font-semibold text-slate-900 dark:text-white">Ulang tahun mendatang</h2><p class="mt-1 text-sm text-slate-500">Dalam 30 hari ke depan</p></div>
                <div v-if="upcomingBirthdays.length" class="mt-5 max-h-72 space-y-2 overflow-y-auto pr-1">
                    <div v-for="birthday in upcomingBirthdays" :key="birthday.id" class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/60">
                        <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ birthday.name }}</p><p class="text-xs text-slate-500">{{ formatDate(birthday.date) }} · genap {{ birthday.age }} tahun</p></div>
                        <span class="shrink-0 rounded-full bg-pink-50 px-2.5 py-1 text-xs font-semibold text-pink-700 dark:bg-pink-950 dark:text-pink-300">{{ birthdayLabel(birthday.daysUntil) }}</span>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Tidak ada ulang tahun dalam 30 hari ke depan.</div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 12">
                <div><h2 class="font-semibold text-slate-900 dark:text-white">Aktivitas terbaru</h2><p class="mt-1 text-sm text-slate-500">Tambah, pembaruan, dan verifikasi data</p></div>
                <div v-if="recentActivity.length" class="mt-5 max-h-72 space-y-1 overflow-y-auto pr-1">
                    <div v-for="activity in recentActivity" :key="activity.id" class="flex gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="activity.type === 'verified' || activity.type === 'restored' ? 'bg-emerald-500' : activity.type === 'created' ? 'bg-blue-500' : activity.type === 'deleted' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                        <div class="min-w-0"><p class="text-xs text-slate-500">{{ activity.action }}</p><p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ activity.name }}</p><p class="text-xs text-slate-400">Oleh {{ activity.actorName }} · {{ activity.actorRole === 'admin' ? 'Admin' : activity.actorRole === 'operator' ? 'Operator' : activity.actorRole === 'student' ? 'Siswa' : 'Sistem' }} · {{ formatDate(activity.date) }}</p></div>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Aktivitas baru akan tercatat di sini setelah data siswa ditambahkan, diperbarui, atau diverifikasi.</div>
            </article>
        </section>
    </main>
</template>

<style scoped>
.dashboard-card {
    animation: card-enter 500ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
    animation-delay: calc(var(--card-order, 0) * 55ms);
    transition: transform 200ms ease, box-shadow 200ms ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px -18px rgb(15 23 42 / 35%);
}

.donut-chart {
    animation: chart-enter 750ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
}

.donut-center {
    transition: transform 260ms cubic-bezier(0.2, 0.75, 0.25, 1), box-shadow 260ms ease;
}

.gender-legend-row {
    transition: background-color 180ms ease, transform 180ms ease;
}

.gender-legend-row:hover,
.gender-legend-row:focus-visible,
.gender-legend-active {
    transform: translateX(3px);
    background-color: rgb(37 99 235 / 8%);
}

.gender-value-enter-active,
.gender-value-leave-active {
    transition: opacity 140ms ease, transform 180ms ease;
}

.gender-value-enter-from {
    opacity: 0;
    transform: translateY(5px) scale(0.94);
}

.gender-value-leave-to {
    opacity: 0;
    transform: translateY(-5px) scale(0.94);
}

.sort-button {
    border-radius: 9999px;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: rgb(100 116 139);
    transition: color 160ms ease, background-color 160ms ease, transform 160ms ease;
}

.sort-button:hover {
    transform: translateY(-1px);
    background-color: rgb(226 232 240 / 75%);
}

.sort-button-active {
    background-color: rgb(37 99 235 / 10%);
    color: rgb(29 78 216);
}

.chart-bar {
    transform-origin: left;
    animation: bar-enter 800ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
    transition: width 500ms ease, filter 180ms ease, transform 180ms ease;
}

.group {
    transition: background-color 180ms ease, box-shadow 180ms ease;
}

.group:hover,
.group:focus-within {
    background-color: rgb(148 163 184 / 8%);
    box-shadow: 0 0 0 0.375rem rgb(148 163 184 / 8%);
}

.group:hover .chart-bar,
.group:focus-within .chart-bar {
    filter: brightness(1.12);
    transform: scaleY(1.35);
}

.demographic-reveal {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
    transition: max-height 240ms ease, opacity 180ms ease, margin 180ms ease, padding 180ms ease;
}

.group:hover .demographic-reveal {
    max-height: 14rem;
    margin-top: 0.5rem;
    padding-top: 0.625rem;
    padding-bottom: 0.625rem;
    opacity: 1;
    overflow-y: auto;
    transition-delay: 1.5s;
}

.group:focus-within .demographic-reveal {
    max-height: 14rem;
    margin-top: 0.5rem;
    padding-top: 0.625rem;
    padding-bottom: 0.625rem;
    opacity: 1;
    overflow-y: auto;
    transition-delay: 0s;
}

@keyframes card-enter {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes chart-enter {
    from { opacity: 0; transform: scale(0.88) rotate(-12deg); }
    to { opacity: 1; transform: scale(1) rotate(0); }
}

@keyframes bar-enter {
    from { transform: scaleX(0); }
    to { transform: scaleX(1); }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
