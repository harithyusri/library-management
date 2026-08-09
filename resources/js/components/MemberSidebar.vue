<script setup lang="ts">
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
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid, BookOpen, ClipboardList, BookMarked,
    DoorOpen, AlertTriangle, Receipt, Megaphone,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const navItems = [
    { title: 'Dashboard',      href: '/member/dashboard',     icon: LayoutGrid,    match: '/member/dashboard' },
    { title: 'Announcements',  href: '/member/announcements', icon: Megaphone,     match: '/member/announcements' },
    { title: 'Book Catalog',   href: '/member/catalog',       icon: BookOpen,      match: '/member/catalog' },
    { title: 'My Loans',       href: '/member/loans',         icon: ClipboardList, match: '/member/loans' },
    { title: 'My Reservations',href: '/member/reservations',  icon: BookMarked,    match: '/member/reservations' },
    { title: 'Room Bookings',  href: '/member/room-bookings', icon: DoorOpen,      match: '/member/room-bookings' },
    { title: 'Report Issue',   href: '/member/maintenance',   icon: AlertTriangle, match: '/member/maintenance' },
    { title: 'My Fines',       href: '/member/fines',         icon: Receipt,       match: '/member/fines' },
];

const isActive = (match: string) => currentUrl.value.startsWith(match);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="bg-[#0d1a14] border-r-0">
        <SidebarHeader class="bg-[#0d1a14] border-b border-white/5 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/member/dashboard" class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#c5a059]">
                                <AppLogoIcon class="size-5 fill-current text-[#0d1a14]" />
                            </div>
                            <div class="grid flex-1 text-left">
                                <span class="truncate font-bold text-[#f1f5f9] leading-tight">PinjamBuku</span>
                                <span class="text-[10px] font-bold text-[#c5a059] uppercase tracking-widest leading-none">Member Portal</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-[#0d1a14] px-2 py-3">
            <SidebarMenu class="gap-0.5">
                <SidebarMenuItem v-for="item in navItems" :key="item.href">
                    <SidebarMenuButton
                        as-child
                        :tooltip="item.title"
                        class="h-10 rounded-xl transition-all"
                        :class="isActive(item.match)
                            ? 'bg-[#c5a059] text-[#0d1a14] font-bold hover:bg-[#c5a059] hover:text-[#0d1a14]'
                            : 'text-[#f1f5f9]/60 hover:bg-white/5 hover:text-[#f1f5f9]'"
                    >
                        <Link :href="item.href" class="flex items-center gap-3">
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="text-sm">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter class="bg-[#0d1a14] border-t border-white/5 pt-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
