<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter
} from '@/components/ui/card';
import { 
    Receipt, 
    CreditCard, 
    TriangleAlert,
    BookmarkX,
    BookOpen, 
    CheckCircle2, 
    ShieldCheck,
    Loader2,
    Info,
    ChevronDown,
    Copy,
    Check
} from 'lucide-vue-next';

interface Payment {
    id: number;
    amount: number;
    method: string;
    date: string;
    stripe_id: string;
}

interface FineDetail {
    id: number;
    book: {
        title: string;
        cover: string | null;
        publisher: string;
    };
    borrowed_date: string;
    due_date: string;
    returned_date: string | null;
    fine_amount: number;
    fine_paid: boolean;
    fine_paid_amount: number;
    remaining_amount: number;
    status: 'unpaid' | 'partial' | 'settled';
    payments?: Payment[]; // New field
}

const props = defineProps<{
    fine: FineDetail;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Fines', href: '/member/fines' },
    { title: `Ref #LOAN-{{ props.fine.id }}`, href: `/member/fines/${props.fine.id}` },
];

const daysLate = computed(() => {
    const dueDate = new Date(props.fine.due_date);
    const comparisonDate = props.fine.returned_date ? new Date(props.fine.returned_date) : new Date();
    
    const diff = comparisonDate.getTime() - dueDate.getTime();
    return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)));
});

const paymentPercentage = computed(() => {
    if (props.fine.fine_amount <= 0) return 0;
    return Math.min(100, Math.round((props.fine.fine_paid_amount / props.fine.fine_amount) * 100));
});

const isPaying = ref(false);
const paymentAmount = ref(props.fine.remaining_amount);

const handlePayment = () => {
    if (paymentAmount.value < 1) return;
    
    isPaying.value = true;
    router.post(`/member/fines/${props.fine.id}/pay`, {
        amount: paymentAmount.value
    }, {
        onFinish: () => {
            isPaying.value = false;
        }
    });
};

const expandedPaymentId = ref<number | null>(null);

const toggleExpand = (id: number) => {
    expandedPaymentId.value = expandedPaymentId.value === id ? null : id;
};

