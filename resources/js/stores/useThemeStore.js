import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
    const isDark = ref((localStorage.getItem('theme') ?? 'light') === 'dark')

    function toggle() {
        isDark.value = !isDark.value
    }

    watch(isDark, (value) => {
        localStorage.setItem('theme', value ? 'dark' : 'light')
        document.documentElement.classList.toggle('dark', value)
    }, { immediate: true })

    return { isDark, toggle }
})
