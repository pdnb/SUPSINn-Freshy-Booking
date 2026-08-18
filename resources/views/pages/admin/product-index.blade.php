<?php
use App\Models\Product;
use App\Services\Catalog\CatalogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('สินค้า')]
class extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $type = 'all';

    #[Url]
    public string $status = 'all';

    public function clearFilters(): void
    {
        $this->search = '';
        $this->type = 'all';
        $this->status = 'all';
    }

    public function toggle(int $productId, CatalogService $catalog): void
    {
        $product = Product::query()->findOrFail($productId);
        $catalog->setActive($product, ! $product->is_active);
    }

    public function duplicate(int $productId, CatalogService $catalog): void
    {
        $catalog->duplicate(Product::query()->findOrFail($productId));
        $this->dispatch('admin-toast', message: 'คัดลอกสินค้าแล้ว');
    }

    public function render(CatalogService $catalog)
    {
        $isActive = match ($this->status) {
            'active' => true,
            'draft' => false,
            default => null,
        };

        $type = match ($this->type) {
            'simple', 'bundle' => $this->type,
            default => null,
        };

        return $this->view([
            'products' => $catalog->adminList([
                'search' => $this->search !== '' ? $this->search : null,
                'type' => $type,
                'is_active' => $isActive,
            ]),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>สินค้า</h1>
            <p class="sub">แคตตาล็อก — ไม่มี SKU หรือสต็อกขายในหน้านี้</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}">เพิ่มสินค้า</a>
    </div>

    <div class="toolbar">
        <div class="filters">
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="ชื่อสินค้า" aria-label="ค้นหา" style="max-width:260px">
            <select class="select" wire:model.live="type" aria-label="ชนิด" style="max-width:160px">
                <option value="all">ทั้งหมด</option>
                <option value="simple">ชิ้นเดียว</option>
                <option value="bundle">ชุดคอมโบ</option>
            </select>
            <select class="select" wire:model.live="status" aria-label="สถานะ" style="max-width:160px">
                <option value="all">ทั้งหมด</option>
                <option value="active">เปิดขาย</option>
                <option value="draft">ปิดขาย</option>
            </select>
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
        </div>
    </div>

    <section class="panel">
        @if ($products->isEmpty())
            <p class="empty">ยังไม่มีสินค้า</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อ</th>
                        <th>ชนิด</th>
                        <th>ราคา</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td>
                                @if ($product->coverImage)
                                    <img class="media-thumb" src="{{ $product->coverImage->url() }}" alt="">
                                @else
                                    <div class="ph-img sm">ไม่มีรูป</div>
                                @endif
                            </td>
                            <td>
                                <a class="linkish" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                            </td>
                            <td>{{ $product->type === \App\Enums\ProductType::Bundle ? 'ชุดคอมโบ' : 'ชิ้นเดียว' }}</td>
                            <td class="num-col">{{ number_format((float) $product->price, 0) }}</td>
                            <td>
                                <span class="pill {{ $product->is_active ? 'pill-active' : 'pill-draft' }}">
                                    {{ $product->is_active ? 'เผยแพร่' : 'ฉบับร่าง' }}
                                </span>
                            </td>
                            <td>
                                <div class="row">
                                    <button type="button" class="btn btn-ghost btn-sm" wire:click="toggle({{ $product->id }})">
                                        {{ $product->is_active ? 'ปิดขาย' : 'เปิดขาย' }}
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-sm" wire:click="duplicate({{ $product->id }})">คัดลอก</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
