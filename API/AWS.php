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


/*
$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => 'us-west-2',
	'credentials' => [
        'key'    => 'AKIAI5WUEZZCJRGSXTXQ',
        'secret' => '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'
    ]
]);
*/

$comprehend = new Aws\Comprehend\ComprehendClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
	'credentials' => [
        'key'    => 'AKIAI5WUEZZCJRGSXTXQ',
        'secret' => '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'
    ]
]);

function comprehend($body)
{
	
	$result = $comprehend->detectKeyPhrases([
		'LanguageCode' => 'en', // REQUIRED
		'Text' => '$body', // REQUIRED
	]);

	$tagArray = [];

	foreach($result['KeyPhrases'] as $keyPhrase) {
		$tagArray[] = $keyPhrase['Text'];
	}
	/*
	print(":::");
	var_dump($tagArray);
	*/
	
	return $tagArray;

}
