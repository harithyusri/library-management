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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Book Loans',
        href: route('loans.index'),
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
    librarian_id?: number;
    borrowed_date: string;
    due_date: string;
    returned_date?: string;
    status: 'active' | 'returned' | 'overdue';
    fine_amount?: number;
    fine_paid?: boolean;
    notes?: string;
    book_copy: BookCopy;
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
    router.get(route('loans.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.book_search = '';
    searchForm.status = 'all';
    searchForm.sort_by = 'borrowed_date';
    searchForm.sort_order = 'desc';
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

const getStatusBadge = (loan: Loan): { variant: 'default' | 'secondary' | 'destructive' | 'outline', label: string } => {
    if (loan.returned_date) {
        return { variant: 'secondary', label: 'Returned' };
    }

    const isOverdue = new Date(loan.due_date) < new Date();
    if (isOverdue) {
        return { variant: 'destructive', label: 'Overdue' };
    }

    return { variant: 'default', label: 'Active' };
};

const getDaysUntilDue = (dueDate: string, returnedDate: string | null): string => {
    if (returnedDate) return '';

    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return `${Math.abs(diffDays)} days overdue`;
    } else if (diffDays === 0) {
        return 'Due today';
    } else if (diffDays === 1) {
        return 'Due tomorrow';
    } else {
        return `${diffDays} days left`;
    }
};
</script>

<template>

    <Head title="Book Loans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Book Loans
                    </h1>
                </div>

                <Link :href="route('loans.create')">
                    <Button>
                        Create Loan
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-border bg-background p-6">
                <div class="grid gap-4 md:grid-cols-6 items-end">

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">Borrower Name</label>
                        <Input v-model="searchForm.search" @input="debounceSearch" placeholder="Search by name..." />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">Book Title</label>
                        <Input v-model="searchForm.book_search" @input="debounceSearch"
                            placeholder="Search by title..." />
                    </div>

                    <div class="md:col-span-1">
                        <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                        <Select v-model="searchForm.status" @update:model-value="search">
                            <SelectTrigger>
                                <SelectValue placeholder="All" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="md:col-span-1">
                        <Button @click="clearFilters" class="w-full">
                            Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Loans Table -->
            <Card>
                <CardContent class="p-0">
                    <!-- Empty State -->
                    <div v-if="loans.data.length === 0" class="py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-muted-foreground"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-4 text-sm font-medium text-foreground">
                            No loans found
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            No book loans match your search criteria.
                        </p>
                        <div class="mt-4 flex justify-center gap-2">
                            <Button variant="outline" @click="clearFilters">
                                Clear Filters
                            </Button>
                            <Link :href="route('loans.create')">
                                <Button>
                                    Create New Loan
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Borrower</TableHead>
                                    <TableHead>Book Title</TableHead>
                                    <TableHead>Barcode</TableHead>
                                    <TableHead>Borrowed Date</TableHead>
                                    <TableHead>Due Date</TableHead>
                                    <TableHead>Returned Date</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="loan in loans.data" :key="loan.id">
                                    <!-- Borrower -->
                                    <TableCell>
                                        <div class="font-medium">{{ loan.user.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ loan.user.email }}</div>
                                    </TableCell>

                                    <!-- Book Title -->
                                    <TableCell>
                                        <div class="font-medium">{{ loan.book_copy.book.title }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            by {{ loan.book_copy.book.author_name }}
                                        </div>
                                    </TableCell>

                                    <!-- Barcode -->
                                    <TableCell>
                                        <code class="rounded bg-muted px-2 py-1 text-xs font-mono">
                                            {{ loan.book_copy.barcode.substring(0, 8) }}...
                                        </code>
                                    </TableCell>

                                    <!-- Borrowed Date -->
                                    <TableCell>
                                        {{ formatDate(loan.borrowed_date) }}
                                    </TableCell>

                                    <!-- Due Date -->
                                    <TableCell>
                                        <div>{{ formatDate(loan.due_date) }}</div>
                                        <div v-if="!loan.returned_date" class="text-xs"
                                            :class="getStatusBadge(loan).variant === 'destructive' ? 'text-destructive font-medium' : 'text-muted-foreground'">
                                            {{ getDaysUntilDue(loan.due_date, loan.returned_date) }}
                                        </div>
                                    </TableCell>

                                    <!-- Returned Date -->
                                    <TableCell>
                                        {{ formatDate(loan.returned_date) }}
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(loan).variant">
                                            {{ getStatusBadge(loan).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <Link :href="route('loans.show', loan.id)">
                                            <Button variant="ghost" size="sm">
                                                View
                                            </Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="loans.last_page > 1" class="rounded-xl border border-border bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing page {{ loans.current_page }} of {{ loans.last_page }} ({{ loans.total }} total)
                    </div>
                    <div class="flex gap-2">
                        <Link v-for="(link, index) in loans.links" :key="index" :href="link.url || '#'" :class="[
                            'rounded-md px-3 py-2 text-sm',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'cursor-not-allowed opacity-50'
                        ]" :disabled="!link.url" v-html="link.label" />
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
