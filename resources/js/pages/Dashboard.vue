<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import { dashboard } from '@/routes';

type GenderCount = { name: string; total: number };
type Demographics = { genders: GenderCount[]; religions: GenderCount[] };
type ClassroomCount = { id: number; name: string; level: string; major: string | null; total: number; demographics: Demographics };
type NamedCount = { id?: number; name: string; total: number; code?: string; demographics?: Demographics };
type AgeGroup = { label: string; total: number };
type Birthday = { id: number; name: string; date: string; age: number; daysUntil: number };
type Activity = { id: number; name: string; action: string; date: string | null; type: 'created' | 'updated' | 'verified' | 'unverified' | 'deleted' | 'restored' | string; actorName: string; actorRole: string; description?: string | null; changes?: Record<string, { old: string | null; new: string | null }> | null };
type ActivityResponse = { data: Activity[]; currentPage: number; lastPage: number; total: number };
type StudentDetailRow = { id: number; name: string; age: number; birthdayAge: number; birthDate: string; nextBirthday: string; daysUntil: number; classroomId: number | null; majorId: number | null; classroom: string; major: string; gender: string; religion: string };
type StatisticsDetailsResponse = { data: StudentDetailRow[]; summary: Record<string, NamedCount[]> | null; filters: { majors: Array<{ id: number; name: string }>; classrooms: Array<{ id: number; name: string; majorId: number | null }> }; currentPage: number; lastPage: number; total: number };

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

const isActivityDialogOpen = ref(false);
const selectedActivity = ref<Activity | null>(null);
const isActivityDetailOpen = ref(false);
const activitySearch = ref('');
const activityRole = ref('');
const activityRows = ref<Activity[]>([]);
const activityLoading = ref(false);
const activityError = ref(false);
const activityCurrentPage = ref(1);
const activityLastPage = ref(1);
const activityTotal = ref(0);
const isStatisticsDetailOpen = ref(false);
const statisticsDetailType = ref<'birthdays' | 'age'>('birthdays');
const statisticsDetailTitle = ref('');
const statisticsAgeGroup = ref(0);
const statisticsDetailRows = ref<StudentDetailRow[]>([]);
const statisticsDetailSummary = ref<Record<string, NamedCount[]> | null>(null);
const statisticsDetailMajors = ref<Array<{ id: number; name: string }>>([]);
const statisticsDetailClassrooms = ref<Array<{ id: number; name: string; majorId: number | null }>>([]);
const statisticsDetailSearch = ref('');
const statisticsDetailMajor = ref('');
const statisticsDetailClassroom = ref('');
const statisticsDetailPage = ref(1);
const statisticsDetailLastPage = ref(1);
const statisticsDetailTotal = ref(0);
const statisticsDetailLoading = ref(false);
const statisticsDetailError = ref(false);
let statisticsDetailRequestId = 0;
let statisticsDetailSearchTimer: ReturnType<typeof setTimeout> | undefined;
let activitySearchTimer: ReturnType<typeof setTimeout> | undefined;
let activityRequestId = 0;

const activityPaginationItems = computed<Array<number | 'ellipsis'>>(() => {
    const last = activityLastPage.value;

    if (last <= 7) {
return Array.from({ length: last }, (_, index) => index + 1);
}

    const start = Math.max(2, Math.min(activityCurrentPage.value - 1, last - 3));
    const end = Math.min(last - 1, Math.max(activityCurrentPage.value + 1, 4));
    const pages: Array<number | 'ellipsis'> = [1];

    if (start > 2) {
pages.push('ellipsis');
}

    for (let page = start; page <= end; page++) {
pages.push(page);
}

    if (end < last - 1) {
pages.push('ellipsis');
}

    pages.push(last);

    return pages;
});

