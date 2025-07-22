<?php

declare(strict_types=1);

use Greenter\Data\DocumentGeneratorInterface;
use Greenter\Data\GeneratorFactory;
use Greenter\Data\SharedStore;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\CdrResponse;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\Report\XmlUtils;
use Greenter\See;
// [MOD] Se añadieron imports necesarios para las clases de la librería Greenter
// que se utilizarán para representar la empresa y el cliente de forma dinámica.
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Address;
use Greenter\Model\Client;
use Greenter\Ws\Services\SoapClient; // Necesario para setService en getSee

final class Util
{
    /**
     * @var Util
     */
    private static $current;
    /**
     * @var SharedStore
     */
    public $shared;

    private function __construct()
    {
        // [MOD] La inicialización de `SharedStore` se ha modificado.
        // Ahora se usa una clase anónima que extiende `SharedStore`.
        // Esto permite sobrescribir los métodos `getCompany()` y `getClient()`
        // para personalizar la lógica de obtención de datos de la empresa y el cliente,
        // permitiendo que el cliente sea dinámico.
        $this->shared = new class extends SharedStore {
            /**
             * [MOD] Implementación personalizada para obtener los datos de la empresa.
             * Sustituye la implementación por defecto de SharedStore.
             *
             * @return Company Datos fiscales y de contacto de la empresa emisora.
             */
            public function getCompany(): Company
            {
                // [MOD] Datos de la empresa emisora.
                // TODO: Reemplazar '20123456789' y la información asociada con los datos fiscales REALES de la empresa.
                // El RUC aquí debe coincidir con el RUC configurado en setClaveSOL() de getSee().
                return (new Company())
                    ->setRuc('20123456789')
                    ->setRazonSocial('TU EMPRESA S.A.C.')
                    ->setNombreComercial('TU EMPRESA COMERCIAL')
                    ->setAddress((new Address())
                        ->setUbigueo('150101')
                        ->setDepartamento('LIMA')
                        ->setProvincia('LIMA')
                        ->setDistrito('LIMA')
                        ->setUrbanizacion('-')
                        ->setDireccion('Av. Los Robles 123'));
            }

            /**
             * [MOD] Implementación personalizada para obtener los datos del cliente.
             * Sustituye la implementación por defecto de SharedStore.
             *
             * @param string $docType Tipo de documento de identidad (ej. '6' para RUC, '1' para DNI).
             * @param string $docNum Número de documento de identidad del cliente.
             * @param string $name Nombre o razón social del cliente.
             * @param string $address Dirección del cliente (opcional).
             * @return Client Datos del cliente.
             */
            public function getClient(string $docType, string $docNum, string $name, string $address = ''): Client
            {
                // [MOD] Los datos del cliente se construyen a partir de los parámetros pasados,
                // permitiendo la entrada dinámica desde el formulario.
                return (new Client())
                    ->setTipoDoc($docType)
                    ->setNumDoc($docNum)
                    ->setRznSocial($name)
                    ->setAddress((new Address())
                        ->setDireccion($address));
                // NOTA: Para obtener ubigeo completo de clientes dinámicamente,
                // se necesitaría una búsqueda en una base de datos o un servicio externo.
            }
        };
    }

    public static function getInstance(): Util
    {
        if (!self::$current instanceof self) {
            self::$current = new self();
        }

        return self::$current;
    }

    /**
     * Obtiene una instancia de See configurada para el envío a SUNAT.
     *
     * @param string|null $endpoint URL del servicio web de SUNAT o un intermediario.
     * @return See Instancia de la clase See configurada.
     * @throws Exception Si no se puede cargar el certificado.
     */
    public function getSee(?string $endpoint): See
    {
        $see = new See();
        // [MOD] Se corrige la asignación del servicio: `setService` espera un objeto `SoapClient`,
        // no una URL directamente.
        $see->setService(new SoapClient($endpoint));

        $certificate = file_get_contents(__DIR__ . '/../resources/cert.pem');
        if ($certificate === false) {
            // [MOD] Mensaje de error más descriptivo si el certificado no se encuentra o no se puede leer.
            throw new Exception('No se pudo cargar el certificado desde ' . __DIR__ . '/../resources/cert.pem');
        }
        $see->setCertificate($certificate);

        /**
         * Clave SOL para autenticación en SUNAT.
         * Ruc     = RUC de la empresa emisora.
         * Usuario = Usuario SOL.
         * Clave   = Clave SOL.
         */
        // [MOD] Credenciales SOL de prueba.
        // TODO: Reemplazar '20123456789', 'MODDATOS', 'moddatos' con las credenciales SOL REALES de la empresa.
        $see->setClaveSOL('20123456789', 'MODDATOS', 'moddatos');
        $see->setCachePath(__DIR__ . '/../cache');

        return $see;
    }

