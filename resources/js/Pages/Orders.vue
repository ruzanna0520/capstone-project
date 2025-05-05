<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, watch, computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const expandedOrders = ref(new Set());

const orderStatuses = [
    { value: 'pending', label: 'Ожидает' },
    { value: 'processing', label: 'В обработке' },
    { value: 'completed', label: 'Завершен' },
    { value: 'shipped', label: 'Отправлен' },
    { value: 'cancelled', label: 'Отменен' },
];

const formatCurrency = (value) => {
    const numberValue = parseFloat(value);
    if (isNaN(numberValue)) {
        return 'N/A';
    }
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
    }).format(numberValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 ring-yellow-600/20';
        case 'processing':
            return 'bg-blue-100 text-blue-800 ring-blue-600/20';
        case 'completed':
            return 'bg-green-100 text-green-800 ring-green-600/20';
        case 'shipped':
            return 'bg-purple-100 text-purple-800 ring-purple-600/20';
        case 'cancelled':
            return 'bg-red-100 text-red-800 ring-red-600/20';
        default:
            return 'bg-gray-100 text-gray-800 ring-gray-500/10';
    }
};

const getStatusText = (status) => {
    const found = orderStatuses.find((s) => s.value === status);
    return found ? found.label : status;
};

const applyFilters = () => {
    const currentFilters = {};
    if (filterForm.status) currentFilters.status = filterForm.status;
    if (filterForm.date_from) currentFilters.date_from = filterForm.date_from;
    if (filterForm.date_to) currentFilters.date_to = filterForm.date_to;

    router.get(route('orders.index'), currentFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.status = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    applyFilters();
};

const toggleOrderDetails = (orderId) => {
    if (expandedOrders.value.has(orderId)) {
        expandedOrders.value.delete(orderId);
    } else {
        expandedOrders.value.add(orderId);
    }
};

const isOrderExpanded = (orderId) => {
    return expandedOrders.value.has(orderId);
};
</script>

<template>
    <Head title="Мои Заказы" />

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg bg-white p-4 shadow-sm sm:p-6">
                <form
                    @submit.prevent="applyFilters"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end"
                >
                    <div>
                        <InputLabel for="status" value="Статус заказа" />
                        <select
                            id="status"
                            v-model="filterForm.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Любой статус</option>
                            <option
                                v-for="status in orderStatuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="date_from" value="Дата с" />
                        <TextInput
                            id="date_from"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="filterForm.date_from"
                        />
                    </div>
                    <div>
                        <InputLabel for="date_to" value="Дата по" />
                        <TextInput
                            id="date_to"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="filterForm.date_to"
                        />
                    </div>
                    <div class="flex items-center space-x-2">
                        <PrimaryButton type="submit">Применить</PrimaryButton>
                        <SecondaryButton type="button" @click="resetFilters"
                            >Сбросить</SecondaryButton
                        >
                    </div>
                </form>
            </div>

            <div v-if="orders.data.length > 0" class="space-y-4">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div
                        class="cursor-pointer p-4 hover:bg-gray-50 sm:p-6"
                        @click="toggleOrderDetails(order.id)"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-semibold leading-6 text-gray-900"
                                >
                                    Заказ #{{ order.id }}
                                </p>
                                <p
                                    class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500"
                                >
                                    <time :datetime="order.created_at">{{
                                        formatDate(order.created_at)
                                    }}</time>
                                </p>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                                <span
                                    class="hidden text-sm font-medium leading-6 text-gray-900 sm:inline-block"
                                    >{{
                                        formatCurrency(order.total_amount)
                                    }}</span
                                >
                                <span
                                    :class="[
                                        'rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                        getStatusClass(order.status),
                                    ]"
                                >
                                    {{ getStatusText(order.status) }}
                                </span>
                                <svg
                                    class="h-5 w-5 flex-none text-gray-400 transition-transform"
                                    :class="{
                                        'rotate-180': isOrderExpanded(order.id),
                                    }"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="isOrderExpanded(order.id)"
                            class="border-t border-gray-200 px-4 py-5 sm:p-6"
                        >
                            <div class="mb-4 sm:hidden">
                                <p
                                    class="text-sm font-medium leading-6 text-gray-900"
                                >
                                    Сумма:
                                    {{ formatCurrency(order.total_amount) }}
                                </p>
                            </div>
                            <h4 class="text-sm font-medium text-gray-600">
                                Товары в заказе:
                            </h4>
                            <ul
                                v-if="
                                    order.products && order.products.length > 0
                                "
                                class="mt-2 space-y-2 text-sm text-gray-700"
                            >
                                <li
                                    v-for="product in order.products"
                                    :key="product.id"
                                    class="flex items-center justify-between rounded-md bg-gray-50 p-2"
                                >
                                    <span>{{ product.name }}</span>
                                    <span
                                        class="whitespace-nowrap text-gray-600"
                                        >{{ product.pivot.quantity }} шт. x
                                        {{
                                            formatCurrency(
                                                product.pivot
                                                    .price_at_time_of_order,
                                            )
                                        }}</span
                                    >
                                </li>
                            </ul>
                            <p v-else class="mt-2 text-sm text-gray-500">
                                Нет информации о товарах.
                            </p>
                        </div>
                    </transition>
                </div>

                <div
                    v-if="orders.links.length > 3"
                    class="mt-6 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 shadow-sm sm:rounded-lg sm:px-6"
                >
                    <div class="flex flex-1 justify-between sm:hidden">
                        <Link
                            :href="orders.prev_page_url"
                            v-if="orders.prev_page_url"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            preserve-scroll
                            preserve-state
                            >Назад</Link
                        >
                        <span
                            v-else
                            class="relative inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-400"
                            >Назад</span
                        >
                        <Link
                            :href="orders.next_page_url"
                            v-if="orders.next_page_url"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            preserve-scroll
                            preserve-state
                            >Вперед</Link
                        >
                        <span
                            v-else
                            class="relative ml-3 inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-400"
                            >Вперед</span
                        >
                    </div>
                    <div
                        class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Показано с
                                <span class="font-medium">{{
                                    orders.from
                                }}</span>
                                по
                                <span class="font-medium">{{ orders.to }}</span>
                                из
                                <span class="font-medium">{{
                                    orders.total
                                }}</span>
                                результатов
                            </p>
                        </div>
                        <div>
                            <nav
                                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                aria-label="Pagination"
                            >
                                <template
                                    v-for="(link, key) in orders.links"
                                    :key="key"
                                >
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                                        :class="{
                                            'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600':
                                                link.active,
                                            'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0':
                                                !link.active,
                                            'rounded-l-md': key === 0,
                                            'rounded-r-md':
                                                key === orders.links.length - 1,
                                        }"
                                        preserve-scroll
                                        preserve-state
                                    />
                                    <span
                                        v-else
                                        v-html="link.label"
                                        class="relative inline-flex cursor-not-allowed items-center px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300"
                                        :class="{
                                            'rounded-l-md': key === 0,
                                            'rounded-r-md':
                                                key === orders.links.length - 1,
                                        }"
                                    ></span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-else
                class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm"
            >
                <p
                    v-if="
                        filters.status || filters.date_from || filters.date_to
                    "
                >
                    Не найдено заказов, соответствующих вашим фильтрам.
                </p>
                <p v-else>У вас пока нет заказов.</p>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
