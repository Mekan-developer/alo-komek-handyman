<script setup>
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

const { t } = useI18n()

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})
</script>

<template>
    <Head :title="t('profile.title')" />

    <AdminLayout :title="t('profile.title')">
        <div class="mx-auto max-w-2xl space-y-5">
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-slate-800">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-slate-800">
                <UpdatePasswordForm />
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-slate-800">
                <DeleteUserForm />
            </div>
        </div>
    </AdminLayout>
</template>
