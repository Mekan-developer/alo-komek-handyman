import { onBeforeUnmount, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

/*
 * Публичный канал заказов. Подписан AdminLayout (тосты + счётчик уведомлений)
 * и страницы, которым нужны свежие данные. Поэтому страницы отписываются через
 * `stopListening`, а не `leave`: канал общий, `leave` снял бы и слушателей лэйаута.
 */
const ORDERS_CHANNEL = 'orders'

/**
 * Партиал-релоады всех подписчиков сливаются в один визит: события прилетают
 * пачками (assign сразу шлёт master.assigned + order.status.changed), а Inertia
 * не мерджит параллельные запросы — каждый пришёл бы отдельным round trip'ом.
 */
const pendingProps = new Set()
let flushTimer = null

const FLUSH_DELAY = 300

/**
 * @param {Array<string>} props Ключи Inertia-пропсов, которые нужно перечитать.
 */
export function scheduleRealtimeReload(props) {
    props.forEach((prop) => pendingProps.add(prop))

    clearTimeout(flushTimer)
    flushTimer = setTimeout(() => {
        const only = [...pendingProps]
        pendingProps.clear()

        if (only.length === 0) { return }

        // async — без глобального прогресс-бара: обновление фоновое, пользователь его не запрашивал.
        router.reload({ only, async: true })
    }, FLUSH_DELAY)
}

/**
 * Подписка на события канала `orders` на время жизни компонента.
 * Без Reverb (window.Echo не создан) тихо ничего не делает.
 *
 * @param {Record<string, (payload: object) => void>} handlers Имя события с точкой -> обработчик.
 */
export function useOrdersChannel(handlers) {
    let channel = null

    onMounted(() => {
        if (!window.Echo) { return }

        channel = window.Echo.channel(ORDERS_CHANNEL)

        Object.entries(handlers).forEach(([event, handler]) => {
            channel.listen(event, handler)
        })
    })

    onBeforeUnmount(() => {
        Object.entries(handlers).forEach(([event, handler]) => {
            channel?.stopListening(event, handler)
        })

        channel = null
    })
}