    // El método getSeeApi no se utiliza en el flujo de facturación dinámica actual, se mantiene como referencia.
    public function getSeeApi()
    {
        $api = new \Greenter\Api([
            'auth' => 'https://gre-test.nubefact.com/v1',
            'cpe' => 'https://gre-test.nubefact.com/v1',
        ]);
        $certificate = file_get_contents(__DIR__ . '/../resources/cert.pem');
        if ($certificate === false) {
            throw new Exception('No se pudo cargar el certificado');
        }
        return $api->setBuilderOptions([
                'strict_variables' => true,
                'optimizations' => 0,
                'debug' => true,
                'cache' => false,
            ])
            ->setApiCredentials('test-85e5b0ae-255c-4891-a595-0b98c65c9854', 'test-Hty/M6QshYvPgItX2P0+Kw==')
            ->setClaveSOL('20161515648', 'MODDATOS', 'MODDATOS')
            ->setCertificate($certificate);
    }

    // El método getGRECompany ya no es el principal para obtener los datos de la empresa,
    // ya que la lógica ha sido trasladada a $this->shared->getCompany(). Se mantiene por compatibilidad.
    public function getGRECompany(): Company
    {
        return (new Company())
            ->setRuc('20161515648')
            ->setRazonSocial('GREENTER S.A.C.');
    }

    /**
     * Muestra la respuesta de SUNAT.
     *
     * @param DocumentInterface $document Objeto del documento electrónico enviado.
     * @param CdrResponse $cdr Objeto de respuesta (CDR) de SUNAT.
     */
    public function showResponse(DocumentInterface $document, CdrResponse $cdr): void
    {
        $filename = $document->getName();
        // [INFO] Incluye un archivo de vista para renderizar la respuesta.
        // Asegurarse de que 'views/response.php' exista y procese las variables $filename y $cdr.
        require __DIR__.'/../views/response.php';
    }

    /**
     * Obtiene el mensaje de error de una respuesta fallida.
     *
     * @param \Greenter\Model\Response\Error $error Objeto de error de Greenter.
     * @return string Mensaje de error formateado.
     */
    public function getErrorResponse(\Greenter\Model\Response\Error $error): string
    {
        $result = <<<HTML
        <h2 class="text-danger">Error:</h2><br>
        <b>Código:</b>{$error->getCode()}<br>
        <b>Descripción:</b>{$error->getMessage()}<br>
HTML;

        return $result;
    }

    /**
     * Guarda el XML generado del documento electrónico.
     *
     * @param DocumentInterface $document Objeto del documento electrónico.
     * @param string|null $xml Contenido XML del documento.
     */
    public function writeXml(DocumentInterface $document, ?string $xml): void
    {
        $this->writeFile($document->getName().'.xml', $xml);
    }

    /**
     * Guarda el CDR (Constancia de Recepción) del documento electrónico.
     *
     * @param DocumentInterface $document Objeto del documento electrónico.
     * @param string|null $zip Contenido del archivo ZIP del CDR.
     */
    public function writeCdr(DocumentInterface $document, ?string $zip): void
    {
        $this->writeFile('R-'.$document->getName().'.zip', $zip);
    }

    /**
     * Escribe contenido en un archivo en el directorio de 'files'.
     *
     * @param string|null $filename Nombre del archivo a crear.
     * @param string|null $content Contenido a escribir en el archivo.
     */
    public function writeFile(?string $filename, ?string $content): void
    {
        if (getenv('GREENTER_NO_FILES')) {
            return;
        }

        $fileDir = __DIR__.'/../files'; // [INFO] Directorio configurado para guardar archivos generados (XML, CDR).

        if (!file_exists($fileDir)) {
            mkdir($fileDir, 0777, true); // [INFO] Crea el directorio si no existe. Asegurar permisos de escritura.
        }

        file_put_contents($fileDir.DIRECTORY_SEPARATOR.$filename, $content);
    }

