import { onMounted, onUnmounted, ref, watch, type Ref } from 'vue';

/**
 * Composable for tracking element visibility using IntersectionObserver
 * 
 * @param elementRef - Ref to the element to observe
 * @param callback - Function to call when element becomes visible
 * @param options - IntersectionObserver options
 * 
 * @example
 * const targetElement = ref<HTMLElement | null>(null);
 * const { isIntersecting } = useIntersectionObserver(
 *   targetElement,
 *   () => loadMoreContent(),
 *   { threshold: 0.1 }
 * );
 */
export function useIntersectionObserver(
    elementRef: Ref<HTMLElement | null>,
    callback: () => void,
    options: IntersectionObserverInit = {}
) {
    const isIntersecting = ref(false);
    let observer: IntersectionObserver | null = null;
    let previousIntersecting = false;

    const defaultOptions: IntersectionObserverInit = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.1, // trigger when 10% visible
        ...options,
    };

    const setupObserver = () => {
        if (!elementRef.value) return;

        // Disconnect existing observer if any
        if (observer) {
            observer.disconnect();
        }

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                isIntersecting.value = entry.isIntersecting;

                // Only trigger callback when transitioning from not intersecting to intersecting
                if (entry.isIntersecting && !previousIntersecting) {
                    callback();
                }

                previousIntersecting = entry.isIntersecting;
            });
        }, defaultOptions);

        observer.observe(elementRef.value);
    };

    // Watch for element ref changes and setup observer when element is available
    watch(elementRef, (newVal) => {
        if (newVal) {
            setupObserver();
        }
    }, { immediate: true });

    onMounted(() => {
        setupObserver();
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    });

    return {
        isIntersecting,
    };
}
