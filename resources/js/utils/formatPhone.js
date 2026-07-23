/**
 * Formats a stored phone number ("+993XXXXXXXX") for display.
 * Returns "+993 XX XX-XX-XX" or the original string if format is unexpected.
 */
export function formatPhone(phone) {
    if (!phone) return '—'
    const digits = String(phone).replace(/\D/g, '')
    const last8 = digits.slice(-8)
    if (last8.length !== 8) return phone
    return `+993 ${last8.slice(0, 2)} ${last8.slice(2, 4)}-${last8.slice(4, 6)}-${last8.slice(6)}`
}
