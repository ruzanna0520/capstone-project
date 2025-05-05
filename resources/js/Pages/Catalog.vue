<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { inject, ref, computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    activeCategoryId: {
        type: Number,
        default: null,
    },
});

const showProductModal = ref(false);
const selectedProduct = ref(null);

const cartItems = inject('cartItems');
const addToCart = inject('addToCart');
const updateQuantity = inject('updateQuantity');
const removeFromCart = inject('removeFromCart');

const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
    }).format(value);
};

const getItemInCart = (productId) => {
    if (!productId) return null;
    return cartItems.value.find((item) => item.id === productId);
};

const openProductModal = (product) => {
    selectedProduct.value = product;
    showProductModal.value = true;
};

const closeProductModal = () => {
    showProductModal.value = false;
    selectedProduct.value = null;
};
</script>

<template>
    <Head title="Каталог продукции" />

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <span class="mr-2 font-medium text-gray-700">Категории:</span>
                <Link
                    :href="route('catalog.index')"
                    :class="[
                        'rounded-md px-3 py-1 text-sm transition',
                        activeCategoryId === null
                            ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500'
                            : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                    ]"
                    preserve-state
                    preserve-scroll
                >
                    Все
                </Link>
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="route('catalog.index', { category: category.id })"
                    :class="[
                        'rounded-md px-3 py-1 text-sm transition',
                        activeCategoryId === category.id
                            ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500'
                            : 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                    ]"
                    preserve-state
                    preserve-scroll
                >
                    {{ category.name }}
                </Link>
            </div>

            <div
                class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 xl:gap-x-8"
            >
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white"
                >
                    <div
                        @click="openProductModal(product)"
                        class="cursor-pointer"
                    >
                        <div
                            class="aspect-h-4 aspect-w-3 sm:aspect-none bg-gray-200 group-hover:opacity-75 sm:h-52"
                        >
                            <img
                                :src="
                                    product.image_url
                                        ? `/storage/${product.image_url}`
                                        : '/storage/products/placeholder.png'
                                "
                                :alt="product.name"
                                class="h-full w-full object-cover object-center sm:h-full sm:w-full"
                            />
                        </div>
                        <div class="p-4 pb-0">
                            <h3
                                class="text-sm font-medium text-gray-900 group-hover:underline"
                            >
                                {{ product.name }}
                            </h3>
                            <p class="mt-1 flex-1 text-sm text-gray-500">
                                {{
                                    product.description?.substring(0, 50) +
                                    (product.description?.length > 50
                                        ? '...'
                                        : '')
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-auto flex flex-1 flex-col justify-end p-4 pt-2"
                    >
                        <p class="text-base font-medium text-gray-900">
                            {{ formatCurrency(product.price) }}
                        </p>
                        <div class="mt-4">
                            <PrimaryButton
                                v-if="!getItemInCart(product.id)"
                                @click.stop="addToCart(product)"
                                class="w-full justify-center"
                            >
                                В корзину
                            </PrimaryButton>

                            <div
                                v-else
                                class="flex items-center justify-between space-x-2"
                            >
                                <div class="flex items-center space-x-1">
                                    <SecondaryButton
                                        @click.stop="
                                            updateQuantity(
                                                product.id,
                                                getItemInCart(product.id)
                                                    .quantity - 1,
                                            )
                                        "
                                        :disabled="
                                            getItemInCart(product.id)
                                                .quantity <= 1
                                        "
                                        class="px-2 py-1 disabled:opacity-50"
                                        aria-label="Уменьшить количество"
                                    >
                                        −
                                    </SecondaryButton>
                                    <span
                                        class="w-10 rounded border border-gray-300 py-1 text-center text-sm"
                                    >
                                        {{ getItemInCart(product.id).quantity }}
                                    </span>
                                    <SecondaryButton
                                        @click.stop="
                                            updateQuantity(
                                                product.id,
                                                getItemInCart(product.id)
                                                    .quantity + 1,
                                            )
                                        "
                                        class="px-2 py-1"
                                        aria-label="Увеличить количество"
                                    >
                                        +
                                    </SecondaryButton>
                                </div>
                                <DangerButton
                                    @click.stop="removeFromCart(product.id)"
                                    class="px-2 py-1"
                                    aria-label="Удалить из корзины"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-4 w-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                        />
                                    </svg>
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="products.links.length > 3"
                class="mt-8 flex justify-between"
            >
                <Link
                    v-if="products.prev_page_url"
                    :href="products.prev_page_url"
                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100"
                    preserve-scroll
                    preserve-state
                    >« Назад</Link
                >
                <div v-else></div>

                <Link
                    v-if="products.next_page_url"
                    :href="products.next_page_url"
                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100"
                    preserve-scroll
                    preserve-state
                    >Вперед »</Link
                >
                <div v-else></div>
            </div>
        </div>
    </div>

    <Modal :show="showProductModal" @close="closeProductModal" max-width="3xl">
        <div class="p-6" v-if="selectedProduct">
            <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <img
                        :src="
                            selectedProduct.image_url
                                ? `/storage/${selectedProduct.image_url}`
                                : '/storage/products/placeholder.png'
                        "
                        :alt="selectedProduct.name"
                        class="h-full w-full rounded-lg object-cover object-center"
                    />
                </div>
                <div class="sm:col-span-2">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ selectedProduct.name }}
                    </h2>
                    <p
                        v-if="selectedProduct.category"
                        class="mt-1 text-sm text-indigo-600"
                    >
                        {{ selectedProduct.category.name }}
                    </p>
                    <p class="mt-2 text-lg text-gray-900">
                        {{ formatCurrency(selectedProduct.price) }}
                    </p>
                    <div class="mt-4">
                        <h3 class="sr-only">Описание</h3>
                        <p class="text-sm text-gray-600">
                            {{ selectedProduct.description }}
                        </p>
                    </div>
                    <div class="mt-6">
                        <div class="mt-4">
                            <PrimaryButton
                                v-if="!getItemInCart(selectedProduct.id)"
                                @click="
                                    addToCart(selectedProduct);
                                    closeProductModal();
                                "
                                class="w-full justify-center"
                            >
                                Добавить в корзину
                            </PrimaryButton>

                            <div
                                v-else
                                class="flex items-center justify-start space-x-4"
                            >
                                <div class="flex items-center space-x-1">
                                    <SecondaryButton
                                        @click="
                                            updateQuantity(
                                                selectedProduct.id,
                                                getItemInCart(
                                                    selectedProduct.id,
                                                ).quantity - 1,
                                            )
                                        "
                                        :disabled="
                                            getItemInCart(selectedProduct.id)
                                                .quantity <= 1
                                        "
                                        class="px-2 py-1 disabled:opacity-50"
                                        aria-label="Уменьшить количество"
                                    >
                                        −
                                    </SecondaryButton>
                                    <span
                                        class="w-10 rounded border border-gray-300 py-1 text-center text-sm"
                                    >
                                        {{
                                            getItemInCart(selectedProduct.id)
                                                .quantity
                                        }}
                                    </span>
                                    <SecondaryButton
                                        @click="
                                            updateQuantity(
                                                selectedProduct.id,
                                                getItemInCart(
                                                    selectedProduct.id,
                                                ).quantity + 1,
                                            )
                                        "
                                        class="px-2 py-1"
                                        aria-label="Увеличить количество"
                                    >
                                        +
                                    </SecondaryButton>
                                </div>
                                <DangerButton
                                    @click="
                                        removeFromCart(selectedProduct.id);
                                        closeProductModal();
                                    "
                                    class="px-2 py-1"
                                    aria-label="Удалить из корзины"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-4 w-4"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                        />
                                    </svg>
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeProductModal">
                    Закрыть
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<style scoped></style>
