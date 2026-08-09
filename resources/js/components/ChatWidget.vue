<script setup lang="ts">
import { computed } from 'vue';
import { MessageCircle, X, Trash2, Send, Bot, Loader2, ArrowLeft, Plus, MessagesSquare, History } from 'lucide-vue-next';
import { useChat } from '@/composables/useChat';

const {
    sessions, activeSession, messages, input,
    isOpen, isLoading, isLoadingSessions,
    unread, view, scrollRef,
    openSession, newConversation,
    send, deleteSession, backToList, open, close,
} = useChat();

function handleKey(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

const isConversationEmpty = computed(() => messages.value.length === 0);
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

        <!-- Chat Panel -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div
                v-if="isOpen"
                class="w-[360px] sm:w-[400px] h-[580px] bg-white rounded-2xl shadow-2xl shadow-black/20 border border-slate-200 flex flex-col overflow-hidden origin-bottom-right"
            >
                <!-- ── CONVERSATION LIST VIEW ── -->
                <template v-if="view === 'list'">

                    <!-- Header -->
                    <div class="bg-[#0d1a14] px-5 py-4 shrink-0">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="activeSession"
                                    @click="view = 'conversation'"
                                    title="Back to conversation"
                                    class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-colors shrink-0"
                                >
                                    <ArrowLeft class="h-4 w-4" />
                                </button>
                                <div class="h-9 w-9 rounded-xl bg-[#c5a059]/20 flex items-center justify-center">
                                    <Bot class="h-5 w-5 text-[#c5a059]" />
                                </div>
                                <div>
                                    <p class="text-white font-bold text-sm leading-tight">Chat History</p>
                                    <p class="text-slate-400 text-[11px] font-medium">All conversations</p>
                                </div>
                            </div>
                            <button
                                @click="close"
                                class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <!-- New conversation button -->
                        <button
                            @click="newConversation"
                            class="w-full flex items-center justify-center gap-2 bg-[#c5a059] hover:bg-[#b8924a] text-[#0d1a14] font-bold text-sm rounded-xl py-2.5 transition-colors active:scale-95"
                        >
                            <Plus class="h-4 w-4" />
                            New Conversation
                        </button>
                    </div>

                    <!-- Session list -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- Loading -->
                        <div v-if="isLoadingSessions" class="flex items-center justify-center h-full">
                            <Loader2 class="h-5 w-5 animate-spin text-slate-300" />
                        </div>

                        <!-- Empty state -->
                        <div v-else-if="sessions.length === 0" class="flex flex-col items-center justify-center h-full gap-3 px-6 text-center">
                            <div class="h-14 w-14 rounded-2xl bg-[#0d1a14]/5 flex items-center justify-center">
                                <MessagesSquare class="h-7 w-7 text-[#0d1a14]/30" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-sm">No conversations yet</p>
                                <p class="text-slate-400 text-xs mt-1">Start a new conversation with Athena.</p>
                            </div>
                        </div>

                        <!-- Sessions -->
                        <div v-else class="divide-y divide-slate-100">
                            <div
                                v-for="session in sessions"
                                :key="session.id"
                                class="group flex items-start gap-3 px-4 py-3.5 hover:bg-slate-50 cursor-pointer transition-colors"
                                @click="openSession(session)"
                            >
                                <div class="h-9 w-9 rounded-xl bg-[#0d1a14]/8 flex items-center justify-center shrink-0 mt-0.5">
                                    <Bot class="h-4 w-4 text-[#0d1a14]/50" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 truncate">{{ session.title }}</p>
                                    <p v-if="session.last_message" class="text-xs text-slate-400 truncate mt-0.5">
                                        {{ session.last_message }}
                                    </p>
                                    <p v-if="session.last_at" class="text-[10px] text-slate-300 mt-1 font-medium">{{ session.last_at }}</p>
                                </div>
                                <button
                                    @click.stop="deleteSession(session)"
                                    class="h-7 w-7 rounded-lg flex items-center justify-center text-slate-300 hover:text-red-400 hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100 shrink-0"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-2.5 border-t border-slate-100 shrink-0">
                        <p class="text-[10px] text-slate-400 text-center">Athena · Powered by Gemini</p>
                    </div>
                </template>

                <!-- ── CONVERSATION VIEW ── -->
                <template v-else>

                    <!-- Header -->
                    <div class="bg-[#0d1a14] px-4 py-3.5 flex items-center gap-3 shrink-0">
                        <button
                            @click="backToList"
                            title="Chat history"
                            class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-[#c5a059] hover:bg-white/10 transition-colors shrink-0"
                        >
                            <History class="h-4 w-4" />
                        </button>
                        <div class="h-8 w-8 rounded-lg bg-[#c5a059]/20 flex items-center justify-center shrink-0">
                            <Bot class="h-4 w-4 text-[#c5a059]" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-bold text-sm truncate leading-tight">
                                {{ activeSession?.title ?? 'New Conversation' }}
                            </p>
                            <p class="text-slate-400 text-[10px]">Athena · Library AI</p>
                        </div>
                        <button
                            @click="close"
                            class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-colors shrink-0"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Messages -->
                    <div ref="scrollRef" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 scroll-smooth">

                        <!-- Empty state -->
                        <div v-if="isConversationEmpty" class="h-full flex flex-col items-center justify-center gap-3 text-center px-6">
                            <div class="h-14 w-14 rounded-2xl bg-[#0d1a14]/5 flex items-center justify-center">
                                <Bot class="h-7 w-7 text-[#0d1a14]/40" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-sm">Hi, I'm Athena!</p>
                                <p class="text-slate-400 text-xs mt-1 leading-relaxed">
                                    Ask me about your loans, fines, room bookings, or anything about the library.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2 justify-center mt-2">
                                <button
                                    v-for="chip in ['What are my active loans?', 'Do I have any fines?', 'My upcoming bookings']"
                                    :key="chip"
                                    @click="input = chip; send()"
                                    class="text-[11px] font-semibold px-3 py-1.5 rounded-full border border-[#c5a059]/40 text-[#c5a059] hover:bg-[#c5a059]/10 transition-colors"
                                >
                                    {{ chip }}
                                </button>
                            </div>
                        </div>

                        <!-- Message bubbles -->
                        <template v-else>
                            <div
                                v-for="(msg, i) in messages"
                                :key="i"
                                class="flex gap-2.5"
                                :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    v-if="msg.role === 'assistant'"
                                    class="h-7 w-7 rounded-lg bg-[#0d1a14] flex items-center justify-center shrink-0 mt-0.5"
                                >
                                    <Bot class="h-3.5 w-3.5 text-[#c5a059]" />
                                </div>
                                <div
                                    class="max-w-[78%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                    :class="msg.role === 'user'
                                        ? 'bg-[#0d1a14] text-[#f1f5f9] rounded-br-sm'
                                        : 'bg-slate-100 text-slate-800 rounded-bl-sm'"
                                >
                                    <span class="whitespace-pre-wrap">{{ msg.content }}</span>
                                    <span
                                        v-if="msg.streaming"
                                        class="inline-block w-1.5 h-3.5 bg-[#c5a059] rounded-sm ml-0.5 animate-pulse align-middle"
                                    />
                                </div>
                            </div>

                            <!-- Thinking dots -->
                            <div v-if="isLoading && messages[messages.length - 1]?.content === ''" class="flex gap-2.5 justify-start">
                                <div class="h-7 w-7 rounded-lg bg-[#0d1a14] flex items-center justify-center shrink-0">
                                    <Bot class="h-3.5 w-3.5 text-[#c5a059]" />
                                </div>
                                <div class="bg-slate-100 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0ms]" />
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:150ms]" />
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:300ms]" />
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Input -->
                    <div class="px-4 py-3 border-t border-slate-100 shrink-0">
                        <div class="flex items-end gap-2 bg-slate-50 rounded-xl border border-slate-200 px-3 py-2 focus-within:border-[#c5a059]/60 focus-within:ring-2 focus-within:ring-[#c5a059]/10 transition-all">
                            <textarea
                                v-model="input"
                                @keydown="handleKey"
                                placeholder="Ask Athena anything..."
                                rows="1"
                                :disabled="isLoading"
                                class="flex-1 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 resize-none outline-none max-h-28 leading-relaxed disabled:opacity-50"
                                style="field-sizing: content;"
                            />
                            <button
                                @click="send"
                                :disabled="!input.trim() || isLoading"
                                class="h-8 w-8 rounded-lg bg-[#0d1a14] flex items-center justify-center text-white shrink-0 transition-all hover:bg-[#122010] disabled:opacity-40 disabled:cursor-not-allowed active:scale-95"
                            >
                                <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 text-center mt-2">Athena · Powered by Gemini</p>
                    </div>
                </template>
            </div>
        </Transition>

        <!-- FAB Button -->
        <button
            @click="isOpen ? close() : open()"
            class="relative h-14 w-14 rounded-2xl bg-[#0d1a14] hover:bg-[#122010] shadow-xl shadow-black/30 flex items-center justify-center transition-all duration-200 hover:scale-105 active:scale-95"
        >
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0 rotate-90 scale-50"
                enter-to-class="opacity-100 rotate-0 scale-100"
                leave-active-class="transition duration-200"
                leave-from-class="opacity-100 rotate-0 scale-100"
                leave-to-class="opacity-0 rotate-90 scale-50"
            >
                <X v-if="isOpen" class="h-6 w-6 text-white absolute" />
                <MessageCircle v-else class="h-6 w-6 text-[#c5a059] absolute" />
            </Transition>

            <!-- Unread badge -->
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0 scale-50"
                enter-to-class="opacity-100 scale-100"
            >
                <span
                    v-if="!isOpen && unread > 0"
                    class="absolute -top-1.5 -right-1.5 h-5 w-5 rounded-full bg-[#c5a059] text-[#0d1a14] text-[10px] font-black flex items-center justify-center shadow-md"
                >
                    {{ unread > 9 ? '9+' : unread }}
                </span>
            </Transition>
        </button>
    </div>
</template>
