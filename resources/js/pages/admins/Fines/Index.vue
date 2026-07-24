<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Search, Receipt, CheckCircle2, AlertCircle, Upload, FileText, ExternalLink } from 'lucide-vue-next';
import { useDebounceFn } from '@vueuse/core';
import { route } from 'ziggy-js';

interface Fine {
    id: number;
    member_name: string;
    book_title: string;
    due_date: string;
    returned_date: string | null;
    fine_amount: number;
    fine_paid: boolean;
    fine_paid_amount: number | null;
    fine_receipt_path: string | null;
    status: string;
}

const props = defineProps<{
    fines: Fine[];
    filters: {
        search: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Fines & Payments', href: route('admin.fines.index') },
];

const searchQuery = ref(props.filters.search || '');
const isPaymentDialogOpen = ref(false);
const selectedFine = ref<Fine | null>(null);

const form = useForm({
    fine_paid_amount: 0,
    fine_receipt: null as File | null,
});

const settledFines = computed(() => props.fines.filter(f => f.fine_paid));
const partialFines = computed(() => props.fines.filter(f => !f.fine_paid && (f.fine_paid_amount || 0) > 0));
const unpaidFines = computed(() => props.fines.filter(f => !f.fine_paid && (!f.fine_paid_amount || f.fine_paid_amount == 0)));

const handleSearch = useDebounceFn(() => {
    router.get(route('admin.fines.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true
    });
}, 300);

watch(searchQuery, () => {
    handleSearch();
});

const openPaymentDialog = (fine: Fine) => {
    selectedFine.value = fine;
    const remaining = Number((fine.fine_amount - (fine.fine_paid_amount || 0)).toFixed(2));
    form.fine_paid_amount = remaining;
    form.fine_receipt = null;
    isPaymentDialogOpen.value = true;
};

const submitPayment = () => {
    if (!selectedFine.value) return;

    form.patch(route('admin.fines.pay', selectedFine.value.id), {
        onSuccess: () => {
            isPaymentDialogOpen.value = false;
            form.reset();
        },
    });
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.length) {
        form.fine_receipt = target.files[0];
    }
};
</script>

<template>
    <Head title="Fines & Payments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-border">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-foreground">Fines & Payments <span class="text-primary text-6xl leading-none">.</span></h1>
                    <p class="text-yellow-800 font-medium">Monitor overdue penalties and manage member payment records.</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full md:w-80 h-11">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search by member name..."
                            class="pl-11 h-full bg-background border-input rounded-xl focus-visible:ring-ring"
                        />
                    </div>
                </div>
            </div>

            <Tabs default-value="active" class="w-full">
                <TabsList class="grid w-[450px] grid-cols-3">
                    <TabsTrigger value="active">Unpaid ({{ unpaidFines.length }})</TabsTrigger>
                    <TabsTrigger value="partial">Partial ({{ partialFines.length }})</TabsTrigger>
                    <TabsTrigger value="settled">Settled ({{ settledFines.length }})</TabsTrigger>
                </TabsList>

                <TabsContent value="active" class="mt-6 border rounded-lg bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member</TableHead>
                                <TableHead>Book</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead>Fine (RM)</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="fine in unpaidFines" :key="fine.id" class="group">
                                <TableCell class="font-medium">{{ fine.member_name }}</TableCell>
                                <TableCell>{{ fine.book_title }}</TableCell>
                                <TableCell>{{ fine.due_date }}</TableCell>
                                <TableCell>
                                    <span class="text-red-600 font-semibold">{{ Number(fine.fine_amount).toFixed(2) }}</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="h-8 gap-1.5"
                                        @click="openPaymentDialog(fine)"
                                    >
                                        Mark Paid
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="unpaidFines.length === 0">
                                <TableCell colspan="5" class="h-32 text-center text-muted-foreground">
                                    No unpaid fines found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </TabsContent>

                <TabsContent value="partial" class="mt-6 border rounded-lg bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member</TableHead>
                                <TableHead>Book</TableHead>
                                <TableHead>Total Fine</TableHead>
                                <TableHead>Paid</TableHead>
                                <TableHead>Remaining</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="fine in partialFines" :key="fine.id" class="group">
                                <TableCell class="font-medium">{{ fine.member_name }}</TableCell>
                                <TableCell>{{ fine.book_title }}</TableCell>
                                <TableCell>RM {{ Number(fine.fine_amount).toFixed(2) }}</TableCell>
                                <TableCell class="text-indigo-600 font-semibold">RM {{ Number(fine.fine_paid_amount).toFixed(2) }}</TableCell>
                                <TableCell class="text-red-600 font-bold">
                                    RM {{ (Number(fine.fine_amount) - Number(fine.fine_paid_amount)).toFixed(2) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="h-8 gap-1.5"
                                        @click="openPaymentDialog(fine)"
                                    >
                                        Record Payment
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="partialFines.length === 0">
                                <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                                    No partial payments found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </TabsContent>

                <TabsContent value="settled" class="mt-6 border rounded-lg bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Member</TableHead>
                                <TableHead>Book</TableHead>
                                <TableHead>Settled On</TableHead>
                                <TableHead>Amount (RM)</TableHead>
                                <TableHead>Receipt</TableHead>
                                <TableHead class="text-right">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="fine in settledFines" :key="fine.id">
                                <TableCell class="font-medium">{{ fine.member_name }}</TableCell>
                                <TableCell>{{ fine.book_title }}</TableCell>
                                <TableCell>{{ fine.returned_date || fine.due_date }}</TableCell>
                                <TableCell>{{ Number(fine.fine_paid_amount || fine.fine_amount).toFixed(2) }}</TableCell>
                                <TableCell>
                                    <a
                                        v-if="fine.fine_receipt_path"
                                        :href="fine.fine_receipt_path"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"
                                    >
                                        <FileText class="h-3 w-3" /> View
                                        <ExternalLink class="h-3 w-3" />
                                    </a>
                                    <span v-else class="text-xs text-muted-foreground italic">None</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Badge variant="secondary" class="gap-1.5">
                                        <CheckCircle2 class="h-3 w-3" /> Paid
                                    </Badge>
                                    <div class="mt-2 text-right">
                                        <a
                                            :href="route('admin.fines.receipt', fine.id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700 underline"
                                        >
                                            Download Receipt
                                        </a>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="settledFines.length === 0">
                                <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                                    No settled fines found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Payment Dialog -->
        <Dialog :open="isPaymentDialogOpen" @update:open="isPaymentDialogOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Mark Fine as Paid</DialogTitle>
                    <DialogDescription>
                        Enter the amount paid by the member and upload the receipt if available.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPayment" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center mb-1">
                            <Label for="amount">Paid Amount (RM)</Label>
                            <span v-if="selectedFine" class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Remaining: RM {{ (Number(selectedFine.fine_amount) - Number(selectedFine.fine_paid_amount || 0)).toFixed(2) }}
                            </span>
                        </div>
                        <Input
                            id="amount"
                            type="number"
                            step="0.01"
                            v-model="form.fine_paid_amount"
                            required
                        />
                        <p v-if="form.errors.fine_paid_amount" class="text-xs text-red-600">{{ form.errors.fine_paid_amount }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="receipt">Receipt (Optional)</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="receipt"
                                type="file"
                                accept="image/*,application/pdf"
                                @change="onFileChange"
                                class="cursor-pointer"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">Max size: 2MB. Supports JPG, PNG, PDF.</p>
                        <p v-if="form.errors.fine_receipt" class="text-xs text-red-600">{{ form.errors.fine_receipt }}</p>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="ghost" @click="isPaymentDialogOpen = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Confirm Payment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
