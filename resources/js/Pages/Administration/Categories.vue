<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios'; // Убедитесь, что axios установлен: npm install axios

const categories = ref([]);
const isLoading = ref(false);
const error = ref(null);

const showAddModal = ref(false);
const newCategoryName = ref('');
const addError = ref(null);
const isAdding = ref(false);

const showEditModal = ref(false);
const editingCategory = ref({ id: null, name: '' });
const editError = ref(null);
const isUpdating = ref(false);
const isDeleting = ref(false);

// --- API Функции ---

const fetchCategories = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/internal-api/categories'); // Убедитесь, что URL правильный
        categories.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки категорий:', err);
        error.value = 'Не удалось загрузить категории.';
    } finally {
        isLoading.value = false;
    }
};

const addCategory = async () => {
    if (!newCategoryName.value.trim()) {
        addError.value = 'Имя категории не может быть пустым.';
        return;
    }
    isAdding.value = true;
    addError.value = null;
    try {
        await axios.post('/internal-api/categories', {
            name: newCategoryName.value,
        });
        closeAddModal();
        await fetchCategories(); // Обновляем список
    } catch (err) {
        console.error('Ошибка добавления категории:', err);
        addError.value =
            err.response?.data?.message || 'Не удалось добавить категорию.';
        if (err.response?.data?.errors?.name) {
            addError.value = err.response.data.errors.name[0];
        }
    } finally {
        isAdding.value = false;
    }
};

const updateCategory = async () => {
    if (!editingCategory.value.name.trim()) {
        editError.value = 'Имя категории не может быть пустым.';
        return;
    }
    if (!editingCategory.value.id) return;

    isUpdating.value = true;
    editError.value = null;
    try {
        await axios.put(
            `/internal-api/categories/${editingCategory.value.id}`,
            {
                name: editingCategory.value.name,
            },
        );
        closeEditModal();
        await fetchCategories(); // Обновляем список
    } catch (err) {
        console.error('Ошибка обновления категории:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось обновить категорию.';
        if (err.response?.data?.errors?.name) {
            editError.value = err.response.data.errors.name[0];
        }
    } finally {
        isUpdating.value = false;
    }
};

const deleteCategory = async () => {
    if (!editingCategory.value.id) return;

    // Простое подтверждение
    if (
        !confirm(
            `Вы уверены, что хотите удалить категорию "${editingCategory.value.name}"?`,
        )
    ) {
        return;
    }

    isDeleting.value = true;
    editError.value = null; // Очищаем ошибку редактирования
    try {
        await axios.delete(
            `/internal-api/categories/${editingCategory.value.id}`,
        );
        closeEditModal();
        await fetchCategories(); // Обновляем список
    } catch (err) {
        console.error('Ошибка удаления категории:', err);
        // Отображаем ошибку в той же области, что и ошибка редактирования
        editError.value =
            err.response?.data?.message || 'Не удалось удалить категорию.';
    } finally {
        isDeleting.value = false;
    }
};

// --- Управление модальными окнами ---

const openAddModal = () => {
    newCategoryName.value = '';
    addError.value = null;
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const openEditModal = (category) => {
    // Копируем объект, чтобы избежать прямой мутации
    editingCategory.value = { ...category };
    editError.value = null;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingCategory.value = { id: null, name: '' }; // Сбрасываем
};

// --- Жизненный цикл ---
onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <div class="container mx-auto">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">
            Управление категориями
        </h1>

        <div class="mb-4">
            <button
                @click="openAddModal"
                class="rounded bg-blue-500 px-4 py-2 font-bold text-white transition duration-150 ease-in-out hover:bg-blue-700"
            >
                Добавить категорию
            </button>
        </div>

        <!-- Индикатор загрузки -->
        <div v-if="isLoading" class="py-10 text-center">
            <p class="text-gray-500">Загрузка...</p>
            <!-- Можно добавить спиннер -->
        </div>

        <!-- Сообщение об ошибке загрузки -->
        <div
            v-if="error"
            class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
            role="alert"
        >
            <strong class="font-bold">Ошибка!</strong>
            <span class="block sm:inline"> {{ error }}</span>
        </div>

        <!-- Таблица категорий -->
        <div
            v-if="!isLoading && !error"
            class="overflow-hidden rounded-lg bg-white shadow-md"
        >
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Имя категории
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Дата создания
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="categories.length === 0">
                        <td
                            colspan="2"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Категории не найдены.
                        </td>
                    </tr>
                    <tr
                        v-for="category in categories"
                        :key="category.id"
                        @click="openEditModal(category)"
                        class="cursor-pointer transition duration-150 ease-in-out hover:bg-gray-100"
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ category.name }}
                            </div>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{
                                new Date(
                                    category.created_at,
                                ).toLocaleDateString()
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно добавления категории -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="closeAddModal"
        >
            <div
                class="mx-auto w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">
                    Добавить новую категорию
                </h3>
                <div
                    v-if="addError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ addError }}
                </div>
                <div>
                    <label
                        for="new-category-name"
                        class="block text-sm font-medium text-gray-700"
                        >Имя категории</label
                    >
                    <input
                        type="text"
                        id="new-category-name"
                        v-model="newCategoryName"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        placeholder="Введите имя"
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
                        @click="addCategory"
                        type="button"
                        :disabled="isAdding"
                        class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ isAdding ? 'Добавление...' : 'Добавить' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Модальное окно редактирования/удаления категории -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="closeEditModal"
        >
            <div
                class="mx-auto w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                @click.stop
            >
                <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">
                    Редактировать категорию
                </h3>
                <div
                    v-if="editError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ editError }}
                </div>
                <div>
                    <label
                        for="edit-category-name"
                        class="block text-sm font-medium text-gray-700"
                        >Имя категории</label
                    >
                    <input
                        type="text"
                        id="edit-category-name"
                        v-model="editingCategory.name"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                        placeholder="Введите имя"
                    />
                </div>
                <div class="mt-6 flex justify-between">
                    <button
                        @click="deleteCategory"
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
                            @click="updateCategory"
                            type="button"
                            :disabled="isUpdating || isDeleting"
                            class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isUpdating ? 'Обновление...' : 'Обновить' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Стили можно добавить здесь, если Tailwind недостаточно */
</style>
