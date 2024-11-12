<?php
require_once '../utils/HttpClient.php';
require_once '../models/GoodWe.php';

class GoodWeService {
    private $goodWe;
    private $httpClient;

    public function __construct() {
        $this->goodWe = new GoodWeTokenAuthentified();
        $this->httpClient = new HttpClient();
    }
    
    public function GetAllPlants() {
        $url = $this->goodWe->getUrl() . "api/PowerStationMonitor/QueryPowerStationMonitor";

        // Token en formato JSON
        $tokenData = [
            'uid' => $this->goodWe->getUid(),
            'timestamp' => $this->goodWe->getTimestamp(),
            'token' => $this->goodWe->getToken(),
            'client' => $this->goodWe->getClient(),
            'version' => $this->goodWe->getVersion(),
            'language' => $this->goodWe->getLanguage()
        ];

        $headers = [
            'Content-Type: application/json',
            'Token: ' . json_encode($tokenData)
        ];

        $data = [
            "key" => "",
            "orderby" => "",
            "powerstation_type" => "",
            "powerstation_status" => "",
            "page_index" => 1,
            "page_size" => 10000,
            "adcode" => "",
            "org_id" => "",
            "condition" => ""
        ];

        try {
            // Realiza la primera solicitud
            $response = $this->httpClient->post($url, $headers, json_encode($data));
            $decodedResponse = json_decode($response, true);

            // Verificar si la respuesta indica que la autorización ha caducado
            if (isset($decodedResponse['code']) && $decodedResponse['code'] === 100002) {
                // Realizar login para obtener nuevos datos de autorización
                $newTokenData = $this->crossLogin();

                if (isset($newTokenData['uid'])) {
                    // Actualizar los datos del token
                    $this->goodWe->setUid($newTokenData['uid']);
                    $this->goodWe->setTimestamp($newTokenData['timestamp']);
                    $this->goodWe->setToken($newTokenData['token']);

                    // Reintentar la solicitud con los nuevos datos
                    $tokenData['uid'] = $newTokenData['uid'];
                    $tokenData['timestamp'] = $newTokenData['timestamp'];
                    $tokenData['token'] = $newTokenData['token'];
                    $headers[1] = 'Token: ' . json_encode($tokenData);

                    // Segunda solicitud con el nuevo token
                    $response = $this->httpClient->post($url, $headers, json_encode($data));
                    $decodedResponse = json_decode($response, true);
                } else {
                    throw new Exception("No se pudo obtener el nuevo token de autorización.");
                }
            }

            return $decodedResponse;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function GetPlantDetailByPowerstationId($powerStationId) {
        $url = $this->goodWe->getUrl() . "api/v3/PowerStation/GetPlantDetailByPowerstationId";
    
        // Token en formato JSON
        $tokenData = [
            'uid' => $this->goodWe->getUid(),
            'timestamp' => $this->goodWe->getTimestamp(),
            'token' => $this->goodWe->getToken(),
            'client' => $this->goodWe->getClient(),
            'version' => $this->goodWe->getVersion(),
            'language' => $this->goodWe->getLanguage()
        ];
    
        $headers = [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Token: ' . json_encode($tokenData)
        ];
    
        $data = [
            'powerStationId' => $powerStationId
        ];
    
        try {
            // Realiza la primera solicitud
            $response = $this->httpClient->get($url, $headers, $data);
            $decodedResponse = json_decode($response, true);
    
            // Verificar si la respuesta indica que la autorización ha caducado
            if (isset($decodedResponse['code']) && $decodedResponse['code'] === 100002) {
                // Realizar login para obtener nuevos datos de autorización
                $newTokenData = $this->crossLogin();
    
                if (isset($newTokenData['uid'])) {
                    // Actualizar los datos del token
                    $this->goodWe->setUid($newTokenData['uid']);
                    $this->goodWe->setTimestamp($newTokenData['timestamp']);
                    $this->goodWe->setToken($newTokenData['token']);
    
                    // Reintentar la solicitud con los nuevos datos
                    $tokenData['uid'] = $newTokenData['uid'];
                    $tokenData['timestamp'] = $newTokenData['timestamp'];
                    $tokenData['token'] = $newTokenData['token'];
                    $headers[1] = 'Token: ' . json_encode($tokenData);
    
                    // Segunda solicitud con el nuevo token
                    $response = $this->httpClient->get($url, $headers, $data);
                    return $response;
                } else {
                    throw new Exception("No se pudo obtener el nuevo token de autorización.");
                }
            }
    
            return $decodedResponse;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function crossLogin() {
        $url = $this->goodWe->getUrl() . "api/v1/Common/CrossLogin";

        // Datos de la solicitud en el cuerpo
        $data = [
            'account' => $this->goodWe->getAccount(),
            'pwd' => $this->goodWe->getPwd()
        ];

        // Headers
        $headers = [
            'Content-Type: application/json',
            'Token: ' . json_encode([
                'version' => 'v2.1.0',
                'client' => 'ios',
                'language' => 'en'
            ])
        ];

        try {
            // Realiza la solicitud POST con los datos y encabezados
            $response = $this->httpClient->post($url, $headers, json_encode($data));

            // Decodifica la respuesta JSON
            $responseData = json_decode($response, true);

            // Verifica si la respuesta es exitosa y contiene los datos necesarios
            if (isset($responseData['hasError']) && $responseData['hasError'] === false && isset($responseData['data'])) {
                return [
                    'uid' => $responseData['data']['uid'],
                    'timestamp' => $responseData['data']['timestamp'],
                    'token' => $responseData['data']['token'],
                    'client' => $responseData['data']['client'],
                    'version' => $responseData['data']['version'],
                    'language' => $responseData['data']['language']
                ];
            } else {
                throw new Exception("Login fallido: " . ($responseData['msg'] ?? 'Error desconocido'));
            }

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

?>