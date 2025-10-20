<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, ArrowLeft } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    about: '',
    auto_approval: true,
});

const submit = () => {
    form.post('/groups', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>

        <Head title="Create Group" />

        <div class="container mx-auto h-full overflow-auto px-4 py-6">
            <!-- Back Button -->
            <Link href="/groups" class="mb-6 inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to Groups
            </Link>

            <div class="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center text-2xl">
                            <Users class="mr-2 h-6 w-6" />
                            Create a New Group
                        </CardTitle>
                        <CardDescription>
                            Create a community space to connect with others who share your interests
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
                                <p class="text-sm text-gray-500">
                                    Help people understand what your group is about
                                </p>
                            </div>

                            <!-- Auto Approval -->
                            <div class="flex items-center justify-between rounded-lg border p-4">
                                <div class="space-y-0.5 flex-1">
                                    <Label for="auto_approval">Auto-approve join requests</Label>
                                    <p class="text-sm text-gray-500">
                                        Automatically approve new members without manual review
                                    </p>
                                </div>
                                <Checkbox id="auto_approval" :checked="form.auto_approval"
                                    @update:checked="(val: boolean) => (form.auto_approval = val)" />
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3">
                                <Link href="/groups">
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Creating...' : 'Create Group' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
