<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceSettingController extends Controller
{
    public function edit()
    {
        return view('settings.invoice', [
            'settings' => Setting::invoice(),
            'themes' => $this->themes(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'theme' => ['required', Rule::in(array_keys($this->themes()))],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'heading_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'use_branch_logo' => ['nullable', 'boolean'],
            'show_logo' => ['nullable', 'boolean'],
            'show_qr' => ['nullable', 'boolean'],
            'show_watermark' => ['nullable', 'boolean'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'created_label' => ['nullable', 'string', 'max:80'],
            'returned_label' => ['nullable', 'string', 'max:80'],
        ]);

        $current = Setting::invoice();
        $logoPath = $current['invoice_logo_path'];

        if ($request->boolean('remove_logo')) {
            Setting::forgetInvoiceLogo($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            Setting::forgetInvoiceLogo($logoPath);
            $logoPath = $request->file('logo')->store('invoice/logo', 'public');
        }

        Setting::putMany([
            'invoice_company_name' => $data['company_name'] ?: null,
            'invoice_tagline' => $data['tagline'] ?: 'Premium Suit Rental',
            'invoice_theme' => $data['theme'],
            'invoice_primary_color' => $data['primary_color'],
            'invoice_heading_color' => $data['heading_color'],
            'invoice_text_color' => $data['text_color'],
            'invoice_muted_color' => $data['muted_color'],
            'invoice_logo_path' => $logoPath,
            'invoice_use_branch_logo' => $request->boolean('use_branch_logo'),
            'invoice_show_logo' => $request->boolean('show_logo'),
            'invoice_show_qr' => $request->boolean('show_qr'),
            'invoice_show_watermark' => $request->boolean('show_watermark'),
            'invoice_terms' => $data['terms'] ?: Setting::INVOICE_DEFAULTS['invoice_terms'],
            'invoice_footer_text' => $data['footer_text'] ?: null,
            'invoice_created_label' => $data['created_label'] ?: 'Customer Service 1',
            'invoice_returned_label' => $data['returned_label'] ?: 'Customer Service 2',
        ]);

        return back()->with('success', 'Pengaturan invoice berhasil disimpan.');
    }

    public function reset()
    {
        $current = Setting::invoice();
        Setting::forgetInvoiceLogo($current['invoice_logo_path']);

        Setting::putMany(Setting::INVOICE_DEFAULTS);

        return back()->with('success', 'Pengaturan invoice dikembalikan ke default.');
    }

    private function themes(): array
    {
        return [
            'gold' => [
                'name' => 'Classic Gold',
                'primary' => '#D6B98C',
                'heading' => '#2B2520',
                'text' => '#2B2B2B',
                'muted' => '#6B6B6B',
            ],
            'sapphire' => [
                'name' => 'Sapphire',
                'primary' => '#2563EB',
                'heading' => '#172554',
                'text' => '#1E293B',
                'muted' => '#64748B',
            ],
            'emerald' => [
                'name' => 'Emerald',
                'primary' => '#059669',
                'heading' => '#064E3B',
                'text' => '#1F2937',
                'muted' => '#6B7280',
            ],
            'rose' => [
                'name' => 'Rose',
                'primary' => '#E11D48',
                'heading' => '#3F1D2B',
                'text' => '#2B2B2B',
                'muted' => '#6B6B6B',
            ],
        ];
    }
}
