<?php
use App\Models\User;
use App\Services\Auth\AdminAccessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('ผู้ใช้')]
class extends Component
{
    public function toggleAccess(int $userId, AdminAccessService $access): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->is_admin) {
            $access->revoke($user);
            $this->dispatch('admin-toast', message: 'ถอนสิทธิ์แล้ว');

            return;
        }

        $access->grant($user);
        $this->dispatch('admin-toast', message: 'อนุญาตแล้ว');
    }

    public function render()
    {
        return $this->view([
            'users' => User::query()->orderBy('is_admin')->latest()->get(),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>ผู้ใช้</h1>
            <p class="sub">อนุญาตหรือถอนสิทธิ์เข้าคอนโซลแอดมิน</p>
        </div>
    </div>

    <section class="panel">
        @error('users')
            <p class="empty" role="alert">{{ $message }}</p>
        @enderror

        @if ($users->isEmpty())
            <p class="empty">ยังไม่มีผู้ใช้</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>ชื่อ</th>
                        <th>อีเมล</th>
                        <th>สถานะ</th>
                        <th>สิทธิ์</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td class="meta">{{ $user->email }}</td>
                            <td>
                                <span class="pill {{ $user->is_admin ? 'pill-paid' : 'pill-pending' }}">
                                    {{ $user->is_admin ? 'แอดมิน' : 'รออนุญาต' }}
                                </span>
                            </td>
                            <td>
                                <x-admin.switch
                                    wire:click="toggleAccess({{ $user->id }})"
                                    :checked="$user->is_admin"
                                    aria-label="{{ $user->is_admin ? 'ถอนสิทธิ์ '.$user->email : 'อนุญาต '.$user->email }}"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
