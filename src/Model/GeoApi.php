<?php

namespace Olivierguissard\EcoRide\Model;

class GeoApi
{
    private string $api_key;

    /**
     * Constructor method to initialize the class with an API key.
     *
     * @param string $api_key The API key required for authentication or configuration.
     * @return void
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;
    }
    public function getCities(string $cityName): array
    {
        $curl = curl_init("https://geo.api.gouv.fr/communes?nom={$cityName}&fields=departement&boost=population&limit=5");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        $data = curl_exec($curl);
        if ($data === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
            return null;
        } else {
            $results = [];
            $data = json_decode($data, true);
            $results = $data['departement']['nom'];
        }
    }
}