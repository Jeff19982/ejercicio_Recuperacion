<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;

class ImpuestoDocSustento
{
    private string $codigo;
    private string $codigoRetencion;
    private float $baseImponible; // CAMBIADO A FLOAT
    private float $porcentajeRetener; // CAMBIADO A FLOAT
    private float $valorRetenido; // CAMBIADO A FLOAT
    private string $codDocSustento;
    private string $numDocSustento;
    private string $fechaEmisionDocSustento;

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getCodigoRetencion(): string
    {
        return $this->codigoRetencion;
    }

    public function setCodigoRetencion(string $codigoRetencion): self
    {
        $this->codigoRetencion = $codigoRetencion;
        return $this;
    }

    public function getBaseImponible(): float // CAMBIADO A FLOAT
    {
        return $this->baseImponible;
    }

    public function setBaseImponible(float $baseImponible): self // CAMBIADO A FLOAT
    {
        $this->baseImponible = $baseImponible;
        return $this;
    }

    public function getPorcentajeRetener(): float // CAMBIADO A FLOAT
    {
        return $this->porcentajeRetener;
    }

    public function setPorcentajeRetener(float $porcentajeRetener): self // CAMBIADO A FLOAT
    {
        $this->porcentajeRetener = $porcentajeRetener;
        return $this;
    }

    public function getValorRetenido(): float // CAMBIADO A FLOAT
    {
        return $this->valorRetenido;
    }

    public function setValorRetenido(float $valorRetenido): self // CAMBIADO A FLOAT
    {
        $this->valorRetenido = $valorRetenido;
        return $this;
    }

    public function getCodDocSustento(): string
    {
        return $this->codDocSustento;
    }

    public function setCodDocSustento(string $codDocSustento): self
    {
        $this->codDocSustento = $codDocSustento;
        return $this;
    }

    public function getNumDocSustento(): string
    {
        return $this->numDocSustento;
    }

    public function setNumDocSustento(string $numDocSustento): self
    {
        $this->numDocSustento = $numDocSustento;
        return $this;
    }

    public function getFechaEmisionDocSustento(): string
    {
        return $this->fechaEmisionDocSustento;
    }

    public function setFechaEmisionDocSustento(DateTime $fechaEmisionDocSustento): self
    {
        $this->fechaEmisionDocSustento = $fechaEmisionDocSustento->format('d/m/Y');
        return $this;
    }
}