import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
    const notifications = ref([])

    function add(notification) {
        const id = Date.now() + Math.random()
        notifications.value.push({ id, ...notification })
        setTimeout(() => remove(id), 6000)
    }

    function remove(id) {
        notifications.value = notifications.value.filter((n) => n.id !== id)
    }

    function success(message) { add({ type: 'success', message }) }
    function error(message) { add({ type: 'error', message }) }
    function warning(message) { add({ type: 'warning', message }) }
    function info(message) { add({ type: 'info', message }) }

    return { notifications, add, remove, success, error, warning, info }
})
