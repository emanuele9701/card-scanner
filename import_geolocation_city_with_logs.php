<?php

// Before run
$places = [];

/**
 * NAZIONI
 */
$collectionNazioni = Deep::getResourceModel('custom_geolocation/country_collection');

foreach ($collectionNazioni as $country) {

    $countryName = trim(strtoupper($country->getName()));

    if (!isset($places[$countryName])) {
        $places[$countryName] = [
            'entity'      => $country,
            'regions' => []
        ];
    }

    /**
     * REGIONI
     */
    $regionCollection = Deep::getResourceModel('custom_geolocation/region_collection')
        ->addFieldToFilter('cust_country_id', ['eq' => $country->getId()]);

    foreach ($regionCollection as $region) {

        $regionName = trim(strtoupper($region->getData('region')));

        if (!isset($places[$countryName]['regions'][$regionName])) {
            $places[$countryName]['regions'][$regionName] = [
                'entity'        => $region,
                'provinces' => []
            ];
        }

        /**
         * PROVINCE
         */
        $provinceCollection = Deep::getResourceModel('custom_geolocation/province_collection')
            ->addFieldToFilter('cust_region_id', ['eq' => $region->getId()]);

        foreach ($provinceCollection as $province) {

            $provinceName = trim(strtoupper($province->getData('name')));

            if (!isset($places[$countryName]['regions'][$regionName]['provinces'][$provinceName])) {
                $places[$countryName]['regions'][$regionName]['provinces'][$provinceName] = [
                    'entity'     => $province,
                    'cities' => []
                ];
            }

            /**
             * CITTÀ
             */
            $citiesCollection = Deep::getResourceModel('custom_geolocation/city_collection')
                ->addFieldToFilter('cust_province_id', ['eq' => $province->getId()]);

            foreach ($citiesCollection as $city) {

                $cityName = trim(strtoupper($city->getData('name')));

                if (!isset($places[$countryName]['regions'][$regionName]['provinces'][$provinceName]['cities'][$cityName])) {
                    $places[$countryName]['regions'][$regionName]['provinces'][$provinceName]['cities'][$cityName] = [
                        'entity'   => $city,
                        'zips' => []
                    ];
                }

                /**
                 * ZIP
                 */
                $zipsCollection = Deep::getResourceModel('custom_geolocation/zip_collection')
                    ->addFieldToFilter('cust_city_id', ['eq' => $city->getId()]);

                foreach ($zipsCollection as $zip) {

                    $zipName = trim(strtoupper($zip->getData('name')));

                    if (!isset(
                        $places[$countryName]['regions'][$regionName]['provinces'][$provinceName]['cities'][$cityName]['zips'][$zipName]
                    )) {
                        $places[$countryName]['regions'][$regionName]['provinces'][$provinceName]['cities'][$cityName]['zips'][$zipName] = [
                            'id' => $zip->getId()
                        ];
                    }
                }
            }
        }
    }
}


// Before row
// Funzione anonima che cicla array $places
$validatePlace = function (
    array $places,
    string $country,
    string $region,
    string $province,
    string $city,
    ?array $zips
) {

    $country  = strtoupper(trim($country));
    $region   = strtoupper(trim($region));
    $province = strtoupper(trim($province));
    $city     = strtoupper(trim($city));
    $zips     = $zips !== null ? array_map(fn($z) => strtoupper(trim($z)), $zips) : [];

    /* COUNTRY */
    if (!isset($places[$country])) {
        return [
            'ok'      => false,
            'level'   => 'country',
            'missing' => $country
        ];
    }

    /* REGION */
    if (!isset($places[$country]['regions'][$region])) {
        return [
            'ok'      => false,
            'level'   => 'region',
            'missing' => $region
        ];
    }

    /* PROVINCE */
    if (!isset($places[$country]['regions'][$region]['provinces'][$province])) {
        return [
            'ok'      => false,
            'level'   => 'province',
            'missing' => $province
        ];
    }

    /* CITY */
    if (!isset(
        $places[$country]['regions'][$region]['provinces'][$province]['cities'][$city]
    )) {
        return [
            'ok'      => false,
            'level'   => 'city',
            'missing' => $city
        ];
    }

    /* ZIPS */
    $existingZips = array_keys(
        $places[$country]['regions'][$region]['provinces'][$province]['cities'][$city]['zips']
    );

    $missingZips = array_values(array_diff($zips, $existingZips));

    if (!empty($missingZips)) {
        return [
            'ok'      => false,
            'level'   => 'zips',
            'missing' => $missingZips
        ];
    }

    return [
        'ok'      => true,
        'level'   => null,
        'missing' => false
    ];
};

