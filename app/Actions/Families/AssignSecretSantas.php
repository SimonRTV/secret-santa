<?php

namespace App\Actions\Families;

use Random\Randomizer;

/**
 * Pairs every participant with someone else to offer a gift to.
 *
 * A single gift circle is preferred because it avoids two people simply
 * swapping presents. When exclusions make a circle hard to find, any valid
 * assignment is accepted instead, and null means none exists at all.
 */
class AssignSecretSantas
{
    /** How many search steps the gift circle may take before falling back. */
    private const CircleSearchBudget = 20_000;

    private int $remainingSteps = 0;

    public function __construct(private Randomizer $randomizer) {}

    /**
     * @param  list<string>  $participants
     * @param  list<array{0: string, 1: string}>  $forbiddenPairs  pairs who may not draw each other, in either direction
     * @return array<string, string>|null giver identifier => recipient identifier
     */
    public function handle(array $participants, array $forbiddenPairs = []): ?array
    {
        if (count($participants) < 2) {
            return null;
        }

        $candidates = $this->candidatesFor($participants, $forbiddenPairs);

        return $this->giftCircle($participants, $candidates) ?? $this->anyAssignment($participants, $candidates);
    }

    /**
     * @param  list<string>  $participants
     * @param  list<array{0: string, 1: string}>  $forbiddenPairs
     * @return array<string, list<string>>
     */
    private function candidatesFor(array $participants, array $forbiddenPairs): array
    {
        $forbidden = [];
        foreach ($forbiddenPairs as [$first, $second]) {
            $forbidden[$first][$second] = true;
            $forbidden[$second][$first] = true;
        }

        $candidates = [];
        foreach ($participants as $giver) {
            $candidates[$giver] = array_values(array_filter(
                $participants,
                fn (string $recipient): bool => $recipient !== $giver && ! isset($forbidden[$giver][$recipient]),
            ));
        }

        return $candidates;
    }

    /**
     * @param  list<string>  $participants
     * @param  array<string, list<string>>  $candidates
     * @return array<string, string>|null
     */
    private function giftCircle(array $participants, array $candidates): ?array
    {
        $this->remainingSteps = self::CircleSearchBudget;
        $start = $participants[$this->randomizer->getInt(0, count($participants) - 1)];
        $path = [$start];
        $visited = [$start => true];

        if (! $this->extendCircle($path, $visited, $candidates, count($participants))) {
            return null;
        }

        $assignments = [];
        foreach ($path as $index => $giver) {
            $assignments[$giver] = $path[($index + 1) % count($path)];
        }

        return $assignments;
    }

    /**
     * @param  list<string>  $path
     * @param  array<string, true>  $visited
     * @param  array<string, list<string>>  $candidates
     */
    private function extendCircle(array &$path, array &$visited, array $candidates, int $size): bool
    {
        if (--$this->remainingSteps < 0) {
            return false;
        }

        $current = $path[count($path) - 1];
        if (count($path) === $size) {
            return in_array($path[0], $candidates[$current], true);
        }

        foreach ($this->randomizer->shuffleArray($candidates[$current]) as $next) {
            if (isset($visited[$next])) {
                continue;
            }
            $path[] = $next;
            $visited[$next] = true;
            if ($this->extendCircle($path, $visited, $candidates, $size)) {
                return true;
            }
            array_pop($path);
            unset($visited[$next]);
        }

        return false;
    }

    /**
     * Finds any perfect giver-to-recipient matching with augmenting paths.
     *
     * @param  list<string>  $participants
     * @param  array<string, list<string>>  $candidates
     * @return array<string, string>|null
     */
    private function anyAssignment(array $participants, array $candidates): ?array
    {
        $giverOf = [];
        foreach ($this->randomizer->shuffleArray($participants) as $giver) {
            $seen = [];
            if (! $this->augment($giver, $candidates, $giverOf, $seen)) {
                return null;
            }
        }

        return array_flip($giverOf);
    }

    /**
     * @param  array<string, list<string>>  $candidates
     * @param  array<string, string>  $giverOf  recipient => giver
     * @param  array<string, true>  $seen
     */
    private function augment(string $giver, array $candidates, array &$giverOf, array &$seen): bool
    {
        foreach ($this->randomizer->shuffleArray($candidates[$giver]) as $recipient) {
            if (isset($seen[$recipient])) {
                continue;
            }
            $seen[$recipient] = true;
            if (! isset($giverOf[$recipient]) || $this->augment($giverOf[$recipient], $candidates, $giverOf, $seen)) {
                $giverOf[$recipient] = $giver;

                return true;
            }
        }

        return false;
    }
}
