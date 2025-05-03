<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// --- Состояние ---
const orders = ref([]); // Список заказов (с краткой информацией)
const customers = ref([]);
const allProducts = ref([]);
const isLoading = ref(false); // Загрузка списка
const listError = ref(null); // Ошибка загрузки списка

const showDetailModal = ref(false);
const selectedOrder = ref(null); // Полные детали загруженного заказа
const isModalLoading = ref(false); // Загрузка ДЕТАЛЕЙ заказа
const modalError = ref(null); // Ошибка загрузки/обновления/удаления в модалке
const isUpdatingStatus = ref(false);
const isDeletingOrder = ref(false);

const showAddOrderModal = ref(false);
const newOrderData = ref({ user_id: null, products: {} });
const addOrderError = ref(null);
const isAddingOrder = ref(false);

const availableStatuses = ['pending', 'processing', 'completed', 'cancelled'];

// --- API URLs ---
const API_ORDERS_URL = '/internal-api/orders';
const API_CUSTOMERS_URL = '/internal-api/customers';
const API_PRODUCTS_URL = '/internal-api/products';

// --- Computed ---
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
const sortedProducts = computed(() =>
    [...allProducts.value].sort((a, b) => a.name.localeCompare(b.name)),
);

// --- Helpers ---
const getImageUrl = (path) => (path ? `/storage/${path}` : null);
const formatCurrency = (value) => Number(value).toFixed(2) + ' ₽';

