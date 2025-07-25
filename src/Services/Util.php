<?php

declare(strict_types=1);

namespace App\Services;

class Util
{
    /**
     * Carga el contenido de un certificado desde una ruta especificada.
     *
     * @param string $path La ruta absoluta o relativa al certificado.
     * @return string El contenido del certificado.
     * @throws \Exception Si no se puede cargar el certificado.
     */
    public static function loadCertificate(string $path): string
    {
        $certificate = file_get_contents($path);
        if ($certificate === false) {
            throw new \Exception('No se pudo cargar el certificado desde: ' . $path);
        }
        return $certificate;
    }

    /**
     * Escribe contenido en un archivo dentro de un directorio 'files'.
     *
     * @param string $filename El nombre del archivo a crear (ej. 'comprobante.xml').
     * @param string $content El contenido que se escribirá en el archivo.
     * @return void
     */
    public static function writeFile(string $filename, string $content): void
    {
        $fileDir = __DIR__.'/../../files'; // Subir dos niveles para llegar a la raíz del proyecto y luego a 'files'

        if (!file_exists($fileDir)) {
            mkdir($fileDir, 0777, true);
        }

        file_put_contents($fileDir.DIRECTORY_SEPARATOR.$filename, $content);
    }

    /**
     * Formatea una fecha para el formato requerido por el SRI (DD/MM/YYYY).
     *
     * @param \DateTime $date Objeto DateTime a formatear.
     * @return string La fecha formateada como string.
     */
    public static function formatSRIDate(\DateTime $date): string
    {
        return $date->format('d/m/Y');
    }

    /**
     * Formatea un número decimal a una cadena con 2 decimales.
     *
     * @param float $value El valor numérico a formatear.
     * @return string El número formateado como cadena con 2 decimales.
     */
    public static function formatDecimal(float $value): string
    {
        return sprintf('%.2f', $value);
    }

    // Aquí puedes añadir más funciones de utilidad genéricas si las necesitas en el futuro,
    // por ejemplo, para manejo de directorios, validaciones comunes, etc.
}