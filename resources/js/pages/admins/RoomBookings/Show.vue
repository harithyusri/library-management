<script setup lang="ts">
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import {
    CalendarDays, Clock, DoorOpen, User, FileText,
    Pencil, Trash2, CircleDollarSign, CheckCircle2,
    XCircle, RotateCcw, ImageOff,
} from 'lucide-vue-next';

interface Booking {
    id: number;
    room: {
        id: number;
        name: string;
        room_number: string;
        floor: number | null;
        capacity: number;
        hourly_rate: number;
        image_url: string | null;
    };
    user: { id: number; name: string; email: string };
    booking_date: string;
    start_time: string;
    end_time: string;
    duration_hours: number;
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
    purpose: string | null;
    notes: string | null;
    cancellation_reason: string | null;
    cancelled_at: string | null;
    total_cost: number;
    created_at: string;
}

const props = defineProps<{
    booking: Booking;
    can: { editBookings: boolean; deleteBookings: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Room Bookings', href: route('admin.room-bookings.index') },
    { title: `Booking #${props.booking.id}`, href: '#' },
];

const statusConfig: Record<string, {
    label: string;
    variant: 'default' | 'secondary' | 'destructive' | 'outline';
    icon: typeof CheckCircle2;
    class: string;
}> = {
    confirmed: { label: 'Confirmed', variant: 'default',     icon: CheckCircle2, class: 'text-emerald-600' },
    pending:   { label: 'Pending',   variant: 'outline',     icon: RotateCcw,    class: 'text-amber-600'  },
    cancelled: { label: 'Cancelled', variant: 'destructive', icon: XCircle,      class: 'text-red-600'    },
    completed: { label: 'Completed', variant: 'secondary',   icon: CheckCircle2, class: 'text-slate-500'  },
};

const currentStatus = computed(() => statusConfig[props.booking.status] ?? statusConfig.pending);
const dateFormatted = computed(() =>
    new Date(props.booking.booking_date + 'T00:00:00')
        .toLocaleDateString('en-MY', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);

const deleteBooking = () => {
    if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
        router.delete(route('admin.room-bookings.destroy', props.booking.id));
    }
};

const isCancelDialogOpen = ref(false);
const cancelReason = ref('');

const confirmCancellation = () => {
    if (!cancelReason.value.trim()) return;

    router.patch(route('admin.room-bookings.status', props.booking.id), {
        status: 'cancelled',
        cancellation_reason: cancelReason.value
    }, {
        onSuccess: () => {
            isCancelDialogOpen.value = false;
            cancelReason.value = '';
        }
    });
};

const updateStatus = (status: string) => {
    if (status === 'cancelled') {
        isCancelDialogOpen.value = true;
        return;
    }

    router.patch(route('admin.room-bookings.status', props.booking.id), {
        status,
        cancellation_reason: null
    });
};
</script>

<template>
    <Head :title="`Booking #${booking.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 space-y-6">
            <FlashAlert />

            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 mb-1">
                        <h1 class="text-2xl font-bold tracking-tight">Booking #{{ booking.id }}</h1>
                        <Badge :variant="currentStatus.variant" class="gap-1">
                            <component :is="currentStatus.icon" class="h-3 w-3" />
                            {{ currentStatus.label }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Created {{ new Date(booking.created_at).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link v-if="can.editBookings && booking.status === 'pending'" :href="route('admin.room-bookings.edit', booking.id)">
                        <Button size="sm" variant="outline" class="gap-1.5">
                            <Pencil class="h-4 w-4" /> Edit
                        </Button>
                    </Link>
                    <Button v-if="can.deleteBookings" size="sm" variant="destructive" class="gap-1.5" @click="deleteBooking">
                        <Trash2 class="h-4 w-4" /> Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Booking Details Card -->
                    <Card>
                        <CardHeader class="pt-4">
                            <CardTitle class="text-base">Booking Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 py-2 pb-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium flex items-center gap-1.5">
                                        <CalendarDays class="h-3.5 w-3.5" /> Date
                                    </p>
                                    <p class="text-sm font-medium">{{ dateFormatted }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5" /> Time
                                    </p>
                                    <p class="text-sm font-medium">{{ booking.start_time }} – {{ booking.end_time }}</p>
                                    <p class="text-xs text-muted-foreground">{{ booking.duration_hours }}h duration</p>
                                </div>
                                <div class="space-y-3">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium flex items-center gap-1.5">
                                        <User class="h-3.5 w-3.5" />Booked For
                                    </p>
                                    <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/20">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                            {{ booking.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold">{{ booking.user.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ booking.user.email }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium flex items-center gap-1.5">
                                        <CircleDollarSign class="h-3.5 w-3.5" /> Total Cost
                                    </p>
                                    <p class="text-sm font-bold">RM {{ Number(booking.total_cost).toFixed(2) }}</p>
                                    <p class="text-xs text-muted-foreground">RM {{ Number(booking.room.hourly_rate).toFixed(2) }}/hr × {{ booking.duration_hours }}h</p>
                                </div>
                            </div>

                            <Separator />

                            <div v-if="booking.purpose" class="space-y-1">
                                <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium flex items-center gap-1.5">
                                    <FileText class="h-3.5 w-3.5" /> Purpose
                                </p>
                                <p class="text-sm">{{ booking.purpose }}</p>
                            </div>

                            <div v-if="booking.notes" class="space-y-1">
                                <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Notes</p>
                                <p class="text-sm text-muted-foreground whitespace-pre-line">{{ booking.notes }}</p>
                            </div>

                            <div v-if="booking.status === 'cancelled' && booking.cancellation_reason" class="space-y-1 p-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20">
                                <p class="text-xs text-red-800 dark:text-red-300 uppercase tracking-wide font-medium flex items-center gap-1.5">
                                    <XCircle class="h-3.5 w-3.5" /> Cancellation Reason
                                </p>
                                <p class="text-sm text-red-700 dark:text-red-400">{{ booking.cancellation_reason }}</p>
                                <p v-if="booking.cancelled_at" class="text-[10px] text-red-600/70 dark:text-red-400/50 mt-1">
                                    Cancelled on {{ new Date(booking.cancelled_at).toLocaleString('en-MY', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Actions -->
                    <Card v-if="can.editBookings && booking.status !== 'completed'">
                        <CardHeader>
                            <CardTitle class="text-base pt-4">Update Status</CardTitle>
                            <CardDescription>Change the current booking status.</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-wrap gap-2 pb-6">
                            <Button
                                v-if="booking.status === 'pending'"
                                size="sm" variant="default" class="gap-1.5"
                                @click="updateStatus('confirmed')"
                            >
                                <CheckCircle2 class="h-4 w-4" /> Confirm
                            </Button>
                            <Button
                                v-if="booking.status === 'confirmed'"
                                size="sm" variant="secondary" class="gap-1.5"
                                @click="updateStatus('completed')"
                            >
                                <CheckCircle2 class="h-4 w-4" /> Mark Completed
                            </Button>
                            <Button
                                v-if="booking.status !== 'cancelled'"
                                size="sm" variant="destructive" class="gap-1.5"
                                @click="updateStatus('cancelled')"
                            >
                                <XCircle class="h-4 w-4" /> Cancel Booking
                            </Button>
                            <Button
                                v-if="booking.status === 'cancelled'"
                                size="sm" variant="outline" class="gap-1.5"
                                @click="updateStatus('pending')"
                            >
                                <RotateCcw class="h-4 w-4" /> Restore to Pending
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Room Info Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <div v-if="booking.room.image_url" class="h-36 overflow-hidden rounded-t-lg">
                            <img :src="booking.room.image_url" :alt="booking.room.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="h-36 bg-muted rounded-t-lg flex items-center justify-center">
                            <ImageOff class="h-6 w-6 text-muted-foreground opacity-40" />
                        </div>
                        <CardHeader class="pt-4 pb-2">
                            <CardTitle class="text-sm">{{ booking.room.name }}</CardTitle>
                            <CardDescription>{{ booking.room.room_number }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2 pb-5">
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Floor</span>
                                <span class="font-medium">{{ booking.room.floor === null ? '—' : booking.room.floor === 0 ? 'Ground' : booking.room.floor }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Capacity</span>
                                <span class="font-medium">{{ booking.room.capacity }} pax</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-muted-foreground">Hourly Rate</span>
                                <span class="font-medium">RM {{ Number(booking.room.hourly_rate).toFixed(2) }}</span>
                            </div>
                            <Separator class="my-2" />
                            <Link :href="route('admin.rooms.show', booking.room.id)">
                                <Button variant="outline" size="sm" class="w-full gap-1.5 text-xs">
                                    <DoorOpen class="h-3.5 w-3.5" /> View Room
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Back -->
            <div class="pb-4">
                <Link :href="route('admin.room-bookings.index')">
                    <Button variant="outline">&larr; Back to Bookings</Button>
                </Link>
            </div>
        </div>

        <!-- Cancellation Dialog -->
        <Dialog v-model:open="isCancelDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel Booking</DialogTitle>
                    <DialogDescription>
                        Please provide a reason for cancelling this booking. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="cancellation_reason" class="text-sm font-medium">Reason for Cancellation</Label>
                        <Textarea
                            id="cancellation_reason"
                            v-model="cancelReason"
                            placeholder="e.g., Scheduling conflict, User requested cancellation..."
                            rows="4"
                            class="resize-none"
                        />
                    </div>
                </div>
                <DialogFooter class="flex sm:justify-between gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="isCancelDialogOpen = false"
                    >
                        Keep Booking
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        class="gap-1.5"
                        :disabled="!cancelReason.trim()"
                        @click="confirmCancellation"
                    >
                        <XCircle class="h-4 w-4" /> Confirm Cancellation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>