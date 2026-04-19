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
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Library, FileCog, Handshake, Settings, Home, DoorOpen, ClipboardList, Receipt, FileSpreadsheet, Megaphone, AlertTriangle} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current URL from Inertia
const page = usePage();
const currentUrl = computed(() => page.url);

const dashboardHref = computed(() => {
    return page.props.auth.is_member ? '/member/dashboard' : '/admin/dashboard';
});

const memberNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/member/dashboard',
        icon: LayoutGrid,
        isActive: computed(() => currentUrl.value === '/member/dashboard' || currentUrl.value.startsWith('/member/dashboard')),
    },
    {
        title: 'Announcements',
        href: '/member/announcements',
        icon: Megaphone,
        isActive: computed(() => currentUrl.value.startsWith('/member/announcements')),
    },
    {
        title: 'Book Catalog',
        href: '/member/catalog',
        icon: BookOpen,
        isActive: computed(() => currentUrl.value.startsWith('/member/catalog')),
    },
    {
        title: 'My Loans',
        href: '/member/loans',
        icon: ClipboardList,
        isActive: computed(() => currentUrl.value === '/member/loans' || currentUrl.value.startsWith('/member/loans')),
    },
    {
        title: 'Room Bookings',
        href: '/member/room-bookings',
        icon: DoorOpen,
        isActive: computed(() => currentUrl.value.startsWith('/member/room-bookings')),
    },
    {
        title: 'Report Issue',
        href: '/member/maintenance',
        icon: AlertTriangle,
        isActive: computed(() => currentUrl.value.startsWith('/member/maintenance')),
    },
    {
        title: 'My Fines',
        href: '/member/fines',
        icon: Receipt,
        isActive: computed(() => currentUrl.value.startsWith('/member/fines')),
    },
]);

const adminNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
        isActive: computed(() => currentUrl.value === '/admin/dashboard' || currentUrl.value.startsWith('/admin/dashboard')),
    },
    {
        title: 'Announcements',
        href: '/admin/announcements',
        icon: Megaphone,
        isActive: computed(() => currentUrl.value.startsWith('/admin/announcements')),
        hidden: !page.props.auth.can?.view_announcements,
    },
    {
        title: 'Books & Loans',
        href: '/admin/books',
        icon: Library,
        isActive: computed(() => 
            currentUrl.value.startsWith('/admin/books') || currentUrl.value.startsWith('/admin/loans')
        ),
        items: [
            {
                title: 'List',
                href: '/admin/books',
                isActive: computed(() => currentUrl.value.startsWith('/admin/books')),
                hidden: !page.props.auth.can?.view_books,
            },
            {
                title: 'Loans',
                href: '/admin/loans',
                isActive: computed(() => currentUrl.value.startsWith('/admin/loans')),
                hidden: !page.props.auth.can?.view_loans,
            },
        ],
    },
    {
        title: 'Rooms',
        icon: DoorOpen,
        isActive: computed(() => 
            currentUrl.value.startsWith('/admin/rooms') || currentUrl.value.startsWith('/admin/room-bookings')
        ),
        items: [
            {
                title: 'List',
                href: '/admin/rooms',
                isActive: computed(() => currentUrl.value.startsWith('/admin/rooms')),
                hidden: !page.props.auth.can?.view_rooms,
            },
            {
                title: 'Room Bookings',
                href: '/admin/room-bookings',
                isActive: computed(() => currentUrl.value.startsWith('/admin/room-bookings')),
                hidden: !page.props.auth.can?.view_room_bookings,
            },
        ],
    },
    {
        title: 'Maintenance',
        href: '/admin/maintenance-reports',
        icon: AlertTriangle,
        isActive: computed(() => currentUrl.value.startsWith('/admin/maintenance-reports')),
        hidden: !page.props.auth.is_staff, // Show to all staff for now, or use a specific permission if available
    },
    {
        title: 'Fines & Payments',
        href: '/admin/fines',
        icon: Receipt,
        isActive: computed(() => currentUrl.value.startsWith('/admin/fines')),
        hidden: !page.props.auth.can?.view_fines,
    },
    {
        title: 'Catalog',
        icon: FileCog,
        isActive: computed(() =>
            currentUrl.value.startsWith('/admin/categories') ||
            currentUrl.value.startsWith('/admin/publishers') ||
            currentUrl.value.startsWith('/admin/genres')
        ),
        items: [
            {
                title: 'Categories',
                href: '/admin/categories',
                isActive: computed(() => currentUrl.value.startsWith('/admin/categories')),
                hidden: !page.props.auth.can?.view_categories,
            },
            {
                title: 'Publishers',
                href: '/admin/publishers',
                isActive: computed(() => currentUrl.value.startsWith('/admin/publishers')),
                hidden: !page.props.auth.can?.view_publishers,
            },
            {
                title: 'Genres',
                href: '/admin/genres',
                isActive: computed(() => currentUrl.value.startsWith('/admin/genres')),
                hidden: !page.props.auth.can?.view_genres,
            },
        ],
    },
    {
        title: 'Reports',
        icon: FileSpreadsheet,
        isActive: computed(() => 
            currentUrl.value.startsWith('/admin/loan-reports') ||
            currentUrl.value.startsWith('/admin/room-reservation-reports')
        ),
        items: [
            {
                title: 'Loan Reports',
                href: '/admin/loan-reports',
                isActive: computed(() => currentUrl.value.startsWith('/admin/loan-reports')),
                hidden: !page.props.auth.can?.view_reports,
            },
            {
                title: 'Room Reservation Reports',
                href: '/admin/room-reservation-reports',
                isActive: computed(() => currentUrl.value.startsWith('/admin/room-reservation-reports')),
                hidden: !page.props.auth.can?.view_reports,
            },
        ],
    },
    {
        title: 'Settings',
        icon: Settings,
        isActive: computed(() =>
            currentUrl.value.startsWith('/admin/roles') ||
            currentUrl.value.startsWith('/admin/staffs') ||
            currentUrl.value.startsWith('/admin/members') ||
            currentUrl.value.startsWith('/admin/departments') ||
            currentUrl.value.startsWith('/admin/audits')
        ),
        // Group visibility depends on sub-items (handled in filter below)
        items: [
            {
                title: 'Access Control',
                href: '/admin/roles',
                isActive: computed(() => currentUrl.value.startsWith('/admin/roles')),
                hidden: !page.props.auth.can?.manage_roles,
            },
            {
                title: 'System Staff',
                href: '/admin/staffs',
                isActive: computed(() => currentUrl.value.startsWith('/admin/staffs')), // Fixed from /admins
                hidden: !page.props.auth.can?.view_users,
            },
            {
                title: 'Members',
                href: '/admin/members',
                isActive: computed(() => currentUrl.value.startsWith('/admin/members')),
                hidden: !page.props.auth.can?.view_users,
            },
            {
                title: 'Libraries',
                href: '/admin/libraries',
                isActive: computed(() => currentUrl.value.startsWith('/admin/libraries')),
                hidden: !page.props.auth.can?.view_libraries,
            },
            {
                title: 'Departments',
                href: '/admin/departments',
                isActive: computed(() => currentUrl.value.startsWith('/admin/departments')),
                hidden: !page.props.auth.can?.manage_roles,
            },
            {
                title: 'Audit Logs',
                href: '/admin/audits',
                isActive: computed(() => currentUrl.value.startsWith('/admin/audits')),
                hidden: !page.props.auth.can?.view_audits,
            },
        ],
    }
]);

// Helper to filter hidden items recursively
const filterHidden = (items: NavItem[]): NavItem[] => {
    return items.filter(i => !i.hidden).map(i => {
        if (i.items) {
            return { ...i, items: filterHidden(i.items) };
        }
        return i;
    }).filter(i => {
        // Hide parent items if they have an empty children array after filtering and no direct href
        if (i.items && i.items.length === 0 && !i.href) return false;
        return true;
    });
};

const filteredMemberNavItems = computed(() => filterHidden(memberNavItems.value));
const filteredAdminNavItems = computed(() => filterHidden(adminNavItems.value));

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="page.props.auth.is_member" :items="filteredMemberNavItems" />
            <NavMain v-if="page.props.auth.is_staff && filteredAdminNavItems.length > 0" :items="filteredAdminNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>