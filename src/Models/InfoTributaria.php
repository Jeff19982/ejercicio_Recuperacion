<?php

declare(strict_types=1);

namespace App\Models;

// No necesitas DOMDocument o DOMElement aquí si no vas a construir XML directamente en el modelo
// use DOMDocument;
// use DOMElement;

class InfoTributaria
{
    private int $ambiente;
    private int $tipoEmision;
    private string $razonSocial;
    private ?string $nombreComercial; // Opcional
    private string $ruc;
    private string $claveAcceso;
    private string $codDoc;
    private string $estab;
    private string $ptoEmi;
    private string $secuencial;
    private string $dirMatriz;
    private ?string $agenteRetencion; // Opcional
    private ?string $contribuyenteRimpe; // Opcional

    // Constructor vacío o no definido, permitiendo usar setters
    public function __construct()
    {
       $this->nombreComercial = null;
        $this->agenteRetencion = null;
        $this->contribuyenteRimpe = null;
        $this->codDoc = "07";
    }

    public function getAmbiente(): int
    {
        return $this->ambiente;
    }

    public function setAmbiente(int $ambiente): self
    {
        $this->ambiente = $ambiente;
        return $this;
    }

    public function getTipoEmision(): int
    {
        return $this->tipoEmision;
    }

    public function setTipoEmision(int $tipoEmision): self
    {
        $this->tipoEmision = $tipoEmision;
        return $this;
    }

    public function getRazonSocial(): string
    {
        return $this->razonSocial;
    }

    public function setRazonSocial(string $razonSocial): self
    {
        $this->razonSocial = $razonSocial;
        return $this;
    }

    public function getNombreComercial(): ?string
    {
        return $this->nombreComercial;
    }

    public function setNombreComercial(?string $nombreComercial): self
    {
        $this->nombreComercial = $nombreComercial;
        return $this;
    }

    public function getRuc(): string
    {
        return $this->ruc;
    }

    public function setRuc(string $ruc): self
    {
        $this->ruc = $ruc;
        return $this;
    }

    public function getClaveAcceso(): string
    {
        return $this->claveAcceso;
    }

    public function setClaveAcceso(string $claveAcceso): self
    {
        $this->claveAcceso = $claveAcceso;
        return $this;
    }

    public function getCodDoc(): string
    {
        return $this->codDoc;
    }

    public function setCodDoc(string $codDoc): self
    {
        $this->codDoc = $codDoc;
        return $this;
    }

    public function getEstab(): string
    {
        return $this->estab;
    }

    public function setEstab(string $estab): self
    {
        $this->estab = $estab;
        return $this;
    }

    public function getPtoEmi(): string
    {
        return $this->ptoEmi;
    }

    public function setPtoEmi(string $ptoEmi): self
    {
        $this->ptoEmi = $ptoEmi;
        return $this;
    }

    public function getSecuencial(): string
    {
        return $this->secuencial;
    }

    public function setSecuencial(string $secuencial): self
    {
        $this->secuencial = $secuencial;
        return $this;
    }

    public function getDirMatriz(): string
    {
        return $this->dirMatriz;
    }

    public function setDirMatriz(string $dirMatriz): self
    {
        $this->dirMatriz = $dirMatriz;
        return $this;
    }

    public function getAgenteRetencion(): ?string
    {
        return $this->agenteRetencion;
    }

    public function setAgenteRetencion(?string $agenteRetencion): self
    {
        $this->agenteRetencion = $agenteRetencion;
        return $this;
    }

    public function getContribuyenteRimpe(): ?string
    {
        return $this->contribuyenteRimpe;
    }

    public function setContribuyenteRimpe(?string $contribuyenteRimpe): self
    {
        $this->contribuyenteRimpe = $contribuyenteRimpe;
        return $this;
    }
}