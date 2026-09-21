<?php

namespace App\Enums;

/**
 * Status tagihan tidak disimpan di database — dihitung dari akumulasi
 * pembayaran yang sudah disetujui admin terhadap nominal tagihan.
 */
enum SppBillStatus: string
{
    case Pending = 'pending';
    case Partial = 'cicil';
    case Paid = 'lunas';
}
