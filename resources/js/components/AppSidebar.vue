<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    School,
    UserCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as classroomsIndex } from '@/routes/classrooms';
import { index as majorsIndex } from '@/routes/majors';
import { index as studentsIndex } from '@/routes/students';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Jurusan',
        href: majorsIndex(),
        icon: GraduationCap,
    },
    {
        title: 'Kelas',
        href: classroomsIndex(),
        icon: School,
    },
    {
        title: 'Siswa',
        href: studentsIndex(),
        icon: Users,
    },
    {
        title: 'Akun Siswa',
        href: '/student-accounts',
        icon: UserCheck,
    },
];

const page = usePage();
const canManageStudents = computed(() => Boolean((page.props as any).auth?.permissions?.manageStudents));
const canManageAcademics = computed(() => Boolean((page.props as any).auth?.permissions?.manageAcademics));
const visibleMainNavItems = computed(() => mainNavItems.filter((item) => {
    if (item.title === 'Dashboard') {
return canManageStudents.value || canManageAcademics.value;
}

    if (item.title === 'Jurusan' || item.title === 'Kelas') {
return canManageAcademics.value;
}

    return canManageStudents.value;
}));

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="canManageStudents || canManageAcademics ? dashboard() : '/'">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleMainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
