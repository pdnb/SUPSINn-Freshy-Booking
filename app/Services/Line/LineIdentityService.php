<?php

namespace App\Services\Line;

use App\Models\Order;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class LineIdentityService
{
    private const SessionKey = 'line.user_id';

    public function __construct(private Session $session) {}

    public function userId(): ?string
    {
        $userId = $this->session->get(self::SessionKey);

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    public function forget(): void
    {
        $this->session->forget(self::SessionKey);
    }

    /**
     * Verify a LIFF ID token with LINE and remember the subject as the guest LINE identity.
     *
     * @throws ValidationException
     */
    public function rememberFromIdToken(string $idToken): string
    {
        $channelId = config('services.line.channel_id');

        if (! is_string($channelId) || $channelId === '') {
            throw ValidationException::withMessages([
                'id_token' => 'ยังไม่ได้ตั้งค่า LINE Channel ID',
            ]);
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post('https://api.line.me/oauth2/v2.1/verify', [
                    'id_token' => $idToken,
                    'client_id' => $channelId,
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'id_token' => 'ยืนยันตัวตน LINE ไม่สำเร็จ',
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'id_token' => 'โทเคน LINE ไม่ถูกต้องหรือหมดอายุ',
            ]);
        }

        /** @var array{sub?: mixed, aud?: mixed} $payload */
        $payload = $response->json() ?? [];

        $sub = $payload['sub'] ?? null;
        $aud = $payload['aud'] ?? null;

        if (! is_string($sub) || $sub === '') {
            throw ValidationException::withMessages([
                'id_token' => 'โทเคน LINE ไม่มีรหัสผู้ใช้',
            ]);
        }

        if ($aud !== $channelId) {
            throw ValidationException::withMessages([
                'id_token' => 'โทเคน LINE ไม่ตรงกับช่องทางนี้',
            ]);
        }

        $this->session->put(self::SessionKey, $sub);

        return $sub;
    }

    /**
     * @return Collection<int, Order>
     */
    public function ordersForCurrentUser(): Collection
    {
        $userId = $this->userId();

        if ($userId === null) {
            return collect();
        }

        return Order::query()
            ->where('line_user_id', $userId)
            ->with(['items', 'slip'])
            ->latest()
            ->get();
    }

    public function isConfigured(): bool
    {
        $liffId = config('services.line.liff_id');
        $channelId = config('services.line.channel_id');

        return is_string($liffId) && $liffId !== ''
            && is_string($channelId) && $channelId !== '';
    }
}