const copiedId = ref<string | null>(null);
const handleCopyId = (stripeId: string) => {
    if (!stripeId) return;
    
    // Fallback for non-secure contexts (http)
    if (!navigator.clipboard) {
        const textArea = document.createElement("textarea");
        textArea.value = stripeId;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            copiedId.value = stripeId;
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        document.body.removeChild(textArea);
    } else {
        navigator.clipboard.writeText(stripeId).then(() => {
            copiedId.value = stripeId;
        }).catch(err => {
            console.error('Clipboard API failed', err);
        });
    }

    if (copiedId.value) {
        setTimeout(() => {
            copiedId.value = null;
        }, 2000);
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'settled': return { variant: 'secondary' as const, label: 'Settled', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
        case 'partial': return { variant: 'outline' as const, label: 'Partially Paid', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default: return { variant: 'destructive' as const, label: 'Unpaid', class: 'bg-rose-50 text-rose-700 border-rose-200' };
    }
};

// Local component for Stripe-like logo
const StripeLogo = {
    template: `<svg viewBox="0 0 40 40"><path fill="currentColor" d="M20 0C8.954 0 0 8.954 0 20s8.954 20 20 20 20-8.954 20-20S31.046 0 20 0zm0 36c-8.837 0-16-7.163-16-16S11.163 4 20 4s16 7.163 16 16-7.163 16-16 16z"/></svg>`
};
</script>

<template>
    <Head :title="`Fine Detail - ${fine.book.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">
                        Fine Details for Ref #LOAN-{{ fine.id.toString().padStart(4, '0') }} <span class="text-indigo-600 text-6xl leading-none">.</span>
                    </h1>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Left Column: Fine Card -->
                <div class="lg:col-span-3 space-y-6">
                    <Card class="border overflow-hidden relative pt-6">
                        <CardHeader class="border-b bg-slate-50/50 pb-6 md:pb-8">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
                                <Badge :variant="getStatusBadge(fine.status).variant" :class="['w-fit px-3 py-1 text-[10px] md:text-xs font-bold uppercase tracking-wider', getStatusBadge(fine.status).class]">
                                    {{ getStatusBadge(fine.status).label }}
                                </Badge>
                                <span class="text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest leading-none">Ref #LOAN-{{ fine.id.toString().padStart(4, '0') }}</span>
                            </div>
                            <CardTitle class="text-xl md:text-2xl font-black text-slate-900 leading-tight">
                                {{ fine.book.title }}
                            </CardTitle>
                            <CardDescription class="text-slate-500 font-medium text-xs md:text-sm">Published by {{ fine.book.publisher }}</CardDescription>
                        </CardHeader>

                        <CardContent class="pt-6 md:pt-8 space-y-8">
                            <!-- Timeline -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                    <span>Borrowed</span>
                                    <span>Due</span>
                                    <span>Returned Late</span>
                                </div>

                                <div class="relative flex items-center justify-between gap-2">
                                    <!-- Borrowed -->
                                    <div class="flex flex-col items-center gap-1.5 z-10">
                                        <div class="h-9 w-9 rounded-full bg-indigo-600 flex items-center justify-center shadow-md shadow-indigo-100">
                                            <BookOpen class="h-4 w-4 text-white" />
                                        </div>
                                        <span class="text-xs font-bold text-slate-700 text-center whitespace-nowrap">{{ fine.borrowed_date }}</span>
                                    </div>

                                    <!-- Line segment 1 -->
                                    <div class="flex-1 h-0.5 bg-indigo-300 -mt-5"></div>

                                    <!-- Due -->
                                    <div class="flex flex-col items-center gap-1.5 z-10">
                                        <div class="h-9 w-9 rounded-full bg-amber-500 flex items-center justify-center shadow-md shadow-amber-100">
                                            <TriangleAlert class="h-4 w-4 text-white" />
                                        </div>
                                        <span class="text-xs font-bold text-center whitespace-nowrap text-amber-500">{{ fine.due_date }}</span>
                                    </div>

                                    <!-- Line segment 2 (overdue stretch) -->
                                    <div class="flex-1 h-0.5 bg-rose-300 -mt-5 border-dashed"></div>

                                    <!-- Returned Late -->
                                    <div class="flex flex-col items-center gap-1.5 z-10">
                                        <div class="h-9 w-9 rounded-full flex items-center justify-center shadow-md"
                                            :class="fine.returned_date ? 'bg-rose-500 shadow-rose-100' : 'bg-slate-100'">
                                            <BookmarkX class="h-4 w-4" :class="fine.returned_date ? 'text-white' : 'text-slate-300'" />
                                        </div>
                                        <span class="text-xs font-bold text-center whitespace-nowrap"
                                            :class="fine.returned_date ? 'text-rose-500' : 'text-slate-300'">
                                            {{ fine.returned_date || 'Not yet returned' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Late badge -->
                                <div class="flex justify-end mt-2 items-center gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full bg-rose-50 text-rose-500 border border-rose-100">
                                        {{ daysLate }} days late
                                    </span>
                                </div>
                            </div>

                            <!-- Financial Breakdown -->
                            <div class="space-y-4 pt-4 border-t border-dashed">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 font-medium">Original Fine Amount</span>
                                    <span class="text-slate-900 font-bold">RM {{ fine.fine_amount.toFixed(2) }}</span>
                                </div>
                                <div v-if="fine.fine_paid_amount > 0" class="flex justify-between items-center text-sm">
                                    <span class="text-emerald-600 font-medium flex items-center gap-1.5">
                                        <CheckCircle2 class="h-4 w-4" /> Total Paid
                                    </span>
                                    <span class="text-emerald-600 font-bold">RM {{ fine.fine_paid_amount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-4 border-t">
                                    <span class="text-base font-black text-slate-900">Remaining Balance</span>
                                    <span class="text-2xl font-black text-indigo-600">RM {{ fine.remaining_amount.toFixed(2) }}</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-3 py-6 border-t">
                                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-slate-400">Payment Progress</span>
                                    <span class="text-indigo-600">{{ paymentPercentage }}% Settled</span>
                                </div>
                                <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden border border-slate-200/50 p-0.5">
                                    <div 
                                        class="h-full bg-indigo-600 rounded-full transition-all duration-1000 ease-out"
                                        :style="{ width: `${paymentPercentage}%` }"
                                    ></div>
                                </div>
                                <p v-if="fine.status === 'partial'" class="text-[10px] text-slate-400 font-bold italic text-center">
                                    You've made {{ fine.payments?.length }} payment(s) so far.
                                </p>
                            </div>
                        </CardContent>

                        <CardFooter class="p-4 pb-4 bg-slate-50/50 border-t flex flex-col gap-4">
                            <template v-if="fine.fine_paid">
                                 <a
                                    :href="`/member/fines/${fine.id}/receipt`"
                                    target="_blank"
                                    class="w-full h-14 inline-flex items-center justify-center rounded-xl text-lg font-black bg-emerald-600 text-white hover:bg-emerald-700 shadow-emerald-200 transition-all hover:scale-[1.02] active:scale-98 gap-3"
                                >
                                    <Receipt class="h-6 w-6" />
                                    Download Full Receipt
                                </a>
                            </template>
                            
                            <Link 
                                href="/member/fines"
                                class="w-full h-12 inline-flex items-center justify-center rounded-xl text-sm font-black text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all gap-2"
                            >
                                Back to All Fines
                            </Link>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Right Column: Payment & Security -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Secure Payment Card (Only if balance remains) -->
                    <Card v-if="fine.remaining_amount > 0" class="border shadow-lg bg-indigo-50/30 border-indigo-100 overflow-hidden pt-6 relative">
                        <div class="absolute right-4 top-4 opacity-5">
                             <StripeLogo class="h-12 w-12" />
                        </div>
                        <CardHeader>
                            <CardTitle class="text-sm font-black uppercase tracking-widest text-indigo-900 flex items-center gap-2">
                                <ShieldCheck class="h-4 w-4" /> Secure Payment
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6 pb-6">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-indigo-900/50 mb-2 block pl-1">Amount to pay (RM)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-indigo-600 font-black">RM</span>
                                    <input 
                                        type="number" 
                                        v-model="paymentAmount"
                                        :max="fine.remaining_amount"
                                        min="1"
                                        step="0.01"
                                        class="w-full h-14 pl-12 pr-4 rounded-xl border-2 border-indigo-100 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 bg-white font-black text-xl text-indigo-900 transition-all outline-none"
                                    />
                                </div>
                                <div class="flex justify-between mt-2 px-1">
                                    <button 
                                        @click="paymentAmount = fine.remaining_amount"
                                        class="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:underline"
                                    >
                                        Pay full amount
                                    </button>
                                    <span class="text-[10px] font-bold text-indigo-400 uppercase">Min: RM 1.00</span>
                                </div>
                            </div>

                            <Button 
                                class="w-full h-14 text-lg font-black bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all hover:scale-[1.02] active:scale-98 gap-3"
                                :disabled="isPaying || paymentAmount <= 0"
                                @click="handlePayment"
                            >
                                <Loader2 v-if="isPaying" class="h-6 w-6 animate-spin" />
                                <CreditCard v-else class="h-6 w-6" />
                                {{ isPaying ? 'Connecting...' : 'Continue to Payment' }}
                            </Button>

                            <div class="pt-2 flex flex-col gap-3 border-t border-indigo-100/50 mt-4">
                                <div class="flex items-center gap-3 text-[10px] text-indigo-900/60 font-black uppercase tracking-widest">
                                    <CheckCircle2 class="h-4 w-4 text-indigo-500" />
                                    SSL Encrypted Transaction
                                </div>
                                <div class="flex items-center gap-3 text-[10px] text-indigo-900/60 font-black uppercase tracking-widest">
                                    <CheckCircle2 class="h-4 w-4 text-indigo-500" />
                                    PCI Compliant Checkout
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Transaction History -->
                    <div v-if="fine.payments && fine.payments.length > 0" class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Transaction History</h3>
                            <span class="text-[10px] font-bold text-slate-300">{{ fine.payments.length }} record(s)</span>
                        </div>
                        
                        <div class="grid gap-3">
                            <div 
                                v-for="payment in fine.payments" 
                                :key="payment.id" 
                                class="bg-white border rounded-2xl overflow-hidden transition-all duration-300 shadow-sm"
                                :class="[
                                    expandedPaymentId === payment.id ? 'border-indigo-200 ring-4 ring-indigo-50' : 'hover:border-indigo-100'
                                ]"
                            >
                                <!-- Header (Clickable) -->
                                <div 
                                    @click="toggleExpand(payment.id)"
                                    class="w-full p-4 flex items-center justify-between text-left transition-colors cursor-pointer segment-header"
                                    :class="{ 'bg-indigo-50/30': expandedPaymentId === payment.id }"
                                >
                                    <div class="flex items-center gap-4">
                                        <div 
                                            class="h-10 w-10 rounded-xl flex items-center justify-center transition-colors"
                                            :class="expandedPaymentId === payment.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-indigo-50 text-indigo-600'"
                                        >
                                            <CreditCard class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900">RM {{ payment.amount.toFixed(2) }}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ payment.date }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <div class="hidden sm:block text-right">
                                            <div class="text-[9px] font-black text-slate-300 uppercase tracking-widest mb-0.5">Stripe Ref</div>
                                            <div class="text-[10px] font-mono font-medium text-slate-500">{{ payment.stripe_id.substring(0, 8) }}...</div>
                                        </div>
                                        <ChevronDown 
                                            class="h-5 w-5 text-slate-300 transition-transform duration-300"
                                            :class="{ 'rotate-180 text-indigo-600': expandedPaymentId === payment.id }"
                                        />
                                    </div>
                                </div>

                                <!-- Expanded Content -->
                                <div 
                                    v-if="expandedPaymentId === payment.id"
                                    class="px-4 pb-4 pt-1 space-y-4 border-t border-indigo-100 bg-indigo-50/20"
                                >
                                    <div class="grid grid-cols-2 gap-4 pt-3">
                                        <div class="space-y-1">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Payment Method</span>
                                            <div class="text-[11px] font-bold text-slate-700 capitalize flex items-center gap-1.5">
                                                <div class="h-1.5 w-1.5 rounded-full bg-indigo-500"></div>
                                                {{ payment.method }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Status</span>
                                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1.5">
                                                <CheckCircle2 class="h-3.5 w-3.5" />
                                                Verified
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white/80 border border-indigo-100 rounded-xl p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Full Stripe Reference</span>
                                            <button 
                                                @click.stop="handleCopyId(payment.stripe_id)"
                                                class="flex items-center gap-1.5 text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-700 transition-colors"
                                            >
                                                <component :is="copiedId === payment.stripe_id ? Check : Copy" class="h-3 w-3" />
                                                {{ copiedId === payment.stripe_id ? 'Copied' : 'Copy' }}
                                            </button>
                                        </div>
                                        <code class="text-[10px] font-mono text-slate-600 break-all block leading-relaxed">
                                            {{ payment.stripe_id }}
                                        </code>
                                    </div>

                                    <div class="flex items-center gap-2 pt-1 text-[10px] text-slate-400 font-medium italic">
                                        <Info class="h-3 w-3" />
                                        Funds reach the library within 1-3 business days.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


