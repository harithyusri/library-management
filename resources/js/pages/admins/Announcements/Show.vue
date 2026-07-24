<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import DOMPurify from 'dompurify';
import { computed } from 'vue';

const props = defineProps<{
    announcement: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Announcements', href: route('admin.announcements.index') },
    { title: props.announcement.title, href: route('admin.announcements.show', props.announcement.id) },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const safeContent = computed(() => {
    return DOMPurify.sanitize(props.announcement.content);
});

const deleteAnnouncement = () => {
    if (confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) {
        router.delete(route('admin.announcements.destroy', props.announcement.id));
    }
};
</script>

<template>
    <Head :title="announcement.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col max-w-5xl mx-auto w-full">
            
            <div class="mb-4">
                <Link :href="route('admin.announcements.index')" class="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Announcements
                </Link>
            </div>

            <article class="rounded-xl border border-border bg-background overflow-hidden shadow-sm">
                <!-- Cover Image -->
                <div v-if="announcement.image_path" class="w-full h-64 sm:h-96 relative bg-muted">
                    <img :src="`/storage/${announcement.image_path}`" :alt="announcement.title" class="w-full h-full object-cover" />
                </div>

                <div class="p-6 md:p-10">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-foreground mb-2">{{ announcement.title }}</h1>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                <span class="font-medium text-foreground">By {{ announcement.creator?.name || 'System' }}</span>
                                <span>&bull;</span>
                                <span>{{ formatDate(announcement.created_at) }}</span>
                                <span v-if="!announcement.is_active" class="rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive">
                                    Draft
                                </span>
                            </div>
                        </div>

                        <!-- Admin Actions -->
                        <div v-if="$page.props.auth.can?.edit_announcements" class="flex gap-2 shrink-0">
                            <Link :href="route('admin.announcements.edit', announcement.id)" class="inline-flex items-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground">
                                Edit
                            </Link>
                            <button @click="deleteAnnouncement" class="inline-flex items-center rounded-md bg-destructive px-3 py-1.5 text-sm font-medium text-destructive-foreground hover:bg-destructive/90">
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div 
                        class="prose prose-sm sm:prose-base dark:prose-invert max-w-none prose-img:rounded-xl prose-img:max-h-[500px] prose-a:text-primary"
                        v-html="safeContent"
                    ></div>
                </div>
            </article>

        </div>
    </AppLayout>
</template>

<style>
/* Any additional quill-specific or HTML rendering styles can go here */
.prose img {
    margin-left: auto;
    margin-right: auto;
}
</style>
