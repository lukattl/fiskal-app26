<?php namespace Fiskalizacija\Bill;

use XMLWriter;

class Bill {
    public $oib;
    public $havePDV;
    public $dateTime;
    public $noteOfOrder = "N";
    public $billNumber;
    public $listPDV;
    public $listPNP;
    public $listOtherTaxRate;
    public $taxFreeValuePdv;
    public $marginForTaxRate;    
    public $taxFreeValue;
    public $refund = array();
    public $totalValue;
    public $typeOfPaying;
    public $oibOperative;
    public $securityCode;
    public $noteOfRedelivary = false;    
    public $noteOfParagonBill;    
    public $specificPurpose;
    public $oibPrimateljaRacuna;
    public $idKupca;
    public $oznakaIdKupca;
    public $operationType = 'create'; // default
    public $newTypeOfPaying;
    public $newKupac;

    public function setOib($oib) {
        $this->oib = $oib;
    }

    public function setHavePDV($havePDV) {
        $this->havePDV = $havePDV;
    }

    public function setDateTime($dateTime) {
        $this->dateTime = $dateTime;
    }

    public function setNoteOfOrder($noteOfOrder) {
        $this->noteOfOrder = $noteOfOrder;
    }

    public function setBillNumber($billNumber) {
        $this->billNumber = $billNumber;
    }

    public function setListPDV($listPDV) {
        $this->listPDV = $listPDV;
    }

    public function setListPNP($listPNP) {
        $this->listPNP = $listPNP;
    }

    public function setListOtherTaxRate($listOtherTaxRate) {
        $this->listOtherTaxRate = $listOtherTaxRate;
    }

    public function setTaxFreeValue($taxFreeValuePdv) {
        $this->taxFreeValuePdv = $taxFreeValuePdv;
    }

    public function setMarginForTaxRate($marginForTaxRate) {
        $this->marginForTaxRate = $marginForTaxRate;
    }

    public function setTaxFree($taxFreeValue) {
        $this->taxFreeValue = $taxFreeValue;
    }

    public function setRefund($refund) {
        $this->refund = $refund;
    }

    public function setTotalValue($totalValue) {
        $this->totalValue = $totalValue;
    }

    public function setTypeOfPlacanje($typeOfPaying) {
        $this->typeOfPaying = $typeOfPaying;
    }

    public function setOibOperative($oibOperative) {
        $this->oibOperative = $oibOperative;
    }

    public function setSecurityCode($securityCode) {
        $this->securityCode = $securityCode;
    }

    public function setNoteOfRedelivary($noteOfRedelivary) {
        $this->noteOfRedelivary = $noteOfRedelivary;
    }

    public function setNoteOfParagonBill($noteOfParagonBill) {
        $this->noteOfParagonBill = $noteOfParagonBill;
    }

    public function setSpecificPurpose($specificPurpose) {
        $this->specificPurpose = $specificPurpose;
    }

    public function setOibPrimateljaRacuna($oibPrimateljaRacuna) {
        $this->oibPrimateljaRacuna = $oibPrimateljaRacuna;
    }

    public function setIdKupca($idKupca) {
        $this->idKupca = trim($idKupca);
    }

    public function setOznakaIdKupca($oznakaIdKupca) {
        $this->oznakaIdKupca = $oznakaIdKupca;
    }

    public function setOperationType($type) {
        $this->operationType = $type;
    }

    public function setNewTypeOfPaying($newTypeOfPaying) {
        $this->newTypeOfPaying = $newTypeOfPaying;
    }

    public function setNewKupac($newKupac) {
        $this->newKupac = $newKupac;
    }
    
