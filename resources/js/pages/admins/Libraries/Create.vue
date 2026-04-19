<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import { MapPin, Phone, Mail, Clock, Library, ArrowLeft, Save, Link2, Loader2, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Libraries', href: route('admin.libraries.index') },
    { title: 'New Branch', href: '#' },
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
        <div class="max-w-4xl mx-auto py-10 px-6 space-y-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <Link :href="route('admin.libraries.index')" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 mb-2 transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Back to List
                    </Link>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-xl text-indigo-600">
                            <Library class="h-8 w-8" />
                        </div>
                        New Library Branch
                    </h1>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Main Information -->
                <Card class="border-none shadow-xl shadow-slate-200/50 overflow-hidden rounded-3xl">
                    <CardHeader class="pb-2 border-b border-slate-50">
                        <CardTitle class="text-xl font-bold">General Information</CardTitle>
                        <CardDescription>Basic details about the library branch.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2 space-y-3">
                                <Label for="name" class="text-sm font-bold text-slate-700">Library Name <span class="text-red-500">*</span></Label>
                                <Input id="name" v-model="form.name" placeholder="e.g. Central City Library" class="h-12 rounded-xl border-slate-200 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.name }" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 font-bold uppercase tracking-wider">{{ form.errors.name }}</p>
                            </div>

                            <div class="md:col-span-2 space-y-3">
                                <Label for="address" class="text-sm font-bold text-slate-700">Physical Address <span class="text-red-500">*</span></Label>
                                <Textarea id="address" v-model="form.address" placeholder="Full address and floor details..." rows="3" class="rounded-xl border-slate-200 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.address }" />
                                <p v-if="form.errors.address" class="text-xs text-red-500 font-bold uppercase tracking-wider">{{ form.errors.address }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contact & Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <Card class="border-none shadow-xl shadow-slate-200/50 overflow-hidden rounded-3xl">
                        <CardHeader class="pb-2 border-b border-slate-50">
                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                <Phone class="h-5 w-5 text-indigo-600" />
                                Contact Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 space-y-5">
                            <div class="space-y-2">
                                <Label for="phone" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Phone Number</Label>
                                <Input id="phone" v-model="form.phone" placeholder="+60 123 456 789" class="h-11 rounded-xl" />
                            </div>
                            <div class="space-y-2">
                                <Label for="email" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Email Address</Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="branch@library.com" class="h-11 rounded-xl" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-none shadow-xl shadow-slate-200/50 overflow-hidden rounded-3xl">
                        <CardHeader class="pb-2 border-b border-slate-50">
                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                <Clock class="h-5 w-5 text-indigo-600" />
                                Operating Hours
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 space-y-5">
                            <div class="space-y-2">
                                <Label for="opening_hours" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Schedule</Label>
                                <Input id="opening_hours" v-model="form.opening_hours" placeholder="e.g. Mon-Fri: 9AM - 9PM" class="h-11 rounded-xl" />
                            </div>
                            <div class="flex items-center space-x-3 pt-4">
                                <input type="checkbox" id="is_active" v-model="form.is_active" class="h-5 w-5 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <Label for="is_active" class="text-sm font-bold text-slate-700 cursor-pointer">Set as Active Branch</Label>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Geolocation -->
                <Card class="border-none shadow-xl shadow-slate-200/50 overflow-hidden rounded-3xl bg-slate-900 text-white">
                    <CardHeader class="pb-2 border-b border-slate-800">
                        <CardTitle class="text-xl font-bold flex items-center gap-2">
                            <MapPin class="h-5 w-5 text-indigo-400" />
                            Geolocation Data
                        </CardTitle>
                        <CardDescription class="text-slate-400 text-sm">Paste a Google Maps link to auto-fill coordinates, or enter them manually.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-8 space-y-6">
                        <!-- Google Maps Link Extractor -->
                        <div class="space-y-3">
                            <Label class="text-sm font-bold text-slate-300 flex items-center gap-2">
                                <Link2 class="h-4 w-4 text-indigo-400" />
                                Extract from Google Maps Link
                            </Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="mapLink"
                                    placeholder="https://maps.app.goo.gl/..."
                                    class="h-12 rounded-xl bg-slate-800 border-slate-700 text-white focus:ring-indigo-500 placeholder:text-slate-500 flex-1"
                                    @keyup.enter="extractFromMapLink"
                                />
                                <Button
                                    type="button"
                                    @click="extractFromMapLink"
                                    :disabled="mapLinkResolving || !mapLink.trim()"
                                    class="h-12 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shrink-0 disabled:opacity-50"
                                >
                                    <Loader2 v-if="mapLinkResolving" class="h-4 w-4 animate-spin" />
                                    <span v-else>Extract</span>
                                </Button>
                            </div>
                            <p v-if="mapLinkError" class="text-xs text-red-400 font-bold flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5" /> {{ mapLinkError }}
                            </p>
                            <p v-if="mapLinkSuccess" class="text-xs text-emerald-400 font-bold flex items-center gap-1">
                                <CheckCircle2 class="h-3.5 w-3.5" /> Coordinates extracted successfully!
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-700" />
                            <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">or enter manually</span>
                            <div class="h-px flex-1 bg-slate-700" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <Label for="latitude" class="text-sm font-bold text-slate-300">Latitude</Label>
                                <Input id="latitude" v-model="form.latitude" type="number" step="any" placeholder="3.1390" class="h-12 rounded-xl bg-slate-800 border-slate-700 text-white focus:ring-indigo-500 placeholder:text-slate-500" />
                                <p v-if="form.errors.latitude" class="text-xs text-red-400 font-bold uppercase tracking-wider">{{ form.errors.latitude }}</p>
                            </div>

                            <div class="space-y-3">
                                <Label for="longitude" class="text-sm font-bold text-slate-300">Longitude</Label>
                                <Input id="longitude" v-model="form.longitude" type="number" step="any" placeholder="101.6869" class="h-12 rounded-xl bg-slate-800 border-slate-700 text-white focus:ring-indigo-500 placeholder:text-slate-500" />
                                <p v-if="form.errors.longitude" class="text-xs text-red-400 font-bold uppercase tracking-wider">{{ form.errors.longitude }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4 pt-4 pb-12">
                    <Link :href="route('admin.libraries.index')">
                        <Button type="button" variant="outline" class="h-12 px-8 rounded-2xl font-bold text-slate-600 hover:bg-slate-50 border-slate-200 transition-all">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="h-12 px-10 rounded-2xl font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                        <Save v-if="!form.processing" class="h-5 w-5" />
                        {{ form.processing ? 'Registering...' : 'Register Branch' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
