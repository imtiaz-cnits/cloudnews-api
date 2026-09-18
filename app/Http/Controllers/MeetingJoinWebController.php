<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingJoinWebController extends Controller
{
    /**
     * Smart Deep Link Gateway for incoming meeting links:
     * - /room/{meetingCode}
     * - /join/{meetingCode}
     *
     * Automatically attempts to wake up the Cloud News mobile app.
     * Serves an optimized mobile web page for users in WhatsApp / Facebook in-app browser.
     */
    public function join(Request $request, ?string $meetingCode = null): View
    {
        $rawCode = trim($meetingCode ?? $request->query('code', ''));

        // Extract clean digits
        $cleanDigits = preg_replace('/\D/', '', $rawCode);
        $formattedCode = (strlen($cleanDigits) === 6)
            ? substr($cleanDigits, 0, 3).'-'.substr($cleanDigits, 3, 3)
            : ((strlen($cleanDigits) === 9)
                ? substr($cleanDigits, 0, 3).'-'.substr($cleanDigits, 3, 3).'-'.substr($cleanDigits, 6, 3)
                : $rawCode);

        $meeting = null;
        if (! empty($rawCode)) {
            $meeting = Meeting::with('host:id,name,avatar_url')
                ->where('meeting_code', $rawCode)
                ->orWhere('meeting_code', $formattedCode)
                ->orWhere('meeting_code', $cleanDigits)
                ->orWhere('meeting_code', 'LIKE', "%{$cleanDigits}%")
                ->orWhere('room_name', $rawCode)
                ->orWhere('room_name', strtolower($rawCode))
                ->first();

            if ($meeting && (! $meeting->is_active || $meeting->ended_at !== null)) {
                $meeting->update([
                    'is_active' => true,
                    'ended_at' => null,
                ]);
            }
        }

        $displayCode = $meeting?->meeting_code ?? (! empty($formattedCode) ? $formattedCode : 'CloudNews');
        $deepLinkScheme = "cloudnews://room/{$displayCode}";

        // Android Intent URI for Chrome, WhatsApp, Facebook, WeChat and Android in-app browsers
        $fallbackWebUrl = urlencode(url("/room/{$displayCode}"));
        $androidIntent = "intent://room/{$displayCode}#Intent;scheme=cloudnews;package=com.cloudnews.mobile;S.browser_fallback_url={$fallbackWebUrl};end";

        return view('meeting.join', [
            'meeting' => $meeting,
            'meetingCode' => $displayCode,
            'deepLinkScheme' => $deepLinkScheme,
            'androidIntent' => $androidIntent,
        ]);
    }

    /**
     * Serve Android Digital Asset Links (assetlinks.json)
     * Allows Android OS to auto-verify App Links for cloudnewsmeet.com
     */
    public function assetLinks(): JsonResponse
    {
        $assetLinks = [
            [
                'relation' => [
                    'delegate_permission/common.handle_all_urls',
                ],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => 'com.cloudnews.mobile',
                    'sha256_cert_fingerprints' => [
                        'FA:C6:17:45:DC:09:03:78:6F:B9:ED:E6:2A:96:2B:39:9F:73:48:F0:BB:6F:89:9B:83:32:66:75:91:03:3B:9C',
                    ],
                ],
            ],
        ];

        return response()->json($assetLinks, 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