const loadStatisticsDetails = async (page = 1) => {
    const requestId = ++statisticsDetailRequestId;
    const params = new URLSearchParams({ type: statisticsDetailType.value, page: String(page) });

    if (statisticsDetailType.value === 'age') {
params.set('age_group', String(statisticsAgeGroup.value));
}

    if (statisticsDetailType.value === 'age' && statisticsDetailSearch.value.trim()) {
params.set('search', statisticsDetailSearch.value.trim());
}

    if (statisticsDetailType.value === 'age' && statisticsDetailMajor.value) {
params.set('major', statisticsDetailMajor.value);
}

    if (statisticsDetailType.value === 'age' && statisticsDetailClassroom.value) {
params.set('classroom', statisticsDetailClassroom.value);
}

    statisticsDetailLoading.value = true;
    statisticsDetailError.value = false;

    try {
        const response = await fetch(`/dashboard/statistics-details?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
throw new Error('Gagal memuat detail statistik.');
}

        const result = await response.json() as StatisticsDetailsResponse;

        if (requestId !== statisticsDetailRequestId) {
return;
}

        statisticsDetailRows.value = result.data;
        statisticsDetailSummary.value = result.summary;
        statisticsDetailMajors.value = result.filters.majors;
        statisticsDetailClassrooms.value = result.filters.classrooms;
        statisticsDetailPage.value = result.currentPage;
        statisticsDetailLastPage.value = result.lastPage;
        statisticsDetailTotal.value = result.total;
    } catch {
        if (requestId === statisticsDetailRequestId) {
statisticsDetailError.value = true;
}
    } finally {
        if (requestId === statisticsDetailRequestId) {
statisticsDetailLoading.value = false;
}
    }
};

const openStatisticsDetails = (type: 'birthdays' | 'age', ageGroup = 0) => {
    statisticsDetailType.value = type;
    statisticsAgeGroup.value = ageGroup;
    statisticsDetailTitle.value = type === 'birthdays' ? 'Semua ulang tahun dalam 30 hari ke depan' : `Detail siswa usia ${props.ageGroups[ageGroup]?.label ?? ''}`;
    statisticsDetailRows.value = [];
    statisticsDetailSearch.value = '';
    statisticsDetailMajor.value = '';
    statisticsDetailClassroom.value = '';
    statisticsDetailPage.value = 1;
    isStatisticsDetailOpen.value = true;
    void loadStatisticsDetails();
};

watch([statisticsDetailSearch, statisticsDetailMajor, statisticsDetailClassroom], () => {
    if (!isStatisticsDetailOpen.value || statisticsDetailType.value !== 'age') {
return;
}

    if (statisticsDetailSearchTimer) {
clearTimeout(statisticsDetailSearchTimer);
}

    statisticsDetailSearchTimer = setTimeout(() => {
 void loadStatisticsDetails(1); 
}, 250);
}, { flush: 'sync' });

watch(statisticsDetailMajor, (major) => {
    const selectedClass = statisticsDetailClassrooms.value.find((classroom) => String(classroom.id) === statisticsDetailClassroom.value);

    if (major && selectedClass && selectedClass.majorId !== Number(major)) {
statisticsDetailClassroom.value = '';
}
}, { flush: 'sync' });
const statisticsAvailableClassrooms = computed(() => statisticsDetailClassrooms.value.filter((classroom) => !statisticsDetailMajor.value || classroom.majorId === Number(statisticsDetailMajor.value)));

const loadActivityHistory = async (pageNumber = 1) => {
    const requestId = ++activityRequestId;
    const params = new URLSearchParams({ page: String(pageNumber) });

    if (activitySearch.value.trim()) {
params.set('search', activitySearch.value.trim());
}

    if (activityRole.value) {
params.set('role', activityRole.value);
}

    activityLoading.value = true;
    activityError.value = false;

    try {
        const response = await fetch(`/dashboard/activities?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
throw new Error('Gagal memuat riwayat aktivitas.');
}

        const result = await response.json() as ActivityResponse;

        if (requestId !== activityRequestId) {
return;
}

        activityRows.value = result.data;
        activityCurrentPage.value = result.currentPage;
        activityLastPage.value = result.lastPage;
        activityTotal.value = result.total;
    } catch {
        if (requestId === activityRequestId) {
activityError.value = true;
}
    } finally {
        if (requestId === activityRequestId) {
activityLoading.value = false;
}
    }
};

const openActivityHistory = () => {
    activitySearch.value = '';
    activityRole.value = '';
    activityRows.value = [];
    isActivityDialogOpen.value = true;
    void loadActivityHistory();
};

