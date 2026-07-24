<script setup lang="ts">
import { route } from "ziggy-js";
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Megaphone } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: route('admin.announcements.index'),
    },
];

interface PaginatedAnnouncements {
    data: any[];
    links: any[];
    total: number;
}

const props = defineProps<{
    announcements: PaginatedAnnouncements;
}>();

const stripHtml = (html: string) => {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto">

            <FlashAlert />
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-yellow-950">Announcements <span class="text-primary text-6xl leading-none">.</span></h1>
                    <p class="text-yellow-800 font-medium">Broadcast updates, events, and important news to library members.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.can?.create_announcements" :href="route('admin.announcements.create')" class="contents">
                        <Button class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                            <Megaphone class="h-4 w-4" />
                            Create New Announcement
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Announcements Grid -->
            <div v-if="announcements.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div v-for="announcement in announcements.data" :key="announcement.id"
                    class="overflow-hidden rounded-xl border border-border bg-background transition hover:shadow-sm">
                    <!-- Cover -->
                    <div class="relative h-48 bg-muted">
                        <img v-if="announcement.image_path" :src="`/storage/${announcement.image_path}`" :alt="announcement.title"
                            class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center bg-primary/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-primary/40" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <span v-if="!announcement.is_active" class="absolute right-2 top-2 rounded-full bg-destructive/90 px-2 py-1 text-xs font-medium text-destructive-foreground">
                            Draft / Inactive
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="flex flex-col gap-3 p-4">
                        <div>
                            <h3 class="line-clamp-2 font-semibold text-foreground">
                                <Link :href="route('admin.announcements.show', announcement.id)">
                                    {{ announcement.title }}
                                </Link>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-1">
                                By {{ announcement.creator?.name || 'System' }} &bull; {{ formatDate(announcement.created_at) }}
                            </p>
                        </div>

                        <p class="text-sm text-foreground line-clamp-3">
                            {{ stripHtml(announcement.content) }}
                        </p>

                        <div class="flex gap-2 mt-2 pt-2 border-t border-border">
                            <Link :href="route('admin.announcements.show', announcement.id)"
                                class="flex-1 rounded-md bg-muted px-3 py-2 text-center text-sm text-foreground hover:bg-muted/80">
                                View
                            </Link>
                            
                            <Link v-if="$page.props.auth.can?.edit_announcements" :href="route('admin.announcements.edit', announcement.id)"
                                class="flex-none rounded-md border border-input bg-background px-3 py-2 text-center text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground">
                                Edit
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border border-border bg-background p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-muted-foreground" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-foreground">
                    No announcements yet
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    There are currently no active announcements.
                </p>
                <Link v-if="$page.props.auth.can?.create_announcements" :href="route('admin.announcements.create')"
                    class="mt-4 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Create One
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="announcements.links?.length > 3" class="bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ announcements.data.length }} of {{ announcements.total }}
                    </div>
                    <div class="flex gap-2">
                        <Link v-for="(link, index) in announcements.links" :key="index" :href="link.url || ''" :class="[
                            'rounded-md px-3 py-2 text-sm',
                            link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'pointer-events-none opacity-50'
                        ]" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
