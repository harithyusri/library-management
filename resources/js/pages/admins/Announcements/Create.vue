<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps<{
    libraries: Array<{ id: number, name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Announcements', href: route('admin.announcements.index') },
    { title: 'Create', href: route('admin.announcements.create') },
];

const form = useForm({
    title: '',
    content: '',
    image: null as File | null,
    is_active: true,
    expires_at: '',
    library_id: null as number | null,
});

const submit = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
    });
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
    }
};

const editorOptions = {
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
};
</script>

<template>
    <Head title="Create Announcement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 max-w-4xl mx-auto w-full">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Create Announcement
                </h1>
                <Link :href="route('admin.announcements.index')" class="text-sm font-medium text-muted-foreground hover:text-foreground">
                    Back to List
                </Link>
            </div>

            <div class="rounded-xl border border-border bg-background p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-foreground mb-1">Title *</label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-foreground focus:ring-ring focus:border-ring" required />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <!-- Cover Image -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-foreground mb-1">Cover Image (Optional)</label>
                        <input id="image" type="file" accept="image/*" @change="handleImageUpload" class="w-full text-foreground text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90" />
                        <p class="text-xs text-muted-foreground mt-1">Max 5MB. Recommended ratio 16:9.</p>
                        <InputError :message="form.errors.image" class="mt-2" />
                    </div>

                    <!-- Content -->
                    <div>
                        <label for="content" class="block text-sm font-medium text-foreground mb-1">Content *</label>
                        <div class="border border-input rounded-md overflow-hidden bg-background">
                            <QuillEditor 
                                theme="snow" 
                                v-model:content="form.content" 
                                contentType="html" 
                                class="min-h-[300px] bg-background text-foreground"
                                :options="editorOptions"
                            />
                        </div>
                        <InputError :message="form.errors.content" class="mt-2" />
                    </div>

                    <!-- Settings Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-border">
                        <div class="flex items-center gap-3">
                            <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input text-primary focus:ring-primary" />
                            <label for="is_active" class="text-sm font-medium text-foreground">Active (Visible to users)</label>
                            <InputError :message="form.errors.is_active" class="mt-2" />
                        </div>

                        <!-- Library Selection -->
                        <div>
                            <label for="library_id" class="block text-sm font-medium text-foreground mb-1">Target Library (Optional)</label>
                            <select id="library_id" v-model="form.library_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-foreground focus:ring-ring focus:border-ring">
                                <option :value="null">Global (Visible to all libraries)</option>
                                <option v-for="lib in libraries" :key="lib.id" :value="lib.id">
                                    {{ lib.name }}
                                </option>
                            </select>
                            <p class="text-xs text-muted-foreground mt-1">Select a library to make this announcement private to that branch.</p>
                            <InputError :message="form.errors.library_id" class="mt-2" />
                        </div>

                        <!-- Expires At -->
                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-foreground mb-1">Expires At (Optional)</label>
                            <input id="expires_at" v-model="form.expires_at" type="datetime-local" class="w-full rounded-md border border-input bg-background px-3 py-2 text-foreground focus:ring-ring focus:border-ring" />
                            <p class="text-xs text-muted-foreground mt-1">Leave blank to never expire.</p>
                            <InputError :message="form.errors.expires_at" class="mt-2" />
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-6 flex justify-end gap-3 border-t border-border mt-6">
                        <Link :href="route('admin.announcements.index')" class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Create Announcement' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AppLayout>
</template>
