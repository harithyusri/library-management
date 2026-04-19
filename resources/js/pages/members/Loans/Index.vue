<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    BookOpen, 
    Calendar, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    History,
    ArrowRight,
    Library
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    loans: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: {
        book_search?: string;
        status?: string;
    };
    statuses: Record<string, string>;
}>();

const breadcrumbs = [
    { title: 'My Loans', href: route('member.loans.index') },
];

const bookSearch = ref(props.filters.book_search || '');
const activeTab = ref(props.filters.status || 'all');

const updateFilters = debounce(() => {
    const params: any = {};
    if (bookSearch.value) params.book_search = bookSearch.value;
    if (activeTab.value !== 'all') params.status = activeTab.value;

    router.get(route('member.loans.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([bookSearch, activeTab], () => {
    updateFilters();
});

const formatDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getLoanProgress = (borrowedDate: string, dueDate: string, returnedDate: string | null) => {
    if (returnedDate) return 100;
    
    const start = new Date(borrowedDate).getTime();
    const end = new Date(dueDate).getTime();
    const now = new Date().getTime();
    
    if (now >= end) return 100;
    
    const total = end - start;
    const elapsed = now - start;
    
    return Math.max(0, Math.min(100, (elapsed / total) * 100));
};

const getStatusConfig = (loan: any) => {
    if (loan.returned_date) {
        return {
            label: 'Returned',
            icon: CheckCircle2,
            variant: 'secondary' as const,
            bgColor: 'bg-slate-100 text-slate-600',
            progressColor: 'bg-slate-400'
        };
    }
    
    const isOverdue = new Date(loan.due_date) < new Date();
    if (isOverdue) {
        return {
            label: 'Overdue',
            icon: AlertCircle,
            variant: 'destructive' as const,
            bgColor: 'bg-red-50 text-red-600 border-red-100',
            progressColor: 'bg-red-500'
        };
    }
    
    return {
        label: 'Active',
        icon: Clock,
        variant: 'default' as const,
        bgColor: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        progressColor: 'bg-emerald-500'
    };
};

const getTimeRemaining = (dueDate: string, returnedDate: string | null) => {
    if (returnedDate) return null;
    
    const now = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - now.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return '1 day left';
    return `${diffDays} days left`;
};

</script>

<template>
    <Head title="My Loans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-8">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">My Borrowing <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Manage your active loans and track your reading history.</p>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-64 group">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                        <Input 
                            v-model="bookSearch" 
                            placeholder="Find a book..." 
                            class="pl-9 h-10 border-slate-200 bg-white focus-visible:ring-indigo-500 rounded-xl shadow-sm w-full"
                        />
                    </div>
                </div>
            </div>

            <!-- Stats & Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <Button 
                    @click="activeTab = 'all'"
                    variant="ghost"
                    class="rounded-xl px-6 h-10 font-bold transition-all"
                    :class="activeTab === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md shadow-indigo-100' : 'bg-white border text-slate-600 hover:bg-slate-50'"
                >
                    All History
                </Button>
                <Button 
                    @click="activeTab = 'active'"x
                    variant="ghost"
                    class="rounded-xl px-6 h-10 font-bold transition-all"
                    :class="activeTab === 'active' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-100' : 'bg-white border text-slate-600 hover:bg-slate-50'"
                >
                    <Clock class="h-4 w-4 mr-2" />
                    Active
                </Button>
                <Button 
                    @click="activeTab = 'overdue'"
                    variant="ghost"
                    class="rounded-xl px-6 h-10 font-bold transition-all"
                    :class="activeTab === 'overdue' ? 'bg-red-500 text-white hover:bg-red-600 shadow-md shadow-red-100' : 'bg-white border text-slate-600 hover:bg-slate-50'"
                >
                    <AlertCircle class="h-4 w-4 mr-2" />
                    Overdue
                </Button>
                <Button 
                    @click="activeTab = 'returned'"
                    variant="ghost"
                    class="rounded-xl px-6 h-10 font-bold transition-all"
                    :class="activeTab === 'returned' ? 'bg-amber-600 text-white hover:bg-amber-700 shadow-md shadow-amber-100' : 'bg-white border text-slate-600 hover:bg-slate-50'"
                >
                    <History class="h-4 w-4 mr-2" />
                    Returned
                </Button>
            </div>

            <!-- Cards Grid -->
            <div v-if="loans.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-4">
                <Card v-for="loan in loans.data" :key="loan.id" class="group border-slate-200 overflow-hidden hover:border-indigo-200 transition-all duration-300 shadow-sm hover:shadow-md rounded-2xl">
                    <CardContent class="p-0">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Book Visual Info -->
                            <div class="w-full lg:w-48 bg-slate-50 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-slate-100 group-hover:bg-indigo-50/30 transition-colors">
                                <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 mb-3 group-hover:scale-110 transition-transform duration-500">
                                    <BookOpen class="h-8 w-8 text-indigo-500" />
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 text-center">
                                    {{ loan.book_copy.barcode }}
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="flex-1 p-6 space-y-6">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline" :class="getStatusConfig(loan).bgColor" class="px-2 py-0 h-5 text-[10px] uppercase font-bold tracking-wider rounded-md border-0">
                                                {{ getStatusConfig(loan).label }}
                                            </Badge>
                                            <span v-if="!loan.returned_date" class="text-xs font-bold" :class="new Date(loan.due_date) < new Date() ? 'text-red-500' : 'text-indigo-600'">
                                                {{ getTimeRemaining(loan.due_date, loan.returned_date) }}
                                            </span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ loan.book_copy.book.title }}
                                        </h3>
                                        <p class="text-sm text-slate-500 font-medium">by {{ loan.book_copy.book.author_name }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                                        <div class="space-y-1">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Borrowed</span>
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                                <Calendar class="h-3.5 w-3.5 text-slate-300" />
                                                {{ formatDate(loan.borrowed_date) }}
                                            </span>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Due Date</span>
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                                <Calendar class="h-3.5 w-3.5 text-slate-300" />
                                                {{ formatDate(loan.due_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress representation -->
                                <div v-if="!loan.returned_date" class="space-y-2 pt-2">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                        <span class="text-slate-400">Borrow Progress</span>
                                        <span :class="new Date(loan.due_date) < new Date() ? 'text-red-500' : 'text-indigo-500'">
                                            {{ Math.round(getLoanProgress(loan.borrowed_date, loan.due_date, loan.returned_date)) }}% Elapsed
                                        </span>
                                    </div>
                                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full transition-all duration-1000 ease-out"
                                            :class="getStatusConfig(loan).progressColor"
                                            :style="{ width: `${getLoanProgress(loan.borrowed_date, loan.due_date, loan.returned_date)}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <div v-else class="flex items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                                    <span class="text-xs font-bold text-slate-600">
                                        Returned on {{ formatDate(loan.returned_date) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center space-y-6 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200">
                <div class="bg-white p-6 rounded-full w-24 h-24 mx-auto shadow-sm flex items-center justify-center border border-slate-100">
                    <Library class="h-10 w-10 text-slate-300" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-2xl font-black text-slate-900">No loans found</h3>
                    <p class="text-slate-500 font-medium max-w-sm mx-auto">
                        {{ bookSearch ? `We couldn't find any books matching "${bookSearch}".` : "You haven't borrowed any books yet. Check out our catalog to get started!" }}
                    </p>
                </div>
                <div v-if="bookSearch">
                    <Button variant="outline" @click="bookSearch = ''; activeTab = 'all'" class="rounded-xl px-8 font-bold">
                        Clear Search
                    </Button>
                </div>
                <div v-else>
                    <Link :href="route('member.catalog.index')">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-10 font-bold shadow-lg shadow-indigo-200/50">
                            Browse Catalog
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="loans.data.length > 0 && loans.links.length > 3" class="pt-10 border-t border-slate-100">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <p class="text-sm font-medium text-slate-500">
                        Total Records: <span class="text-slate-900 font-bold">{{ loans.total }}</span>
                    </p>
                    <div class="flex items-center gap-1.5">
                        <Link 
                            v-for="link in loans.links" 
                            :key="link.label"
                            :href="link.url || '#'"
                            class="h-10 min-w-10 flex items-center justify-center rounded-xl px-4 text-sm font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-500 hover:text-indigo-600',
                                !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
