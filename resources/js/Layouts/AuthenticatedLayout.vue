<script setup>
import { computed, ref, provide, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const showingNavigationDropdown = ref(false);
const showCartModal = ref(false);
const cartItems = ref([]);
const orderProcessing = ref(false);
const orderError = ref(null);
const orderSuccess = ref(null);

const isAdmin = computed(() => page.props.auth.user?.is_admin === true);

const dashboardRouteName = computed(() => {
    return isAdmin.value ? 'admin.dashboard' : 'user.dashboard';
});

const cartCount = computed(() => {
    return cartItems.value.reduce((sum, item) => sum + item.quantity, 0);
});

const cartTotal = computed(() => {
    return cartItems.value
        .reduce((sum, item) => sum + item.price * item.quantity, 0)
        .toFixed(2);
});

const loadCartFromLocalStorage = () => {
    const savedCart = localStorage.getItem('shoppingCart');
    if (savedCart) {
        cartItems.value = JSON.parse(savedCart);
    }
};

const saveCartToLocalStorage = () => {
    localStorage.setItem('shoppingCart', JSON.stringify(cartItems.value));
};

watch(cartItems, saveCartToLocalStorage, { deep: true });

const addToCart = (product) => {
    const existingItem = cartItems.value.find((item) => item.id === product.id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cartItems.value.push({ ...product, quantity: 1 });
    }
    orderSuccess.value = null;
    orderError.value = null;
};

const updateQuantity = (productId, quantity) => {
    const item = cartItems.value.find((item) => item.id === productId);
    if (item) {
        const newQuantity = Math.max(1, quantity);
        item.quantity = newQuantity;
    }
};

const removeFromCart = (productId) => {
    cartItems.value = cartItems.value.filter((item) => item.id !== productId);
};

provide('addToCart', addToCart);
provide('cartItems', cartItems);
provide('updateQuantity', updateQuantity);
provide('removeFromCart', removeFromCart);

const clearCart = () => {
    cartItems.value = [];
    localStorage.removeItem('shoppingCart');
};

const formatCurrency = (value) => {
    // Проверяем, что value - это число или может быть преобразовано в число
    const numberValue = parseFloat(value);
    if (isNaN(numberValue)) {
        return 'N/A'; // Или другое значение по умолчанию
    }
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
    }).format(numberValue);
};

const placeOrder = async () => {
    orderProcessing.value = true;
    orderError.value = null;
    orderSuccess.value = null;

    const orderData = {
        products: cartItems.value.map((item) => ({
            id: item.id,
            quantity: item.quantity,
        })),
    };

    try {
        const response = await axios.post(route('api.orders.store'), orderData);
        if (response.status === 201) {
            orderSuccess.value = 'Заказ успешно оформлен!';
            clearCart();
            setTimeout(() => {
                showCartModal.value = false;
                orderSuccess.value = null;
            }, 2000);
        } else {
            throw new Error('Неожиданный ответ сервера');
        }
    } catch (error) {
        console.error(
            'Order placement error:',
            error.response?.data || error.message,
        );
        orderError.value =
            error.response?.data?.message ||
            'Не удалось оформить заказ. Пожалуйста, попробуйте еще раз.';
    } finally {
        orderProcessing.value = false;
    }
};

loadCartFromLocalStorage();
</script>

