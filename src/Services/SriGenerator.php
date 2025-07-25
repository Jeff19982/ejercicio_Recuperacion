<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement; 
use App\Models\ComprobanteRetencionSRI;
use App\Models\InfoTributaria;
use App\Models\InfoCompRetencion;
use App\Models\ImpuestoDocSustento; 
use App\Models\CampoAdicional;
use DateTime; 

class SriGenerator
{
    /**
     * Genera un DOMDocument del comprobante de retención a partir del objeto modelo.
     *
     * @param ComprobanteRetencionSRI $comprobante El objeto ComprobanteRetencionSRI con todos los datos.
     * @return DOMDocument El objeto DOMDocument que representa el XML del comprobante.
     * @throws \Exception Si hay un error en la generación del XML.
     */
    public function generateXml(ComprobanteRetencionSRI $comprobante): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8'); // ¡CAMBIADO $xml a $dom!
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Elemento raíz <comprobanteRetencion>
        $retencionNode = $dom->createElement('comprobanteRetencion');
        $retencionNode->setAttribute('id', 'comprobante');
        $retencionNode->setAttribute('version', '1.0.0'); // O '2.0.0' si usas el esquema más reciente
        $dom->appendChild($retencionNode); // Añadir al DOM

        // Información Tributaria
        $infoTributaria = $comprobante->getInfoTributaria();
        $infoTributariaNode = $dom->createElement('infoTributaria');
        $retencionNode->appendChild($infoTributariaNode); // Añadir al nodo padre

        $infoTributariaNode->appendChild($dom->createElement('ambiente', (string)$infoTributaria->getAmbiente()));
        $infoTributariaNode->appendChild($dom->createElement('tipoEmision', (string)$infoTributaria->getTipoEmision()));
        $infoTributariaNode->appendChild($dom->createElement('razonSocial', htmlspecialchars($infoTributaria->getRazonSocial())));
        if ($infoTributaria->getNombreComercial()) {
            $infoTributariaNode->appendChild($dom->createElement('nombreComercial', htmlspecialchars($infoTributaria->getNombreComercial())));
        }
        $infoTributariaNode->appendChild($dom->createElement('ruc', $infoTributaria->getRuc()));
        $infoTributariaNode->appendChild($dom->createElement('claveAcceso', $infoTributaria->getClaveAcceso()));
        $infoTributariaNode->appendChild($dom->createElement('codDoc', $infoTributaria->getCodDoc()));
        $infoTributariaNode->appendChild($dom->createElement('estab', $infoTributaria->getEstab()));
        $infoTributariaNode->appendChild($dom->createElement('ptoEmi', $infoTributaria->getPtoEmi()));
        $infoTributariaNode->appendChild($dom->createElement('secuencial', sprintf('%09d', (int)$infoTributaria->getSecuencial())));
        $infoTributariaNode->appendChild($dom->createElement('dirMatriz', htmlspecialchars($infoTributaria->getDirMatriz())));

        if ($infoTributaria->getAgenteRetencion()) {
            $infoTributariaNode->appendChild($dom->createElement('agenteRetencion', htmlspecialchars($infoTributaria->getAgenteRetencion())));
        }
        if ($infoTributaria->getContribuyenteRimpe()) {
            $infoTributariaNode->appendChild($dom->createElement('contribuyenteRimpe', htmlspecialchars($infoTributaria->getContribuyenteRimpe())));
        }

        // Información Comprobante Retención
        $infoCompRetencion = $comprobante->getInfoCompRetencion();
        $infoCompRetencionNode = $dom->createElement('infoCompRetencion');
        $retencionNode->appendChild($infoCompRetencionNode); // Añadir al nodo padre

