<?php

namespace Airalo\Helpers;


use Airalo\Exceptions\AiraloException;

final class CloudSimShareValidator
{
    private static string $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    private static array $allowedSharingOptions = ['link', 'pdf'];

    /**
     * @param $date
     * @param $format
     * @return bool
     */
    public static function validate(array $simCloudShare, array $requiredFields = []): bool
    {
        self::checkRequiredFields($simCloudShare, $requiredFields);

        if (isset($simCloudShare['to_email']) && !preg_match(self::$emailRegex, $simCloudShare['to_email'])) {
            throw new AiraloException('The to_email must be valid email address, payload: ' . json_encode($simCloudShare));
        }

        foreach ($simCloudShare['sharing_option'] ?? [] as $sharingOption) {
            if (!in_array($sharingOption, self::$allowedSharingOptions)) {
                throw new AiraloException('The sharing_option may be '.implode(' or ', self::$allowedSharingOptions).' or both, payload: ' . json_encode($simCloudShare));
            }
        }

        foreach ($simCloudShare['copy_address'] ?? [] as $eachCCemail) {
            if (!preg_match(self::$emailRegex, $eachCCemail)) {
                throw new AiraloException("The copy_address: $eachCCemail must be valid email address, payload: " . json_encode($simCloudShare));
            }
        }

        return true;
    }

    private static function checkRequiredFields(array $simCloudShare, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (empty($simCloudShare[$field])) {
                throw new AiraloException("The $field is required, payload: " . json_encode($simCloudShare));
            }
        }

        return true;
    }
}
