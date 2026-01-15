<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { UserPlusIcon, UserIcon, TrashIcon, EditIcon, BookOpenIcon, AlertCircleIcon } from 'lucide-vue-next';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Members',
        href: route('members.index'),
    },
];

/* =========================
   Types
========================= */
interface Member {
    id: number;
    name: string;
    email: string;
    phone?: string;
    status: 'active' | 'inactive' | 'suspended';
    active_loans_count: number;
    overdue_loans_count: number;
    total_fines: number;
    created_at: string;
}

interface PaginatedMembers {
    data: Member[];
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
    members: PaginatedMembers;
    filters: Record<string, any>;
    can: Record<string, boolean>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
    has_overdue: props.filters?.has_overdue ?? 'all',
});

const memberToDelete = ref<Member | null>(null);
const showDeleteDialog = ref(false);

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
    router.get(route('members.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.status = 'all';
    searchForm.has_overdue = 'all';
    search();
};

const confirmDelete = (member: Member) => {
    memberToDelete.value = member;
    showDeleteDialog.value = true;
};

const deleteMember = () => {
    if (!memberToDelete.value) return;

    router.delete(route('members.destroy', memberToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            memberToDelete.value = null;
        },
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return { variant: 'default' as const, label: 'Active' };
        case 'inactive':
            return { variant: 'secondary' as const, label: 'Inactive' };
        case 'suspended':
            return { variant: 'destructive' as const, label: 'Suspended' };
        default:
            return { variant: 'outline' as const, label: status };
    }
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatCurrency = (amount?: number): string => {
    if (!amount) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};
</script>

<template>
    <Head title="Members" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Members / Borrowers
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage library members and borrowers
                    </p>
                </div>

                <Link v-if="can.createUsers" :href="route('members.create')">
                    <Button>
                        <UserPlusIcon class="mr-2 h-4 w-4" />
                        Add Member
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-6">
                    <div class="grid gap-4 md:grid-cols-5 items-end">

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                            <Input
                                v-model="searchForm.search"
                                @input="debounceSearch"
                                placeholder="Search by name, email, or phone..."
                            />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                            <Select v-model="searchForm.status" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="suspended">Suspended</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground">Overdue</label>
                            <Select v-model="searchForm.has_overdue" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="All" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Members</SelectItem>
                                    <SelectItem value="yes">Has Overdue</SelectItem>
                                    <SelectItem value="no">No Overdue</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Button @click="clearFilters" variant="outline" class="w-full">
                                Clear
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Members Table -->
            <Card>
                <CardContent class="p-0">
                    <!-- Empty State -->
                    <div v-if="members.data.length === 0" class="py-12 text-center">
                        <UserIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-sm font-medium text-foreground">
                            No members found
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            No members match your search criteria.
                        </p>
                        <div class="mt-4 flex justify-center gap-2">
                            <Button variant="outline" @click="clearFilters">
                                Clear Filters
                            </Button>
                            <Link v-if="can.createUsers" :href="route('members.create')">
                                <Button>
                                    <UserPlusIcon class="mr-2 h-4 w-4" />
                                    Add Member
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Contact</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Active Loans</TableHead>
                                    <TableHead>Overdue</TableHead>
                                    <TableHead>Total Fines</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="member in members.data" :key="member.id">
                                    <!-- Member Name -->
                                    <TableCell>
                                        <div class="font-medium">{{ member.name }}</div>
                                    </TableCell>

                                    <!-- Contact -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">{{ member.email }}</div>
                                        <div v-if="member.phone" class="text-xs text-muted-foreground">
                                            {{ member.phone }}
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(member.status).variant">
                                            {{ getStatusBadge(member.status).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Active Loans -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <BookOpenIcon class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">{{ member.active_loans_count }}</span>
                                        </div>
                                    </TableCell>

                                    <!-- Overdue Loans -->
                                    <TableCell>
                                        <div v-if="member.overdue_loans_count > 0" class="flex items-center gap-2">
                                            <AlertCircleIcon class="h-4 w-4 text-destructive" />
                                            <span class="font-medium text-destructive">
                                                {{ member.overdue_loans_count }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>

                                    <!-- Total Fines -->
                                    <TableCell>
                                        <span :class="member.total_fines > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                                            {{ formatCurrency(member.total_fines) }}
                                        </span>
                                    </TableCell>

                                    <!-- Joined Date -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(member.created_at) }}
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('members.show', member.id)">
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                            <Link v-if="can.editUsers" :href="route('members.edit', member.id)">
                                                <Button variant="ghost" size="sm">
                                                    <EditIcon class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="can.deleteUsers"
                                                variant="ghost"
                                                size="sm"
                                                @click="confirmDelete(member)"
                                            >
                                                <TrashIcon class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="members.last_page > 1" class="rounded-xl border border-border bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing page {{ members.current_page }} of {{ members.last_page }} ({{ members.total }} total)
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="(link, index) in members.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50'
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the member <strong>{{ memberToDelete?.name }}</strong>.
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="deleteMember" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </AppLayout>
</template>