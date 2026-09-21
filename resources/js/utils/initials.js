/**
 * Initials from a full name: "Иван Петров" → "ИП", "Аман" → "А".
 */
export function initials(name) {
    const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean)

    if (parts.length === 0) {
        return '?'
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase()
    }

    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}
