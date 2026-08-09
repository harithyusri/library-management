<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Receipt, CreditCard, ChevronRight, AlertCircle, CheckCircle2, ShieldCheck, Lock, BadgeCheck } from 'lucide-vue-next';
import { route } from 'ziggy-js';

interface Fine {
    id: number;
    book_title: string;
    due_date: string;
    fine_amount: number;
    fine_paid: boolean;
    fine_paid_amount: number;
    remaining_amount: number;
    status: 'unpaid' | 'partial' | 'settled';
}

defineProps<{
    fines: Fine[];
    total_unpaid: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Fines', href: route('member.fines.index') },
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'settled': return { variant: 'secondary' as const, label: 'Settled', icon: CheckCircle2 };
        case 'partial': return { variant: 'outline' as const, label: 'Partially Paid', icon: CreditCard };
        default: return { variant: 'destructive' as const, label: 'Unpaid', icon: AlertCircle };
    }
};
</script>

<template>
    <Head title="My Fines" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <FlashAlert />

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">My Fines & Penalties</h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">Track your outstanding dues and settle them securely.</p>
            </section>

            <!-- Summary Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="md:col-span-2 overflow-hidden border-none shadow-2xl bg-gradient-to-br from-[#0d1a14] to-[#1a2e26] text-white relative group py-6 md:py-4">
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-700"></div>
                    <CardHeader class="relative z-10 pl-6">
                        <CardTitle class="text-[#f1f5f9]/80 font-medium text-sm md:text-base">Total Outstanding</CardTitle>
                        <div class="flex items-baseline gap-2 pt-1 md:pt-2">
                            <span class="text-4xl md:text-5xl font-black tracking-tighter">RM {{ total_unpaid.toFixed(2) }}</span>
                        </div>
                    </CardHeader>
                    <CardContent class="relative z-10 pt-2 md:pt-4">
                        <p class="text-[#f1f5f9]/50 text-xs md:text-sm leading-relaxed max-w-xs">
                            Please settle your fines to avoid restrictions on book borrowing and room reservations.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-[#c5a059]/20 bg-[#c5a059]/5 py-2 md:py-4 hidden md:block">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2 text-[#0d1a14] pl-6">
                            <CreditCard class="h-5 w-5" /> Secured by Stripe
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- <p class="text-xs text-slate-600 leading-relaxed">
                            All transactions are encrypted and processed securely. We don't store your card details.
                        </p> -->
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2.5 text-xs text-slate-500">
                                <div class="h-6 w-6 rounded-lg bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                    <ShieldCheck class="h-3.5 w-3.5 text-[#c5a059]" />
                                </div>
                                <span>256-bit SSL encryption on all payments</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-xs text-slate-500">
                                <div class="h-6 w-6 rounded-lg bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                    <Lock class="h-3.5 w-3.5 text-[#c5a059]" />
                                </div>
                                <span>Card details never stored on our servers</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-xs text-slate-500">
                                <div class="h-6 w-6 rounded-lg bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                    <BadgeCheck class="h-3.5 w-3.5 text-[#c5a059]" />
                                </div>
                                <span>PCI DSS compliant payment processing</span>
                            </div>
                        </div>
                        <div class="pt-1 border-t border-[#c5a059]/20">
                            <p class="text-[10px] text-slate-400 font-medium">Powered by <span class="font-bold text-[#c5a059]">Stripe</span> — trusted by millions worldwide.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Fines Table (Shown on Desktop) -->
            <div class="hidden md:block space-y-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Payment History & Dues</h2>
                <p class="text-sm text-muted-foreground">A complete list of your current and past penalties.</p>
            </div>
            <Card class="border shadow-lg overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50/30">
                            <TableHead class="w-[350px]">Book Details</TableHead>
                            <TableHead>Due Date</TableHead>
                            <TableHead>Amount (RM)</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="fine in fines" :key="fine.id" class="hover:bg-slate-50/50 transition-colors group">
                            <TableCell>
                                <div class="font-bold text-slate-900">{{ fine.book_title }}</div>
                                <div class="text-xs text-slate-400 font-medium tracking-wide">Ref #LOAN-{{ fine.id.toString().padStart(4, '0') }}</div>
                            </TableCell>
                            <TableCell class="text-slate-600 font-medium">{{ fine.due_date }}</TableCell>
                            <TableCell>
                                <div class="font-bold text-slate-900">RM {{ fine.fine_amount.toFixed(2) }}</div>
                                <div v-if="fine.fine_paid_amount > 0" class="text-[10px] text-[#c5a059] font-black uppercase">
                                    Paid RM {{ fine.fine_paid_amount.toFixed(2) }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadge(fine.status).variant" class="gap-1.5 py-1 px-3">
                                    <component :is="getStatusBadge(fine.status).icon" class="h-3.5 w-3.5" />
                                    {{ getStatusBadge(fine.status).label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('member.fines.show', fine.id)">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 px-3 gap-1 text-xs font-semibold border-slate-200 hover:border-[#c5a059] hover:text-[#c5a059] transition-all"
                                        >
                                            Details <ChevronRight class="h-3.5 w-3.5" />
                                        </Button>
                                    </Link>
                                    <Link v-if="!fine.fine_paid" :href="route('member.fines.show', fine.id)">
                                        <Button
                                            size="sm"
                                            class="h-8 px-3 text-xs font-bold bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] transition-all"
                                        >
                                            Pay Now
                                        </Button>
                                    </Link>
                                    <a v-if="fine.fine_paid" :href="route('member.fines.receipt', fine.id)" target="_blank">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-8 px-3 text-xs font-bold border-[#c5a059]/30 text-[#c5a059] hover:bg-[#c5a059]/10 transition-all"
                                        >
                                            Receipt
                                        </Button>
                                    </a>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="fines.length === 0">
                            <TableCell colspan="5" class="h-48 text-center bg-slate-50/20">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center">
                                        <CheckCircle2 class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <div class="space-y-1">
                                        <h3 class="font-bold text-slate-900">No Fines Found</h3>
                                        <p class="text-sm text-slate-500">You don't have any outstanding or past fines. Good job!</p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
            </div>

            <!-- Mobile List (Shown on mobile) -->
            <div class="md:hidden space-y-4">
                <h2 class="text-lg font-bold text-slate-800 px-1">Payment History & Dues</h2>
                <div v-for="fine in fines" :key="'mob-' + fine.id" class="bg-white rounded-2xl border shadow-sm overflow-hidden p-5 space-y-4">
                    <div class="flex justify-between items-start border-b pb-3">
                        <div>
                            <div class="font-black text-slate-900 leading-tight">{{ fine.book_title }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Ref #LOAN-{{ fine.id.toString().padStart(4, '0') }}</div>
                        </div>
                        <Badge :variant="getStatusBadge(fine.status).variant" class="gap-1 py-1 px-2 text-[10px]">
                            <component :is="getStatusBadge(fine.status).icon" class="h-3 w-3" />
                            {{ getStatusBadge(fine.status).label }}
                        </Badge>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Due Date</div>
                            <div class="text-sm font-bold text-slate-600">{{ fine.due_date }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</div>
                            <div class="text-sm font-black text-slate-900">RM {{ fine.fine_amount.toFixed(2) }}</div>
                            <div v-if="fine.fine_paid_amount > 0" class="text-[9px] text-[#c5a059] font-bold uppercase">
                                Paid RM {{ fine.fine_paid_amount.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col gap-2">
                        <Link 
                            v-if="!fine.fine_paid"
                            :href="route('member.fines.show', fine.id)"
                            class="w-full inline-flex items-center justify-center rounded-xl h-11 text-sm font-black bg-[#0d1a14] text-[#f1f5f9] hover:bg-[#122010] transition-all active:scale-95"
                        >
                            Pay RM {{ fine.remaining_amount.toFixed(2) }}
                        </Link>
                        <div class="flex gap-2" :class="{ 'w-full': fine.fine_paid }">
                             <Link 
                                :href="route('member.fines.show', fine.id)"
                                class="flex-1 inline-flex items-center justify-center rounded-xl h-11 text-sm font-bold bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition-all active:scale-95"
                            >
                                Details
                            </Link>
                             <a
                                v-if="fine.fine_paid"
                                :href="route('member.fines.receipt', fine.id)"
                                target="_blank"
                                class="flex-1 inline-flex items-center justify-center rounded-xl h-11 text-sm font-bold bg-[#c5a059]/10 text-[#0d1a14] border border-[#c5a059]/20 hover:bg-[#c5a059]/20 transition-all active:scale-95 gap-2"
                            >
                                <Receipt class="h-4 w-4" />
                                Receipt
                            </a>
                        </div>
                    </div>
                </div>

                <div v-if="fines.length === 0" class="text-center py-12 bg-slate-50/50 rounded-2xl border border-dashed">
                    <CheckCircle2 class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                    <p class="text-slate-500 font-medium">All clear! No fines found.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
