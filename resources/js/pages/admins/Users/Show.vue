<script setup lang="ts">
import { route } from 'ziggy-js';
import { Link, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeftIcon, PowerIcon, UserIcon, BookOpenIcon, CalendarIcon } from 'lucide-vue-next';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    phone?: string;
    status: 'active' | 'inactive';
    created_at: string;
    roles: string[];
    member: any;
    staff: any;
    loans: { id: number; book_title: string; borrowed_date: string; due_date: string; status: string }[];
    room_bookings: { id: number; room_name: string; booking_date: string; status: string }[];
}

const props = defineProps<{ user: UserDetail }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'All Users', href: route('admin.users.index') },
    { title: props.user.name, href: '#' },
];

const toggleStatus = () => {
    router.patch(route('admin.users.toggle-status', props.user.id), {}, { preserveScroll: true });
};

const roleBadgeVariant = (role: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (role) {
        case 'Super Admin': return 'destructive';
        case 'Admin':       return 'default';
        case 'Librarian':   return 'secondary';
        default:            return 'outline';
    }
};

const loanStatusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'active')   return 'default';
    if (status === 'overdue')  return 'destructive';
    if (status === 'returned') return 'secondary';
    return 'outline';
};

const formatDate = (date?: string) =>
    date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">

            <FlashAlert />

            <!-- Header -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.users.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeftIcon class="h-4 w-4 mr-1" /> Back
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ user.name }}</h1>
                        <p class="text-muted-foreground text-sm">{{ user.email }}</p>
                    </div>
                </div>

                <Button
                    :variant="user.status === 'active' ? 'outline' : 'default'"
                    size="sm"
                    @click="toggleStatus"
                >
                    <PowerIcon class="h-4 w-4 mr-2" />
                    {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                </Button>
            </div>

            <!-- Info Cards row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Profile -->
                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <UserIcon class="h-4 w-4" /> Profile
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status</span>
                            <Badge :variant="user.status === 'active' ? 'default' : 'secondary'">
                                {{ user.status }}
                            </Badge>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ user.phone ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Joined</span>
                            <span>{{ formatDate(user.created_at) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1 pt-2">
                            <Badge
                                v-for="role in user.roles"
                                :key="role"
                                :variant="roleBadgeVariant(role)"
                            >
                                {{ role }}
                            </Badge>
                        </div>

                        <!-- Member details -->
                        <template v-if="user.member">
                            <div class="border-t pt-3 space-y-2">
                                <p class="font-semibold text-xs uppercase text-muted-foreground tracking-wider">Member Details</p>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Membership</span>
                                    <span class="capitalize">{{ user.member.membership_type }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Expires</span>
                                    <span>{{ formatDate(user.member.membership_expiry_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Max Books</span>
                                    <span>{{ user.member.max_books_allowed }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- Staff details -->
                        <template v-if="user.staff">
                            <div class="border-t pt-3 space-y-2">
                                <p class="font-semibold text-xs uppercase text-muted-foreground tracking-wider">Staff Details</p>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Position</span>
                                    <span>{{ user.staff.position }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Hired</span>
                                    <span>{{ formatDate(user.staff.hire_date) }}</span>
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Loans -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BookOpenIcon class="h-4 w-4" /> Loan History ({{ user.loans.length }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="user.loans.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No loans found.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Book</TableHead>
                                        <TableHead>Borrowed</TableHead>
                                        <TableHead>Due</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="loan in user.loans" :key="loan.id">
                                        <TableCell class="font-medium text-sm">{{ loan.book_title }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(loan.borrowed_date) }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(loan.due_date) }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="loanStatusVariant(loan.status)" class="capitalize">
                                                {{ loan.status }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Room Bookings -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <CalendarIcon class="h-4 w-4" /> Room Bookings ({{ user.room_bookings.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="user.room_bookings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No room bookings found.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Room</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="booking in user.room_bookings" :key="booking.id">
                                    <TableCell class="font-medium text-sm">{{ booking.room_name }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(booking.booking_date) }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="booking.status === 'confirmed' ? 'default' : booking.status === 'cancelled' ? 'destructive' : 'secondary'"
                                            class="capitalize"
                                        >
                                            {{ booking.status }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
