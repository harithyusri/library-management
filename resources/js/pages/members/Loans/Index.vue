<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, BookOpen, Calendar, Clock, CheckCircle2, AlertCircle, History, ArrowRight, Library, RefreshCw, X } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    loans: { data: any[]; links: any[]; total: number };
    filters: { book_search?: string; status?: string };
    statuses: Record<string, string>;
    max_renewals: number;
}>();

const breadcrumbs = [{ title: 'My Loans', href: route('member.loans.index') }];

const bookSearch = ref(props.filters.book_search || '');
const activeTab = ref(props.filters.status || 'all');

const updateFilters = debounce(() => {
    const params: any = {};
    if (bookSearch.value) params.book_search = bookSearch.value;
    if (activeTab.value !== 'all') params.status = activeTab.value;
    router.get(route('member.loans.index'), params, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([bookSearch, activeTab], updateFilters);

const formatDate = (date: string | null) =>
    date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';

const getLoanProgress = (borrowedDate: string, dueDate: string, returnedDate: string | null) => {
    if (returnedDate) return 100;
    const start = new Date(borrowedDate).getTime();
    const end = new Date(dueDate).getTime();
    const now = new Date().getTime();
    if (now >= end) return 100;
    return Math.max(0, Math.min(100, ((now - start) / (end - start)) * 100));
};

const getStatusConfig = (loan: any) => {
    if (loan.returned_date) return { label: 'Returned', progressColor: 'bg-muted-foreground', timeColor: '' };
    const isOverdue = new Date(loan.due_date) < new Date();
    if (isOverdue) return { label: 'Overdue', progressColor: 'bg-destructive', timeColor: 'text-destructive' };
    return { label: 'Active', progressColor: 'bg-emerald-500', timeColor: 'text-[color:var(--leather)]' };
};

const getTimeRemaining = (dueDate: string, returnedDate: string | null) => {
    if (returnedDate) return null;
    const diffDays = Math.ceil((new Date(dueDate).getTime() - Date.now()) / 86400000);
    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    return `${diffDays} day${diffDays === 1 ? '' : 's'} left`;
};

const renewLoan = (loanId: number) => {
    if (!confirm('Renew this loan for another 14 days?')) return;
    router.post(route('member.loans.renew', loanId), {}, { preserveScroll: true });
};

const tabs = [
    { key: 'all', label: 'All', icon: null },
    { key: 'active', label: 'Active', icon: Clock },
    { key: 'overdue', label: 'Overdue', icon: AlertCircle },
    { key: 'returned', label: 'Returned', icon: History },
];
</script>

<template>
    <Head title="My Loans" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">
            <FlashAlert class="mb-4" />

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">
                    My Borrowing History
                </h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">
                    Track your active loans, due dates, and reading history.
                </p>

                <label class="mt-6 flex items-center gap-3 rounded-full border border-border bg-card px-5 py-3.5 max-w-2xl">
                    <Search class="h-4 w-4 text-muted-foreground shrink-0" />
                    <input
                        v-model="bookSearch"
                        placeholder="Search by book title…"
                        class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                    <button v-if="bookSearch" @click="bookSearch = ''" class="shrink-0 text-muted-foreground hover:text-foreground transition">
                        <X class="h-3.5 w-3.5" />
                    </button>
                </label>
            </section>

            <!-- Filter rail -->
            <div class="sticky top-0 z-20 border-b border-border bg-background/90 backdrop-blur -mx-4 px-4 sm:-mx-6 sm:px-6 py-3">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="shrink-0 flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition border"
                        :class="activeTab === tab.key
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-card text-muted-foreground border-border hover:text-foreground'"
                    >
                        <component :is="tab.icon" v-if="tab.icon" class="h-3.5 w-3.5" />
                        {{ tab.label }}
                    </button>
                    <p class="ml-auto text-xs text-muted-foreground font-medium whitespace-nowrap shrink-0">
                        <span class="font-bold text-foreground">{{ loans.total }}</span> records
                    </p>
                </div>
            </div>

            <!-- Shelf heading -->
            <div class="flex items-end justify-between gap-4 pt-8 pb-4">
                <h2 class="font-serif text-2xl">{{ activeTab === 'all' ? 'All loans' : tabs.find(t => t.key === activeTab)?.label }}</h2>
            </div>

            <!-- Loan cards -->
            <div v-if="loans.data.length > 0" class="flex flex-col gap-4">
                <article
                    v-for="loan in loans.data"
                    :key="loan.id"
                    class="group relative border border-border rounded-xl overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--shadow-book)] bg-card"
                >
                    <!-- Spine accent -->
                    <span class="absolute left-0 top-0 h-full w-1.5" :style="{ background: loan.returned_date ? 'var(--dust)' : new Date(loan.due_date) < new Date() ? 'oklch(0.55 0.18 25)' : 'var(--sage)' }" />

                    <div class="flex flex-col lg:flex-row pl-2">
                        <!-- Icon panel -->
                        <div class="w-full lg:w-40 bg-secondary/40 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-border">
                            <div class="bg-card p-3 rounded-xl border border-border mb-2 group-hover:scale-110 transition-transform duration-500">
                                <BookOpen class="h-7 w-7" style="color: var(--brass)" />
                            </div>
                            <p class="text-[10px] font-mono text-muted-foreground text-center truncate max-w-full px-2">{{ loan.book_copy.barcode }}</p>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 p-6 space-y-4">
                            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold border"
                                            :class="loan.returned_date ? 'bg-secondary text-muted-foreground border-border' : new Date(loan.due_date) < new Date() ? 'bg-destructive/10 text-destructive border-destructive/25' : 'bg-primary/10 text-primary border-primary/25'">
                                            {{ getStatusConfig(loan).label }}
                                        </span>
                                        <span v-if="!loan.returned_date" class="text-xs font-bold" :class="getStatusConfig(loan).timeColor">
                                            {{ getTimeRemaining(loan.due_date, loan.returned_date) }}
                                        </span>
                                    </div>
                                    <h3 class="font-serif text-xl leading-tight group-hover:text-[color:var(--leather)] transition-colors">
                                        {{ loan.book_copy.book.title }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground">by {{ loan.book_copy.book.author_name }}</p>
                                </div>

                                <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm shrink-0">
                                    <div class="space-y-0.5">
                                        <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Borrowed</span>
                                        <span class="font-bold flex items-center gap-1.5 whitespace-nowrap">
                                            <Calendar class="h-3.5 w-3.5 text-muted-foreground" /> {{ formatDate(loan.borrowed_date) }}
                                        </span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Due Date</span>
                                        <span class="font-bold flex items-center gap-1.5 whitespace-nowrap">
                                            <Calendar class="h-3.5 w-3.5 text-muted-foreground" /> {{ formatDate(loan.due_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div v-if="!loan.returned_date" class="space-y-1.5">
                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-muted-foreground">Borrow progress</span>
                                    <span :class="getStatusConfig(loan).timeColor">{{ Math.round(getLoanProgress(loan.borrowed_date, loan.due_date, loan.returned_date)) }}% elapsed</span>
                                </div>
                                <div class="h-1.5 w-full bg-secondary rounded-full overflow-hidden">
                                    <div class="h-full transition-all duration-1000 ease-out rounded-full" :class="getStatusConfig(loan).progressColor"
                                        :style="{ width: `${getLoanProgress(loan.borrowed_date, loan.due_date, loan.returned_date)}%` }" />
                                </div>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-[10px] text-muted-foreground">Renewals: {{ loan.renewals_count ?? 0 }} / {{ max_renewals }}</span>
                                    <Button v-if="(loan.renewals_count ?? 0) < max_renewals" size="sm" variant="outline"
                                        class="h-7 px-3 rounded-full text-xs font-bold" style="border-color: var(--brass); color: var(--leather);"
                                        @click="renewLoan(loan.id)">
                                        <RefreshCw class="h-3 w-3 mr-1" /> Renew
                                    </Button>
                                    <span v-else class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Max renewals reached</span>
                                </div>
                            </div>

                            <div v-else class="flex items-center gap-2 p-3 bg-secondary/40 rounded-lg border border-border">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                                <span class="text-xs font-bold text-muted-foreground">Returned on {{ formatDate(loan.returned_date) }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <Library class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">No loans found</h3>
                    <p class="text-sm text-muted-foreground">{{ bookSearch ? `No results for "${bookSearch}".` : "You haven't borrowed any books yet." }}</p>
                </div>
                <Link v-if="!bookSearch" :href="route('member.catalog.index')">
                    <Button class="rounded-full px-8 font-bold" style="background: var(--ink); color: var(--dust);">
                        Browse Catalog <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </Link>
                <Button v-else variant="outline" @click="bookSearch = ''" class="rounded-full px-6">Clear search</Button>
            </div>

            <!-- Quote -->
            <div class="mt-12 rounded-xl border border-border bg-card p-6 text-center">
                <p class="font-serif italic text-lg">"A reader lives a thousand lives before he dies."</p>
                <p class="mt-2 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">George R.R. Martin</p>
            </div>

            <!-- Pagination -->
            <div v-if="loans.data.length > 0 && loans.links.length > 3" class="flex flex-col md:flex-row items-center justify-between gap-4 pt-8 border-t border-border">
                <p class="text-sm text-muted-foreground">Total: <span class="font-bold text-foreground">{{ loans.total }}</span></p>
                <div class="flex items-center gap-1.5">
                    <Link v-for="link in loans.links" :key="link.label" :href="link.url || '#'"
                        class="h-9 min-w-9 flex items-center justify-center rounded-full px-3.5 text-sm font-bold transition-all border"
                        :class="[link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border text-muted-foreground hover:border-[color:var(--brass)] hover:text-[color:var(--leather)]', !link.url && 'opacity-40 cursor-not-allowed pointer-events-none']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
