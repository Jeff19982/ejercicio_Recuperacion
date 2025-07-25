<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Models\InfoTributaria;
use App\Models\InfoCompRetencion;
use App\Models\ImpuestoDocSustento;
use App\Models\CampoAdicional;
use App\Models\ComprobanteRetencionSRI;
use App\Services\SriGenerator;
use App\Services\SriSigner; // Asegúrate de importar esta clase
use App\Services\Util;
use DateTime;

echo "Iniciando generación y envío de Comprobante de Retención...\n";

// --- CONFIGURACIÓN ---
$passwordCertificado = "tu_clave_de_certificado"; // ¡CAMBIA ESTO POR LA CONTRASEÑA DE TU CERTIFICADO!
$rutaCertificado = __DIR__ . '/../certificados/tu_certificado.p12'; // ¡CAMBIA ESTO POR LA RUTA REAL DE TU CERTIFICADO .p12!
// Si tu certificado .p12 ya incluye la clave privada, entonces el $rutaLlavePrivada no sería necesario
// y extraerías la clave privada del mismo .p12.
// Pero si tienes un archivo .key separado, la ruta iría aquí:
$rutaLlavePrivada = __DIR__ . '/../certificados/tu_clave_privada.pem'; // ¡CAMBIA ESTO POR LA RUTA REAL DE TU CLAVE PRIVADA .pem!

// Cargar el contenido del certificado y la clave privada
// Asumiendo que Util::loadCertificate ya extrae el contenido PEM.
// Si tu certificado es .p12, necesitarás una función que lo convierta a PEM y extraiga la clave privada.
// Por ahora, asumimos que tienes un .pem para la clave privada y otro para el certificado público.
$contenidoCertificadoPublico = Util::loadCertificate($rutaCertificado); // Asumiendo que carga el .pem del certificado público
$contenidoLlavePrivada = Util::loadCertificate($rutaLlavePrivada); // Asumiendo que carga el .pem de la clave privada
echo "Certificado cargado exitosamente.\n";

// Generar clave de acceso (ejemplo simple, en producción debe ser más robusto)
$fechaEmision = new DateTime(); // Fecha actual del comprobante
$rucEmisor = "1792186718001";
$ambiente = "1"; // 1=Pruebas, 2=Producción
$tipoEmision = "1"; // 1=Emisión Normal
$codDoc = "07"; // Comprobante de Retención
$serie = "001001";
$secuencial = "000000001"; // Debe ser secuencial y único
$codigoNumerico = "12345678"; // 8 dígitos
$tipoDoc = "07"; // Tipo de documento (07 para retención)

$claveAcceso = str_replace('/', '', Util::formatSRIDate($fechaEmision)) . // DDMMYYYY
                $tipoDoc . // 07
                $rucEmisor .
                $ambiente .
                $serie .
                $secuencial .
                $codigoNumerico .
                $tipoEmision;

// Dígito verificador (módulo 11) - Implementación simplificada, usar una función real
$claveAcceso .= 1; // Último dígito de verificación (ejemplo)


// --- CREACIÓN DE OBJETOS MODELO ---
// InfoTributaria
$infoTributaria = (new InfoTributaria())
    ->setAmbiente(1) // Pruebas
    ->setTipoEmision(1) // Emisión Normal
    ->setRazonSocial("TU RAZON SOCIAL S.A.")
    ->setNombreComercial("TU NOMBRE COMERCIAL") // Opcional
    ->setRuc("1792186718001") // Tu RUC
    ->setClaveAcceso($claveAcceso)
    ->setCodDoc("07") // Comprobante de Retención
    ->setEstab("001")
    ->setPtoEmi("001")
    ->setSecuencial(sprintf('%09d', (int)$secuencial)) // Asegura que sea un string de 9 dígitos
    ->setDirMatriz("Av. Ejemplo y Calle Demostracion");

// Aquí puedes establecer estos si aplica (se inicializan a null por defecto en el constructor de InfoTributaria)
// $infoTributaria->setAgenteRetencion("AGENTE DE RETENCION RESOLUCION Nro. NAC-DGERCGC20-00000001");
// $infoTributaria->setContribuyenteRimpe("CONTRIBUYENTE RÉGIMEN RIMPE");


