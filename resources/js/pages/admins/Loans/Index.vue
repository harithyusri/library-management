<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Eye, Plus } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Book Loans',
        href: route('admin.loans.index'),
    },
];

/* =========================
   Types
========================= */
interface Book {
    id: number;
    title: string;
    author_name: string;
    isbn?: string;
}

interface BookCopy {
    id: number;
    barcode: string;
    call_number?: string;
    book: Book;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Loan {
    id: number;
    book_copy_id: number;
    user_id: number;
    borrowed_date: string;
    due_date: string;
    returned_date: string | null;
    status: 'active' | 'returned' | 'overdue';
    book_copy: BookCopy | null;
    user: User;
}

interface PaginatedLoans {
    data: Loan[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    loans: PaginatedLoans;
    filters: Record<string, any>;
    statuses: Record<string, string>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    book_search: props.filters?.book_search ?? '',
    status: props.filters?.status ?? 'all',
    sort_by: props.filters?.sort_by ?? 'borrowed_date',
    sort_order: props.filters?.sort_order ?? 'desc',
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Methods
========================= */
const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const search = () => {
    router.get(route('admin.loans.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.book_search = '';
    searchForm.status = 'all';
    search();
};

const formatDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusConfig = (loan: Loan) => {
    if (loan.returned_date) {
        return {
            label: props.statuses['returned'],
            class: 'border-orange-500 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/20',
            dotClass: 'bg-orange-500'
        };
    }
    const isOverdue = new Date(loan.due_date) < new Date();
    if (isOverdue) {
        return {
            label: props.statuses['overdue'],
            class: 'border-red-500 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/20',
            dotClass: 'bg-red-500'
        };
    }
    return {
        label: props.statuses['active'],
        class: 'border-green-500 text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/20',
        dotClass: 'bg-green-500'
    };
};

const getDaysUntilDue = (dueDate: string, returnedDate: string | null | undefined): string => {
    if (returnedDate) return '';
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    return `${diffDays} days left`;
};
</script>

<template>

    <Head title="Book Loans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-yellow-950">Book Loans <span class="text-primary text-6xl leading-none">.</span></h1>
                    <p class="text-yellow-800 font-medium">Monitor active circulations and overdue tracking.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('admin.loans.create')" class="contents">
                        <Button class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                            <Plus class="h-4 w-4" />
                            Issue New Loan
                        </Button>
                    </Link>
                </div>
            </div>

            <div>
                <div class="grid gap-4 md:grid-cols-5 items-end">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">Borrower Search</label>
                        <Input v-model="searchForm.search" @input="debounceSearch" placeholder="Name or email..." />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">Book Search</label>
                        <Input v-model="searchForm.book_search" @input="debounceSearch"
                            placeholder="Title or ISBN..." />
                    </div>
                    <div class="md:col-span-1">
                        <Button @click="clearFilters" variant="outline" class="w-full">Reset</Button>
                    </div>
                </div>
            </div>

            <div>
                <div class="space-y-4">
                    <Tabs v-model="searchForm.status" @update:model-value="search">
                        <TabsList>
                            <TabsTrigger value="all">All Loans</TabsTrigger>
                            <TabsTrigger v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <CardContent class="p-0 border rounded-lg overflow-hidden bg-background">
                        <div v-if="loans.data.length === 0" class="py-12 text-center">
                            <h3 class="mt-4 text-sm font-medium text-foreground">No loans found</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Book Title</TableHead>
                                        <TableHead>Book ID</TableHead>
                                        <TableHead>Borrowed Date</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead v-if="searchForm.status === 'returned'">Returned Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="loan in loans.data" :key="loan.id">
                                        <TableCell>
                                            <div class="font-medium">{{ loan.user.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ loan.user.email }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="font-medium">{{ loan.book_copy?.book?.title ?? '—' }}</div>
                                            <div class="text-xs text-muted-foreground">by {{ loan.book_copy?.book?.author_name ?? 'Unknown' }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <code class="rounded bg-muted px-2 py-1 text-xs font-mono">
                                                {{ loan.book_copy?.call_number ?? '—' }}
                                            </code>
                                        </TableCell>
                                        <TableCell>{{ formatDate(loan.borrowed_date) }}</TableCell>
                                        <TableCell>
                                            <div>{{ formatDate(loan.due_date) }}</div>
                                            <div v-if="!loan.returned_date" class="text-xs"
                                                :class="getStatusConfig(loan).label === props.statuses['overdue'] ? 'text-destructive font-medium' : 'text-muted-foreground'">
                                                {{ getDaysUntilDue(loan.due_date, loan.returned_date) }}
                                            </div>
                                        </TableCell>
                                        <TableCell v-if="searchForm.status === 'returned'">{{
                                            formatDate(loan.returned_date) }}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" :class="getStatusConfig(loan).class">
                                                <span class="relative flex h-2 w-2 mr-2">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                                        :class="getStatusConfig(loan).dotClass"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2"
                                                        :class="getStatusConfig(loan).dotClass"></span>
                                                </span>
                                                {{ getStatusConfig(loan).label }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Link :href="route('admin.loans.show', loan.id)">
                                                <Button variant="outline" size="sm">
                                                    <Eye />
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </div>
            </div>

            <div v-if="loans.last_page > 1" class="rounded-xl border border-border bg-background">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing page {{ loans.current_page }} of {{ loans.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <Link v-for="(link, index) in loans.links" :key="index" :href="link.url || '#'" :class="[
                            'rounded-md px-3 py-2 text-sm',
                            link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'cursor-not-allowed opacity-50'
                        ]" v-html="link.label" />
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="loans.data.length" class="bg-background">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ loans.data.length }} books
                    </div>
                    <div class="flex gap-2">
                        <Link v-for="(link, index) in loans.links" :key="index" :href="link.url || ''" :class="[
                            'rounded-md px-3 py-2 text-sm',
                            link.active ? 'bg-black text-white hover:bg-neutral-900' : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'pointer-events-none opacity-50'
                        ]" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>