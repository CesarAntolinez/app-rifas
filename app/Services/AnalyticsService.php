<?php

namespace App\Services;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getAdminDashboard(): array
    {
        $rafflesByStatus = Raffle::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totalParticipants = DB::table('participants')->count();

        $executedThisMonth = Raffle::where('status', Raffle::STATUS_EXECUTED)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return [
            'raffles_by_status' => $rafflesByStatus,
            'total_participants' => $totalParticipants,
            'executed_this_month' => $executedThisMonth,
        ];
    }

    public function getOrganizerDashboard(User $user): array
    {
        $rafflesByStatus = Raffle::where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $topByParticipants = Raffle::where('user_id', $user->id)
            ->withCount('participants')
            ->orderByDesc('participants_count')
            ->limit(5)
            ->get(['id', 'name', 'status']);

        $nextClosings = Raffle::where('user_id', $user->id)
            ->where('status', Raffle::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->orderBy('ends_at')
            ->limit(5)
            ->get(['id', 'name', 'ends_at']);

        return [
            'raffles_by_status' => $rafflesByStatus,
            'top_by_participants' => $topByParticipants,
            'next_closings' => $nextClosings,
        ];
    }

    public function getTimeline(Raffle $raffle): array
    {
        return DB::table('participants')
            ->where('raffle_id', $raffle->id)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getComparison(User $user): array
    {
        return Raffle::where('user_id', $user->id)
            ->withCount('participants')
            ->withCount('tickets')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'status', 'created_at'])
            ->toArray();
    }
}
