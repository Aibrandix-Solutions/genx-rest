@php
    $branding = receipt_footer_branding();
    $withBorder = $withBorder ?? true;
@endphp

@once
    <style>
        .receipt-footer-branding {
            text-align: center;
            width: 100%;
            margin-top: 4mm;
            padding: 0 1mm 1mm;
            box-sizing: border-box;
        }

        .receipt-footer-branding--bordered {
            padding-top: 3mm;
            border-top: 1px dashed #000;
        }

        .receipt-footer-branding__ornament {
            font-size: 6pt;
            letter-spacing: 0.4em;
            line-height: 1;
            margin-bottom: 2.5mm;
            opacity: 0.7;
        }

        .receipt-footer-branding__text {
            font-size: 7.5pt;
            font-weight: 600;
            line-height: 1.5;
            letter-spacing: 0.02em;
            margin: 0 auto;
            max-width: 100%;
        }

        .receipt-footer-branding__text-prefix {
            font-weight: 500;
            font-size: 7pt;
        }

        .receipt-footer-branding__phone {
            margin-top: 2.5mm;
            text-align: center;
            width: 100%;
        }

        .receipt-footer-branding__phone-badge {
            display: inline-block;
            border: 1px solid #000;
            border-radius: 2mm;
            padding: 1mm 3mm;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.05em;
            line-height: 1.3;
            text-align: center;
        }

        .receipt-footer-branding__phone-icon {
            display: inline-block;
            margin-right: 1.5mm;
            font-size: 7pt;
            font-weight: 400;
            vertical-align: middle;
        }

        .receipt-footer-branding__phone-number {
            unicode-bidi: embed;
            direction: ltr;
            display: inline-block;
            vertical-align: middle;
        }

        .receipt-footer-branding [dir="ltr"] {
            text-align: center;
        }
    </style>
@endonce

@if ($branding)
    <div @class([
        'receipt-footer-branding',
        'receipt-footer-branding--bordered' => $withBorder,
    ])>
        <div class="receipt-footer-branding__ornament" aria-hidden="true">— ✦ —</div>

        @if ($branding['text'] !== '')
            @php
                $footerText = $branding['text'];
                $prefix = null;
                $suffix = $footerText;

                if (preg_match('/^(Powered by\s+)(.+)$/i', $footerText, $matches)) {
                    $prefix = $matches[1];
                    $suffix = $matches[2];
                }
            @endphp

            <div class="receipt-footer-branding__text">
                @if ($prefix)
                    <span class="receipt-footer-branding__text-prefix">{{ $prefix }}</span>{{ $suffix }}
                @else
                    {{ $footerText }}
                @endif
            </div>
        @endif

        @if ($branding['phone'] !== '')
            <div class="receipt-footer-branding__phone">
                <span class="receipt-footer-branding__phone-badge">
                    <span class="receipt-footer-branding__phone-icon" aria-hidden="true">☎</span>
                    <span dir="ltr" class="receipt-footer-branding__phone-number">{{ $branding['phone'] }}</span>
                </span>
            </div>
        @endif
    </div>
@endif
