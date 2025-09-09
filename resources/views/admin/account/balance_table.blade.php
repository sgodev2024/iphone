 @php
     $i = 1;
     $total_opening_debit = 0;
     $total_opening_credit = 0;
     $total_period_debit = 0;
     $total_period_credit = 0;
     $total_closing_debit = 0;
     $total_closing_credit = 0;
 @endphp

 {{-- Tính tổng trước --}}
 @foreach ($accounts as $acc)
     @php
         $total_opening_debit += $acc->opening_debit;
         $total_opening_credit += $acc->opening_credit;
         $total_period_debit += $acc->period_debit;
         $total_period_credit += $acc->period_credit;
         $total_closing_debit += $acc->closing_balance_debit;
         $total_closing_credit += $acc->closing_balance_credit;
     @endphp
 @endforeach

 {{-- Hiển thị tổng lên đầu --}}
 <tr class="total-row">
     <td></td>
     <td></td>
     <td class="text-end"><strong>Tổng</strong></td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_opening_debit != 0 ? formatPrice($total_opening_debit) : '' }}</strong>
     </td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_opening_credit != 0 ? formatPrice($total_opening_credit) : '' }}</strong>
     </td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_period_debit != 0 ? formatPrice($total_period_debit) : '' }}</strong>
     </td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_period_credit != 0 ? formatPrice($total_period_credit) : '' }}</strong>
     </td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_closing_debit != 0 ? formatPrice($total_closing_debit) : '' }}</strong>
     </td>
     <td class="text-right-custom number-format">
         <strong>{{ $total_closing_credit != 0 ? formatPrice($total_closing_credit) : '' }}</strong>
     </td>
 </tr>

 {{-- Sau đó mới bắt đầu hiển thị từng dòng --}}
 @php $i = 1; @endphp
 @foreach ($accounts as $acc)
     <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
         <td>{{ $i++ }}</td>
         <td>
             {!! str_repeat('&nbsp;&nbsp;', $acc->level - 1) !!}
             <a href="#" class="code-column">{{ $acc->account_code }}</a>
         </td>
         <td class="name-column">
             {!! str_repeat('&nbsp;&nbsp;', $acc->level - 1) !!}
             {{ $acc->account_name }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->opening_debit != 0 ? formatPrice($acc->opening_debit) : '' }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->opening_credit != 0 ? formatPrice($acc->opening_credit) : '' }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->period_debit != 0 ? formatPrice($acc->period_debit) : '' }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->period_credit != 0 ? formatPrice($acc->period_credit) : '' }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->closing_balance_debit != 0 ? formatPrice($acc->closing_balance_debit) : '' }}
         </td>
         <td class="text-right-custom number-format">
             {{ $acc->closing_balance_credit != 0 ? formatPrice($acc->closing_balance_credit) : '' }}
         </td>
     </tr>
 @endforeach    