    /**
     * Generiranje za�titnog koda na temelju ulaznih parametara
     * @param  [type] $pkey privatni kljuc iz certifikata
     * @param  [type] $oib  oib
     * @param  [type] $dt   datum i vrijeme izdavanja ra�una zapisan kao tekst u formatu 'dd.mm.gggg hh:mm:ss'
     * @param  [type] $bor  broj�ana oznaka ra�una
     * @param  [type] $opp  oznaka poslovnog prostora
     * @param  [type] $onu  oznaka naplatnog ure�aja
     * @param  [type] $uir  ukupni iznos ra�una
     * @return [type]       md5 hash
     */
    public function securityCode($pkey, $oib, $dt, $bor, $opp, $onu, $uir) {
        $dt = str_replace(' ', 'T', $dt);
        $dt = date('d.m.Y H:i:s', strtotime($dt));
        $medjurezultat = '';
        $medjurezultat .= $oib;
        $medjurezultat .= $dt;
        $medjurezultat .= $bor;
        $medjurezultat .= $opp;
        $medjurezultat .= $onu;
        $medjurezultat .= $uir;
        $ZastKodSignature = null;
        if (!openssl_sign($medjurezultat, $ZastKodSignature, $pkey, OPENSSL_ALGO_SHA1)) {
            throw new \RuntimeException('Ne mogu generirati ZKI iz dostavljenog privatnog kljuca.');
        }
        return $this->securityCode = md5($ZastKodSignature);
    }

