<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItemType } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { FileSpreadsheet, Download, RefreshCcw, Loader2, Calendar as CalendarIcon } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, type Ref } from 'vue';
import { store, status, download } from '@/routes/admin/reports';
import type { DateValue } from '@internationalized/date';
import { fromDate, getLocalTimeZone } from '@internationalized/date';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps<{
    reports: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    reportType: string;
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    {
        title: 'Room Reservation Reports',
        href: '/room-reservation-reports',
    },
];

const form = useForm({
    type: 'room_reservation',
    start_date: '',
    end_date: '',
});

// Date picker state
const startDate = ref() as Ref<DateValue | undefined>;
const endDate = ref() as Ref<DateValue | undefined>;
const showStartCalendar = ref(false);
const showEndCalendar = ref(false);

// Format date for display
const formatDateDisplay = (dateValue: DateValue | undefined): string => {
    if (!dateValue) return 'Pick a date';
    return new Date(dateValue.year, dateValue.month - 1, dateValue.day).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Convert DateValue to YYYY-MM-DD string
const dateValueToString = (dateValue: DateValue): string => {
    return `${dateValue.year}-${String(dateValue.month).padStart(2, '0')}-${String(dateValue.day).padStart(2, '0')}`;
};

// Handlers
const handleStartDateChange = (date: DateValue | undefined) => {
    if (date) {
        startDate.value = date;
        form.start_date = dateValueToString(date);
        showStartCalendar.value = false;
    }
};

const handleEndDateChange = (date: DateValue | undefined) => {
    if (date) {
        endDate.value = date;
        form.end_date = dateValueToString(date);
        showEndCalendar.value = false;
    }
};

const generateReport = () => {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            props.reports.data.forEach((report: any) => {
                if (report.status === 'pending' || report.status === 'processing') {
                    startPolling(report.id);
                }
            });
        }
    });
};

const parsedFilters = (filters: any): Record<string, string> | null => {
    try {
        return typeof filters === 'string' ? JSON.parse(filters) : filters;
    } catch {
        return null;
    }
};

const formatFilterDate = (dateStr: string): string => {
    if (!dateStr) return '—';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const pollingIntervals = ref<Record<number, any>>({});

const startPolling = (reportId: number) => {
    if (pollingIntervals.value[reportId]) return;

    pollingIntervals.value[reportId] = setInterval(async () => {
        try {
            const response = await fetch(status.url(reportId));
            const data = await response.json();

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(pollingIntervals.value[reportId]);
                delete pollingIntervals.value[reportId];
                router.reload({ only: ['reports'] });
            }
        } catch (error) {
            console.error('Error polling report status:', error);
            if (pollingIntervals.value[reportId]) {
                clearInterval(pollingIntervals.value[reportId]);
                delete pollingIntervals.value[reportId];
            }
        }
    }, 3000);
};

onMounted(() => {
    props.reports.data.forEach((report: any) => {
        if (report.status === 'pending' || report.status === 'processing') {
            startPolling(report.id);
        }
    });
});

