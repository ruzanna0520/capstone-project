<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// --- State ---
const products = ref([]);
const categories = ref([]); // Для выпадающего списка
const isLoading = ref(false);
const error = ref(null);

const showAddModal = ref(false);
const newProduct = ref({
    name: '',
    description: '',
    price: '',
    category_id: null,
    image: null, // Для хранения файла
});
const addProductImagePreview = ref(null);
const addError = ref(null);
const isAdding = ref(false);

const showEditModal = ref(false);
const editingProduct = ref({
    id: null,
    name: '',
    description: '',
    price: '',
    category_id: null,
    image_url: null, // Текущий URL картинки
    image: null, // Для нового файла при редактировании
});
const editProductImagePreview = ref(null);
const editError = ref(null);
const isUpdating = ref(false);
const isDeleting = ref(false);

const addFileInputRef = ref(null); // Ref для input type=file в модалке добавления
const editFileInputRef = ref(null); // Ref для input type=file в модалке редактирования

// --- API Base URLs ---
const API_PRODUCTS_URL = '/internal-api/products';
const API_CATEGORIES_URL = '/internal-api/categories';

// --- Computed ---
const sortedCategories = computed(() => {
    // Клонируем массив, чтобы не мутировать оригинал, и сортируем по имени
    return [...categories.value].sort((a, b) => a.name.localeCompare(b.name));
});

// --- Helper ---
const getImageUrl = (path) => {
    // Возвращает полный URL, если есть путь, иначе null
    return path ? `/storage/${path}` : null;
};

// --- API Functions ---
const fetchProducts = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        const response = await axios.get(API_PRODUCTS_URL);
        // Данные могут быть в response.data или response.data.data, если есть пагинация
        products.value = response.data.data
            ? response.data.data
            : response.data;
    } catch (err) {
        console.error('Ошибка загрузки товаров:', err);
        error.value =
            err.response?.data?.message || 'Не удалось загрузить товары.';
    } finally {
        isLoading.value = false;
    }
};

const fetchCategories = async () => {
    // Не устанавливаем isLoading здесь, чтобы не мешать загрузке товаров
    try {
        const response = await axios.get(API_CATEGORIES_URL);
        categories.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки категорий:', err);
        // Можно установить отдельную ошибку для категорий, если нужно
        error.value = error.value || 'Не удалось загрузить категории.';
    }
};