    public function toXML() {
        $ns = 'tns';
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString("    ");

        switch ($this->operationType) {
            case 'create':
                $writer->startElementNs($ns, 'Racun', null);
                $writer->writeElementNs($ns, 'Oib', null, $this->oib);
                $writer->writeElementNs($ns, 'USustPdv', null, $this->havePDV ? "true" : "false");
                $writer->writeElementNs($ns, 'DatVrijeme', null, $this->dateTime);
                $writer->writeElementNs($ns, 'OznSlijed', null, $this->noteOfOrder);

                $writer->writeRaw($this->billNumber->toXML());

                /*********** PDV *****************************/
                if (!empty($this->listPDV)) {
                    $writer->startElementNs($ns, 'Pdv', null);
                        foreach ($this->listPDV as $pdv) {
                            $writer->writeRaw($pdv->toXML());
                        }
                    $writer->endElement();
                }
                /*********************************************/

                /*********** PNP *****************************/
                if (!empty($this->listPNP)) {
                    $writer->startElementNs($ns, 'Pnp', null);
                    foreach ($this->listPNP as $pnp) {
                        $writer->writeRaw($pnp->toXML());
                    }
                    $writer->endElement();
                }
                /*********************************************/

                /*********** Ostali Porez ********************/
                if (!empty($this->listOtherTaxRate)) {
                    $writer->startElementNs($ns, 'OstaliPor', null);
                        foreach ($this->listOtherTaxRate as $ostali) {
                            $writer->writeRaw($ostali->toXML());
                        }
                    $writer->endElement();
                }
                /*********************************************/

                if (!empty($this->taxFreeValuePdv)) {
                    $writer->writeElementNs($ns, 'IznosOslobPdv', null, number_format($this->taxFreeValuePdv, 2, '.', ''));
                }

                if (!empty($this->marginForTaxRate)) {
                    $writer->writeElementNs($ns, 'IznosMarza', null, number_format($this->marginForTaxRate, 2, '.', ''));
                }

                if (!empty($this->taxFreeValue)) {
                    $writer->writeElementNs($ns, 'IznosNePodlOpor', null, number_format($this->taxFreeValue, 2, '.', ''));
                }

        //        $writer->writeElementNs($ns, 'IznosOslobPdv', null, number_format($this->taxFreeValuePdv, 2, '.', ''));
        //        $writer->writeElementNs($ns, 'IznosMarza', null, number_format($this->marginForTaxRate, 2, '.', ''));
        //        $writer->writeElementNs($ns, 'IznosNePodlOpor', null, number_format($this->taxFreeValue, 2, '.', ''));

                /*********** Naknada *************************/
                if (!empty($this->refund)) {
                    $writer->startElementNs($ns, 'Naknade', null);
                        foreach ($this->refund as $naknada) {
                            $writer->writeRaw($naknada->toXML());
                        }
                    $writer->endElement();
                }
                /*********************************************/

                $writer->writeElementNs($ns, 'IznosUkupno', null, number_format($this->totalValue, 2, '.', ''));
                $writer->writeElementNs($ns, 'NacinPlac', null, $this->typeOfPaying);
                $writer->writeElementNs($ns, 'OibOper', null, $this->oibOperative);
                $writer->writeElementNs($ns, 'ZastKod', null, $this->securityCode);

                $writer->writeElementNs($ns, 'NakDost', null, $this->noteOfRedelivary ? "true" : "false");

                /* IDENTIFIKACIJA KUPCA (2026) */

                $dt = strtotime($this->dateTime);
                $target = strtotime('2026-01-01 00:00:00');

                if ($dt >= $target && $this->typeOfPaying != 'T') {

                    if (!empty($this->idKupca)) {

                        $oznaka = $this->oznakaIdKupca
                            ?: $this->detectOznakaIdKupca($this->idKupca);

                        if ($oznaka === 'OIB') {

                            $writer->writeElementNs(
                                $ns,
                                'OibPrimateljaRacuna',
                                null,
                                $this->idKupca
                            );

                        } /*else {

                            $writer->writeElementNs(
                                $ns,
                                'IDKupca',
                                null,
                                $this->idKupca
                            );

                            $writer->writeElementNs(
                                $ns,
                                'OznakaIDKupca',
                                null,
                                $oznaka
                            );
                        }*/
                    }
                }

                /* KRAJ IDENTIFIKACIJE */

                if($this->noteOfParagonBill)
                    $writer->writeElementNs($ns, 'ParagonBrRac', null, $this->noteOfParagonBill);
                if($this->specificPurpose)
                $writer->writeElementNs($ns, 'SpecNamj', null, $this->specificPurpose);

                $writer->endElement();
                break;
            case 'changePayment':
                    $writer->startElement('tns:Racun');
                    $writer->writeElement('tns:Oib', $this->oib);
                    $writer->writeElement('tns:USustPdv', $this->havePDV ? 'true' : 'false');
                    $writer->writeElement('tns:DatVrijeme', $this->dateTime);
                    $writer->writeElement('tns:OznSlijed', $this->noteOfOrder);
                    $writer->writeRaw($this->billNumber->toXML());
                    $writer->writeElement('tns:IznosUkupno', number_format($this->totalValue, 2, '.', ''));
                    $writer->writeElement('tns:NacinPlac', $this->typeOfPaying); // stari način plaćanja
                    $writer->writeElement('tns:OibOper', $this->oibOperative);
                    $writer->writeElement('tns:ZastKod', $this->securityCode);
                    $writer->writeElement('tns:NakDost', $this->noteOfRedelivary ? 'true' : 'false');
                    $writer->writeElement('tns:PromijenjeniNacinPlac', $this->newTypeOfPaying); // novi
                    $writer->endElement(); // Racun
                    break;

                case 'changeCustomer':
                    $writer->startElement('tns:Racun');
                    $writer->writeElement('tns:Oib', $this->oib);
                    $writer->writeElement('tns:USustPdv', $this->havePDV ? 'true' : 'false');
                    $writer->writeElement('tns:DatVrijeme', $this->dateTime);
                    $writer->writeElement('tns:OznSlijed', $this->noteOfOrder);
                    $writer->writeRaw($this->billNumber->toXML());
                    $writer->writeElement('tns:IznosUkupno', number_format($this->totalValue, 2, '.', ''));
                    $writer->writeElement('tns:NacinPlac', $this->typeOfPaying);
                    $writer->writeElement('tns:OibOper', $this->oibOperative);
                    $writer->writeElement('tns:ZastKod', $this->securityCode);
                    $writer->writeElement('tns:NakDost', $this->noteOfRedelivary ? 'true' : 'false');
                    $writer->writeElement('tns:OibPrimateljaRacuna', $this->idKupca); // stari kupac
                    $writer->writeElement('tns:PromijenjeniNacinPlac', $this->typeOfPaying); 
                    $writer->writeElement('tns:PromijenjeniOibPrimateljaRacuna', $this->newKupac); // novi
                    $writer->endElement(); // Racun
                    break;
        }

        return $writer->outputMemory();
    }

    private function detectOznakaIdKupca($id) {
        $id = trim($id);

        // HR OIB – točno 11 znamenki
        if (preg_match('/^[0-9]{11}$/', $id)) {
            return 'OIB';
        }

        // EU VAT ID – ISO prefiks + alfanumerički
        if (preg_match('/^[A-Z]{2}[0-9A-Z]+$/', $id)) {
            return 'VAT';
        }

        // Sve ostalo (treće zemlje, nacionalni brojevi)
        return 'TAX';
    }
}
