import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import axios from 'axios'

export const useLocaleStore = defineStore('locale', () => {
    const locale = ref(localStorage.getItem('locale') || 'ru')

    function setLocale(lang) {
        locale.value = lang
    }

    watch(locale, (value) => {
        localStorage.setItem('locale', value)
        axios.post(`/locale/${value}`)
    })

    return { locale, setLocale }
})
