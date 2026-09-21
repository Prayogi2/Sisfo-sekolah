<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'disetujui';
    case Rejected = 'ditolak';
}
