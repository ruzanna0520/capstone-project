<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="БЕШТАУ - Российский производитель электроники" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Главная
            </h2>
        </template>

        <!-- Hero Section -->
        <div class="bg-indigo-700 text-white">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-8 md:grid-cols-2">
                    <div>
                        <h1 class="mb-4 text-4xl font-bold">
                            Группа компаний «Бештау»
                        </h1>
                        <p class="mb-6 text-xl">
                            Разработчик и производитель российской
                            радиоэлектронной техники
                        </p>
                        <p class="mb-8">
                            Компания названа в честь памятника природы – южной
                            горы Бештау
                        </p>
                        <button
                            class="rounded-lg bg-white px-6 py-3 font-bold text-indigo-700 transition hover:bg-gray-100"
                        >
                            Наша продукция
                        </button>
                    </div>
                    <div class="flex justify-center">
                        <div class="rounded-lg p-2">
                            <img
                                src="@assets/images/main.webp"
                                alt="Продукция Бештау"
                                class="w-full rounded"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mission Section -->
        <div class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-800">
                        Наша миссия
                    </h2>
                    <div class="mx-auto h-1 w-24 bg-indigo-400"></div>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-8 shadow-md">
                        <h3 class="mb-4 text-xl font-semibold text-gray-800">
                            Миссия
                        </h3>
                        <p class="text-gray-600">
                            Внесение весомого вклада в обеспечение
                            технологического суверенитета Российской Федерации.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-md">
                        <h3 class="mb-4 text-xl font-semibold text-gray-800">
                            Цель
                        </h3>
                        <p class="text-gray-600">
                            Создание современного производства с
                            конкурентоспособной продукцией.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-md">
                        <h3 class="mb-4 text-xl font-semibold text-gray-800">
                            Задача
                        </h3>
                        <p class="text-gray-600">
                            Производство радиоэлектронной продукции с
                            максимально возможным уровнем локализации в
                            Российской Федерации.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-800">
                        Наши решения
                    </h2>
                    <p class="mx-auto max-w-3xl text-gray-600">
                        Материнские платы, корпуса, блоки питания и многие
                        другие комплектующие, используемые в устройствах
                        «Бештау» — авторские разработки инженеров нашей
                        компании.
                    </p>
                    <div class="mx-auto mt-4 h-1 w-24 bg-indigo-400"></div>
                </div>

                <div
                    class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4"
                >
                    <div class="rounded-lg bg-white p-6 text-center shadow-md">
                        <div class="mb-4 flex justify-center">
                            <img
                                src="@assets/images/мониторы.webp"
                                alt="Мониторы"
                                class="h-32 w-32"
                            />
                        </div>
                        <h3 class="font-semibold text-gray-800">Мониторы</h3>
                    </div>

                    <div class="rounded-lg bg-white p-6 text-center shadow-md">
                        <div class="mb-4 flex justify-center">
                            <img
                                src="@assets/images/мыши.webp"
                                alt="Компьютерные мыши"
                                class="h-32 w-32"
                            />
                        </div>
                        <h3 class="font-semibold text-gray-800">
                            Компьютерные мыши
                        </h3>
                    </div>

                    <div class="rounded-lg bg-white p-6 text-center shadow-md">
                        <div class="mb-4 flex justify-center">
                            <img
                                src="@assets/images/клавиатуры.webp"
                                alt="Клавиатуры"
                                class="h-32 w-32"
                            />
                        </div>
                        <h3 class="font-semibold text-gray-800">Клавиатуры</h3>
                    </div>

                    <div class="rounded-lg bg-white p-6 text-center shadow-md">
                        <div class="mb-4 flex justify-center">
                            <img
                                src="@assets/images/пк.webp"
                                alt="Системные блоки"
                                class="h-32 w-32"
                            />
                        </div>
                        <h3 class="font-semibold text-gray-800">
                            Системные блоки
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Factories Section -->
        <div class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-800">
                        Производственные площадки
                    </h2>
                    <div class="mx-auto h-1 w-24 bg-indigo-400"></div>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div
                        class="overflow-hidden rounded-lg bg-white p-6 shadow-md"
                    >
                        <img
                            src="@assets/images/rnd.webp"
                            alt="Ростов-на-Дону"
                            class="mb-4 h-48 w-full rounded object-cover"
                        />
                        <h3 class="mb-2 text-xl font-semibold text-gray-800">
                            Ростов-на-Дону
                        </h3>
                        <p class="text-gray-600">
                            Современное производство электроники с полным
                            технологическим циклом
                        </p>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white p-6 shadow-md"
                    >
                        <img
                            src="@assets/images/es.webp"
                            alt="Ессентуки"
                            class="mb-4 h-48 w-full rounded object-cover"
                        />
                        <h3 class="mb-2 text-xl font-semibold text-gray-800">
                            Ессентуки
                        </h3>
                        <p class="text-gray-600">
                            Производственная площадка с передовыми технологиями
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certification Section -->
        <div class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="rounded-lg bg-indigo-700 p-8 text-center text-white shadow-lg"
                >
                    <h2 class="mb-4 text-2xl font-bold">
                        Вся продукция ГК «Бештау» внесена в реестр Минпромторга
                        РФ
                    </h2>
                    <p class="mb-6">
                        Гарантия качества и соответствия государственным
                        стандартам
                    </p>
                    <button
                        class="rounded-lg bg-white px-6 py-3 font-bold text-indigo-700 transition hover:bg-gray-100"
                    >
                        Подробнее о сертификации
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
