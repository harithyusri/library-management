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
    XCircle,
    Plus,
    Hammer,
    ArrowRight,
    MessageSquare,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';

const props = defineProps<{
    reports: {
        data: any[];
        links?: Array<{ url: string | null; label: string; active: boolean }>;
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

// Status drives both the badge AND the left-panel icon/tone now, so a
// resolved report reads as calm/done rather than as an active alarm.
const getStatusConfig = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'resolved') {
        return {
            label: 'Resolved',
            icon: CheckCircle2,
            badgeClass: 'bg-emerald-50 text-emerald-600 border-emerald-100',
            panelClass: 'bg-emerald-50/60 group-hover:bg-emerald-50',
            iconWrapClass: 'text-emerald-500',
        };
    }
    if (s === 'rejected') {
        return {
            label: 'Rejected',
            icon: XCircle,
            badgeClass: 'bg-slate-100 text-slate-500 border-slate-200',
            panelClass: 'bg-slate-50 group-hover:bg-slate-100/70',
            iconWrapClass: 'text-slate-400',
        };
    }
    if (s === 'in_progress' || s === 'assigned') {
        return {
            label: s === 'in_progress' ? 'In Progress' : 'Assigned',
            icon: Hammer,
            badgeClass: 'bg-[#c5a059]/10 text-[#8a6a2f] border-[#c5a059]/20',
            panelClass: 'bg-[#c5a059]/5 group-hover:bg-[#c5a059]/10',
            iconWrapClass: 'text-[#c5a059]',
        };
    }
    return {
        label: 'Pending',
        icon: Clock,
        badgeClass: 'bg-amber-50 text-amber-600 border-amber-100',
        panelClass: 'bg-amber-50/60 group-hover:bg-amber-50',
        iconWrapClass: 'text-amber-500',
    };
};

const priorityDot = (priority: string) => {
    const p = priority.toLowerCase();
    if (p === 'high' || p === 'urgent' || p === 'critical') return 'bg-red-500';
    if (p === 'medium') return 'bg-[#c5a059]';
    if (p === 'low') return 'bg-emerald-500';
    return 'bg-slate-400';
};

// ── Status filter tabs ───────────────────────────────────────────
const activeFilter = ref<string>('all');

const filterCounts = computed(() => {
    const counts: Record<string, number> = { all: props.reports.data.length };
    for (const report of props.reports.data) {
        const key = report.status.toLowerCase();
        counts[key] = (counts[key] ?? 0) + 1;
    }
    return counts;
});

const filteredReports = computed(() => {
    if (activeFilter.value === 'all') return props.reports.data;
    return props.reports.data.filter(r => r.status.toLowerCase() === activeFilter.value);
});

const filterTabs = computed(() => [
    { key: 'all', label: 'All' },
    ...Object.entries(props.statuses).map(([key, label]) => ({ key, label })),
]);
</script>

<template>
    <Head title="Maintenance Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <FlashAlert />

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                        <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">Report an Issue</h1>
                        <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">Found something broken? Let us know so we can fix it.</p>
                    </div>
                    <Link :href="route('member.maintenance.create')">
                        <Button class="rounded-full font-bold shrink-0" style="background: var(--ink); color: var(--dust);">
                            <Plus class="h-4 w-4 mr-1.5" /> Report Damage
                        </Button>
                    </Link>
                </div>
            </section>

            <!-- Status Filter Tabs -->
            <div v-if="reports.data.length > 0" class="flex flex-wrap items-center gap-2">
                <button
                    v-for="tab in filterTabs"
                    :key="tab.key"
                    @click="activeFilter = tab.key"
                    class="flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium border transition-all"
                    :class="activeFilter === tab.key
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'bg-card text-muted-foreground border-border hover:text-foreground'"
                >
                    {{ tab.label }}
                    <span class="text-[10px] px-1.5 rounded-full" :class="activeFilter === tab.key ? 'bg-primary-foreground/20' : 'bg-secondary'">
                        {{ filterCounts[tab.key] ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- List Grid -->
            <div v-if="filteredReports.length > 0" class="grid grid-cols-1 gap-4">
                <Card
                    v-for="report in filteredReports"
                    :key="report.id"
                    class="group border-slate-200 overflow-hidden hover:border-[#c5a059]/40 transition-all duration-300 shadow-sm hover:shadow-md rounded-2xl"
                >
                    <CardContent class="p-0">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Visual Panel — reflects the report's actual status -->
                            <div
                                class="w-full lg:w-48 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-slate-100 transition-colors"
                                :class="getStatusConfig(report.status).panelClass"
                            >
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-3 group-hover:scale-110 transition-transform duration-500">
                                    <component
                                        :is="getStatusConfig(report.status).icon"
                                        class="h-8 w-8"
                                        :class="getStatusConfig(report.status).iconWrapClass"
                                    />
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
                                            <Badge
                                                variant="outline"
                                                :class="getStatusConfig(report.status).badgeClass"
                                                class="px-2 py-0 h-5 text-[10px] uppercase font-bold tracking-wider rounded-md border-0"
                                            >
                                                {{ getStatusConfig(report.status).label }}
                                            </Badge>
                                            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                <span class="h-1.5 w-1.5 rounded-full" :class="priorityDot(report.priority)" />
                                                {{ report.priority }} priority
                                            </span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-[#8a6a2f] transition-colors">
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

            <!-- No results for current filter -->
            <div v-else-if="reports.data.length > 0" class="py-16 text-center space-y-3 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-3 rounded-full w-14 h-14 mx-auto flex items-center justify-center border border-border">
                    <Clock class="h-6 w-6 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">No reports with this status.</p>
                <button @click="activeFilter = 'all'" class="text-xs font-bold text-muted-foreground hover:text-foreground uppercase tracking-wider">Clear filter</button>
            </div>

            <!-- True empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <Hammer class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">All clear!</h3>
                    <p class="text-sm text-muted-foreground max-w-sm mx-auto">No maintenance issues reported yet. If you notice anything that needs repair, let us know.</p>
                </div>
                <Link :href="route('member.maintenance.create')">
                    <Button class="rounded-full px-8 font-bold" style="background: var(--ink); color: var(--dust);">Report Damage <ArrowRight class="ml-2 h-4 w-4" /></Button>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="reports.links && reports.links.length > 3" class="flex items-center justify-center gap-1.5 pt-4">
                <template v-for="(link, i) in reports.links" :key="i">
                    <Link v-if="link.url" :href="link.url"
                        class="h-9 min-w-9 flex items-center justify-center rounded-full px-3.5 text-sm font-bold transition-all border"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border text-muted-foreground hover:border-[color:var(--brass)] hover:text-[color:var(--leather)]'"
                        v-html="link.label" />
                    <span v-else class="h-9 min-w-9 flex items-center justify-center rounded-full px-3.5 text-sm font-bold text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>