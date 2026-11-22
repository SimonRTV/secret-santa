<?php

namespace App\Actions\Families;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Remembers, in a long-lived encrypted cookie, which exchanges this browser
 * organises and which invitations it opened, so nobody needs an account.
 */
class BrowserExchanges
{
    public const CookieName = 'noel_secret';

    /** Two years, in minutes. */
    private const Lifetime = 60 * 24 * 365 * 2;

    /** How many entries of each kind are kept, newest first; 20 + 20 keeps the encrypted cookie near 3 KB, under the 4 KB browser limit. */
    private const Limit = 20;

    /** @var array{families: list<string>, invitations: list<string>}|null */
    private ?array $remembered = null;

    public function __construct(private Request $request) {}

    /** @return list<string> */
    public function families(): array
    {
        return $this->remembered()['families'];
    }

    /** @return list<string> */
    public function invitations(): array
    {
        return $this->remembered()['invitations'];
    }

    public function rememberFamily(string $familyId): void
    {
        $this->remember('families', $familyId);
    }

    public function rememberInvitation(string $memberId): void
    {
        $this->remember('invitations', $memberId);
    }

    /**
     * @param  list<string>  $memberIds
     */
    public function forget(string $familyId, array $memberIds = []): void
    {
        $remembered = $this->remembered();
        $remembered['families'] = array_values(array_diff($remembered['families'], [$familyId]));
        $remembered['invitations'] = array_values(array_diff($remembered['invitations'], $memberIds));
        $this->store($remembered);
    }

    /**
     * @param  'families'|'invitations'  $kind
     */
    private function remember(string $kind, string $id): void
    {
        $remembered = $this->remembered();
        if (($remembered[$kind][0] ?? null) === $id) {
            return;
        }
        $remembered[$kind] = array_slice([$id, ...array_diff($remembered[$kind], [$id])], 0, self::Limit);
        $this->store($remembered);
    }

    /**
     * @param  array{families: list<string>, invitations: list<string>}  $remembered
     */
    private function store(array $remembered): void
    {
        $this->remembered = $remembered;
        Cookie::queue(Cookie::make(self::CookieName, (string) json_encode($remembered), self::Lifetime, sameSite: 'lax'));
    }

    /** @return array{families: list<string>, invitations: list<string>} */
    private function remembered(): array
    {
        if ($this->remembered !== null) {
            return $this->remembered;
        }
        $cookie = $this->request->cookie(self::CookieName);
        $decoded = is_string($cookie) ? json_decode($cookie, true) : null;
        if (! is_array($decoded)) {
            $decoded = [];
        }

        return $this->remembered = [
            'families' => $this->identifiers($decoded['families'] ?? []),
            'invitations' => $this->identifiers($decoded['invitations'] ?? []),
        ];
    }

    /** @return list<string> */
    private function identifiers(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_slice(array_values(array_unique(array_filter(
            $values,
            fn (mixed $value): bool => is_string($value) && preg_match('/^[0-9a-f-]{36}$/', $value) === 1,
        ))), 0, self::Limit);
    }
}
