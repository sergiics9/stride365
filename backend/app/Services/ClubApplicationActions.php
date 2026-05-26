<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use App\Notifications\ClubApplicationApprovedNotification;
use App\Notifications\ClubApplicationRejectedNotification;
use Throwable;

class ClubApplicationActions
{
    public const RESULT_OK = 'ok';
    public const RESULT_ALREADY_APPROVED = 'already_approved';
    public const RESULT_ALREADY_REJECTED = 'already_rejected';

    public function approve(Club $club, User $actor): string
    {
        if ($club->application_status === Club::STATUS_APPROVED) {
            return self::RESULT_ALREADY_APPROVED;
        }

        $club->fill([
            'application_status' => Club::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'active' => true,
        ])->save();

        if ($club->requested_by) {
            ClubUser::firstOrCreate(
                [
                    'user_id' => $club->requested_by,
                    'club_id' => $club->id,
                    'role' => ClubUser::ROLE_ADMIN,
                ],
                [
                    'status' => ClubUser::STATUS_PENDING,
                    'subscription_name' => ClubUser::buildSubscriptionName('club', $club->id),
                    'joined_at' => now()->toDateString(),
                ]
            );

            $requester = User::find($club->requested_by);
            if ($requester) {
                $subName = ClubUser::buildSubscriptionName('club', $club->id);
                if ($requester->subscribed($subName)) {
                    $cashierSub = $requester->subscription($subName);
                    if ($cashierSub) {
                        ClubUser::syncFromCashierSubscription($cashierSub);
                    }
                }

                $clubUrl = config('app.frontend_url').'/clubes/'.$club->id;
                $requester->notify(new ClubApplicationApprovedNotification($club->fresh(), $clubUrl));
            }
        }

        return self::RESULT_OK;
    }

    public function reject(Club $club, User $actor, string $reason): string
    {
        if ($club->application_status === Club::STATUS_REJECTED) {
            return self::RESULT_ALREADY_REJECTED;
        }

        $club->fill([
            'application_status' => Club::STATUS_REJECTED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'active' => false,
        ])->save();

        if ($club->requested_by) {
            $membership = ClubUser::where('user_id', $club->requested_by)
                ->where('club_id', $club->id)
                ->where('role', ClubUser::ROLE_ADMIN)
                ->first();

            if ($membership) {
                $user = $membership->user;
                $name = $membership->subscription_name ?? ClubUser::buildSubscriptionName('club', $club->id);
                if ($user && $user->subscribed($name)) {
                    try {
                        $user->subscription($name)->cancelNow();
                    } catch (Throwable $e) {
                        // Si Stripe falla, marcamos localmente como cancelada.
                    }
                }
                $membership->update([
                    'status' => ClubUser::STATUS_INACTIVE,
                    'left_at' => now()->toDateString(),
                    'left_reason' => 'Solicitud rechazada: '.$reason,
                ]);
            }

            $requester = User::find($club->requested_by);
            if ($requester) {
                $requester->notify(new ClubApplicationRejectedNotification($club->fresh(), $reason));
            }
        }

        return self::RESULT_OK;
    }
}
