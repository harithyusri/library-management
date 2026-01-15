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
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        Loan Details
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Loan ID: #{{ loan.id }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <!-- Return Book Button (only for active loans) -->
                    <Dialog v-if="!loan.returned_date" v-model:open="showReturnDialog">
                        <DialogTrigger as-child>
                            <Button>
                                <CheckCircleIcon class="mr-2 h-4 w-4" />
                                Return Book
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[425px]">
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
                        <Button variant="outline">
                            Back to List
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Status Card - Redesigned -->
            <Card :class="cn('border', statusInfo.bgClass)">
                <CardContent class="px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <!-- Status Info -->
                        <div class="flex items-center gap-4">
                            <div :class="cn('flex h-12 w-12 items-center justify-center rounded-full bg-white/60 border', statusInfo.iconClass)">
                                <component :is="statusInfo.icon" class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide" :class="statusInfo.textClass">
                                    Loan Status
                                </p>
                                <p class="text-xl font-bold mt-0.5" :class="statusInfo.textClass">
                                    {{ statusInfo.label }}
                                </p>
                            </div>
                        </div>

                        <!-- Due Date Info -->
                        <div v-if="daysUntilDue !== null" class="flex items-center gap-3 sm:text-right">
                            <div class="hidden sm:block">
                                <p class="text-xs font-medium" :class="statusInfo.textClass">
                                    {{ daysUntilDue < 0 ? 'Overdue by' : 'Due in' }}
                                </p>
                                <p class="text-base font-semibold mt-0.5" :class="statusInfo.textClass">
                                    {{ Math.abs(daysUntilDue) }} {{ Math.abs(daysUntilDue) === 1 ? 'day' : 'days' }}
                                </p>
                            </div>
                            <Badge :variant="daysUntilDue < 0 ? 'destructive' : 'secondary'" class="sm:hidden">
                                {{ daysUntilDue < 0 ? 'Overdue by' : 'Due in' }} {{ Math.abs(daysUntilDue) }} {{ Math.abs(daysUntilDue) === 1 ? 'day' : 'days' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-5">

                <!-- Left Column - Book Information -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <BookOpenIcon class="h-5 w-5" />
                                Book Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Book Cover -->
                            <div class="aspect-[2/3] overflow-hidden rounded-lg bg-muted border">
                                <img v-if="loan.book_copy.book.cover_image_url"
                                    :src="loan.book_copy.book.cover_image_url" :alt="loan.book_copy.book.title"
                                    class="h-full w-full object-cover" />
                                <div v-else class="flex h-full w-full items-center justify-center">
                                    <BookOpenIcon class="h-16 w-16 text-muted-foreground/30" />
                                </div>
                            </div>

                            <!-- Book Details -->
                            <div>
                                <h3 class="font-semibold text-base text-foreground leading-tight">
                                    {{ loan.book_copy.book.title }}
                                </h3>
                                <p class="text-sm text-muted-foreground mt-1">
                                    by {{ loan.book_copy.book.author_name }}
                                </p>
                            </div>

                            <Separator />

                            <!-- Copy Details -->
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">Barcode</p>
                                    <code class="text-sm font-mono bg-muted px-2 py-1 rounded">{{ loan.book_copy.barcode }}</code>
                                </div>

                                <div v-if="loan.book_copy.call_number">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">Call Number</p>
                                    <p class="text-sm font-medium">{{ loan.book_copy.call_number }}</p>
                                </div>

                                <div v-if="loan.book_copy.book.isbn">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">ISBN</p>
                                    <code class="text-sm font-mono bg-muted px-2 py-1 rounded">{{ loan.book_copy.book.isbn }}</code>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">Condition</p>
                                    <Badge variant="outline" class="capitalize">{{ loan.book_copy.condition }}</Badge>
                                </div>

                                <div v-if="loan.book_copy.location">
                                    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1">Location</p>
                                    <p class="text-sm font-medium">{{ loan.book_copy.location }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Loan Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Borrower Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <UserIcon class="h-5 w-5" />
                                Borrower Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-4 p-4 rounded-lg bg-muted/50 border">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 border">
                                    <UserIcon class="h-6 w-6 text-primary" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-foreground truncate">{{ loan.user.name }}</p>
                                    <p class="text-sm text-muted-foreground truncate">{{ loan.user.email }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Loan Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <CalendarIcon class="h-5 w-5" />
                                Loan Timeline
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Borrowed Date -->
                                <div class="flex items-start gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 border border-blue-200 flex-shrink-0">
                                        <CalendarIcon class="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <p class="text-sm font-semibold text-foreground">Borrowed</p>
                                        <p class="text-sm text-muted-foreground mt-0.5">
                                            {{ formatShortDate(loan.borrowed_date) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Due Date -->
                                <div class="flex items-start gap-4">
                                    <div :class="cn(
                                        'flex h-10 w-10 items-center justify-center rounded-full border flex-shrink-0',
                                        isOverdue ? 'bg-red-100 border-red-200' : 'bg-orange-100 border-orange-200'
                                    )">
                                        <ClockIcon :class="cn(
                                            'h-5 w-5',
                                            isOverdue ? 'text-red-600' : 'text-orange-600'
                                        )" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-foreground">Due Date</p>
                                            <Badge v-if="isOverdue" variant="destructive" class="text-xs">
                                                {{ Math.abs(daysUntilDue!) }} days overdue
                                            </Badge>
                                        </div>
                                        <p :class="cn(
                                            'text-sm mt-0.5',
                                            isOverdue ? 'text-red-600 font-medium' : 'text-muted-foreground'
                                        )">
                                            {{ formatShortDate(loan.due_date) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Returned Date -->
                                <div v-if="loan.returned_date" class="flex items-start gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 border border-green-200 flex-shrink-0">
                                        <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <p class="text-sm font-semibold text-foreground">Returned</p>
                                        <p class="text-sm text-muted-foreground mt-0.5">
                                            {{ formatShortDate(loan.returned_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Fine Information -->
                    <Card v-if="loan.fine_amount && loan.fine_amount > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <DollarSignIcon class="h-5 w-5" />
                                Fine Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="p-4 rounded-lg bg-red-50 border border-red-200">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <div>
                                        <p class="text-xs font-semibold text-red-900/60 uppercase tracking-wide">Outstanding Fine</p>
                                        <p class="text-2xl font-bold text-red-700 mt-1">
                                            {{ formatCurrency(loan.fine_amount) }}
                                        </p>
                                    </div>
                                    <Badge :variant="loan.fine_paid ? 'secondary' : 'destructive'" class="text-xs">
                                        {{ loan.fine_paid ? 'Paid' : 'Unpaid' }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes -->
                    <Card v-if="loan.notes">
                        <CardHeader>
                            <CardTitle class="text-lg">Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="p-4 rounded-lg bg-muted/50 border">
                                <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">{{ loan.notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Librarian Info -->
                    <Card v-if="loan.librarian">
                        <CardHeader>
                            <CardTitle class="text-lg">Issued By</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-4 p-4 rounded-lg bg-muted/50 border">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted border flex-shrink-0">
                                    <UserIcon class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-foreground truncate">{{ loan.librarian.name }}</p>
                                    <p class="text-sm text-muted-foreground truncate">{{ loan.librarian.email }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>

            </div>

        </div>
    </AppLayout>
</template>