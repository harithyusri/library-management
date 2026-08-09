<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Megaphone, Calendar, User, ChevronRight, Newspaper } from 'lucide-vue-next';
import { formatDate } from '@vueuse/core';

interface Announcement {
    id: number;
    title: string;
    content: string;
    image_path: string | null;
    created_at: string;
    creator?: { name: string };
}

defineProps<{ announcements: { data: Announcement[]; links: any[] } }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Announcements', href: '/my-announcements' }];

const stripHtml = (html: string) => {
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const getSnippet = (content: string) => {
    const text = stripHtml(content);
    return text.length > 140 ? text.substring(0, 140) + '…' : text;
};
</script>

<template>
    <Head title="Announcements" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-4">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">
                    Library News &
                    <span class="italic" style="color: var(--leather)"> Notices.</span>
                </h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">
                    Stay updated with the latest happenings and important notices from the library.
                </p>
            </section>

            <!-- Shelf heading -->
            <div class="flex items-end justify-between gap-4 py-4">
                <h2 class="font-serif text-2xl">Latest bulletins</h2>
                <span class="text-xs text-muted-foreground">{{ announcements.data.length }} active</span>
            </div>

            <!-- Grid -->
            <div v-if="announcements.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <article
                    v-for="announcement in announcements.data"
                    :key="announcement.id"
                    class="group card-book overflow-hidden rounded-xl border border-border transition-all duration-300 hover:-translate-y-0.5 flex flex-col"
                >
                    <!-- Spine accent -->
                    <span class="absolute left-0 top-0 h-full w-1.5" style="background: var(--leather)" />

                    <!-- Image -->
                    <div class="relative h-44 w-full overflow-hidden bg-secondary">
                        <img v-if="announcement.image_path" :src="`/storage/${announcement.image_path}`" :alt="announcement.title"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" />
                        <div v-else class="h-full w-full flex flex-col items-center justify-center text-muted-foreground">
                            <Newspaper class="h-12 w-12 opacity-20 mb-1" />
                            <span class="text-[10px] font-bold uppercase tracking-widest opacity-40">Library Bulletin</span>
                        </div>
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-card/90 backdrop-blur px-2.5 py-1 text-[11px] font-bold border border-border">
                                <Calendar class="h-3 w-3" style="color: var(--brass)" />
                                {{ formatDate(new Date(announcement.created_at), 'MMM dd') }}
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 p-5 pl-7 space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="h-0.5 w-6 rounded-full" style="background: var(--brass)"></div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-muted-foreground">Notice</span>
                        </div>
                        <h3 class="font-serif text-lg leading-snug line-clamp-2 group-hover:text-[color:var(--leather)] transition-colors">
                            {{ announcement.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground leading-relaxed line-clamp-3">{{ getSnippet(announcement.content) }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 pl-7 pb-5 pt-3 border-t border-border flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-full bg-secondary flex items-center justify-center border border-border">
                                <User class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            <span class="text-xs font-medium text-muted-foreground">{{ announcement.creator?.name || 'Librarian' }}</span>
                        </div>
                        <Link :href="`/my-announcements/${announcement.id}`"
                            class="h-8 w-8 rounded-full bg-secondary flex items-center justify-center border border-border hover:bg-primary hover:text-primary-foreground hover:border-primary transition-all">
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <Megaphone class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">Silent in the stacks…</h3>
                    <p class="text-sm text-muted-foreground">No active announcements at the moment. Check back later.</p>
                </div>
            </div>

            <!-- Quote -->
            <div class="mt-12 rounded-xl border border-border bg-card p-6 text-center">
                <p class="font-serif italic text-lg">"The more that you read, the more things you will know."</p>
                <p class="mt-2 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Dr. Seuss</p>
            </div>
        </div>
    </AppLayout>
</template>
