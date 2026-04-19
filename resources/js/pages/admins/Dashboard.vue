<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    BookOpen, Users, Calendar, DoorOpen, Clock,
    ArrowRight, TrendingUp, TrendingDown, Plus,
    BookMarked, AlertTriangle,
    BarChart3, Activity, Percent, Eye,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

// ── Props ────────────────────────────────────────────────────────
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
    loan_trends: Array<{ month: string; loans: number; returned: number }>;
    booking_trends: Array<{ month: string; bookings: number; confirmed: number; cancelled: number }>;
    loan_status_breakdown?: { active: number; overdue: number; returned: number };
    booking_status_breakdown?: { confirmed: number; pending: number; cancelled: number; completed: number };
    top_books: Array<{ title: string; author: string; count: number }>;
    room_utilization: Array<{ name: string; room_number: string; bookings: number; utilization: number }>;
    announcements?: Array<{
        id: number;
        title: string;
        content: string;
        image_path: string | null;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: route('admin.dashboard') }];

// ── Helpers ──────────────────────────────────────────────────────
const getStatusVariant = (status: string): 'default' | 'destructive' | 'secondary' | 'outline' => {
    const s = status.toLowerCase();
    if (s === 'active' || s === 'available' || s === 'confirmed') return 'default';
    if (s === 'overdue' || s === 'cancelled') return 'destructive';
    if (s === 'returned' || s === 'completed') return 'secondary';
    return 'outline';
};

const loanUtilization = computed(() => {
    const totalMembers = props.stats.total_members as number | undefined;
    const activeLoans = props.stats.active_loans as number | undefined;
    if (!totalMembers) return 0;
    return Math.min(100, Math.round(((activeLoans ?? 0) / totalMembers) * 100));
});
const roomUtilizationPct = computed(() => {
    const totalRooms = props.stats.total_rooms as number | undefined;
    const availableRooms = props.stats.available_rooms as number | undefined;
    if (!totalRooms) return 0;
    return Math.round((((totalRooms) - (availableRooms ?? 0)) / totalRooms) * 100);
});

const now = new Date();
const greeting = computed(() => {
    const h = now.getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});
const todayFormatted = now.toLocaleDateString('en-MY', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
});

// ── Bar Chart ────────────────────────────────────────────────────
const chartTab = ref<'loans' | 'bookings'>('loans');
const BAR_W = 520;
const BAR_H = 220;
const PAD = { top: 12, right: 8, bottom: 28, left: 30 };

const loanBars = computed(() => buildBars(
    props.loan_trends.map(d => d.loans), '#3b82f6', 'Issued',
    props.loan_trends.map(d => d.returned), '#10b981', 'Returned',
    props.loan_trends.map(d => d.month),
));
const bookingBars = computed(() => buildBars(
    props.booking_trends.map(d => d.bookings), '#6366f1', 'Total',
    props.booking_trends.map(d => d.confirmed), '#10b981', 'Confirmed',
    props.booking_trends.map(d => d.month),
));
const activeBars = computed(() => chartTab.value === 'loans' ? loanBars.value : bookingBars.value);
const activeMonths = computed(() =>
    chartTab.value === 'loans'
        ? props.loan_trends.map(d => d.month)
        : props.booking_trends.map(d => d.month)
);

function yTicks(allVals: number[], count = 5) {
    const max = Math.max(...allVals, 1);
    const step = Math.ceil(max / count) || 1;
    const topVal = Math.ceil(max / step) * step;
    const innerH = BAR_H - PAD.top - PAD.bottom;
    const ticks = [];
    for (let i = 0; i <= count; i++) {
        const val = i * step;
        if (val > topVal) break;
        const y = PAD.top + innerH - (val / topVal) * innerH;
        ticks.push({ val, y });
    }
    return ticks;
}

