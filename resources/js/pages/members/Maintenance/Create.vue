<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    AlertTriangle,
    Send,
    Camera,
    X,
    LifeBuoy,
    Clock,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';

const props = defineProps<{
    categories: string[];
    priorities: Record<string, string>;
}>();

const form = useForm({
    title: '',
    category: '',
    description: '',
    priority: 'medium',
    image: null as File | null,
});

const breadcrumbs = [
    { title: 'Report Issue', href: route('member.maintenance.index') },
    { title: 'New Report', href: '#' },
];

const submit = () => {
    form.post(route('maintenance.store'), {
        preserveScroll: true,
    });
};

// ── Image upload with preview ─────────────────────────────────
const imagePreviewUrl = ref<string | null>(null);

const onFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    if (imagePreviewUrl.value) URL.revokeObjectURL(imagePreviewUrl.value);
    imagePreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

const clearImage = () => {
    form.image = null;
    if (imagePreviewUrl.value) URL.revokeObjectURL(imagePreviewUrl.value);
    imagePreviewUrl.value = null;
    const input = document.getElementById('image-upload') as HTMLInputElement | null;
    if (input) input.value = '';
};

// ── Priority as a visual, color-coded button group ─────────────
// Native <select> hides the difference between "just a heads up" and
// "this is urgent" behind identical plain text. Buttons let severity
// be seen at a glance, which is the whole point of the field.
const priorityStyles: Record<string, { dot: string; ring: string; text: string }> = {
    low:      { dot: 'bg-emerald-500', ring: 'ring-emerald-200 border-emerald-300 bg-emerald-50', text: 'text-emerald-800' },
    medium:   { dot: 'bg-[#c5a059]',   ring: 'ring-[#c5a059]/30 border-[#c5a059] bg-[#c5a059]/10', text: 'text-[#7a5f2e]' },
    high:     { dot: 'bg-orange-500',  ring: 'ring-orange-200 border-orange-300 bg-orange-50', text: 'text-orange-800' },
    urgent:   { dot: 'bg-red-500',     ring: 'ring-red-200 border-red-300 bg-red-50', text: 'text-red-800' },
    critical: { dot: 'bg-red-500',     ring: 'ring-red-200 border-red-300 bg-red-50', text: 'text-red-800' },
};
const fallbackStyle = { dot: 'bg-slate-400', ring: 'ring-slate-200 border-slate-300 bg-slate-50', text: 'text-slate-700' };
const styleFor = (key: string) => priorityStyles[key.toLowerCase()] ?? fallbackStyle;

const priorityEntries = computed(() => Object.entries(props.priorities));
</script>

