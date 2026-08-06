@php
    use App\Services\Admin\AdminOrderHelper;
    use App\Services\Admin\AdminOrderInvoiceService;

    $order = $invoice['order'];
    $detail = $invoice['detail'];
    $subOrders = $invoice['sub_orders'];
    $orderType = (int) $invoice['order_type'];
    $itemsTable = $invoice['items_table'];
    $money = fn (float $amount) => AdminOrderInvoiceService::formatInvoiceMoney($amount);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Receipt — {{ $order['order_id_formatted'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 1rem; }
        table { color: #000; font-size: 12px; line-height: 1.4; padding: 0; margin: 0; border-collapse: collapse; }
        .sub-order-details { border-bottom: solid 1px #000; line-height: 1.5; vertical-align: top; }
        .order-items-heading th {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            background-color: #eee;
            font-weight: 700;
        }
        .order-items-heading td { padding: 10px; }
        .items-body td { padding: 10px; vertical-align: top; }
        h3 { margin: 0 0 8px; font-size: 14px; }
        .logo img { max-height: 56px; max-width: 180px; width: auto; height: auto; }
        .receipt-title { padding: 10px; display: block; font-size: 20px; font-weight: 700; }
    </style>
</head>
<body>
<div style="max-width:1000px; margin:1rem auto;">
    <table cellpadding="5" cellspacing="0" align="center" style="border: solid 1px #000; width:100%">
        <tr>
            <td style="border-bottom: solid 1px #000; text-align:center; line-height:1.5;" colspan="2">
                <table cellpadding="5" cellspacing="0" style="width:100%">
                    <tr>
                        <td align="left" class="logo">
                            <img src="{{ $invoice['logo_url'] }}" alt="W3Mentors">
                        </td>
                        <td align="right">
                            <strong class="receipt-title">Order Receipt</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="sub-order-details" width="50%" style="border-right: 1px solid #000;">
                <table style="width: 100%;">
                    <tbody>
                        <tr><td colspan="2"><h3>Bill To</h3></td></tr>
                        <tr>
                            <td style="font-weight: 900; width: 30%;">Name:</td>
                            <td>{{ ucwords($order['learner_full_name']) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Email:</td>
                            <td>{{ $order['learner_email'] ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Order id:</td>
                            <td>{{ $order['order_id_formatted'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Order date:</td>
                            <td style="font-weight: 900;">{{ $order['order_addedon_formatted'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Pay method:</td>
                            <td style="font-weight: 900;">{{ $order['pay_method'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="sub-order-details" width="50%">
                <table style="width: 100%;">
                    <tbody>
                        <tr><td colspan="2"><h3>Order detail</h3></td></tr>
                        <tr>
                            <td style="font-weight: 900; width: 30%;">Type:</td>
                            <td>{{ $order['order_type_label'] }}</td>
                        </tr>
                        @if (!empty($detail['item_name']))
                            <tr>
                                <td style="font-weight: 900;">Name:</td>
                                <td style="font-weight: 900;">{{ $detail['item_name'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['lesson_duration']))
                            <tr>
                                <td style="font-weight: 900;">Duration:</td>
                                <td style="font-weight: 900;">{{ $detail['lesson_duration'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['teacher_name']))
                            <tr>
                                <td style="font-weight: 900;">Teacher:</td>
                                <td style="font-weight: 900;">{{ $detail['teacher_name'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($order['service_type_label']) && in_array($orderType, [
                            AdminOrderHelper::TYPE_LESSON,
                            AdminOrderHelper::TYPE_SUBSCR,
                            AdminOrderHelper::TYPE_GCLASS,
                            AdminOrderHelper::TYPE_PACKGE,
                        ], true))
                            <tr>
                                <td style="font-weight: 900;">Service type:</td>
                                <td style="font-weight: 900;">{{ $order['service_type_label'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['quantity']))
                            <tr>
                                <td style="font-weight: 900;">Quantity:</td>
                                <td style="font-weight: 900;">{{ $detail['quantity'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['teach_language']))
                            <tr>
                                <td style="font-weight: 900;">Language:</td>
                                <td style="font-weight: 900;">{{ $detail['teach_language'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['class_duration']))
                            <tr>
                                <td style="font-weight: 900;">Duration:</td>
                                <td style="font-weight: 900;">{{ $detail['class_duration'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['receiver_name']))
                            <tr>
                                <td style="font-weight: 900;">Recipient name:</td>
                                <td style="font-weight: 900;">{{ $detail['receiver_name'] }}</td>
                            </tr>
                        @endif
                        @if (!empty($detail['receiver_email']))
                            <tr>
                                <td style="font-weight: 900;">Recipient email:</td>
                                <td style="font-weight: 900;">{{ $detail['receiver_email'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="font-weight: 900;">Payment status:</td>
                            <td style="font-weight: 900;">{{ $order['order_payment_status_label'] }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Status:</td>
                            <td style="font-weight: 900;">{{ $order['order_status_label'] }}</td>
                        </tr>
                        @if (!empty($order['order_related_order_id']))
                            <tr>
                                <td style="font-weight: 900;">Related order:</td>
                                <td style="font-weight: 900;">{{ $order['order_related_order_id_formatted'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>

        @if ($invoice['show_items_table'] && !empty($subOrders))
            <tr>
                <td style="line-height:1.5; border-bottom: 1px solid #000;" colspan="2">
                    <table width="100%" border="0" cellpadding="10" cellspacing="0">
                        <thead>
                            @if ($itemsTable['type'] === 'lessons')
                                <tr class="order-items-heading">
                                    <th>{{ $itemsTable['col_id'] }}</th>
                                    <th>{{ $itemsTable['col_start'] }}</th>
                                    <th>{{ $itemsTable['col_end'] }}</th>
                                    <th>{{ $itemsTable['col_status'] }}</th>
                                </tr>
                            @elseif ($itemsTable['type'] === 'classes')
                                <tr class="order-items-heading">
                                    <th>{{ $itemsTable['col_id'] }}</th>
                                    <th>{{ $itemsTable['col_start'] }}</th>
                                    <th>{{ $itemsTable['col_end'] }}</th>
                                    <th>{{ $itemsTable['col_status'] }}</th>
                                </tr>
                            @elseif ($itemsTable['type'] === 'giftcard')
                                <tr class="order-items-heading">
                                    <th>Item</th>
                                    <th>Amount</th>
                                </tr>
                            @elseif ($itemsTable['type'] === 'subplan')
                                <tr class="order-items-heading">
                                    <th>Start time</th>
                                    <th>End time</th>
                                    <th>Validity</th>
                                    <th>Lessons</th>
                                    <th>Lesson duration</th>
                                    <th>Status</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody class="items-body">
                            @if ($itemsTable['type'] === 'giftcard')
                                <tr>
                                    <td>{{ $order['order_type_label'] }}</td>
                                    <td>{{ $money((float) $order['order_total_amount']) }}</td>
                                </tr>
                            @else
                                @foreach ($subOrders as $subOrder)
                                    @if ($itemsTable['type'] === 'lessons')
                                        <tr>
                                            <td>{{ $subOrder['ordles_id'] ?? '' }}</td>
                                            <td>{{ $subOrder['ordles_lesson_starttime_fmt'] ?? '—' }}</td>
                                            <td>{{ $subOrder['ordles_lesson_endtime_fmt'] ?? '—' }}</td>
                                            <td>{{ $subOrder['ordles_status_label'] ?? '—' }}</td>
                                        </tr>
                                    @elseif ($itemsTable['type'] === 'classes')
                                        <tr>
                                            <td>{{ $subOrder['ordcls_id'] ?? '' }}</td>
                                            <td>{{ $subOrder['grpcls_start_datetime_fmt'] ?? '—' }}</td>
                                            <td>{{ $subOrder['grpcls_end_datetime_fmt'] ?? '—' }}</td>
                                            <td>{{ $subOrder['ordcls_status_label'] ?? '—' }}</td>
                                        </tr>
                                    @elseif ($itemsTable['type'] === 'subplan')
                                        <tr>
                                            <td>{{ $detail['subplan_start'] }}</td>
                                            <td>{{ $detail['subplan_end'] }}</td>
                                            <td>{{ $detail['subplan_validity'] }}</td>
                                            <td>{{ $detail['subplan_lessons'] }}</td>
                                            <td>{{ $detail['subplan_duration'] }}</td>
                                            <td>{{ $detail['subplan_status'] }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        @endif

        <tr>
            <td></td>
            <td>
                <table style="width: 100%; padding-top: 10px; padding-bottom: 10px; text-align: right;">
                    <tbody>
                        <tr>
                            <td style="font-weight: 900;">Total Order Amount:</td>
                            <td>{{ $money((float) $order['order_total_amount']) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Order discount:</td>
                            <td>{{ $money((float) $order['order_discount_value']) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Order Reward:</td>
                            <td>{{ $money((float) $order['order_reward_value']) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">Net amount:</td>
                            <td>{{ $money((float) $order['order_net_amount']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