watch([activitySearch, activityRole], () => {
    if (!isActivityDialogOpen.value) {
return;
}

    if (activitySearchTimer) {
clearTimeout(activitySearchTimer);
}

    activitySearchTimer = setTimeout(() => {
 void loadActivityHistory(); 
}, 300);
}, { flush: 'sync' });

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const numberFormat = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });
const activityDateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
type SortOrder = 'desc' | 'asc';
type ClassSortOrder = SortOrder | 'major';
const classSort = ref<ClassSortOrder>('major');
const majorSort = ref<SortOrder>('desc');
const religionSort = ref<SortOrder>('desc');
const selectedClassMajor = ref('Semua jurusan');
const activeGender = ref<string | null>(null);
const hoveredClassroom = ref<ClassroomCount | null>(null);
const classChartArea = ref<HTMLElement | null>(null);
const classChartAvailableWidth = ref(760);
let classroomHoverTimer: ReturnType<typeof setTimeout> | undefined;
let classroomClearTimer: ReturnType<typeof setTimeout> | undefined;
watch(classChartArea, (area, _previousArea, onCleanup) => {
    if (!area) {
return;
}

    const observer = new ResizeObserver(([entry]) => {
        if (entry) {
classChartAvailableWidth.value = Math.max(320, Math.floor(entry.contentRect.width));
}
    });
    observer.observe(area);
    onCleanup(() => observer.disconnect());
}, { flush: 'post' });
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
const majorAxisMax = computed(() => Math.max(10, Math.ceil(Math.max(1, ...props.majorCounts.map((item) => item.total)) / 10) * 10));
const majorAxisTicks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((fraction) => ({
    value: Math.round(majorAxisMax.value * fraction),
    position: fraction * 100,
})));
const majorBarColors = ['#4388d8', '#4bc477', '#ffae42', '#f76168', '#9854b2', '#21a9a0', '#e879b5'];
const majorColorByCode: Record<string, string> = {
    AKL: '#22c55e',
    DKV: '#3b82f6',
    TKJ: '#ef4444',
    MP: '#a855f7',
    RPL: '#ffeb3b',
    KULINER: '#6b7280',
    PM: '#ff6d00',
};
const majorBarColor = (major: NamedCount, index: number) => {
    const code = (major.code ?? '').trim().toLocaleUpperCase('id-ID');

    if (majorColorByCode[code]) {
return majorColorByCode[code];
}

    const name = major.name.toLocaleUpperCase('id-ID');

    if (name.includes('KULINER')) {
return majorColorByCode.KULINER;
}

    const namedCode = Object.keys(majorColorByCode).find((candidate) => candidate !== 'KULINER' && new RegExp(`(^|[^A-Z0-9])${candidate}([^A-Z0-9]|$)`).test(name));

    return namedCode ? majorColorByCode[namedCode] : majorBarColors[index % majorBarColors.length];
};
const maxReligionCount = computed(() => Math.max(1, ...props.religionCounts.map((item) => item.total)));
const maxAgeGroupCount = computed(() => Math.max(1, ...props.ageGroups.map((item) => item.total)));
const classMajorOptions = computed(() => ['Semua jurusan', ...new Set(props.classrooms.map((item) => item.major ?? 'Lainnya'))]);
const sortedClassrooms = computed(() => props.classrooms
    .filter((item) => selectedClassMajor.value === 'Semua jurusan' || (item.major ?? 'Lainnya') === selectedClassMajor.value)
    .slice()
    .sort((a, b) => {
        if (classSort.value === 'major') {
            const majorOrder = (a.major ?? 'Lainnya').localeCompare(b.major ?? 'Lainnya', 'id');

            if (majorOrder !== 0) {
return majorOrder;
}

            const levelOrder = ['X', 'XI', 'XII'].indexOf(a.level) - ['X', 'XI', 'XII'].indexOf(b.level);

            return levelOrder || a.name.localeCompare(b.name, 'id', { numeric: true });
        }

        return (a.total - b.total) * (classSort.value === 'desc' ? -1 : 1);
    }));
