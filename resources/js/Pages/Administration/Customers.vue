<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const customers = ref([]);
const isLoading = ref(false);
const error = ref(null);

const showEditModal = ref(false);
const editingUser = ref({ id: null, name: '', email: '' });
const editError = ref(null);
const isUpdating = ref(false);
const isDeleting = ref(false);

const API_BASE_URL_CUSTOMERS = '/internal-api/customers';
const API_BASE_URL_USERS = '/internal-api/users';

const fetchCustomers = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        const response = await axios.get(API_BASE_URL_CUSTOMERS);
        customers.value = response.data;
    } catch (err) {
        console.error('Ошибка загрузки покупателей:', err);
        error.value =
            err.response?.data?.message || 'Не удалось загрузить покупателей.';
    } finally {
        isLoading.value = false;
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
        await axios.put(`${API_BASE_URL_USERS}/${editingUser.value.id}`, {
            name: editingUser.value.name,
            email: editingUser.value.email,
        });
        closeEditModal();
        await fetchCustomers();
    } catch (err) {
        console.error('Ошибка обновления покупателя:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось обновить покупателя.';
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
            `Вы уверены, что хотите удалить покупателя "${editingUser.value.name}"? Это действие необратимо.`,
        )
    ) {
        return;
    }

    isDeleting.value = true;
    editError.value = null;
    try {
        await axios.delete(`${API_BASE_URL_USERS}/${editingUser.value.id}`);
        closeEditModal();
        await fetchCustomers();
    } catch (err) {
        console.error('Ошибка удаления покупателя:', err);
        editError.value =
            err.response?.data?.message || 'Не удалось удалить покупателя.';
    } finally {
        isDeleting.value = false;
    }
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

onMounted(() => {
    fetchCustomers();
});
</script>

<template>
    <div class="container mx-auto">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">
            Список покупателей
        </h1>

        <!-- Кнопки добавления здесь нет -->

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
                    <tr v-if="customers.length === 0">
                        <td
                            colspan="3"
                            class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500"
                        >
                            Покупатели не найдены.
                        </td>
                    </tr>
                    <tr
                        v-for="customer in customers"
                        :key="customer.id"
                        @click="openEditModal(customer)"
                        class="cursor-pointer transition duration-150 ease-in-out hover:bg-gray-100"
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ customer.name }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ customer.email }}
                            </div>
                        </td>
                        <td
                            class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                        >
                            {{
                                new Date(
                                    customer.created_at,
                                ).toLocaleDateString()
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
                    Редактировать покупателя
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
                            for="edit-cust-name"
                            class="block text-sm font-medium text-gray-700"
                            >Имя</label
                        >
                        <input
                            type="text"
                            id="edit-cust-name"
                            v-model="editingUser.name"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Имя Покупателя"
                        />
                    </div>
                    <div>
                        <label
                            for="edit-cust-email"
                            class="block text-sm font-medium text-gray-700"
                            >Email</label
                        >
                        <input
                            type="email"
                            id="edit-cust-email"
                            v-model="editingUser.email"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="customer@example.com"
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
