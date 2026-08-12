<script setup>
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PendingOtpPanel from '@/Components/PendingOtpPanel.vue'

const { t } = useI18n()

defineProps({
    codes: { type: Array, default: () => [] },
})
</script>

<template>
    <Head :title="t('pending_otps.title')" />

    <AdminLayout :title="t('pending_otps.title')">
        <PendingOtpPanel :initial-codes="codes" show-empty-state />
    </AdminLayout>
</template>