const handleFileChange = (event, target) => {
    const file = event.target.files[0];
    if (!file) {
        target.value = null;
        if (target === newProduct.value.image)
            addProductImagePreview.value = null;
        if (target === editingProduct.value.image)
            editProductImagePreview.value = null;
        return;
    }
    target.image = file; // Сохраняем файл в нужном ref

    // Создаем превью
    const reader = new FileReader();
    reader.onload = (e) => {
        if (target === newProduct.value)
            addProductImagePreview.value = e.target.result;
        if (target === editingProduct.value)
            editProductImagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const addProduct = async () => {
    isAdding.value = true;
    addError.value = null;

    const formData = new FormData();
    formData.append('name', newProduct.value.name);
    formData.append('description', newProduct.value.description || ''); // Пустая строка если null
    formData.append('price', newProduct.value.price);
    formData.append('category_id', newProduct.value.category_id);
    if (newProduct.value.image) {
        formData.append('image', newProduct.value.image);
    }

    try {
        await axios.post(API_PRODUCTS_URL, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        closeAddModal();
        await fetchProducts();
    } catch (err) {
        console.error('Ошибка добавления товара:', err);
        addError.value =
            err.response?.data?.message || 'Не удалось добавить товар.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            addError.value = err.response.data.errors[firstErrorKey][0];
        }
    } finally {
        isAdding.value = false;
    }
};

const updateProduct = async () => {
    if (!editingProduct.value.id) return;
    isUpdating.value = true;
    editError.value = null;

    const formData = new FormData();
    formData.append('name', editingProduct.value.name);
    formData.append('description', editingProduct.value.description || '');
    formData.append('price', editingProduct.value.price);
    formData.append('category_id', editingProduct.value.category_id);
    if (editingProduct.value.image) {
        formData.append('image', editingProduct.value.image);
    }
    formData.append('_method', 'PUT'); // Используем POST с _method=PUT для FormData

    try {
        // Отправляем POST с _method=PUT
        await axios.post(
            `${API_PRODUCTS_URL}/${editingProduct.value.id}`,
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
            },
        );
        closeEditModal();
        await fetchProducts();
    } catch (err) {
        console.error('Ошибка обновления товара:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось обновить товар.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            editError.value = err.response.data.errors[firstErrorKey][0];
        }
    } finally {
        isUpdating.value = false;
    }
};

const deleteProduct = async () => {
    if (!editingProduct.value.id) return;

    if (
        !confirm(
            `Вы уверены, что хотите удалить товар "${editingProduct.value.name}"?`,
        )
    ) {
        return;
    }

    isDeleting.value = true;
    editError.value = null;
    try {
        await axios.delete(`${API_PRODUCTS_URL}/${editingProduct.value.id}`);
        closeEditModal();
        await fetchProducts();
    } catch (err) {
        console.error('Ошибка удаления товара:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось удалить товар.';
    } finally {
        isDeleting.value = false;
    }
};

// --- Modal Control ---
const openAddModal = () => {
    newProduct.value = {
        name: '',
        description: '',
        price: '',
        category_id: categories.value[0]?.id || null,
        image: null,
    };
    addProductImagePreview.value = null;
    addError.value = null;
    if (addFileInputRef.value) addFileInputRef.value.value = ''; // Сброс input file
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const openEditModal = (product) => {
    editingProduct.value = {
        id: product.id,
        name: product.name,
        description: product.description,
        price: product.price,
        category_id: product.category_id,
        image_url: product.image_url, // Сохраняем текущий URL
        image: null, // Сбрасываем поле нового файла
    };
    editProductImagePreview.value = getImageUrl(product.image_url); // Показываем текущее или null
    editError.value = null;
    if (editFileInputRef.value) editFileInputRef.value.value = ''; // Сброс input file
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

// --- Lifecycle ---
onMounted(async () => {
    await fetchCategories(); // Сначала загружаем категории для селектора
    await fetchProducts();
});
</script>

<template>
    <div class="container mx-auto p-6">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">
            Управление товарами
        </h1>

        <div class="mb-4">
            <button
                @click="openAddModal"
                class="rounded bg-blue-500 px-4 py-2 font-bold text-white transition duration-150 ease-in-out hover:bg-blue-700"
            >
                Добавить товар
            </button>
        </div>

        <div v-if="isLoading" class="py-10 text-center">
            <p class="text-gray-500">Загрузка...</p>
        </div>

        <div
            v-if="error"
            class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
            role="alert"
        >
            <strong class="font-bold">Ошибка!</strong>
            <span class="block sm:inline"> {{ error }}</span>
        </div>

        <div
            v-if="!isLoading && !error"
            class="overflow-hidden rounded-lg bg-white shadow-md"
        >
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            scope="col"
                            class="w-20 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Фото
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Название
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Категория
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Цена
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Описание
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Дата
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="products.length === 0">
                        <td
                            colspan="6"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Товары не найдены.
                        </td>
                    </tr>
                    <tr
                        v-for="product in products"
                        :key="product.id"
                        @click="openEditModal(product)"
                        class="cursor-pointer transition duration-150 ease-in-out hover:bg-gray-100"
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <img
                                v-if="getImageUrl(product.image_url)"
                                :src="getImageUrl(product.image_url)"
                                alt="Фото товара"
                                class="h-10 w-10 rounded object-cover"
                            />
                            <div
                                v-else
                                class="flex h-10 w-10 items-center justify-center rounded bg-gray-200 text-xs text-gray-500"
                            >
                                Нет фото
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ product.name }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ product.category?.name || 'Не указана' }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">
                                {{ Number(product.price).toFixed(2) }} ₽
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="line-clamp-2 text-sm text-gray-500">
                                {{ product.description || '-' }}
                            </div>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{
                                new Date(
                                    product.created_at,
                                ).toLocaleDateString()
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- Здесь можно добавить пагинацию, если API её возвращает -->
        </div>

        <!-- Модальное окно добавления товара -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 p-4"
            @click.self="closeAddModal"
        >
            <div
                class="mx-auto my-8 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">
                    Добавить новый товар
                </h3>
                <div
                    v-if="addError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ addError }}
                </div>
                <form @submit.prevent="addProduct" class="space-y-4">
                    <div>
                        <label
                            for="new-prod-name"
                            class="block text-sm font-medium text-gray-700"
                            >Название</label
                        >
                        <input
                            type="text"
                            id="new-prod-name"
                            v-model="newProduct.name"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label
                            for="new-prod-category"
                            class="block text-sm font-medium text-gray-700"
                            >Категория</label
                        >
                        <select
                            id="new-prod-category"
                            v-model="newProduct.category_id"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        >
                            <option
                                v-if="categories.length === 0"
                                disabled
                                value="null"
                            >
                                Сначала добавьте категории
                            </option>
                            <option
                                v-for="cat in sortedCategories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            for="new-prod-price"
                            class="block text-sm font-medium text-gray-700"
                            >Цена (₽)</label
                        >
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="new-prod-price"
                            v-model="newProduct.price"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label
                            for="new-prod-desc"
                            class="block text-sm font-medium text-gray-700"
                            >Описание</label
                        >
                        <textarea
                            id="new-prod-desc"
                            v-model="newProduct.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        ></textarea>
                    </div>
                    <div>
                        <label
                            for="new-prod-image"
                            class="block text-sm font-medium text-gray-700"
                            >Изображение</label
                        >
                        <input
                            type="file"
                            id="new-prod-image"
                            ref="addFileInputRef"
                            @change="handleFileChange($event, newProduct)"
                            accept="image/png, image/jpeg, image/gif"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <img
                            v-if="addProductImagePreview"
                            :src="addProductImagePreview"
                            alt="Превью"
                            class="mt-2 h-20 w-20 rounded object-cover"
                        />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="closeAddModal"
                            type="button"
                            :disabled="isAdding"
                            class="rounded bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300 disabled:opacity-50"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="isAdding || categories.length === 0"
                            class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isAdding ? 'Добавление...' : 'Добавить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Модальное окно редактирования/удаления товара -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50 p-4"
            @click.self="closeEditModal"
        >
            <div
                class="mx-auto my-8 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">
                    Редактировать товар
                </h3>
                <div
                    v-if="editError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ editError }}
                </div>
                <form @submit.prevent="updateProduct" class="space-y-4">
                    <div>
                        <label
                            for="edit-prod-name"
                            class="block text-sm font-medium text-gray-700"
                            >Название</label
                        >
                        <input
                            type="text"
                            id="edit-prod-name"
                            v-model="editingProduct.name"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label
                            for="edit-prod-category"
                            class="block text-sm font-medium text-gray-700"
                            >Категория</label
                        >
                        <select
                            id="edit-prod-category"
                            v-model="editingProduct.category_id"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        >
                            <option
                                v-for="cat in sortedCategories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            for="edit-prod-price"
                            class="block text-sm font-medium text-gray-700"
                            >Цена (₽)</label
                        >
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="edit-prod-price"
                            v-model="editingProduct.price"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>
                    <div>
                        <label
                            for="edit-prod-desc"
                            class="block text-sm font-medium text-gray-700"
                            >Описание</label
                        >
                        <textarea
                            id="edit-prod-desc"
                            v-model="editingProduct.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        ></textarea>
                    </div>
                    <div>
                        <label
                            for="edit-prod-image"
                            class="block text-sm font-medium text-gray-700"
                            >Заменить изображение (необязательно)</label
                        >
                        <div
                            v-if="
                                editProductImagePreview && !editingProduct.image
                            "
                            class="mt-2"
                        >
                            <p class="mb-1 text-xs text-gray-500">Текущее:</p>
                            <img
                                :src="editProductImagePreview"
                                alt="Текущее фото"
                                class="h-20 w-20 rounded object-cover"
                            />
                        </div>
                        <!-- Показываем превью нового файла, если он выбран -->
                        <img
                            v-if="
                                editingProduct.image && editProductImagePreview
                            "
                            :src="editProductImagePreview"
                            alt="Превью нового"
                            class="mt-2 h-20 w-20 rounded object-cover"
                        />
                        <input
                            type="file"
                            id="edit-prod-image"
                            ref="editFileInputRef"
                            @change="handleFileChange($event, editingProduct)"
                            accept="image/png, image/jpeg, image/gif"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button
                            @click="deleteProduct"
                            type="button"
                            :disabled="isDeleting || isUpdating"
                            class="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isDeleting ? 'Удаление...' : 'Удалить' }}
                        </button>
                        <div class="flex space-x-3">
                            <button
                                @click="closeEditModal"
                                type="button"
                                :disabled="isUpdating || isDeleting"
                                class="rounded bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300 disabled:opacity-50"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="isUpdating || isDeleting"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ isUpdating ? 'Обновление...' : 'Обновить' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