function buildBars(primary: number[], pColor: string, pLabel: string, secondary: number[], sColor: string, sLabel: string, months: string[]) {
    const rawMax = Math.max(...primary, ...secondary, 1);
    const step   = Math.ceil(rawMax / 5) || 1;
    const max    = Math.ceil(rawMax / step) * step;
    const innerW = BAR_W - PAD.left - PAD.right;
    const innerH = BAR_H - PAD.top - PAD.bottom;
    const n = primary.length;
    const groupW = innerW / n;
    const barW = Math.max(6, groupW * 0.3);
    const gap = barW * 0.4;

    return primary.map((v, i) => {
        const cx = PAD.left + i * groupW + groupW / 2;
        const x1 = cx - barW - gap / 2;
        const x2 = cx + gap / 2;
        const h1 = (v / max) * innerH;
        const h2 = (secondary[i] / max) * innerH;
        const tooltipX = cx;
        return [
            { x: x1, y: PAD.top + innerH - h1, w: barW, h: h1, color: pColor, val: v, label: pLabel, month: months[i], tooltipX, groupIdx: i },
            { x: x2, y: PAD.top + innerH - h2, w: barW, h: h2, color: sColor, val: secondary[i], label: sLabel, month: months[i], tooltipX, groupIdx: i },
        ];
    });
}
const activeTicks = computed(() =>
    chartTab.value === 'loans'
        ? yTicks([...props.loan_trends.map(d => d.loans), ...props.loan_trends.map(d => d.returned)])
        : yTicks([...props.booking_trends.map(d => d.bookings), ...props.booking_trends.map(d => d.confirmed)])
);

const loanTrend = computed(() => {
    const t = props.loan_trends;
    return t.length < 2 ? 0 : t[t.length - 1].loans - t[t.length - 2].loans;
});
const bookingTrend = computed(() => {
    const t = props.booking_trends;
    return t.length < 2 ? 0 : t[t.length - 1].bookings - t[t.length - 2].bookings;
});
const activeTrend = computed(() => chartTab.value === 'loans' ? loanTrend.value : bookingTrend.value);

// ── Tooltip ──────────────────────────────────────────────────────
interface TooltipState {
    visible: boolean;
    x: number;
    y: number;
    month: string;
    primary: { label: string; val: number; color: string };
    secondary: { label: string; val: number; color: string };
}

const tooltip = ref<TooltipState>({
    visible: false, x: 0, y: 0, month: '',
    primary: { label: '', val: 0, color: '' },
    secondary: { label: '', val: 0, color: '' },
});

function showTooltip(groupIdx: number) {
    const groups = activeBars.value;
    if (groupIdx < 0 || groupIdx >= groups.length) return;
    const [bar1, bar2] = groups[groupIdx];
    const innerW = BAR_W - PAD.left - PAD.right;
    const groupW = innerW / groups.length;
    const cx = PAD.left + groupIdx * groupW + groupW / 2;
    const topY = Math.min(bar1.y, bar2.y) - 8;
    tooltip.value = {
        visible: true,
        x: cx,
        y: Math.max(topY, PAD.top + 4),
        month: bar1.month,
        primary:   { label: bar1.label, val: bar1.val, color: bar1.color },
        secondary: { label: bar2.label, val: bar2.val, color: bar2.color },
    };
}

function hideTooltip() { tooltip.value.visible = false; }
const TT_W = 110;
const TT_H = 62;
const tooltipX = computed(() => {
    const raw = tooltip.value.x - TT_W / 2;
    return Math.max(PAD.left, Math.min(raw, BAR_W - PAD.right - TT_W));
});
const tooltipY = computed(() => Math.max(PAD.top, tooltip.value.y - TT_H - 4));

