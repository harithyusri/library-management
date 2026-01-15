<script setup lang="ts">
import { route } from "ziggy-js";
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import type { DateValue } from '@internationalized/date';
import { CalendarDate, fromDate, getLocalTimeZone, toCalendarDate } from '@internationalized/date';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { CalendarIcon, SearchIcon, CheckIcon, Loader2Icon } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Book Loans',
        href: route('loans.index'),
    },
    {
        title: 'Create Loan',
        href: '#',
    },
];

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

/* =========================
   Props
========================= */
const props = defineProps<{
    users: User[];
}>();

/* =========================
   State
========================= */
const form = useForm({
    user_id: '',
    book_copy_id: '',
    borrowed_date: new Date().toISOString().split('T')[0],
    due_date: '',
    notes: '',
});

const openUserSelect = ref(false);
const selectedUser = ref<User | null>(null);
const selectedCopy = ref<BookCopy | null>(null);

// Date pickers state
const borrowedDate = ref(fromDate(new Date(), getLocalTimeZone())) as Ref<DateValue>;
const dueDate = ref<DateValue | undefined>(undefined);
const showBorrowedCalendar = ref(false);
const showDueCalendar = ref(false);

// Book search state
const copySearchQuery = ref('');
const copySearchResults = ref<BookCopy[]>([]);
const isSearching = ref(false);
const showSearchDropdown = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Computed
========================= */
const defaultDueDate = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 14);
    return fromDate(date, getLocalTimeZone());
});

// Set default due date
if (!form.due_date) {
    dueDate.value = defaultDueDate.value;
    form.due_date = defaultDueDate.value.toString();
}

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

/* =========================
   Methods
========================= */
const selectUser = (user: User) => {
    selectedUser.value = user;
    form.user_id = String(user.id);
    openUserSelect.value = false;
};

// Handle borrowed date change
const handleBorrowedDateChange = (date: DateValue | undefined) => {
    if (date) {
        borrowedDate.value = date;
        form.borrowed_date = dateValueToString(date);
        showBorrowedCalendar.value = false;

        // Auto-calculate due date (14 days later)
        const dueDateCalc = new Date(date.year, date.month - 1, date.day);
        dueDateCalc.setDate(dueDateCalc.getDate() + 14);
        const newDueDate = fromDate(dueDateCalc, getLocalTimeZone());
        dueDate.value = newDueDate;
        form.due_date = dateValueToString(newDueDate);
    }
};

// Handle due date change
const handleDueDateChange = (date: DateValue | undefined) => {
    if (date) {
        dueDate.value = date;
        form.due_date = dateValueToString(date);
        showDueCalendar.value = false;
    }
};

// Book search
const handleSearchChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    copySearchQuery.value = value;

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    const trimmedQuery = value.trim();

    if (!trimmedQuery || trimmedQuery.length < 2) {
        copySearchResults.value = [];
        showSearchDropdown.value = false;
        isSearching.value = false;
        return;
    }

    showSearchDropdown.value = true;
    isSearching.value = true;

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(
                route('api.book-copies.search', { q: trimmedQuery })
            );
            const data = await response.json();
            copySearchResults.value = data.data || [];
        } catch (error) {
            console.error('Search failed:', error);
            copySearchResults.value = [];
        } finally {
            isSearching.value = false;
        }
    }, 300);
};

const selectBookCopy = (copy: BookCopy) => {
    selectedCopy.value = copy;
    form.book_copy_id = String(copy.id);
    copySearchQuery.value = '';
    copySearchResults.value = [];
    showSearchDropdown.value = false;
};

const clearCopySelection = () => {
    selectedCopy.value = null;
    form.book_copy_id = '';
    copySearchResults.value = [];
    copySearchQuery.value = '';
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.search-container')) {
        showSearchDropdown.value = false;
    }
};

