<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { UserPlus, UserMinus, Loader2 } from 'lucide-vue-next';

interface Props {
    userId: number;
    isFollowing: boolean;
    size?: 'default' | 'sm' | 'lg' | 'icon';
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    showIcon?: boolean;
    showText?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'default',
    variant: 'default',
    showIcon: true,
    showText: true,
});

const isLoading = ref(false);
const localIsFollowing = ref(props.isFollowing);

const toggleFollow = () => {
    if (isLoading.value) return;

    isLoading.value = true;

    router.post(`/users/${props.userId}/follow`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Toggle local state
            localIsFollowing.value = !localIsFollowing.value;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};
</script>

<template>
    <Button :size="size" :variant="localIsFollowing ? 'outline' : variant" @click.prevent="toggleFollow"
        :disabled="isLoading" class="gap-2">
        <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
        <template v-else>
            <UserMinus v-if="showIcon && localIsFollowing" class="h-4 w-4" />
            <UserPlus v-else-if="showIcon" class="h-4 w-4" />
            <span v-if="showText">
                {{ localIsFollowing ? 'Unfollow' : 'Follow' }}
            </span>
        </template>
    </Button>
</template>
