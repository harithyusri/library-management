<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { ScanLine, X, Camera, CameraOff, Loader2 } from 'lucide-vue-next';

interface BookCopy {
    id: number;
    barcode: string;
    call_number?: string;
    status: string;
    condition: string;
    location?: string;
    book: { id: number; title: string; author_name: string; isbn?: string };
}

const emit = defineEmits<{
    scanned: [copy: BookCopy];
}>();

const isOpen      = ref(false);
const isStarting  = ref(false);
const error       = ref('');
const videoRef    = ref<HTMLVideoElement | null>(null);
const stream      = ref<MediaStream | null>(null);
let   detector: any = null;
let   scanInterval: ReturnType<typeof setInterval> | null = null;

async function open() {
    isOpen.value    = true;
    error.value     = '';
    isStarting.value = true;

    // Check BarcodeDetector support
    if (! ('BarcodeDetector' in window)) {
        error.value = 'Your browser does not support the Barcode Scanner API. Please use Chrome or Edge.';
        isStarting.value = false;
        return;
    }

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
        });

        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
            await videoRef.value.play();
        }

        // @ts-ignore
        detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e'] });

        scanInterval = setInterval(scanFrame, 400);
    } catch (e: any) {
        error.value = e.name === 'NotAllowedError'
            ? 'Camera access denied. Please allow camera permission and try again.'
            : 'Could not access camera. Please check your device.';
    } finally {
        isStarting.value = false;
    }
}

async function scanFrame() {
    if (! videoRef.value || ! detector) return;
    if (videoRef.value.readyState < 2) return;

    try {
        const barcodes = await detector.detect(videoRef.value);
        if (barcodes.length === 0) return;

        const raw = barcodes[0].rawValue;
        stopCamera();

        // Try to parse as JSON (QR code from our system)
        let barcode = raw;
        try {
            const parsed = JSON.parse(raw);
            barcode = parsed.barcode ?? raw;
        } catch {
            // plain barcode string — use as-is
        }

        await lookupBarcode(barcode);
    } catch {
        // detection error — keep scanning
    }
}

async function lookupBarcode(barcode: string) {
    isStarting.value = true;
    error.value      = '';

    try {
        const res  = await fetch(`/admin/scan/${encodeURIComponent(barcode)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (! res.ok) {
            error.value = data.error ?? 'Book copy not found.';
            return;
        }

        if (data.status !== 'available') {
            error.value = `This copy is currently "${data.status}" and cannot be loaned.`;
            return;
        }

        emit('scanned', data as BookCopy);
        close();
    } catch {
        error.value = 'Failed to look up barcode. Please try again.';
    } finally {
        isStarting.value = false;
    }
}

function stopCamera() {
    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
    stream.value?.getTracks().forEach(t => t.stop());
    stream.value = null;
}

function close() {
    stopCamera();
    isOpen.value     = false;
    error.value      = '';
    isStarting.value = false;
}

onUnmounted(stopCamera);
</script>

<template>
    <!-- Trigger button -->
    <button
        type="button"
        @click="open"
        title="Scan barcode"
        class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg border border-input bg-background hover:bg-accent transition-colors"
    >
        <ScanLine class="h-4 w-4 text-muted-foreground" />
    </button>

    <!-- Modal overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                <div class="w-full max-w-sm bg-white rounded-2xl overflow-hidden shadow-2xl">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 bg-[#0d1a14]">
                        <div class="flex items-center gap-2">
                            <ScanLine class="h-5 w-5 text-[#c5a059]" />
                            <span class="text-white font-bold text-sm">Scan Book Barcode</span>
                        </div>
                        <button @click="close" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Camera view -->
                    <div class="relative bg-black aspect-[4/3] w-full">
                        <video
                            ref="videoRef"
                            class="w-full h-full object-cover"
                            muted
                            playsinline
                        />

                        <!-- Scan overlay -->
                        <div v-if="!error && !isStarting" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="relative w-52 h-52">
                                <!-- Corner brackets -->
                                <span class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-[#c5a059] rounded-tl-lg" />
                                <span class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-[#c5a059] rounded-tr-lg" />
                                <span class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-[#c5a059] rounded-bl-lg" />
                                <span class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-[#c5a059] rounded-br-lg" />
                                <!-- Scan line animation -->
                                <span class="absolute left-2 right-2 h-0.5 bg-[#c5a059]/80 animate-[scan_2s_ease-in-out_infinite]" style="top: 50%;" />
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div v-if="isStarting" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/60">
                            <Loader2 class="h-8 w-8 text-[#c5a059] animate-spin" />
                            <p class="text-white text-sm font-medium">Starting camera...</p>
                        </div>

                        <!-- Error state -->
                        <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 px-6 text-center">
                            <CameraOff class="h-10 w-10 text-red-400" />
                            <p class="text-white text-sm leading-relaxed">{{ error }}</p>
                            <button
                                type="button"
                                @click="error = ''; open()"
                                class="mt-1 px-4 py-2 bg-[#c5a059] text-[#0d1a14] text-sm font-bold rounded-lg hover:bg-[#b8924a] transition-colors"
                            >
                                Try Again
                            </button>
                        </div>
                    </div>

                    <!-- Footer hint -->
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                        <p class="text-xs text-slate-500 text-center flex items-center justify-center gap-1.5">
                            <Camera class="h-3.5 w-3.5" />
                            Point camera at the book's QR code or barcode
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
@keyframes scan {
    0%, 100% { transform: translateY(-80px); opacity: 0.4; }
    50%       { transform: translateY(80px);  opacity: 1; }
}
</style>
