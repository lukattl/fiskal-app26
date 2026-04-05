<?php namespace Fiskalizacija\Bill;

require_once __DIR__ . "/Request.php";

use Fiskalizacija\Request;

class BillRequest extends Request { 
    public function __construct(Bill $bill) {
        $this->request = $bill;
        $this->requestName = 'RacunZahtjev';
    }
}
