<?php

namespace Tests\Unit;

use App\Actions\Families\AssignSecretSantas;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class AssignSecretSantasTest extends TestCase
{
    /** @return array<string, array{int}> */
    public static function familySizes(): array
    {
        return ['trois personnes' => [3], 'famille moyenne' => [8], 'grande tablée' => [60]];
    }

    #[DataProvider('familySizes')]
    public function test_everyone_gives_and_receives_exactly_one_gift_in_a_single_circle(int $size): void
    {
        $people = $this->people($size);
        $assignments = (new AssignSecretSantas(new Randomizer))->handle($people);

        $this->assertNotNull($assignments);
        $this->assertEqualsCanonicalizing($people, array_keys($assignments));
        $this->assertEqualsCanonicalizing($people, array_values($assignments));

        $current = $people[0];
        for ($step = 1; $step <= $size; $step++) {
            $current = $assignments[$current];
            $this->assertSame($step === $size, $current === $people[0], 'The assignments should form one circle.');
        }
    }

    public function test_couples_are_never_paired_across_many_draws(): void
    {
        $people = $this->people(6);
        $couples = [[$people[0], $people[1]], [$people[2], $people[3]], [$people[4], $people[5]]];

        for ($seed = 0; $seed < 200; $seed++) {
            $assignments = (new AssignSecretSantas(new Randomizer(new Mt19937($seed))))->handle($people, $couples);
            $this->assertNotNull($assignments);
            foreach ($couples as [$first, $second]) {
                $this->assertNotSame($second, $assignments[$first]);
                $this->assertNotSame($first, $assignments[$second]);
            }
        }
    }

    public function test_it_falls_back_to_any_valid_assignment_when_no_circle_exists(): void
    {
        $people = $this->people(4);
        $forbidden = [[$people[0], $people[2]], [$people[0], $people[3]], [$people[1], $people[2]], [$people[1], $people[3]]];

        $assignments = (new AssignSecretSantas(new Randomizer))->handle($people, $forbidden);

        $this->assertSame([$people[0] => $people[1], $people[1] => $people[0], $people[2] => $people[3], $people[3] => $people[2]], array_merge(array_flip($people), $assignments));
    }

    public function test_it_returns_null_when_nobody_can_be_assigned(): void
    {
        $people = $this->people(3);
        $assignSecretSantas = new AssignSecretSantas(new Randomizer);

        $this->assertNull($assignSecretSantas->handle([$people[0]]));
        $this->assertNull($assignSecretSantas->handle($people, [[$people[0], $people[1]], [$people[0], $people[2]]]));
    }

    /** @return list<string> */
    private function people(int $count): array
    {
        return array_map(fn (int $index): string => sprintf('00000000-0000-4000-8000-%012d', $index), range(1, $count));
    }
}
