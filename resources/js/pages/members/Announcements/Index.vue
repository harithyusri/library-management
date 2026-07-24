<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { 
    Megaphone, 
    Calendar, 
    User, 
    ChevronRight, 
    Clock,
    Search,
    Newspaper,
    FilterX
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Card, 
    CardContent, 
    CardDescription, 
    CardFooter, 
    CardHeader, 
    CardTitle 
} from '@/components/ui/card';
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
    announcements: {
        data: Announcement[];
        links: any[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Announcements',
        href: '/my-announcements',
    },
];

const stripHtml = (html: string) => {
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const getSnippet = (content: string) => {
    const text = stripHtml(content);
    return text.length > 150 ? text.substring(0, 150) + '...' : text;
};
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">
                        Library News <span class="text-indigo-600 text-6xl leading-none">.</span>
                    </h1>
                    <p class="text-slate-500 font-medium">Stay updated with the latest happenings and important notices.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-50 px-4 py-2 rounded-xl flex items-center gap-2 border border-indigo-100">
                        <Megaphone class="h-4 w-4 text-indigo-600" />
                        <span class="text-xs font-black text-indigo-900 uppercase tracking-wider">
                            {{ announcements.data.length }} Active Updates
                        </span>
                    </div>
                </div>
            </div>

            <!-- Announcements Grid -->
            <div v-if="announcements.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <Card 
                    v-for="announcement in announcements.data" 
                    :key="announcement.id"
                    class="group border-0 shadow-sm hover:shadow-2xl hover:shadow-indigo-100/50 transition-all duration-500 rounded-[2.5rem] overflow-hidden bg-white flex flex-col"
                >
                    <!-- Image Wrapper -->
                    <div class="relative h-56 w-full overflow-hidden bg-slate-100">
                        <img 
                            v-if="announcement.image_path"
                            :src="`/storage/${announcement.image_path}`"
                            :alt="announcement.title"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                        />
                        <div v-else class="h-full w-full flex flex-col items-center justify-center text-slate-300">
                            <Newspaper class="h-16 w-16 mb-2 opacity-20" />
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-40">LMS Bulletin</span>
                        </div>
                        
                        <!-- Overlay Badges -->
                        <div class="absolute top-4 left-4 flex gap-2">
                            <Badge class="bg-white/90 backdrop-blur-md text-indigo-600 border-0 shadow-lg font-black text-[10px] uppercase tracking-wider py-1.5 px-3">
                                <Clock class="h-3 w-3 mr-1.5" />
                                {{ formatDate(new Date(announcement.created_at), 'MMM dd') }}
                            </Badge>
                        </div>
                    </div>

                    <CardHeader class="pt-8 pb-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-1 w-8 bg-indigo-600 rounded-full"></div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-[0.2em]">Important Notice</span>
                        </div>
                        <CardTitle class="text-xl font-black text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-tight">
                            {{ announcement.title }}
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="flex-grow">
                        <p class="text-slate-500 text-sm leading-relaxed line-clamp-3 font-medium">
                            {{ getSnippet(announcement.content) }}
                        </p>
                    </CardContent>

                    <CardFooter class="pt-4 pb-8 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                           <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200">
                               <User class="h-4 w-4 text-slate-400" />
                           </div>
                           <div class="flex flex-col">
                               <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter leading-none mb-0.5">Posted By</span>
                               <span class="text-[10px] font-bold text-slate-700 leading-none">{{ announcement.creator?.name || 'Librarian' }}</span>
                           </div>
                        </div>

                        <Link 
                            :href="`/my-announcements/${announcement.id}`"
                            class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm hover:shadow-lg group/btn"
                        >
                            <ChevronRight class="h-5 w-5 transition-transform group-hover/btn:translate-x-0.5" />
                        </Link>
                    </CardFooter>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="min-h-[50vh] flex flex-col items-center justify-center text-center p-12 bg-slate-50 rounded-[3rem] border border-dashed border-slate-200">
                <div class="h-24 w-24 rounded-full bg-white shadow-xl flex items-center justify-center mb-6">
                    <Megaphone class="h-10 w-10 text-slate-200" />
                </div>
                <h3 class="text-2xl font-black text-slate-900 mb-2">Silent in the stacks...</h3>
                <p class="text-slate-500 max-w-sm font-medium">There are no active announcements at the moment. Check back later for updates!</p>
            </div>
        </div>
    </AppLayout>
</template>