const classChartHeight = 340;
const classChartWidth = computed(() => Math.max(classChartAvailableWidth.value, sortedClassrooms.value.length * 100));
const classChartMax = computed(() => Math.max(10, Math.ceil(Math.max(1, ...sortedClassrooms.value.map((item) => item.total)) / 10) * 10));
const classChartPoints = computed(() => {
    const left = 58;
    const right = 24;
    const top = 36;
    const bottom = 62;
    const plotWidth = classChartWidth.value - left - right;
    const plotHeight = classChartHeight - top - bottom;

    return sortedClassrooms.value.map((classroom, index) => ({
        classroom,
        x: sortedClassrooms.value.length === 1 ? left + plotWidth / 2 : left + index * plotWidth / (sortedClassrooms.value.length - 1),
        y: top + plotHeight - (classroom.total / classChartMax.value) * plotHeight,
    }));
});
const classChartPath = computed(() => classChartPoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' '));
const classChartTicks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((fraction) => ({
    value: Math.round(classChartMax.value * fraction),
    y: 36 + (classChartHeight - 36 - 62) * (1 - fraction),
})));
const showClassroomDetails = (classroom: ClassroomCount) => {
    if (classroomHoverTimer) {
clearTimeout(classroomHoverTimer);
}

    if (classroomClearTimer) {
clearTimeout(classroomClearTimer);
}

    classroomHoverTimer = setTimeout(() => {
 hoveredClassroom.value = classroom; 
}, 1500);
};
const clearClassroomDetails = () => {
    if (classroomHoverTimer) {
clearTimeout(classroomHoverTimer);
}

    if (classroomClearTimer) {
clearTimeout(classroomClearTimer);
}

    classroomClearTimer = setTimeout(() => {
 hoveredClassroom.value = null; 
}, 1200);
};
const keepClassroomDetails = () => {
    if (classroomClearTimer) {
clearTimeout(classroomClearTimer);
}
};
const sortedMajors = computed(() => [...props.majorCounts].sort((a, b) => (a.total - b.total) * (majorSort.value === 'desc' ? -1 : 1)));
const sortedReligions = computed(() => [...props.religionCounts].sort((a, b) => (a.total - b.total) * (religionSort.value === 'desc' ? -1 : 1)));
const genderColors = ['bg-blue-600', 'bg-pink-500', 'bg-teal-500', 'bg-amber-500', 'bg-violet-500'];
const birthdayLabel = (days: number) => days === 0 ? 'Hari ini' : `${days} hari lagi`;
const formatDate = (date: string | null) => date ? dateFormat.format(new Date(date)) : 'Tanggal tidak tersedia';
const formatActivityDate = (date: string | null) => date ? activityDateFormat.format(new Date(date)) : 'Tanggal tidak tersedia';
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
                    <div><h2 class="font-semibold text-slate-900 dark:text-white">Aktivitas terbaru</h2><p class="mt-1 text-sm text-slate-500">Tambah, pembaruan, dan verifikasi data</p></div>
                    <Button variant="ghost" size="sm" class="shrink-0 text-xs text-blue-700 hover:text-blue-800 dark:text-blue-300" @click="openActivityHistory"><Search class="mr-1.5 size-3.5" /> Lihat semua</Button>
                </div>
                <div v-if="recentActivity.length" class="mt-5 max-h-72 space-y-1 overflow-y-auto pr-1">
                    <div v-for="activity in recentActivity" :key="activity.id" class="flex gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="activity.type === 'verified' || activity.type === 'restored' ? 'bg-emerald-500' : activity.type === 'created' ? 'bg-blue-500' : activity.type === 'deleted' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                        <div class="min-w-0"><p class="text-xs text-slate-500">{{ activity.action }}</p><p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ activity.name }}</p><p class="text-xs text-slate-400">Oleh {{ activity.actorName }} · {{ activity.actorRole === 'admin' ? 'Admin' : activity.actorRole === 'operator' ? 'Operator' : activity.actorRole === 'student' ? 'Siswa' : 'Sistem' }} · {{ formatDate(activity.date) }}</p></div>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Aktivitas baru akan tercatat di sini setelah data siswa ditambahkan, diperbarui, atau diverifikasi.</div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:col-span-2" style="--card-order: 8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Siswa per kelas</h2>
                        <p class="mt-1 text-sm text-slate-500">Jumlah siswa berdasarkan rombongan belajar</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ sortedClassrooms.length }} kelas</span>
                        <select v-model="selectedClassMajor" class="h-9 max-w-48 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" aria-label="Filter kelas berdasarkan jurusan">
                            <option v-for="major in classMajorOptions" :key="major" :value="major">{{ major }}</option>
                        </select>
                        <div class="flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="Urutkan kelas">
                            <button type="button" class="sort-button" :class="classSort === 'major' ? 'sort-button-active' : ''" :aria-pressed="classSort === 'major'" @click="classSort = 'major'">Per jurusan</button>
                            <button type="button" class="sort-button" :class="classSort === 'desc' ? 'sort-button-active' : ''" :aria-pressed="classSort === 'desc'" @click="classSort = 'desc'">Terbanyak</button>
                            <button type="button" class="sort-button" :class="classSort === 'asc' ? 'sort-button-active' : ''" :aria-pressed="classSort === 'asc'" @click="classSort = 'asc'">Tersedikit</button>
                        </div>
                    </div>
                </div>
                <div v-if="sortedClassrooms.length" class="mt-6">
                    <div ref="classChartArea" class="overflow-x-auto rounded-xl border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <svg :viewBox="`0 0 ${classChartWidth} ${classChartHeight}`" :style="{ width: `${classChartWidth}px`, height: `${classChartHeight}px` }" class="max-w-none" role="group" aria-label="Grafik garis jumlah siswa per kelas">
                        <g v-for="tick in classChartTicks" :key="tick.value">
                            <line x1="58" :y1="tick.y" :x2="classChartWidth - 24" :y2="tick.y" stroke="currentColor" class="text-slate-300 dark:text-slate-700" stroke-dasharray="5 5" />
                            <text x="48" :y="tick.y + 4" text-anchor="end" class="fill-slate-500" font-size="12">{{ numberFormat.format(tick.value) }}</text>
                        </g>
                        <line x1="58" :y1="classChartHeight - 62" :x2="classChartWidth - 24" :y2="classChartHeight - 62" stroke="currentColor" class="text-slate-500" />
                        <path v-if="classChartPoints.length > 1" :d="classChartPath" pathLength="1" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="class-line" />
                        <g v-for="point in classChartPoints" :key="point.classroom.id" class="class-point" tabindex="0" role="button" :aria-label="`${point.classroom.name}: ${point.classroom.total} siswa. Fokus untuk rincian gender dan agama.`" @mouseenter="showClassroomDetails(point.classroom)" @mouseleave="clearClassroomDetails" @focus="showClassroomDetails(point.classroom)" @blur="clearClassroomDetails">
                            <circle :cx="point.x" :cy="point.y" r="12" fill="transparent" />
                            <circle :cx="point.x" :cy="point.y" r="5" fill="#2563eb" stroke="white" stroke-width="2" class="class-dot" />
                            <text :x="point.x" :y="point.y - 14" text-anchor="middle" class="fill-slate-700 dark:fill-slate-200" font-size="12" font-weight="600">{{ numberFormat.format(point.classroom.total) }}</text>
                            <text :x="point.x" :y="classChartHeight - 38" text-anchor="middle" class="fill-slate-700 dark:fill-slate-200" font-size="12" :title="point.classroom.name">{{ point.classroom.name }}</text>
                        </g>
                        <g transform="translate(0 14)">
                            <line x1="0" y1="0" x2="22" y2="0" stroke="#2563eb" stroke-width="3" stroke-linecap="round" />
                            <circle cx="11" cy="0" r="4" fill="#2563eb" />
                            <text x="30" y="4" class="fill-slate-500" font-size="11">Jumlah siswa</text>
                        </g>
                    </svg>
                    </div>
                    <Transition name="gender-value">
                        <div v-if="hoveredClassroom" class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 rounded-xl border border-blue-100 bg-blue-50/80 p-4 text-xs dark:border-blue-900 dark:bg-blue-950/40" @mouseenter="keepClassroomDetails" @mouseleave="clearClassroomDetails">
                            <p class="col-span-2 font-semibold text-slate-800 dark:text-slate-100">{{ hoveredClassroom.name }} · {{ numberFormat.format(hoveredClassroom.total) }} siswa</p>
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Jenis kelamin</p><p v-for="gender in hoveredClassroom.demographics.genders" :key="gender.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ gender.name }}</span><span class="font-medium">{{ numberFormat.format(gender.total) }}</span></p><p v-if="!hoveredClassroom.demographics.genders.length" class="text-slate-500">Belum ada data</p></div>
                            <div><p class="mb-1 font-semibold text-slate-700 dark:text-slate-200">Agama</p><p v-for="religion in hoveredClassroom.demographics.religions" :key="religion.name" class="flex justify-between gap-2 py-0.5 text-slate-600 dark:text-slate-300"><span>{{ religion.name }}</span><span class="font-medium">{{ numberFormat.format(religion.total) }}</span></p><p v-if="!hoveredClassroom.demographics.religions.length" class="text-slate-500">Belum ada data</p></div>
                        </div>
                    </Transition>
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
                <div v-if="majorCounts.length" class="mt-6 w-full pb-2">
                    <div class="relative h-[22rem] w-full min-w-0 pl-11 pr-2" role="group" aria-label="Grafik batang jumlah siswa per jurusan">
                        <div class="absolute top-7 right-2 bottom-7 left-11">
                            <div v-for="tick in majorAxisTicks" :key="tick.position" class="absolute right-0 left-0 border-t border-dashed border-slate-300 dark:border-slate-700" :style="{ bottom: `${tick.position}%` }">
                                <span class="absolute right-full mr-2 -translate-y-1/2 text-xs text-slate-500">{{ numberFormat.format(tick.value) }}</span>
                            </div>
                        </div>
                        <div class="absolute top-7 right-2 bottom-7 left-11 flex items-end gap-2 border-b border-slate-400 dark:border-slate-600">
                            <div v-for="(major, index) in sortedMajors" :key="major.code ?? major.name" class="group relative flex h-full min-w-0 flex-1 items-end justify-center outline-none" tabindex="0" :aria-label="`${major.name}, ${major.total} siswa. Arahkan atau fokus untuk melihat rincian gender dan agama.`">
                                <div class="major-column relative w-full" :style="{ height: `${(major.total / majorAxisMax) * 100}%` }">
                                    <span class="major-value absolute left-1/2 -translate-x-1/2 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ numberFormat.format(major.total) }}</span>
                                    <div class="major-bar absolute inset-x-2 bottom-0 rounded-t-sm" :style="{ backgroundColor: majorBarColor(major, index) }"></div>
                                </div>
                                <span class="absolute top-full mt-2 w-full truncate px-1 text-center text-xs font-medium text-slate-700 dark:text-slate-200" :title="major.name">{{ major.code ?? major.name }}</span>
                                <div class="major-tooltip pointer-events-none absolute top-1 left-1/2 z-30 w-64 -translate-x-1/2 rounded-xl border border-teal-100 bg-white p-3 text-left shadow-xl dark:border-teal-900 dark:bg-slate-950">
                                    <p class="mb-2 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ major.name }} · {{ numberFormat.format(major.total) }} siswa</p>
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div><p class="mb-1 font-semibold text-slate-600 dark:text-slate-300">Jenis kelamin</p><p v-for="gender in major.demographics?.genders ?? []" :key="gender.name" class="flex justify-between gap-2 py-0.5 text-slate-500"><span>{{ gender.name }}</span><span>{{ numberFormat.format(gender.total) }}</span></p><p v-if="!major.demographics?.genders?.length" class="text-slate-500">Belum ada data</p></div>
                                        <div><p class="mb-1 font-semibold text-slate-600 dark:text-slate-300">Agama</p><p v-for="religion in major.demographics?.religions ?? []" :key="religion.name" class="flex justify-between gap-2 py-0.5 text-slate-500"><span>{{ religion.name }}</span><span>{{ numberFormat.format(religion.total) }}</span></p><p v-if="!major.demographics?.religions?.length" class="text-slate-500">Belum ada data</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Jurusan aktif belum tersedia.</div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Statistik usia dan ulang tahun siswa">
            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 10">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-slate-900 dark:text-white">Statistik usia</h2><p class="mt-1 text-sm text-slate-500">Usia siswa dengan kelas aktif</p></div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Rata-rata {{ summary.averageAge }} th</span>
                </div>
                <div v-if="ageGroups.length" class="mt-6 space-y-4">
                    <div v-for="(group, index) in ageGroups" :key="group.label">
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm"><span class="font-medium text-slate-800 dark:text-slate-200">{{ group.label }}</span><div class="flex items-center gap-2"><span class="font-semibold text-slate-700 dark:text-slate-300">{{ numberFormat.format(group.total) }}</span><Button variant="ghost" size="sm" class="h-7 px-2 text-xs" @click="openStatisticsDetails('age', index)">Detail</Button></div></div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="chart-bar h-full rounded-full bg-indigo-500" :style="{ width: `${(group.total / maxAgeGroupCount) * 100}%` }"></div></div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Data tanggal lahir belum tersedia.</div>
            </article>

            <article class="dashboard-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="--card-order: 11">
                <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900 dark:text-white">Ulang tahun mendatang</h2><p class="mt-1 text-sm text-slate-500">Dalam 30 hari ke depan</p></div><Button variant="outline" size="sm" @click="openStatisticsDetails('birthdays')">Cek detail</Button></div>
                <div v-if="upcomingBirthdays.length" class="mt-5 max-h-72 space-y-2 overflow-y-auto pr-1">
                    <div v-for="birthday in upcomingBirthdays" :key="birthday.id" class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/60">
                        <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200">{{ birthday.name }}</p><p class="text-xs text-slate-500">{{ formatDate(birthday.date) }} · genap {{ birthday.age }} tahun</p></div>
                        <span class="shrink-0 rounded-full bg-pink-50 px-2.5 py-1 text-xs font-semibold text-pink-700 dark:bg-pink-950 dark:text-pink-300">{{ birthdayLabel(birthday.daysUntil) }}</span>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Tidak ada ulang tahun dalam 30 hari ke depan.</div>
            </article>

        </section>

        <Dialog v-model:open="isActivityDialogOpen">
            <DialogContent class="flex h-[min(78vh,680px)] max-h-[85vh] flex-col gap-0 sm:max-w-2xl">
                <DialogHeader class="shrink-0 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <DialogTitle>Semua riwayat aktivitas</DialogTitle>
                    <DialogDescription>Riwayat penambahan, perubahan, penghapusan, dan verifikasi data siswa.</DialogDescription>
                </DialogHeader>
                <div class="relative mt-4 shrink-0">
                    <Search class="absolute top-2.5 left-3 size-4 text-slate-400" />
                    <Input v-model="activitySearch" class="pl-9 pr-20" placeholder="Cari siswa, aktivitas, atau pelaku..." aria-label="Cari riwayat aktivitas" />
                    <span class="absolute top-2.5 right-3 text-xs text-slate-400">{{ activityTotal }} log</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2" aria-label="Filter role pelaku aktivitas">
                    <Button v-for="role in [{ label: 'Semua', value: '' }, { label: 'Siswa', value: 'student' }, { label: 'Admin', value: 'admin' }, { label: 'Operator', value: 'operator' }]" :key="role.value || 'all'" :variant="activityRole === role.value ? 'default' : 'outline'" size="sm" :disabled="activityLoading" @click="activityRole = role.value">{{ role.label }}</Button>
                </div>
                <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-xl border border-slate-100 dark:border-slate-800">
                    <div v-if="activityRows.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div v-for="activity in activityRows" :key="activity.id" role="button" tabindex="0" class="flex cursor-pointer gap-3 px-4 py-3 text-left transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary dark:hover:bg-slate-800/50" @click="selectedActivity = activity; isActivityDetailOpen = true" @keydown.enter="selectedActivity = activity; isActivityDetailOpen = true" @keydown.space.prevent="selectedActivity = activity; isActivityDetailOpen = true">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="activity.type === 'verified' || activity.type === 'restored' ? 'bg-emerald-500' : activity.type === 'created' ? 'bg-blue-500' : activity.type === 'deleted' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ activity.action }}</p>
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ activity.name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">Oleh {{ activity.actorName }} · {{ activity.actorRole === 'admin' ? 'Admin' : activity.actorRole === 'operator' ? 'Operator' : activity.actorRole === 'student' ? 'Siswa' : 'Sistem' }}</p>
                            </div>
                            <time class="shrink-0 text-right text-xs text-slate-400">{{ formatActivityDate(activity.date) }}</time>
                        </div>
                    </div>
                    <div v-else-if="activityLoading" class="p-8 text-center text-sm text-slate-500">Memuat riwayat aktivitas...</div>
                    <div v-else-if="activityError" class="p-8 text-center text-sm text-rose-600">Riwayat gagal dimuat. <button type="button" class="font-semibold underline" @click="loadActivityHistory(activityCurrentPage)">Coba lagi</button></div>
                    <div v-else class="p-8 text-center text-sm text-slate-500">Tidak ada aktivitas yang cocok dengan pencarian.</div>
                </div>
                <DialogFooter class="mt-4 shrink-0 sm:justify-between">
                    <span class="self-center text-xs text-slate-500">{{ activityTotal ? `Menampilkan ${(activityCurrentPage - 1) * 20 + 1}–${Math.min(activityCurrentPage * 20, activityTotal)} dari ${activityTotal}` : 'Tidak ada log' }}</span>
                    <nav v-if="activityLastPage > 1" aria-label="Pagination riwayat aktivitas" class="flex items-center gap-1">
                        <Button variant="outline" size="sm" :disabled="activityLoading || activityCurrentPage <= 1" aria-label="Halaman sebelumnya" @click="loadActivityHistory(activityCurrentPage - 1)">‹</Button>
                        <template v-for="(item, index) in activityPaginationItems" :key="`${item}-${index}`">
                            <span v-if="item === 'ellipsis'" class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>
                            <Button v-else :variant="item === activityCurrentPage ? 'default' : 'outline'" size="sm" :disabled="activityLoading" :aria-current="item === activityCurrentPage ? 'page' : undefined" @click="loadActivityHistory(item)">{{ item }}</Button>
                        </template>
                        <Button variant="outline" size="sm" :disabled="activityLoading || activityCurrentPage >= activityLastPage" aria-label="Halaman berikutnya" @click="loadActivityHistory(activityCurrentPage + 1)">›</Button>
                    </nav>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isActivityDetailOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Detail aktivitas</DialogTitle>
                    <DialogDescription v-if="selectedActivity">{{ selectedActivity.action }} · {{ formatActivityDate(selectedActivity.date) }}</DialogDescription>
                </DialogHeader>
                <div v-if="selectedActivity" class="mt-4 space-y-4">
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
                        <p class="text-xs text-slate-500">Siswa</p>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ selectedActivity.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">Dilakukan oleh {{ selectedActivity.actorName }} · {{ selectedActivity.actorRole === 'admin' ? 'Admin' : selectedActivity.actorRole === 'operator' ? 'Operator' : selectedActivity.actorRole === 'student' ? 'Siswa' : 'Sistem' }}</p>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">Perubahan data</h3>
                        <div v-if="selectedActivity.changes && Object.keys(selectedActivity.changes).length" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                            <div v-for="(change, field) in selectedActivity.changes" :key="field" class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3 border-b border-slate-100 p-3 last:border-b-0 dark:border-slate-800">
                                <div class="min-w-0"><p class="text-xs font-medium text-slate-500">{{ field }} · Sebelum</p><p class="break-words text-sm">{{ change.old || '—' }}</p></div>
                                <div class="min-w-0"><p class="text-xs font-medium text-slate-500">Sesudah</p><p class="break-words text-sm font-medium text-slate-900 dark:text-slate-100">{{ change.new || '—' }}</p></div>
                            </div>
                        </div>
                        <p v-else class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-700">Detail perubahan tidak tersedia untuk riwayat ini.</p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isStatisticsDetailOpen">
            <DialogContent class="flex h-[min(82vh,720px)] max-h-[85vh] flex-col gap-0 sm:max-w-4xl">
                <DialogHeader class="shrink-0 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <DialogTitle>{{ statisticsDetailTitle }}</DialogTitle>
                    <DialogDescription v-if="statisticsDetailType === 'age'">Rincian siswa beserta kelas, jurusan, jenis kelamin, dan agama.</DialogDescription>
                    <DialogDescription v-else>Daftar seluruh siswa yang berulang tahun dalam 30 hari ke depan.</DialogDescription>
                </DialogHeader>
                <div v-if="statisticsDetailType === 'age'" class="mt-4 grid shrink-0 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,0.8fr)_minmax(10rem,0.8fr)]">
                    <div class="relative">
                        <Search class="absolute top-2.5 left-3 size-4 text-slate-400" />
                        <Input v-model="statisticsDetailSearch" class="pl-9" placeholder="Cari nama, kelas, jurusan..." aria-label="Cari detail siswa berdasarkan usia" />
                    </div>
                    <select v-model="statisticsDetailMajor" class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Filter berdasarkan jurusan">
                        <option value="">Semua jurusan</option>
                        <option v-for="major in statisticsDetailMajors" :key="major.id" :value="String(major.id)">{{ major.name }}</option>
                    </select>
                    <select v-model="statisticsDetailClassroom" class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Filter berdasarkan kelas">
                        <option value="">Semua kelas</option>
                        <option v-for="classroom in statisticsAvailableClassrooms" :key="classroom.id" :value="String(classroom.id)">{{ classroom.name }}</option>
                    </select>
                </div>
                <div v-if="statisticsDetailType === 'age' && statisticsDetailSummary" class="mt-4 grid shrink-0 grid-cols-2 gap-3 xl:grid-cols-4">
                    <section v-for="(counts, label) in statisticsDetailSummary" :key="label" class="max-h-32 overflow-y-auto rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                        <h3 class="mb-2 text-xs font-semibold capitalize text-slate-700 dark:text-slate-200">{{ label }}</h3>
                        <div v-for="item in counts" :key="item.name" class="flex justify-between gap-2 py-0.5 text-xs text-slate-500"><span class="truncate">{{ item.name }}</span><span class="font-medium">{{ numberFormat.format(item.total) }}</span></div>
                        <p v-if="!counts.length" class="text-xs text-slate-400">Belum ada data.</p>
                    </section>
                </div>
                <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-xl border border-slate-100 dark:border-slate-800">
                    <div v-if="statisticsDetailRows.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <article v-for="student in statisticsDetailRows" :key="student.id" class="px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0"><h3 class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ student.name }}</h3><p v-if="statisticsDetailType === 'birthdays'" class="mt-0.5 text-xs text-pink-700 dark:text-pink-300">{{ formatDate(student.nextBirthday) }} · genap {{ student.birthdayAge }} tahun</p><p v-else class="mt-0.5 text-xs text-slate-500">{{ student.age }} tahun · lahir {{ formatDate(student.birthDate) }}</p></div>
                                <span v-if="statisticsDetailType === 'birthdays'" class="rounded-full bg-pink-50 px-2.5 py-1 text-xs font-semibold text-pink-700 dark:bg-pink-950 dark:text-pink-300">{{ birthdayLabel(student.daysUntil) }}</span>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-500 sm:grid-cols-4"><p><span class="font-medium text-slate-600 dark:text-slate-300">Kelas:</span> {{ student.classroom }}</p><p><span class="font-medium text-slate-600 dark:text-slate-300">Jurusan:</span> {{ student.major }}</p><p><span class="font-medium text-slate-600 dark:text-slate-300">Kelamin:</span> {{ student.gender }}</p><p><span class="font-medium text-slate-600 dark:text-slate-300">Agama:</span> {{ student.religion }}</p></div>
                        </article>
                    </div>
                    <div v-else-if="statisticsDetailLoading" class="p-8 text-center text-sm text-slate-500">Memuat detail siswa...</div>
                    <div v-else-if="statisticsDetailError" class="p-8 text-center text-sm text-rose-600">Detail gagal dimuat. <button type="button" class="font-semibold underline" @click="loadStatisticsDetails(statisticsDetailPage)">Coba lagi</button></div>
                    <div v-else class="p-8 text-center text-sm text-slate-500">Tidak ada siswa untuk ditampilkan.</div>
                </div>
                <DialogFooter class="mt-4 shrink-0 sm:justify-between">
                    <span class="self-center text-xs text-slate-500">{{ statisticsDetailTotal ? `Halaman ${statisticsDetailPage} dari ${statisticsDetailLastPage} · ${statisticsDetailTotal} siswa` : 'Tidak ada siswa' }}</span>
                    <div v-if="statisticsDetailLastPage > 1" class="flex gap-2"><Button variant="outline" size="sm" :disabled="statisticsDetailLoading || statisticsDetailPage <= 1" @click="loadStatisticsDetails(statisticsDetailPage - 1)">Sebelumnya</Button><Button variant="outline" size="sm" :disabled="statisticsDetailLoading || statisticsDetailPage >= statisticsDetailLastPage" @click="loadStatisticsDetails(statisticsDetailPage + 1)">Berikutnya</Button></div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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

