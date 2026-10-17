<?php

namespace App\Enums;

enum StatusType: string
{
    case Quotation = 'quotation';
    case Order = 'order';
    case Project = 'project';
    case ProjectDt = 'project_dt';
    case Term = 'term';
    case Invoice = 'invoice';
    case Payment = 'payment';
}
