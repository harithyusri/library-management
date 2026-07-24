<script setup lang="ts">
import { route } from "ziggy-js";
import { ref, reactive } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    QrCode, PlusIcon, Trash2Icon, DownloadIcon, PencilIcon, History, Shield,
    BookOpen, User, Calendar, Globe, Layers, Book as BookIcon, Building,
    Hash, Languages, Info, CheckCircle2,
    PackageSearch, ArrowLeft, Printer
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'List', href: route('admin.books.index') },
    { title: 'Book Details', href: '#' },
];

/* =========================
   Types
========================= */
interface Genre { id: number; name: string; }

interface BookCopy {
    id: number;
    barcode: string;
    call_number?: string;
    status: 'available' | 'borrowed' | 'reserved' | 'maintenance' | 'lost';
    condition: 'excellent' | 'good' | 'fair' | 'poor' | 'damaged';
    location?: string;
    acquisition_date?: string;
    acquisition_price?: number;
    notes?: string;
    qr_code_url?: string;
    borrowed_by?: { id: number; name: string; email: string; };
    borrowed_at?: string;
    due_at?: string;
    library_id: number;
    library?: { id: number; name: string; };
    created_at: string;
}

interface Book {
    id: number;
    title: string;
    author_name: string;
    isbn?: string;
    format: string;
    pages: number;
    language: string;
    published_year?: number;
    description?: string;
    cover_image?: string;
    created_at: string;
    updated_at: string;
    genres: Genre[];
    category: { id: number; name: string; };
    publisher?: { id: number; name: string; country?: string; };
    copies: BookCopy[];
}

/* =========================
   Props
========================= */
const props = defineProps<{
    book: Book;
    audits?: any[];
    libraries: Array<{ id: number, name: string }>;
}>();

/* =========================
   State
========================= */
const activeTab = ref('details');
const showEditDialog = ref(false);
const processing = ref(false);
const editingCopy = ref<BookCopy | null>(null);

const editForm = reactive({
    call_number: '',
    condition: 'good' as 'excellent' | 'good' | 'fair' | 'poor' | 'damaged',
    status: 'available' as 'available' | 'borrowed' | 'reserved' | 'maintenance' | 'lost',
    location: '',
    acquisition_date: '',
    acquisition_price: '',
    notes: '',
    library_id: '',
});

const errors = ref<Record<string, string>>({});

/* =========================
   Methods
========================= */
const formatBookFormat = (format: string): string => {
    const formats: Record<string, string> = {
        hardcover: 'Hardcover', paperback: 'Paperback', ebook: 'E-book', audiobook: 'Audiobook',
    };
    return formats[format] ?? format;
};

const formatDate = (date: string): string => new Date(date).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric',
});

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        available: 'Available', borrowed: 'Borrowed', reserved: 'Reserved',
        maintenance: 'Maintenance', lost: 'Lost',
    };
    return labels[status] || status;
};

const addCopy = () => {
    processing.value = true;
    router.post(route('admin.books.copies.store', props.book.id),
        { library_id: props.libraries.length > 0 ? props.libraries[0].id : null, condition: 'good' },
        { preserveScroll: true, onFinish: () => { processing.value = false; } }
    );
};

const openEditDialog = (copy: BookCopy) => {
    editingCopy.value = copy;
    editForm.call_number = copy.call_number || '';
    editForm.condition = copy.condition;
    editForm.status = copy.status;
    editForm.location = copy.location || '';
    editForm.acquisition_date = copy.acquisition_date || '';
    editForm.acquisition_price = copy.acquisition_price?.toString() || '';
    editForm.notes = copy.notes || '';
    editForm.library_id = copy.library_id?.toString() || '';
    showEditDialog.value = true;
    errors.value = {};
};

const updateCopy = () => {
    if (!editingCopy.value) return;
    processing.value = true;
    errors.value = {};
    router.put(
        route('admin.books.copies.update', [props.book.id, editingCopy.value.id]),
        editForm,
        {
            preserveScroll: true,
            onSuccess: () => { showEditDialog.value = false; editingCopy.value = null; },
            onError: (serverErrors) => { errors.value = serverErrors; },
            onFinish: () => { processing.value = false; },
        }
    );
};

