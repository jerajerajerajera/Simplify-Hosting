<?php

declare(strict_types=1);

namespace Simplify\Hosting\Models;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A support question of a customer, about one of their servers or about nothing in particular (billing, a question).
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $server_id
 * @property string $subject
 * @property string|null $category
 * @property string $status open (support's turn), answered (the customer's turn) or closed
 * @property string $priority low, normal or high
 * @property int|null $assigned_to
 * @property bool $staff_unread
 * @property bool $customer_unread
 * @property Carbon|null $last_reply_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read Server|null $server
 * @property-read User|null $assignee
 */
final class Ticket extends Model
{
    public const OPEN = 'open';

    public const ANSWERED = 'answered';

    public const CLOSED = 'closed';

    public const STATUSES = [self::OPEN, self::ANSWERED, self::CLOSED];

    public const PRIORITIES = ['low', 'normal', 'high'];

    protected $table = 'simplify_hosting_tickets';

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'server_id' => 'integer',
        'assigned_to' => 'integer',
        'staff_unread' => 'boolean',
        'customer_unread' => 'boolean',
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }
}
