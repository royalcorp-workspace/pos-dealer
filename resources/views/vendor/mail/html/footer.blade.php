@php
    $officialBrands = ['LADY AMERICANA', 'ELITE', 'ROYAL FOAM', 'SERENITY', 'MORO', 'TOTE'];
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; width: 570px;">
<tr>
<td class="content-cell" align="center" style="padding: 24px 20px 32px; text-align: center;">

    <!-- Official Brand Partners Section -->
    <div style="margin-bottom: 24px; padding: 18px 12px; background-color: #fdfbf7; border: 1px solid #f2ebd9; border-radius: 8px;">
        <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; color: #ad8a58; letter-spacing: 1.5px; text-transform: uppercase; margin: 0 0 10px; text-align: center;">
            ★ Official Brand Partners ★
        </p>
        <table cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto;">
            <tr>
                @foreach($officialBrands as $bName)
                    <td style="padding: 4px 8px; text-align: center;">
                        <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 10px; font-weight: 700; color: #2b1d12; letter-spacing: 0.5px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 3px 7px; display: inline-block;">
                            {{ $bName }}
                        </span>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <!-- Corporate Footer Links & Copyright -->
    <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; line-height: 1.6; color: #71717a; margin: 0 0 8px; text-align: center;">
        <strong>IMG (International Mattress Gallery)</strong><br>
        Destinasi kasur dan perlengkapan tidur premium nomor satu di Indonesia.
    </p>

    <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; color: #a1a1aa; margin: 0; text-align: center;">
        &copy; {{ date('Y') }} IMG Mattress Gallery. Seluruh hak cipta dilindungi undang-undang.
    </p>
</td>
</tr>
</table>
</td>
</tr>
