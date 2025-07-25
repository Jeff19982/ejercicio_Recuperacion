<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SriSigner
{
    /**
     * Firma un documento XML con el certificado y clave proporcionados.
     *
     * @param DOMDocument 
     * @param string 
     * @param string 
     * @param string 
     * @return DOMDocument El objeto DOMDocument firmado.
     * @throws \Exception Si hay un error durante el proceso de firma.
     */
    public function signXml(DOMDocument $xmlDoc, string $privateKeyContent, string $publicKeyContent, string $password): DOMDocument
    {
        // Crear un objeto XMLSecurityDSig
        $objDSig = new XMLSecurityDSig();

        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N_EXCLUSIVE_WITH_COMMENTS);

        $objDSig->addReference(
            $xmlDoc,
            XMLSecurityDSig::SHA1,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), // Tipo de transformación
            ['id' => 'comprobante'] // Referencia al ID del elemento raíz. Asegúrate que tu elemento raíz tenga id="comprobante"
        );

        // Crear un objeto XMLSecurityKey para la clave privada
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);

        // Cargar la clave privada desde su CONTENIDO (no desde un archivo)
        // El segundo parámetro 'false' es CRUCIAL para indicar que es el contenido.
        if (!$objKey->loadKey($privateKeyContent, false, $password)) {
            throw new \Exception('No se pudo cargar la clave privada. Verifique la contraseña o el formato.');
        }

        // Firmar el documento XML
        $objDSig->sign($xmlDoc, $objKey);

        // Añadir el certificado (clave pública) a la sección KeyInfo
        $objDSig->add509Cert($publicKeyContent, true); // true para incluir la cadena de certificados si es necesario

        // Añadir el elemento Signature al DOMDocument
        $objDSig->appendSignature($xmlDoc->documentElement);

        return $xmlDoc;
    }
}