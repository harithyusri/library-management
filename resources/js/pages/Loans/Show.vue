<script setup lang="ts">
import { route } from "ziggy-js";
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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
    status: string;
    fine_amount?: number;
    fine_paid?: boolean;
    notes?: string;
    bookCopy: BookCopy;
    user: User;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    loan: Loan;
}>();

/* =========================
   Computed
========================= */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Borrowed Books',
        href: route('loans.index'),
    },
    {
        title: props.loan.bookCopy.book.title,
        href: route('loans.show', props.loan.id),
    },
];

const formatDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusBadge = (status: string): string => {
    const badges: Record<string, string> = {
        'active': 'bg-blue-100 text-blue-800',
        'returned': 'bg-green-100 text-green-800',
        'overdue': 'bg-red-100 text-red-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const isOverdue = (dueDate: string, returnedDate: string | null): boolean => {
    if (returnedDate) return false;
    return new Date(dueDate) < new Date();
};

const daysOverdue = (dueDate: string, returnedDate: string | null): number => {
    if (returnedDate) return 0;
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = today.getTime() - due.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return Math.max(0, diffDays);
};

const daysUntilDue = (dueDate: string, returnedDate: string | null): number => {
    if (returnedDate) return 0;
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Loan Details - ${loan.bookCopy.book.title}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Loan Details</h1>
                    <Link
                        :href="route('loans.index')"
                        class="mt-2 text-sm text-indigo-600 hover:text-indigo-500"
                    >
                        ← Back to My Borrowed Books
                    </Link>
                </div>
            </div>

            <!-- Book Information Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Book Information</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Title</p>
                        <p class="mt-1 text-lg text-gray-900">{{ loan.bookCopy.book.title }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Author</p>
                        <p class="mt-1 text-lg text-gray-900">{{ loan.bookCopy.book.author_name }}</p>
                    </div>
                    <div v-if="loan.bookCopy.book.isbn">
                        <p class="text-sm font-medium text-gray-500">ISBN</p>
                        <p class="mt-1 text-lg text-gray-900">{{ loan.bookCopy.book.isbn }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Copy Barcode</p>
                        <p class="mt-1 text-lg text-gray-900 font-mono">{{ loan.bookCopy.barcode }}</p>
                    </div>
                </div>
            </div>

            <!-- Loan Status Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Loan Status</h2>
                <div class="mb-4">
                    <span
                        class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold"
                        :class="getStatusBadge(loan.status)"
                    >
                        {{ loan.status.toUpperCase() }}
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Borrowed Date</p>
                        <p class="mt-1 text-lg text-gray-900">{{ formatDate(loan.borrowed_date) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Due Date</p>
                        <p
                            class="mt-1 text-lg"
                            :class="isOverdue(loan.due_date, loan.returned_date) ? 'text-red-600 font-semibold' : 'text-gray-900'"
                        >
                            {{ formatDate(loan.due_date) }}
                            <span v-if="!loan.returned_date && daysUntilDue(loan.due_date, loan.returned_date) >= 0" class="text-sm text-gray-500">
                                (in {{ daysUntilDue(loan.due_date, loan.returned_date) }} days)
                            </span>
                        </p>
                    </div>
                    <div v-if="loan.returned_date">
                        <p class="text-sm font-medium text-gray-500">Returned Date</p>
                        <p class="mt-1 text-lg text-gray-900">{{ formatDate(loan.returned_date) }}</p>
                    </div>
                    <div v-if="isOverdue(loan.due_date, loan.returned_date)">
                        <p class="text-sm font-medium text-red-500">Days Overdue</p>
                        <p class="mt-1 text-lg font-semibold text-red-600">
                            {{ daysOverdue(loan.due_date, loan.returned_date) }} days
                        </p>
                    </div>
                </div>
            </div>

            <!-- Fine Information Card -->
            <div v-if="loan.fine_amount || loan.fine_paid" class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Fine Information</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fine Amount</p>
                        <p class="mt-1 text-lg text-gray-900">${{ loan.fine_amount?.toFixed(2) || '0.00' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fine Status</p>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-1"
                            :class="loan.fine_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                        >
                            {{ loan.fine_paid ? 'Paid' : 'Pending' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Notes Card -->
            <div v-if="loan.notes" class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Notes</h2>
                <p class="text-gray-700">{{ loan.notes }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <Link
                    :href="route('loans.index')"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 font-medium"
                >
                    Back to List
                </Link>
                <Link
                    :href="route('books.show', loan.bookCopy.book.id)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium"
                >
                    View Book Details
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add any additional styling here */
</style>
