<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const isLoading = ref(false);
const error = ref(null);

const showAddModal = ref(false);
const newUser = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const addError = ref(null);
const isAdding = ref(false);

const showEditModal = ref(false);
const editingUser = ref({ id: null, name: '', email: '' });
const editError = ref(null);
const isUpdating = ref(false);
const isDeleting = ref(false);

const API_BASE_URL = '/internal-api/users'; // Используем один базовый URL для админов

// --- API Функции ---

const fetchUsers = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        // Запрашиваем список админов
        const response = await axios.get(API_BASE_URL);
        users.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки администраторов:', err);
        error.value =
            err.response?.data?.message ||
            'Не удалось загрузить администраторов.';
    } finally {
        isLoading.value = false;
    }
};

const addUser = async () => {
    if (
        !newUser.value.name.trim() ||
        !newUser.value.email.trim() ||
        !newUser.value.password ||
        !newUser.value.password_confirmation
    ) {
        addError.value = 'Все поля обязательны для заполнения.';
        return;
    }
    if (newUser.value.password !== newUser.value.password_confirmation) {
        addError.value = 'Пароли не совпадают.';
        return;
    }
    isAdding.value = true;
    addError.value = null;
    try {
        await axios.post(API_BASE_URL, {
            name: newUser.value.name,
            email: newUser.value.email,
            password: newUser.value.password,
            password_confirmation: newUser.value.password_confirmation,
        });
        closeAddModal();
        await fetchUsers();
    } catch (err) {
        console.error('Ошибка добавления администратора:', err);
        addError.value =
            err.response?.data?.message ||
            'Не удалось добавить администратора.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            addError.value = err.response.data.errors[firstErrorKey][0];
        }
    } finally {
        isAdding.value = false;
    }
};

const updateUser = async () => {
    if (!editingUser.value.name.trim() || !editingUser.value.email.trim()) {
        editError.value = 'Имя и Email не могут быть пустыми.';
        return;
    }
    if (!editingUser.value.id) return;

    isUpdating.value = true;
    editError.value = null;
    try {
        await axios.put(`${API_BASE_URL}/${editingUser.value.id}`, {
            name: editingUser.value.name,
            email: editingUser.value.email,
        });
        closeEditModal();
        await fetchUsers();
    } catch (err) {
        console.error('Ошибка обновления администратора:', err);
        editError.value =
            err.response?.data?.message ||
            'Не удалось обновить администратора.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            editError.value = err.response.data.errors[firstErrorKey][0];
        }
    } finally {
        isUpdating.value = false;
    }
};

const deleteUser = async () => {
    if (!editingUser.value.id) return;

    if (
        !confirm(
            `Вы уверены, что хотите удалить администратора "${editingUser.value.name}"? Это действие необратимо.`,
        )
    ) {
        return;
    }

    isDeleting.value = true;
    editError.value = null;
    try {
        await axios.delete(`${API_BASE_URL}/${editingUser.value.id}`);
        closeEditModal();
        await fetchUsers();
    } catch (err) {
        console.error('Ошибка удаления администратора:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось удалить администратора.';
    } finally {
        isDeleting.value = false;
    }
};

// --- Управление модальными окнами ---

const openAddModal = () => {
    newUser.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    };
    addError.value = null;
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
};

const openEditModal = (user) => {
    editingUser.value = { id: user.id, name: user.name, email: user.email };
    editError.value = null;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = { id: null, name: '', email: '' };
};

// --- Жизненный цикл ---
onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="container mx-auto p-6">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">
            Управление администраторами
        </h1>

        <div class="mb-4">
            <button
                @click="openAddModal"
                class="rounded bg-blue-500 px-4 py-2 font-bold text-white transition duration-150 ease-in-out hover:bg-blue-700"
            >
                Добавить администратора
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
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Имя
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Email
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        >
                            Дата регистрации
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr v-if="users.length === 0">
                        <td
                            colspan="3"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Администраторы не найдены.
                        </td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        @click="openEditModal(user)"
                        class="cursor-pointer transition duration-150 ease-in-out hover:bg-gray-100"
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ user.name }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ user.email }}
                            </div>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно добавления администратора -->
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
                    Добавить администратора
                </h3>
                <div
                    v-if="addError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ addError }}
                </div>
                <form @submit.prevent="addUser" class="space-y-4">
                    <div>
                        <label
                            for="new-user-name"
                            class="block text-sm font-medium text-gray-700"
                            >Имя</label
                        >
                        <input
                            type="text"
                            id="new-user-name"
                            v-model="newUser.name"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Иван Иванов"
                        />
                    </div>
                    <div>
                        <label
                            for="new-user-email"
                            class="block text-sm font-medium text-gray-700"
                            >Email</label
                        >
                        <input
                            type="email"
                            id="new-user-email"
                            v-model="newUser.email"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="admin@example.com"
                        />
                    </div>
                    <div>
                        <label
                            for="new-user-password"
                            class="block text-sm font-medium text-gray-700"
                            >Пароль</label
                        >
                        <input
                            type="password"
                            id="new-user-password"
                            v-model="newUser.password"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="********"
                        />
                    </div>
                    <div>
                        <label
                            for="new-user-password-confirm"
                            class="block text-sm font-medium text-gray-700"
                            >Подтверждение пароля</label
                        >
                        <input
                            type="password"
                            id="new-user-password-confirm"
                            v-model="newUser.password_confirmation"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="********"
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
                            :disabled="isAdding"
                            class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isAdding ? 'Добавление...' : 'Добавить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Модальное окно редактирования/удаления администратора -->
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
                    Редактировать администратора
                </h3>
                <div
                    v-if="editError"
                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-600"
                >
                    {{ editError }}
                </div>
                <form @submit.prevent="updateUser" class="space-y-4">
                    <div>
                        <label
                            for="edit-user-name"
                            class="block text-sm font-medium text-gray-700"
                            >Имя</label
                        >
                        <input
                            type="text"
                            id="edit-user-name"
                            v-model="editingUser.name"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Иван Иванов"
                        />
                    </div>
                    <div>
                        <label
                            for="edit-user-email"
                            class="block text-sm font-medium text-gray-700"
                            >Email</label
                        >
                        <input
                            type="email"
                            id="edit-user-email"
                            v-model="editingUser.email"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="admin@example.com"
                        />
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button
                            @click="deleteUser"
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

<style scoped>
/* Можно добавить стили */
</style>
