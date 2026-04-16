<?php

namespace Fiskalizacija;

use DOMDocument;
use DOMElement;
use Exception;

class Fiskalizacija {
    public $certificate;
    private $security;
    private $url = "https://cis.porezna-uprava.hr:8449/FiskalizacijaService";
    private $publicCertificateData;
    private $privateKeyResource;

    public function __construct($path, $pass, $security = 'SSL', $demo) {
        if ($demo == true) {
            $this->url = "https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest";
        }

        $this->configureOpenSslProviders();
        $this->setCertificate($path, $pass);

        $privateKeyPem = $this->certificate['pkey'] ?? null;
        $certificatePem = $this->certificate['cert'] ?? null;

        if (empty($privateKeyPem) || empty($certificatePem)) {
            throw new Exception('PKCS#12 certifikat ne sadrzi privatni kljuc ili javni certifikat.');
        }

        $open_ssl_pkey = openssl_pkey_get_private($privateKeyPem, $pass);
        if (!$open_ssl_pkey) {
            $open_ssl_pkey = openssl_pkey_get_private($privateKeyPem);
        }

        if (!$open_ssl_pkey) {
            throw new Exception($this->getOpenSslErrorMessage('Ne mogu otvoriti privatni kljuc iz certifikata.'));
        }

        $publicCertificateData = openssl_x509_parse($certificatePem);
        if ($publicCertificateData === false) {
            throw new Exception($this->getOpenSslErrorMessage('Ne mogu procitati javni certifikat.'));
        }

        $this->privateKeyResource = $open_ssl_pkey;
        $this->publicCertificateData = $publicCertificateData;
        $this->security = $security;
    }

    public function setCertificate($path, $pass) {
        $pkcs12 = $this->readCertificateFromDisk($path);
        $this->certificate = [];

        if (!openssl_pkcs12_read($pkcs12, $this->certificate, $pass)) {
            $fallbackCertificate = $this->readPkcs12ViaOpenSslBinary($path, $pass);

            if ($fallbackCertificate !== null) {
                $this->certificate = $fallbackCertificate;
                return;
            }

            throw new Exception($this->getOpenSslErrorMessage('Ne mogu ucitati PKCS#12 certifikat. Provjerite putanju i p12 lozinku.'));
        }
    }

    public function readCertificateFromDisk($path) {
        $cert = @file_get_contents($path);

        if (false === $cert) {
            throw new \Exception("Ne mogu procitati certifikat sa lokacije: " .
                $path, 1);
        }
        
        return $cert;
    }

    public function getPrivateKey() {
        if (empty($this->certificate['pkey'])) {
            throw new Exception('Privatni kljuc nije dostupan u ucitanom certifikatu.');
        }

        return $this->certificate['pkey'];
    }

