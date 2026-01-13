<script setup lang="ts">
import { route } from "ziggy-js";
import { ref, reactive, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
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
import { CalendarIcon, SearchIcon, CheckIcon } from 'lucide-vue-next';
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
const openCopySearch = ref(false);
const selectedUser = ref<User | null>(null);
const selectedCopy = ref<BookCopy | null>(null);

const copySearchQuery = ref('');
const copySearchResults = ref<BookCopy[]>([]);
const isSearching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Computed
========================= */
const defaultDueDate = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 14); // Default 14 days
    return date.toISOString().split('T')[0];
});

// Set default due date
if (!form.due_date) {
    form.due_date = defaultDueDate.value;
}

/* =========================
   Methods
========================= */
const selectUser = (user: User) => {
    selectedUser.value = user;
    form.user_id = String(user.id);
    openUserSelect.value = false;
};

const searchBookCopies = () => {
    if (!copySearchQuery.value || copySearchQuery.value.length < 2) {
        copySearchResults.value = [];
        return;
    }

    if (searchTimeout) clearTimeout(searchTimeout);

    isSearching.value = true;

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(
                route('api.book-copies.search', { q: copySearchQuery.value })
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
    openCopySearch.value = false;
    copySearchQuery.value = '';
    copySearchResults.value = [];
};

const clearCopySelection = () => {
    selectedCopy.value = null;
    form.book_copy_id = '';
    copySearchResults.value = [];
};

const submitForm = () => {
    form.post(route('loans.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const cancel = () => {
    router.visit(route('loans.index'));
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
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

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">

                <!-- Borrower Selection -->
                <Card>
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
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            :class="cn(
                                                'w-full justify-between mt-2',
                                                !selectedUser && 'text-muted-foreground',
                                                form.errors.user_id && 'border-destructive'
                                            )"
                                        >
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
                                                    <CommandItem
                                                        v-for="user in props.users"
                                                        :key="user.id"
                                                        :value="user.name"
                                                        @select="() => selectUser(user)"
                                                    >
                                                        <CheckIcon
                                                            :class="cn(
                                                                'mr-2 h-4 w-4',
                                                                selectedUser?.id === user.id ? 'opacity-100' : 'opacity-0'
                                                            )"
                                                        />
                                                        <div class="flex flex-col">
                                                            <span class="font-medium">{{ user.name }}</span>
                                                            <span class="text-xs text-muted-foreground">{{ user.email }}</span>
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
                </Card>

                <!-- Book Copy Selection -->
                <Card>
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
                                <Popover v-model:open="openCopySearch">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            :class="cn(
                                                'w-full justify-between mt-2',
                                                !selectedCopy && 'text-muted-foreground',
                                                form.errors.book_copy_id && 'border-destructive'
                                            )"
                                        >
                                            <span v-if="selectedCopy">{{ selectedCopy.book.title }}</span>
                                            <span v-else>Search for book...</span>
                                            <SearchIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                        <Command>
                                            <CommandInput
                                                v-model="copySearchQuery"
                                                @input="searchBookCopies"
                                                placeholder="Search by title, barcode, ISBN..."
                                            />
                                            <CommandList>
                                                <CommandEmpty>
                                                    <div v-if="isSearching" class="py-6 text-center text-sm">
                                                        Searching...
                                                    </div>
                                                    <div v-else-if="copySearchQuery.length < 2" class="py-6 text-center text-sm">
                                                        Type at least 2 characters to search
                                                    </div>
                                                    <div v-else class="py-6 text-center text-sm">
                                                        No available copies found
                                                    </div>
                                                </CommandEmpty>
                                                <CommandGroup v-if="copySearchResults.length > 0">
                                                    <CommandItem
                                                        v-for="copy in copySearchResults"
                                                        :key="copy.id"
                                                        :value="copy.book.title"
                                                        @select="() => selectBookCopy(copy)"
                                                    >
                                                        <CheckIcon
                                                            :class="cn(
                                                                'mr-2 h-4 w-4',
                                                                selectedCopy?.id === copy.id ? 'opacity-100' : 'opacity-0'
                                                            )"
                                                        />
                                                        <div class="flex flex-col flex-1">
                                                            <span class="font-medium">{{ copy.book.title }}</span>
                                                            <span class="text-xs text-muted-foreground">
                                                                by {{ copy.book.author_name }}
                                                            </span>
                                                            <div class="mt-1 flex items-center gap-2">
                                                                <code class="text-xs font-mono bg-muted px-1 rounded">
                                                                    {{ copy.barcode.substring(0, 8) }}...
                                                                </code>
                                                                <Badge v-if="copy.call_number" variant="outline" class="text-xs">
                                                                    {{ copy.call_number }}
                                                                </Badge>
                                                                <Badge variant="default" class="text-xs">
                                                                    {{ copy.status }}
                                                                </Badge>
                                                            </div>
                                                        </div>
                                                    </CommandItem>
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                <p v-if="form.errors.book_copy_id" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.book_copy_id }}
                                </p>
                            </div>

                            <!-- Selected Copy Display -->
                            <div v-if="selectedCopy" class="rounded-lg border border-border bg-muted p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-foreground">{{ selectedCopy.book.title }}</p>
                                        <p class="text-sm text-muted-foreground">by {{ selectedCopy.book.author_name }}</p>
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
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="clearCopySelection"
                                    >
                                        Change
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Loan Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Loan Details</CardTitle>
                        <CardDescription>
                            Set the borrowed and due dates
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Borrowed Date -->
                            <div>
                                <Label for="borrowed_date">
                                    Borrowed Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="borrowed_date"
                                    v-model="form.borrowed_date"
                                    type="date"
                                    class="mt-2"
                                    :class="{ 'border-destructive': form.errors.borrowed_date }"
                                />
                                <p v-if="form.errors.borrowed_date" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.borrowed_date }}
                                </p>
                            </div>

                            <!-- Due Date -->
                            <div>
                                <Label for="due_date">
                                    Due Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    class="mt-2"
                                    :class="{ 'border-destructive': form.errors.due_date }"
                                />
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
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Add any additional notes about this loan..."
                                    rows="3"
                                    class="mt-2"
                                    :class="{ 'border-destructive': form.errors.notes }"
                                />
                                <p v-if="form.errors.notes" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.notes }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 rounded-xl border border-border bg-background p-6">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancel"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Creating Loan...</span>
                        <span v-else>Create Loan</span>
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
