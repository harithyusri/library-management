<script setup lang="ts">
import { route } from "ziggy-js";
import { ref, reactive } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { 
    QrCode, PlusIcon, Trash2Icon, DownloadIcon, PencilIcon, History, Shield, 
    BookOpen, User, Tag, Calendar, Globe, Layers, Book as BookIcon, Building, 
    Hash, Languages, Info, ExternalLink, Bookmark, CheckCircle2, AlertCircle, 
    PackageSearch, MapPin, DollarSign, ArrowLeft
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: route('admin.books.index'),
    },
    {
        title: 'Book Details',
        href: '#',
    },
];

/* =========================
   Types
========================= */
interface Genre {
    id: number;
    name: string;
}

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
    borrowed_by?: {
        id: number;
        name: string;
        email: string;
    };
    borrowed_at?: string;
    due_at?: string;
    due_at?: string;
    library_id: number;
    library?: {
        id: number;
        name: string;
    };
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
    category: {
        id: number;
        name: string;
    };
    publisher?: {
        id: number;
        name: string;
        country?: string;
    };
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
        hardcover: 'Hardcover',
        paperback: 'Paperback',
        ebook: 'E-book',
        audiobook: 'Audiobook',
    };
    return formats[format] ?? format;
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatPrice = (price?: number): string => {
    if (!price) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);
};

const getStatusColor = (status: string): 'default' | 'destructive' | 'outline' | 'secondary' => {
    const colors: Record<string, 'default' | 'destructive' | 'outline' | 'secondary'> = {
        available: 'default',
        borrowed: 'secondary',
        reserved: 'outline',
        maintenance: 'destructive',
        lost: 'destructive',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        available: 'Available',
        borrowed: 'Borrowed',
        reserved: 'Reserved',
        maintenance: 'Maintenance',
        lost: 'Lost',
    };
    return labels[status] || status;
};

const getConditionLabel = (condition: string): string => {
    return condition.charAt(0).toUpperCase() + condition.slice(1);
};

const addCopy = () => {
    processing.value = true;

    router.post(
        route('admin.books.copies.store', props.book.id),
        {
            library_id: props.libraries.length > 0 ? props.libraries[0].id : null
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        }
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
            onSuccess: () => {
                showEditDialog.value = false;
                editingCopy.value = null;
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        }
    );
};

const generateQRCode = (copyId: number) => {
    router.post(route('admin.books.copies.generate-qr', [props.book.id, copyId]), {}, {
        preserveScroll: true,
    });
};

const downloadQRCode = (qrCodeUrl: string, barcode: string) => {
    const link = document.createElement('a');
    link.href = qrCodeUrl;
    link.download = `${props.book.title}-${barcode}-QR.png`;
    link.click();
};

