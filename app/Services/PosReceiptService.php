<?php

namespace App\Services;

use App\Models\Pos\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PosReceiptService
{
    public function pdf(Order $order)
    {
        $order->loadMissing(['items', 'student.classroom', 'parent', 'user']);

        return Pdf::loadView('pos.receipts.order', [
            'order' => $order,
            'schoolName' => config('app.name', 'School'),
            'pay' => app(PosService::class)->paymentInstructions(),
        ])->setPaper('a4');
    }

    public function stream(Order $order): Response
    {
        $filename = 'POS-Receipt-'.$order->order_number.'.pdf';

        return $this->pdf($order)->stream($filename);
    }

    public function download(Order $order): Response
    {
        $filename = 'POS-Receipt-'.$order->order_number.'.pdf';

        return $this->pdf($order)->download($filename);
    }
}
