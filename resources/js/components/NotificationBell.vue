<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';

interface Notification {
    id: string;
    data: { message: string; url?: string; type: string };
    read_at: string | null;
    created_at: string;
}

const page = usePage<{ unread_notifications_count: number }>();
const open = ref(false);
const notifications = ref<Notification[]>([]);
const loading = ref(false);

async function fetchNotifications() {
    loading.value = true;
    const res = await fetch('/member/notifications');
    notifications.value = await res.json();
    loading.value = false;
}

function toggle() {
    open.value = !open.value;
    if (open.value && notifications.value.length === 0) fetchNotifications();
}

async function markRead(id: string, url?: string) {
    await fetch(`/member/notifications/${id}/read`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content } });
    const n = notifications.value.find(n => n.id === id);
    if (n) n.read_at = new Date().toISOString();
    if (url) { open.value = false; router.visit(url); }
}

async function markAllRead() {
    await fetch('/member/notifications/mark-all-read', { method: 'PATCH', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content } });
    notifications.value.forEach(n => { if (!n.read_at) n.read_at = new Date().toISOString(); });
    router.reload({ only: ['unread_notifications_count'] });
}

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!(e.target as Element).closest('[data-notification-bell]')) open.value = false;
    });
});
</script>

<template>
    <div class="relative" data-notification-bell>
        <button @click="toggle" class="relative flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground">
            <Bell class="h-4 w-4" />
            <span v-if="page.props.unread_notifications_count > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white">
                {{ page.props.unread_notifications_count > 9 ? '9+' : page.props.unread_notifications_count }}
            </span>
        </button>

        <div v-if="open" class="absolute right-0 top-10 z-50 w-80 rounded-xl border border-border bg-card shadow-lg">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <span class="text-sm font-semibold">Notifications</span>
                <button v-if="notifications.some(n => !n.read_at)" @click="markAllRead"
                    class="text-[11px] text-amber-600 hover:underline">Mark all read</button>
            </div>

            <div v-if="loading" class="py-8 text-center text-xs text-muted-foreground">Loading…</div>

            <ul v-else-if="notifications.length" class="max-h-80 overflow-y-auto divide-y divide-border">
                <li v-for="n in notifications" :key="n.id"
                    @click="markRead(n.id, n.data.url)"
                    class="flex cursor-pointer gap-3 px-4 py-3 transition hover:bg-muted/50"
                    :class="{ 'bg-amber-50/40': !n.read_at }">
                    <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="n.read_at ? 'bg-transparent' : 'bg-amber-500'" />
                    <span class="min-w-0">
                        <p class="text-xs leading-snug" :class="n.read_at ? 'text-muted-foreground' : 'text-foreground font-medium'">
                            {{ n.data.message }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-muted-foreground">{{ n.created_at }}</p>
                    </span>
                </li>
            </ul>

            <p v-else class="py-8 text-center text-xs text-muted-foreground">No notifications yet.</p>
        </div>
    </div>
</template>