const generateQRCode = (copyId: number) => {
    router.post(route('admin.books.copies.generate-qr', [props.book.id, copyId]), {}, { preserveScroll: true });
};

const downloadQRCode = (qrCodeUrl: string, barcode: string) => {
    const link = document.createElement('a');
    link.href = qrCodeUrl;
    link.download = `${props.book.title}-${barcode}-QR.png`;
    link.click();
};

const deleteCopy = (copyId: number) => {
    if (confirm('Are you sure you want to delete this copy?')) {
        router.delete(route('admin.books.copies.destroy', [props.book.id, copyId]), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <!-- ── Top Header Section (Referenced Design) ────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pt-6 pb-2 border-b border-border">
                <div class="space-y-2">
                    <h2 class="text-4xl md:text-4xl font-extrabold text-foreground tracking-tight leading-none font-serif">
                        {{ book.title }}
                    </h2>
                    <p class="text-md text-muted-foreground italic font-serif">
                        by {{ book.author_name }}
                    </p>
                </div>
                
                <!-- Action Buttons from design -->
                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="route('admin.books.index')">
                        <Button variant="outline" class="h-11 px-5 rounded-lg border-border hover:bg-muted font-semibold text-xs tracking-wider uppercase flex items-center gap-2">
                            <ArrowLeft class="h-4 w-4" /> Back to List
                        </Button>
                    </Link>
                    <Link v-if="$page.props.auth.can?.edit_books" :href="route('admin.books.edit', book.id)">
                        <Button variant="outline" class="h-11 px-5 rounded-lg border-border hover:bg-muted font-semibold text-xs tracking-wider uppercase flex items-center gap-2">
                            <PencilIcon class="h-4 w-4" /> Edit Record
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ── Tabs navigation ────────────────────────────── -->
            <Tabs v-model="activeTab" class="w-full space-y-6">
                <TabsList class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
                    <TabsTrigger value="details" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        <span class="inline-flex items-center gap-2">
                            <BookOpen class="h-3.5 w-3.5" />
                            <span>Overview</span>
                        </span>
                    </TabsTrigger>
                    <TabsTrigger value="copies" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        <span class="inline-flex items-center gap-2">
                            <Layers class="h-3.5 w-3.5" />
                            <span>Inventory</span>
                            <span class="ml-1 rounded-full bg-muted px-2 py-0.5 text-[13px] font-semibold leading-none text-muted-foreground">{{ book.copies?.length || 0 }}</span>
                        </span>
                    </TabsTrigger>
                    <TabsTrigger v-if="$page.props.auth.can?.view_audits" value="history" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        <span class="inline-flex items-center gap-2">
                            <History class="h-3.5 w-3.5" />
                            <span>Audit Logs</span>
                        </span>
                    </TabsTrigger>
                </TabsList>

                <!-- ── Overview Tab (Asymmetric Layout) ──────────────── -->
                <TabsContent value="details" class="mt-0 outline-none">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <!-- Left Column: Cover & Quick Status (4 cols) -->
                        <div class="lg:col-span-4 space-y-6">
                            <!-- Book Cover Card -->
                            <div class="bg-card border border-border p-4 rounded-2xl">
                                <div class="aspect-[2/3] w-full bg-muted overflow-hidden rounded-xl shadow-inner relative group border border-border">
                                    <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                                    <div v-else class="flex flex-col h-full w-full items-center justify-center gap-3 bg-gradient-to-br from-muted to-muted/60 text-muted-foreground/30">
                                        <BookIcon class="h-16 w-16" />
                                        <span class="text-xs font-bold uppercase tracking-widest">No Archival Cover</span>
                                    </div>
                                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                                </div>
                            </div>

                            <!-- Circulation Status Card -->
                            <div class="bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4">
                                    <span class="inline-flex items-center justify-center w-2.5 h-2.5 rounded-full"
                                        :class="book.copies?.some(c => c.status === 'available') ? 'bg-green-500 animate-pulse' : 'bg-amber-500'">
                                    </span>
                                </div>
                                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-6">Circulation Status</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-border/50 pb-2.5">
                                        <span class="text-muted-foreground text-sm">State</span>
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full border"
                                            :class="book.copies?.some(c => c.status === 'available') 
                                                ? 'bg-green-50/70 text-green-800 border-green-200' 
                                                : 'bg-amber-50/70 text-amber-800 border-amber-200'">
                                            {{ book.copies?.some(c => c.status === 'available') ? 'AVAILABLE' : 'ALL COPIES BORROWED' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-border/50 pb-2.5">
                                        <span class="text-muted-foreground text-sm">Total Copies</span>
                                        <span class="text-foreground font-semibold text-sm">{{ book.copies?.length || 0 }}</span>
                                    </div>
                                    <div v-if="book.copies?.length" class="flex items-center justify-between pb-1">
                                        <span class="text-muted-foreground text-sm">Primary Shelf</span>
                                        <span class="text-foreground font-mono text-sm font-bold uppercase">{{ book.copies[0].location || 'A-1' }}</span>
                                    </div>
                                </div>

                                <!-- Last Borrower Info -->
                                <div v-if="book.copies?.some(c => c.status === 'borrowed' && c.borrowed_by)" class="mt-6 pt-6 border-t border-border">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-secondary-container/30 flex items-center justify-center text-primary border border-border">
                                            <User class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Active Borrower</p>
                                            <p class="text-sm font-bold text-foreground">
                                                {{ book.copies.find(c => c.status === 'borrowed')?.borrowed_by?.name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Details & Archival Info (8 cols) -->
                        <div class="lg:col-span-8 space-y-6">
                            <!-- Synopsis Section -->
                            <div class="bg-card border border-border p-8 rounded-2xl">
                                <h3 class="text-xl font-bold text-foreground font-serif mb-6 border-b border-border pb-3 flex items-center gap-2">
                                    <BookOpen class="h-5 w-5 text-primary shrink-0" /> Synopsis
                                </h3>
                                <div class="prose dark:prose-invert max-w-none space-y-6 text-muted-foreground leading-relaxed">
                                    <p class="text-base first-letter:text-5xl first-letter:font-bold first-letter:text-primary first-letter:mr-3 first-letter:float-left first-letter:font-serif">
                                        {{ book.description || 'No detailed synopsis has been provided for this archival record.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Archival Information Grid -->
                            <div class="bg-card border border-border p-8 rounded-2xl">
                                <div class="flex items-center justify-between mb-8 pb-3 border-b border-border">
                                    <h3 class="text-xl font-bold text-foreground font-serif">Archival Information</h3>
                                    <Layers class="h-5 w-5 text-muted-foreground opacity-65" />
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">ISBN-13</label>
                                        <p class="text-sm font-mono text-foreground font-bold border-b border-border/40 pb-1">{{ book.isbn || 'N/A' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Publication Year</label>
                                        <p class="text-sm text-foreground font-semibold border-b border-border/40 pb-1">{{ book.published_year || 'N/A' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Publisher</label>
                                        <p class="text-sm text-foreground font-semibold border-b border-border/40 pb-1">{{ book.publisher?.name || 'N/A' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Language</label>
                                        <p class="text-sm text-foreground font-semibold border-b border-border/40 pb-1">{{ book.language || 'English' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Page Count</label>
                                        <p class="text-sm text-foreground font-semibold border-b border-border/40 pb-1">{{ book.pages || 0 }} Pages</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Format Classification</label>
                                        <div class="flex gap-2 pt-1">
                                            <span class="px-2.5 py-0.5 bg-primary/10 text-primary text-[10px] font-bold rounded capitalize">{{ formatBookFormat(book.format) }}</span>
                                            <span v-if="book.category" class="px-2.5 py-0.5 bg-muted text-muted-foreground text-[10px] font-bold rounded">{{ book.category.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Metadata Verification Info -->
                                <div class="mt-8 p-4 bg-muted/50 border border-border rounded-xl flex items-center justify-between text-muted-foreground">
                                    <div class="flex items-center gap-3">
                                        <Shield class="h-4.5 w-4.5 text-primary" />
                                        <span class="text-xs font-semibold">Archival Record Authenticity Secured</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs font-medium italic">Verified System Entry</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </TabsContent>

                <!-- ── Inventory Tab ──────────────────────────────── -->
                <TabsContent value="copies" class="mt-0 outline-none">
                    <div class="space-y-5">
                        <!-- Header -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-black text-foreground tracking-tight">Physical Inventory</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    {{ book.copies?.length || 0 }} total · {{ book.copies?.filter(c => c.status === 'available').length || 0 }} available
                                </p>
                            </div>
                            <Button v-if="$page.props.auth.can?.create_book_copies || $page.props.auth.can?.create_books || $page.props.auth.can?.edit_books" @click="addCopy" :disabled="processing"
                                class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl px-5 py-2.5 shadow-lg shadow-[#eabcb8]/30 transition-all active:scale-95 flex items-center gap-2 text-sm font-bold shrink-0">
                                <PlusIcon class="h-4 w-4" /> Add Copy
                            </Button>
                        </div>

                        <!-- Grid -->
                        <div v-if="book.copies && book.copies.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div v-for="copy in book.copies" :key="copy.id"
                                class="group relative bg-card rounded-2xl border border-border hover:shadow-md hover:border-[#eabcb8]/70 transition-all duration-200 overflow-hidden flex flex-col">

                                <!-- Status ribbon -->
                                <div class="h-1 w-full shrink-0"
                                    :class="{
                                        'bg-[#795553]': copy.status === 'available',
                                        'bg-[#4f6073]': copy.status === 'borrowed',
                                        'bg-[#cba72f]': copy.status === 'reserved',
                                        'bg-destructive': copy.status === 'maintenance' || copy.status === 'lost',
                                    }">
                                </div>

                                <div class="p-4 flex flex-col gap-4 flex-1">
                                    <!-- Top: QR + Info -->
                                    <div class="flex gap-3">
                                        <!-- QR -->
                                        <div class="shrink-0 flex flex-col items-center gap-2">
                                            <div class="h-20 w-20 bg-muted rounded-xl border border-dashed border-border p-1 flex items-center justify-center overflow-hidden">
                                                <img v-if="copy.qr_code_url" :src="copy.qr_code_url" class="w-full h-full object-contain" />
                                                <QrCode v-else class="h-7 w-7 text-muted-foreground/30" />
                                            </div>
                                            <Button v-if="copy.qr_code_url" variant="ghost" size="sm"
                                                @click="downloadQRCode(copy.qr_code_url, copy.barcode)"
                                                class="h-6 w-20 text-[11px] font-bold bg-[#cba72f] text-white rounded-lg px-0 hover:bg-[#735c00] transition-all active:scale-95 gap-1">
                                                <DownloadIcon class="h-3 w-3" /> Save
                                            </Button>
                                            <Button v-else-if="$page.props.auth.can?.edit_book_copies" variant="ghost" size="sm"
                                                @click="generateQRCode(copy.id)"
                                                class="h-6 w-20 text-[11px] font-bold bg-[#795553] text-white rounded-lg px-0 hover:bg-primary transition-all active:scale-95 gap-1">
                                                <QrCode class="h-3 w-3" /> Gen
                                            </Button>
                                        </div>

                                        <!-- Details -->
                                        <div class="flex-1 min-w-0 space-y-2">
                                            <div class="flex items-start justify-between gap-1.5">
                                                <div class="min-w-0">
                                                    <p class="text-[9px] font-black text-muted-foreground uppercase tracking-[0.18em] mb-0.5">Barcode</p>
                                                    <p class="text-xs font-black text-foreground truncate font-mono">{{ copy.barcode }}</p>
                                                </div>
                                                <span class="shrink-0 text-[10px] font-black px-2 py-0.5 rounded-full capitalize leading-none mt-0.5"
                                                    :class="{
                                                        'bg-[#795553]/15 text-[#795553]': copy.status === 'available',
                                                        'bg-[#4f6073]/15 text-[#4f6073]': copy.status === 'borrowed',
                                                        'bg-[#cba72f]/15 text-[#735c00]': copy.status === 'reserved',
                                                        'bg-destructive/10 text-destructive': copy.status === 'maintenance' || copy.status === 'lost',
                                                    }">
                                                    {{ getStatusLabel(copy.status) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-tight mb-0.5">Condition</p>
                                                <span class="text-[11px] font-black text-foreground capitalize flex items-center gap-1">
                                                    <CheckCircle2 class="h-3 w-3 text-[#cba72f] shrink-0" /> {{ copy.condition }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-tight mb-0.5">Location</p>
                                                <span class="text-[11px] font-black text-foreground truncate block">
                                                    {{ copy.library?.name || 'Unknown' }}<span v-if="copy.location" class="text-muted-foreground font-normal"> · {{ copy.location }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Borrower row -->
                                    <div v-if="copy.status === 'borrowed' && copy.borrowed_by"
                                        class="bg-[#4f6073]/8 rounded-xl px-3 py-2 border border-[#4f6073]/15 flex items-center gap-2.5">
                                        <div class="h-7 w-7 rounded-full bg-[#4f6073]/15 flex items-center justify-center shrink-0">
                                            <User class="h-3.5 w-3.5 text-[#4f6073]" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[9px] font-bold text-[#4f6073]/70 uppercase tracking-tight">Borrowed by</p>
                                            <p class="text-xs font-black text-foreground truncate">{{ copy.borrowed_by.name }}</p>
                                        </div>
                                        <div v-if="copy.due_at" class="text-right shrink-0">
                                            <p class="text-[9px] font-bold text-muted-foreground uppercase">Due</p>
                                            <p class="text-[10px] font-black text-foreground">{{ formatDate(copy.due_at) }}</p>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2 pt-1 border-t border-border/50 mt-auto">
                                        <Button v-if="$page.props.auth.can?.edit_book_copies"
                                            variant="ghost" size="sm" @click="openEditDialog(copy)"
                                            class="flex-1 h-8 rounded-xl bg-muted/60 hover:bg-muted text-foreground hover:text-primary transition-all font-bold text-xs gap-1.5">
                                            <PencilIcon class="h-3.5 w-3.5" /> Edit Copy
                                        </Button>
                                        <Button v-if="$page.props.auth.can?.delete_book_copies"
                                            variant="ghost" size="sm" @click="deleteCopy(copy.id)"
                                            :disabled="copy.status === 'borrowed'"
                                            class="h-8 w-8 p-0 rounded-xl hover:bg-destructive/10 hover:text-destructive transition-all disabled:opacity-40">
                                            <Trash2Icon class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty -->
                        <div v-else class="py-24 text-center bg-card rounded-3xl border border-dashed border-border">
                            <div class="relative inline-flex mb-5">
                                <div class="h-20 w-20 rounded-full bg-muted flex items-center justify-center">
                                    <PackageSearch class="h-9 w-9 text-muted-foreground/40" />
                                </div>
                                <div class="absolute -top-1 -right-1 h-7 w-7 rounded-full bg-[#ffdad7]/60 border-2 border-card flex items-center justify-center">
                                    <PlusIcon class="h-3.5 w-3.5 text-primary" />
                                </div>
                            </div>
                            <h3 class="text-lg font-black text-foreground tracking-tight mb-1">No Copies Yet</h3>
                            <p class="text-sm text-muted-foreground font-medium max-w-xs mx-auto mb-6">This book doesn't have any physical copies assigned yet.</p>
                            <Button v-if="$page.props.auth.can?.create_book_copies || $page.props.auth.can?.create_books || $page.props.auth.can?.edit_books" @click="addCopy" :disabled="processing"
                                class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl px-8 py-2.5 shadow-lg shadow-[#eabcb8]/30 transition-all active:scale-95 font-bold text-sm gap-2 inline-flex items-center">
                                <PlusIcon class="h-4 w-4" /> Add First Copy
                            </Button>
                        </div>
                    </div>
                </TabsContent>

                <!-- ── Audit Logs Tab ─────────────────────────────── -->
                <TabsContent value="history" class="mt-0 outline-none">
                    <Card class="rounded-3xl border-border overflow-hidden">
                        <CardHeader class="bg-gradient-to-r from-muted/50 to-transparent pb-3 pt-5 px-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-[#ffdad7]/60 rounded-xl">
                                    <History class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="text-base font-black text-foreground tracking-tight">Audit History</CardTitle>
                                    <p class="text-[10px] text-muted-foreground mt-0.5">{{ audits?.length || 0 }} recorded events</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 py-5">
                            <div v-if="audits && audits.length > 0" class="relative space-y-3">
                                <div class="absolute left-3.5 top-4 bottom-4 w-px bg-gradient-to-b from-transparent via-border to-transparent"></div>
                                <div v-for="audit in audits" :key="audit.id" class="relative flex gap-4 group">
                                    <!-- Dot -->
                                    <div class="relative shrink-0 z-10 flex items-center justify-center w-7 h-7 rounded-full border-2 border-card mt-0.5 transition-transform group-hover:scale-110 duration-200"
                                        :class="{
                                            'bg-[#795553]/15': audit.event === 'created',
                                            'bg-[#cba72f]/15': audit.event === 'updated',
                                            'bg-destructive/10': audit.event === 'deleted',
                                            'bg-muted': !['created','updated','deleted'].includes(audit.event),
                                        }">
                                        <CheckCircle2 v-if="audit.event === 'created'" class="h-3 w-3 text-[#795553]" />
                                        <PencilIcon v-else-if="audit.event === 'updated'" class="h-3 w-3 text-[#cba72f]" />
                                        <Trash2Icon v-else-if="audit.event === 'deleted'" class="h-3 w-3 text-destructive" />
                                        <Shield v-else class="h-3 w-3 text-muted-foreground" />
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 bg-card border border-border rounded-2xl p-4 group-hover:border-[#eabcb8]/60 transition-all duration-200">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full capitalize"
                                                    :class="{
                                                        'bg-[#795553]/15 text-[#795553]': audit.event === 'created',
                                                        'bg-[#cba72f]/15 text-[#735c00]': audit.event === 'updated',
                                                        'bg-destructive/10 text-destructive': audit.event === 'deleted',
                                                        'bg-muted text-muted-foreground': !['created','updated','deleted'].includes(audit.event),
                                                    }">{{ audit.event }}</span>
                                                <span class="text-xs text-muted-foreground">by <span class="font-black text-primary">{{ audit.user?.name || 'System' }}</span></span>
                                            </div>
                                            <time class="font-mono text-[10px] text-muted-foreground bg-muted px-2 py-1 rounded-lg">{{ new Date(audit.created_at).toLocaleString() }}</time>
                                        </div>
                                        <div v-if="audit.new_values && Object.keys(audit.new_values).length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            <div v-for="(val, key) in audit.new_values" :key="key" class="bg-muted/50 px-3 py-2 rounded-xl border border-border/60">
                                                <p class="text-[9px] font-black text-muted-foreground uppercase tracking-tight mb-0.5">{{ key }}</p>
                                                <p class="text-[11px] font-bold text-foreground truncate">{{ val }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-20 text-center">
                                <div class="h-16 w-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                                    <History class="h-7 w-7 text-muted-foreground/30" />
                                </div>
                                <h3 class="text-base font-black text-foreground tracking-tight mb-1">No Events Yet</h3>
                                <p class="text-sm text-muted-foreground font-medium">No modifications have been recorded for this book.</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- ── Edit Copy Dialog ─────────────────────────────────── -->
            <Dialog v-model:open="showEditDialog">
                <DialogContent class="sm:max-w-[520px] rounded-3xl p-0 overflow-hidden gap-0">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-[#ffdad7]/40 to-transparent px-6 pt-6 pb-4 border-b border-border">
                        <DialogHeader>
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-[#ffdad7]/70 rounded-xl"><PencilIcon class="h-5 w-5 text-primary" /></div>
                                <div>
                                    <DialogTitle class="text-base font-black text-foreground">Edit Book Copy</DialogTitle>
                                    <DialogDescription class="text-xs mt-0.5">Update the details for this physical copy.</DialogDescription>
                                </div>
                            </div>
                        </DialogHeader>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5 grid gap-5 max-h-[68vh] overflow-y-auto">
                        <!-- Identification -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Identification</p>
                            <div class="grid gap-1.5">
                                <Label for="call_number" class="text-xs font-bold">Call Number</Label>
                                <Input id="call_number" v-model="editForm.call_number" placeholder="e.g., 813.54 FIT" :class="{ 'border-destructive': errors.call_number }" class="rounded-xl" />
                                <p v-if="errors.call_number" class="text-xs text-destructive">{{ errors.call_number }}</p>
                            </div>
                        </div>

                        <div class="h-px bg-border/60"></div>

                        <!-- Status & Condition -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Status & Condition</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="condition" class="text-xs font-bold">Condition <span class="text-destructive">*</span></Label>
                                    <Select v-model="editForm.condition">
                                        <SelectTrigger id="condition" class="rounded-xl"><SelectValue placeholder="Select condition" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="excellent">Excellent</SelectItem>
                                            <SelectItem value="good">Good</SelectItem>
                                            <SelectItem value="fair">Fair</SelectItem>
                                            <SelectItem value="poor">Poor</SelectItem>
                                            <SelectItem value="damaged">Damaged</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="errors.condition" class="text-xs text-destructive">{{ errors.condition }}</p>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="status" class="text-xs font-bold">Status <span class="text-destructive">*</span></Label>
                                    <Select v-model="editForm.status">
                                        <SelectTrigger id="status" class="rounded-xl"><SelectValue placeholder="Select status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="available">Available</SelectItem>
                                            <SelectItem value="borrowed">Borrowed</SelectItem>
                                            <SelectItem value="reserved">Reserved</SelectItem>
                                            <SelectItem value="maintenance">Maintenance</SelectItem>
                                            <SelectItem value="lost">Lost</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="errors.status" class="text-xs text-destructive">{{ errors.status }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-border/60"></div>

                        <!-- Location -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Location</p>
                            <div class="grid gap-1.5">
                                <Label for="library" class="text-xs font-bold">Library Branch <span class="text-destructive">*</span></Label>
                                <Select v-model="editForm.library_id">
                                    <SelectTrigger id="library" class="rounded-xl"><SelectValue placeholder="Select library" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">{{ lib.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.library_id" class="text-xs text-destructive">{{ errors.library_id }}</p>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="location" class="text-xs font-bold">Shelf / Placement</Label>
                                <Input id="location" v-model="editForm.location" placeholder="e.g., Shelf A1" :class="{ 'border-destructive': errors.location }" class="rounded-xl" />
                                <p v-if="errors.location" class="text-xs text-destructive">{{ errors.location }}</p>
                            </div>
                        </div>

                        <div class="h-px bg-border/60"></div>

                        <!-- Acquisition -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Acquisition</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="acquisition_date" class="text-xs font-bold">Date</Label>
                                    <Input id="acquisition_date" v-model="editForm.acquisition_date" type="date" :class="{ 'border-destructive': errors.acquisition_date }" class="rounded-xl" />
                                    <p v-if="errors.acquisition_date" class="text-xs text-destructive">{{ errors.acquisition_date }}</p>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="acquisition_price" class="text-xs font-bold">Price</Label>
                                    <Input id="acquisition_price" v-model="editForm.acquisition_price" type="number" step="0.01" placeholder="0.00" :class="{ 'border-destructive': errors.acquisition_price }" class="rounded-xl" />
                                    <p v-if="errors.acquisition_price" class="text-xs text-destructive">{{ errors.acquisition_price }}</p>
                                </div>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="notes" class="text-xs font-bold">Notes</Label>
                                <Textarea id="notes" v-model="editForm.notes" placeholder="Any additional notes about this copy..." rows="3" :class="{ 'border-destructive': errors.notes }" class="rounded-xl resize-none" />
                                <p v-if="errors.notes" class="text-xs text-destructive">{{ errors.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <DialogFooter class="px-6 py-4 border-t border-border bg-muted/30 gap-2">
                        <Button type="button" variant="outline" @click="showEditDialog = false" :disabled="processing" class="rounded-xl font-bold">Cancel</Button>
                        <Button type="submit" @click="updateCopy" :disabled="processing" class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl font-bold shadow-md shadow-[#eabcb8]/30 min-w-[120px]">
                            <span v-if="processing" class="flex items-center gap-2">
                                <span class="h-3.5 w-3.5 rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground animate-spin"></span>
                                Saving...
                            </span>
                            <span v-else>Save Changes</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
