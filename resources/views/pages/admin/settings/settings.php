<?php

use App\Models\AdsBanner;
use App\Models\ShippingRate;
use App\Services\Ads\AdsBannerService;
use App\Services\Checkout\DepositSettingService;
use App\Services\Order\AcademicYearSettingService;
use App\Services\Shipping\ShippingRateService;
use App\Services\Storefront\StorefrontLogoService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts.admin')]
#[Title('ตั้งค่า')]
class extends Component
{
    use WithFileUploads;

    public string $tab = 'banners';

    public ?int $rateId = null;

    public string $rate_name = '';

    /**
     * @var list<array{min_qty: string, max_qty: string, amount: string}>
     */
    public array $tiers = [
        ['min_qty' => '1', 'max_qty' => '', 'amount' => ''],
    ];

    public bool $rate_active = true;

    public string $banner_url = '';

    public ?TemporaryUploadedFile $banner_image = null;

    public bool $showBannerDeleteConfirm = false;

    public ?int $bannerPendingDeleteId = null;

    public ?TemporaryUploadedFile $logo_image = null;

    public bool $showLogoClearConfirm = false;

    public string $deposit_amount = '0.00';

    public string $academic_year = '';

    public function mount(DepositSettingService $deposit, AcademicYearSettingService $academicYear): void
    {
        $this->deposit_amount = $deposit->amount();
        $this->academic_year = (string) $academicYear->year();
    }

    public function editRate(int $id, ShippingRateService $rates): void
    {
        $rate = ShippingRate::query()->findOrFail($id);
        $this->rateId = $rate->id;
        $this->rate_name = $rate->name;
        $this->rate_active = $rate->is_active;
        $this->tiers = collect($rates->tiersFor($rate))
            ->map(fn (array $tier): array => [
                'min_qty' => (string) $tier['min_qty'],
                'max_qty' => $tier['max_qty'] === null ? '' : (string) $tier['max_qty'],
                'amount' => (string) $tier['amount'],
            ])
            ->all();
        $this->tab = 'shipping';
        $this->resetErrorBag();
    }

    public function cancelEditRate(): void
    {
        $this->resetRateForm();
    }

    public function addTier(): void
    {
        $this->tiers[] = ['min_qty' => '', 'max_qty' => '', 'amount' => ''];
    }

    public function removeTier(int $index): void
    {
        unset($this->tiers[$index]);
        $this->tiers = array_values($this->tiers);
    }

    public function saveRate(ShippingRateService $rates): void
    {
        try {
            $payload = [
                'name' => $this->rate_name,
                'tiers' => $this->tiers,
                'is_active' => $this->rate_active,
            ];

            if ($this->rateId === null) {
                $rates->create($payload);
            } else {
                $rates->update(ShippingRate::query()->findOrFail($this->rateId), $payload);
            }

            session()->flash('status', 'บันทึกเรทค่าส่งแล้ว');
            $this->resetRateForm();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function toggleRate(int $id, ShippingRateService $rates): void
    {
        $rate = ShippingRate::query()->findOrFail($id);
        $rates->setActive($rate, ! $rate->is_active);
    }

    public function saveBanner(AdsBannerService $banners): void
    {
        try {
            $banners->create([
                'image' => $this->banner_image,
                'url' => $this->banner_url,
                'is_active' => true,
            ]);
            $this->banner_url = '';
            $this->banner_image = null;
            session()->flash('status', 'เพิ่มแบนเนอร์แล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function toggleBanner(int $id, AdsBannerService $banners): void
    {
        $banner = AdsBanner::query()->findOrFail($id);
        $banners->setActive($banner, ! $banner->is_active);
    }

    public function openDeleteBanner(int $id): void
    {
        AdsBanner::query()->findOrFail($id);
        $this->bannerPendingDeleteId = $id;
        $this->showBannerDeleteConfirm = true;
        $this->tab = 'banners';
    }

    public function closeDeleteBanner(): void
    {
        $this->showBannerDeleteConfirm = false;
        $this->bannerPendingDeleteId = null;
    }

    public function confirmDeleteBanner(AdsBannerService $banners): void
    {
        if ($this->bannerPendingDeleteId === null) {
            return;
        }

        $banners->delete(AdsBanner::query()->findOrFail($this->bannerPendingDeleteId));
        $this->closeDeleteBanner();
        session()->flash('status', 'ลบแบนเนอร์แล้ว');
    }

    public function moveBanner(int $id, int $direction, AdsBannerService $banners): void
    {
        $banners->move(AdsBanner::query()->findOrFail($id), $direction);
    }

    public function saveLogo(StorefrontLogoService $logo): void
    {
        try {
            $logo->update($this->logo_image);
            $this->logo_image = null;
            session()->flash('status', 'บันทึกโลโก้แล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function openClearLogo(): void
    {
        $this->showLogoClearConfirm = true;
        $this->tab = 'logo';
    }

    public function closeClearLogo(): void
    {
        $this->showLogoClearConfirm = false;
    }

    public function confirmClearLogo(StorefrontLogoService $logo): void
    {
        $logo->clear();
        $this->closeClearLogo();
        session()->flash('status', 'ลบโลโก้แล้ว — แสดงชื่อร้านแทน');
    }

    public function saveDeposit(DepositSettingService $deposit): void
    {
        try {
            $deposit->update($this->deposit_amount);
            $this->deposit_amount = $deposit->amount();
            session()->flash('status', 'บันทึกจำนวนมัดจำแล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function saveAcademicYear(AcademicYearSettingService $academicYear): void
    {
        try {
            $academicYear->update($this->academic_year);
            $this->academic_year = (string) $academicYear->year();
            session()->flash('status', 'บันทึกปีการศึกษาแล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render(ShippingRateService $rates, AdsBannerService $banners, StorefrontLogoService $logo)
    {
        return $this->view([
            'rates' => $rates->list(),
            'banners' => $banners->list(),
            'logoUrl' => $logo->url(),
        ]);
    }

    private function resetRateForm(): void
    {
        $this->rateId = null;
        $this->rate_name = '';
        $this->rate_active = true;
        $this->tiers = [['min_qty' => '1', 'max_qty' => '', 'amount' => '']];
        $this->resetErrorBag();
    }
};
