<script setup lang="ts">
import { route } from "ziggy-js";
import { ref, reactive, computed } from 'vue';
import { router, useForm, Head, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { BookOpenIcon, UserIcon, CalendarIcon, ClockIcon, DollarSignIcon, CheckCircleIcon, XCircleIcon, AlertCircleIcon } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/* =========================
   Types
========================= */
interface User {
    id: number;
    name: string;
    email: string;
}

interface Book {
    id: number;
    title: string;
    author_name: string;
    isbn?: string;
    cover_image_url?: string;
}

interface BookCopy {
    id: number;
    barcode: string;
    call_number?: string;
    status: string;
    condition: string;
    location?: string;
    book: Book;
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
    librarian?: User;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    loan: Loan;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Book Loans',
        href: route('loans.index'),
    },
    {
        title: 'Loan Details',
        href: '#',
    },
];

/* =========================
   State
========================= */
const showReturnDialog = ref(false);

const returnForm = useForm({
    returned_date: new Date().toISOString().split('T')[0],
    condition_notes: '',
});

/* =========================
   Computed
========================= */
const isOverdue = computed(() => {
    if (props.loan.returned_date) return false;
    return new Date(props.loan.due_date) < new Date();
});

const daysUntilDue = computed(() => {
    if (props.loan.returned_date) return null;

    const today = new Date();
    const due = new Date(props.loan.due_date);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays;
});

const statusInfo = computed(() => {
    if (props.loan.returned_date) {
        return {
            variant: 'secondary' as const,
            label: 'Returned',
            icon: CheckCircleIcon,
            bgClass: 'bg-green-50 border-green-200',
            textClass: 'text-green-700',
            iconClass: 'text-green-600',
        };
    }

    if (isOverdue.value) {
        return {
            variant: 'destructive' as const,
            label: 'Overdue',
            icon: AlertCircleIcon,
            bgClass: 'bg-red-50 border-red-200',
            textClass: 'text-red-700',
            iconClass: 'text-red-600',
        };
    }

    return {
        variant: 'default' as const,
        label: 'Active',
        icon: ClockIcon,
        bgClass: 'bg-green-50 border-green-200',
        textClass: 'text-green-700',
        iconClass: 'text-green-600',
    };
});

/* =========================
   Methods
========================= */
const formatDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatShortDate = (date: string | null): string => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatCurrency = (amount?: number): string => {
    if (!amount) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const submitReturn = () => {
    returnForm.post(route('loans.return', props.loan.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReturnDialog.value = false;
        },
    });
};

const cancel = () => {
    router.visit(route('loans.index'));
};
</script>

