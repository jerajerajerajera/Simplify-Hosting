<?php

declare(strict_types=1);

namespace Simplify\Hosting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One message in a ticket. An internal note is written by support for support: the customer never sees it.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property bool $staff
 * @property bool $internal
 * @property string $body
 * @property string|null $log the console lines or the explained error sent with it
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
final class TicketMessage extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'simplify_hosting_ticket_messages';

    protected $guarded = ['id'];

    protected $casts = ['ticket_id' => 'integer', 'user_id' => 'integer', 'staff' => 'boolean', 'internal' => 'boolean'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