// ── Sparklines ───────────────────────────────────────────────────
function sparkline(values: number[], w = 72, h = 24): string {
    if (values.length < 2) return '';
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    return values.map((v, i) => {
        const x = (i / (values.length - 1)) * w;
        const y = h - ((v - min) / range) * (h - 4) - 2;
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
}
const loanSpark = computed(() => sparkline(props.loan_trends.map(d => d.loans)));
const bookingSpark = computed(() => sparkline(props.booking_trends.map(d => d.bookings)));

// ── Donut Chart ──────────────────────────────────────────────────
function makeDonut(values: number[], colors: string[]) {
    const total = values.reduce((a, b) => a + b, 0) || 1;
    const r = 38;
    const circ = 2 * Math.PI * r;
    let offset = 0;
    return values.map((v, i) => {
        const dash = (v / total) * circ;
        const seg = { dash, offset, color: colors[i], pct: Math.round((v / total) * 100), value: v };
        offset += dash;
        return seg;
    });
}
const loanDonut = computed(() => makeDonut(
    [props.loan_status_breakdown?.active ?? 0, props.loan_status_breakdown?.overdue ?? 0, props.loan_status_breakdown?.returned ?? 0],
    ['#3b82f6', '#ef4444', '#10b981'],
));
const loanTotal = computed(() =>
    (props.loan_status_breakdown?.active ?? 0) + (props.loan_status_breakdown?.overdue ?? 0) + (props.loan_status_breakdown?.returned ?? 0)
);
const bookingDonut = computed(() => makeDonut(
    [props.booking_status_breakdown?.confirmed ?? 0, props.booking_status_breakdown?.pending ?? 0,
     props.booking_status_breakdown?.cancelled ?? 0, props.booking_status_breakdown?.completed ?? 0],
    ['#10b981', '#f59e0b', '#ef4444', '#9ca3af'],
));
const bookingTotal = computed(() =>
    (props.booking_status_breakdown?.confirmed ?? 0) + (props.booking_status_breakdown?.pending ?? 0) +
    (props.booking_status_breakdown?.cancelled ?? 0) + (props.booking_status_breakdown?.completed ?? 0)
);

const maxBookCount = computed(() => Math.max(...props.top_books.map(b => b.count), 1));
const stripHtml = (html: string) => {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
};

const activityTab = ref<'loans' | 'bookings'>('loans');
const recentLoans = computed(() => props.recent_activities.filter(a => a.type === 'loan'));
const recentBookings = computed(() => props.recent_activities.filter(a => a.type === 'room_booking'));
const activeActivities = computed(() => activityTab.value === 'loans' ? recentLoans.value : recentBookings.value);
</script>

<template>
    <Head title="Admin Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-6">
            <!-- ── Header ──────────────────────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">{{ greeting }}, {{ user.name }} <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">{{ todayFormatted }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.loans.create')">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            New Loan
                        </Button>
                    </Link>
                    <Link :href="route('admin.room-bookings.create')">
                        <Button variant="outline" class="border-slate-200 hover:bg-slate-50 rounded-lg px-4 py-2 text-sm font-bold text-slate-600 flex items-center gap-2">
                            <DoorOpen class="h-5 w-5" />
                            Book Room
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ── Statistics Overview ───────────────────────────── -->
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent dark:from-blue-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5">
                        <div class="flex items-start justify-between mb-2">
                            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/40 p-2">
                                <BookOpen class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <svg width="72" height="24" class="opacity-40 mt-1">
                                <polyline :points="loanSpark" fill="none" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.total_books as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5">Total Books</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent dark:from-emerald-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5">
                        <div class="flex items-start justify-between mb-2">
                            <div class="rounded-lg bg-emerald-100 dark:bg-emerald-900/40 p-2">
                                <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div v-if="stats.new_members_this_month" class="flex items-center gap-0.5 text-xs font-medium text-emerald-600 mt-1">
                                <TrendingUp class="h-3 w-3" />+{{ stats.new_members_this_month }} this month
                            </div>
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.total_members as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5">Registered Members</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent dark:from-amber-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5">
                        <div class="flex items-start justify-between mb-2">
                            <div class="rounded-lg bg-amber-100 dark:bg-amber-900/40 p-2">
                                <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            </div>
                            <span v-if="stats.overdue_loans" class="flex items-center gap-0.5 text-xs font-medium text-destructive mt-1">
                                <AlertTriangle class="h-3 w-3" /> {{ stats.overdue_loans }} overdue
                            </span>
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.active_loans as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5">Active Loans</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent dark:from-indigo-950/20 opacity-60 pointer-events-none" />
                    <CardContent class="pt-5 pb-4 px-5">
                        <div class="flex items-start justify-between mb-2">
                            <div class="rounded-lg bg-indigo-100 dark:bg-indigo-900/40 p-2">
                                <DoorOpen class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <svg width="72" height="24" class="opacity-40 mt-1">
                                <polyline :points="bookingSpark" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold tabular-nums">{{ (stats.available_rooms as number ?? 0).toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-0.5">Available Rooms</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Charts & Breakdown ────────────────────────────── -->
            <div class="grid gap-6 lg:grid-cols-5">
                <Card class="lg:col-span-3">
                    <CardHeader class="pt-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <CardTitle class="text-base flex items-center gap-2">
                                    <BarChart3 class="h-4 w-4 text-muted-foreground" /> Monthly Trends
                                </CardTitle>
                                <CardDescription class="mt-0.5">Library utilization trends</CardDescription>
                            </div>
                            <div class="flex items-center rounded-lg border bg-muted/40 p-0.5 gap-0.5 text-xs">
                                <button
                                    class="px-3 py-1 rounded-md font-medium transition-colors"
                                    :class="chartTab === 'loans' ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                    @click="chartTab = 'loans'; hideTooltip()"
                                >Loans</button>
                                <button
                                    class="px-3 py-1 rounded-md font-medium transition-colors"
                                    :class="chartTab === 'bookings' ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                    @click="chartTab = 'bookings'; hideTooltip()"
                                >Bookings</button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pb-4 px-4">
                        <svg :viewBox="`0 0 ${BAR_W} ${BAR_H}`" class="w-full block" @mouseleave="hideTooltip" :style="{ height: `${BAR_H}px` }">
                            <template v-for="tick in activeTicks" :key="tick.val">
                                <line :x1="PAD.left" :y1="tick.y" :x2="BAR_W - PAD.right" :y2="tick.y" stroke="currentColor" stroke-width="0.5" class="text-border" stroke-dasharray="3,3" />
                                <text :x="PAD.left - 4" :y="tick.y + 3" text-anchor="end" font-size="8" class="fill-muted-foreground">{{ tick.val }}</text>
                            </template>
                            <line :x1="PAD.left" :y1="BAR_H - PAD.bottom" :x2="BAR_W - PAD.right" :y2="BAR_H - PAD.bottom" stroke="currentColor" stroke-width="0.75" class="text-border" />
                            <template v-for="(group, gi) in activeBars" :key="`gi-${gi}`">
                                <rect v-for="(bar, bi) in group" :key="bi" :x="bar.x" :y="bar.y" :width="bar.w" :height="bar.h" :fill="bar.color" rx="2" @mouseenter="showTooltip(gi)" class="opacity-80 hover:opacity-100 transition-opacity cursor-pointer" />
                            </template>
                            <template v-for="(month, mi) in activeMonths" :key="`mi-${mi}`">
                                <text :x="PAD.left + (mi + 0.5) * ((BAR_W - PAD.left - PAD.right) / activeMonths.length)" :y="BAR_H - 7" text-anchor="middle" font-size="8" class="fill-muted-foreground">{{ month }}</text>
                            </template>
                            <g v-if="tooltip.visible">
                                <rect :x="tooltipX" :y="tooltipY" :width="TT_W" :height="TT_H" rx="4" fill="white" stroke="#e5e7eb" class="dark:fill-zinc-900 dark:stroke-zinc-700 shadow-xl" />
                                <text :x="tooltipX + TT_W/2" :y="tooltipY + 12" text-anchor="middle" font-size="9" font-weight="700" class="fill-foreground">{{ tooltip.month }}</text>
                                <text :x="tooltipX + 8" :y="tooltipY + 30" font-size="8.5" class="fill-muted-foreground">{{ tooltip.primary.label }}: {{ tooltip.primary.val }}</text>
                                <text :x="tooltipX + 8" :y="tooltipY + 45" font-size="8.5" class="fill-muted-foreground">{{ tooltip.secondary.label }}: {{ tooltip.secondary.val }}</text>
                            </g>
                        </svg>
                    </CardContent>
                </Card>

                <div class="lg:col-span-2 flex flex-col gap-6">
                    <Card>
                        <CardHeader class="pb-2 pt-4"><CardTitle class="text-sm">Loan Breakdown</CardTitle></CardHeader>
                        <CardContent class="flex items-center gap-4 pb-5">
                            <svg width="64" height="64" viewBox="0 0 96 96">
                                <circle v-for="seg in loanDonut" :key="seg.color" cx="48" cy="48" r="38" fill="none" :stroke="seg.color" stroke-width="12" :stroke-dasharray="`${seg.dash} ${2 * Math.PI * 38 - seg.dash}`" :stroke-dashoffset="-seg.offset" transform="rotate(-90 48 48)" />
                            </svg>
                            <div class="flex flex-col gap-1 text-xs">
                                <div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full bg-blue-500" />Active: {{ loan_status_breakdown?.active }}</div>
                                <div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full bg-red-500" />Overdue: {{ loan_status_breakdown?.overdue }}</div>
                                <div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full bg-emerald-500" />Returned: {{ loan_status_breakdown?.returned }}</div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2 pt-4"><CardTitle class="text-sm">Booking Breakdown</CardTitle></CardHeader>
                        <CardContent class="flex items-center gap-4 pb-5">
                            <svg width="64" height="64" viewBox="0 0 96 96">
                                <circle v-for="seg in bookingDonut" :key="seg.color" cx="48" cy="48" r="38" fill="none" :stroke="seg.color" stroke-width="12" :stroke-dasharray="`${seg.dash} ${2 * Math.PI * 38 - seg.dash}`" :stroke-dashoffset="-seg.offset" transform="rotate(-90 48 48)" />
                            </svg>
                            <div class="flex flex-col gap-1 text-xs">
                                <div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full bg-emerald-500" />Confirmed: {{ booking_status_breakdown?.confirmed }}</div>
                                <div class="flex items-center gap-2"><div class="h-2 w-2 rounded-full bg-amber-500" />Pending: {{ booking_status_breakdown?.pending }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-7">
                <Card class="lg:col-span-4">
                    <CardHeader class="pt-4 pb-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <CardTitle class="text-base flex items-center gap-2">
                                    <Activity class="h-4 w-4 text-muted-foreground" /> Recent Activity
                                </CardTitle>
                                <CardDescription class="mt-0.5">Track system events and records</CardDescription>
                            </div>
                            <div class="flex items-center rounded-lg border bg-muted/40 p-0.5 gap-0.5 text-xs">
                                <button
                                    class="px-3 py-1 rounded-md font-medium transition-colors"
                                    :class="activityTab === 'loans' ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                    @click="activityTab = 'loans'"
                                >Book Loans</button>
                                <button
                                    class="px-3 py-1 rounded-md font-medium transition-colors"
                                    :class="activityTab === 'bookings' ? 'bg-background shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                    @click="activityTab = 'bookings'"
                                >Room Bookings</button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="px-3 pb-6 flex flex-col h-[270px]">
                        <div class="flex-1 overflow-y-auto pr-1 custom-scrollbar">
                            <div v-if="activeActivities.length > 0" class="space-y-1">
                                <div v-for="activity in activeActivities" :key="activity.id" class="group flex items-center gap-3 px-3 py-3 hover:bg-muted/50 rounded-lg transition-colors">
                                    <div class="h-9 w-9 rounded-xl bg-muted flex items-center justify-center border border-slate-100 dark:border-slate-800">
                                        <BookOpen v-if="activity.type === 'loan'" class="h-4 w-4 text-blue-500" />
                                        <DoorOpen v-else class="h-4 w-4 text-indigo-500" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold truncate text-slate-900 dark:text-slate-100">{{ activity.title }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5"><span class="font-medium text-slate-500">{{ activity.user }}</span> · {{ activity.date }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-[10px] capitalize font-semibold tracking-tight h-5" :class="getStatusVariant(activity.status) === 'destructive' ? 'text-red-600 bg-red-50 border-red-100' : ''">
                                            {{ activity.status }}
                                        </Badge>
                                        <Link :href="activity.type === 'loan' ? route('admin.loans.index') : route('admin.room-bookings.index')">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Eye class="h-4 w-4 text-slate-400 group-hover:text-indigo-600" />
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                                <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                    <Activity class="h-6 w-6 text-slate-300" />
                                </div>
                                <p class="text-sm text-slate-500 font-medium">No recent {{ activityTab }} found.</p>
                                <p class="text-[10px] text-slate-400 mt-1">Activities from the past 30 days will appear here.</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-50 dark:border-slate-800/50 px-1">
                            <Link :href="activityTab === 'loans' ? route('admin.loans.index') : route('admin.room-bookings.index')">
                                <Button variant="outline" size="sm" class="w-full text-xs font-semibold gap-2 rounded-xl h-9 border-slate-200 text-slate-600 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50/50 transition-all">
                                    View All {{ activityTab === 'loans' ? 'Book Loans' : 'Room Bookings' }} 
                                    <ArrowRight class="h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <div class="lg:col-span-3 flex flex-col gap-6">
                    <Card>
                        <CardHeader class="pt-4"><CardTitle class="text-base">Quick Actions</CardTitle></CardHeader>
                        <CardContent class="grid grid-cols-2 gap-2 pb-5">
                            <Link :href="route('admin.books.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><BookOpen class="h-5 w-5" /><span class="text-xs">Add Book</span></Button></Link>
                            <Link :href="route('admin.loans.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><Clock class="h-5 w-5" /><span class="text-xs">Issue Loan</span></Button></Link>
                            <Link :href="route('admin.rooms.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><DoorOpen class="h-5 w-5" /><span class="text-xs">Create Room</span></Button></Link>
                            <Link :href="route('admin.members.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><Users class="h-5 w-5" /><span class="text-xs">Register Member</span></Button></Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pt-4"><CardTitle class="text-base">Top Borrowed Books</CardTitle></CardHeader>
                        <CardContent class="space-y-3 pb-5">
                            <div v-for="(book, i) in top_books" :key="i" class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate leading-none">{{ book.title }}</p>
                                    <div class="mt-1 h-1 w-full rounded-full bg-muted overflow-hidden">
                                        <div class="h-full bg-blue-500" :style="{ width: `${(book.count / maxBookCount) * 100}%` }" />
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-blue-600">{{ book.count }}×</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
