<script setup lang="ts">
import { route } from "ziggy-js";
import { ref } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { DoorOpen, Upload, X } from 'lucide-vue-next';

interface Room {
    id: number;
    name: string;
    room_number: string;
    type: string;
    capacity: number;
    description: string | null;
    amenities: string[];
    floor: number | null;
    status: string;
    hourly_rate: number;
    library_id: number;
    image_url: string | null;
}

const props = defineProps<{
    room: Room;
    types: Record<string, string>;
    amenitiesList: Record<string, string>;
    libraries: Array<{ id: number, name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rooms', href: route('admin.rooms.index') },
    { title: props.room.name, href: route('admin.rooms.show', props.room.id) },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    name:        props.room.name,
    room_number: props.room.room_number,
    type:        props.room.type,
    capacity:    props.room.capacity,
    description: props.room.description ?? '',
    amenities:   Array.isArray(props.room.amenities) ? [...props.room.amenities] : [],
    floor:       props.room.floor ?? undefined,
    status:      props.room.status,
    hourly_rate: props.room.hourly_rate,
    library_id:  props.room.library_id?.toString() || '',
    image:       null as File | null,
    _method:     'PUT',
});

// Start with the existing image as preview; null means "no new file chosen"
const imagePreview = ref<string | null>(props.room.image_url ?? null);
const existingImage = ref<boolean>(!!props.room.image_url);
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const handleImageChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) loadImageFile(file);
};

const loadImageFile = (file: File) => {
    form.image = file;
    existingImage.value = false;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    existingImage.value = false;
    if (fileInput.value) fileInput.value.value = '';
};

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) loadImageFile(file);
};

const toggleAmenity = (
    amenity: string,
    checked: boolean | 'indeterminate'
) => {
    if (checked === true) {
        if (!form.amenities.includes(amenity)) {
            form.amenities.push(amenity);
        }
    } else {
        form.amenities = form.amenities.filter(a => a !== amenity);
    }
};

const submit = () => {
    const data = new FormData();
    data.append('_method', 'PUT');
    data.append('name', form.name);
    data.append('room_number', form.room_number);
    data.append('type', form.type);
    data.append('capacity', String(form.capacity));
    data.append('description', form.description ?? '');
    data.append('floor', form.floor !== undefined ? String(form.floor) : '');
    data.append('status', form.status);
    data.append('hourly_rate', String(form.hourly_rate));
    data.append('library_id', form.library_id);
    // Append each amenity individually so PHP sees amenities[]
    // Deduplicate once more before appending as a final safety measure
    Array.from(new Set(form.amenities)).forEach(a => data.append('amenities[]', a));
    if (form.image) data.append('image', form.image);

    router.post(route('admin.rooms.update', props.room.id), data, {
        onStart: () => { form.processing = true; },
        onFinish: () => { form.processing = false; },
        onError: (errors) => { form.errors = errors; },
    });
};

</script>

