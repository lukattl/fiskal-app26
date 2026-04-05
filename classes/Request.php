<?php namespace Fiskalizacija;

use XMLWriter;

class Request {
    protected $request;
    protected $requestName = 'Zahtjev';

    public function toXML() {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString("    ");
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElementNS('tns', $this->requestName, 'http://www.apis-it.hr/fin/2012/types/f73');
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation', 'http://www.apis-it.hr/fin/2012/types/f73 FiskalizacijaSchema.xsd');
        $writer->writeAttribute('Id', $this->requestName);

        $writer->startElementNS('tns', 'Zaglavlje', null);
        $writer->writeElementNS('tns', 'IdPoruke', null, $this->generateMessageId());
        $writer->writeElementNS('tns', 'DatumVrijeme', null, date('d.m.Y\TH:i:s'));
        $writer->endElement();

        if ($this->request && method_exists($this->request, 'toXML')) {
            $writer->writeRaw($this->request->toXML());
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    protected function generateMessageId() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
