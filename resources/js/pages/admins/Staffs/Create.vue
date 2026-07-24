<script setup lang="ts">
import { route } from 'ziggy-js'
import { reactive, ref, computed } from 'vue'
import { router, Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import FlashAlert from '@/components/FlashAlert.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { type BreadcrumbItem } from '@/types'
import { ClipboardList, UserIcon, BriefcaseIcon, LockIcon, PhoneIcon, MailIcon, Clock, Calendar } from 'lucide-vue-next'

const props = defineProps<{
    roles: Array<{ id: number, name: string }>;
    departments: Array<{ id: number, name: string, library_id?: number | null }>;
    libraries: Array<{ id: number, name: string }>;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'System Staff', href: route('admin.staffs.index') },
    { title: 'Add New Staff', href: route('admin.staffs.create') },
]

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    status: 'active',
    role: 'librarian',
    employee_id: '',
    hire_date: new Date().toISOString().split('T')[0],
    department_id: 'none',
    library_id: '',
    position: '',
    notes: '',
})

const filteredDepartments = computed(() => {
    if (!form.library_id) return [];
    return props.departments.filter(d => d.library_id === Number(form.library_id));
});

const isDepartmentDialogOpen = ref(false)
const departmentForm = useForm({
    name: '',
    code: '',
    description: '',
})

const openAddDepartment = () => {
    departmentForm.reset()
    departmentForm.clearErrors()
    isDepartmentDialogOpen.value = true
}

const submitDepartment = () => {
    departmentForm.post(route('admin.departments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const created = (page.props.flash as any)?.created_department;
            if (created) {
                form.department_id = created.id.toString();
            }
            isDepartmentDialogOpen.value = false;
            departmentForm.reset();
        },
    });
};

const submit = () => {
    const data = { ...form }
    if (data.department_id === 'none') data.department_id = ''
    router.post(route('admin.staffs.store'), data as any)
}
</script>

