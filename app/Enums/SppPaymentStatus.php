<?php

namespace App\Enums;

enum SppPaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'disetujui';
    case Rejected = 'ditolak';
}
