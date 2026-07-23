<script setup>
import { useI18n } from 'vue-i18n'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import PasswordInput from '@/Components/PasswordInput.vue'

const { t } = useI18n()

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value.focus()
            }
        },
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
    <section>
        <div class="mb-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('profile.password.title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ t('profile.password.description') }}</p>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    {{ t('profile.password.current') }}
                </label>
                <PasswordInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    autocomplete="current-password"
                    :class="inputCls(!!form.errors.current_password)"
                />
                <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-red-500">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    {{ t('profile.password.new') }}
                </label>
                <PasswordInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    autocomplete="new-password"
                    :class="inputCls(!!form.errors.password)"
                />
                <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                    {{ t('profile.password.confirm') }}
                </label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    :class="inputCls(!!form.errors.password_confirmation)"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-800"
                >
                    {{ t('profile.password.save') }}
                </button>
                <Transition
                    enter-active-class="transition ease-in-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">
                        {{ t('profile.password.saved') }}
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