// --- API Functions ---
const fetchOrders = async () => {
    isLoading.value = true;
    listError.value = null;
    try {
        const response = await axios.get(API_ORDERS_URL);
        orders.value = response.data.data ? response.data.data : response.data;
    } catch (err) {
        console.error('Ошибка загрузки списка заказов:', err);
        listError.value =
            err.response?.data?.message ||
            'Не удалось загрузить список заказов.';
    } finally {
        isLoading.value = false;
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

const fetchAllProducts = async () => {
    try {
        const response = await axios.get(API_PRODUCTS_URL);
        allProducts.value = response.data.data
            ? response.data.data
            : response.data;
    } catch (err) {
        console.error('Ошибка загрузки всех товаров:', err);
        listError.value = listError.value || 'Не удалось загрузить все товары.';
    }
};

// --- Модифицированная функция открытия модалки деталей ---
const openDetailModal = async (orderStub) => {
    // orderStub содержит только данные из списка (ID, user.name и т.д.)
    if (!orderStub || !orderStub.id) return;

    showDetailModal.value = true; // Показываем модалку сразу
    isModalLoading.value = true; // Включаем индикатор загрузки в модалке
    modalError.value = null;
    selectedOrder.value = null; // Очищаем предыдущие данные

    try {
        // Запрашиваем ПОЛНЫЕ данные для этого заказа
        const response = await axios.get(`${API_ORDERS_URL}/${orderStub.id}`);
        selectedOrder.value = response.data; // Сохраняем ПОЛНЫЕ данные
    } catch (err) {
        console.error('Ошибка загрузки деталей заказа:', err);
        modalError.value =
            err.response?.data?.message ||
            'Не удалось загрузить детали заказа.';
        // Оставляем модалку открытой с ошибкой, или можно закрыть ее:
        // closeDetailModal();
    } finally {
        isModalLoading.value = false; // Выключаем индикатор загрузки в модалке
    }
};

const updateOrderStatus = async () => {
    if (!selectedOrder.value?.id || !selectedOrder.value?.status) return;
    isUpdatingStatus.value = true;
    modalError.value = null;
    const originalStatus = orders.value.find(
        (o) => o.id === selectedOrder.value.id,
    )?.status; // Запоминаем старый статус из списка

    try {
        await axios.put(`${API_ORDERS_URL}/${selectedOrder.value.id}`, {
            status: selectedOrder.value.status,
        });
        // Обновляем статус в ОСНОВНОМ списке заказов для отображения в таблице
        const index = orders.value.findIndex(
            (o) => o.id === selectedOrder.value.id,
        );
        if (index !== -1) {
            orders.value[index].status = selectedOrder.value.status;
        }
        // Статус в selectedOrder.value уже обновлен через v-model
    } catch (err) {
        console.error('Ошибка обновления статуса заказа:', err);
        modalError.value =
            err.response?.data?.message || 'Не удалось обновить статус.';
        // Откатываем статус в selectedOrder.value к тому, что был в списке
        if (originalStatus) selectedOrder.value.status = originalStatus;
    } finally {
        isUpdatingStatus.value = false;
    }
};

const deleteOrder = async () => {
    const orderToDelete = selectedOrder.value;
    if (!orderToDelete?.id) return;
    if (
        !confirm(
            `Вы уверены, что хотите удалить заказ #${orderToDelete.id}? Это действие необратимо.`,
        )
    )
        return;

    isDeletingOrder.value = true;
    modalError.value = null;
    try {
        await axios.delete(`${API_ORDERS_URL}/${orderToDelete.id}`);
        closeDetailModal();
        await fetchOrders(); // Обновляем основной список
    } catch (err) {
        console.error('Ошибка удаления заказа:', err);
        // Ошибку показываем в основном поле, т.к. модалка закрыта
        listError.value =
            err.response?.data?.message || 'Не удалось удалить заказ.';
        // Если нужно оставить модалку и показать ошибку там:
        // modalError.value = err.response?.data?.message || 'Не удалось удалить заказ.';
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

// --- Modal Control ---
const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedOrder.value = null;
    modalError.value = null; // Очищаем ошибку модалки при закрытии
};

const openAddOrderModal = () => {
    newOrderData.value = { user_id: null, products: {} };
    addOrderError.value = null;
    showAddOrderModal.value = true;
};

const closeAddOrderModal = () => {
    showAddOrderModal.value = false;
};

// --- Product Selection ---
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
    event.target.value = quantity; // Обновляем инпут
    if (newOrderData.value.products[productId]) {
        newOrderData.value.products[productId].quantity = quantity;
    }
};

// --- Lifecycle ---
onMounted(async () => {
    await fetchCustomers();
    await fetchAllProducts();
    await fetchOrders(); // Загружаем КРАТКИЙ список заказов
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
                :disabled="customers.length === 0 || allProducts.length === 0"
                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition duration-150 ease-in-out hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Добавить заказ вручную
            </button>
            <p
                v-if="customers.length === 0 || allProducts.length === 0"
                class="mt-1 text-xs text-red-600"
            >
                * Для добавления заказа нужны покупатели и товары в базе.
            </p>
        </div>

        <div v-if="isLoading" class="py-10 text-center">
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
            v-if="!isLoading && !listError"
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
                    <tr v-if="orders.length === 0">
                        <td
                            colspan="5"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Заказы не найдены.
                        </td>
                    </tr>
                    <!-- Используем order из СПИСКА для таблицы -->
                    <tr
                        v-for="order in orders"
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
                                    order.status === 'completed'
                                        ? 'bg-green-100 text-green-800'
                                        : order.status === 'processing'
                                          ? 'bg-yellow-100 text-yellow-800'
                                          : order.status === 'cancelled'
                                            ? 'bg-red-100 text-red-800'
                                            : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ order.status }}
                            </span>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{ new Date(order.created_at).toLocaleString() }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно деталей/редактирования заказа -->
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
                    <!-- Заголовок может показать ID до загрузки полных данных -->
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

                <!-- Индикатор загрузки ДЛЯ МОДАЛКИ -->
                <div v-if="isModalLoading" class="py-10 text-center">
                    <p class="text-gray-500">Загрузка деталей заказа...</p>
                    <!-- Спиннер -->
                </div>

                <!-- Ошибка загрузки/обновления/удаления В МОДАЛКЕ -->
                <div
                    v-if="modalError && !isModalLoading"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ modalError }}
                </div>

                <!-- Содержимое модалки показываем ТОЛЬКО ПОСЛЕ ЗАГРУЗКИ и если нет ошибки -->
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
                                    ).toLocaleString()
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
                                        v-for="status in availableStatuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
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
                <!-- Конец блока v-if="!isModalLoading && !modalError && selectedOrder" -->
            </div>
        </div>

        <!-- Модальное окно добавления заказа (остается без изменений) -->
        <div
            v-if="showAddOrderModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 p-4"
            @click.self="closeAddOrderModal"
        >
            <div
                class="mx-auto my-8 w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <div
                    class="mb-4 flex items-center justify-between border-b pb-3"
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
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ addOrderError }}
                </div>
                <form @submit.prevent="addOrder" class="space-y-6">
                    <div>
                        <label
                            for="customer-select"
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Выберите покупателя:</label
                        >
                        <select
                            id="customer-select"
                            v-model="newOrderData.user_id"
                            required
                            class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        >
                            <option :value="null" disabled>
                                -- Выберите покупателя --
                            </option>
                            <option
                                v-for="customer in sortedCustomers"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.name }} ({{ customer.email }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <h4
                            class="text-md mb-2 border-t pt-4 font-semibold text-gray-700"
                        >
                            Выберите товары:
                        </h4>
                        <div
                            class="max-h-80 space-y-3 overflow-y-auto rounded border p-2"
                        >
                            <div
                                v-if="allProducts.length === 0"
                                class="p-4 text-center text-sm text-gray-500"
                            >
                                Нет доступных товаров для добавления.
                            </div>
                            <div
                                v-for="product in sortedProducts"
                                :key="product.id"
                                class="flex items-center space-x-3 border-b pb-2 last:border-b-0 last:pb-0"
                            >
                                <input
                                    type="checkbox"
                                    :id="'product-select-' + product.id"
                                    :checked="
                                        !!newOrderData.products[product.id]
                                    "
                                    @change="toggleProductSelection(product)"
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
                                    {{ product.name }}
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
                    <div class="border-t pt-4 text-right text-lg font-semibold">
                        Итого: {{ formatCurrency(newOrderTotal) }}
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
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

<style scoped>
/* Стили можно добавить здесь */
</style>
