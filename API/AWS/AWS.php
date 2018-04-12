<?php
/*
 * Copyright 2013. Amazon Web Services, Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

// Open credentials file
$credentialsFile = fopen('../aws_credentials', 'r');

if (!$credentialsFile) {
    echo "ERROR: Could not open AWS credentials\n";
    die;
}

// Read credentials file
$credentials = fgets($credentialsFile);

// Close secrets file
fclose($credentialsFile);

// Create an array from the retrieved string
$credentials = explode(',', $credentials);
$key         = trim($credentials[0]);
$secret      = trim($credentials[1]);

$options = [
    'version'     => 'latest',
    'region'      => 'us-west-2',
    'credentials' => [
        'key'     => $key,
        'secret'  => $secret,
    ],
];

$comprehend = new Aws\Comprehend\ComprehendClient($options);

$rekognition = new Aws\Rekognition\RekognitionClient($options);

function rekognition($imageURL)
{
    global $rekognition;

    $result = $rekognition->detectLabels([
        'Image' => [
            'Bytes' => $imageURL,
        ],
        'MaxLabels' => 100,
        'MinConfidence' => 70,
    ]);

    $tagArray = [];

    foreach ($result['Labels'] as $labels) {
        $tagArray[] = $labels['Name'];
    }

    return $tagArray;
}

function comprehend($bodyText)
{
    global $comprehend;

    $result = $comprehend->detectEntities([
        'LanguageCode' => 'en', // REQUIRED
        'Text'         => $bodyText, // REQUIRED
    ]);

    $tagArray = [];

    foreach ($result['Entities'] as $entities) {
        $tagArray[] = $entities['Text'];
    }

    return $tagArray;
}
