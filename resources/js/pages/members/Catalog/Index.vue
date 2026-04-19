<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Search, Filter, BookOpen, User, Tag, Calendar, Globe, Layers, Book as BookIcon, CheckCircle2, AlertCircle, ShoppingBag } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    books: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: {
        search?: string;
        genre?: string;
        category?: string;
    };
    genres: any[];
    categories: any[];
    is_member: boolean;
}>();

const breadcrumbs = [
    { title: 'Catalog', href: route('member.catalog.index') },
];

const search = ref(props.filters.search || '');
const selectedGenre = ref(props.filters.genre || 'all');
const selectedCategory = ref(props.filters.category || 'all');

const updateFilters = debounce(() => {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (selectedGenre.value !== 'all') params.genre = selectedGenre.value;
    if (selectedCategory.value !== 'all') params.category = selectedCategory.value;

    import('@inertiajs/vue3').then(({ router }) => {
        router.get(route('member.catalog.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    });
}, 300);

watch([search, selectedGenre, selectedCategory], () => {
    updateFilters();
});

const borrowForm = useForm({});

const borrowBook = (bookId: number) => {
    if (!confirm('Are you sure you want to borrow this book?')) return;
    borrowForm.post(route('member.catalog.borrow', bookId));
};
</script>

<template>
    <Head title="Book Catalog" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-8">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Book Catalog <span class="text-emerald-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Explore thousands of books and discover your next journey.</p>
                </div>

                <div class="hidden md:block">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl px-4 py-2 flex items-center gap-3">
                        <div class="shrink-0 bg-white h-8 w-8 rounded-lg flex items-center justify-center shadow-sm">
                            <BookIcon class="h-4 w-4 text-indigo-600" />
                        </div>
                        <div class="text-xs">
                            <span class="block font-black text-indigo-900 uppercase tracking-widest leading-none">{{ books.total }}</span>
                            <span class="text-[10px] text-indigo-600 font-bold uppercase tracking-tighter">Available Titles</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discovery Toolbar -->
            <div class="sticky z-30 bg-white/80 backdrop-blur-xl flex flex-col md:flex-row items-center gap-4">
                <div class="relative w-full md:w-96 group">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                    <Input 
                        v-model="search" 
                        placeholder="Title, author, or ISBN..." 
                        class="pl-10 h-11 border-slate-200 bg-slate-50 focus-visible:ring-indigo-500 focus-visible:bg-white transition-all rounded-xl w-full"
                    />
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <select 
                        v-model="selectedGenre"
                        class="h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-sm font-medium focus:ring-2 focus:ring-indigo-500 transition-all outline-none"
                    >
                        <option value="all">All Genres</option>
                        <option v-for="genre in genres" :key="genre.id" :value="genre.id">{{ genre.name }}</option>
                    </select>

                    <select 
                        v-model="selectedCategory"
                        class="h-11 px-4 rounded-xl border border-slate-200 bg-slate-50 text-sm font-medium focus:ring-2 focus:ring-indigo-500 transition-all outline-none"
                    >
                        <option value="all">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </div>

                <div class="ml-auto hidden md:block">
                    <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
                        Showing <span class="text-indigo-600">{{ books.total }}</span> available titles
                    </p>
                </div>
            </div>

            <!-- Listing View -->
            <div v-if="books.data.length > 0" class="flex flex-col gap-4">
                <div v-for="book in books.data" :key="book.id" class="group relative flex flex-col gap-4 p-4 md:p-6 rounded-3xl border border-slate-100 bg-white hover:border-indigo-100 hover:shadow-2xl hover:shadow-indigo-100/30 transition-all duration-500 overflow-hidden">
                    
                    <!-- Top Row: Cover + Info + Right Stats -->
                    <div class="flex flex-row items-start gap-4">
                        <!-- Cover Image -->
                        <Link :href="route('member.catalog.show', book.id)" class="shrink-0 w-12 md:w-16 aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 border border-slate-200 shadow-sm transition-all duration-500 group-hover:scale-105 group-hover:shadow-md relative z-10">
                            <img 
                                v-if="book.cover_image" 
                                :src="book.cover_image" 
                                :alt="book.title"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center p-1.5 text-slate-300">
                                <BookOpen class="h-5 w-5 md:h-6 md:w-6 mb-0.5" />
                                <span class="text-[6px] font-bold uppercase tracking-wider">No Cover</span>
                            </div>
                        </Link>

                        <!-- Book Info -->
                        <div class="flex-1 min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Badge variant="outline" class="px-1.5 py-0 h-5 text-[10px] font-bold border-0 uppercase bg-emerald-50 text-emerald-600">
                                    {{ book.category?.name || 'Uncategorized' }}
                                </Badge>
                                <div class="flex gap-1 flex-wrap">
                                    <span v-for="genre in book.genres.slice(0, 3)" :key="genre.id" class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 uppercase tracking-tighter">
                                        {{ genre.name }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <h3 class="text-base md:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                                    <Link :href="route('member.catalog.show', book.id)" class="hover:text-indigo-600 transition-colors">{{ book.title }}</Link>
                                </h3>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-medium">
                                    <div class="flex items-center gap-1">
                                        <div class="h-4 w-4 rounded-full bg-slate-100 flex items-center justify-center">
                                            <User class="h-2.5 w-2.5 text-slate-400" />
                                        </div>
                                        <span>{{ book.author_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 border-l border-slate-200 pl-3">
                                        <Calendar class="h-3 w-3 text-slate-400" />
                                        <span>{{ book.published_year || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Availability + Copies + Buttons (desktop) -->
                        <div class="hidden md:flex flex-col items-end gap-3 shrink-0">
                            <!-- Availability + Copies side by side -->
                            <div class="flex items-center gap-4">
                                <Badge class="shadow-sm border-0 h-7 px-4 text-[10px] font-black uppercase tracking-wider" :class="book.available_copies > 0 ? 'bg-emerald-500 shadow-emerald-100' : 'bg-slate-400'">
                                    {{ book.available_copies > 0 ? 'Available' : 'Reserved' }}
                                </Badge>
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1">Copies</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-black text-slate-900 leading-none">{{ book.available_copies }}</span>
                                        <span class="text-xs font-bold text-slate-400">/ {{ book.total_copies }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center gap-2 relative z-10">
                                <Button 
                                    v-if="is_member && book.available_copies > 0"
                                    @click="borrowBook(book.id)"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 h-9 text-sm font-black shadow-lg shadow-indigo-100 transition-all active:scale-95 disabled:opacity-50"
                                    :disabled="borrowForm.processing"
                                >
                                    <ShoppingBag class="h-3.5 w-3.5 mr-1.5" v-if="!borrowForm.processing" />
                                    {{ borrowForm.processing ? '...' : 'Borrow' }}
                                </Button>
                                <Link :href="route('member.catalog.show', book.id)">
                                    <Button variant="outline" class="rounded-xl px-4 h-9 text-sm font-bold border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all">
                                        Details
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Mobile only - Copies + Buttons -->
                    <div class="flex items-center justify-between gap-4 pt-3 border-t border-slate-50 md:hidden relative z-10">
                        <div class="flex items-center gap-3">
                            <Badge class="shadow-sm border-0 h-6 px-3 text-[9px] font-black uppercase" :class="book.available_copies > 0 ? 'bg-emerald-500' : 'bg-slate-400'">
                                {{ book.available_copies > 0 ? 'Available' : 'Reserved' }}
                            </Badge>
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-sm font-black text-slate-900">{{ book.available_copies }}</span>
                                <span class="text-xs font-bold text-slate-400">/ {{ book.total_copies }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button 
                                v-if="is_member && book.available_copies > 0"
                                @click="borrowBook(book.id)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 h-9 text-sm font-black shadow-lg shadow-indigo-100 transition-all active:scale-95 disabled:opacity-50"
                                :disabled="borrowForm.processing"
                            >
                                <ShoppingBag class="h-3.5 w-3.5 mr-1.5" v-if="!borrowForm.processing" />
                                {{ borrowForm.processing ? '...' : 'Borrow' }}
                            </Button>
                            <Link :href="route('member.catalog.show', book.id)">
                                <Button variant="outline" class="rounded-xl px-4 h-9 text-sm font-bold border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all">
                                    Details
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center space-y-4 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                <div class="bg-white p-4 rounded-full w-20 h-20 mx-auto shadow-sm flex items-center justify-center border border-slate-100">
                    <Search class="h-10 w-10 text-slate-300" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-xl font-bold text-slate-900">No books found</h3>
                    <p class="text-slate-500">Try adjusting your filters or search terms.</p>
                </div>
                <Button variant="outline" @click="search = ''; selectedGenre = 'all'; selectedCategory = 'all'" class="rounded-xl px-6">
                    Clear all filters
                </Button>
            </div>

            <!-- Pagination -->
            <div v-if="books.data.length > 0" class="flex flex-col md:flex-row items-center justify-between gap-4 pt-10 border-t border-slate-100">
                <p class="text-sm font-medium text-slate-500">
                    Showing <span class="text-slate-900 font-bold">{{ books.data.length }}</span> of {{ books.total }} results
                </p>
                <div class="flex items-center gap-1.5">
                    <Link 
                        v-for="link in books.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        class="h-10 min-w-10 flex items-center justify-center rounded-xl px-4 text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-500 hover:text-indigo-600',
                            !link.url && 'opacity-40 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