    /**
     * Genera un PDF a partir del documento electrónico.
     * Requiere wkhtmltopdf instalado en el sistema.
     *
     * @param DocumentInterface $document Objeto del documento electrónico.
     * @return string|null Contenido binario del PDF o null si falla.
     */
    public function getPdf(DocumentInterface $document): ?string
    {
        $html = new HtmlReport('', [
            'cache' => __DIR__ . '/../cache',
            'strict_variables' => true,
        ]);
        $resolver = new DefaultTemplateResolver();
        $template = $resolver->getTemplate($document);
        $html->setTemplate($template);

        $render = new PdfReport($html);
        $render->setOptions( [
            'no-outline',
            'print-media-type',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
            'footer-html' => __DIR__.'/../resources/footer.html',
        ]);
        $binPath = self::getPathBin();
        if (file_exists($binPath)) {
            $render->setBinPath($binPath);
        }
        $hash = $this->getHash($document);
        $params = self::getParametersPdf();
        $params['system']['hash'] = $hash;
        $params['user']['footer'] = '<div>consulte en <a href="https://github.com/giansalex/sufel">sufel.com</a></div>';

        $pdf = $render->render($document, $params);

        if ($pdf === null) {
            $error = $render->getExporter()->getError();
            echo 'Error: '.$error;
            exit();
        }

        // Write html
        $this->writeFile($document->getName().'.html', $render->getHtml());

        return $pdf;
    }

    /**
     * Obtiene una instancia del generador de documentos por tipo.
     *
     * @param string $type Tipo de documento (ej. 'invoice', 'bill').
     * @return DocumentGeneratorInterface|null
     */
    public function getGenerator(string $type): ?DocumentGeneratorInterface
    {
        $factory = new GeneratorFactory();
        $factory->shared = $this->shared; // [INFO] Asegura que el factory use la instancia modificada de shared.

        return $factory->create($type);
    }

    /**
     * Genera un array de SaleDetail con el mismo item.
     *
     * @param SaleDetail $item Detalle de venta.
     * @param int $count Número de veces a duplicar el item.
     * @return array<SaleDetail> Array de detalles de venta.
     */
    public function generator(SaleDetail $item, int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Muestra un PDF en el navegador.
     *
     * @param string|null $content Contenido binario del PDF.
     * @param string|null $filename Nombre del archivo para la descarga.
     */
    public function showPdf(?string $content, ?string $filename): void
    {
        $this->writeFile($filename, $content);
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($content));

        echo $content;
    }

    /**
     * Obtiene la ruta al binario de wkhtmltopdf.
     *
     * @return string Ruta completa al ejecutable.
     */
    public static function getPathBin(): string
    {
        $path = __DIR__.'/../vendor/bin/wkhtmltopdf';
        if (self::isWindows()) {
            $path .= '.exe';
        }

        return $path;
    }

    /**
     * Verifica si el sistema operativo es Windows.
     *
     * @return bool True si es Windows, false en caso contrario.
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Obtiene el hash de la firma del documento XML.
     *
     * @param DocumentInterface $document Objeto del documento electrónico.
     * @return string|null Hash de la firma.
     */
    private function getHash(DocumentInterface $document): ?string
    {
        // [INFO] Se necesita un endpoint para inicializar See, aunque no se use para la firma en sí.
        $see = $this->getSee('');
        $xml = $see->getXmlSigned($document);

        return (new XmlUtils())->getHashSign($xml);
    }

    /**
     * Retorna parámetros para la generación del PDF.
     *
     * @return array<string, array<string, array<int, array<string, string>>|bool|string>>
     */
    private static function getParametersPdf(): array
    {
        $logo = file_get_contents(__DIR__.'/../resources/logo.png');

        return [
            'system' => [
                'logo' => $logo,
                'hash' => ''
            ],
            'user' => [
                'resolucion' => '212321',
                'header' => 'Telf: <b>(056) 123375</b>',
                'extras' => [
                    ['name' => 'FORMA DE PAGO', 'value' => 'Contado'],
                    ['name' => 'VENDEDOR', 'value' => 'GITHUB SELLER'],
                ],
            ]
        ];
    }
}