const deleteCopy = (copyId: number) => {
    if (confirm('Are you sure you want to delete this copy?')) {
        router.delete(route('admin.books.copies.destroy', [props.book.id, copyId]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-6">
            <!-- Hero Section -->
            <div class="relative overflow-hidden rounded-3xl bg-white border border-slate-100 shadow-sm">
                <!-- Background Decoration -->
                <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-indigo-50/50 to-transparent pointer-events-none"></div>
                
                <div class="relative p-6 md:p-10 flex flex-col md:flex-row gap-8 items-start">
                    <!-- Floating Cover -->
                    <div class="shrink-0 relative group">
                        <div class="absolute inset-0 bg-indigo-600 rounded-2xl blur-2xl opacity-10 group-hover:opacity-20 transition-opacity duration-700"></div>
                        <div class="relative w-40 md:w-56 aspect-[2/3] rounded-2xl overflow-hidden bg-slate-50 border-4 border-white shadow-2xl transition-transform duration-700 group-hover:rotate-1 group-hover:scale-[1.02]">
                            <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title" class="w-full h-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-300">
                                <BookIcon class="h-16 w-16" />
                            </div>
                        </div>
                    </div>

                    <!-- Book Primary Info -->
                    <div class="flex-1 space-y-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="secondary" class="bg-indigo-50 text-indigo-600 border-indigo-100 font-bold uppercase tracking-widest text-[10px] px-3 py-1">
                                    {{ book.category?.name || 'Uncategorized' }}
                                </Badge>
                                <Badge v-for="genre in book.genres" :key="genre.id" variant="outline" class="text-[10px] border-slate-200 text-slate-500 font-bold tracking-widest uppercase">
                                    {{ genre.name }}
                                </Badge>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-none">
                                {{ book.title }}
                            </h1>
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="text-xl font-medium tracking-tight">Author: <span class="font-bold text-slate-700">{{ book.author_name }}</span></span>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex flex-wrap gap-3 pt-2">
                            <Link v-if="$page.props.auth.can?.edit_books" :href="route('admin.books.edit', book.id)">
                                <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 py-2 shadow-lg shadow-indigo-100 transition-all active:scale-95 flex items-center gap-2">
                                    <PencilIcon class="h-4 w-4" />
                                    Edit Book
                                </Button>
                            </Link>
                            <Link :href="route('admin.books.index')">
                                <Button variant="outline" class="border-slate-200 hover:bg-slate-50 rounded-xl px-6 py-2 transition-all active:scale-95 flex items-center gap-2">
                                    <ArrowLeft class="h-4 w-4" />
                                    Back to Catalog
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-2 group hover:border-indigo-100 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ISBN Number</span>
                        <Hash class="h-4 w-4 text-indigo-500" />
                    </div>
                    <p class="text-sm font-black text-slate-900 font-mono">{{ book.isbn || 'N/A' }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-2 group hover:border-indigo-100 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Physical Format</span>
                        <Layers class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="text-sm font-black text-slate-900 uppercase tracking-tight">{{ formatBookFormat(book.format) }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-2 group hover:border-indigo-100 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Language</span>
                        <Languages class="h-4 w-4 text-purple-500" />
                    </div>
                    <p class="text-sm font-black text-slate-900 uppercase tracking-tight">{{ book.language || 'English' }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-2 group hover:border-indigo-100 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Published</span>
                        <Calendar class="h-4 w-4 text-orange-500" />
                    </div>
                    <p class="text-sm font-black text-slate-900 tracking-tight">{{ book.published_year || 'N/A' }}</p>
                </div>
            </div>

            <!-- Tabs Section -->
            <Tabs v-model="activeTab" class="w-full space-y-6">
                <TabsList class="bg-white border border-slate-100 p-1 rounded-2xl shadow-sm h-14 w-full md:w-auto">
                    <TabsTrigger value="details" class="rounded-xl px-8 h-full data-[state=active]:bg-indigo-600 data-[state=active]:text-white data-[state=active]:shadow-lg transition-all duration-300">
                        Overview
                    </TabsTrigger>
                    <TabsTrigger value="copies" class="rounded-xl px-8 h-full data-[state=active]:bg-indigo-600 data-[state=active]:text-white data-[state=active]:shadow-lg transition-all duration-300">
                        Inventory ({{ book.copies?.length || 0 }})
                    </TabsTrigger>
                    <TabsTrigger v-if="$page.props.auth.can?.view_audits" value="history" class="rounded-xl px-8 h-full data-[state=active]:bg-indigo-600 data-[state=active]:text-white data-[state=active]:shadow-lg transition-all duration-300">
                        Audit Logs
                    </TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="details" class="mt-0 space-y-6 outline-none">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Synopsis Card -->
                        <Card class="lg:col-span-8 rounded-3xl border-slate-100 shadow-sm overflow-hidden py-4">
                            <CardHeader class="border-slate-50 bg-slate-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 rounded-lg">
                                        <BookOpen class="h-5 w-5 text-indigo-600" />
                                    </div>
                                    <CardTitle class="text-xl font-black text-slate-900 tracking-tight">Synopsis</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="p-4">
                                <p class="text-slate-600 leading-relaxed text-lg whitespace-pre-line">
                                    {{ book.description || 'No description provided for this book.' }}
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Extra Details Grid -->
                        <div class="lg:col-span-4 space-y-6">
                            <Card class="rounded-3xl border-slate-100 shadow-sm py-4">
                                <CardHeader class="border-slate-50">
                                    <CardTitle class="text-sm font-black text-slate-400 uppercase tracking-widest">Extended Info</CardTitle>
                                </CardHeader>
                                <CardContent class="p-4 space-y-6">
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 bg-slate-100 rounded-md text-slate-500">
                                                    <Building class="h-3.5 w-3.5" />
                                                </div>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Publisher</span>
                                            </div>
                                            <span class="text-xs font-black text-slate-800">{{ book.publisher?.name || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 bg-slate-100 rounded-md text-slate-500">
                                                    <Info class="h-3.5 w-3.5" />
                                                </div>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Page Count</span>
                                            </div>
                                            <span class="text-xs font-black text-slate-800">{{ book.pages || 0 }} Pages</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 bg-slate-100 rounded-md text-slate-500">
                                                    <Calendar class="h-3.5 w-3.5" />
                                                </div>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Added Date</span>
                                            </div>
                                            <span class="text-xs font-black text-slate-800">{{ formatDate(book.created_at) }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- Inventory Tab -->
                <TabsContent value="copies" class="mt-0 outline-none">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Physical Inventory</h3>
                                <p class="text-xs text-slate-500 font-medium">Manage and track available copies using QR codes</p>
                            </div>
                            <Button v-if="$page.props.auth.can?.create_book_copies" @click="addCopy" :disabled="processing" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 py-2 shadow-lg shadow-indigo-100 transition-all active:scale-95 flex items-center gap-2">
                                <PlusIcon class="h-4 w-4" />
                                Add New Copy
                            </Button>
                        </div>

                        <!-- Copies Grid -->
                        <div v-if="book.copies && book.copies.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <Card v-for="copy in book.copies" :key="copy.id" class="rounded-3xl border-slate-100 shadow-sm overflow-hidden group hover:border-indigo-200 transition-all">
                                <CardContent class="p-5 space-y-4">
                                    <div class="flex gap-4">
                                        <!-- QR Section -->
                                        <div class="shrink-0 flex flex-col items-center gap-2">
                                            <div class="h-24 w-24 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-1 flex items-center justify-center overflow-hidden">
                                                <img v-if="copy.qr_code_url" :src="copy.qr_code_url" class="w-full h-full object-contain" />
                                                <QrCode v-else class="h-8 w-8 text-slate-300" />
                                            </div>
                                            <Button 
                                                v-if="copy.qr_code_url" 
                                                variant="ghost" 
                                                size="sm" 
                                                @click="downloadQRCode(copy.qr_code_url, copy.barcode)"
                                                class="h-7 text-[14px] bg-amber-500 text-white rounded-lg px-4 py-1 shadow-lg shadow-amber-100 hover:bg-amber-600 hover:text-white transition-all active:scale-95 flex items-center gap-2"
                                            >
                                                <DownloadIcon class="h-3 w-3 mr-1" /> Save
                                            </Button>
                                            <Button 
                                                v-else-if="$page.props.auth.can?.edit_book_copies"
                                                variant="ghost" 
                                                size="sm" 
                                                @click="generateQRCode(copy.id)"
                                                class="h-7 text-[14px] bg-green-600 text-white rounded-lg px-4 py-1 shadow-lg shadow-green-100 hover:bg-green-700 hover:text-white transition-all active:scale-95 flex items-center gap-2"
                                            >
                                                Generate
                                            </Button>
                                        </div>

                                        <!-- Info Section -->
                                        <div class="flex-1 min-w-0 space-y-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Barcode</p>
                                                    <p class="text-sm font-black text-slate-900 truncate font-mono">{{ copy.barcode }}</p>
                                                </div>
                                                <Badge :variant="getStatusColor(copy.status)" class="rounded-full px-2 py-0 text-[10px] font-bold border-0 shadow-none capitalize shrink-0">
                                                    {{ getStatusLabel(copy.status) }}
                                                </Badge>
                                            </div>

                                            <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                                                <div>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">Condition</p>
                                                    <span class="text-[11px] font-black text-slate-700 capitalize flex items-center gap-1">
                                                        <CheckCircle2 class="h-2.5 w-2.5 text-emerald-500" />
                                                        {{ copy.condition }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">Library / Location</p>
                                                    <span class="text-[11px] font-black text-slate-700 truncate block">
                                                        {{ copy.library?.name || 'Unknown' }}
                                                        <span v-if="copy.location" class="text-slate-400 font-normal"> ({{ copy.location }})</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Borrower Snapshot -->
                                    <div v-if="copy.status === 'borrowed' && copy.borrowed_by" class="bg-slate-50 rounded-2xl p-3 border border-slate-100 flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-white border border-indigo-100 flex items-center justify-center">
                                            <User class="h-4 w-4 text-indigo-500" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">With Member</p>
                                            <p class="text-xs font-black text-slate-900 truncate">{{ copy.borrowed_by.name }}</p>
                                        </div>
                                    </div>

                                    <!-- Actions Overlay -->
                                    <div class="flex items-center gap-2 pt-2 border-t border-slate-50">
                                        <Button 
                                            v-if="$page.props.auth.can?.edit_book_copies"
                                            variant="outline" 
                                            size="sm" 
                                            @click="openEditDialog(copy)"
                                            class="flex-1 h-9 rounded-xl border-slate-200 hover:bg-slate-50 hover:text-indigo-600 transition-all font-bold text-xs"
                                        >
                                            <PencilIcon class="h-3 w-3 mr-2" /> Modify
                                        </Button>
                                        <Button 
                                            v-if="$page.props.auth.can?.delete_book_copies"
                                            variant="outline" 
                                            size="sm" 
                                            @click="deleteCopy(copy.id)"
                                            :disabled="copy.status === 'borrowed'"
                                            class="h-9 w-9 p-0 rounded-xl border-slate-200 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all"
                                        >
                                            <Trash2Icon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-20 text-center bg-white rounded-3xl border border-dashed border-slate-200">
                            <div class="bg-slate-50 h-20 w-20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-white shadow-xl shadow-slate-100">
                                <PackageSearch class="h-10 w-10 text-slate-300" />
                            </div>
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Zero Inventory</h3>
                            <p class="mt-1 text-sm text-slate-500 font-medium max-w-xs mx-auto">This book doesn't have any physical copies assigned yet.</p>
                            <Button v-if="$page.props.auth.can?.create_book_copies" @click="addCopy" :disabled="processing" class="mt-6 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl px-8 py-2 shadow-lg transition-all active:scale-95">
                                <PlusIcon class="h-4 w-4 mr-2" />
                                Add First Copy
                            </Button>
                        </div>
                    </div>
                </TabsContent>

                <!-- Audit Logs Tab -->
                <TabsContent value="history" class="mt-0 outline-none">
                    <Card class="rounded-3xl border-slate-100 shadow-sm overflow-hidden py-4">
                        <CardHeader class="border-slate-50 bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 rounded-lg">
                                    <History class="h-5 w-5 text-indigo-600" />
                                </div>
                                <CardTitle class="text-xl font-black text-slate-900 tracking-tight">Audit History</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="p-4">
                            <div v-if="audits && audits.length > 0" class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-100 before:to-transparent">
                                <div v-for="audit in audits" :key="audit.id" class="relative flex items-center gap-6 group">
                                    <!-- Icon Circle -->
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-slate-50 shadow-sm shrink-0 z-10 group-hover:bg-white transition-colors">
                                        <CheckCircle2 v-if="audit.event === 'created'" class="h-4 w-4 text-emerald-500" />
                                        <PencilIcon v-else-if="audit.event === 'updated'" class="h-4 w-4 text-indigo-500" />
                                        <Trash2Icon v-else-if="audit.event === 'deleted'" class="h-4 w-4 text-red-500" />
                                        <Shield v-else class="h-4 w-4 text-slate-400" />
                                    </div>
                                    
                                    <!-- Log Details -->
                                    <div class="flex-1 bg-white p-5 rounded-3xl border border-slate-100 shadow-sm group-hover:border-indigo-100 transition-all">
                                        <div class="flex items-center justify-between gap-4 mb-3">
                                            <div class="flex items-center gap-3">
                                                <Badge variant="outline" class="text-[10px] font-black uppercase tracking-widest border-slate-200">
                                                    {{ audit.event }}
                                                </Badge>
                                                <span class="text-xs font-bold text-slate-700">by <span class="text-indigo-600 underline underline-offset-4 decoration-indigo-200">{{ audit.user?.name || 'System' }}</span></span>
                                            </div>
                                            <time class="font-mono text-[10px] text-slate-400 font-bold uppercase">{{ new Date(audit.created_at).toLocaleString() }}</time>
                                        </div>

                                        <!-- Diff Snippet -->
                                        <div v-if="audit.new_values && Object.keys(audit.new_values).length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                            <div v-for="(val, key) in audit.new_values" :key="key" class="bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-tight">{{ key }}</p>
                                                <p class="text-[11px] font-bold text-slate-800 truncate">{{ val }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Empty State Log -->
                            <div v-else class="py-20 text-center">
                                <History class="mx-auto h-16 w-16 text-slate-100 mb-4" />
                                <h3 class="text-lg font-black text-slate-900 tracking-tight">Pristine Record</h3>
                                <p class="text-sm text-slate-500 font-medium">No modifications have been recorded for this book yet.</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Edit Copy Dialog -->
            <Dialog v-model:open="showEditDialog">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Edit Book Copy</DialogTitle>
                        <DialogDescription>
                            Update the details of this book copy.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">

                        <!-- Call Number -->
                        <div class="grid gap-2">
                            <Label for="call_number">
                                Call Number
                            </Label>
                            <Input
                                id="call_number"
                                v-model="editForm.call_number"
                                placeholder="e.g., 813.54 FIT"
                                :class="{ 'border-destructive': errors.call_number }"
                            />
                            <p v-if="errors.call_number" class="text-xs text-destructive">
                                {{ errors.call_number }}
                            </p>
                        </div>

                        <!-- Condition and Status -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Condition -->
                            <div class="grid gap-2">
                                <Label for="condition">
                                    Condition <span class="text-destructive">*</span>
                                </Label>
                                <Select v-model="editForm.condition">
                                    <SelectTrigger id="condition">
                                        <SelectValue placeholder="Select condition" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="excellent">Excellent</SelectItem>
                                        <SelectItem value="good">Good</SelectItem>
                                        <SelectItem value="fair">Fair</SelectItem>
                                        <SelectItem value="poor">Poor</SelectItem>
                                        <SelectItem value="damaged">Damaged</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.condition" class="text-xs text-destructive">
                                    {{ errors.condition }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div class="grid gap-2">
                                <Label for="status">
                                    Status <span class="text-destructive">*</span>
                                </Label>
                                <Select v-model="editForm.status">
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="available">Available</SelectItem>
                                        <SelectItem value="borrowed">Borrowed</SelectItem>
                                        <SelectItem value="reserved">Reserved</SelectItem>
                                        <SelectItem value="maintenance">Maintenance</SelectItem>
                                        <SelectItem value="lost">Lost</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.status" class="text-xs text-destructive">
                                    {{ errors.status }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="library">
                                Library Branch <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="editForm.library_id">
                                <SelectTrigger id="library">
                                    <SelectValue placeholder="Select library" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">
                                        {{ lib.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="errors.library_id" class="text-xs text-destructive">
                                {{ errors.library_id }}
                            </p>
                        </div>

                        <!-- Location -->
                        <div class="grid gap-2">
                            <Label for="location">
                                Placement Location (e.g. Shelf ID)
                            </Label>
                            <Input
                                id="location"
                                v-model="editForm.location"
                                placeholder="e.g., Shelf A1"
                                :class="{ 'border-destructive': errors.location }"
                            />
                            <p v-if="errors.location" class="text-xs text-destructive">
                                {{ errors.location }}
                            </p>
                        </div>

                        <!-- Acquisition Date and Price -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Acquisition Date -->
                            <div class="grid gap-2">
                                <Label for="acquisition_date">
                                    Acquisition Date
                                </Label>
                                <Input
                                    id="acquisition_date"
                                    v-model="editForm.acquisition_date"
                                    type="date"
                                    :class="{ 'border-destructive': errors.acquisition_date }"
                                />
                                <p v-if="errors.acquisition_date" class="text-xs text-destructive">
                                    {{ errors.acquisition_date }}
                                </p>
                            </div>

                            <!-- Acquisition Price -->
                            <div class="grid gap-2">
                                <Label for="acquisition_price">
                                    Acquisition Price
                                </Label>
                                <Input
                                    id="acquisition_price"
                                    v-model="editForm.acquisition_price"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    :class="{ 'border-destructive': errors.acquisition_price }"
                                />
                                <p v-if="errors.acquisition_price" class="text-xs text-destructive">
                                    {{ errors.acquisition_price }}
                                </p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="grid gap-2">
                            <Label for="notes">
                                Notes
                            </Label>
                            <Textarea
                                id="notes"
                                v-model="editForm.notes"
                                placeholder="Any additional notes about this copy..."
                                rows="3"
                                :class="{ 'border-destructive': errors.notes }"
                            />
                            <p v-if="errors.notes" class="text-xs text-destructive">
                                {{ errors.notes }}
                            </p>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditDialog = false"
                            :disabled="processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            @click="updateCopy"
                            :disabled="processing"
                        >
                            <span v-if="processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
