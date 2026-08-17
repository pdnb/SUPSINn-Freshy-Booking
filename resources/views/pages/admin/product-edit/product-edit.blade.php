<div>
    <div class="page-head">
        <div>
            <p class="meta"><a href="{{ route('admin.products') }}">สินค้า</a> / แก้ไข</p>
            <h1 style="margin-top: 6px;">{{ $name !== '' ? $name : ($productId ? 'แก้สินค้า' : 'เพิ่มสินค้า') }}</h1>
            <p class="sub">ชื่อและราคาจำเป็น — ไม่มี SKU หรือราคาต่อไซส์</p>
        </div>
        <div class="row">
            <button type="button" class="btn btn-secondary" wire:click="saveDraft">บันทึกฉบับร่าง</button>
            <button type="button" class="btn btn-primary" wire:click="publish">เผยแพร่</button>
        </div>
    </div>

    <form class="grid-2-1" wire:submit="publish">
        <div class="stack">
            <section class="panel">
                <div class="panel-head"><h2>ข้อมูลพื้นฐาน</h2></div>
                <div class="panel-body stack">
                    <div class="field">
                        <label for="product-name">ชื่อสินค้า *</label>
                        <input
                            id="product-name"
                            class="input @error('name') is-invalid @enderror"
                            type="text"
                            wire:model.live.debounce.300ms="name"
                        >
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="product-description">คำอธิบาย</label>
                        <textarea
                            id="product-description"
                            class="textarea"
                            wire:model="description"
                            placeholder="คำอธิบายสั้นๆ สำหรับหน้าร้าน"
                        ></textarea>
                    </div>
                    <div class="field">
                        <span class="field-caption">ชนิด</span>
                        <div class="radio-list" role="radiogroup" aria-label="ชนิด">
                            <label class="radio-card">
                                <input type="radio" wire:model.live="type" value="simple">
                                <span><strong>ชิ้นเดียว</strong></span>
                            </label>
                            <label class="radio-card">
                                <input type="radio" wire:model.live="type" value="bundle">
                                <span><strong>ชุดคอมโบ</strong></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>ราคาและตัวเลือก</h2></div>
                <div class="panel-body stack">
                    <div class="field">
                        <label for="product-price">ราคา (บาท) *</label>
                        <input
                            id="product-price"
                            class="input @error('price') is-invalid @enderror"
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="price"
                        >
                        @error('price') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    @if ($type === 'simple')
                        <div class="stack">
                            <div class="row-between">
                                <h3>ตัวเลือก</h3>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="addOptionGroup">เพิ่มกลุ่ม</button>
                            </div>
                            @foreach ($optionGroups as $index => $group)
                                <div class="field-row option-group-row" wire:key="og-{{ $index }}">
                                    <div class="field">
                                        <label>Label</label>
                                        <input class="input" type="text" wire:model="optionGroups.{{ $index }}.label">
                                    </div>
                                    <div class="field">
                                        <label>Values</label>
                                        <x-admin.tag-input
                                            :tags="$group['values']"
                                            add-method="pushOptionGroupValues"
                                            :add-args="[$index]"
                                            remove-method="removeOptionGroupValue"
                                            :remove-args="[$index]"
                                        />
                                    </div>
                                    <button type="button" class="icon-btn" wire:click="askRemoveOptionGroup({{ $index }})" aria-label="ลบกลุ่ม">
                                        <x-icon name="trash" size="sm" />
                                    </button>
                                </div>
                            @endforeach
                            @error('option_groups') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="stack">
                            <div class="row-between">
                                <h3>ส่วนประกอบชุด</h3>
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="addComponent">เพิ่มสินค้า</button>
                            </div>
                            @error('components') <span class="error">{{ $message }}</span> @enderror
                            @foreach ($components as $index => $component)
                                <div class="card stack" wire:key="comp-{{ $index }}">
                                    <div class="field">
                                        <label>ชื่อสินค้า</label>
                                        <input class="input" type="text" wire:model="components.{{ $index }}.name">
                                    </div>
                                    <div class="row-between">
                                        <span class="field-caption">ตัวเลือก</span>
                                        <button type="button" class="btn btn-ghost btn-sm" wire:click="addComponentOptionGroup({{ $index }})">เพิ่มตัวเลือก</button>
                                    </div>
                                    @foreach ($component['option_groups'] as $groupIndex => $group)
                                        <div class="field-row option-group-row" wire:key="comp-{{ $index }}-og-{{ $groupIndex }}">
                                            <div class="field">
                                                <label>Label</label>
                                                <input class="input" type="text" wire:model="components.{{ $index }}.option_groups.{{ $groupIndex }}.label">
                                            </div>
                                            <div class="field">
                                                <label>Values</label>
                                                <x-admin.tag-input
                                                    :tags="$group['values']"
                                                    add-method="pushComponentOptionGroupValues"
                                                    :add-args="[$index, $groupIndex]"
                                                    remove-method="removeComponentOptionGroupValue"
                                                    :remove-args="[$index, $groupIndex]"
                                                />
                                            </div>
                                            <button type="button" class="icon-btn" wire:click="askRemoveComponentOptionGroup({{ $index }}, {{ $groupIndex }})" aria-label="ลบกลุ่ม">
                                                <x-icon name="trash" size="sm" />
                                            </button>
                                        </div>
                                    @endforeach
                                    <button type="button" class="btn btn-ghost btn-sm" wire:click="askRemoveComponent({{ $index }})">ลบสินค้า</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section class="panel">
            <div class="panel-head">
                <h2>รูปภาพ</h2>
                <span class="pill {{ $is_active ? 'pill-active' : 'pill-draft' }}">
                    {{ $is_active ? 'เผยแพร่' : 'ฉบับร่าง' }}
                </span>
            </div>
            <div class="panel-body stack">
                @if ($product?->images?->isNotEmpty())
                    <div class="media-grid">
                        @foreach ($product->images as $image)
                            <div class="media-item" wire:key="img-{{ $image->id }}">
                                <img class="media-thumb lg" src="{{ $image->url() }}" alt="">
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="deleteImage({{ $image->id }})">ลบ</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($uploads !== [])
                    <div class="media-grid">
                        @foreach ($uploads as $index => $file)
                            <div class="media-item" wire:key="upload-{{ $index }}">
                                @if ($file->isPreviewable())
                                    <img class="media-thumb lg" src="{{ $file->temporaryUrl() }}" alt="">
                                @else
                                    <div class="ph-img">{{ $file->getClientOriginalName() }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="field">
                    <x-admin.dropzone id="product-uploads" model="uploads" />
                    @error('images') <span class="error">{{ $message }}</span> @enderror
                    @error('images.*') <span class="error">{{ $message }}</span> @enderror
                    @error('uploads') <span class="error">{{ $message }}</span> @enderror
                    @error('uploads.*') <span class="error">{{ $message }}</span> @enderror
                </div>
                <p class="meta">สต็อกมีของจัดการที่หน้าสต็อก — ไม่ใส่ในแคตตาล็อก</p>
            </div>
        </section>
    </form>

    <div class="overlay {{ $showDeleteConfirm ? 'is-open' : '' }}" wire:click="closeDeleteConfirm"></div>
    <div class="dialog {{ $showDeleteConfirm ? 'is-open' : '' }}" role="dialog" aria-modal="true" aria-labelledby="product-delete-title">
        <div class="dialog-head">
            <h2 id="product-delete-title">{{ $deleteConfirmTitle }}</h2>
            <button type="button" class="icon-btn" wire:click="closeDeleteConfirm" aria-label="ปิด">
                <x-icon name="x-mark" size="sm" />
            </button>
        </div>
        <div class="dialog-body">
            <p>{{ $deleteConfirmMessage }}</p>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-secondary" wire:click="closeDeleteConfirm">ไม่ใช่</button>
            <button type="button" class="btn btn-danger" wire:click="confirmDelete">ยืนยันลบ</button>
        </div>
    </div>
</div>
