import { ref, nextTick } from 'vue';

export interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    streaming?: boolean;
}

export interface ChatSession {
    id: number;
    title: string;
    last_message?: string;
    last_at?: string;
    message_count?: number;
}

type View = 'list' | 'conversation';

export function useChat() {
    const sessions        = ref<ChatSession[]>([]);
    const activeSession   = ref<ChatSession | null>(null);
    const messages        = ref<ChatMessage[]>([]);
    const input           = ref('');
    const isOpen          = ref(false);
    const isLoading       = ref(false);
    const isLoadingSessions = ref(false);
    const unread          = ref(0);
    const view            = ref<View>('list');
    const scrollRef       = ref<HTMLElement | null>(null);

    let abortController: AbortController | null = null;

    function getCsrfToken(): string {
        return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
    }

    async function loadSessions() {
        isLoadingSessions.value = true;
        try {
            const res  = await fetch('/member/chat', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            sessions.value = data.sessions ?? [];
        } finally {
            isLoadingSessions.value = false;
        }
    }

    async function openSession(session: ChatSession) {
        activeSession.value = session;
        messages.value      = [];
        view.value          = 'conversation';

        const res  = await fetch(`/member/chat/${session.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        messages.value          = data.messages ?? [];
        activeSession.value     = data.session;

        await scrollToBottom();
    }

    async function newConversation() {
        const res  = await fetch('/member/chat', {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        const session = data.session as ChatSession;
        sessions.value.unshift(session);
        await openSession(session);
    }

    async function send() {
        const text = input.value.trim();
        if (! text || isLoading.value || ! activeSession.value) return;

        input.value     = '';
        isLoading.value = true;

        messages.value.push({ role: 'user', content: text });

        const assistantMsg: ChatMessage = { role: 'assistant', content: '', streaming: true };
        messages.value.push(assistantMsg);
        const idx = messages.value.length - 1;

        await scrollToBottom();

        abortController = new AbortController();

        try {
            const res = await fetch(`/member/chat/${activeSession.value.id}/stream`, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'text/event-stream',
                },
                body:   JSON.stringify({ message: text }),
                signal: abortController.signal,
            });

            const reader  = res.body!.getReader();
            const decoder = new TextDecoder();
            let   buffer  = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() ?? '';

                for (const line of lines) {
                    if (! line.startsWith('data: ')) continue;
                    const payload = line.slice(6).trim();
                    if (payload === '[DONE]') break;

                    try {
                        const parsed = JSON.parse(payload);
                        messages.value[idx].content += parsed.text ?? '';
                        await scrollToBottom();
                    } catch {
                        // malformed chunk — skip
                    }
                }
            }
        } catch (err: any) {
            if (err.name !== 'AbortError') {
                messages.value[idx].content = 'Sorry, something went wrong. Please try again.';
            }
        } finally {
            messages.value[idx].streaming = false;
            isLoading.value = false;

            // Update session title in list if it was just set (first message)
            if (activeSession.value && activeSession.value.title === 'New conversation') {
                activeSession.value.title = text.substring(0, 60);
                const s = sessions.value.find(s => s.id === activeSession.value!.id);
                if (s) s.title = activeSession.value.title;
            }

            // Update last_message preview in session list
            const s = sessions.value.find(s => s.id === activeSession.value?.id);
            if (s) {
                s.last_message = messages.value[idx].content.substring(0, 80);
                s.last_at      = 'just now';
            }

            if (! isOpen.value) unread.value++;

            await scrollToBottom();
        }
    }

    async function deleteSession(session: ChatSession) {
        await fetch(`/member/chat/${session.id}`, {
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
        });
        sessions.value = sessions.value.filter(s => s.id !== session.id);

        if (activeSession.value?.id === session.id) {
            backToList();
        }
    }

    function backToList() {
        view.value          = 'list';
        activeSession.value = null;
        messages.value      = [];
        abortController?.abort();
    }

    async function open() {
        isOpen.value = true;
        unread.value = 0;
        await loadSessions();
        await newConversation();
    }

    function close() {
        isOpen.value = false;
        abortController?.abort();
    }

    async function scrollToBottom() {
        await nextTick();
        if (scrollRef.value) {
            scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
        }
    }

    return {
        sessions, activeSession, messages, input,
        isOpen, isLoading, isLoadingSessions,
        unread, view, scrollRef,
        loadSessions, openSession, newConversation,
        send, deleteSession, backToList, open, close,
    };
}