$getPlaceId = function (
    array $places,
    string $level,
    string $country,
    ?string $region = null,
    ?string $province = null,
    ?string $city = null
) {
    $level    = strtolower($level);
    $country  = strtoupper(trim($country));
    $region   = $region !== null ? strtoupper(trim($region)) : null;
    $province = $province !== null ? strtoupper(trim($province)) : null;
    $city     = $city !== null ? strtoupper(trim($city)) : null;

    /* COUNTRY */
    if ($level === 'country') {
        return $places[$country]['id'] ?? null;
    }

    if (!isset($places[$country])) {
        return null;
    }

    /* REGION */
    if ($level === 'region') {
        return $places[$country]['regions'][$region]['id'] ?? null;
    }

    if (!isset($places[$country]['regions'][$region])) {
        return null;
    }

    /* PROVINCE */
    if ($level === 'province') {
        return $places[$country]['regions'][$region]['provinces'][$province]['id'] ?? null;
    }

    if (!isset($places[$country]['regions'][$region]['provinces'][$province])) {
        return null;
    }

    /* CITY */
    if ($level === 'city') {
        return $places[$country]['regions'][$region]['provinces'][$province]['cities'][$city]['id'] ?? null;
    }

    return null;
};



// Prima di eseguire la riga verifico se Nazione, Regione, Provincia sono stati creati. Se non lo sono stati allora provvederò alla loro creazione.
$cityName = trim(strtoupper($data[15]));
$provinceName = trim(strtoupper($data[16]));
$regionName = trim(strtoupper($data[17]));
$macroRegionName = trim(strtoupper($data[18]));
$countryName = trim(strtoupper($data[19]));
$zips = json_decode(trim(strtoupper($data[20])), true);

if (empty($cityName) || strlen($cityName) === 0) {
    $model = null;
    $errorsMap[$line]['warning'][] = Deep::helper('deep_import')->__("WARNING: City non è valorizzato.");
}

// PROVINCE
if (empty($provinceName) || strlen($provinceName) === 0) {
    $model = null;
    $errorsMap[$line]['warning'][] =
        Deep::helper('deep_import')->__("WARNING: Province non è valorizzato.");
}

// REGION
if (empty($regionName) || strlen($regionName) === 0) {
    $model = null;
    $errorsMap[$line]['warning'][] =
        Deep::helper('deep_import')->__("WARNING: Region non è valorizzato.");
}

// MACRO REGION
if (empty($macroRegionName) || strlen($macroRegionName) === 0) {
    $model = null;
    $errorsMap[$line]['warning'][] =
        Deep::helper('deep_import')->__("WARNING: Macro Region non è valorizzato.");
}

// COUNTRY
if (empty($countryName) || strlen($countryName) === 0) {
    $model = null;
    $errorsMap[$line]['warning'][] =
        Deep::helper('deep_import')->__("WARNING: Country non è valorizzato.");
}

