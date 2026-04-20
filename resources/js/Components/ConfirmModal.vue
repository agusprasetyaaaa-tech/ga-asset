<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi' },
    message: { type: String, default: '' },
    type: { type: String, default: 'danger' },
    confirmText: { type: String, default: 'Ya, Hapus' }
});

const emit = defineEmits(['close', 'confirm']);

const isOpen = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const modalType = ref('danger');
const modalConfirmText = ref('Ya, Hapus');
const modalAction = ref(null);

// Sync with props for backward compatibility
watch(() => props.show, (val) => { isOpen.value = val; }, { immediate: true });
watch(() => props.title, (val) => { modalTitle.value = val; }, { immediate: true });
watch(() => props.message, (val) => { modalMessage.value = val; }, { immediate: true });
watch(() => props.type, (val) => { modalType.value = val; }, { immediate: true });
watch(() => props.confirmText, (val) => { modalConfirmText.value = val; }, { immediate: true });

const open = (options) => {
    modalTitle.value = options.title || 'Konfirmasi';
    modalMessage.value = options.message || '';
    modalType.value = options.type || 'danger';
    modalConfirmText.value = options.confirmText || 'Ya, Hapus';
    modalAction.value = options.onConfirm || null;
    isOpen.value = true;
};

const close = () => {
    isOpen.value = false;
    emit('close');
};

const handleConfirm = () => {
    if (modalAction.value) {
        modalAction.value();
    }
    emit('confirm');
    close();
};

defineExpose({ open, close });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all border border-gray-100">
                    <div class="p-6 text-center">
                        <!-- Icon Section -->
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 shadow-sm"
                             :class="{
                                 'bg-red-50': modalType === 'danger',
                                 'bg-amber-50': modalType === 'warning',
                                 'bg-emerald-50': modalType === 'success'
                             }">
                            <!-- Danger Icon -->
                            <svg v-if="modalType === 'danger'" class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <!-- Warning Icon -->
                            <svg v-if="modalType === 'warning'" class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 17c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <!-- Success Icon -->
                            <svg v-if="modalType === 'success'" class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 mb-2 leading-tight px-4">{{ modalTitle }}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed px-6">
                            {{ modalMessage }}
                        </p>
                    </div>

                    <div class="bg-gray-50/80 flex gap-3 p-5 px-6 mt-2 border-t border-gray-100">
                        <button @click="close"
                            class="flex-1 px-4 py-2.5 bg-white border border-gray-200 text-[11px] font-bold text-gray-700 rounded-lg hover:bg-gray-100 transition shadow-sm">
                            Batal
                        </button>
                        <button @click="handleConfirm"
                            class="flex-1 px-4 py-2.5 text-[11px] font-bold text-white rounded-lg shadow-sm transition-all active:scale-95"
                            :class="{
                                'bg-red-600 hover:bg-red-700 shadow-red-200': modalType === 'danger',
                                'bg-amber-600 hover:bg-amber-700 shadow-amber-200': modalType === 'warning',
                                'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200': modalType === 'success',
                            }">
                            {{ modalConfirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
