<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { MapPin, Phone, Clock, Link2, Loader2, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Libraries', href: route('admin.libraries.index') },
    { title: 'New Library', href: '#' },
];

const form = useForm({
    name: '',
    address: '',
    phone: '',
    email: '',
    opening_hours: '',
    latitude: '',
    longitude: '',
    is_active: true,
    max_borrow_limit: 10,
});

const mapLink = ref('');
const mapLinkResolving = ref(false);
const mapLinkError = ref('');
const mapLinkSuccess = ref(false);

const extractFromMapLink = async () => {
    if (!mapLink.value.trim()) return;
    mapLinkResolving.value = true;
    mapLinkError.value = '';
    mapLinkSuccess.value = false;

    try {
        const { data } = await axios.post(route('admin.libraries.resolve-map-link'), { url: mapLink.value.trim() });
        form.latitude = data.latitude;
        form.longitude = data.longitude;
        mapLinkSuccess.value = true;
        setTimeout(() => { mapLinkSuccess.value = false; }, 3000);
    } catch (e: any) {
        mapLinkError.value = e.response?.data?.error ?? 'Failed to resolve the link. Please check the URL and try again.';
    } finally {
        mapLinkResolving.value = false;
    }
};

const submit = () => {
    form.post(route('admin.libraries.store'));
};
</script>

<template>
    <Head title="New Library Branch" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <FlashAlert />

            <PageHeader title="New Library" description="Fill in the details below to register a new library.">
                <Link :href="route('admin.libraries.index')">
                    <Button variant="outline" size="sm">Cancel</Button>
                </Link>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- General Information -->
                <h2 class="text-lg font-semibold text-foreground">General Information</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-sm font-medium text-foreground">
                            Library Name <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Perpustakaan Kuala Lumpur"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-sm font-medium text-foreground">
                            Physical Address <span class="text-destructive">*</span>
                        </label>
                        <textarea
                            v-model="form.address"
                            rows="3"
                            placeholder="Full address and floor details..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none resize-none"
                            :class="{ 'border-destructive': form.errors.address }"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                    </div>
                </div>

                <hr />

                <!-- Contact & Schedule -->
                <h2 class="text-lg font-semibold text-foreground">Contact & Schedule</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">Phone Number</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="+60 123 456 789"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="library@example.com"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">Opening Hours</label>
                        <input
                            v-model="form.opening_hours"
                            type="text"
                            placeholder="e.g. Mon-Fri: 9AM - 9PM"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">
                            Borrow Limit <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model.number="form.max_borrow_limit"
                            type="number"
                            min="1"
                            max="50"
                            placeholder="10"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.max_borrow_limit }"
                        />
                        <p class="text-xs text-muted-foreground">Max books a member can borrow at once (1–50).</p>
                        <p v-if="form.errors.max_borrow_limit" class="text-xs text-destructive">{{ form.errors.max_borrow_limit }}</p>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <Label for="is_active" class="text-sm font-medium cursor-pointer">Set as Active</Label>
                    </div>
                </div>

                <hr />

                <!-- Geolocation -->
                <h2 class="text-lg font-semibold text-foreground">Geolocation</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-sm font-medium text-foreground">Extract from Google Maps Link</label>
                        <div class="flex gap-2">
                            <input
                                v-model="mapLink"
                                type="text"
                                placeholder="https://maps.app.goo.gl/..."
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                                @keyup.enter="extractFromMapLink"
                            />
                            <Button
                                type="button"
                                @click="extractFromMapLink"
                                :disabled="mapLinkResolving || !mapLink.trim()"
                                class="shrink-0"
                            >
                                <Loader2 v-if="mapLinkResolving" class="h-4 w-4 animate-spin mr-1" />
                                Extract
                            </Button>
                        </div>
                        <p v-if="mapLinkError" class="text-xs text-destructive flex items-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5" /> {{ mapLinkError }}
                        </p>
                        <p v-if="mapLinkSuccess" class="text-xs text-emerald-600 flex items-center gap-1">
                            <CheckCircle2 class="h-3.5 w-3.5" /> Coordinates extracted successfully!
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">Latitude</label>
                        <input
                            v-model="form.latitude"
                            type="number"
                            step="any"
                            placeholder="3.1390"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.latitude }"
                        />
                        <p v-if="form.errors.latitude" class="text-xs text-destructive">{{ form.errors.latitude }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-foreground">Longitude</label>
                        <input
                            v-model="form.longitude"
                            type="number"
                            step="any"
                            placeholder="101.6869"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.longitude }"
                        />
                        <p v-if="form.errors.longitude" class="text-xs text-destructive">{{ form.errors.longitude }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pb-4">
                    <Link :href="route('admin.libraries.index')">
                        <Button type="button" variant="outline" :disabled="form.processing">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="min-w-36">
                        <span v-if="form.processing">Registering...</span>
                        <span v-else>Register Library</span>
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
