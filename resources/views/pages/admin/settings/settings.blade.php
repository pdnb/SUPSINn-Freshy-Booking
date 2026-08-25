<div>
    <div class="page-head">
        <div>
            <h1>ตั้งค่า</h1>
            <p class="sub">เรทค่าส่งตามช่วงจำนวน แบนเนอร์หน้าแรก โลโก้หน้าร้าน มัดจำ และปีการศึกษา</p>
        </div>
    </div>

    <div class="tabs" role="tablist">
        <button type="button" class="tab {{ $tab === 'banners' ? 'is-active' : '' }}" wire:click="$set('tab', 'banners')">แบนเนอร์</button>
        <button type="button" class="tab {{ $tab === 'shipping' ? 'is-active' : '' }}" wire:click="$set('tab', 'shipping')">ค่าส่ง</button>
        <button type="button" class="tab {{ $tab === 'deposit' ? 'is-active' : '' }}" wire:click="$set('tab', 'deposit')">มัดจำ</button>
        <button type="button" class="tab {{ $tab === 'academic' ? 'is-active' : '' }}" wire:click="$set('tab', 'academic')">ปีการศึกษา</button>
        <button type="button" class="tab {{ $tab === 'logo' ? 'is-active' : '' }}" wire:click="$set('tab', 'logo')">โลโก้</button>
    </div>

    @if ($tab === 'shipping')
        <div class="grid-2">
            <section class="panel">
                <div class="panel-head">เรทค่าส่ง</div>
                <div class="panel-body">
                    @forelse ($rates as $rate)
                        <div class="list-row" wire:key="rate-{{ $rate->id }}">
                            <div>
                                <strong>{{ $rate->name }}</strong>
                                <div class="muted">{{ $rate->amountSummary() }}</div>
                            </div>
                            <div class="row">
                                <span class="pill {{ $rate->is_active ? 'pill-active' : 'pill-draft' }}">{{ $rate->is_active ? 'ใช้ได้' : 'ปิด' }}</span>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="editRate({{ $rate->id }})">แก้ไข</button>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="toggleRate({{ $rate->id }})">สลับ</button>
                            </div>
                        </div>
                    @empty
                        <p class="empty">ยังไม่มีเรทค่าส่ง</p>
                    @endforelse
                </div>
            </section>
            <form class="panel" wire:submit="saveRate">
                <div class="panel-head">{{ $rateId ? 'แก้ไขเรท' : 'เพิ่มเรท' }}</div>
                <div class="panel-body stack">
                    <label class="field">
                        ชื่อ
                        <input class="input" type="text" wire:model="rate_name">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </label>
                    @foreach ($tiers as $index => $tier)
                        <div class="field-row tier-row" wire:key="tier-{{ $index }}">
                            <label class="field">เริ่ม <input class="input" type="number" min="1" wire:model="tiers.{{ $index }}.min_qty"></label>
                            <label class="field">ถึง <input class="input" type="number" min="1" wire:model="tiers.{{ $index }}.max_qty" placeholder="ไม่จำกัด"></label>
                            <label class="field">บาท <input class="input" type="number" min="0" step="0.01" wire:model="tiers.{{ $index }}.amount"></label>
                            <button type="button" class="btn btn-ghost btn-sm" wire:click="removeTier({{ $index }})">ลบ</button>
                        </div>
                    @endforeach
                    @error('tiers') <span class="error">{{ $message }}</span> @enderror
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="addTier">เพิ่มช่วง</button>
                    <x-admin.switch wire:model="rate_active">เปิดใช้</x-admin.switch>
                    <div class="row">
                        <button type="submit" class="btn btn-primary">บันทึกเรท</button>
                        @if ($rateId)
                            <button type="button" class="btn btn-ghost" wire:click="cancelEditRate">ยกเลิก</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @elseif ($tab === 'banners')
        <div class="grid-2">
            <section class="panel">
                <div class="panel-head">แบนเนอร์</div>
                <div class="panel-body">
                    @forelse ($banners as $banner)
                        <div class="list-row" wire:key="banner-{{ $banner->id }}">
                            <div class="row">
                                <img class="media-thumb" src="{{ $banner->imageUrl() }}" alt="">
                                @if (filled($banner->url))
                                    <a class="linkish" href="{{ $banner->url }}" target="_blank" rel="noreferrer">{{ $banner->url }}</a>
                                @else
                                    <span class="muted">ไม่มีลิงก์</span>
                                @endif
                            </div>
                            <div class="row">
                                <button type="button" class="icon-btn" wire:click="moveBanner({{ $banner->id }}, -1)" aria-label="เลื่อนขึ้น">
                                    <x-icon name="chevron-up" size="sm" />
                                </button>
                                <button type="button" class="icon-btn" wire:click="moveBanner({{ $banner->id }}, 1)" aria-label="เลื่อนลง">
                                    <x-icon name="chevron-down" size="sm" />
                                </button>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="toggleBanner({{ $banner->id }})">สลับ</button>
                                <button type="button" class="btn btn-danger btn-sm" wire:click="openDeleteBanner({{ $banner->id }})">ลบ</button>
                            </div>
                        </div>
                    @empty
                        <p class="empty">ยังไม่มีแบนเนอร์</p>
                    @endforelse
                </div>
            </section>
            <form class="panel" wire:submit="saveBanner">
                <div class="panel-head">เพิ่มแบนเนอร์</div>
                <div class="panel-body stack">
                    <div class="field">
                        <span class="field-caption">รูป</span>
                        @if ($banner_image?->isPreviewable())
                            <img class="media-thumb lg" src="{{ $banner_image->temporaryUrl() }}" alt="">
                        @endif
                        <x-admin.dropzone
                            id="banner-image"
                            model="banner_image"
                            :multiple="false"
                            title="ลากแบนเนอร์มาวางที่นี่"
                        />
                        @error('image') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <label class="field">
                        <span>ลิงก์ <span class="hint">ไม่บังคับ</span></span>
                        <input class="input" type="url" wire:model="banner_url" placeholder="https://">
                        @error('url') <span class="error">{{ $message }}</span> @enderror
                    </label>
                    <button type="submit" class="btn btn-primary">เพิ่มแบนเนอร์</button>
                </div>
            </form>
        </div>
    @elseif ($tab === 'logo')
        <div class="grid-2">
            <section class="panel">
                <div class="panel-head">โลโก้ปัจจุบัน</div>
                <div class="panel-body stack">
                    @if ($logoUrl)
                        <img class="media-thumb lg" src="{{ $logoUrl }}" alt="{{ config('app.name') }}">
                        <button type="button" class="btn btn-danger btn-sm" wire:click="openClearLogo">ลบโลโก้</button>
                    @else
                        <p class="empty">ไม่มีโลโก้ — header แสดงชื่อร้าน «{{ config('app.name') }}»</p>
                    @endif
                </div>
            </section>
            <form class="panel" wire:submit="saveLogo">
                <div class="panel-head">อัปโหลดโลโก้</div>
                <div class="panel-body stack">
                    <div class="field">
                        <span class="field-caption">รูป</span>
                        @if ($logo_image?->isPreviewable())
                            <img class="media-thumb lg" src="{{ $logo_image->temporaryUrl() }}" alt="">
                        @endif
                        <x-admin.dropzone
                            id="logo-image"
                            model="logo_image"
                            :multiple="false"
                            title="ลากโลโก้มาวางที่นี่"
                        />
                        @error('image') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกโลโก้</button>
                </div>
            </form>
        </div>
    @elseif ($tab === 'deposit')
        <form class="panel" wire:submit="saveDeposit" style="max-width:420px">
            <div class="panel-head">จำนวนมัดจำ</div>
            <div class="panel-body stack">
                <p class="muted">ตั้งเป็นบาทคงที่สำหรับออเดอร์รับเอง (ร้าน / หอประชุม) เมื่อยอดรวมสูงกว่ามัดจำ ลูกค้าเลือกจ่ายมัดจำหรือจ่ายเต็มได้ — ใส่ 0 เพื่อปิดตัวเลือกมัดจำ</p>
                <label class="field">
                    บาท
                    <input class="input" type="number" min="0" step="0.01" wire:model="deposit_amount">
                    @error('amount') <span class="error">{{ $message }}</span> @enderror
                </label>
                <button type="submit" class="btn btn-primary">บันทึกมัดจำ</button>
            </div>
        </form>
    @elseif ($tab === 'academic')
        <form class="panel" wire:submit="saveAcademicYear" style="max-width:420px">
            <div class="panel-head">ปีการศึกษา</div>
            <div class="panel-body stack">
                <p class="muted">รหัสคำสั่งซื้อจะเป็น SRU + 2 ตัวท้ายของ พ.ศ. + running 4 หลัก เช่น SRU69-0001 — เปลี่ยนปีแล้ว running เริ่ม 0001 ใหม่</p>
                <label class="field">
                    พ.ศ.
                    <input class="input" type="number" min="2500" max="2700" step="1" wire:model="academic_year">
                    @error('year') <span class="error">{{ $message }}</span> @enderror
                </label>
                <button type="submit" class="btn btn-primary">บันทึกปีการศึกษา</button>
            </div>
        </form>
    @endif

    <x-admin.confirm-dialog
        :open="$showBannerDeleteConfirm"
        title="ลบแบนเนอร์"
        title-id="delete-banner-title"
        close="closeDeleteBanner"
        confirm="confirmDeleteBanner"
    >
        ต้องการลบแบนเนอร์นี้หรือไม่? การลบจะเอาไฟล์รูปออกด้วย
    </x-admin.confirm-dialog>

    <x-admin.confirm-dialog
        :open="$showLogoClearConfirm"
        title="ลบโลโก้"
        title-id="clear-logo-title"
        close="closeClearLogo"
        confirm="confirmClearLogo"
    >
        ต้องการลบโลโก้หรือไม่? header จะกลับไปแสดงชื่อร้านแทน
    </x-admin.confirm-dialog>
</div>