    public function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    public function signXML($XMLRequest) {
        if (empty($this->certificate['cert']) || empty($this->publicCertificateData) || empty($this->privateKeyResource)) {
            throw new Exception('Certifikat nije ispravno ucitan za potpisivanje XML-a.');
        }

        $XMLRequestDOMDoc = new DOMDocument();
        $XMLRequestDOMDoc->loadXML($XMLRequest);

        $canonical = $XMLRequestDOMDoc->C14N();
        $DigestValue = base64_encode(hash('sha1', $canonical, true));

        $rootElem = $XMLRequestDOMDoc->documentElement;

        $SignatureNode = $rootElem->appendChild(new DOMElement('Signature'));
        $SignatureNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $SignedInfoNode = $SignatureNode->appendChild(new DOMElement('SignedInfo'));
        $SignedInfoNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $CanonicalizationMethodNode = $SignedInfoNode->appendChild(new DOMElement('CanonicalizationMethod'));
        $CanonicalizationMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $SignatureMethodNode = $SignedInfoNode->appendChild(new DOMElement('SignatureMethod'));
        $SignatureMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

        $ReferenceNode = $SignedInfoNode->appendChild(new DOMElement('Reference'));
        $ReferenceNode->setAttribute('URI', sprintf('#%s', $XMLRequestDOMDoc->documentElement->getAttribute('Id')));

        $TransformsNode = $ReferenceNode->appendChild(new DOMElement('Transforms'));

        $Transform1Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform1Node->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

        $Transform2Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform2Node->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $DigestMethodNode = $ReferenceNode->appendChild(new DOMElement('DigestMethod'));
        $DigestMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        $ReferenceNode->appendChild(new DOMElement('DigestValue', $DigestValue));

        $SignedInfoNode = $XMLRequestDOMDoc->getElementsByTagName('SignedInfo')->item(0);

        $X509Issuer = $this->publicCertificateData['issuer'];
        $X509IssuerName = sprintf('O=%s,C=%s', $X509Issuer['O'], $X509Issuer['C']);
        $X509IssuerSerial = $this->bchexdec($this->publicCertificateData['serialNumberHex']);

        $publicCertificatePureString = str_replace('-----BEGIN CERTIFICATE-----', '', $this->certificate['cert']);
        $publicCertificatePureString = str_replace('-----END CERTIFICATE-----', '', $publicCertificatePureString);

        $signedInfoSignature = null;

        if (!openssl_sign($SignedInfoNode->C14N(true), $signedInfoSignature, $this->privateKeyResource, OPENSSL_ALGO_SHA1)) {
            throw new Exception('Unable to sign the request');
        }

        $SignatureNode = $XMLRequestDOMDoc->getElementsByTagName('Signature')->item(0);
        $SignatureValueNode = new DOMElement('SignatureValue', base64_encode($signedInfoSignature));
        $SignatureNode->appendChild($SignatureValueNode);

        $KeyInfoNode = $SignatureNode->appendChild(new DOMElement('KeyInfo'));

        $X509DataNode = $KeyInfoNode->appendChild(new DOMElement('X509Data'));
        $X509CertificateNode = new DOMElement('X509Certificate', $publicCertificatePureString);
        $X509DataNode->appendChild($X509CertificateNode);

        $X509IssuerSerialNode = $X509DataNode->appendChild(new DOMElement('X509IssuerSerial'));

        $X509IssuerNameNode = new DOMElement('X509IssuerName', $X509IssuerName);
        $X509IssuerSerialNode->appendChild($X509IssuerNameNode);

        $X509SerialNumberNode = new DOMElement('X509SerialNumber', $X509IssuerSerial);
        $X509IssuerSerialNode->appendChild($X509SerialNumberNode);

        $envelope = new DOMDocument();

        $envelope->loadXML('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
		    <soapenv:Body></soapenv:Body>
		</soapenv:Envelope>');

        $envelope->encoding = 'UTF-8';
        $envelope->version = '1.0';
        $XMLRequestType = $XMLRequestDOMDoc->documentElement->localName;
        $XMLRequestTypeNode = $XMLRequestDOMDoc->getElementsByTagName($XMLRequestType)->item(0);
        $XMLRequestTypeNode = $envelope->importNode($XMLRequestTypeNode, true);

        $envelope->getElementsByTagName('Body')->item(0)->appendChild($XMLRequestTypeNode);
        return $envelope->saveXML();
    }

    public function sendSoap($payload) {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $this->url,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,

            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => false,

            // 🔥 BITNO
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_ENCODING => '',

            // debug (po želji)
            CURLOPT_HEADER => false,
        );

        if ($this->security === 'TLS') {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 🔥 VRATI RAW XML (bez parseResponse!)
        return $response;
    }

    private function getOpenSslErrorMessage($fallbackMessage) {
        $errors = [];

        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        if (empty($errors)) {
            return $fallbackMessage;
        }

        return $fallbackMessage . ' OpenSSL: ' . implode(' | ', $errors);
    }

    private function configureOpenSslProviders() {
        $projectRoot = dirname(__DIR__);
        $configPath = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'openssl-legacy.cnf';
        $modulesPath = 'D:\\xampp\\php\\extras\\ssl';

        if (is_file($configPath)) {
            putenv('OPENSSL_CONF=' . $configPath);
            $_ENV['OPENSSL_CONF'] = $configPath;
            $_SERVER['OPENSSL_CONF'] = $configPath;
        }

        if (is_dir($modulesPath)) {
            putenv('OPENSSL_MODULES=' . $modulesPath);
            $_ENV['OPENSSL_MODULES'] = $modulesPath;
            $_SERVER['OPENSSL_MODULES'] = $modulesPath;
        }
    }

    private function readPkcs12ViaOpenSslBinary($path, $pass) {
        $opensslBinary = $this->findOpenSslBinary();

        if ($opensslBinary === null) {
            return null;
        }

        $modulesPath = 'D:\\xampp\\php\\extras\\ssl';
        putenv('OPENSSL_MODULES=' . $modulesPath);

        $privateKeyOutput = [];
        $privateKeyExitCode = 1;
        $privateKeyCommand = escapeshellarg($opensslBinary)
            . ' pkcs12 -legacy -in ' . escapeshellarg($path)
            . ' -passin pass:' . escapeshellarg($pass)
            . ' -nocerts -nodes 2>&1';
        exec($privateKeyCommand, $privateKeyOutput, $privateKeyExitCode);

        $certificateOutput = [];
        $certificateExitCode = 1;
        $certificateCommand = escapeshellarg($opensslBinary)
            . ' pkcs12 -legacy -in ' . escapeshellarg($path)
            . ' -passin pass:' . escapeshellarg($pass)
            . ' -clcerts -nokeys 2>&1';
        exec($certificateCommand, $certificateOutput, $certificateExitCode);

        if ($privateKeyExitCode !== 0 || $certificateExitCode !== 0) {
            return null;
        }

        $privateKeyPem = $this->extractPemBlock(implode(PHP_EOL, $privateKeyOutput), 'PRIVATE KEY');
        $certificatePem = $this->extractPemBlock(implode(PHP_EOL, $certificateOutput), 'CERTIFICATE');

        if ($privateKeyPem === null || $certificatePem === null) {
            return null;
        }

        return [
            'pkey' => $privateKeyPem,
            'cert' => $certificatePem,
        ];
    }

    private function findOpenSslBinary() {
        $candidates = [
            'D:\\xampp\\apache\\bin\\openssl.exe',
            'D:\\xampp\\php\\extras\\openssl\\openssl.exe',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractPemBlock($content, $type) {
        $pattern = '/-----BEGIN ' . preg_quote($type, '/') . '-----(.*?)-----END ' . preg_quote($type, '/') . '-----/s';

        if (!preg_match($pattern, $content, $matches)) {
            return null;
        }

        return '-----BEGIN ' . $type . '-----' . PHP_EOL
            . trim($matches[1]) . PHP_EOL
            . '-----END ' . $type . '-----';
    }
}

