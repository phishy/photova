@extends('dashboard.layout')

@section('title', 'Pricing')

@section('content')
<div x-data="pricingPage()" x-init="init()">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Provider Pricing</h1>
        <button
            @click="openModal()"
            class="px-4 py-2 bg-[#238636] hover:bg-[#2ea043] rounded-md text-white text-sm font-medium transition-colors"
        >
            Add Pricing
        </button>
    </div>

    <template x-if="!isSuperAdmin">
        <div class="bg-[#f85149]/10 border border-[#f85149]/40 rounded-lg p-6 text-center">
            <p class="text-[#f85149]">Access denied. Superadmin role required.</p>
        </div>
    </template>

    <template x-if="isSuperAdmin">
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5">
                    <div class="text-xs text-[#8b949e] mb-1">Total Entries</div>
                    <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="pricing.length"></div>
                </div>
                <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5">
                    <div class="text-xs text-[#8b949e] mb-1">Avg Margin</div>
                    <div class="text-2xl font-semibold text-[#3fb950]" x-text="avgMargin + '%'"></div>
                </div>
                <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5">
                    <div class="text-xs text-[#8b949e] mb-1">Active Entries</div>
                    <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="pricing.filter(p => p.is_active).length"></div>
                </div>
            </div>

            <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#21262d] bg-[#0d1117]/50">
                                <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Provider</th>
                                <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Operation</th>
                                <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Model</th>
                                <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Unit</th>
                                <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Cost</th>
                                <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Price</th>
                                <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Margin</th>
                                <th class="text-center p-3 text-xs text-[#8b949e] font-medium">Active</th>
                                <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in pricing" :key="item.id">
                                <tr class="border-b border-[#21262d] hover:bg-[#21262d]/30 transition-colors">
                                    <td class="p-3 text-[13px] text-[#c9d1d9]" x-text="item.provider"></td>
                                    <td class="p-3 text-[13px] text-[#c9d1d9]" x-text="item.operation"></td>
                                    <td class="p-3 text-[13px] text-[#8b949e] max-w-[200px] truncate" :title="item.model" x-text="item.model || '—'"></td>
                                    <td class="p-3 text-[13px] text-[#8b949e]" x-text="formatUnit(item.unit_type)"></td>
                                    <td class="p-3 text-[13px] text-[#c9d1d9] text-right font-mono" x-text="formatDollars(item.cost_per_unit)"></td>
                                    <td class="p-3 text-[13px] text-[#c9d1d9] text-right font-mono" x-text="formatDollars(item.price_per_unit)"></td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="item.margin_percent >= 30 ? 'bg-[#3fb950]/20 text-[#3fb950]' : item.margin_percent >= 15 ? 'bg-[#d29922]/20 text-[#d29922]' : 'bg-[#f85149]/20 text-[#f85149]'"
                                            x-text="item.margin_percent + '%'"
                                        ></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span 
                                            class="inline-block w-2 h-2 rounded-full"
                                            :class="item.is_active ? 'bg-[#3fb950]' : 'bg-[#8b949e]'"
                                        ></span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button @click="openModal(item)" class="text-[#58a6ff] hover:text-[#79c0ff] text-xs mr-2">Edit</button>
                                        <button @click="deletePricing(item.id)" class="text-[#f85149] hover:text-[#ff7b72] text-xs">Delete</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <template x-if="pricing.length === 0">
                    <div class="p-12 text-center text-[#8b949e] text-sm">No pricing configured yet.</div>
                </template>
            </div>
        </div>
    </template>

    <div 
        x-show="showModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
        @click.self="closeModal()"
    >
        <div class="bg-[#161b22] border border-[#30363d] rounded-xl w-full max-w-lg mx-4 p-6">
            <h2 class="text-lg font-semibold text-[#c9d1d9] mb-4" x-text="editingId ? 'Edit Pricing' : 'Add Pricing'"></h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-[#8b949e] mb-1">Provider</label>
                        <input 
                            type="text" 
                            x-model="form.provider" 
                            placeholder="replicate"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-[#8b949e] mb-1">Operation</label>
                        <select 
                            x-model="form.operation"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                        >
                            <option value="">Select...</option>
                            <option value="background-remove">background-remove</option>
                            <option value="upscale">upscale</option>
                            <option value="unblur">unblur</option>
                            <option value="colorize">colorize</option>
                            <option value="inpaint">inpaint</option>
                            <option value="restore">restore</option>
                            <option value="analyze">analyze</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-[#8b949e] mb-1">Model (optional)</label>
                    <input 
                        type="text" 
                        x-model="form.model" 
                        placeholder="cjwbw/rembg:fb8af171..."
                        class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                    >
                </div>

                <div>
                    <label class="block text-xs text-[#8b949e] mb-1">Unit Type</label>
                    <select 
                        x-model="form.unit_type"
                        class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                    >
                        <option value="per_image">Per Image</option>
                        <option value="per_second">Per Second</option>
                        <option value="per_megapixel">Per Megapixel</option>
                        <option value="per_token">Per Token</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-[#8b949e] mb-1">Cost ($)</label>
                        <input 
                            type="number" 
                            x-model.number="form.cost_per_unit" 
                            min="0"
                            step="0.000001"
                            placeholder="0.00051"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-[#8b949e] mb-1">Price ($)</label>
                        <input 
                            type="number" 
                            x-model.number="form.price_per_unit" 
                            min="0"
                            step="0.000001"
                            placeholder="0.001"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff]"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.is_active" id="is_active" class="rounded">
                    <label for="is_active" class="text-sm text-[#c9d1d9]">Active</label>
                </div>

                <div>
                    <label class="block text-xs text-[#8b949e] mb-1">Notes (optional)</label>
                    <textarea 
                        x-model="form.notes" 
                        rows="2"
                        placeholder="Source: Replicate docs 2025-01"
                        class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] resize-none"
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button 
                    @click="closeModal()"
                    class="px-4 py-2 bg-transparent border border-[#30363d] rounded-md text-[#c9d1d9] text-sm hover:border-[#8b949e] transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="savePricing()"
                    class="px-4 py-2 bg-[#238636] hover:bg-[#2ea043] rounded-md text-white text-sm font-medium transition-colors"
                >
                    <span x-text="editingId ? 'Update' : 'Create'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function pricingPage() {
        return {
            pricing: [],
            isSuperAdmin: false,
            showModal: false,
            editingId: null,
            form: {
                provider: '',
                operation: '',
                model: '',
                unit_type: 'per_image',
                cost_per_unit: 0,
                price_per_unit: 0,
                is_active: true,
                notes: ''
            },

            get avgMargin() {
                if (this.pricing.length === 0) return 0;
                const sum = this.pricing.reduce((acc, p) => acc + (p.margin_percent || 0), 0);
                return Math.round(sum / this.pricing.length);
            },

            async init() {
                try {
                    const res = await window.apiFetch('/api/auth/me');
                    if (res.ok) {
                        const data = await res.json();
                        this.isSuperAdmin = data.user?.role === 'superadmin';
                        if (this.isSuperAdmin) {
                            await this.loadPricing();
                        }
                    }
                } catch (e) {
                    console.error('Auth check failed:', e);
                }
            },

            async loadPricing() {
                try {
                    const res = await window.apiFetch('/api/admin/pricing');
                    if (res.ok) {
                        const data = await res.json();
                        this.pricing = data.pricing || [];
                    }
                } catch (e) {
                    console.error('Failed to load pricing:', e);
                }
            },

            openModal(item = null) {
                if (item) {
                    this.editingId = item.id;
                    this.form = {
                        provider: item.provider,
                        operation: item.operation,
                        model: item.model || '',
                        unit_type: item.unit_type,
                        cost_per_unit: item.cost_per_unit,
                        price_per_unit: item.price_per_unit,
                        is_active: item.is_active,
                        notes: item.notes || ''
                    };
                } else {
                    this.editingId = null;
                    this.form = {
                        provider: '',
                        operation: '',
                        model: '',
                        unit_type: 'per_image',
                        cost_per_unit: 0,
                        price_per_unit: 0,
                        is_active: true,
                        notes: ''
                    };
                }
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
                this.editingId = null;
            },

            async savePricing() {
                try {
                    const url = this.editingId 
                        ? `/api/admin/pricing/${this.editingId}`
                        : '/api/admin/pricing';
                    const method = this.editingId ? 'PATCH' : 'POST';

                    const body = { ...this.form };
                    if (!body.model) delete body.model;
                    if (!body.notes) delete body.notes;

                    const res = await window.apiFetch(url, {
                        method,
                        body: JSON.stringify(body)
                    });

                    if (res.ok) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: this.editingId ? 'Pricing updated' : 'Pricing created', type: 'success' }
                        }));
                        this.closeModal();
                        await this.loadPricing();
                    } else {
                        const data = await res.json();
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: data.message || 'Failed to save', type: 'error' }
                        }));
                    }
                } catch (e) {
                    console.error('Failed to save pricing:', e);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Failed to save pricing', type: 'error' }
                    }));
                }
            },

            async deletePricing(id) {
                if (!confirm('Delete this pricing entry?')) return;

                try {
                    const res = await window.apiFetch(`/api/admin/pricing/${id}`, {
                        method: 'DELETE'
                    });

                    if (res.ok || res.status === 204) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Pricing deleted', type: 'success' }
                        }));
                        await this.loadPricing();
                    }
                } catch (e) {
                    console.error('Failed to delete pricing:', e);
                }
            },

            formatDollars(dollars) {
                if (dollars < 0.01) {
                    return '$' + dollars.toFixed(6);
                }
                return '$' + dollars.toFixed(4);
            },

            formatUnit(unit) {
                return unit.replace('per_', '/').replace('_', ' ');
            }
        }
    }
</script>
@endsection
