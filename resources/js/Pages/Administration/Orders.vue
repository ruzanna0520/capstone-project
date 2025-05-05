<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; // Импортируем SelectInput

const ordersData = ref({ data: [], links: [] });
const customers = ref([]);
const modalProductsData = ref({ data: [], links: [] });
const categories = ref([]); // Добавляем состояние для категорий
const isLoadingOrders = ref(false);
const listError = ref(null);

const showDetailModal = ref(false);
const selectedOrder = ref(null);
const isModalLoading = ref(false);
const modalError = ref(null);
const isUpdatingStatus = ref(false);
const isDeletingOrder = ref(false);

const showAddOrderModal = ref(false);
const newOrderData = ref({ user_id: null, products: {} });
const addOrderError = ref(null);
const isAddingOrder = ref(false);
const isLoadingModalProducts = ref(false);

// Состояние для фильтров в модалке
const modalProductFilters = ref({
    search: '',
    category_id: '',
});

const orderStatusMap = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'Отправлен',
    completed: 'Завершен',
    cancelled: 'Отменен',
};

const availableStatuses = Object.keys(orderStatusMap);
const availableStatusesForSelect = computed(() => {
    return availableStatuses.map((status) => ({
        value: status,
        label: orderStatusMap[status],
    }));
});

const modalCategoryOptions = computed(() => {
    // Добавляем опцию "Все категории"
    return [
        { value: '', label: 'Все категории' },
        ...categories.value.map((cat) => ({ value: cat.id, label: cat.name })),
    ];
});

const API_ORDERS_URL = '/internal-api/orders';
const API_CUSTOMERS_URL = '/internal-api/customers';
const API_PRODUCTS_URL = '/internal-api/products';
const API_CATEGORIES_URL = '/internal-api/categories';

const newOrderTotal = computed(() => {
    let total = 0;
    for (const productId in newOrderData.value.products) {
        total +=
            (newOrderData.value.products[productId]?.price || 0) *
            (newOrderData.value.products[productId]?.quantity || 0);
    }
    return total;
});

const sortedCustomers = computed(() =>
    [...customers.value].sort((a, b) => a.name.localeCompare(b.name)),
);

const getImageUrl = (path) => (path ? `/storage/${path}` : null);

const formatCurrency = (value) => {
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return 'N/A';
    return numValue.toFixed(2) + ' ₽';
};

const getStatusText = (statusKey) => {
    return orderStatusMap[statusKey] || statusKey;
};

const getStatusClass = (statusKey) => {
    switch (statusKey) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'shipped':
            return 'bg-purple-100 text-purple-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const fetchOrders = async (url = API_ORDERS_URL) => {
    isLoadingOrders.value = true;
    listError.value = null;
    try {
        const response = await axios.get(url);
        ordersData.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки списка заказов:', err);
        listError.value =
            err.response?.data?.message ||
            'Не удалось загрузить список заказов.';
        ordersData.value = { data: [], links: [] };
    } finally {
        isLoadingOrders.value = false;
    }
};

const fetchCustomers = async () => {
    try {
        const response = await axios.get(API_CUSTOMERS_URL);
        customers.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки покупателей:', err);
        listError.value =
            listError.value || 'Не удалось загрузить покупателей.';
    }
};

const fetchCategoriesForModal = async () => {
    try {
        const response = await axios.get(API_CATEGORIES_URL);
        categories.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки категорий:', err);
        addOrderError.value =
            addOrderError.value ||
            'Не удалось загрузить категории для фильтрации.';
    }
};

const fetchModalProducts = async (url = API_PRODUCTS_URL) => {
    isLoadingModalProducts.value = true;
    const params = { per_page: 5 }; // Маленькая пагинация для модалки
    if (modalProductFilters.value.search) {
        params.search = modalProductFilters.value.search;
    }
    if (modalProductFilters.value.category_id) {
        params.category = modalProductFilters.value.category_id;
    }

    // Если URL передан (из пагинации), извлекаем параметры из него
    let targetUrl = url;
    if (url !== API_PRODUCTS_URL) {
        try {
            const urlObj = new URL(url);
            urlObj.searchParams.forEach((value, key) => {
                if (key !== 'page') {
                    // Добавляем фильтры к параметрам запроса, если их нет в URL
                    if (!urlObj.searchParams.has('search') && params.search)
                        urlObj.searchParams.set('search', params.search);
                    if (!urlObj.searchParams.has('category') && params.category)
                        urlObj.searchParams.set('category', params.category);
                }
            });
            targetUrl = urlObj.toString();
        } catch (e) {
            console.error('Invalid URL for pagination:', url);
        }
    }

    try {
        const response = await axios.get(targetUrl, {
            params: url === API_PRODUCTS_URL ? params : {},
        }); // Передаем параметры только если это не URL пагинации
        modalProductsData.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки товаров для модалки:', err);
        addOrderError.value = 'Не удалось загрузить товары для выбора.';
        modalProductsData.value = { data: [], links: [] };
    } finally {
        isLoadingModalProducts.value = false;
    }
};

