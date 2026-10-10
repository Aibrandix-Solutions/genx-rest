@php
    $branding = receipt_footer_branding();
    $withBorder = $withBorder ?? true;
@endphp

@once
    <style>
        .receipt-footer-branding {
            text-align: center;
            width: 100%;
            margin-top: 3mm;
            padding: 0 1mm;
            box-sizing: border-box;
            font-size: 7.5pt;
            line-height: 1.35;
        }

        .receipt-footer-branding--bordered {
            padding-top: 2.5mm;
            border-top: 1px dashed #000;
        }

        .receipt-footer-branding__row {
            display: inline-block;
            white-space: nowrap;
            max-width: 100%;
        }

        .receipt-footer-branding__prefix {
            font-weight: 400;
        }

        .receipt-footer-branding__brand {
            font-weight: 700;
        }

        .receipt-footer-branding__sep {
            font-weight: 400;
            margin: 0 0.8mm;
        }

        .receipt-footer-branding__phone {
            font-weight: 400;
            unicode-bidi: embed;
            direction: ltr;
            display: inline-block;
        }

        .receipt-footer-branding [dir="ltr"] {
            text-align: center;
        }
    </style>
@endonce

@if ($branding)
    @php
        $footerText = $branding['text'];
        $brandName = null;

        if ($footerText !== '' && preg_match('/^Powered by\s+(.+)$/i', $footerText, $matches)) {
            $brandName = trim($matches[1]);
        }

        $hasText = $footerText !== '';
        $hasPhone = $branding['phone'] !== '';
    @endphp

    <div @class([
        'receipt-footer-branding',
        'receipt-footer-branding--bordered' => $withBorder,
    ])>
        <div class="receipt-footer-branding__row">
            @if ($brandName)
                <span class="receipt-footer-branding__prefix">Powered by </span><span class="receipt-footer-branding__brand">{{ $brandName }}</span>
            @elseif ($hasText)
                <span class="receipt-footer-branding__brand">{{ $footerText }}</span>
            @endif

            @if ($hasText && $hasPhone)
                <span class="receipt-footer-branding__sep">|</span>
            @endif

            @if ($hasPhone)
                <span dir="ltr" class="receipt-footer-branding__phone">{{ $branding['phone'] }}</span>
            @endif
        </div>
    </div>
@endif
