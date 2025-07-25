<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use Exception;

class SriClient
{
    // Endpoints del SRI para ambientes de pruebas y producción
    private const ENDPOINT_RECEPCION_PRUEBAS = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    private const ENDPOINT_RECEPCION_PRODUCCION = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    private const ENDPOINT_AUTORIZACION_PRUEBAS = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
    private const ENDPOINT_AUTORIZACION_PRODUCCION = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

    private int $ambiente; // 1 para pruebas, 2 para producción

    public function __construct(int $ambiente)
    {
        if (!in_array($ambiente, [1, 2])) {
            throw new Exception('Ambiente no válido. Use 1 para pruebas o 2 para producción.');
        }
        $this->ambiente = $ambiente;
    }

    /**
     * Envía un comprobante electrónico al servicio web de Recepción del SRI.
     *
     * @param string $xmlFirmado El XML del comprobante ya firmado digitalmente.
     * @return array La respuesta del servicio de Recepción.
     * @throws Exception Si hay un error en la conexión o respuesta del SOAP.
     */
    public function enviarComprobante(string $xmlFirmado): array
    {
        $endpoint = ($this->ambiente === 1) ? self::ENDPOINT_RECEPCION_PRUEBAS : self::ENDPOINT_RECEPCION_PRODUCCION;

        $options = [
            'soap_version' => SOAP_1_1, // SRI usa SOAP 1.1
            'exceptions' => true,
            'trace' => 1, // Para depuración
            'connection_timeout' => 30, // Tiempo de espera en segundos
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false, // Considera true en producción con certificados CA root actualizados
                    'verify_peer_name' => false, // Considera true en producción
                    'allow_self_signed' => true,
                ]
            ])
        ];

        try {
            $client = new \SoapClient($endpoint, $options);

            // El XML debe ir como un array de bytes (base64_encode)
            $response = $client->validarComprobante([
                'xml' => base64_encode($xmlFirmado)
            ]);

            // La respuesta contiene un objeto `RespuestaRecepcionComprobante`
            // que a su vez contiene `estado` y `comprobantes`.
            // El estado puede ser 'RECIBIDA' o 'DEVUELTA'.
            return json_decode(json_encode($response), true); // Convertir objeto SOAP a array asociativo
        } catch (\SoapFault $e) {
            // Manejo de errores SOAP
            error_log("SOAP Fault al enviar comprobante: " . $e->getMessage());
            error_log("SOAP Request:\n" . $client->__getLastRequest());
            error_log("SOAP Response:\n" . $client->__getLastResponse());
            throw new Exception("Error al conectar con el SRI (Recepción): " . $e->getMessage() .
                                "\nCódigo: " . $e->getCode() .
                                "\nRequest: " . $client->__getLastRequest() .
                                "\nResponse: " . $client->__getLastResponse());
        } catch (\Exception $e) {
            error_log("Error general al enviar comprobante: " . $e->getMessage());
            throw new Exception("Error general al enviar comprobante: " . $e->getMessage());
        }
    }

    /**
     * Consulta el estado de autorización de un comprobante electrónico.
     *
     * @param string $claveAcceso La clave de acceso del comprobante.
     * @return array La respuesta del servicio de Autorización.
     * @throws Exception Si hay un error en la conexión o respuesta del SOAP.
     */
    public function consultarAutorizacion(string $claveAcceso): array
    {
        $endpoint = ($this->ambiente === 1) ? self::ENDPOINT_AUTORIZACION_PRUEBAS : self::ENDPOINT_AUTORIZACION_PRODUCCION;

        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'connection_timeout' => 30,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false, // Desactivar la verificación SSL para pruebas (NO RECOMENDADO EN PROD SIN CERTIFICADO CA VALIDO)
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ])
        ];

        try {
            $client = new \SoapClient($endpoint, $options);

            $response = $client->autorizacionComprobante([
                'claveAccesoComprobante' => $claveAcceso
            ]);

            // La respuesta contiene un objeto `RespuestaAutorizacionComprobante`
            // que a su vez contiene `claveAccesoConsultada` y `autorizaciones`.
            return json_decode(json_encode($response), true);
        } catch (\SoapFault $e) {
            error_log("SOAP Fault al consultar autorización: " . $e->getMessage());
            error_log("SOAP Request:\n" . $client->__getLastRequest());
            error_log("SOAP Response:\n" . $client->__getLastResponse());
            throw new Exception("Error al conectar con el SRI (Autorización): " . $e->getMessage() .
                                "\nCódigo: " . $e->getCode() .
                                "\nRequest: " . $client->__getLastRequest() .
                                "\nResponse: " . $client->__getLastResponse());
        } catch (\Exception $e) {
            error_log("Error general al consultar autorización: " . $e->getMessage());
            throw new Exception("Error general al consultar autorización: " . $e->getMessage());
        }
    }
}