<template>
    <Head title="Loan Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 space-y-8">

            <FlashAlert />

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold">
                        Loan #{{ loan.id }}
                    </h1>

                    <div class="flex items-center gap-2 text-sm">
                        <!-- Status dot -->
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="{
                                'bg-green-500': statusInfo.label === 'Active',
                                'bg-red-500': statusInfo.label === 'Overdue',
                                'bg-gray-400': statusInfo.label === 'Returned',
                            }"
                        />

                        <span class="text-muted-foreground">
                            {{ statusInfo.label }}
                        </span>

                        <span v-if="daysUntilDue !== null" class="text-muted-foreground">
                            ·
                            <span :class="daysUntilDue < 0 ? 'text-destructive font-medium' : ''">
                                {{ Math.abs(daysUntilDue) }} days
                                {{ daysUntilDue < 0 ? 'overdue' : 'left' }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Dialog v-if="!loan.returned_date" v-model:open="showReturnDialog">
                        <DialogTrigger as-child>
                            <Button size="sm">
                                Return
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Return Book</DialogTitle>
                                <DialogDescription>
                                    Mark this book as returned and update its condition.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <!-- Return Date -->
                                <div class="grid gap-2">
                                    <Label for="returned_date">
                                        Return Date <span class="text-destructive">*</span>
                                    </Label>
                                    <Input id="returned_date" v-model="returnForm.returned_date" type="date"
                                        :class="{ 'border-destructive': returnForm.errors.returned_date }" />
                                    <p v-if="returnForm.errors.returned_date" class="text-xs text-destructive">
                                        {{ returnForm.errors.returned_date }}
                                    </p>
                                </div>

                                <!-- Condition Notes -->
                                <div class="grid gap-2">
                                    <Label for="condition_notes">
                                        Condition Notes (Optional)
                                    </Label>
                                    <Textarea id="condition_notes" v-model="returnForm.condition_notes"
                                        placeholder="Note any damage or issues with the book..." rows="3"
                                        :class="{ 'border-destructive': returnForm.errors.condition_notes }" />
                                    <p v-if="returnForm.errors.condition_notes" class="text-xs text-destructive">
                                        {{ returnForm.errors.condition_notes }}
                                    </p>
                                </div>

                                <!-- Overdue Warning -->
                                <div v-if="isOverdue" class="rounded-lg bg-destructive/10 p-3 text-sm">
                                    <p class="font-medium text-destructive">⚠️ This loan is overdue!</p>
                                    <p class="mt-1 text-destructive/80">
                                        A fine of {{ formatCurrency(loan.fine_amount) }} will be applied.
                                    </p>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="showReturnDialog = false"
                                    :disabled="returnForm.processing">
                                    Cancel
                                </Button>
                                <Button type="submit" @click="submitReturn" :disabled="returnForm.processing">
                                    <span v-if="returnForm.processing">Processing...</span>
                                    <span v-else>Confirm Return</span>
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Link :href="route('loans.index')">
                        <Button variant="ghost" size="sm">Back</Button>
                    </Link>
                </div>
            </div>

            <Separator />

            <!-- Book -->
            <div class="flex items-start gap-4">
                <div class="h-24 w-16 flex-shrink-0 overflow-hidden rounded border bg-muted">
                    <img
                        v-if="loan.book_copy.book.cover_image_url"
                        :src="loan.book_copy.book.cover_image_url"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                        <BookOpenIcon class="h-6 w-6 text-muted-foreground/40" />
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="font-medium leading-tight text-foreground">
                        {{ loan.book_copy.book.title }}
                    </p>

                    <p class="text-sm text-muted-foreground">
                        {{ loan.book_copy.book.author_name }}
                    </p>

                    <p class="text-xs text-muted-foreground pt-1">
                        <span class="font-mono">{{ loan.book_copy.barcode }}</span>
                        <span v-if="loan.book_copy.call_number">
                            · {{ loan.book_copy.call_number }}
                        </span>
                        · <span class="capitalize">{{ loan.book_copy.condition }}</span>
                    </p>
                </div>
            </div>

            <Separator />

            <!-- Borrower -->
            <div class="space-y-1">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">
                    Borrower
                </p>

                <p class="font-medium text-foreground">
                    {{ loan.user.name }}
                </p>

                <p class="text-sm text-muted-foreground">
                    {{ loan.user.email }}
                </p>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-muted-foreground">Borrowed</p>
                    <p class="text-foreground">
                        {{ formatShortDate(loan.borrowed_date) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-muted-foreground">Due</p>
                    <p
                        :class="isOverdue
                            ? 'text-destructive font-medium'
                            : 'text-foreground'
                        "
                    >
                        {{ formatShortDate(loan.due_date) }}
                    </p>
                </div>

                <div v-if="loan.returned_date">
                    <p class="text-xs text-muted-foreground">Returned</p>
                    <p class="text-foreground">
                        {{ formatShortDate(loan.returned_date) }}
                    </p>
                </div>

                <div v-if="loan.fine_amount">
                    <p class="text-xs text-muted-foreground">Fine</p>
                    <p class="font-medium text-destructive">
                        {{ formatCurrency(loan.fine_amount) }}
                    </p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="loan.notes" class="space-y-1">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">
                    Notes
                </p>

                <p class="text-sm text-foreground whitespace-pre-wrap">
                    {{ loan.notes }}
                </p>
            </div>

        </div>
    </AppLayout>
</template>