<?php

namespace inquid\SatDownload;

/**
 * Created by PhpStorm.
 * User: gogl92
 * Date: 12/3/18
 * Time: 11:12 PM
 */
class DownloadHandler
{
    public string $downloadPath = '/tmp/facturas/';
    public int $downloadMaxSimultaneous = 10;
    public DescargaMasivaCfdi $massiveCfdiDownloadService;
    public string $cerFile;
    public string $keyFile;
    public string $password;

    /**
     * Initialize with the credentials.
     */
    public function __construct(string $cerFile, string $keyFile, string $password)
    {
        $this->massiveCfdiDownloadService = new DescargaMasivaCfdi();

        $this->cerFile = $cerFile;
        $this->keyFile = $keyFile;
        $this->password = $password;
    }

    /**
     * Logins into the SAT webservice.
     *
     * @return bool
     */
    public function login(): bool
    {
        $certificateUtils = new UtilCertificado();
        $filesLoadedSuccessfully = $certificateUtils->loadFiles(
            $this->cerFile,
            $this->keyFile,
            $this->password
        );

        if ($filesLoadedSuccessfully) {
            $startSessionWithFiel = $this->massiveCfdiDownloadService->iniciarSesionFiel($certificateUtils);
            if ($startSessionWithFiel) {

                return true;
            }
        }

        return false;
    }

    /**
     * Search Incoming bills.
     *
     * @param string|int $year
     * @param string|int $month
     * @param string|int|null $dia
     * @return array
     */
    public function searchForIncomingCfdis($year, $month, $dia = null): array
    {
        $filters = new BusquedaRecibidos();
        $filters->establecerFecha($year, $month, $dia);

        $xmlInfoArr = $this->massiveCfdiDownloadService->buscar($filters);
        if ($xmlInfoArr) {
            $items = [];
            foreach ($xmlInfoArr as $xmlInfo) {
                $items[] = (array)$xmlInfo;
            }

            return $items;
        }

        return ['error' => 'Error in Login'];
    }

    /**
     * @param $startYear
     * @param $startMonth
     * @param $startDay
     * @param $endYear
     * @param $endMonth
     * @param $endDay
     * @return array
     */
    public function searchForCreatedCfdis($startYear, $startMonth, $startDay, $endYear, $endMonth, $endDay): array
    {
        $filters = new BusquedaEmitidos();
        $filters->establecerFechaInicial($startYear, $startMonth, $startDay);
        $filters->establecerFechaFinal($endYear, $endMonth, $endDay);

        $xmlInfoArr = $this->massiveCfdiDownloadService->buscar($filters);
        if ($xmlInfoArr) {
            $items = array();
            foreach ($xmlInfoArr as $xmlInfo) {
                $items[] = (array)$xmlInfo;
            }

            return [
                'items' => $items,
                'sesion' => $this->massiveCfdiDownloadService->obtenerSesion()
            ];
        }

        return array(
            'mensaje' => 'No se han encontrado CFDIs',
            'sesion' => $this->massiveCfdiDownloadService->obtenerSesion()
        );
    }

    /**
     * Download the given xmls
     *
     * @param array $xmls
     * @return bool
     */
    public function downloadXmls(array $xmls): bool
    {
        $asyncDownload = new DescargaAsincrona($this->downloadMaxSimultaneous);
        foreach ($xmls as $xml) {
            $asyncDownload->agregarXml($xml['urlDescargaXml'], $this->downloadPath, $xml['folioFiscal']);
        }

        return $asyncDownload->procesar();
    }
}
