// MapLibre GL loads tiles/glyphs/sprites inside a Web Worker, which can't
// resolve relative URLs against the page location. This fetches the style
// and rewrites its source/glyphs/sprite URLs to absolute ones, so both a
// same-origin static style.json and an already-absolute tileserver-gl style
// URL work the same way.
//
// Template URLs ({z}/{x}/{y}, {fontstack}, {range}) must NOT go through
// new URL() — it percent-encodes the braces and breaks MapLibre's tokens,
// so absolutization is done with plain string concatenation.
export async function loadMapStyle(styleUrl) {
    const absoluteStyleUrl = new URL(styleUrl, window.location.origin).href
    const style = await fetch(absoluteStyleUrl).then((response) => response.json())
    const styleOrigin = new URL(absoluteStyleUrl).origin
    const styleDir = absoluteStyleUrl.replace(/\/[^/]*$/, '/')

    const absolutize = (url) => {
        if (/^(https?:)?\/\//.test(url)) { return url }

        return url.startsWith('/') ? styleOrigin + url : styleDir + url
    }

    for (const source of Object.values(style.sources ?? {})) {
        if (Array.isArray(source.tiles)) {
            source.tiles = source.tiles.map(absolutize)
        }
        if (source.url) {
            source.url = absolutize(source.url)
        }
    }

    if (style.glyphs) {
        style.glyphs = absolutize(style.glyphs)
    }

    if (style.sprite) {
        style.sprite = absolutize(style.sprite)
    }

    return style
}

// Openmaptiles data quirk: rare POIs carry subclass = " ", so the style asks the
// sprite for an icon literally named " ". Register a transparent 1x1 pixel for
// blank names to keep the console clean; the POI simply renders without an icon.
export function suppressBlankIconWarnings(maplibreMap) {
    maplibreMap?.on('styleimagemissing', (event) => {
        if (!event.id.trim()) {
            maplibreMap.addImage(event.id, { width: 1, height: 1, data: new Uint8Array(4) })
        }
    })
}
