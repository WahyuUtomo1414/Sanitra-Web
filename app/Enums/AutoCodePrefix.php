<?php

namespace App\Enums;

enum AutoCodePrefix: string
{
    // Master (format: PREFIX + 4 digit, no reset)
    case MsCompany = 'COM';
    case MsEmployee = 'EMP';
    case MsClient = 'CLI';
    case MsProjectType = 'PTY';
    case MsItemCategory = 'IC';
    case MsItem = 'ITM';
    case MsBankAccount = 'BNK';

    // Transaksi (format: PREFIX/YY/MM/4 digit, reset per bulan)
    case TransSalesQuotationHd = 'QUO';
    case TransSalesOrderHd = 'SO';
    case TransProjectHd = 'PRO';
    case TransSalesInvoiceHd = 'INV';
    case TransSalesPayment = 'PAY';

    public function format(): CodeFormat
    {
        return match (true) {
            str_starts_with($this->name, 'Trans') => CodeFormat::Transaction,
            default => CodeFormat::Master,
        };
    }
}
