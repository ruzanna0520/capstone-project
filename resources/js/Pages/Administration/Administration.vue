<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, ref } from 'vue';
import Categories from '@/Pages/Administration/Categories.vue';
import Products from '@/Pages/Administration/Products.vue';
import Orders from '@/Pages/Administration/Orders.vue';

const tabs = [
    {
        id: 1,
        title: 'Категории',
        content: Categories,
    },
    {
        id: 2,
        title: 'Товары',
        content: Products,
    },
    {
        id: 3,
        title: 'Заказы',
        content: Orders,
    },
];

const currentTab = ref(1);

const selectedComponent = computed(
    () => tabs.find((tab) => tab.id === currentTab.value)?.content,
);
</script>

<template>
    <Head title="Администрирование" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-row">
                        <nav class="w-[20%] border-r-2 p-6">
                            <ul class="menu rounded-box bg-base-100 w-full">
                                <li
                                    class="tab__btn"
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                >
                                    <button
                                        @click="currentTab = tab.id"
                                        :class="{
                                            'bg-primary text-white':
                                                currentTab === tab.id,
                                        }"
                                    >
                                        {{ tab.title }}
                                    </button>
                                </li>
                            </ul>
                        </nav>
                        <div class="h-full w-4/5 p-6 text-gray-900">
                            <component :is="selectedComponent" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.tab__btn:not(:last-child) {
    margin-bottom: 0.5rem;
}
</style>
