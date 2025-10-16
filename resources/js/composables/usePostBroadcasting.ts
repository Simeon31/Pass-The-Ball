import type { Comment, PostReactions } from '@/types';
import { ref } from 'vue';

export function usePostBroadcasting(postId: number) {
    const isConnected = ref(false);

    const listenForReactions = (callback: (reactions: PostReactions) => void) => {
        if (!window.Echo) {
            console.warn('Laravel Echo is not initialized');
            return;
        }

        window.Echo.channel(`posts.${postId}`)
            .listen('.post.reacted', (event: any) => {
                callback(event.reactions);
            });
        isConnected.value = true;
    };

    const listenForComments = (callback: (comment: Comment) => void) => {
        if (!window.Echo) {
            console.warn('Laravel Echo is not initialized');
            return;
        }

        window.Echo.channel(`posts.${postId}`)
            .listen('.comment.created', (event: any) => {
                callback(event.comment);
            });
    };

    const disconnect = () => {
        if (!window.Echo) return;

        window.Echo.leave(`posts.${postId}`);
        isConnected.value = false;
    };

    return {
        isConnected,
        listenForReactions,
        listenForComments,
        disconnect,
    };
}
