<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    BookOpen, Calendar, DoorOpen, Clock,
    ArrowRight, AlertTriangle, CheckCircle2, Banknote,
    ArrowLeft
} from 'lucide-vue-next';

const props = defineProps<{
    user: { name: string };
    stats: Record<string, number | string | null | undefined>;
    recent_activities: Array<{
        id: number;
        type: 'loan' | 'room_booking';
        title: string;
        user: string;
        date: string;
        status: string;
    }>;
    announcements?: Array<{
        id: number;
        title: string;
        content: string;
        image_path: string | null;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/member/dashboard' }];

/* =========================
   Slider Logic
========================= */
const activeSlide = ref(0);
let slideInterval: ReturnType<typeof setInterval> | null = null;

const nextSlide = () => {
    if (!props.announcements || props.announcements.length <= 1) return;
    activeSlide.value = (activeSlide.value + 1) % props.announcements.length;
};

const prevSlide = () => {
    if (!props.announcements || props.announcements.length <= 1) return;
    activeSlide.value = (activeSlide.value - 1 + props.announcements.length) % props.announcements.length;
};

const startAutoSlide = () => {
    if (props.announcements && props.announcements.length > 1) {
        slideInterval = setInterval(nextSlide, 5000);
    }
};

const stopAutoSlide = () => {
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = null;
    }
};

onMounted(() => {
    startAutoSlide();
});

onUnmounted(() => {
    stopAutoSlide();
});

const getStatusVariant = (status: string): 'default' | 'destructive' | 'secondary' | 'outline' => {
    const s = status.toLowerCase();
    if (s === 'active' || s === 'available' || s === 'confirmed') return 'default';
    if (s === 'overdue' || s === 'cancelled') return 'destructive';
    if (s === 'returned' || s === 'completed') return 'secondary';
    return 'outline';
};

const now = new Date();
const greeting = computed(() => {
    const h = now.getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

const nextDueDate = computed(() => {
    const d = props.stats.next_due_date as string | null | undefined;
    if (!d) return null;
    return new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' });
});

const stripHtml = (html: string) => {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
};
</script>

<template>
    <Head title="My Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-8">

            <!-- ── Welcome Header ──────────────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900 leading-tight">
                        {{ greeting }}, {{ user.name }} <span class="text-indigo-600 text-5xl md:text-6xl leading-none">.</span>
                    </h1>
                    <p class="text-slate-500 font-medium text-sm md:text-base">Welcome back to your library dashboard.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <Link href="/member/catalog" class="w-full sm:w-auto">
                        <Button class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 h-11 font-bold shadow-lg shadow-indigo-100 dark:shadow-none items-center gap-2">
                            <BookOpen class="h-5 w-5" />
                            Browse Catalog
                        </Button>
                    </Link>
                    <Link href="/member/room-bookings/create" class="w-full sm:w-auto">
                        <Button variant="outline" class="w-full sm:w-auto border-slate-200 hover:bg-slate-50 rounded-xl px-6 h-11 font-bold text-slate-600 flex items-center gap-2">
                            <DoorOpen class="h-5 w-5" />
                            Book a Room
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ── Announcements Hero Slider ─────────────────────────── -->
            <div v-if="announcements && announcements.length > 0" class="relative group">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                         <div class="h-8 w-1 bg-indigo-600 rounded-full"></div>
                        <h2 class="text-xl font-black tracking-tight text-slate-900">Latest Announcements</h2>
                    </div>
                    <Link href="/member/announcements">
                        <Button variant="link" size="sm" class="gap-1.5 font-bold text-indigo-600 hover:text-indigo-800 p-0">
                            View All <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>

                <!-- Slider Container -->
                <div class="relative overflow-hidden rounded-3xl md:rounded-[2rem] bg-slate-900 shadow-2xl shadow-indigo-200/50">
                    <div 
                        class="flex transition-transform duration-1000 cubic-bezier(0.4, 0, 0.2, 1)"
                        :style="{ transform: `translateX(-${activeSlide * 100}%)` }"
                    >
                        <div 
                            v-for="announcement in announcements" 
                            :key="announcement.id"
                            class="w-full shrink-0 relative aspect-[4/5] sm:aspect-video md:aspect-[25/7] overflow-hidden"
                        >
                            <!-- Background Image with Overlay -->
                            <div class="absolute inset-0">
                                <img 
                                    v-if="announcement.image_path" 
                                    :src="`/storage/${announcement.image_path}`" 
                                    class="h-full w-full object-cover opacity-60 scale-105 group-hover:scale-110 transition-transform duration-1000" 
                                />
                                <div v-else class="h-full w-full bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 opacity-100"></div>
                                <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent"></div>
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-transparent to-transparent md:hidden"></div>
                            </div>

                            <!-- Content -->
                            <div class="relative h-full flex flex-col justify-end md:justify-center px-6 md:px-16 pb-12 md:pb-0 max-w-2xl space-y-3 md:space-y-4">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <Badge class="bg-indigo-500 hover:bg-indigo-600 border-0 text-[9px] md:text-[10px] font-black uppercase tracking-widest px-2 md:px-3 py-0.5 md:py-1">Important</Badge>
                                    <span class="text-[10px] md:text-xs font-bold text-slate-400 font-mono">{{ new Date(announcement.created_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span>
                                </div>
                                <h3 class="text-xl md:text-4xl font-black text-white leading-tight tracking-tight line-clamp-3 md:line-clamp-2">
                                    {{ announcement.title }}
                                </h3>
                                <p class="text-slate-300 text-xs md:text-base line-clamp-3 md:line-clamp-2 max-w-xl font-medium leading-relaxed">
                                    {{ stripHtml(announcement.content) }}
                                </p>
                                <div class="pt-2 md:pt-4">
                                     <Link :href="`/member/announcements/${announcement.id}`">
                                        <Button class="bg-white hover:bg-indigo-50 text-slate-900 rounded-xl px-6 md:px-8 h-10 md:h-12 text-sm md:text-base font-black transition-all active:scale-95">
                                            Read More
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Dots -->
                    <div v-if="announcements.length > 1" class="absolute bottom-4 md:bottom-6 right-6 md:right-8 flex items-center gap-2 md:gap-3 bg-white/10 backdrop-blur-md px-3 md:px-4 py-1.5 md:py-2 rounded-full">
                        <button 
                            v-for="(_, index) in announcements" 
                            :key="index"
                            @click="activeSlide = index"
                            class="h-1 md:h-1.5 transition-all duration-300 rounded-full"
                            :class="activeSlide === index ? 'w-6 md:w-8 bg-white' : 'w-1.5 md:w-2 bg-white/30 cursor-pointer hover:bg-white/50'"
                        ></button>
                    </div>

                    <!-- Navigation Arrows (Desktop Only) -->
                    <button 
                        v-if="announcements.length > 1"
                        @click="prevSlide"
                        class="absolute left-4 top-1/2 -translate-y-1/2 h-10 w-10 hidden md:flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md text-white transition-all opacity-0 group-hover:opacity-100"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </button>
                    <button 
                        v-if="announcements.length > 1"
                        @click="nextSlide"
                        class="absolute right-4 top-1/2 -translate-y-1/2 h-10 w-10 hidden md:flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md text-white transition-all opacity-0 group-hover:opacity-100"
                    >
                        <ArrowRight class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <!-- ── Personal Stats ──────────────────────────────── -->
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-5">
                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent dark:from-blue-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5 text-center sm:text-left">
                        <div class="rounded-lg bg-blue-100 dark:bg-blue-900/40 p-2 mb-3 w-fit mx-auto sm:mx-0">
                            <Clock class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.active_loans as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5 font-medium">Active Loans</p>
                        <p v-if="nextDueDate" class="text-xs text-blue-600 dark:text-blue-400 mt-1.5 font-bold">Due: {{ nextDueDate }}</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md border-red-100 dark:border-red-950" v-if="(stats.overdue_loans as number) > 0">
                    <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-transparent dark:from-red-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5 text-center sm:text-left">
                        <div class="rounded-lg bg-red-100 dark:bg-red-900/40 p-2 mb-3 w-fit mx-auto sm:mx-0">
                            <AlertTriangle class="h-4 w-4 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="text-2xl font-bold tabular-nums text-red-600">{{ (stats.overdue_loans as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-red-600/70 mt-0.5 font-bold uppercase tracking-wider">Overdue Items</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md" v-if="(stats.total_fines as number) > 0">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-transparent dark:from-orange-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5 text-center sm:text-left">
                        <div class="rounded-lg bg-orange-100 dark:bg-orange-900/40 p-2 mb-3 w-fit mx-auto sm:mx-0">
                            <Banknote class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div class="text-2xl font-bold tabular-nums">RM {{ (stats.total_fines as number ?? 0).toFixed(2) }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5 font-medium">Unpaid Fines</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent dark:from-emerald-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5 text-center sm:text-left">
                        <div class="rounded-lg bg-emerald-100 dark:bg-emerald-900/40 p-2 mb-3 w-fit mx-auto sm:mx-0">
                            <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.completed_loans as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5 font-medium">Books Read</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent dark:from-indigo-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5 text-center sm:text-left">
                        <div class="rounded-lg bg-indigo-100 dark:bg-indigo-900/40 p-2 mb-3 w-fit mx-auto sm:mx-0">
                            <DoorOpen class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.available_rooms as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5 font-medium">Available Rooms</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Recent Activity ────────────────────────────── -->
            <div class="grid gap-6">
                <Card class="border-none shadow-none bg-transparent hover:shadow-none">
                    <CardHeader class="px-0 pt-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-xl font-bold">Recent Activities</CardTitle>
                                <CardDescription class="mt-1">Tracking your loans and bookings.</CardDescription>
                            </div>
                            <Link href="/member/loans">
                                <Button variant="ghost" size="sm" class="gap-1.5 font-bold text-blue-600 hover:bg-blue-50">View All Records <ArrowRight class="h-4 w-4" /></Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent class="px-0">
                        <div v-if="recent_activities.length === 0" class="py-12 flex flex-col items-center justify-center gap-2 text-muted-foreground bg-muted/20 rounded-2xl border-2 border-dashed border-muted">
                            <BookOpen class="h-10 w-10 opacity-20" />
                            <p class="font-medium">No recent activity yet.</p>
                        </div>
                        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="activity in recent_activities" :key="`${activity.type}-${activity.id}`" class="overflow-hidden group hover:shadow-md transition-all duration-300">
                                <CardContent class="p-4 flex gap-4">
                                    <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-xl"
                                        :class="activity.type === 'loan' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600' : 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600'">
                                        <BookOpen v-if="activity.type === 'loan'" class="h-5 w-5" />
                                        <Calendar v-else class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                                        <p class="text-sm font-bold truncate group-hover:text-blue-600 transition-colors">{{ activity.title }}</p>
                                        <p class="text-[11px] text-muted-foreground font-medium mt-1">{{ activity.date }} · <span class="capitalize">{{ activity.type.replace('_', ' ') }}</span></p>
                                    </div>
                                    <div class="flex items-center">
                                        <Badge :variant="getStatusVariant(activity.status)" class="capitalize text-[10px] h-5 px-1.5 font-bold">
                                            {{ activity.status }}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
