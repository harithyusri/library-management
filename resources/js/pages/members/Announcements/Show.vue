<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Calendar, User, ArrowLeft, Clock, Megaphone } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

interface Announcement {
    id: number;
    title: string;
    content: string;
    image_path: string | null;
    created_at: string;
    creator?: { name: string };
}

const props = defineProps<{ announcement: Announcement }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Announcements', href: '/my-announcements' },
    { title: props.announcement.title, href: '#' },
];
</script>

<template>
    <Head :title="announcement.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">

            <!-- Hero header -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <div class="flex items-center gap-3 mb-4 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-[0.22em] text-muted-foreground">
                        <Calendar class="h-3.5 w-3.5" style="color: var(--brass)" />
                        {{ new Date(announcement.created_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                    </span>
                    <span class="text-muted-foreground">·</span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-[0.22em] text-muted-foreground">
                        <Clock class="h-3.5 w-3.5" style="color: var(--brass)" />
                        {{ new Date(announcement.created_at).toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' }) }}
                    </span>
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background: var(--brass); color: var(--ink); border-color: var(--brass)">
                        <Megaphone class="h-3 w-3 inline mr-1" />Official Notice
                    </span>
                </div>

                <h1 class="font-serif text-3xl lg:text-5xl leading-[1.05] max-w-3xl">
                    {{ announcement.title }}<span style="color: var(--brass)">.</span>
                </h1>

                <div class="mt-4 flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-secondary flex items-center justify-center border border-border">
                        <User class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Posted by</p>
                        <p class="text-sm font-bold">{{ announcement.creator?.name || 'Administrator' }}</p>
                    </div>
                </div>
            </section>

            <!-- Featured image -->
            <div v-if="announcement.image_path" class="mt-8 rounded-xl overflow-hidden border border-border aspect-video">
                <img :src="`/storage/${announcement.image_path}`" :alt="announcement.title" class="w-full h-full object-cover" />
            </div>

            <!-- Content -->
            <div class="mt-8 prose prose-slate prose-lg max-w-none prose-headings:font-serif prose-a:text-[color:var(--leather)] prose-img:rounded-xl prose-strong:font-bold pb-12">
                <div v-html="announcement.content"></div>
            </div>

            <!-- Footer -->
            <div class="mt-12 rounded-xl border border-border bg-card p-6 text-center space-y-3">
                <p class="font-serif italic text-lg">"End of bulletin."</p>
                <p class="text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Share this update with fellow members</p>
                <Link href="/my-announcements" class="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="h-4 w-4" /> Back to all announcements
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.prose) {
    --tw-prose-body: #475569;
    --tw-prose-headings: #0f172a;
}
</style>