.class-line {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
    animation: class-line-enter 900ms ease-out forwards;
}

.class-point {
    cursor: pointer;
    outline: none;
}

.class-dot {
    transition: r 160ms ease, filter 160ms ease;
}

.class-point:hover .class-dot,
.class-point:focus .class-dot {
    r: 7px;
    filter: drop-shadow(0 2px 3px rgb(37 99 235 / 35%));
}

.major-bar {
    height: 100%;
    transform-origin: bottom;
    animation: major-bar-enter 750ms cubic-bezier(0.2, 0.75, 0.25, 1) both;
    transition: filter 180ms ease, transform 180ms ease;
}

.major-value {
    top: -1.5rem;
    white-space: nowrap;
}

.major-tooltip {
    visibility: hidden;
    opacity: 0;
    transition: opacity 180ms ease, visibility 0s linear 1.5s;
}

.group:hover .major-tooltip {
    visibility: visible;
    opacity: 1;
    transition-delay: 1.5s;
}

.group:focus-within .major-tooltip {
    visibility: visible;
    opacity: 1;
    transition-delay: 0s;
}

.group:hover .major-bar,
.group:focus-within .major-bar {
    filter: brightness(1.08);
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

@keyframes major-bar-enter {
    from { transform: scaleY(0); }
    to { transform: scaleY(1); }
}

@keyframes class-line-enter {
    to { stroke-dashoffset: 0; }
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
