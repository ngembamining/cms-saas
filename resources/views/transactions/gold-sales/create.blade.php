@extends('layouts.main')

@section('title', 'New Gold Sale')

@section('content')
<x-page-header
    title="Gold Sale"
    subtitle="Record a new gold sale transaction"
    icon="ik ik-trending-up"
    :breadcrumbs="[
        'Home' => route('dashboard'),
        'Transactions' => route('transactions.index'),
        'Gold Sales' => route('transactions.gold.sales.index'),
        'New' => null
    ]"
/>

<div class="max-w-6xl mx-auto">

    {{-- ============================== --}}
    {{-- SKELETON LOADER                --}}
    {{-- ============================== --}}
    <div id="pageSkeleton">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="h-4 w-20 animate-pulse rounded bg-gray-200"></div>
                    <div class="mt-3 h-6 w-28 animate-pulse rounded bg-gray-200"></div>
                </div>
            @endfor
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="h-5 w-48 animate-pulse rounded bg-gray-200"></div>
            <div class="mt-6 h-40 w-full animate-pulse rounded-lg bg-gray-200"></div>
        </div>
    </div>

    {{-- ============================== --}}
    {{-- REAL CONTENT                   --}}
    {{-- ============================== --}}
    <div id="pageContent" class="hidden opacity-0 transition-opacity duration-300">

        {{-- Stats --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <x-stat-card
                value="{{ number_format($branchCash, 0) }}"
                label="Cash Balance"
                icon="ik ik-dollar-sign"
                color="primary" />

            <x-stat-card
                value="{{ number_format($branchGold, 2) . ' g' }}"
                label="Gold Inventory"
                icon="ik ik-gem"
                color="amber" />

            <x-stat-card
                value="{{ number_format($currentGoldRate, 2) }}"
                label="Gold Price (TZS/g)"
                icon="ik ik-currency-exchange"
                color="green" />

            <x-stat-card
                value="{{ $customers->count() }}"
                label="Customers"
                icon="ik ik-users"
                color="accent" />
        </div>

        {{-- Main Form --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <span>{{ __('Record Gold Sale') }}</span>
                    <span class="text-sm text-gray-500">
                        <i class="ik ik-info-circle mr-1"></i>
                        Available Gold:
                        <strong class="text-amber-600">{{ number_format($branchGold, 2) }} g</strong>
                    </span>
                </div>
            </x-slot:header>

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <i class="ik ik-alert-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    <i class="ik ik-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="mb-1 font-medium">
                        <i class="ik ik-alert-circle mr-2"></i>Please fix the following before submitting:
                    </p>
                    <ul class="ml-6 list-disc">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('transactions.gold.sales.store') }}" id="goldSaleForm">
                @csrf
                {{-- Idempotency token --}}
                <input type="hidden" name="request_token" value="{{ $requestToken }}">

                {{-- ============================== --}}
                {{-- Branch & Customer              --}}
                {{-- ============================== --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-form.select name="branch_id" id="branch_id" label="Branch" :required="true">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $branch->id ?? '') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-form.select>
                        @error('branch_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Searchable customer (type to filter) --}}
                    <div>
                        <label for="customer_search" class="block text-sm font-medium text-gray-700 mb-1">
                            Customer <span class="text-red-500">*</span>
                            <span class="text-gray-400">(type to search)</span>
                        </label>

                        <div id="customerCombo" class="relative">
                            {{-- Value actually submitted --}}
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                            <input type="text" id="customer_search" autocomplete="off"
                                   placeholder="Search customer by name or phone..."
                                   role="combobox" aria-expanded="false" aria-controls="customerList"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 pr-9 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ik ik-search"></i>
                            </span>

                            <ul id="customerList" role="listbox"
                                class="absolute z-30 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-lg"></ul>
                        </div>

                        <p class="mt-1 text-xs text-gray-500">
                            Customer will receive a thank-you SMS if contact is available.
                        </p>
                        @error('customer_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Recent Prices Reference        --}}
                {{-- ============================== --}}
                @if($recentPrices->count() > 0)
                    <div class="mt-4 rounded-lg bg-gray-50 p-3">
                        <p class="mb-2 text-xs font-semibold uppercase text-gray-600">
                            Recent Sale Prices (Last 7 Days)
                        </p>
                        <div class="space-y-1">
                            @foreach($recentPrices as $rp)
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span>{{ number_format($rp->weight, 2) }}g @ {{ $rp->purity }}% purity</span>
                                    <span class="font-medium">{{ number_format($rp->agreed_price, 0) }} TZS/g</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ============================== --}}
                {{-- Gold Items                     --}}
                {{-- ============================== --}}
                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700">
                            Gold Items <span class="text-red-500">*</span>
                        </label>
                        <button type="button" id="addItemBtn"
                                class="text-sm text-primary-600 hover:text-primary-700">
                            <i class="ik ik-plus"></i> Add Item
                        </button>
                    </div>

                    <div id="saleItemsContainer" class="space-y-3">
                        {{-- First item (more items are created in addItem() in JS) --}}
                        <div class="sale-item rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Weight (g)</label>
                                    <input type="number"
                                           class="weight w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                           name="weights[]" placeholder="0.00" step="0.01" min="0.01">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Purity (%)</label>
                                    <input type="number"
                                           class="purity w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                           name="purities[]" value="99.9" step="0.1" min="0.01" max="100">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Sale Price (TZS/g)</label>
                                    {{-- Visible, comma-formatted field (not submitted) --}}
                                    <input type="text" inputmode="decimal" autocomplete="off"
                                           class="price-display w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                           placeholder="0">
                                    {{-- Raw numeric value that is actually submitted --}}
                                    <input type="hidden" class="sale_price" name="prices[]" value="{{ $currentGoldRate }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600">Amount</label>
                                    <div class="amount-display flex h-10 items-center rounded-lg bg-gray-100 px-3 text-sm font-bold text-green-600">
                                        0 TZS
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-end">
                                <button type="button" class="remove-item text-sm text-red-500 hover:text-red-600" style="display:none;">
                                    <i class="ik ik-trash-2"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Markup Rate                    --}}
                {{-- ============================== --}}
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="markup_rate" class="block text-sm font-medium text-gray-700 mb-1">
                            Markup Percentage <span class="text-red-500">*</span>
                        </label>
                        <div class="flex">
                            <input type="number" id="markup_rate" name="markup_rate"
                                   value="{{ old('markup_rate', $defaultMarkupRate) }}"
                                   step="0.1" min="0" max="100" required
                                   class="w-full rounded-l-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500">
                                %
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Deduction percentage from base price.</p>
                        @error('markup_rate')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Formula Info                   --}}
                {{-- ============================== --}}
                <div class="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-700">
                    <strong><i class="ik ik-calculator mr-2"></i>Formula per item:</strong>
                    (Weight × Purity% × SalePrice ÷ {{ (int) $dividerConstant }}) × (1 - Markup%)
                </div>

                {{-- ============================== --}}
                {{-- Totals                         --}}
                {{-- ============================== --}}
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Total Sale Amount (TZS)</label>
                        <div class="flex">
                            <input type="text" id="total_amount_display"
                                   class="w-full rounded-l-lg border border-gray-200 bg-gray-50 px-4 py-3 text-lg font-bold text-green-600 outline-none"
                                   readonly>
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500">
                                TZS
                            </span>
                        </div>
                        {{-- Actual value submitted --}}
                        <input type="hidden" id="total_amount_hidden" name="total_amount">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Total Weight</label>
                        <div class="flex">
                            <input type="text" id="total_weight"
                                   class="w-full rounded-l-lg border border-gray-200 bg-gray-50 px-4 py-3 text-lg font-semibold text-amber-600 outline-none"
                                   readonly>
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500">
                                g
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Last 4 Digits Adjustment       --}}
                {{-- ============================== --}}
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <h6 class="mb-1 text-sm font-semibold text-gray-700">
                        <i class="ik ik-edit-2 mr-2"></i>Amount Adjustment
                    </h6>
                    <p class="mb-2 text-xs text-gray-500">
                        Optionally adjust the last 4 digits of the total amount.
                    </p>
                    <div class="flex gap-2">
                        <input type="text" id="last_digits" name="last_digits" maxlength="4"
                               placeholder="Last 4 digits" pattern="\d{4}" inputmode="numeric"
                               class="w-40 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <button type="button" id="applyAdjustment"
                                class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                            Apply
                        </button>
                        <button type="button" id="resetAdjustment"
                                class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                            Reset
                        </button>
                    </div>
                    @error('last_digits')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ============================== --}}
                {{-- Payment Method                 --}}
                {{-- ============================== --}}
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" id="payment_method" required
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="mobile_money" {{ old('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Reference
                        </label>
                        <input type="text" name="payment_reference" id="payment_reference"
                               placeholder="Optional payment reference"
                               value="{{ old('payment_reference') }}"
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        @error('payment_reference')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Description                    --}}
                {{-- ============================== --}}
                <div class="mt-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <input type="text" name="description" id="description"
                           placeholder="Optional notes"
                           value="{{ old('description') }}"
                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    @error('description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ============================== --}}
                {{-- SMS Notifications Info         --}}
                {{-- ============================== --}}
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h6 class="font-semibold text-blue-800">
                        <i class="ik ik-message-circle mr-2"></i>SMS Notifications
                    </h6>
                    <div class="mt-2 space-y-2 text-sm text-blue-700">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">📱</span>
                            <div><strong>Customer:</strong> Thank-you message</div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">📱</span>
                            <div><strong>Branch Manager:</strong> Sale alert with details</div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">📱</span>
                            <div><strong>Directors:</strong> Sale report summary</div>
                        </div>
                    </div>
                    <div class="mt-3 rounded-lg bg-blue-100 p-2">
                        <p class="text-xs text-blue-700">
                            <i class="ik ik-info mr-1"></i>
                            SMS will be sent automatically after successful sale recording.
                        </p>
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- Actions                        --}}
                {{-- ============================== --}}
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="reset" id="resetFormBtn"
                            class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                        <i class="ik ik-rotate-ccw"></i> Reset
                    </button>
                    <button type="button" id="reviewBtn"
                            class="rounded-lg bg-green-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-green-600">
                        <i class="ik ik-shopping-cart"></i> Review &amp; Submit
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>

