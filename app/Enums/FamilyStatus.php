<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case BiologicalChild = 'anak_kandung';
    case StepChild = 'anak_tiri';
    case AdoptedChild = 'anak_angkat';
}
