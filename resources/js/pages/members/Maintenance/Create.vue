<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { 
    AlertTriangle, 
    ChevronLeft,
    Send,
    Hammer,
    Camera
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

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

const onFileChange = (e: any) => {
    form.image = e.target.files[0];
};

</script>

<template>
    <Head title="Report Issue" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            
            <!-- Header Section -->
            <div class="space-y-1">
                <h1 class="text-3xl font-black tracking-tight text-slate-900">New Report <span class="text-red-600 text-6xl leading-none">.</span></h1>
                <p class="text-slate-500 font-medium text-sm">Fill in the details below to help us understand the issue.</p>
            </div>

            <Card class="border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                <CardContent class="p-5 md:p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="title" class="text-xs font-black uppercase tracking-widest text-slate-400">Issue Title</Label>
                            <Input 
                                id="title" 
                                v-model="form.title" 
                                placeholder="e.g., Broken study desk light" 
                                class="h-12 rounded-xl border-slate-200 focus-visible:ring-red-500"
                                required
                            />
                            <div v-if="form.errors.title" class="text-xs text-red-500 font-bold mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="category" class="text-xs font-black uppercase tracking-widest text-slate-400">Category</Label>
                                <select 
                                    id="category" 
                                    v-model="form.category" 
                                    class="w-full h-12 rounded-xl border-slate-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                    required
                                >
                                    <option value="" disabled>Select a category</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                                <div v-if="form.errors.category" class="text-xs text-red-500 font-bold mt-1">{{ form.errors.category }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="priority" class="text-xs font-black uppercase tracking-widest text-slate-400">Severity</Label>
                                <select 
                                    id="priority" 
                                    v-model="form.priority" 
                                    class="w-full h-12 rounded-xl border-slate-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                    required
                                >
                                    <option v-for="(label, value) in priorities" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-xs font-black uppercase tracking-widest text-slate-400">Detailed Description</Label>
                            <Textarea 
                                id="description" 
                                v-model="form.description" 
                                placeholder="Please describe what is wrong and its location..." 
                                class="min-h-[120px] rounded-xl border-slate-200 focus-visible:ring-red-500 py-3"
                                required
                            />
                            <div v-if="form.errors.description" class="text-xs text-red-500 font-bold mt-1">{{ form.errors.description }}</div>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Upload Photo (Optional)</Label>
                            <div class="relative group mt-1">
                                <input 
                                    type="file" 
                                    @change="onFileChange" 
                                    class="hidden" 
                                    id="image-upload"
                                    accept="image/*"
                                />
                                <label 
                                    for="image-upload" 
                                    class="flex flex-col items-center justify-center p-6 md:p-8 border-2 border-dashed border-slate-200 rounded-3xl hover:border-red-500 hover:bg-red-50/50 transition-all cursor-pointer group"
                                >
                                    <div class="bg-white p-3 rounded-full shadow-sm border border-slate-100 mb-3 group-hover:scale-110 transition-transform">
                                        <Camera class="h-6 w-6 text-slate-400 group-hover:text-red-500" />
                                    </div>
                                    <p class="text-sm font-bold text-slate-600">Click to upload image</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-black">PNG, JPG up to 2MB</p>
                                    <p v-if="form.image" class="mt-4 text-xs font-black text-red-600 bg-red-50 px-3 py-1 rounded-full">{{ (form.image as any).name }}</p>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row gap-3">
                            <Button 
                                type="submit" 
                                class="w-full sm:flex-1 bg-red-600 hover:bg-red-700 text-white rounded-xl h-12 font-bold shadow-lg shadow-red-100 transition-all active:scale-95 disabled:opacity-50"
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

            <!-- Tip Section -->
            <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100 flex flex-col sm:flex-row gap-4">
                <div class="shrink-0 bg-white h-10 w-10 rounded-xl flex items-center justify-center shadow-sm border border-amber-200">
                    <AlertTriangle class="h-5 w-5 text-amber-500" />
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-black text-amber-900 uppercase tracking-widest">Emergency?</h4>
                    <p class="text-xs text-amber-700 font-medium leading-relaxed">For urgent safety issues (wire sparks, flooding, etc.), please inform the librarian at the help desk immediately instead of using this form.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