{{-- ============================== --}}
{{-- Confirmation Modal             --}}
{{-- ============================== --}}
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-800">
                <i class="ik ik-check-circle mr-2 text-green-600"></i>Confirm Gold Sale
            </h3>
            <button type="button" id="closeConfirmModal" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>

        <div class="max-h-[60vh] overflow-y-auto px-5 py-4 text-sm">
            <p class="mb-3 text-gray-500">
                Please review the details below. This cannot be undone.
            </p>
            <dl id="confirmDetails" class="space-y-2"></dl>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
            <button type="button" id="cancelConfirmBtn"
                    class="rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-300">
                Cancel
            </button>
            <button type="button" id="confirmSubmitBtn"
                    class="rounded-lg bg-green-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-600">
                <i class="ik ik-check"></i> Confirm &amp; Submit
            </button>
        </div>
    </div>
</div>

<script>
    // ============================================
    // GOLD SALE CALCULATOR  (mirrors purchase)
    // ============================================
    const DIVIDER           = {{ (int) $dividerConstant }};
    const BRANCH_GOLD       = {{ (float) $branchGold }};
    const DEFAULT_MARKUP    = {{ (float) $defaultMarkupRate }};
    const CURRENT_GOLD_RATE = {{ (float) $currentGoldRate }};

    // Customer data for the search box
    const CUSTOMERS = @json($customers->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->name, 'contact' => (string) ($c->contact ?? '')])->values());

    let adjustedTotal = null;
    let hasSubmitted = false;

    // ============================================
    // COMMA FORMATTING HELPERS (Sale Price)
    // ============================================
    function formatWithCommas(raw) {
        let [intPart, decPart] = raw.split('.');
        intPart = (intPart || '').replace(/^0+(?=\d)/, '');
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return decPart !== undefined ? intPart + '.' + decPart : intPart;
    }

    function cleanNumber(str) {
        str = String(str).replace(/[^\d.]/g, '');
        const i = str.indexOf('.');
        if (i !== -1) str = str.slice(0, i + 1) + str.slice(i + 1).replace(/\./g, '');
        return str;
    }

    // Set both visible (formatted) and hidden (raw) price values
    function setPrice(item, rawValue) {
        const raw = cleanNumber(rawValue);
        item.querySelector('.sale_price').value = raw;
        item.querySelector('.price-display').value = formatWithCommas(raw);
    }

    function onPriceInput(e) {
        const input = e.target;
        const item = input.closest('.sale-item');

        // Remember how many numeric characters were left of the caret
        const caret = input.selectionStart ?? input.value.length;
        const sigBefore = input.value.slice(0, caret).replace(/[^\d.]/g, '').length;

        const raw = cleanNumber(input.value);
        const formatted = formatWithCommas(raw);
        input.value = formatted;
        item.querySelector('.sale_price').value = raw;

        // Restore caret position after reformatting
        let pos = 0, seen = 0;
        while (pos < formatted.length && seen < sigBefore) {
            if (/[\d.]/.test(formatted[pos])) seen++;
            pos++;
        }
        try { input.setSelectionRange(pos, pos); } catch (_) {}

        calculateItem(input);
    }

    // ---------- Per-item calculation ----------
    function calculateItem(input) {
        const item = input.closest('.sale-item');
        if (!item) return;
        updateItemAmount(item);
        calculateTotal();
    }

    function updateItemAmount(item) {
        const weight = parseFloat(item.querySelector('.weight').value) || 0;
        const purity = parseFloat(item.querySelector('.purity').value) || 0;
        const price  = parseFloat(item.querySelector('.sale_price').value) || 0;
        const markup = parseFloat(document.getElementById('markup_rate').value) || 0;

        if (weight > 0 && purity > 0 && price > 0) {
            const itemAmount = (weight * (purity / 100) * price) / DIVIDER * (1 - (markup / 100));
            item.querySelector('.amount-display').textContent =
                Math.round(itemAmount).toLocaleString() + ' TZS';
        } else {
            item.querySelector('.amount-display').textContent = '0 TZS';
        }
    }

    // ---------- Total calculation ----------
    function calculateTotal() {
        let totalAmount = 0;
        let totalWeight = 0;

        document.querySelectorAll('.sale-item').forEach(item => {
            const txt = item.querySelector('.amount-display').textContent;
            totalAmount += parseFloat(txt.replace(/[^\d.]/g, '')) || 0;
            totalWeight += parseFloat(item.querySelector('.weight').value) || 0;
        });

        const rounded = Math.round(totalAmount);
        document.getElementById('total_amount_display').value = rounded.toLocaleString();
        document.getElementById('total_amount_hidden').value = rounded;
        document.getElementById('total_weight').value = totalWeight.toFixed(2) + ' g';

        // Clear the last-4 input when items change
        document.getElementById('last_digits').value = '';
        adjustedTotal = null;

        updateSubmitState(totalWeight);
    }

    // ---------- Enable/disable submit based on gold stock ----------
    function updateSubmitState(totalWeight) {
        const reviewBtn = document.getElementById('reviewBtn');
        if (!reviewBtn) return;

        if (totalWeight > BRANCH_GOLD && BRANCH_GOLD > 0) {
            reviewBtn.disabled = true;
            reviewBtn.innerHTML = '<i class="ik ik-alert-circle"></i> Insufficient Gold';
            reviewBtn.className = 'rounded-lg bg-gray-400 px-6 py-2.5 text-sm font-medium text-white cursor-not-allowed';
        } else {
            reviewBtn.disabled = false;
            reviewBtn.innerHTML = '<i class="ik ik-shopping-cart"></i> Review &amp; Submit';
            reviewBtn.className = 'rounded-lg bg-green-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-green-600';
        }
    }

    // ---------- Add / remove item ----------
    function wireItem(item) {
        item.querySelectorAll('.weight, .purity').forEach(inp => {
            inp.addEventListener('input', function () { calculateItem(this); });
        });
        const display = item.querySelector('.price-display');
        display.addEventListener('input', onPriceInput);
        // Tidy the number when leaving the field (e.g. "1,234." -> "1,234")
        display.addEventListener('blur', function () {
            setPrice(item, item.querySelector('.sale_price').value.replace(/\.$/, ''));
        });
        item.querySelector('.remove-item').addEventListener('click', function () {
            removeItem(this);
        });
    }

    function refreshRemoveButtons() {
        const items = document.querySelectorAll('.sale-item');
        items.forEach(it => {
            it.querySelector('.remove-item').style.display = items.length > 1 ? 'block' : 'none';
        });
    }

    function addItem() {
        const container = document.getElementById('saleItemsContainer');
        const newItem = document.createElement('div');
        newItem.className = 'sale-item rounded-lg border border-gray-200 bg-gray-50 p-4';
        newItem.innerHTML = `
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600">Weight (g)</label>
                    <input type="number"
                           class="weight w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           name="weights[]" placeholder="0.00" step="0.01" min="0.01" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Purity (%)</label>
                    <input type="number"
                           class="purity w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           name="purities[]" value="99.9" step="0.1" min="0.01" max="100" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Sale Price (TZS/g)</label>
                    <input type="text" inputmode="decimal" autocomplete="off"
                           class="price-display w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           placeholder="0">
                    <input type="hidden" class="sale_price" name="prices[]" value="${CURRENT_GOLD_RATE}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Amount</label>
                    <div class="amount-display flex h-10 items-center rounded-lg bg-gray-100 px-3 text-sm font-bold text-green-600">
                        0 TZS
                    </div>
                </div>
            </div>
            <div class="mt-2 flex justify-end">
                <button type="button" class="remove-item text-sm text-red-500 hover:text-red-600">
                    <i class="ik ik-trash-2"></i> Remove
                </button>
            </div>
        `;
        container.appendChild(newItem);
        setPrice(newItem, CURRENT_GOLD_RATE);
        wireItem(newItem);
        refreshRemoveButtons();
        newItem.querySelector('.weight').focus();
    }

    function removeItem(button) {
        const item = button.closest('.sale-item');
        const container = document.getElementById('saleItemsContainer');

        if (container.querySelectorAll('.sale-item').length > 1) {
            item.remove();
            refreshRemoveButtons();
            calculateTotal();
        } else {
            alert('You need at least one gold item');
        }
    }

    // ---------- Markup changes => recalc all ----------
    function recalcAll() {
        document.querySelectorAll('.sale-item').forEach(updateItemAmount);
        calculateTotal();
    }

    // ---------- Last 4 digit adjustment ----------
    // returns true when the adjustment is valid & applied
    function applyAdjustment() {
        const currentTotal = parseFloat(document.getElementById('total_amount_hidden').value) || 0;
        const lastDigits = document.getElementById('last_digits').value.trim();

        if (lastDigits.length !== 4 || !/^\d{4}$/.test(lastDigits)) {
            alert('Please enter exactly 4 digits for the adjustment');
            return false;
        }

        const baseAmount = Math.floor(currentTotal / 10000) * 10000;
        const newTotal = baseAmount + parseInt(lastDigits, 10);

        if (Math.abs(newTotal - currentTotal) < 10000) {
            adjustedTotal = newTotal;
            document.getElementById('total_amount_display').value = newTotal.toLocaleString();
            document.getElementById('total_amount_hidden').value = newTotal;
            return true;
        }

        alert('Adjustment too large. Only last 4 digits can be changed.');
        document.getElementById('last_digits').value = '';
        return false;
    }

    function resetAdjustment() {
        adjustedTotal = null;
        document.getElementById('last_digits').value = '';
        // Recompute from items (also restores the unadjusted total)
        recalcAll();
    }

    // ---------- Validation ----------
    function validateBeforeReview() {
        let hasItems = false;
        let totalWeight = 0;

        document.querySelectorAll('.weight').forEach(inp => {
            const w = parseFloat(inp.value) || 0;
            totalWeight += w;
            if (w > 0) hasItems = true;
        });

        if (!hasItems || totalWeight <= 0) {
            alert('Please add at least one gold item with weight');
            return false;
        }
        if (!document.getElementById('branch_id').value) {
            alert('Please select a branch');
            return false;
        }
        if (!document.getElementById('customer_id').value) {
            alert('Please select a customer');
            document.getElementById('customer_search').focus();
            return false;
        }

        const totalAmount = parseFloat(document.getElementById('total_amount_hidden').value) || 0;
        if (totalAmount <= 0) {
            alert('Please enter valid sale details');
            return false;
        }
        if (totalWeight > BRANCH_GOLD && BRANCH_GOLD > 0) {
            alert('Insufficient gold inventory for this sale');
            return false;
        }
        return true;
    }

    // ---------- Confirmation modal ----------
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function buildConfirmDetails() {
        const branchSel = document.getElementById('branch_id');
        const paymentSel = document.getElementById('payment_method');
        const customerName = document.getElementById('customer_search').value || '—';
        const totalAmount = document.getElementById('total_amount_display').value;
        const totalWeight = document.getElementById('total_weight').value;
        const markup = document.getElementById('markup_rate').value;

        let itemsHtml = '<dt class="font-medium text-gray-600">Items</dt><dd><ul class="mt-1 space-y-1">';
        document.querySelectorAll('.sale-item').forEach((item, idx) => {
            const weight = item.querySelector('.weight').value || 0;
            const purity = item.querySelector('.purity').value || 0;
            const price  = item.querySelector('.sale_price').value || 0;
            const amount = item.querySelector('.amount-display').textContent.trim();
            itemsHtml += `<li class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
                #${idx + 1} — ${weight}g @ ${purity}% purity,
                ${Number(price).toLocaleString()} TZS/g → <strong class="text-green-600">${amount}</strong>
            </li>`;
        });
        itemsHtml += '</ul></dd>';

        let html = `
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Branch</dt>
                <dd class="font-medium text-gray-800">${escapeHtml(branchSel.options[branchSel.selectedIndex]?.text.trim() || '—')}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Customer</dt>
                <dd class="font-medium text-gray-800">${escapeHtml(customerName)}</dd>
            </div>
            <div class="pt-1">${itemsHtml}</div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Markup Rate</dt>
                <dd class="font-medium text-gray-800">${escapeHtml(markup)}%</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Total Weight</dt>
                <dd class="font-medium text-gray-800">${escapeHtml(totalWeight)}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Payment Method</dt>
                <dd class="font-medium text-gray-800">${escapeHtml(paymentSel.options[paymentSel.selectedIndex]?.text || '—')}</dd>
            </div>
            <div class="mt-2 flex justify-between gap-4 border-t border-gray-200 pt-2 text-base">
                <dt class="font-semibold text-gray-700">Total Amount</dt>
                <dd class="font-bold text-green-600">${escapeHtml(totalAmount)} TZS</dd>
            </div>
        `;

        document.getElementById('confirmDetails').innerHTML = html;
    }

    function openConfirmModal() {
        buildConfirmDetails();
        const m = document.getElementById('confirmModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeConfirmModal() {
        const m = document.getElementById('confirmModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // ============================================
    // SEARCHABLE CUSTOMER COMBOBOX
    // ============================================
    function initCustomerCombo() {
        const input  = document.getElementById('customer_search');
        const hidden = document.getElementById('customer_id');
        const list   = document.getElementById('customerList');
        const combo  = document.getElementById('customerCombo');
        const MAX_SHOWN = 60;

        let active = -1;       // highlighted row
        let shown = [];        // currently displayed options
        let committed = null;  // chosen customer (null = none yet)

        const labelOf = c => c.contact ? `${c.name} (${c.contact})` : c.name;

        function commit(c) {
            committed = c;
            hidden.value = c ? c.id : '';
            input.value = c ? labelOf(c) : '';
            close();
        }

        function open()  { list.classList.remove('hidden'); input.setAttribute('aria-expanded', 'true'); }
        function close() { list.classList.add('hidden');    input.setAttribute('aria-expanded', 'false'); active = -1; }

        function highlight(text, q) {
            const safe = escapeHtml(text);
            if (!q) return safe;
            const i = text.toLowerCase().indexOf(q);
            if (i === -1) return safe;
            return escapeHtml(text.slice(0, i)) +
                   '<mark class="bg-yellow-100 text-inherit">' + escapeHtml(text.slice(i, i + q.length)) + '</mark>' +
                   escapeHtml(text.slice(i + q.length));
        }

        function render(query) {
            const q = (query || '').trim().toLowerCase();
            shown = [];

            for (const c of CUSTOMERS) {
                if (!q || c.name.toLowerCase().includes(q) || c.contact.toLowerCase().includes(q)) {
                    shown.push(c);
                    if (shown.length >= MAX_SHOWN) break;
                }
            }

            if (shown.length === 0) {
                list.innerHTML = '<li class="px-3 py-2 text-gray-400">No customer found</li>';
                active = -1;
                return;
            }

            list.innerHTML = shown.map((c, i) => `
                <li role="option" data-index="${i}"
                    class="cursor-pointer px-3 py-2 hover:bg-gray-100 ${i === active ? 'bg-gray-100' : ''}">
                    <span class="font-medium text-gray-800">${highlight(c.name, q)}</span>
                    ${c.contact ? `<span class="ml-1 text-gray-500">(${highlight(c.contact, q)})</span>` : ''}
                </li>`).join('');

            if (shown.length >= MAX_SHOWN) {
                list.innerHTML += '<li class="px-3 py-1 text-xs text-gray-400">Keep typing to narrow results…</li>';
            }
        }

        function move(delta) {
            if (!shown.length) return;
            active = (active + delta + shown.length) % shown.length;
            list.querySelectorAll('[role="option"]').forEach((el, i) => {
                el.classList.toggle('bg-gray-100', i === active);
                if (i === active) el.scrollIntoView({ block: 'nearest' });
            });
        }

        // Restore previously selected customer (old() after validation error)
        const initial = CUSTOMERS.find(c => c.id === parseInt(hidden.value, 10));
        commit(initial || null);

        input.addEventListener('focus', () => { input.select(); render(''); open(); });
        input.addEventListener('click', () => { if (list.classList.contains('hidden')) { render(''); open(); } });
        input.addEventListener('input', () => {
            // typing invalidates the previous choice until a new one is picked
            committed = null;
            hidden.value = '';
            active = 0;
            render(input.value);
            open();
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') { e.preventDefault(); if (list.classList.contains('hidden')) { render(input.value); open(); } move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') {
                if (!list.classList.contains('hidden')) {
                    e.preventDefault();               // don't submit the form
                    if (shown[active]) commit(shown[active]);
                    else if (shown.length === 1) commit(shown[0]);
                }
            }
            else if (e.key === 'Escape') { commit(committed); }
        });

        // mousedown (not click) so it fires before the input's blur
        list.addEventListener('mousedown', e => {
            const li = e.target.closest('[role="option"]');
            if (!li) return;
            e.preventDefault();
            commit(shown[parseInt(li.dataset.index, 10)]);
        });

        // Leaving the box without choosing => revert to last chosen customer (or empty)
        input.addEventListener('blur', () => {
            input.value = committed ? labelOf(committed) : '';
            close();
        });

        document.addEventListener('click', e => { if (!combo.contains(e.target)) close(); });

        // Form reset => no customer selected
        document.getElementById('goldSaleForm').addEventListener('reset', () => {
            setTimeout(() => commit(null), 0);
        });
    }

    // ---------- Init ----------
    document.addEventListener('DOMContentLoaded', function () {
        // Wire the first item + show formatted price
        const firstItem = document.querySelector('.sale-item');
        setPrice(firstItem, firstItem.querySelector('.sale_price').value);
        wireItem(firstItem);
        refreshRemoveButtons();

        initCustomerCombo();

        document.getElementById('addItemBtn').addEventListener('click', e => {
            e.preventDefault();
            addItem();
        });

        document.getElementById('markup_rate').addEventListener('input', recalcAll);

        document.getElementById('applyAdjustment').addEventListener('click', applyAdjustment);
        document.getElementById('resetAdjustment').addEventListener('click', resetAdjustment);

        document.getElementById('reviewBtn').addEventListener('click', () => {
            if (validateBeforeReview()) openConfirmModal();
        });

        document.getElementById('cancelConfirmBtn').addEventListener('click', closeConfirmModal);
        document.getElementById('closeConfirmModal').addEventListener('click', closeConfirmModal);
        document.getElementById('confirmModal').addEventListener('click', function (e) {
            if (e.target === this) closeConfirmModal();
        });

        // Reset button - clear JS state and restore price display + totals
        document.getElementById('goldSaleForm').addEventListener('reset', function () {
            adjustedTotal = null;
            setTimeout(() => {
                document.querySelectorAll('.sale-item').forEach(it => {
                    setPrice(it, it.querySelector('.sale_price').value);
                    updateItemAmount(it);
                });
                calculateTotal();
            }, 0);
        });

        // Confirm & submit
        document.getElementById('confirmSubmitBtn').addEventListener('click', function () {
            if (hasSubmitted) return;

            // If user typed last-4-digits but did not press Apply, apply it now
            const lastDigits = document.getElementById('last_digits').value.trim();
            if (lastDigits.length > 0 && adjustedTotal === null) {
                if (!applyAdjustment()) return;   // invalid → stay on the page
            }

            hasSubmitted = true;

            // Sanitize total before submit
            const hidden = document.getElementById('total_amount_hidden');
            hidden.value = parseFloat(hidden.value.toString().replace(/[^\d.]/g, '')) || 0;

            this.disabled = true;
            this.innerHTML = '<i class="ik ik-loader-2"></i> Submitting...';
            document.getElementById('reviewBtn').disabled = true;
            document.getElementById('cancelConfirmBtn').disabled = true;

            document.getElementById('goldSaleForm').submit();
        });

        // Form submit interception (Enter key)
        document.getElementById('goldSaleForm').addEventListener('submit', function (e) {
            if (hasSubmitted) return;
            e.preventDefault();
            if (validateBeforeReview()) openConfirmModal();
        });

        calculateTotal();

        // Reveal content
        setTimeout(() => {
            document.getElementById('pageSkeleton').style.display = 'none';
            const c = document.getElementById('pageContent');
            c.classList.remove('hidden');
            requestAnimationFrame(() => c.classList.remove('opacity-0'));
        }, 300);
    });

    // Expose globals
    window.calculateItem = calculateItem;
    window.removeItem = removeItem;
</script>
@endsection