<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use App\Models\ImpuestoDocSustento; // Asegúrate de que esta clase también exista

class InfoCompRetencion
{
    private DateTime $fechaEmision;
    private string $dirEstablecimiento;
    private string $obligadoContabilidad;
    private int $tipoIdentificacionSujetoRetenido;
    private string $razonSocialSujetoRetenido;
    private string $identificacionSujetoRetenido;
    private string $periodoFiscal;
    private ?string $direccionSujetoRetenido; // Opcional
    /**
     * @var ImpuestoDocSustento[]
     */
    private array $impuestos;

    public function __construct()
    {
        // Constructor vacío, permite crear la instancia sin argumentos
        $this->impuestos = [];
        $this->direccionSujetoRetenido = null;
    }

    public function getFechaEmision(): DateTime
    {
        return $this->fechaEmision;
    }

    public function setFechaEmision(DateTime $fechaEmision): self
    {
        $this->fechaEmision = $fechaEmision;
        return $this;
    }

    public function getDirEstablecimiento(): string
    {
        return $this->dirEstablecimiento;
    }

    public function setDirEstablecimiento(string $dirEstablecimiento): self
    {
        $this->dirEstablecimiento = $dirEstablecimiento;
        return $this;
    }

    public function getObligadoContabilidad(): string
    {
        return $this->obligadoContabilidad;
    }

    public function setObligadoContabilidad(string $obligadoContabilidad): self
    {
        // Debe ser 'SI' o 'NO'
        if (!in_array($obligadoContabilidad, ['SI', 'NO'])) {
            throw new \InvalidArgumentException('obligadoContabilidad debe ser "SI" o "NO".');
        }
        $this->obligadoContabilidad = $obligadoContabilidad;
        return $this;
    }

    public function getTipoIdentificacionSujetoRetenido(): int
    {
        return $this->tipoIdentificacionSujetoRetenido;
    }

    public function setTipoIdentificacionSujetoRetenido(int $tipoIdentificacionSujetoRetenido): self
    {
        $this->tipoIdentificacionSujetoRetenido = $tipoIdentificacionSujetoRetenido;
        return $this;
    }

    public function getRazonSocialSujetoRetenido(): string
    {
        return $this->razonSocialSujetoRetenido;
    }

    public function setRazonSocialSujetoRetenido(string $razonSocialSujetoRetenido): self
    {
        $this->razonSocialSujetoRetenido = $razonSocialSujetoRetenido;
        return $this;
    }

    public function getIdentificacionSujetoRetenido(): string
    {
        return $this->identificacionSujetoRetenido;
    }

    public function setIdentificacionSujetoRetenido(string $identificacionSujetoRetenido): self
    {
        $this->identificacionSujetoRetenido = $identificacionSujetoRetenido;
        return $this;
    }

    public function getPeriodoFiscal(): string
    {
        return $this->periodoFiscal;
    }

    public function setPeriodoFiscal(string $periodoFiscal): self
    {
        // Puedes agregar validación de formato MM/YYYY aquí si lo deseas
        $this->periodoFiscal = $periodoFiscal;
        return $this;
    }

    public function getDireccionSujetoRetenido(): ?string
    {
        return $this->direccionSujetoRetenido;
    }

    public function setDireccionSujetoRetenido(?string $direccionSujetoRetenido): self
    {
        $this->direccionSujetoRetenido = $direccionSujetoRetenido;
        return $this;
    }

    /**
     * @return ImpuestoDocSustento[]
     */
    public function getImpuestoRetencionSRI(): array
    {
        return $this->impuestos;
    }

    public function addImpuesto(ImpuestoDocSustento $impuesto): self
    {
        $this->impuestos[] = $impuesto;
        return $this;
    }
}