<?php

namespace App\Actions\Families;

use App\Models\FamilyMember;
use App\Notifications\NewGiftIdeas;
use Illuminate\Support\Facades\Cache;

class AnnounceNewGiftIdeas
{
    /** How long, in minutes, ideas are gathered before one e-mail announces them all. */
    public const DelayInMinutes = 10;

    /**
     * Tell whoever drew this participant that their wish list gained ideas.
     *
     * The wish list saves itself while it is being typed, so one e-mail is
     * sent per editing session rather than one per save.
     *
     * @param  list<string>  $previousWishes
     */
    public function handle(FamilyMember $member, array $previousWishes): void
    {
        if (array_diff($member->wishList(), $previousWishes) === []) {
            return;
        }

        $giver = FamilyMember::query()->where('recipient_id', $member->id)->whereNotNull('email')->first();
        if ($giver === null) {
            return;
        }

        $delay = now()->addMinutes(self::DelayInMinutes);
        if (! Cache::add('gift-ideas-announcement:'.$member->id, true, $delay)) {
            return;
        }

        $giver->notify((new NewGiftIdeas($member->id))->delay($delay));
    }
}
