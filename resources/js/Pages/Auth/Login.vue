<script setup>
import { useI18n } from 'vue-i18n'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useThemeStore } from '@/stores/useThemeStore'
import PasswordInput from '@/Components/PasswordInput.vue'

const { t } = useI18n()
useThemeStore()

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

function inputCls(hasError) {
    return [
        'mt-1.5 block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-sm transition-colors',
        'focus:outline-none focus:ring-1',
        'dark:bg-slate-700 dark:text-white dark:placeholder:text-slate-500',
        hasError
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400 dark:border-red-500'
            : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:focus:border-indigo-400 dark:focus:ring-indigo-400',
    ].join(' ')
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 dark:from-slate-900 dark:to-slate-800">
        <Head :title="t('auth.login.title')" />

        <div class="w-full max-w-md">
            <!-- Logo & title -->
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="flex h-16 shrink-0 items-center px-6">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-gray-200">
                            <image src="/icons/logo/alo_help.png" alt="Logo" class="h-auto w-16" />
                        </div>
                    </div>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Alo-kömek</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ t('auth.login.subtitle') }}</p>
            </div>

            <!-- Card -->
            <div class="rounded-2xl bg-white p-8 shadow-sm dark:bg-slate-800">
                <!-- Status -->
                <div v-if="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('auth.login.email') }}
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="email@example.com"
                            :class="inputCls(!!form.errors.email)"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ t('auth.login.password') }}
                        </label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            :class="inputCls(!!form.errors.password)"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:ring-offset-slate-800"
                            />
                            <span class="text-sm text-gray-600 dark:text-slate-400">{{ t('auth.login.remember') }}</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{ t('auth.login.forgot_password') }}
                        </Link>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900"
                    >
                        {{ form.processing ? t('auth.login.processing') : t('auth.login.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
