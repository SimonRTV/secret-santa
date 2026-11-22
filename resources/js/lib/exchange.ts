const longDate = new Intl.DateTimeFormat('fr-CH', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

/**
 * Parses a `YYYY-MM-DD` string as a local calendar day.
 */
function localDay(isoDate: string): Date {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function formatExchangeDate(isoDate: string): string {
    return longDate.format(localDay(isoDate));
}

export function daysUntil(isoDate: string, today: Date = new Date()): number {
    const start = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    );

    return Math.round(
        (localDay(isoDate).getTime() - start.getTime()) / 86_400_000,
    );
}

export function countdownLabel(isoDate: string): string {
    const days = daysUntil(isoDate);

    if (days < 0) {
        return 'L’échange a eu lieu';
    }

    if (days === 0) {
        return 'C’est aujourd’hui !';
    }

    if (days === 1) {
        return 'C’est demain !';
    }

    return `Dans ${days} jours`;
}

/**
 * "jeudi 24 décembre, dans 32 jours", for a single line of exchange details.
 */
export function exchangeDateLine(isoDate: string): string {
    const days = daysUntil(isoDate);
    const when =
        days < 0
            ? 'passé'
            : days === 0
              ? 'aujourd’hui'
              : days === 1
                ? 'demain'
                : `dans ${days} jours`;

    return `${formatExchangeDate(isoDate)}, ${when}`;
}

export type TextSegment = { text: string; href?: string };

/**
 * Splits a wish into plain text and safe http(s) links.
 */
export function linkify(text: string): TextSegment[] {
    const segments: TextSegment[] = [];
    const pattern = /\bhttps?:\/\/[^\s<>"]+[^\s<>".,;:!?)\]]/gi;
    let cursor = 0;

    for (const match of text.matchAll(pattern)) {
        const index = match.index ?? 0;

        if (index > cursor) {
            segments.push({ text: text.slice(cursor, index) });
        }

        try {
            const url = new URL(match[0]);
            segments.push({
                text: url.hostname.replace(/^www\./, ''),
                href: url.href,
            });
        } catch {
            segments.push({ text: match[0] });
        }

        cursor = index + match[0].length;
    }

    if (cursor < text.length) {
        segments.push({ text: text.slice(cursor) });
    }

    return segments;
}

const stampPalette = [
    { ink: '#36573f', tint: '#e5ecdf' },
    { ink: '#99433b', tint: '#f6e5de' },
    { ink: '#866824', tint: '#f4ead0' },
    { ink: '#3d5a73', tint: '#e2eaf1' },
    { ink: '#6d4a6b', tint: '#efe4ee' },
];

/**
 * A stable stamp colour for a person, so they keep it across pages.
 */
export function stampColors(seed: string): { ink: string; tint: string } {
    let hash = 0;

    for (const character of seed) {
        hash = (hash * 31 + character.charCodeAt(0)) >>> 0;
    }

    return stampPalette[hash % stampPalette.length];
}

export function invitationMessage(
    name: string,
    exchangeName: string,
    url: string,
): string {
    return `${name}, voici ton invitation Lutin mystère pour « ${exchangeName} ». Note tes idées de cadeaux et découvre à qui tu vas faire plaisir : ${url}\n\nCe lien est personnel, garde-le pour toi.`;
}
