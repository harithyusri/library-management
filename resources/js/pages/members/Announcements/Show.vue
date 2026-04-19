<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { 
    Calendar, 
    User, 
    ArrowLeft,
    Clock,
    Share2,
    Megaphone
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@vueuse/core';

interface Announcement {
    id: number;
    title: string;
    content: string;
    image_path: string | null;
    created_at: string;
    creator?: {
        name: string;
    };
}

const props = defineProps<{
    announcement: Announcement;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Announcements', href: '/my-announcements' },
    { title: 'Bulletin Detail', href: `/my-announcements/${props.announcement.id}` },
];
</script>

<template>
    <Head :title="announcement.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-8">

            <article class="space-y-10">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                            <div class="flex items-center gap-1.5">
                                <Calendar class="h-4 w-4 text-indigo-500" />
                                {{ new Date(announcement.created_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                            </div>
                            <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                            <div class="flex items-center gap-1.5">
                                <Clock class="h-4 w-4 text-indigo-500" />
                                {{ new Date(announcement.created_at).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' }) }}
                            </div>
                            <div class="flex items-center gap-3">
                                <Badge variant="outline" class="border-indigo-100 bg-indigo-50/50 text-indigo-600 font-black text-[10px] uppercase tracking-wider px-3 py-1">
                                    <Megaphone class="h-3 w-3 mr-1.5" />
                                    Official Notice
                                </Badge>
                            </div>
                        </div>
                        <h1 class="text-4xl md:text-6xl font-black text-slate-900 leading-[1.1] tracking-tight">
                            {{ announcement.title }}<span class="text-indigo-600">.</span>
                        </h1>
                    </div>

                    <!-- Author Info -->
                    <div class="flex items-center gap-4 p-4 w-fit">
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider leading-none mb-1">Posted By</div>
                            <div class="text-sm font-bold text-slate-900 leading-none">{{ announcement.creator?.name || 'Administrator' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div v-if="announcement.image_path" class="relative rounded-[3rem] overflow-hidden border-8 border-white shadow-2xl shadow-slate-200/50 aspect-video">
                    <img 
                        :src="`/storage/${announcement.image_path}`" 
                        :alt="announcement.title"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Content Area -->
                <div class="prose prose-slate prose-lg max-w-none prose-headings:font-black prose-headings:tracking-tight prose-a:text-indigo-600 prose-img:rounded-3xl prose-strong:font-black pb-12">
                    <div v-html="announcement.content"></div>
                </div>
                
                <div class="pt-8 border-t border-slate-100 flex flex-col items-center text-center space-y-4">
                    <div class="h-12 w-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <Share2 class="h-5 w-5" />
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-black text-slate-900">End of Bulletin</h4>
                        <p class="text-xs font-medium text-slate-400">Share this update with fellow members or return to the feed.</p>
                    </div>
                </div>
            </article>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.prose) {
    --tw-prose-body: #475569;
    --tw-prose-headings: #0f172a;
}
</style>