<template>
    <div>
        <div class="min-w-screen min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <div class="max-w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route(dashboardRouteName)">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route(dashboardRouteName)"
                                    :active="
                                        route().current('admin.dashboard') ||
                                        route().current('user.dashboard')
                                    "
                                >
                                    Главная
                                </NavLink>

                                <NavLink
                                    v-if="!isAdmin"
                                    :href="route('catalog.index')"
                                    :active="route().current('catalog.index')"
                                >
                                    Каталог
                                </NavLink>
                                <NavLink
                                    v-if="!isAdmin"
                                    :href="route('orders.index')"
                                    :active="route().current('orders.index')"
                                >
                                    Мои Заказы
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <button
                                v-if="!isAdmin"
                                @click="showCartModal = true"
                                class="relative mr-4 inline-flex items-center rounded-md p-2 text-gray-500 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-700 focus:bg-gray-100 focus:text-gray-700 focus:outline-none"
                                aria-label="Корзина"
                            >
                                <svg
                                    viewBox="0 0 32 32"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    class="h-6 w-6"
                                >
                                    <path
                                        d="M6 6 L30 6 27 19 9 19 M27 23 L10 23 5 2 2 2"
                                    ></path>
                                    <circle cx="25" cy="27" r="2"></circle>
                                    <circle cx="12" cy="27" r="2"></circle>
                                </svg>
                                <span
                                    v-if="cartCount > 0"
                                    class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white"
                                >
                                    {{ cartCount }}
                                </span>
                            </button>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{
                                                    $page.props.auth.user?.name
                                                }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Профиль
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Выйти
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                v-if="!isAdmin"
                                @click="showCartModal = true"
                                class="relative mr-2 inline-flex items-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                                aria-label="Корзина"
                            >
                                <svg
                                    viewBox="0 0 32 32"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    class="h-6 w-6"
                                >
                                    <path
                                        d="M6 6 L30 6 27 19 9 19 M27 23 L10 23 5 2 2 2"
                                    ></path>
                                    <circle cx="25" cy="27" r="2"></circle>
                                    <circle cx="12" cy="27" r="2"></circle>
                                </svg>
                                <span
                                    v-if="cartCount > 0"
                                    class="absolute -right-1 -top-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white"
                                >
                                    {{ cartCount }}
                                </span>
                            </button>
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route(dashboardRouteName)"
                            :active="
                                route().current('admin.dashboard') ||
                                route().current('user.dashboard')
                            "
                        >
                            Главная
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="!isAdmin"
                            :href="route('catalog.index')"
                            :active="route().current('catalog.index')"
                        >
                            Каталог
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="!isAdmin"
                            :href="route('orders.index')"
                            :active="route().current('orders.index')"
                        >
                            Мои Заказы
                        </ResponsiveNavLink>
                    </div>

                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                        v-if="$page.props.auth.user"
                    >
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>

                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Профиль
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Выйти
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>

        <Modal
            :show="showCartModal"
            @close="showCartModal = false"
            max-width="2xl"
        >
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Корзина</h2>

                <div
                    v-if="orderSuccess"
                    class="mt-4 rounded-md bg-green-100 p-4 text-sm font-medium text-green-700"
                >
                    {{ orderSuccess }}
                </div>
                <div
                    v-if="orderError"
                    class="mt-4 rounded-md bg-red-100 p-4 text-sm font-medium text-red-700"
                >
                    {{ orderError }}
                </div>

                <div class="mt-6">
                    <ul
                        v-if="cartItems.length > 0"
                        role="list"
                        class="-my-6 divide-y divide-gray-200"
                    >
                        <li
                            v-for="item in cartItems"
                            :key="item.id"
                            class="flex py-6"
                        >
                            <div
                                class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200"
                            >
                                <img
                                    :src="
                                        item.image_url
                                            ? `/storage/${item.image_url}`
                                            : '/storage/products/placeholder.png'
                                    "
                                    :alt="item.name"
                                    class="h-full w-full object-cover object-center"
                                />
                            </div>

                            <div class="ml-4 flex flex-1 flex-col">
                                <div>
                                    <div
                                        class="flex justify-between text-base font-medium text-gray-900"
                                    >
                                        <h3>{{ item.name }}</h3>
                                        <p class="ml-4">
                                            {{
                                                formatCurrency(
                                                    item.price * item.quantity,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ formatCurrency(item.price) }} / шт.
                                    </p>
                                </div>
                                <div
                                    class="flex flex-1 items-end justify-between text-sm"
                                >
                                    <div class="flex items-center">
                                        <label
                                            :for="'quantity-' + item.id"
                                            class="sr-only"
                                            >Количество</label
                                        >
                                        <input
                                            :id="'quantity-' + item.id"
                                            type="number"
                                            min="1"
                                            :value="item.quantity"
                                            @input="
                                                updateQuantity(
                                                    item.id,
                                                    parseInt(
                                                        $event.target.value,
                                                    ) || 1,
                                                )
                                            "
                                            class="w-16 rounded border border-gray-300 text-center"
                                        />
                                    </div>

                                    <div class="flex">
                                        <button
                                            @click="removeFromCart(item.id)"
                                            type="button"
                                            class="font-medium text-indigo-600 hover:text-indigo-500"
                                        >
                                            Удалить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-center text-gray-500">
                        Ваша корзина пуста.
                    </p>
                </div>

                <div
                    v-if="cartItems.length > 0"
                    class="mt-6 border-t border-gray-200 pt-6"
                >
                    <div
                        class="flex justify-between text-base font-medium text-gray-900"
                    >
                        <p>Итого</p>
                        <p>{{ formatCurrency(cartTotal) }}</p>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Стоимость доставки будет рассчитана при оформлении
                        заказа.
                    </p>
                    <div class="mt-6">
                        <PrimaryButton
                            @click="placeOrder"
                            class="w-full justify-center"
                            :class="{ 'opacity-25': orderProcessing }"
                            :disabled="
                                orderProcessing || cartItems.length === 0
                            "
                        >
                            {{
                                orderProcessing
                                    ? 'Обработка...'
                                    : 'Оформить заказ'
                            }}
                        </PrimaryButton>
                    </div>
                    <div
                        class="mt-6 flex justify-center text-center text-sm text-gray-500"
                    >
                        <p>
                            или
                            <button
                                type="button"
                                class="font-medium text-indigo-600 hover:text-indigo-500"
                                @click="showCartModal = false"
                            >
                                Продолжить покупки
                                <span aria-hidden="true"> →</span>
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
