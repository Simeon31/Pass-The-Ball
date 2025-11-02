import type { PostReactions } from '@/types';
import { ref } from 'vue';

export function useCommentBroadcasting(commentId: number) {
    const isConnected = ref(false);

    const listenForReactions = (callback: (reactions: PostReactions) => void) => {
        if (!window.Echo) {
            console.warn('Laravel Echo is not initialized');
            return;
        }

        window.Echo.channel(`comments.${commentId}`)
            .listen('.comment.reacted', (event: any) => {
                callback(event.reactions);
            });
        isConnected.value = true;
    };

    const disconnect = () => {
        if (!window.Echo) return;

        window.Echo.leave(`comments.${commentId}`);
        isConnected.value = false;
    };

    return {
        isConnected,
        listenForReactions,
        disconnect,
    };
}