$countryId = $regionId = $provinceId = null;
if ($model != null) {

    $missingData = $validatePlace($places, $countryName, $regionName, $provinceName, $cityName, $zips);

    if (!$missingData['ok']) {

        if ($missingData['level'] == 'country') {
            // Creazione del country
            $country = Deep::getModel('custom_geolocation/country');
            $country->setData('name', $countryName);
            $country->save();
            Deep::log(
                'Creazione Country: ' . $countryName,
                null,
                'import_geolocation_city.log'
            );


            $region = Deep::getModel('custom_geolocation/region');
            $region->setData('name', $regionName);
            $region->setData('region', $regionName);
            $region->setData('cust_country_id', $country->getId());
            $region->save();
            Deep::log(
                'Creazione Region: ' . $regionName .
                    ' | Country: ' . $countryName,
                null,
                'import_geolocation_city.log'
            );

            $province = Deep::getModel('custom_geolocation/province');
            $province->setData('name', $provinceName);
            $province->setData('cust_region_id', $region->getId());
            $province->save();
            Deep::log(
                'Creazione Province: ' . $provinceName .
                    ' | Region: ' . $regionName,
                null,
                'import_geolocation_city.log'
            );

            $places[$countryName] = [
                'entity' => $country,
                'regions' => [
                    $regionName => [
                        'entity' => $region,
                        'provinces' => [
                            $provinceName => [
                                'entity' => $province,
                                'cities' => []
                            ]
                        ]
                    ]
                ]
            ];

            $countryId = $country->getId();
            $regionId = $region->getId();
            $provinceId = $province->getId();
        }
    }
} elseif ($missingData['level'] === 'region') {

    $countryId = $getPlaceId($places, 'country', $countryName);

    $region = Deep::getModel('custom_geolocation/region');
    $region->setData('name', $regionName);
    $region->setData('region', $regionName);
    $region->setData('cust_country_id', $countryId);
    $region->save();
    Deep::log(
        'Creazione Region: ' . $regionName .
            ' | Country: ' . $countryName,
        null,
        'import_geolocation_city.log'
    );

    $province = Deep::getModel('custom_geolocation/province');
    $province->setData('name', $provinceName);
    $province->setData('cust_region_id', $region->getId());
    $province->save();
    Deep::log(
        'Creazione Province: ' . $provinceName .
            ' | Region: ' . $regionName,
        null,
        'import_geolocation_city.log'
    );

    $places[$countryName]['regions'][$regionName] = [
        'entity' => $region,
        'provinces' => [
            $provinceName => [
                'entity' => $province,
                'cities' => []
            ]
        ]
    ];
    $countryId = $countryId;
    $regionId = $region->getId();
    $provinceId = $province->getId();
} elseif ($missingData['level'] === 'province') {

    $countryId = $getPlaceId($places, 'country', $countryName);
    $regionId  = $getPlaceId($places, 'region', $countryName, $regionName);

    $province = Deep::getModel('custom_geolocation/province');
    $province->setData('name', $provinceName);
    $province->setData('cust_region_id', $regionId);
    $province->save();
    Deep::log(
        'Creazione Province: ' . $provinceName .
            ' | Region: ' . $regionName,
        null,
        'import_geolocation_city.log'
    );

    $places[$countryName]['regions'][$regionName]['provinces'][$provinceName] = [
        'entity' => $province,
        'cities' => []
    ];


    $countryId = $countryId;
    $regionId = $regionId;
    $provinceId = $province->getId();
}


// After row
if (is_array($zips) && $model && !empty($zips) && $model->getId()) {
    $zipsCreated = [];
    foreach ($zips ?? [] as $zipN) {
        $zip = Deep::getModel('custom_geolocation/zip');
        $zip->setData('name', $zipN);
        $zip->setData('cust_city_id', $model->getId());
        $zip->save();
        Deep::log(
            'Creazione ZIP: ' . $zipN .
                ' | City: ' . $cityName,
            null,
            'import_geolocation_city.log'
        );

        $zipsCreated[$zipN] = ['id' => $zip->getId()];
    }

    $places[$countryName]['regions'][$regionName] = [
        'entity' => $region,
        'provinces' => [
            $provinceName => [
                'entity' => $province,
                'cities' => [
                    $cityName => [
                        'entity' => $city,
                        'zips' => $zipsCreated
                    ]
                ]
            ]
        ]
    ];
}