const submitForm = () => {
    form.post(route('loans.store'), {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.visit(route('loans.index'));
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>

    <Head title="Create Loan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold text-foreground">
                    Create New Loan
                </h1>
                <p class="text-sm text-muted-foreground">
                    Issue a book to a borrower
                </p>
            </div>

            <hr>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">

                <!-- Borrower Selection -->
                <CardHeader>
                    <CardTitle>Select Borrower</CardTitle>
                    <CardDescription>
                        Choose the user who will borrow the book
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <Label for="user">
                                Borrower <span class="text-destructive">*</span>
                            </Label>
                            <Popover v-model:open="openUserSelect">
                                <PopoverTrigger as-child>
                                    <Button variant="outline" role="combobox" :class="cn(
                                        'w-full justify-between mt-2',
                                        !selectedUser && 'text-muted-foreground',
                                        form.errors.user_id && 'border-destructive'
                                    )">
                                        <span v-if="selectedUser">{{ selectedUser.name }}</span>
                                        <span v-else>Select borrower...</span>
                                        <SearchIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                    <Command>
                                        <CommandInput placeholder="Search users..." />
                                        <CommandList>
                                            <CommandEmpty>No user found.</CommandEmpty>
                                            <CommandGroup>
                                                <CommandItem v-for="user in props.users" :key="user.id"
                                                    :value="user.name" @select="() => selectUser(user)">
                                                    <CheckIcon :class="cn(
                                                        'mr-2 h-4 w-4',
                                                        selectedUser?.id === user.id ? 'opacity-100' : 'opacity-0'
                                                    )" />
                                                    <div class="flex flex-col">
                                                        <span class="font-medium">{{ user.name }}</span>
                                                        <span class="text-xs text-muted-foreground">{{ user.email
                                                            }}</span>
                                                    </div>
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.user_id" class="mt-1 text-xs text-destructive">
                                {{ form.errors.user_id }}
                            </p>
                        </div>

                        <!-- Selected User Display -->
                        <div v-if="selectedUser" class="rounded-lg border border-border bg-muted p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-foreground">{{ selectedUser.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ selectedUser.email }}</p>
                                </div>
                                <Badge variant="secondary">Selected</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <!-- Book Copy Selection -->
                <CardHeader>
                    <CardTitle>Select Book Copy</CardTitle>
                    <CardDescription>
                        Search for an available book copy by title, barcode, or ISBN
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <Label for="book_copy">
                                Book Copy <span class="text-destructive">*</span>
                            </Label>

                            <div class="search-container relative mt-2">
                                <div class="relative">
                                    <SearchIcon
                                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input v-model="copySearchQuery" @input="handleSearchChange"
                                        @focus="() => { if (copySearchQuery.length >= 2) showSearchDropdown.value = true }"
                                        type="text" placeholder="Search by title, barcode, ISBN..." class="pl-10"
                                        :class="{ 'border-destructive': form.errors.book_copy_id }" />
                                    <Loader2Icon v-if="isSearching"
                                        class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                                </div>

                                <!-- Custom Dropdown -->
                                <div v-if="showSearchDropdown"
                                    class="absolute z-50 mt-2 w-full rounded-md border border-border bg-popover shadow-md">
                                    <div v-if="isSearching" class="p-6 text-center text-sm text-muted-foreground">
                                        Searching...
                                    </div>

                                    <div v-else-if="copySearchResults.length === 0"
                                        class="p-6 text-center text-sm text-muted-foreground">
                                        <div v-if="copySearchQuery.length < 2">
                                            Type at least 2 characters to search
                                        </div>
                                        <div v-else>
                                            No available copies found
                                        </div>
                                    </div>

                                    <div v-else class="max-h-80 overflow-y-auto">
                                        <button v-for="copy in copySearchResults" :key="copy.id" type="button"
                                            @click="selectBookCopy(copy)"
                                            class="w-full p-3 text-left hover:bg-accent transition-colors border-b border-border last:border-0">
                                            <div class="flex items-start gap-3">
                                                <CheckIcon :class="cn(
                                                    'h-4 w-4 mt-1 shrink-0',
                                                    selectedCopy?.id === copy.id ? 'opacity-100' : 'opacity-0'
                                                )" />
                                                <div class="flex-1">
                                                    <div class="font-medium">{{ copy.book.title }}</div>
                                                    <div class="text-xs text-muted-foreground">
                                                        by {{ copy.book.author_name }}
                                                    </div>
                                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                        <code class="text-xs font-mono bg-muted px-1 rounded">
                                                                {{ copy.barcode.substring(0, 8) }}...
                                                            </code>
                                                        <Badge v-if="copy.call_number" variant="outline"
                                                            class="text-xs">
                                                            {{ copy.call_number }}
                                                        </Badge>
                                                        <Badge variant="default" class="text-xs">
                                                            {{ copy.status }}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.book_copy_id" class="mt-1 text-xs text-destructive">
                                {{ form.errors.book_copy_id }}
                            </p>
                        </div>

                        <!-- Selected Copy Display -->
                        <div v-if="selectedCopy" class="rounded-lg border border-border bg-muted p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-foreground">{{ selectedCopy.book.title }}</p>
                                    <p class="text-sm text-muted-foreground">by {{ selectedCopy.book.author_name }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <code class="text-xs font-mono bg-background px-2 py-1 rounded">
                                                {{ selectedCopy.barcode }}
                                            </code>
                                        <Badge v-if="selectedCopy.call_number" variant="outline">
                                            {{ selectedCopy.call_number }}
                                        </Badge>
                                        <Badge variant="secondary">
                                            {{ selectedCopy.condition }}
                                        </Badge>
                                        <Badge v-if="selectedCopy.location">
                                            {{ selectedCopy.location }}
                                        </Badge>
                                    </div>
                                </div>
                                <Button type="button" variant="ghost" size="sm" @click="clearCopySelection">
                                    Change
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <!-- Loan Details with Calendar -->
                <CardHeader>
                    <CardTitle>Loan Details</CardTitle>
                    <CardDescription>
                        Set the borrowed and due dates
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Borrowed Date with Calendar -->
                        <div>
                            <Label for="borrowed_date">
                                Borrowed Date <span class="text-destructive">*</span>
                            </Label>
                            <Popover v-model:open="showBorrowedCalendar">
                                <PopoverTrigger as-child>
                                    <Button variant="outline" :class="cn(
                                        'w-full justify-start text-left font-normal mt-2',
                                        form.errors.borrowed_date && 'border-destructive'
                                    )">
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{ formatDateDisplay(borrowedDate) }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0" align="start">
                                    <Calendar v-model="borrowedDate" @update:model-value="handleBorrowedDateChange"
                                        :max-value="new CalendarDate(2035, 12, 31)" class="rounded-md border" />
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.borrowed_date" class="mt-1 text-xs text-destructive">
                                {{ form.errors.borrowed_date }}
                            </p>
                        </div>

                        <!-- Due Date with Calendar -->
                        <div>
                            <Label for="due_date">
                                Due Date <span class="text-destructive">*</span>
                            </Label>
                            <Popover v-model:open="showDueCalendar">
                                <PopoverTrigger as-child>
                                    <Button variant="outline" :class="cn(
                                        'w-full justify-start text-left font-normal mt-2',
                                        !dueDate && 'text-muted-foreground',
                                        form.errors.due_date && 'border-destructive'
                                    )">
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{ formatDateDisplay(dueDate) }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0" align="start">
                                    <Calendar v-model="dueDate" @update:model-value="handleDueDateChange"
                                        :min-value="borrowedDate" :max-value="new CalendarDate(2035, 12, 31)"
                                        class="rounded-md border" />
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.due_date" class="mt-1 text-xs text-destructive">
                                {{ form.errors.due_date }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Default: 14 days from borrowed date
                            </p>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <Label for="notes">
                                Notes (Optional)
                            </Label>
                            <Textarea id="notes" v-model="form.notes"
                                placeholder="Add any additional notes about this loan..." rows="3" class="mt-2"
                                :class="{ 'border-destructive': form.errors.notes }" />
                            <p v-if="form.errors.notes" class="mt-1 text-xs text-destructive">
                                {{ form.errors.notes }}
                            </p>
                        </div>
                    </div>
                </CardContent>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 bg-background p-6">
                    <Button type="button" variant="outline" @click="cancel" :disabled="form.processing">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Creating Loan...</span>
                        <span v-else>Create Loan</span>
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>