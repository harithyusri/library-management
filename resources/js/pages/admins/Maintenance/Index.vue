<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    Filter, 
    MoreHorizontal, 
    Eye, 
    CheckCircle2, 
    Clock, 
    AlertTriangle, 
    Wrench,
    Hammer,
    User,
    Calendar,
    MessageSquare,
    X,
    ExternalLink,
    Activity
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
}>();

const breadcrumbs = [
    { title: 'Maintenance Management', href: route('admin.maintenance.index') },
];

// ── Search & Filters ──────────────────────────────────────────────
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const categoryFilter = ref(props.filters.category || 'all');

const updateFilters = debounce(() => {
    router.get(route('admin.maintenance.index'), {
        search: search.value,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
        category: categoryFilter.value !== 'all' ? categoryFilter.value : null,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch([search, statusFilter, categoryFilter], () => {
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
        <div class="px-6 pt-2 pb-8 space-y-6">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Maintenance Requests <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium italic">Manage library facility repairs and member feedback.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Badge variant="outline" class="h-11 px-6 rounded-xl border-slate-200 bg-white shadow-sm flex items-center gap-2">
                        <Activity class="h-4 w-4 text-indigo-500" />
                        <span class="font-bold text-slate-700 tracking-tight">{{ reports.total }} Active Reports</span>
                    </Badge>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <div class="relative flex-1 w-full group">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-red-500 transition-colors" />
                    <Input 
                        v-model="search" 
                        placeholder="Search by title or member name..." 
                        class="pl-10 h-11 border-slate-200 rounded-xl focus-visible:ring-red-500 bg-slate-50/50"
                    />
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <select 
                        v-model="statusFilter"
                        class="h-11 px-4 rounded-xl border border-slate-200 bg-white text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none transition-all min-w-[140px]"
                    >
                        <option value="all">All Statuses</option>
                        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                    </select>

                    <select 
                        v-model="categoryFilter"
                        class="h-11 px-4 rounded-xl border border-slate-200 bg-white text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none transition-all min-w-[140px]"
                    >
                        <option value="all">All Categories</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <Card class="border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Report Details</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Reporter</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Priority</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Reported At</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="report in reports.data" :key="report.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-start gap-3">
                                            <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 group-hover:bg-red-50 transition-colors">
                                                <Hammer v-if="report.category === 'Furniture'" class="h-5 w-5 text-slate-400 group-hover:text-red-500" />
                                                <AlertTriangle v-else class="h-5 w-5 text-slate-400 group-hover:text-red-500" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-900 group-hover:text-red-600 transition-colors truncate max-w-[200px]">{{ report.title }}</p>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ report.category }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="h-7 w-7 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                                                <User class="h-3.5 w-3.5 text-indigo-500" />
                                            </div>
                                            <span class="text-sm font-medium text-slate-700">{{ report.user.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="text-xs uppercase tracking-widest" :class="getPriorityColor(report.priority)">
                                            {{ report.priority }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <Badge variant="outline" :class="getStatusBadge(report.status)" class="px-2 py-0 h-5 text-[10px] font-black rounded-md border-0 uppercase tracking-wider">
                                            {{ report.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-5 text-sm text-slate-500 font-medium">
                                        {{ formatDate(report.created_at) }}
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <Button 
                                            @click="openUpdateModal(report)"
                                            variant="ghost" 
                                            size="sm" 
                                            class="rounded-lg h-9 w-9 p-0 hover:bg-slate-100"
                                        >
                                            <MoreHorizontal class="h-4 w-4 text-slate-400" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="reports.total > 0" class="flex flex-col md:flex-row items-center justify-between gap-6 pt-2">
                <p class="text-sm font-semibold text-slate-500 tracking-tight">
                    Showing page <span class="text-slate-900">{{ reports.current_page }}</span> of {{ reports.last_page }}
                </p>
                <div class="flex items-center gap-2">
                    <Link 
                        v-for="link in reports.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        class="h-10 min-w-[40px] px-3 flex items-center justify-center rounded-xl text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-red-600 text-white shadow-lg shadow-red-100' : 'bg-white border border-slate-200 text-slate-600 hover:border-red-600 hover:text-red-600',
                            !link.url && 'opacity-30 cursor-not-allowed pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="reports.total === 0" class="py-24 text-center bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                <div class="h-20 w-20 rounded-full bg-white shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-4">
                    <CheckCircle2 class="h-10 w-10 text-emerald-400" />
                </div>
                <h3 class="text-xl font-bold text-slate-900">All clear!</h3>
                <p class="text-slate-500 font-medium max-w-xs mx-auto">No maintenance reports found matching your criteria.</p>
            </div>
        </div>

        <!-- Update Modal (Custom implementation using Card) -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all animate-in fade-in duration-200">
            <Card class="w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
                <CardHeader class="border-b border-slate-100 bg-slate-50/50 p-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <CardTitle class="text-xl font-black text-slate-900">Manage Request</CardTitle>
                            <CardDescription class="text-[10px] font-black uppercase tracking-widest text-slate-400">Report ID: #{{ selectedReport.id }}</CardDescription>
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
                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Issue Overview</h4>
                                <h3 class="text-lg font-bold text-slate-900 leading-tight">{{ selectedReport.title }}</h3>
                                <p class="text-sm text-slate-600 mt-2 italic font-medium">"{{ selectedReport.description }}"</p>
                            </div>

                            <div v-if="selectedReport.image_path" class="space-y-2">
                                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Evidence Photo</h4>
                                <div class="relative group rounded-2xl overflow-hidden border border-slate-100 bg-slate-50">
                                    <img :src="`/storage/${selectedReport.image_path}`" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500" />
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <a :href="`/storage/${selectedReport.image_path}`" target="_blank" class="text-white flex items-center gap-2 text-xs font-bold bg-slate-900/80 px-4 py-2 rounded-xl">
                                            <ExternalLink class="h-3 w-3" /> View Original
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="h-10 w-10 rounded-xl bg-white flex items-center justify-center shadow-sm">
                                    <User class="h-5 w-5 text-indigo-500" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reporter Info</p>
                                    <p class="text-sm font-bold text-slate-900">{{ selectedReport.user.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-medium">{{ selectedReport.user.email || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="h-10 w-10 rounded-xl bg-white flex items-center justify-center shadow-sm">
                                    <Calendar class="h-5 w-5 text-amber-500" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Submitted Date</p>
                                    <p class="text-sm font-bold text-slate-900">{{ formatDate(selectedReport.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitUpdate" class="space-y-6 pt-6 border-t border-slate-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Update Status</label>
                                <select 
                                    v-model="updateForm.status"
                                    class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none transition-all"
                                >
                                    <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Internal Librarian Notes</label>
                            <textarea 
                                v-model="updateForm.admin_notes"
                                placeholder="Add notes about repair progress or reasons for status change..."
                                class="w-full min-h-[120px] rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm focus:ring-2 focus:ring-red-500 outline-none transition-all"
                            ></textarea>
                            <p class="text-[9px] text-slate-400 font-medium italic">Members can see these notes on their portal.</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button type="button" variant="ghost" @click="closeUpdateModal" class="rounded-xl h-12 px-6 font-bold hover:bg-slate-100">Cancel</Button>
                            <Button 
                                type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white rounded-xl h-12 px-10 font-black shadow-lg shadow-red-100 disabled:opacity-50"
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

<style scoped>
.animate-in {
    animation-duration: 0.2s;
    animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
    animation-fill-mode: forwards;
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoom-in-95 {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.fade-in { animation-name: fade-in; }
.zoom-in-95 { animation-name: zoom-in-95; }
</style>
