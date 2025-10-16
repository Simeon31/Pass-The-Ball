import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/**
 * Composable for handling flash messages with auto-hide functionality
 *
 * @param flashKey - The key to watch in the flash object (e.g., 'status', 'error')
 * @param autoHideDuration - Duration in milliseconds before auto-hiding (default: 5000)
 * @returns Object containing visibility state and manual dismiss function
 */
export function useFlashMessage(
    flashKey: string = 'status',
    autoHideDuration: number = 5000,
) {
    const showMessage = ref(false);
    let messageTimeout: ReturnType<typeof setTimeout> | null = null;

    // Watching for flash messages
    watch(
        () => (usePage().props.flash as any)?.[flashKey],
        (newMessage) => {
            // Clearing any existing timeout
            if (messageTimeout) {
                clearTimeout(messageTimeout);
                messageTimeout = null;
            }

            if (newMessage) {
                showMessage.value = true;

                // Auto-hiding after specified duration
                messageTimeout = setTimeout(() => {
                    showMessage.value = false;
                    messageTimeout = null;
                }, autoHideDuration);
            } else {
                showMessage.value = false;
            }
        },
        { immediate: true },
    );

    /**
     * Manually dismiss the flash message
     */
    const dismiss = () => {
        showMessage.value = false;
        if (messageTimeout) {
            clearTimeout(messageTimeout);
            messageTimeout = null;
        }
    };

    /**
     * Get the current flash message value
     */
    const message = () => (usePage().props.flash as any)?.[flashKey];

    return {
        showMessage,
        message,
        dismiss,
    };
}