watch(
    modalProductFilters,
    () => {
        // Применяем фильтры при их изменении (сбрасываем на 1 страницу)
        fetchModalProducts(API_PRODUCTS_URL);
    },
    { deep: true },
);

const openDetailModal = async (orderStub) => {
    if (!orderStub || !orderStub.id) return;
    showDetailModal.value = true;
    isModalLoading.value = true;
    modalError.value = null;
    selectedOrder.value = null;
    try {
        const response = await axios.get(`${API_ORDERS_URL}/${orderStub.id}`);
        selectedOrder.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки деталей заказа:', err);
        modalError.value =
            err.response?.data?.message ||
            'Не удалось загрузить детали заказа.';
    } finally {
        isModalLoading.value = false;
    }
};

const updateOrderStatus = async () => {
    if (!selectedOrder.value?.id || !selectedOrder.value?.status) return;
    isUpdatingStatus.value = true;
    modalError.value = null;
    const originalStatus = ordersData.value.data.find(
        (o) => o.id === selectedOrder.value.id,
    )?.status;
    try {
        await axios.put(`${API_ORDERS_URL}/${selectedOrder.value.id}`, {
            status: selectedOrder.value.status,
        });
        const index = ordersData.value.data.findIndex(
            (o) => o.id === selectedOrder.value.id,
        );
        if (index !== -1) {
            ordersData.value.data[index].status = selectedOrder.value.status;
        }
    } catch (err) {
        console.error('Ошибка обновления статуса заказа:', err);
        modalError.value =
            err.response?.data?.message || 'Не удалось обновить статус.';
        if (originalStatus) selectedOrder.value.status = originalStatus;
    } finally {
        isUpdatingStatus.value = false;
    }
};

const deleteOrder = async () => {
    const orderToDelete = selectedOrder.value;
    if (!orderToDelete?.id) return;
    if (!confirm(`Вы уверены, что хотите удалить заказ #${orderToDelete.id}?`))
        return;
    isDeletingOrder.value = true;
    modalError.value = null;
    try {
        await axios.delete(`${API_ORDERS_URL}/${orderToDelete.id}`);
        closeDetailModal();
        await fetchOrders(
            ordersData.value.links?.find((l) => l.active)?.url ||
                API_ORDERS_URL,
        );
    } catch (err) {
        console.error('Ошибка удаления заказа:', err);
        listError.value =
            err.response?.data?.message || 'Не удалось удалить заказ.';
    } finally {
        isDeletingOrder.value = false;
    }
};

const addOrder = async () => {
    if (!newOrderData.value.user_id) {
        addOrderError.value = 'Необходимо выбрать покупателя.';
        return;
    }
    const productsToSend = Object.entries(newOrderData.value.products).map(
        ([id, data]) => ({ id: parseInt(id), quantity: data.quantity }),
    );
    if (productsToSend.length === 0) {
        addOrderError.value = 'Необходимо добавить хотя бы один товар в заказ.';
        return;
    }
    isAddingOrder.value = true;
    addOrderError.value = null;
    try {
        await axios.post(API_ORDERS_URL, {
            user_id: newOrderData.value.user_id,
            products: productsToSend,
        });
        closeAddOrderModal();
        await fetchOrders();
    } catch (err) {
        console.error('Ошибка добавления заказа:', err);
        addOrderError.value =
            err.response?.data?.message || 'Не удалось добавить заказ.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            if (firstErrorKey.startsWith('products.')) {
                addOrderError.value = `Ошибка в товарах: ${err.response.data.errors[firstErrorKey][0]}`;
            } else {
                addOrderError.value =
                    err.response.data.errors[firstErrorKey][0];
            }
        }
    } finally {
        isAddingOrder.value = false;
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedOrder.value = null;
    modalError.value = null;
};

const openAddOrderModal = async () => {
    newOrderData.value = { user_id: null, products: {} };
    addOrderError.value = null;
    modalProductFilters.value = { search: '', category_id: '' }; // Сброс фильтров модалки
    showAddOrderModal.value = true;
    if (categories.value.length === 0) {
        await fetchCategoriesForModal(); // Загружаем категории, если их нет
    }
    await fetchModalProducts();
};

