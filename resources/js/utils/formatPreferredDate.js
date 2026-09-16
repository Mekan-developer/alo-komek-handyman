export function formatPreferredDate(value, locale = 'ru') {
    if (!value) {
        return null
    }

    const date = new Date(`${value}T12:00:00`)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    const tag = locale === 'tk' ? 'tk' : 'ru-RU'
    const parts = new Intl.DateTimeFormat(tag, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }).formatToParts(date)

    const weekday = parts.find((part) => part.type === 'weekday')?.value
    const day = parts.find((part) => part.type === 'day')?.value
    const month = parts.find((part) => part.type === 'month')?.value

    if (!weekday || !day || !month) {
        return new Intl.DateTimeFormat(tag, {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        }).format(date)
    }

    return `${weekday}, ${day} ${month}`
}