// InfoCompRetencion
$infoCompRetencion = (new InfoCompRetencion())
    ->setFechaEmision($fechaEmision) // Objeto DateTime
    ->setDirEstablecimiento("Av. Ejemplo y Calle Demostracion")
    ->setObligadoContabilidad("SI")
    ->setTipoIdentificacionSujetoRetenido(4) // RUC: 04, Cédula: 05, Pasaporte: 06, Consumidor final: 07, Otro: 08, Placa: 09
    ->setRazonSocialSujetoRetenido("PROVEEDOR DE PRUEBAS S.A.")
    ->setIdentificacionSujetoRetenido("1790000000001") // RUC del sujeto retenido
    ->setPeriodoFiscal($fechaEmision->format('m/Y')); // Formato MM/YYYY

// Opcional: si aplica
// $infoCompRetencion->setDireccionSujetoRetenido("Direccion del Sujeto Retenido");


// Impuestos (pueden ser varios)
$impuesto1 = (new ImpuestoDocSustento())
    ->setCodigo("1") // 1=Renta, 2=IVA, 6=ISD
    ->setCodigoRetencion("304") // Por ejemplo, 304 para Honorarios profesionales (Ret. Renta)
    ->setBaseImponible(100.00) // 100 dólares
    ->setPorcentajeRetener(10.00) // 10%
    ->setValorRetenido(10.00) // 10 dólares (100 * 0.10)
    ->setCodDocSustento("01") // 01=Factura, 02=Nota de Venta, etc.
    ->setNumDocSustento("001002000000001") // Número de documento sustento
    ->setFechaEmisionDocSustento(new DateTime('2024-07-20')); // Fecha de la factura sustentadora

$impuesto2 = (new ImpuestoDocSustento())
    ->setCodigo("2") // IVA
    ->setCodigoRetencion("9") // Por ejemplo, 9 para Retención 30% IVA
    ->setBaseImponible(50.00) // 50 dólares (base imponible del IVA)
    ->setPorcentajeRetener(30.00) // 30%
    ->setValorRetenido(15.00) // 15 dólares (50 * 0.30)
    ->setCodDocSustento("01")
    ->setNumDocSustento("001002000000001")
    ->setFechaEmisionDocSustento(new DateTime('2024-07-20'));

$infoCompRetencion->addImpuestoRetencionSRI($impuesto1);
$infoCompRetencion->addImpuestoRetencionSRI($impuesto2);


// Comprobante de Retención principal
$comprobanteRetencion = (new ComprobanteRetencionSRI())
    ->setInfoTributaria($infoTributaria)
    ->setInfoCompRetencion($infoCompRetencion);

// Campos Adicionales (Opcional)
$campoAdicional1 = (new CampoAdicional())
    ->setNombre("Email")
    ->setValor("info@tudominio.com");
$comprobanteRetencion->addCampoAdicional($campoAdicional1);

echo "Modelos de datos creados y poblados.\n";

// Instancia el generador de XML y genera el XML
$sriGenerator = new SriGenerator();
$xmlDoc = $sriGenerator->generateXml($comprobanteRetencion); // Ahora devuelve un DOMDocument
echo "XML del comprobante generado.\n";

// Instancia el firmador XML
$sriSigner = new SriSigner();
// ¡CAMBIO AQUÍ! Pasamos el contenido de las claves, no las rutas
$signedXmlDoc = $sriSigner->signXml($xmlDoc, $contenidoLlavePrivada, $contenidoCertificadoPublico, $passwordCertificado);
echo "XML del comprobante firmado.\n";

// Guardar el XML firmado en un archivo para verificación
Util::writeFile($claveAcceso . '_retencion_firmada.xml', $signedXmlDoc->saveXML());
echo "XML firmado guardado como " . $claveAcceso . "_retencion_firmada.xml\n";

// *** AQUÍ IRÍA LA LÓGICA DE ENVÍO AL SRI (SOAP) ***
echo "Comprobante de Retención generado y listo para enviar al SRI.\n";

?>