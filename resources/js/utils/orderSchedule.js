export function localTodayIso(now = new Date()) {
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

export function localTomorrowIso(now = new Date()) {
    const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1)

    return localTodayIso(tomorrow)
}

export function formatTimeSlotLabel(slot) {
    if (!slot) {
        return ''
    }

    const [start, end] = String(slot).split('-')

    return `${start}:00 - ${end}:00`
}

export function isTimeSlotAvailable(slot, preferredDate, now = new Date()) {
    if (!slot || !preferredDate) {
        return true
    }

    const startHour = Number(String(slot).split('-')[0])

    if (Number.isNaN(startHour)) {
        return true
    }

    const [year, month, day] = preferredDate.split('-').map(Number)
    const preferredDay = new Date(year, month - 1, day)
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

    if (preferredDay > today) {
        return true
    }

    if (preferredDay < today) {
        return false
    }

    const slotStartsAt = new Date(year, month - 1, day, startHour, 0, 0, 0)

    return now < slotStartsAt
}
