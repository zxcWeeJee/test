<?php

use App\Models\{Master, ReferralEarning, Referral};
use App\Services\Referral\ReferralService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Текущий мастер приходит в заголовке X-Master-Id и уже разложен
| в атрибуты запроса middleware'ом ResolveCurrentMaster:
|
|     $master = $request->attributes->get('current_master');
|
| Здесь нужно написать три роута — см. README.md.
|
*/

Route::get('/ping', fn () => ['ok' => true]);

// TODO: POST /api/referrals/attach
Route::post('/referrals/attach', function (Request $request, ReferralService $referrals) {
    $master = $request->attributes->get('current_master');

    if (!$master instanceof Master) {
        return response()->json(['message' => 'Unknown or missing X-Master-Id'], 401);
    }

    $code = trim((string) $request->input('code', ''));

    if ($code === '') {
        return response()->json(['message' => 'code is required'], 422);
    }

    $referral = $referrals->registerReferral($master, $code);

    if (!$referral) {
        return response()->json(['message' => 'Invalid code or self-referral'], 422);
    }

    return response()->json([
        'attached' => true,
        'referrer_master_id' => $referral->referrer_master_id,
        'status' => $referral->status,
    ]);
});

// TODO: GET  /api/referrals/my
Route::get('/referrals/my', function (Request $request) {
    $master = $request->attributes->get('current_master');

    if (!$master instanceof Master) {
        return response()->json(['message' => 'Unknown or missing X-Master-Id'], 401);
    }

    $rows = Referral::where('referrer_master_id', $master->id)
        ->with('referredMaster')
        ->get()
        ->map(function (Referral $referral) {
            $earned = ReferralEarning::where('referral_id', $referral->id)->sum('amount');

            return [
                'name' => $referral->referredMaster->name,
                'attached_at' => $referral->created_at->toDateTimeString(),
                'is_rewarded' => $referral->status === Referral::STATUS_REWARDED,
                'earned' => (int) $earned,
            ];
        });

    return response()->json($rows);
});

// TODO: GET  /api/referrals/earnings
Route::get('/referrals/earnings', function (Request $request) {
    $master = $request->attributes->get('current_master');

    if (!$master instanceof Master) {
        return response()->json(['message' => 'Unknown or missing X-Master-Id'], 401);
    }

    $base = ReferralEarning::where('referrer_master_id', $master->id);

    $total = (clone $base)->sum('amount');
    $pending = (clone $base)->where('status', ReferralEarning::STATUS_PENDING)->sum('amount');
    $paid = (clone $base)->where('status', ReferralEarning::STATUS_PAID)->sum('amount');

    $rewardedReferrals = Referral::where('referrer_master_id', $master->id)
        ->where('status', Referral::STATUS_REWARDED)
        ->count();

    return response()->json([
        'total' => (int) $total,
        'pending' => (int) $pending,
        'paid' => (int) $paid,
        'rewarded_referrals' => $rewardedReferrals,
    ]);
});