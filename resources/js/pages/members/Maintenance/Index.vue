<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Calendar, 
    AlertTriangle, 
    Clock, 
    CheckCircle2, 
    Plus,
    Hammer,
    ArrowRight,
    MessageSquare
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    reports: {
        data: any[];
    };
    categories: string[];
    statuses: Record<string, string>;
}>();

const breadcrumbs = [
    { title: 'Report Issue', href: route('member.maintenance.index') },
];

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusConfig = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'resolved') {
        return {
            label: 'Resolved',
            icon: CheckCircle2,
            bgColor: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        };
    }
    if (s === 'rejected') {
        return {
            label: 'Rejected',
            icon: AlertTriangle,
            bgColor: 'bg-red-50 text-red-600 border-red-100',
        };
    }
    if (s === 'in_progress' || s === 'assigned') {
        return {
            label: s === 'in_progress' ? 'In Progress' : 'Assigned',
            icon: Hammer,
            bgColor: 'bg-blue-50 text-blue-600 border-blue-100',
        };
    }
    return {
        label: 'Pending',
        icon: Clock,
        bgColor: 'bg-amber-50 text-amber-600 border-amber-100',
    };
};

const getPriorityColor = (priority: string) => {
    const p = priority.toLowerCase();
    if (p === 'high') return 'text-red-500';
    if (p === 'medium') return 'text-amber-500';
    return 'text-slate-400';
};

</script>

<template>
    <Head title="Maintenance Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-8">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Report Issue <span class="text-red-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Found something broken? Let us know so we can fix it.</p>
                </div>

                <Link :href="route('member.maintenance.create')">
                    <Button class="bg-red-600 hover:bg-red-700 text-white rounded-xl px-6 h-11 font-bold shadow-lg shadow-red-100 dark:shadow-none flex items-center gap-2">
                        <Plus class="h-5 w-5" />
                        Report Damage
                    </Button>
                </Link>
            </div>

            <!-- List Grid -->
            <div v-if="reports.data.length > 0" class="grid grid-cols-1 gap-4">
                <Card v-for="report in reports.data" :key="report.id" class="group border-slate-200 overflow-hidden hover:border-red-200 transition-all duration-300 shadow-sm hover:shadow-md rounded-2xl">
                    <CardContent class="p-0">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Visual Icon -->
                            <div class="w-full lg:w-48 bg-slate-50 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-slate-100 group-hover:bg-red-50/30 transition-colors">
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-3 group-hover:scale-110 transition-transform duration-500">
                                    <AlertTriangle class="h-8 w-8 text-red-500" />
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 text-center">
                                    {{ report.category }}
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="flex-1 p-6 space-y-4">
                                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline" :class="getStatusConfig(report.status).bgColor" class="px-2 py-0 h-5 text-[10px] uppercase font-bold tracking-wider rounded-md border-0">
                                                {{ getStatusConfig(report.status).label }}
                                            </Badge>
                                            <span class="text-[10px] font-bold uppercase tracking-widest pt-0.5" :class="getPriorityColor(report.priority)">
                                                {{ report.priority }} priority
                                            </span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-red-600 transition-colors">
                                            {{ report.title }}
                                        </h3>
                                        <p class="text-sm text-slate-600 font-medium line-clamp-2">
                                            {{ report.description }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm shrink-0">
                                        <div class="space-y-1">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reported On</span>
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                                <Calendar class="h-3.5 w-3.5 text-slate-300" />
                                                {{ formatDate(report.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Feedback -->
                                <div v-if="report.admin_notes" class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex gap-3">
                                    <MessageSquare class="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
                                    <div class="space-y-1">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Librarian Response</span>
                                        <p class="text-xs font-bold text-slate-600">{{ report.admin_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center space-y-6 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200">
                <div class="bg-white p-6 rounded-full w-24 h-24 mx-auto shadow-sm flex items-center justify-center border border-slate-100">
                    <Hammer class="h-10 w-10 text-slate-300" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-2xl font-black text-slate-900">All clear!</h3>
                    <p class="text-slate-500 font-medium max-w-sm mx-auto">
                        No maintenance issues reported by you yet. If you notice anything that needs repair, don't hesitate to let us know.
                    </p>
                </div>
                <div>
                    <Link :href="route('maintenance.create')">
                        <Button class="bg-red-600 hover:bg-red-700 rounded-xl px-10 font-bold shadow-lg shadow-red-200/50">
                            Report Damage
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
