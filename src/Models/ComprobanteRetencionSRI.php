<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\InfoTributaria;
use App\Models\InfoCompRetencion;
use App\Models\CampoAdicional; // Asegúrate de que esta clase también exista

class ComprobanteRetencionSRI
{
    private InfoTributaria $infoTributaria;
    private InfoCompRetencion $infoCompRetencion;
    /**
     * @var CampoAdicional[]
     */
    private array $infoAdicional;

    public function __construct()
    {
        $this->infoAdicional = [];
    }

    public function getInfoTributaria(): InfoTributaria
    {
        return $this->infoTributaria;
    }

    public function setInfoTributaria(InfoTributaria $infoTributaria): self
    {
        $this->infoTributaria = $infoTributaria;
        return $this;
    }

    public function getInfoCompRetencion(): InfoCompRetencion
    {
        return $this->infoCompRetencion;
    }

    public function setInfoCompRetencion(InfoCompRetencion $infoCompRetencion): self
    {
        $this->infoCompRetencion = $infoCompRetencion;
        return $this;
    }

    /**
     * @return CampoAdicional[]
     */
    public function getInfoAdicional(): array
    {
        return $this->infoAdicional;
    }

    public function addCampoAdicional(CampoAdicional $campoAdicional): self
    {
        $this->infoAdicional[] = $campoAdicional;
        return $this;
    }
}