        $infoCompRetencionNode->appendChild($dom->createElement('fechaEmision', Util::formatSRIDate($infoCompRetencion->getFechaEmision())));
        $infoCompRetencionNode->appendChild($dom->createElement('dirEstablecimiento', htmlspecialchars($infoCompRetencion->getDirEstablecimiento())));
        if ($infoCompRetencion->getDireccionSujetoRetenido()) { // Solo si está presente
            $infoCompRetencionNode->appendChild($dom->createElement('direccionSujetoRetenido', htmlspecialchars($infoCompRetencion->getDireccionSujetoRetenido())));
        }
        $infoCompRetencionNode->appendChild($dom->createElement('obligadoContabilidad', $infoCompRetencion->getObligadoContabilidad()));
        $infoCompRetencionNode->appendChild($dom->createElement('tipoIdentificacionSujetoRetenido', (string)$infoCompRetencion->getTipoIdentificacionSujetoRetenido()));
        $infoCompRetencionNode->appendChild($dom->createElement('razonSocialSujetoRetenido', htmlspecialchars($infoCompRetencion->getRazonSocialSujetoRetenido())));
        $infoCompRetencionNode->appendChild($dom->createElement('identificacionSujetoRetenido', $infoCompRetencion->getIdentificacionSujetoRetenido()));
        $infoCompRetencionNode->appendChild($dom->createElement('periodoFiscal', $infoCompRetencion->getPeriodoFiscal())); // Periodo fiscal ya es string MM/YYYY

        // Impuestos
        $impuestosNode = $dom->createElement('impuestos');
        $infoCompRetencionNode->appendChild($impuestosNode); // ¡CORRECCIÓN: Debe ir dentro de infoCompRetencionNode!

        foreach ($infoCompRetencion->getImpuestoRetencionSRI() as $impuesto) {
            $impuestoRetencionNode = $dom->createElement('impuesto');
            $impuestosNode->appendChild($impuestoRetencionNode);

            $impuestoRetencionNode->appendChild($dom->createElement('codigo', $impuesto->getCodigo()));
            $impuestoRetencionNode->appendChild($dom->createElement('codigoRetencion', $impuesto->getCodigoRetencion()));
            $impuestoRetencionNode->appendChild($dom->createElement('baseImponible', Util::formatDecimal($impuesto->getBaseImponible())));
            $impuestoRetencionNode->appendChild($dom->createElement('porcentajeRetener', Util::formatDecimal($impuesto->getPorcentajeRetener())));
            $impuestoRetencionNode->appendChild($dom->createElement('valorRetenido', Util::formatDecimal($impuesto->getValorRetenido())));
            $impuestoRetencionNode->appendChild($dom->createElement('codDocSustento', $impuesto->getCodDocSustento()));
            $impuestoRetencionNode->appendChild($dom->createElement('numDocSustento', $impuesto->getNumDocSustento()));
            // --- ¡CAMBIO CRUCIAL AQUÍ! ---
            // Convertir la cadena de fecha a un objeto DateTime antes de pasarlo a formatSRIDate
            $fechaEmisionDocSustentoStr = $impuesto->getFechaEmisionDocSustento();
            $fechaEmisionDocSustentoObj = DateTime::createFromFormat('d/m/Y', $fechaEmisionDocSustentoStr);
            if ($fechaEmisionDocSustentoObj === false) {
                // Manejar error si el formato de la fecha es incorrecto
                throw new \Exception('Fecha de emisión de documento sustento inválida: ' . $fechaEmisionDocSustentoStr . '. Formato esperado: DD/MM/YYYY');
            }
            $impuestoRetencionNode->appendChild($dom->createElement('fechaEmisionDocSustento', Util::formatSRIDate($fechaEmisionDocSustentoObj)));
            // --- FIN DEL CAMBIO CRUCIAL ---
        }

        // Información Adicional (CamposAdicionales)
        if (count($comprobante->getInfoAdicional()) > 0) {
            $infoAdicionalNode = $dom->createElement('infoAdicional');
            $retencionNode->appendChild($infoAdicionalNode);

            foreach ($comprobante->getInfoAdicional() as $campoAdicional) {
                $campoAdicionalNode = $dom->createElement('campoAdicional', htmlspecialchars($campoAdicional->getValor()));
                $campoAdicionalNode->setAttribute('nombre', $campoAdicional->getNombre());
                $infoAdicionalNode->appendChild($campoAdicionalNode);
            }
        }

        // Cambio el retorno para que sea el DOMDocument y no el XML string
        return $dom;
    }
}