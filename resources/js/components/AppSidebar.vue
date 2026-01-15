<script setup lang="ts">
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
import { type NavItem } from '@/types';

import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Library, FileCog, Handshake, CircleUserRound, ShieldUser } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current URL from Inertia
const page = usePage();
const currentUrl = computed(() => page.url);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        isActive: computed(() => currentUrl.value === '/dashboard' || currentUrl.value.startsWith('/dashboard')),
    },
    {
        title: 'Book Loans',
        href: '/loans',
        icon: Handshake,
        isActive: computed(() => currentUrl.value.startsWith('/loans')),
    },
    {
        title: 'Books',
        href: '/books',
        icon: Library,
        isActive: computed(() => currentUrl.value.startsWith('/books')),
    },
    {
        title: 'Catalog',
        icon: FileCog,
        isActive: computed(() =>
            currentUrl.value.startsWith('/categories') ||
            currentUrl.value.startsWith('/publishers') ||
            currentUrl.value.startsWith('/genres')
        ),
        items: [
            {
                title: 'Categories',
                href: '/categories',
                isActive: computed(() => currentUrl.value.startsWith('/categories')),
            },
            {
                title: 'Publishers',
                href: '/publishers',
                isActive: computed(() => currentUrl.value.startsWith('/publishers')),
            },
            {
                title: 'Genres',
                href: '/genres',
                isActive: computed(() => currentUrl.value.startsWith('/genres')),
            },
        ],
    },
    {
        title: 'Admins',
        href: '/admins',
        icon: ShieldUser,
        isActive: computed(() => currentUrl.value.startsWith('/admins')),
    },
    {
        title: 'Members',
        href: '/members',
        icon: CircleUserRound,
        isActive: computed(() => currentUrl.value.startsWith('/members')),
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
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
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>