<template>
    <Head title="Report Issue" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">

            <PageHeader title="New Report" description="Fill in the details below to help us understand the issue." />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                <!-- ── Main Form ─────────────────────────────────── -->
                <Card class="lg:col-span-2 border-slate-200 rounded-3xl overflow-hidden">
                    <CardContent class="p-5 md:p-8">
                        <form @submit.prevent="submit" class="space-y-7">
                            <div class="space-y-2">
                                <Label for="title" class="text-xs font-black uppercase tracking-widest text-slate-400">Issue Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="e.g., Broken study desk light"
                                    class="h-12 rounded-xl border-slate-200 focus-visible:ring-[#c5a059]"
                                    :class="{ 'border-red-300': form.errors.title }"
                                    required
                                />
                                <p v-if="form.errors.title" class="text-xs text-red-500 font-bold">{{ form.errors.title }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="category" class="text-xs font-black uppercase tracking-widest text-slate-400">Category</Label>
                                <Select v-model="form.category">
                                    <SelectTrigger id="category" class="w-full h-12 rounded-xl border-slate-200"
                                        :class="{ 'border-red-300': form.errors.category }">
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="text-xs text-red-500 font-bold">{{ form.errors.category }}</p>
                            </div>

                            <!-- Priority: color-coded button group instead of a plain dropdown -->
                            <div class="space-y-2">
                                <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Severity</Label>
                                <div class="grid gap-2" :class="priorityEntries.length > 2 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-2'">
                                    <button
                                        v-for="[value, label] in priorityEntries"
                                        :key="value"
                                        type="button"
                                        @click="form.priority = value"
                                        class="flex items-center gap-2 h-12 px-3 rounded-xl border text-sm font-bold transition-all"
                                        :class="form.priority === value
                                            ? [styleFor(value).ring, styleFor(value).text, 'ring-2']
                                            : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <span class="h-2 w-2 rounded-full shrink-0" :class="styleFor(value).dot" />
                                        <span class="truncate">{{ label }}</span>
                                    </button>
                                </div>
                                <p v-if="form.errors.priority" class="text-xs text-red-500 font-bold">{{ form.errors.priority }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description" class="text-xs font-black uppercase tracking-widest text-slate-400">Detailed Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Please describe what is wrong and its location..."
                                    class="min-h-[120px] rounded-xl border-slate-200 focus-visible:ring-[#c5a059] py-3"
                                    :class="{ 'border-red-300': form.errors.description }"
                                    required
                                />
                                <p v-if="form.errors.description" class="text-xs text-red-500 font-bold">{{ form.errors.description }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Upload Photo (Optional)</Label>

                                <input
                                    type="file"
                                    @change="onFileChange"
                                    class="hidden"
                                    id="image-upload"
                                    accept="image/*"
                                />

                                <!-- Preview state -->
                                <div v-if="imagePreviewUrl" class="relative rounded-3xl overflow-hidden border border-slate-200 group">
                                    <img :src="imagePreviewUrl" class="w-full h-48 object-cover" alt="Upload preview" />
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors" />
                                    <button
                                        type="button"
                                        @click="clearImage"
                                        class="absolute top-3 right-3 h-9 w-9 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition-colors"
                                        aria-label="Remove photo"
                                    >
                                        <X class="h-4 w-4 text-slate-700" />
                                    </button>
                                    <p class="absolute bottom-0 inset-x-0 px-4 py-2 text-xs font-bold text-white bg-gradient-to-t from-black/60 to-transparent truncate">
                                        {{ (form.image as File)?.name }}
                                    </p>
                                </div>

                                <!-- Empty state -->
                                <label
                                    v-else
                                    for="image-upload"
                                    class="flex flex-col items-center justify-center p-6 md:p-8 border-2 border-dashed border-slate-200 rounded-3xl hover:border-[#c5a059] hover:bg-[#c5a059]/5 transition-all cursor-pointer group"
                                >
                                    <div class="bg-white p-3 rounded-full border border-slate-100 mb-3 group-hover:scale-110 transition-transform">
                                        <Camera class="h-6 w-6 text-slate-400 group-hover:text-[#c5a059]" />
                                    </div>
                                    <p class="text-sm font-bold text-slate-600">Click to upload image</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-black">PNG, JPG up to 2MB</p>
                                </label>
                                <p v-if="form.errors.image" class="text-xs text-red-500 font-bold">{{ form.errors.image }}</p>
                            </div>

                            <div class="pt-2 flex flex-col sm:flex-row gap-3">
                                <Button
                                    type="submit"
                                    class="w-full sm:flex-1 bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] rounded-xl h-12 font-bold transition-all active:scale-95 disabled:opacity-50"
                                    :disabled="form.processing"
                                >
                                    <Send class="h-4 w-4 mr-2" />
                                    {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                                </Button>
                                <Link :href="route('member.maintenance.index')" class="w-full sm:flex-1">
                                    <Button type="button" variant="outline" class="w-full rounded-xl h-12 font-bold hover:bg-slate-50">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- ── Sidebar ───────────────────────────────────── -->
                <div class="space-y-6">
                    <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 bg-white h-10 w-10 rounded-xl flex items-center justify-center border border-amber-200">
                                <AlertTriangle class="h-5 w-5 text-amber-500" />
                            </div>
                            <h4 class="text-sm font-black text-amber-900 uppercase tracking-widest">Emergency?</h4>
                        </div>
                        <p class="text-xs text-amber-700 font-medium leading-relaxed">
                            For urgent safety issues (wire sparks, flooding, etc.), please inform the librarian at the help desk immediately instead of using this form.
                        </p>
                    </div>

                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 bg-white h-10 w-10 rounded-xl flex items-center justify-center border border-slate-200">
                                <Clock class="h-5 w-5 text-slate-500" />
                            </div>
                            <h4 class="text-sm font-black text-slate-700 uppercase tracking-widest">What happens next</h4>
                        </div>
                        <ol class="space-y-3 text-xs text-slate-500 font-medium leading-relaxed">
                            <li class="flex gap-2">
                                <span class="font-black text-slate-700">1.</span>
                                Your report is sent to the facilities team for review.
                            </li>
                            <li class="flex gap-2">
                                <span class="font-black text-slate-700">2.</span>
                                Severity determines how quickly it's scheduled.
                            </li>
                            <li class="flex gap-2">
                                <span class="font-black text-slate-700">3.</span>
                                You'll see status updates on your reports page.
                            </li>
                        </ol>
                    </div>

                    <div class="p-6 bg-white rounded-3xl border border-slate-200 flex items-start gap-3">
                        <div class="shrink-0 bg-slate-50 h-10 w-10 rounded-xl flex items-center justify-center border border-slate-100">
                            <LifeBuoy class="h-5 w-5 text-slate-400" />
                        </div>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">
                            Not sure which category fits? Pick the closest one — staff can recategorize it after review.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>