<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChevronDown, Shield, UserCog, User } from 'lucide-vue-next';
import type { GroupRole } from '@/types';

interface Props {
    currentRole: GroupRole;
    memberId: number;
    groupSlug: string;
    disabled?: boolean;
    isOwner?: boolean;
}

const props = defineProps<Props>();

const roleInfo = computed(() => {
    const roles: Record<GroupRole, { label: string; icon: any; variant: 'default' | 'secondary' | 'outline' }> = {
        admin: { label: 'Administrator', icon: Shield, variant: 'default' },
        moderator: { label: 'Moderator', icon: UserCog, variant: 'secondary' },
        member: { label: 'Member', icon: User, variant: 'outline' },
    };
    return roles[props.currentRole];
});

const availableRoles: Array<{ value: GroupRole; label: string; icon: any }> = [
    { value: 'admin', label: 'Administrator', icon: Shield },
    { value: 'moderator', label: 'Moderator', icon: UserCog },
    { value: 'member', label: 'Member', icon: User },
];

const handleRoleChange = (newRole: GroupRole) => {
    if (newRole === props.currentRole) return;

    router.post(
        `/groups/${props.groupSlug}/members/${props.memberId}/role`,
        { role: newRole },
        {
            preserveScroll: true,
            onError: (errors) => {
                console.error('Failed to update role:', errors);
            },
        }
    );
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button :variant="roleInfo.variant" size="sm" :disabled="disabled || isOwner">
                <component :is="roleInfo.icon" class="mr-2 h-3 w-3" />
                {{ roleInfo.label }}
                <ChevronDown v-if="!disabled && !isOwner" class="ml-2 h-3 w-3" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Change Role</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem v-for="role in availableRoles" :key="role.value" @click="handleRoleChange(role.value)"
                :class="{ 'bg-accent': role.value === currentRole }">
                <component :is="role.icon" class="mr-2 h-4 w-4" />
                {{ role.label }}
                <Badge v-if="role.value === currentRole" variant="outline" class="ml-auto text-xs">
                    Current
                </Badge>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