<template>
    <Head :title="`Edit — ${room.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
            <FlashAlert />

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <DoorOpen class="h-6 w-6" />
                        Edit Room
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Updating <span class="font-medium text-foreground">{{ room.name }}</span> &mdash; {{ room.room_number }}
                    </p>
                </div>
                <Link :href="route('admin.rooms.show', room.id)">
                    <Button variant="outline" size="sm">Cancel</Button>
                </Link>
            </div>

            <!-- Room Information Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Room Information</CardTitle>
                    <CardDescription>Update the details about this room.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6 py-6">

                    <!-- Row 1: Name + Room Number -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="name">Room Name <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Collaborative Corner"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="room_number">Room Number <span class="text-destructive">*</span></Label>
                            <Input
                                id="room_number"
                                v-model="form.room_number"
                                placeholder="e.g. L2-01"
                                :class="{ 'border-destructive': form.errors.room_number }"
                            />
                            <p v-if="form.errors.room_number" class="text-xs text-destructive">{{ form.errors.room_number }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Library Branch <span class="text-destructive">*</span></Label>
                            <Select v-model="form.library_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.library_id }">
                                    <SelectValue placeholder="Select library" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">
                                        {{ lib.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.library_id" class="text-xs text-destructive">{{ form.errors.library_id }}</p>
                        </div>
                    </div>

                    <!-- Row 2: Type + Capacity + Floor -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label>Type <span class="text-destructive">*</span></Label>
                            <Select v-model="form.type">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(label, key) in types" :key="key" :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="capacity">Capacity <span class="text-destructive">*</span></Label>
                            <Input
                                id="capacity"
                                type="number"
                                v-model="form.capacity"
                                min="1"
                                :class="{ 'border-destructive': form.errors.capacity }"
                            />
                            <p v-if="form.errors.capacity" class="text-xs text-destructive">{{ form.errors.capacity }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="floor">Floor</Label>
                            <Input
                                id="floor"
                                type="number"
                                v-model="form.floor"
                                min="0"
                                placeholder="0 for Ground"
                                :class="{ 'border-destructive': form.errors.floor }"
                            />
                            <p v-if="form.errors.floor" class="text-xs text-destructive">{{ form.errors.floor }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Provide a brief description of the room..."
                            rows="4"
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <Separator />

                    <!-- Amenities -->
                    <div class="space-y-3">
                        <Label>Amenities</Label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="(label, key) in amenitiesList" :key="key" class="flex items-center space-x-2">
                                <Checkbox
                                    :model-value="form.amenities.includes(String(key))"
                                    @update:model-value="(checked) => toggleAmenity(String(key), checked)"
                                />
                                <label :for="`amenity-${key}`">{{ label }}</label>
                            </div>
                        </div>
                        <p v-if="form.errors.amenities" class="text-xs text-destructive">{{ form.errors.amenities }}</p>
                    </div>

                    <Separator />

                    <!-- Row 3: Status + Hourly Rate -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Status <span class="text-destructive">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="available">Available</SelectItem>
                                    <SelectItem value="maintenance">Under Maintenance</SelectItem>
                                    <SelectItem value="unavailable">Unavailable</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="hourly_rate">Hourly Rate <span class="text-destructive">*</span></Label>
                            <div
                                class="flex items-center rounded-md border focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
                                :class="{ 'border-destructive': form.errors.hourly_rate }"
                            >
                                <span class="px-3 py-2 text-sm text-muted-foreground bg-muted border-r rounded-l-md select-none">RM</span>
                                <Input
                                    id="hourly_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.hourly_rate"
                                    class="border-0 rounded-l-none focus-visible:ring-0 focus-visible:ring-offset-0"
                                />
                            </div>
                            <p v-if="form.errors.hourly_rate" class="text-xs text-destructive">{{ form.errors.hourly_rate }}</p>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="space-y-2">
                        <Label>Room Image</Label>

                        <!-- No image selected -->
                        <div
                            v-if="!imagePreview"
                            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
                            :class="isDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-muted-foreground/50 hover:bg-muted/50'"
                            @click="fileInput?.click()"
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop"
                        >
                            <input
                                type="file"
                                id="image"
                                ref="fileInput"
                                class="hidden"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                            <div class="flex flex-col items-center gap-2">
                                <Upload class="h-8 w-8 text-muted-foreground" />
                                <p class="text-sm font-medium">Click to upload or drag and drop</p>
                                <p class="text-xs text-muted-foreground">PNG, JPG or WebP up to 2MB</p>
                            </div>
                        </div>

                        <!-- Image preview (existing or newly selected) -->
                        <div v-else class="space-y-2">
                            <div class="relative rounded-lg overflow-hidden border">
                                <img :src="imagePreview" alt="Room preview" class="w-full h-52 object-cover" />
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    class="absolute top-2 right-2 h-7 w-7 rounded-full shadow-md"
                                    @click="removeImage"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <!-- Option to replace -->
                            <p
                                class="text-xs text-muted-foreground underline underline-offset-2 cursor-pointer hover:text-foreground transition-colors w-fit"
                                @click="fileInput?.click()"
                            >
                                Replace image
                            </p>
                            <input
                                type="file"
                                id="image"
                                ref="fileInput"
                                class="hidden"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                        </div>

                        <p v-if="form.errors.image" class="text-xs text-destructive">{{ form.errors.image }}</p>
                    </div>

                </CardContent>
            </Card>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pb-4">
                <Link :href="route('admin.rooms.show', room.id)">
                    <Button type="button" variant="outline" :disabled="form.processing">Cancel</Button>
                </Link>
                <Button type="button" @click="submit" :disabled="form.processing" class="min-w-32">
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Changes</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>