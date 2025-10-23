<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Settings, ArrowLeft, Trash2 } from 'lucide-vue-next';
import type { Group } from '@/types';
import { router } from '@inertiajs/vue3';

interface Props {
    group: Group;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.group.name,
    about: props.group.about || '',
    auto_approval: props.group.auto_approval,
});

const submit = () => {
    console.log('Submitting form with auto_approval:', form.auto_approval);
    form.put(`/groups/${props.group.slug}`, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Group updated successfully');
        },
    });
};

const deleteGroup = () => {
    if (
        confirm(
            `Are you sure you want to delete "${props.group.name}"? This action cannot be undone.`
        )
    ) {
        router.delete(`/groups/${props.group.slug}`);
    }
};
</script>

<template>
    <AppLayout>

        <Head :title="`Edit ${group.name}`" />

        <div class="container mx-auto h-full overflow-auto px-4 py-6">
            <!-- Back Button -->
            <Link :href="`/groups/${group.slug}`"
                class="mb-6 inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to Group
            </Link>

            <div class="mx-auto max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center text-2xl">
                            <Settings class="mr-2 h-6 w-6" />
                            Group Settings
                        </CardTitle>
                        <CardDescription>
                            Update your group's information and settings
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Group Name -->
                            <div class="space-y-2">
                                <Label for="name">Group Name *</Label>
                                <Input id="name" v-model="form.name" type="text" placeholder="Enter group name" required
                                    :class="{ 'border-red-500': form.errors.name }" />
                                <p v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- About -->
                            <div class="space-y-2">
                                <Label for="about">About</Label>
                                <textarea id="about" v-model="form.about"
                                    placeholder="Describe what this group is about..." rows="4"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{ 'border-red-500': form.errors.about }"></textarea>
                                <p v-if="form.errors.about" class="text-sm text-red-600">
                                    {{ form.errors.about }}
                                </p>
                            </div>

                            <!-- Auto Approval -->
                            <div class="flex items-center justify-between rounded-lg border p-4"
                                :class="form.auto_approval ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'">
                                <div class="flex-1 space-y-0.5">
                                    <Label for="auto_approval">Auto-approve join requests</Label>
                                    <p class="text-sm" :class="form.auto_approval ? 'text-green-700' : 'text-gray-500'">
                                        {{ form.auto_approval
                                            ? 'Members can join immediately without approval'
                                            : 'Join requests require admin approval'
                                        }}
                                    </p>
                                </div>
                                <Checkbox id="auto_approval" :checked="form.auto_approval"
                                    @update:checked="(val: boolean) => (form.auto_approval = val)" />
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3">
                                <Link :href="`/groups/${group.slug}`">
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Danger Zone -->
                <Card class="border-red-200">
                    <CardHeader>
                        <CardTitle class="text-red-600">Danger Zone</CardTitle>
                        <CardDescription>
                            Irreversible actions that will permanently affect this group
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <div class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-4">
                            <div>
                                <h4 class="font-medium text-red-900">Delete Group</h4>
                                <p class="text-sm text-red-700">
                                    Once deleted, the group and all its posts will be permanently removed
                                </p>
                            </div>
                            <Button type="button" variant="destructive" @click="deleteGroup">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