onUnmounted(() => {
    Object.values(pollingIntervals.value).forEach(interval => clearInterval(interval));
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'completed': return { variant: 'default', label: 'Completed', class: 'bg-green-500 hover:bg-green-600' };
        case 'failed': return { variant: 'destructive', label: 'Failed' };
        case 'processing': return { variant: 'secondary', label: 'Processing' };
        case 'pending': return { variant: 'outline', label: 'Pending' };
        default: return { variant: 'default', label: status };
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Room Reservation Reports" />

        <div class="px-6 pt-2 pb-8 space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Room Usage Reports <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Generate and download detailed reports of room bookings and usage.</p>
                </div>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-4">
                <div class="grid w-full max-w-xs items-center gap-1.5">
                    <Label for="start_date">Start Date</Label>
                    <Popover v-model:open="showStartCalendar">
                        <PopoverTrigger as-child>
                            <Button variant="outline" :class="cn(
                                'w-full justify-start text-left font-normal mt-0.5',
                                !startDate && 'text-muted-foreground'
                            )">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ formatDateDisplay(startDate) }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="startDate" @update:model-value="handleStartDateChange" />
                        </PopoverContent>
                    </Popover>
                </div>
                <div class="grid w-full max-w-xs items-center gap-1.5">
                    <Label for="end_date">End Date</Label>
                    <Popover v-model:open="showEndCalendar">
                        <PopoverTrigger as-child>
                            <Button variant="outline" :class="cn(
                                'w-full justify-start text-left font-normal mt-0.5',
                                !endDate && 'text-muted-foreground'
                            )">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ formatDateDisplay(endDate) }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="endDate" @update:model-value="handleEndDateChange" />
                        </PopoverContent>
                    </Popover>
                </div>
                <Button @click="generateReport" :disabled="form.processing" size="lg" class="gap-2">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <FileSpreadsheet v-else class="h-4 w-4" />
                    Generate New Report
                </Button>
            </div>

            <Card class="py-5">
                <CardHeader>
                    <CardTitle>Recent Reports</CardTitle>
                    <CardDescription>
                        Reports are generated in the background. You can download them once they are completed.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Report Name</TableHead>
                                    <TableHead>Requested Date</TableHead>
                                    <TableHead>Generated By</TableHead>
                                    <TableHead>Filters</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="report in reports.data" :key="report.id">
                                    <TableCell>
                                        <span v-if="report.file_name" class="font-medium text-blue-600 dark:text-blue-400">
                                            {{ report.file_name }}
                                        </span>
                                        <span v-else class="text-muted-foreground italic flex items-center gap-1.5">
                                            <Loader2 class="h-3 w-3 animate-spin" v-if="report.status === 'processing' || report.status === 'pending'" />
                                            Pending generation...
                                        </span>
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        {{ new Date(report.created_at).toLocaleString() }}
                                    </TableCell>
                                    <TableCell>
                                        {{ report.user?.name || 'System' }}
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="report.filters" class="flex flex-col gap-1 text-xs">
                                            <div v-if="parsedFilters(report.filters) as any" class="flex items-center gap-1.5 text-muted-foreground">
                                                <CalendarIcon class="h-3.5 w-3.5 shrink-0" />
                                                <span>
                                                    {{ parsedFilters(report.filters)?.start_date ? formatFilterDate(parsedFilters(report.filters)!.start_date) : 'Start' }}
                                                    –
                                                    {{ parsedFilters(report.filters)?.end_date ? formatFilterDate(parsedFilters(report.filters)!.end_date) : 'End' }}
                                                </span>
                                            </div>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs">—</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col gap-1">
                                            <Badge 
                                                :variant="getStatusBadge(report.status).variant as any"
                                                :class="(getStatusBadge(report.status) as any).class"
                                            >
                                                <Loader2 v-if="report.status === 'processing' || report.status === 'pending'" class="mr-2 h-3 w-3 animate-spin" />
                                                {{ getStatusBadge(report.status).label }}
                                            </Badge>
                                            <span v-if="report.error_message" class="text-[10px] text-destructive max-w-[200px] truncate" :title="report.error_message">
                                                {{ report.error_message }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button 
                                            v-if="report.status === 'completed'" 
                                            variant="outline" 
                                            size="sm" 
                                            as-child
                                            class="bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-600 hover:text-white dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 transition-colors shadow-sm font-semibold"
                                        >
                                            <a :href="download.url(report.id)" class="flex items-center gap-2">
                                                <Download class="h-4 w-4" />
                                                Download CSV
                                            </a>
                                        </Button>
                                        <div v-else-if="report.status === 'processing' || report.status === 'pending'" class="flex items-center justify-end gap-2 text-sm text-muted-foreground mr-3">
                                            <RefreshCcw class="h-3 w-3 animate-spin" />
                                            Generating...
                                        </div>
                                        <span v-else class="text-muted-foreground text-sm mr-3">-</span>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="reports.data.length === 0">
                                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                        No reports found. Generate your first report to get started.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