<template>
    <Head title="Add Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto space-y-6">

            <FlashAlert />

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Add New Staff <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium tracking-tight">Create a new system account and associate it with a department.</p>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('admin.staffs.index')">
                        <Button variant="ghost" class="rounded-xl font-bold">Cancel</Button>
                    </Link>
                    <Button @click="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-8 font-black shadow-lg shadow-indigo-100 dark:shadow-none">
                        Create Staff Member
                    </Button>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Left Column: Personal Info -->
                <div class="lg:col-span-2 space-y-6">
                    <Card class="border-slate-100 shadow-sm rounded-3xl overflow-hidden">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <UserIcon class="h-4 w-4 text-indigo-600" />
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Personal Information</h2>
                        </div>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Full Name</Label>
                                    <div class="relative">
                                        <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input v-model="form.name" placeholder="Enter full name" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Email Address</Label>
                                    <div class="relative">
                                        <MailIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input type="email" v-model="form.email" placeholder="staff@library.com" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Phone Number</Label>
                                    <div class="relative">
                                        <PhoneIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input v-model="form.phone" placeholder="+60123456789" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Account Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="rounded-xl">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                            <SelectItem value="suspended">Suspended</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-slate-100 shadow-sm rounded-3xl overflow-hidden">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <LockIcon class="h-4 w-4 text-indigo-600" />
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Security</h2>
                        </div>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Password</Label>
                                    <div class="relative">
                                        <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input type="password" v-model="form.password" placeholder="••••••••" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Confirm Password</Label>
                                    <div class="relative">
                                        <LockIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input type="password" v-model="form.password_confirmation" placeholder="••••••••" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Employment Details -->
                <div class="space-y-6">
                    <Card class="border-slate-100 shadow-sm rounded-3xl overflow-hidden">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <BriefcaseIcon class="h-4 w-4 text-indigo-600" />
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Employment</h2>
                        </div>
                        <CardContent class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Assigned Library</Label>
                                    <Select v-model="form.library_id" @update:model-value="form.department_id = 'none'">
                                        <SelectTrigger class="rounded-xl font-bold" :class="{ 'border-destructive': page.props.errors.library_id }">
                                            <SelectValue placeholder="Select Library"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">
                                                {{ lib.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="page.props.errors.library_id" class="text-xs text-destructive">
                                        {{ page.props.errors.library_id }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Department</Label>
                                    <Select v-model="form.department_id" :disabled="!form.library_id">
                                        <SelectTrigger class="rounded-xl font-bold">
                                            <SelectValue placeholder="Select Department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id.toString()">
                                                {{ dept.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="!form.library_id" class="text-[10px] text-slate-400">Select a library first</p>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Cannot find the department?
                                        <button type="button" @click="openAddDepartment"
                                            class="text-indigo-600 font-bold hover:underline">
                                            Create a new department
                                        </button>
                                    </p>

                                    <Dialog v-model:open="isDepartmentDialogOpen">
                                        <DialogContent class="sm:max-w-[425px] rounded-3xl">
                                            <form @submit.prevent="submitDepartment">
                                                <DialogHeader>
                                                    <DialogTitle class="text-xl font-black">New Department</DialogTitle>
                                                    <DialogDescription class="font-medium">
                                                        Add a new department to the system.
                                                    </DialogDescription>
                                                </DialogHeader>

                                                <div class="grid gap-4 py-4">
                                                    <div class="space-y-2">
                                                        <Label for="dept_name" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Name</Label>
                                                        <Input id="dept_name" v-model="departmentForm.name" placeholder="e.g. Finance" class="rounded-xl"
                                                            :class="{ 'border-destructive': departmentForm.errors.name }" />
                                                        <span v-if="departmentForm.errors.name" class="text-xs text-destructive">
                                                            {{ departmentForm.errors.name }}
                                                        </span>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <Label for="dept_code" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Code</Label>
                                                        <Input id="dept_code" v-model="departmentForm.code" placeholder="e.g. FIN" @input="departmentForm.code = departmentForm.code.toUpperCase()"
                                                            class="rounded-xl font-mono uppercase"
                                                            :class="{ 'border-destructive': departmentForm.errors.code }" />
                                                        <span v-if="departmentForm.errors.code" class="text-xs text-destructive">
                                                            {{ departmentForm.errors.code }}
                                                        </span>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <Label for="dept_description" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Description</Label>
                                                        <Input id="dept_description" v-model="departmentForm.description" placeholder="Short description..." class="rounded-xl" />
                                                    </div>
                                                </div>

                                                <DialogFooter>
                                                    <Button type="button" variant="ghost" @click="isDepartmentDialogOpen = false" class="rounded-xl font-bold">
                                                        Cancel
                                                    </Button>
                                                    <Button type="submit" :disabled="departmentForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black px-6">
                                                        Save Department
                                                    </Button>
                                                </DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Position / Title</Label>
                                    <Input v-model="form.position" placeholder="e.g. Senior Librarian" class="rounded-xl" />
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Employee ID</Label>
                                    <Input v-model="form.employee_id" placeholder="Auto-generated if blank" class="rounded-xl font-mono text-xs" />
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Hire Date</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" />
                                        <Input type="date" v-model="form.hire_date" class="pl-10 rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-slate-100 shadow-sm rounded-3xl overflow-hidden">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                            <ClipboardList class="h-4 w-4 text-indigo-600" />
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest">Notes</h2>
                        </div>
                        <CardContent class="p-6">
                            <textarea 
                                v-model="form.notes" 
                                rows="4" 
                                placeholder="Internal employment notes..."
                                class="w-full rounded-2xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2"
                            ></textarea>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                <Link :href="route('admin.staffs.index')">
                    <Button variant="ghost" class="rounded-xl font-bold px-8">Cancel</Button>
                </Link>
                <Button @click="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-12 h-14 font-black shadow-xl shadow-indigo-100 dark:shadow-none transition-all active:scale-95">
                    Create Staff Member
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
