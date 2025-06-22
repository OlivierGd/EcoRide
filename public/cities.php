<?php
$curl = curl_init('https://geo.api.gouv.fr/communes?nom=Paris&fields=departement&boost=population&limit=5');

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 3,
]);

$data = curl_exec($curl);

if ($data === false) {
    var_dump(curl_error($curl));
    curl_close($curl);
} else {
    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200) {
        $data = json_decode($data, true);
        echo '<pre>';
        var_dump($data);
        echo '<pre>';
        curl_close($curl);
    }
}
curl_close($curl);