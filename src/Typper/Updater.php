<?php
/**
 * Typper Updater
 */

namespace Typper;

class Updater
{
    /**
     * Checks for updates and performs the update if possible.
     * @return array ['success' => bool, 'message' => string]
     */
    public static function update(): array
    {
        $rootDir = realpath(__DIR__ . '/../../');
        
        $ch = curl_init('https://api.github.com/repos/laraantunes/typper/releases/latest');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Typper-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $apiResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (!$apiResponse || $httpCode !== 200) {
            return ['success' => false, 'message' => "Falha ao buscar a última versão no GitHub. HTTP: $httpCode"];
        }
        
        $releaseData = json_decode($apiResponse, true);
        $assets = $releaseData['assets'] ?? [];
        
        $zipUrl = '';
        foreach ($assets as $asset) {
            if (strpos($asset['name'], 'typper-release.zip') !== false) {
                $zipUrl = $asset['browser_download_url'];
                break;
            }
        }
        
        if (empty($zipUrl)) {
            return ['success' => false, 'message' => "Nenhum arquivo de build (.zip) encontrado na última versão."];
        }
        
        $cacheDir = $rootDir . '/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $tempZip = $cacheDir . '/typper-release-temp.zip';
        
        // Usando cURL para baixar o ZIP
        $ch = curl_init($zipUrl);
        $fp = fopen($tempZip, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Typper-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 segundos max para baixar
        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        
        if ($curlError || filesize($tempZip) < 1024) {
            if (file_exists($tempZip)) unlink($tempZip);
            return ['success' => false, 'message' => "Falha ao baixar o arquivo da versão. Erro: $curlError"];
        }
        
        $zip = new \ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            $zip->extractTo($rootDir);
            $zip->close();
            unlink($tempZip);
            
            // Clear cache
            try {
                $loader = new Loader();
                $loader->clear();
            } catch (\Exception $e) {
                // Ignore cache clear error
            }
            
            return ['success' => true, 'message' => 'Aplicação atualizada com sucesso para a ' . $releaseData['tag_name']];
        } else {
            if (file_exists($tempZip)) unlink($tempZip);
            return ['success' => false, 'message' => "Falha ao extrair o arquivo .zip da atualização."];
        }
    }
}
