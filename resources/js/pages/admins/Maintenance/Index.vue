<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Search,
    MoreHorizontal,
    CheckCircle2,
    AlertTriangle,
    Hammer,
    User,
    Calendar,
    X,
    ExternalLink,
    Activity,
    RotateCcw,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    reports: {
        data: any[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        status?: string;
        category?: string;
    };
    categories: string[];
    statuses: Record<string, string>;
    priorities: Record<string, string>;
    libraries: Array<{ id: number; name: string }>;
    selected_library_id: number | null;
    is_super_admin: boolean;
}>();

const breadcrumbs = [
    { title: 'Maintenance Management', href: route('admin.maintenance.index') },
];

// ── Search & Filters ──────────────────────────────────────────────
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const categoryFilter = ref(props.filters.category || 'all');
const libraryFilter = ref(props.selected_library_id ? String(props.selected_library_id) : 'all');

const updateFilters = debounce(() => {
    router.get(route('admin.maintenance.index'), {
        search: search.value,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
        category: categoryFilter.value !== 'all' ? categoryFilter.value : null,
        library_id: libraryFilter.value !== 'all' ? libraryFilter.value : null,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, statusFilter, categoryFilter, libraryFilter], () => {
    updateFilters();
});

// ── Update Modal ──────────────────────────────────────────────────
const isModalOpen = ref(false);
const selectedReport = ref<any>(null);

const updateForm = useForm({
    status: '',
    admin_notes: '',
});

const openUpdateModal = (report: any) => {
    selectedReport.value = report;
    updateForm.status = report.status;
    updateForm.admin_notes = report.admin_notes || '';
    isModalOpen.value = true;
};

const closeUpdateModal = () => {
    isModalOpen.value = false;
    selectedReport.value = null;
    updateForm.reset();
};

const submitUpdate = () => {
    updateForm.put(route('admin.maintenance.update', selectedReport.value.id), {
        onSuccess: () => {
            closeUpdateModal();
        },
        preserveScroll: true,
    });
};

// ── Styles ────────────────────────────────────────────────────────
const getStatusBadge = (status: string) => {
    const s = status.toLowerCase();
    switch (s) {
        case 'resolved': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
        case 'in_progress':
        case 'assigned': return 'bg-blue-100 text-blue-700 border-blue-200';
        default: return 'bg-amber-100 text-amber-700 border-amber-200';
    }
};

const getPriorityColor = (priority: string) => {
    const p = priority.toLowerCase();
    if (p === 'high') return 'text-red-500 font-black';
    if (p === 'medium') return 'text-amber-500 font-bold';
    return 'text-slate-400 font-medium';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

</script>

<template>
    <Head title="Maintenance Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <FlashAlert />

            <PageHeader title="Maintenance Requests " description="Manage library facility repairs and member feedback.">
                    <Badge variant="outline" class="h-11 px-6 rounded-xl flex items-center gap-2">
                        <Activity class="h-4 w-4 text-primary" />
                        <span class="font-bold tracking-tight">{{ reports.total }} Active Reports</span>
                    </Badge>
            </PageHeader>

            <!-- Filters -->
            <div class="grid gap-4 md:grid-cols-4">
                <div :class="is_super_admin && libraries?.length ? 'md:col-span-1' : 'md:col-span-2'">
                    <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Search</Label>
                    <div class="relative mt-2">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search by title or member name..." class="pl-9 bg-background" />
                    </div>
                </div>
                <div v-if="is_super_admin && libraries?.length">
                    <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Library</Label>
                    <select v-model="libraryFilter" class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:ring-ring">
                        <option value="all">All Libraries</option>
                        <option v-for="lib in libraries" :key="lib.id" :value="String(lib.id)">{{ lib.name }}</option>
                    </select>
                </div>
                <div>
                    <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</Label>
                    <select v-model="statusFilter" class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:ring-ring">
                        <option value="all">All Statuses</option>
                        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Category</Label>
                    <select v-model="categoryFilter" class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:ring-ring">
                        <option value="all">All Categories</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <Card class="overflow-hidden border-none">
                <CardContent class="p-0">
                    <div v-if="reports.data.length === 0" class="flex flex-col items-center justify-center py-24 text-center px-4">
                        <div class="bg-muted rounded-full p-4 mb-4">
                            <CheckCircle2 class="h-8 w-8 text-muted-foreground/60" />
                        </div>
                        <h3 class="text-lg font-medium">All clear!</h3>
                        <p class="text-sm text-muted-foreground max-w-xs mt-1">
                            No maintenance reports found matching your criteria.
                        </p>
                    </div>

                    <Table v-else>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="pl-6 font-medium">Report Details</TableHead>
                                <TableHead>Reporter</TableHead>
                                <TableHead>Priority</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Reported At</TableHead>
                                <TableHead class="text-right pr-6">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="report in reports.data" :key="report.id" class="group transition-colors">
                                <!-- Report Details -->
                                <TableCell class="pl-6">
                                    <div class="flex items-start gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-muted flex items-center justify-center shrink-0 group-hover:bg-primary/10 transition-colors">
                                            <Hammer v-if="report.category === 'Furniture'" class="h-5 w-5 text-muted-foreground group-hover:text-primary" />
                                            <AlertTriangle v-else class="h-5 w-5 text-muted-foreground group-hover:text-primary" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-foreground group-hover:text-primary transition-colors truncate max-w-[200px]">{{ report.title }}</p>
                                            <p class="text-xs text-muted-foreground uppercase tracking-tight font-medium mt-0.5">{{ report.category }}</p>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Reporter -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="h-7 w-7 rounded-full bg-muted border border-border flex items-center justify-center">
                                            <User class="h-3.5 w-3.5 text-muted-foreground" />
                                        </div>
                                        <span class="text-sm font-medium text-foreground">{{ report.user.name }}</span>
                                    </div>
                                </TableCell>

                                <!-- Priority -->
                                <TableCell>
                                    <span class="text-xs uppercase tracking-widest" :class="getPriorityColor(report.priority)">
                                        {{ report.priority }}
                                    </span>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge variant="outline" :class="getStatusBadge(report.status)" class="px-2 py-0 h-5 text-[10px] font-black rounded-md border-0 uppercase tracking-wider">
                                        {{ report.status }}
                                    </Badge>
                                </TableCell>

                                <!-- Reported At -->
                                <TableCell class="text-sm text-muted-foreground font-medium">
                                    {{ formatDate(report.created_at) }}
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right pr-6">
                                    <Button
                                        @click="openUpdateModal(report)"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 rounded-full"
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Update Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <Card class="w-full max-w-2xl overflow-hidden">
                <CardHeader class="border-b border-border bg-muted/50 p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <CardTitle class="text-xl font-black text-foreground">Manage Request</CardTitle>
                            <CardDescription class="text-[10px] font-black uppercase tracking-widest">Report ID: #{{ selectedReport.id }}</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" @click="closeUpdateModal" class="rounded-full h-8 w-8 p-0">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-8 space-y-8 max-h-[70vh] overflow-y-auto">
                    <!-- Report Detail Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Issue Overview</h4>
                                <h3 class="text-lg font-bold text-foreground leading-tight">{{ selectedReport.title }}</h3>
                                <p class="text-sm text-muted-foreground mt-2 italic font-medium">"{{ selectedReport.description }}"</p>
                            </div>

                            <div v-if="selectedReport.image_path" class="space-y-2">
                                <h4 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Evidence Photo</h4>
                                <div class="relative group rounded-2xl overflow-hidden border border-border bg-muted">
                                    <img :src="`/storage/${selectedReport.image_path}`" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500" />
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <a :href="`/storage/${selectedReport.image_path}`" target="_blank" class="text-white flex items-center gap-2 text-xs font-bold bg-black/80 px-4 py-2 rounded-xl">
                                            <ExternalLink class="h-3 w-3" /> View Original
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center gap-4 p-4 bg-muted rounded-2xl border border-border">
                                <div class="h-10 w-10 rounded-xl bg-card flex items-center justify-center">
                                    <User class="h-5 w-5 text-primary" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Reporter Info</p>
                                    <p class="text-sm font-bold text-foreground">{{ selectedReport.user.name }}</p>
                                    <p class="text-[10px] text-muted-foreground font-medium">{{ selectedReport.user.email || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-muted rounded-2xl border border-border">
                                <div class="h-10 w-10 rounded-xl bg-card flex items-center justify-center">
                                    <Calendar class="h-5 w-5 text-primary" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Submitted Date</p>
                                    <p class="text-sm font-bold text-foreground">{{ formatDate(selectedReport.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitUpdate" class="space-y-6 pt-6 border-t border-border">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Update Status</Label>
                                <select
                                    v-model="updateForm.status"
                                    class="w-full h-12 rounded-xl border border-input bg-background px-4 text-sm font-bold focus:border-ring focus:ring-1 focus:ring-ring outline-none transition-all"
                                >
                                    <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Internal Librarian Notes</Label>
                            <textarea
                                v-model="updateForm.admin_notes"
                                placeholder="Add notes about repair progress or reasons for status change..."
                                class="w-full min-h-[120px] rounded-2xl border border-input bg-background p-4 text-sm focus:border-ring focus:ring-1 focus:ring-ring outline-none transition-all"
                            ></textarea>
                            <p class="text-[9px] text-muted-foreground font-medium italic">Members can see these notes on their portal.</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button type="button" variant="ghost" @click="closeUpdateModal" class="rounded-xl h-12 px-6 font-bold">Cancel</Button>
                            <Button
                                type="submit"
                                class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl h-12 px-10 font-black disabled:opacity-50"
                                :disabled="updateForm.processing"
                            >
                                <CheckCircle2 class="h-4 w-4 mr-2" />
                                {{ updateForm.processing ? 'Updating...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

