<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    BookOpen, Users, DoorOpen, Clock,
    ArrowRight, Plus, AlertTriangle,
    BarChart3, Activity, Eye,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import StatCard from '@/components/dashboard/StatCard.vue';
import DonutBreakdown from '@/components/dashboard/DonutBreakdown.vue';

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
    libraries?: Array<{ id: number; name: string }>;
    selected_library_id?: number | null;
    is_super_admin?: boolean;
}>();

const selectedLibrary = ref<number | null>(props.selected_library_id ?? null);

const switchLibrary = (id: string) => {
    const numId = id === 'all' ? null : Number(id);
    selectedLibrary.value = numId;
    router.get(route('admin.dashboard'), numId ? { library_id: numId } : {}, {
        preserveState: false,
        preserveScroll: true,
    });
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: route('admin.dashboard') }];

// ── Helpers ──────────────────────────────────────────────────────
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
const todayFormatted = now.toLocaleDateString('en-MY', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
});

// ── Library filter (national admin view) ──────────────────────
const selectedLibraryId = ref<string>('all');
const onLibraryChange = (id: string) => {
    router.get(route('admin.dashboard'), { library_id: id === 'all' ? undefined : id }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// ── Bar Chart ────────────────────────────────────────────────────
const chartTab = ref<'loans' | 'bookings'>('loans');
const BAR_W = 520;
const BAR_H = 220;
const PAD = { top: 12, right: 8, bottom: 28, left: 30 };

const loanBars = computed(() => buildBars(
    props.loan_trends.map(d => d.loans), '#795553', 'Issued',
    props.loan_trends.map(d => d.returned), '#cba72f', 'Returned',
    props.loan_trends.map(d => d.month),
));
const bookingBars = computed(() => buildBars(
    props.booking_trends.map(d => d.bookings), '#4a2c2a', 'Total',
    props.booking_trends.map(d => d.confirmed), '#cba72f', 'Confirmed',
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

const maxBookCount = computed(() => Math.max(...props.top_books.map(b => b.count), 1));
const stripHtml = (html: string) => {
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const activityTab = ref<'loans' | 'bookings'>('loans');
const recentLoans = computed(() => props.recent_activities.filter(a => a.type === 'loan'));
const recentBookings = computed(() => props.recent_activities.filter(a => a.type === 'room_booking'));
const activeActivities = computed(() => activityTab.value === 'loans' ? recentLoans.value : recentBookings.value);
</script>

<template>
    <Head title="Admin Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- ── Header ──────────────────────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-border">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground font-medium">{{ todayFormatted }}</p>
                    <h1 class="font-serif text-4xl lg:text-5xl leading-[1.05] tracking-tight text-foreground">
                        {{ greeting }}, <span class="italic text-primary">{{ user.name }}</span><span class="text-[#cba72f] text-6xl leading-none">.</span>
                    </h1>
                    <Select v-if="is_super_admin && libraries?.length" :model-value="selectedLibrary ? String(selectedLibrary) : 'all'" @update:model-value="switchLibrary">
                        <SelectTrigger class="w-56 h-10 rounded-lg">
                            <SelectValue placeholder="All Libraries (National)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Libraries (National)</SelectItem>
                            <SelectItem v-for="lib in libraries" :key="lib.id" :value="String(lib.id)">{{ lib.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.loans.create')">
                        <Button class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            New Loan
                        </Button>
                    </Link>
                    <Link :href="route('admin.room-bookings.create')">
                        <Button variant="outline" class="rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                            <DoorOpen class="h-5 w-5" />
                            Book Room
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ── Statistics Overview ───────────────────────────── -->
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                <StatCard :icon="BookOpen" icon-bg="oklch(0.9 0.03 150)" :value="(stats.total_books as number ?? 0).toLocaleString()"
                    label="Total Books" :sparkline-points="loanSpark" sparkline-color="#795553" />
                <StatCard :icon="Users" icon-bg="oklch(0.86 0.04 145)" :value="(stats.total_members as number ?? 0).toLocaleString()"
                    label="Registered Members"
                    :trend-text="stats.new_members_this_month ? `+${stats.new_members_this_month} this month` : undefined" trend-variant="up" />
                <StatCard :icon="Clock" icon-bg="oklch(0.92 0.04 78)" :value="(stats.active_loans as number ?? 0).toLocaleString()"
                    label="Active Loans"
                    :trend-text="stats.overdue_loans ? `${stats.overdue_loans} overdue` : undefined" trend-variant="warning" />
                <StatCard :icon="DoorOpen" icon-bg="oklch(0.88 0.03 170)" :value="(stats.available_rooms as number ?? 0).toLocaleString()"
                    label="Available Rooms" :sparkline-points="bookingSpark" sparkline-color="#4a2c2a" />
            </div>

            <!-- ── Charts & Breakdown ────────────────────────────── -->
            <div class="grid gap-6 lg:grid-cols-5">
                <Card class="lg:col-span-3">
                    <CardHeader class="pt-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <CardTitle class="font-serif text-xl flex items-center gap-2">
                                    <BarChart3 class="h-4 w-4 text-primary" /> Monthly Circulation
                                </CardTitle>
                                <CardDescription class="mt-0.5">Loans and room bookings — last twelve months</CardDescription>
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
                    <DonutBreakdown title="Loan Breakdown" :segments="[
                        { label: 'Active', value: loan_status_breakdown?.active ?? 0, color: '#795553' },
                        { label: 'Overdue', value: loan_status_breakdown?.overdue ?? 0, color: '#ba1a1a' },
                        { label: 'Returned', value: loan_status_breakdown?.returned ?? 0, color: '#cba72f' },
                    ]" />
                    <DonutBreakdown title="Booking Breakdown" :segments="[
                        { label: 'Confirmed', value: booking_status_breakdown?.confirmed ?? 0, color: '#cba72f' },
                        { label: 'Pending', value: booking_status_breakdown?.pending ?? 0, color: '#735c00' },
                        { label: 'Cancelled', value: booking_status_breakdown?.cancelled ?? 0, color: '#ba1a1a' },
                        { label: 'Completed', value: booking_status_breakdown?.completed ?? 0, color: '#827472' },
                    ]" />
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-7">
                <Card class="lg:col-span-4">
                    <CardHeader class="pt-4 pb-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <CardTitle class="font-serif text-xl flex items-center gap-2">
                                    <Activity class="h-4 w-4 text-primary" /> Recent Activity
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
                                    <div class="h-9 w-9 rounded-xl bg-muted flex items-center justify-center border border-border">
                                        <BookOpen v-if="activity.type === 'loan'" class="h-4 w-4 text-primary" />
                                        <DoorOpen v-else class="h-4 w-4 text-[#4f6073]" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold truncate text-foreground">{{ activity.title }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5"><span class="font-medium">{{ activity.user }}</span> · {{ activity.date }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-[10px] capitalize font-semibold tracking-tight h-5" :class="getStatusVariant(activity.status) === 'destructive' ? 'text-destructive bg-destructive/10 border-destructive/20' : ''">
                                            {{ activity.status }}
                                        </Badge>
                                        <Link :href="activity.type === 'loan' ? route('admin.loans.index') : route('admin.room-bookings.index')">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Eye class="h-4 w-4 text-muted-foreground group-hover:text-primary" />
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                                <div class="h-12 w-12 rounded-full bg-muted flex items-center justify-center mb-3">
                                    <Activity class="h-6 w-6 text-muted-foreground/40" />
                                </div>
                                <p class="text-sm text-muted-foreground font-medium">No recent {{ activityTab }} found.</p>
                                <p class="text-[10px] text-muted-foreground/60 mt-1">Activities from the past 30 days will appear here.</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-border px-1">
                            <Link :href="activityTab === 'loans' ? route('admin.loans.index') : route('admin.room-bookings.index')">
                                <Button variant="outline" size="sm" class="w-full text-xs font-semibold gap-2 rounded-xl h-9 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all">
                                    View All {{ activityTab === 'loans' ? 'Book Loans' : 'Room Bookings' }}
                                    <ArrowRight class="h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <div class="lg:col-span-3 flex flex-col gap-6">
                    <Card>
                        <CardHeader class="pt-4"><CardTitle class="font-serif text-xl">Quick Actions</CardTitle></CardHeader>
                        <CardContent class="grid grid-cols-2 gap-2 pb-5">
                            <Link :href="route('admin.books.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><BookOpen class="h-5 w-5" /><span class="text-xs">Add Book</span></Button></Link>
                            <Link :href="route('admin.loans.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><Clock class="h-5 w-5" /><span class="text-xs">Issue Loan</span></Button></Link>
                            <Link :href="route('admin.rooms.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><DoorOpen class="h-5 w-5" /><span class="text-xs">Create Room</span></Button></Link>
                            <Link :href="route('admin.members.create')" class="contents"><Button variant="outline" class="h-auto flex-col gap-1.5 py-4"><Users class="h-5 w-5" /><span class="text-xs">Register Member</span></Button></Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pt-4"><CardTitle class="font-serif text-xl">Top Borrowed Books</CardTitle></CardHeader>
                        <CardContent class="space-y-3 pb-5">
                            <div v-for="(book, i) in top_books" :key="i" class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate leading-none">{{ book.title }}</p>
                                    <div class="mt-1 h-1 w-full rounded-full bg-muted overflow-hidden">
                                        <div class="h-full bg-primary" :style="{ width: `${(book.count / maxBookCount) * 100}%` }" />
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-primary">{{ book.count }}×</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ── Room Utilization ───────────────────────────────── -->
            <Card>
                <CardHeader class="pt-4"><CardTitle class="font-serif text-xl">Room Utilization</CardTitle></CardHeader>
                <CardContent class="space-y-3 pb-5">
                    <div v-for="room in room_utilization" :key="room.room_number" class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium truncate">{{ room.name }} ({{ room.room_number }})</span>
                                <span class="text-muted-foreground">{{ room.utilization }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                <div class="h-full bg-primary" :style="{ width: `${room.utilization}%` }" />
                            </div>
                        </div>
                    </div>
                    <p v-if="!room_utilization?.length" class="text-sm text-muted-foreground text-center py-4">No room data available.</p>
                </CardContent>
            </Card>

            <!-- ── Announcements ──────────────────────────────────── -->
            <Card v-if="announcements?.length">
                <CardHeader class="pt-4"><CardTitle class="font-serif text-xl">Announcements</CardTitle></CardHeader>
                <CardContent class="space-y-3 pb-5">
                    <div v-for="a in announcements" :key="a.id" class="flex gap-3">
                        <img v-if="a.image_path" :src="a.image_path" class="h-12 w-12 rounded-lg object-cover flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate">{{ a.title }}</p>
                            <p class="text-xs text-muted-foreground line-clamp-2">{{ stripHtml(a.content) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
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
    background: #d4c3c1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #827472;
}
</style>