const closeAddOrderModal = () => {
    showAddOrderModal.value = false;
    modalProductsData.value = { data: [], links: [] };
};

const toggleProductSelection = (product) => {
    const productId = product.id;
    if (newOrderData.value.products[productId]) {
        delete newOrderData.value.products[productId];
    } else {
        newOrderData.value.products[productId] = {
            quantity: 1,
            price: product.price,
            name: product.name,
        };
    }
};

const updateNewOrderQuantity = (productId, event) => {
    let quantity = parseInt(event.target.value) || 1;
    if (quantity < 1) quantity = 1;
    event.target.value = quantity;
    if (newOrderData.value.products[productId]) {
        newOrderData.value.products[productId].quantity = quantity;
    }
};

onMounted(async () => {
    await fetchCustomers();
    await fetchOrders();
    await fetchCategoriesForModal(); // Предзагружаем категории
});
</script>

<template>
    <div class="container mx-auto p-6">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">
            Управление заказами
        </h1>

        <div class="mb-4">
            <button
                @click="openAddOrderModal"
                :disabled="customers.length === 0"
                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition duration-150 ease-in-out hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Добавить заказ вручную
            </button>
            <p v-if="customers.length === 0" class="mt-1 text-xs text-red-600">
                * Для добавления заказа нужны покупатели в базе.
            </p>
        </div>

        <div v-if="isLoadingOrders" class="py-10 text-center">
            <p class="text-gray-500">Загрузка списка заказов...</p>
        </div>

        <div
            v-if="listError"
            class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
            role="alert"
        >
            <strong class="font-bold">Ошибка!</strong>
            <span class="block sm:inline"> {{ listError }}</span>
        </div>

        <div
            v-if="!isLoadingOrders && !listError"
            class="overflow-hidden rounded-lg bg-white shadow-md"
        >
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            ID Заказа
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Покупатель
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Сумма
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Статус
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Дата заказа
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="ordersData.data.length === 0">
                        <td
                            colspan="5"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Заказы не найдены.
                        </td>
                    </tr>
                    <tr
                        v-for="order in ordersData.data"
                        :key="order.id"
                        @click="openDetailModal(order)"
                        class="cursor-pointer transition duration-150 ease-in-out hover:bg-gray-100"
                    >
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"
                        >
                            #{{ order.id }}
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-700"
                        >
                            {{ order.user?.name || 'N/A' }}
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900"
                        >
                            {{ formatCurrency(order.total_amount) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                    getStatusClass(order.status),
                                ]"
                            >
                                {{ getStatusText(order.status) }}
                            </span>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{
                                new Date(order.created_at).toLocaleString(
                                    'ru-RU',
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                class="border-t border-gray-200 bg-white px-4 py-3"
                v-if="ordersData.links && ordersData.links.length > 3"
            >
                <div class="-mb-1 flex flex-wrap items-center justify-center">
                    <template
                        v-for="(link, key) in ordersData.links"
                        :key="key"
                    >
                        <div
                            v-if="link.url === null"
                            class="mb-1 mr-1 cursor-default rounded border px-3 py-1.5 text-sm text-gray-400"
                            v-html="link.label"
                        />
                        <button
                            v-else
                            @click="fetchOrders(link.url)"
                            class="mb-1 mr-1 rounded border px-3 py-1.5 text-sm hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500"
                            :class="{
                                'border-indigo-300 bg-white font-semibold text-indigo-600':
                                    link.active,
                            }"
                            v-html="link.label"
                        ></button>
                    </template>
                </div>
            </div>
        </div>

        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 p-4"
            @click.self="closeDetailModal"
        >
            <div
                class="mx-auto my-8 w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <div
                    class="mb-4 flex items-center justify-between border-b pb-3"
                >
                    <h3 class="text-xl font-semibold text-gray-900">
                        Детали заказа
                        {{ selectedOrder ? '#' + selectedOrder.id : '...' }}
                    </h3>
                    <button
                        @click="closeDetailModal"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                    >
                        ×
                    </button>
                </div>

                <div v-if="isModalLoading" class="py-10 text-center">
                    <p class="text-gray-500">Загрузка деталей заказа...</p>
                </div>

                <div
                    v-if="modalError && !isModalLoading"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ modalError }}
                </div>

                <div v-if="!isModalLoading && !modalError && selectedOrder">
                    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <h4
                                class="text-md mb-2 font-semibold text-gray-700"
                            >
                                Информация о покупателе
                            </h4>
                            <p class="text-sm text-gray-600">
                                <strong>Имя:</strong>
                                {{ selectedOrder.user?.name || 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <strong>Email:</strong>
                                {{ selectedOrder.user?.email || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <h4
                                class="text-md mb-2 font-semibold text-gray-700"
                            >
                                Детали заказа
                            </h4>
                            <p class="text-sm text-gray-600">
                                <strong>Дата:</strong>
                                {{
                                    new Date(
                                        selectedOrder.created_at,
                                    ).toLocaleString('ru-RU')
                                }}
                            </p>
                            <p class="text-sm text-gray-600">
                                <strong>Итоговая сумма:</strong>
                                <span class="font-semibold">{{
                                    formatCurrency(selectedOrder.total_amount)
                                }}</span>
                            </p>
                            <div class="mt-2">
                                <label
                                    for="order-status"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Статус заказа:</label
                                >
                                <select
                                    id="order-status"
                                    v-model="selectedOrder.status"
                                    @change="updateOrderStatus"
                                    :disabled="
                                        isUpdatingStatus || isDeletingOrder
                                    "
                                    class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 disabled:bg-gray-100 sm:text-sm"
                                >
                                    <option
                                        v-for="statusOption in availableStatusesForSelect"
                                        :key="statusOption.value"
                                        :value="statusOption.value"
                                    >
                                        {{ statusOption.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="isUpdatingStatus"
                                    class="mt-1 text-xs text-blue-600"
                                >
                                    Обновление статуса...
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4
                            class="text-md mb-2 border-t pt-4 font-semibold text-gray-700"
                        >
                            Состав заказа
                        </h4>
                        <ul
                            v-if="
                                selectedOrder.products &&
                                selectedOrder.products.length > 0
                            "
                            class="max-h-60 divide-y divide-gray-200 overflow-y-auto pr-2"
                        >
                            <li
                                v-for="product in selectedOrder.products"
                                :key="product.id + '-' + product.pivot.quantity"
                                class="flex items-center space-x-4 py-3"
                            >
                                <img
                                    v-if="getImageUrl(product.image_url)"
                                    :src="getImageUrl(product.image_url)"
                                    alt=""
                                    class="h-12 w-12 flex-shrink-0 rounded object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded bg-gray-200 text-xs text-gray-500"
                                >
                                    Нет фото
                                </div>
                                <div class="flex-grow">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ product.name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Кол-во: {{ product.pivot.quantity }}
                                    </p>
                                </div>
                                <p
                                    class="whitespace-nowrap text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        formatCurrency(
                                            product.pivot
                                                .price_at_time_of_order *
                                                product.pivot.quantity,
                                        )
                                    }}
                                </p>
                                <p
                                    class="ml-2 whitespace-nowrap text-xs text-gray-500"
                                >
                                    ({{
                                        formatCurrency(
                                            product.pivot
                                                .price_at_time_of_order,
                                        )
                                    }}
                                    / шт.)
                                </p>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-500">
                            Нет товаров в этом заказе.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-between border-t pt-4">
                        <button
                            @click="deleteOrder"
                            type="button"
                            :disabled="isDeletingOrder || isUpdatingStatus"
                            class="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                isDeletingOrder
                                    ? 'Удаление...'
                                    : 'Удалить заказ'
                            }}
                        </button>
                        <button
                            @click="closeDetailModal"
                            type="button"
                            :disabled="isUpdatingStatus || isDeletingOrder"
                            class="rounded bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300 disabled:opacity-50"
                        >
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showAddOrderModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 p-4"
            @click.self="closeAddOrderModal"
        >
            <div
                class="mx-auto my-8 flex w-full max-w-3xl flex-col rounded-lg bg-white p-6 shadow-xl"
                style="max-height: 90vh"
                @click.stop
            >
                <div
                    class="mb-4 flex flex-shrink-0 items-center justify-between border-b pb-3"
                >
                    <h3 class="text-xl font-semibold text-gray-900">
                        Создать новый заказ
                    </h3>
                    <button
                        @click="closeAddOrderModal"
                        class="text-2xl leading-none text-gray-400 hover:text-gray-600"
                    >
                        ×
                    </button>
                </div>
                <div
                    v-if="addOrderError"
                    class="mb-4 flex-shrink-0 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ addOrderError }}
                </div>
                <form
                    @submit.prevent="addOrder"
                    class="flex flex-grow flex-col space-y-6 overflow-hidden"
                >
                    <div class="flex-shrink-0">
                        <InputLabel
                            for="customer-select"
                            value="Выберите покупателя:"
                        />
                        <SelectInput
                            id="customer-select"
                            class="mt-1 block w-full"
                            v-model="newOrderData.user_id"
                            :options="
                                sortedCustomers.map((c) => ({
                                    value: c.id,
                                    label: `${c.name} (${c.email})`,
                                }))
                            "
                            placeholder="-- Выберите покупателя --"
                            required
                        />
                    </div>
                    <div class="flex flex-grow flex-col overflow-hidden">
                        <h4
                            class="text-md mb-2 flex-shrink-0 border-t pt-4 font-semibold text-gray-700"
                        >
                            Выберите товары:
                        </h4>
                        <div
                            class="mb-2 grid flex-shrink-0 grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <InputLabel
                                    for="product-search"
                                    value="Поиск по ID/Названию"
                                />
                                <TextInput
                                    id="product-search"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="modalProductFilters.search"
                                    placeholder="ID или название..."
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="product-category"
                                    value="Категория"
                                />
                                <SelectInput
                                    id="product-category"
                                    class="mt-1 block w-full"
                                    v-model="modalProductFilters.category_id"
                                    :options="modalCategoryOptions"
                                />
                            </div>
                        </div>
                        <div
                            class="flex-grow space-y-3 overflow-y-auto rounded border p-2"
                        >
                            <div
                                v-if="isLoadingModalProducts"
                                class="p-4 text-center text-sm text-gray-500"
                            >
                                Загрузка товаров...
                            </div>
                            <div
                                v-else-if="
                                    modalProductsData.data.length === 0 &&
                                    !isLoadingModalProducts
                                "
                                class="p-4 text-center text-sm text-gray-500"
                            >
                                Товары не найдены.
                            </div>
                            <div v-else>
                                <div
                                    v-for="product in modalProductsData.data"
                                    :key="product.id"
                                    class="flex items-center space-x-3 border-b pb-2 last:border-b-0 last:pb-0"
                                >
                                    <input
                                        type="checkbox"
                                        :id="'product-select-' + product.id"
                                        :checked="
                                            !!newOrderData.products[product.id]
                                        "
                                        @change="
                                            toggleProductSelection(product)
                                        "
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <img
                                        v-if="getImageUrl(product.image_url)"
                                        :src="getImageUrl(product.image_url)"
                                        alt=""
                                        class="h-10 w-10 flex-shrink-0 rounded object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded bg-gray-200 text-xs text-gray-500"
                                    >
                                        Фото
                                    </div>
                                    <label
                                        :for="'product-select-' + product.id"
                                        class="flex-grow cursor-pointer text-sm font-medium text-gray-700"
                                    >
                                        {{ product.name }} (ID:
                                        {{ product.id }})
                                        <span class="text-gray-500"
                                            >({{
                                                formatCurrency(product.price)
                                            }})</span
                                        >
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        v-if="newOrderData.products[product.id]"
                                        :value="
                                            newOrderData.products[product.id]
                                                .quantity
                                        "
                                        @input="
                                            updateNewOrderQuantity(
                                                product.id,
                                                $event,
                                            )
                                        "
                                        class="w-20 rounded-md border border-gray-300 px-2 py-1 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Кол-во"
                                    />
                                </div>
                            </div>
                        </div>
                        <div
                            class="mt-2 flex-shrink-0 border-t pt-2"
                            v-if="
                                modalProductsData.links &&
                                modalProductsData.links.length > 3
                            "
                        >
                            <div class="-mb-1 flex flex-wrap justify-center">
                                <template
                                    v-for="(
                                        link, key
                                    ) in modalProductsData.links"
                                    :key="key"
                                >
                                    <div
                                        v-if="link.url === null"
                                        class="mb-1 mr-1 cursor-default rounded border px-3 py-1.5 text-sm text-gray-400"
                                        v-html="link.label"
                                    />
                                    <button
                                        v-else
                                        @click="fetchModalProducts(link.url)"
                                        type="button"
                                        class="mb-1 mr-1 rounded border px-3 py-1.5 text-sm hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500"
                                        :class="{
                                            'border-indigo-300 bg-white font-semibold text-indigo-600':
                                                link.active,
                                        }"
                                        v-html="link.label"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex-shrink-0 border-t pt-4 text-right text-lg font-semibold"
                    >
                        Итого: {{ formatCurrency(newOrderTotal) }}
                    </div>
                    <div class="mt-6 flex flex-shrink-0 justify-end space-x-3">
                        <button
                            @click="closeAddOrderModal"
                            type="button"
                            :disabled="isAddingOrder"
                            class="rounded bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300 disabled:opacity-50"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="
                                isAddingOrder ||
                                !newOrderData.user_id ||
                                Object.keys(newOrderData.products).length === 0
                            "
                            class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                isAddingOrder ? 'Создание...' : 'Создать заказ'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
